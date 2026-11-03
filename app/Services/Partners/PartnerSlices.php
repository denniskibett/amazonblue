<?php

namespace App\Services\Partners;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\Loans\LoanFundings;
use App\Services\Loans\RepaymentSplit;
use App\Services\PartnerLedger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Who funded which part of a loan, recorded the way it happened (owner, 3 Nov 2026).
 *
 *  - slices(): a loan's funders as slices of its money, each tied to the disbursement it paid for and the line it
 *    came from (null line = the house). They replace the loan's loan_fundings rows; their total must stay the same,
 *    so the house's part is what is left. Every repayment of the loan is then re-split (by difference: new journal
 *    rows, nothing edited) and every line touched has its lent and returned figures recomputed.
 *  - settleByHouse(): the house pays a partner out of a slice before the borrower has repaid it (a facility that
 *    rolled over, a partner who wanted their money back). The partner earns their share of that slice's cycle
 *    interest on the day, gets the rest of their capital back on their line, and from that day the slice is the
 *    house's: later repayments of the loan share nothing with that partner.
 * Both run in one transaction and can be previewed with preview(): run, read, roll back.
 */
class PartnerSlices
{
    public function __construct(private LoanFundings $fundings, private RepaymentSplit $split) {}

    /**
     * @param array<int, array{line: ?int, amount: float, disbursement: ?int}> $slices  null line = the house
     * @return string[] what changed
     */
    public function slices(Loan $loan, array $slices, ?User $by = null, string $why = 'partner_slices'): array
    {
        return DB::transaction(function () use ($loan, $slices, $by, $why) {
            $loan = Loan::withTrashed()->lockForUpdate()->findOrFail($loan->id);
            $old = $this->fundings->rows($loan->id);
            $total = round((float) $old->sum('amount'), 2);
            if ($old->isEmpty()) {
                $total = round((float) Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->sum('amount'), 2);
            }
            $sum = round(array_sum(array_map(fn ($s) => (float) $s['amount'], $slices)), 2);
            if (abs($sum - $total) > 0.009) {
                throw new \DomainException('The slices add up to KES ' . number_format($sum, 2) . '; loan #' . $loan->id . ' is KES ' . number_format($total, 2) . '. Give the house what is left.');
            }
            if ($old->contains(fn ($r) => $r->settled_on ?? null)) {
                throw new \DomainException('Loan #' . $loan->id . ' has a slice the house already settled; its slices can no longer be changed here.');
            }
            $houseLine = Investment::where('return_type', 'house')->orderBy('id')->value('id');
            $lines = [];
            DB::table('loan_fundings')->where('loan_id', $loan->id)->delete();
            foreach ($slices as $s) {
                $line = $s['line'] ? Investment::findOrFail($s['line']) : Investment::find($houseLine);
                $amount = round((float) $s['amount'], 2);
                if ($amount <= 0) {
                    continue;
                }
                if ($s['disbursement'] && ! Disbursement::where('id', $s['disbursement'])->where('loan_id', $loan->id)->exists()) {
                    throw new \DomainException('Disbursement #' . $s['disbursement'] . ' is not on loan #' . $loan->id . '.');
                }
                DB::table('loan_fundings')->insert(['loan_id' => $loan->id, 'investment_id' => $line?->id, 'partner_id' => LoanFundings::partnerOf($line),
                    'amount' => $amount, 'share' => $total > 0 ? round($amount / $total, 6) : 1, 'disbursement_id' => $s['disbursement'] ?: null,
                    'source' => 'slices', 'notes' => $why, 'created_at' => now(), 'updated_at' => now()]);
                if ($line && ! $line->isHouseLine()) {
                    $lines[$line->id] = $line;
                }
            }
            foreach ($old as $r) {
                if ($r->investment_id && ($l = Investment::find($r->investment_id)) && ! $l->isHouseLine()) {
                    $lines[$l->id] = $l;
                }
            }
            // a disbursement paid for by one partner line wholly says so (statements, the line's loans)
            foreach (collect($slices)->filter(fn ($s) => $s['disbursement'] && $s['line'])->groupBy('disbursement') as $dId => $parts) {
                $d = Disbursement::find($dId);
                if ($parts->count() === 1 && abs((float) $parts->first()['amount'] - (float) $d->amount) < 0.01) {
                    $d->forceFill(['investment_id' => $parts->first()['line'], 'funding_source' => 'partner'])->saveQuietly();
                }
            }
            foreach ($loan->cycles()->pluck('id') as $cycleId) {
                $this->split->syncCycle((int) $cycleId);
            }
            foreach ($lines as $line) {
                $this->syncDraws($line);
                $this->split->syncLineReturns($line->fresh());
            }
            $changes = array_map(fn ($s) => 'KES ' . number_format((float) $s['amount'], 2) . ' from ' . ($s['line'] ? Investment::whereKey($s['line'])->value('name') : 'the house')
                . ($s['disbursement'] ? ' (disbursement #' . $s['disbursement'] . ')' : ''), $slices);
            AuditTrail::record('loans', $loan->id, 'update', ['fundings' => $old->map(fn ($r) => [$r->investment_id, (float) $r->amount])->all()],
                ['event' => $why, 'slices' => $changes, 'by' => $by?->id]);

            return $changes;
        });
    }

    /**
     * The partner's share of the interest of the cycle a slice was lent in, on what is still theirs: the default the
     * house pays when it settles the slice (60% x 20% x 10,000 = 1,200).
     */
    public function shareDue(object $row): float
    {
        $line = Investment::find($row->investment_id);
        $rate = (float) ($line?->partner_share_rate ?? Partner::whereKey($row->partner_id)->value('profit_share_rate'));
        $day = $row->disbursement_id ? Disbursement::whereKey($row->disbursement_id)->value('disburse_date') : null;
        $cycle = LoanCycle::where('loan_id', $row->loan_id)->when($day, fn ($q) => $q->whereDate('start_date', '<=', $day)->whereDate('due_date', '>=', $day))
            ->orderByDesc('cycle_number')->first() ?? LoanCycle::where('loan_id', $row->loan_id)->orderByDesc('cycle_number')->first();

        return round((float) $row->amount * (float) ($cycle->interest_rate ?? 0) / 100 * $rate / 100, 2);
    }

    /** The house pays the partner out of one slice on $date: their interest share now, the rest of their capital on their line. */
    public function settleByHouse(int $rowId, string $date, ?float $interest = null, ?User $by = null, ?string $note = null): array
    {
        return DB::transaction(function () use ($rowId, $date, $interest, $by, $note) {
            $row = DB::table('loan_fundings')->lockForUpdate()->find($rowId) ?? throw new \DomainException('No such slice.');
            $partner = Partner::find($row->partner_id);
            if (! $partner || $partner->is_house_account) {
                throw new \DomainException('That slice is the house\'s already.');
            }
            if ($row->settled_on) {
                throw new \DomainException('The house settled that slice on ' . Carbon::parse($row->settled_on)->format('j M Y') . '.');
            }
            if (! $this->fundings->isPartFunded((int) $row->loan_id)) {
                throw new \DomainException('Loan #' . $row->loan_id . ' has one funder; give the house its part first (Slices), then settle the partner\'s.');
            }
            $date = Carbon::parse($date)->toDateString();
            $interest = round($interest ?? $this->shareDue($row), 2);
            $journal = $interest > 0 ? app(PartnerLedger::class)->post($partner, 'interest', 'profit_distribution', $interest,
                loanId: (int) $row->loan_id, investmentId: $row->investment_id, reference: 'SETTLE-LF' . $row->id,
                notes: 'House settled ' . $partner->name . '\'s KES ' . number_format((float) $row->amount, 2) . ' of loan #' . $row->loan_id . ': their share of the interest'
                    . ($note ? ' · ' . $note : ''), transactionDate: $date, force: true) : null;
            DB::table('loan_fundings')->where('id', $row->id)->update(['settled_on' => $date, 'settled_by' => 'house', 'settled_interest' => $interest,
                'settled_transaction_id' => $journal?->id, 'updated_at' => now()]);
            // payments after $date share nothing with the partner; their line gets its capital back
            foreach (LoanCycle::where('loan_id', $row->loan_id)->pluck('id') as $cycleId) {
                $this->split->syncCycle((int) $cycleId);
            }
            if ($line = Investment::find($row->investment_id)) {
                $this->split->syncLineReturns($line);
            }
            AuditTrail::record('loan_fundings', $row->id, 'update', ['settled_on' => null], ['event' => 'house_settled_slice', 'loan_id' => $row->loan_id,
                'partner_id' => $partner->id, 'amount' => (float) $row->amount, 'interest' => $interest, 'date' => $date, 'by' => $by?->id, 'note' => $note]);

            return ['partner' => $partner->name, 'loan_id' => (int) $row->loan_id, 'capital' => (float) $row->amount, 'interest' => $interest, 'date' => $date];
        });
    }

    /** A partner line's lent figure is what its slices put into loans (recomputed, never incremented). */
    public function syncDraws(Investment $line): void
    {
        $lent = round((float) DB::table('loan_fundings')->where('investment_id', $line->id)->sum('amount'), 2);
        $funding = InvestmentFunding::where('investment_id', $line->id)->whereHas('partner', fn ($q) => $q->where('is_house_account', false))->orderBy('id')->first();
        $funding?->forceFill(['amount_disbursed' => $lent])->save();
    }

    /** Runs $fn and returns what it did to the journal and the line figures, then rolls everything back. */
    public function preview(callable $fn): array
    {
        $before = DB::table('partner_transactions')->max('id') ?? 0;
        DB::beginTransaction();
        try {
            $result = $fn();
            $rows = DB::table('partner_transactions as t')->join('partners as p', 'p.id', '=', 't.partner_id')->where('t.id', '>', $before)
                ->orderBy('t.id')->get(['p.name', 't.wallet_slug', 't.type', 't.amount', 't.loan_id', 't.reference', 't.transaction_date']);
            $lines = DB::table('investment_fundings as f')->join('investments as i', 'i.id', '=', 'f.investment_id')->where('i.return_type', 'loan_share')
                ->get(['i.id', 'i.name', 'f.amount_committed', 'f.amount_disbursed', 'f.amount_returned']);

            return ['result' => $result, 'journal' => $rows, 'lines' => $lines];
        } finally {
            DB::rollBack();
        }
    }
}
