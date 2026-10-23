<?php

namespace App\Services\Partners;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Partner funding lines (owner, 23 Oct 2026).
 *
 * A partner sends money (recorded from the M-Pesa SMS) and it becomes a line of its own:
 *  - short term: funds one or a few loans and comes back when they are repaid (by the loan's due date, or a date
 *    staff set). When every loan it funded is closed, the line closes and a payout of what it is owed waits on
 *    Requests for staff to send, so the partner's wallet goes back to 0 until the next time;
 *  - long term: funds many loans until its maturity date. Principal paid back is lent again; the partner earns
 *    their profit-share % of the interest on every loan it funds (RepaymentSplit). At maturity, once its loans are
 *    closed, it closes and pays out the same way.
 * Interest shares and principal returns are posted by RepaymentSplit on every repayment; this class only opens,
 * closes and pays out lines. A loss (a written-off loan) is never hidden: the shortfall is on the payout request.
 */
class PartnerLines
{
    public const TERMS = ['short_term' => 'Short term', 'long_term' => 'Long term'];

    /** Open a line from money the partner sent. $code is the M-Pesa (or bank) reference; it can be used once. */
    public function open(Partner $partner, float $amount, string $term, ?string $maturity, string $code, ?string $date = null, ?User $by = null, ?string $note = null): Investment
    {
        if ($partner->is_house_account) {
            throw new \DomainException('The house account lends AC\'s own money; it has no partner lines.');
        }
        if (! array_key_exists($term, self::TERMS)) {
            throw new \DomainException('Choose short term or long term.');
        }
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \DomainException('The amount must be more than zero.');
        }
        $code = strtoupper(trim($code));
        if ($code === '') {
            throw new \DomainException('Enter the M-Pesa or bank reference.');
        }
        if ($this->codeUsed($code)) {
            throw new \DomainException("{$code} has already been recorded as partner money.");
        }
        $date = $date ?: now()->toDateString();
        if ($term === 'long_term' && ! $maturity) {
            throw new \DomainException('A long-term line needs the date it is to be paid back.');
        }

        return DB::transaction(function () use ($partner, $amount, $term, $maturity, $code, $date, $by, $note) {
            $line = Investment::create([
                'name' => $partner->name . ' · ' . self::TERMS[$term] . ' · ' . $code,
                'type' => 'other',
                'facility_type' => $term,
                'return_type' => 'loan_share',
                'duration_days' => $maturity ? max(1, Carbon::parse($date)->diffInDays(Carbon::parse($maturity))) : null,
                'country' => 'Kenya',
                'initial_amount' => $amount,
                'current_value' => $amount,
                'expected_return' => 0,
                'purchase_date' => $date,
                'maturity_date' => $maturity,
                'status' => 'active',
                'funding_partners' => [],
                'notes' => $note,
                'created_by' => $by?->id,
                'updated_by' => $by?->id,
            ]);
            $funding = InvestmentFunding::create(['investment_id' => $line->id, 'partner_id' => $partner->id, 'amount_committed' => $amount,
                'amount_disbursed' => 0, 'amount_returned' => 0, 'status' => 'active', 'notes' => 'Partner money ' . $code]);
            $journal = $partner->addContribution($amount, 'MPESA-' . $code, 'Money received ' . $code . ' (' . strtolower(self::TERMS[$term]) . ' line #' . $line->id . ')',
                $line->id, $funding->id);
            $funding->forceFill(['partner_transaction_id' => $journal->id])->save();
            $line->syncLegacyFunding();
            AuditTrail::record('investments', $line->id, 'create', null, ['event' => 'partner_line', 'partner_id' => $partner->id,
                'amount' => $amount, 'term' => $term, 'maturity' => $maturity, 'reference' => $code]);

            return $line->fresh();
        });
    }

    public function codeUsed(string $code): bool
    {
        return PartnerTransaction::where('reference', 'MPESA-' . strtoupper($code))->exists();
    }

    /** Partner lines that can still lend: active, not past maturity, with money not yet lent. */
    public function openLines(?int $partnerId = null): Collection
    {
        return Investment::with(['fundings.partner:id,name,is_house_account'])->where('status', 'active')->where('return_type', 'loan_share')
            ->whereHas('fundings', fn ($q) => $q->whereHas('partner', fn ($p) => $p->where('is_house_account', false))
                ->when($partnerId, fn ($f) => $f->where('partner_id', $partnerId)))
            ->orderBy('id')->get()
            ->filter(fn (Investment $i) => ! $this->pastMaturity($i))
            ->map(fn (Investment $i) => (object) ['line' => $i, 'partner' => $i->fundings->first(fn ($f) => ! $f->partner?->is_house_account)?->partner,
                'room' => $i->remainingCommitted()])
            ->filter(fn ($o) => $o->room > 0.004)->values();
    }

    public function pastMaturity(Investment $line): bool
    {
        return $line->maturity_date && Carbon::parse($line->maturity_date)->endOfDay()->isPast();
    }

    /** Loans a line lent to (its cash disbursements). */
    public function loanIds(Investment $line): Collection
    {
        return Disbursement::where('investment_id', $line->id)->where('kind', 'cash')->distinct()->pluck('loan_id');
    }

    /** Where a line stands: lent, back, at risk, earned, and whether it can close. */
    public function status(Investment $line): array
    {
        $funding = $line->fundings()->whereHas('partner', fn ($q) => $q->where('is_house_account', false))->orderBy('id')->first();
        $loanIds = $this->loanIds($line);
        $loans = Loan::withTrashed()->whereIn('id', $loanIds)->get(['id', 'status', 'deleted_at']);
        $open = $loans->filter(fn ($l) => ! $l->deleted_at && ! in_array($l->status, Loan::STATUS_FINAL_LIST, true));
        $committed = (float) ($funding->amount_committed ?? 0);
        $lent = (float) ($funding->amount_disbursed ?? 0);
        $back = (float) ($funding->amount_returned ?? 0);
        $earned = $funding ? (float) PartnerTransaction::where('partner_id', $funding->partner_id)->whereIn('loan_id', $loanIds->all() ?: [0])
            ->whereIn('wallet_slug', ['interest', 'broker_fee'])->sum('amount') : 0.0;
        $closable = $funding && $loans->isNotEmpty() && $open->isEmpty()
            && ($line->facility_type !== 'long_term' || $this->pastMaturity($line));

        return [
            'funding' => $funding, 'partner_id' => $funding?->partner_id, 'committed' => $committed, 'lent' => $lent, 'back' => $back,
            'out' => max(0, round($lent - $back, 2)), 'idle' => max(0, round($committed - $lent + $back, 2)), 'earned' => round($earned, 2),
            'loans' => $loans->count(), 'open_loans' => $open->count(), 'closable' => $closable,
            // closed loans that did not pay back their principal (written off)
            'shortfall' => $loans->isNotEmpty() && $open->isEmpty() ? max(0, round($lent - $back, 2)) : 0.0,
        ];
    }

    /**
     * Close a line whose loans are all closed (short term) or that has matured with its loans closed (long term),
     * and put its payout on Requests: the capital (less any loss) from the principal wallet and what it earned from
     * the interest / broker-fee wallets, each capped by what the wallet holds. Idempotent per line.
     */
    public function closeIfDone(Investment $line): bool
    {
        if ($line->status !== 'active' || $line->return_type !== 'loan_share' || $line->isHouseLine()) {
            return false;
        }
        $s = $this->status($line);
        if (! $s['closable']) {
            return false;
        }

        return DB::transaction(function () use ($line, $s) {
            $line = Investment::whereKey($line->id)->lockForUpdate()->first();
            if ($line->status !== 'active') {
                return false;
            }
            $partner = Partner::findOrFail($s['partner_id']);
            $capital = max(0, round($s['committed'] - $s['shortfall'], 2));
            // capped by what each wallet holds, read from the journal (the wallets equal it; wallets:check)
            $held = fn (string $wallet) => (float) PartnerTransaction::where('partner_id', $partner->id)->where('wallet_slug', $wallet)->sum('amount');
            $pay = ['principal' => min($capital, $held('principal'))];
            $loanIds = $this->loanIds($line)->all() ?: [0];
            foreach (['interest', 'broker_fee'] as $wallet) {
                $earned = (float) PartnerTransaction::where('partner_id', $partner->id)->whereIn('loan_id', $loanIds)->where('wallet_slug', $wallet)->sum('amount');
                $pay[$wallet] = max(0, min(round($earned, 2), $held($wallet)));
            }
            $note = 'Line #' . $line->id . ' closed: ' . $s['loans'] . ' loan(s) closed. Capital KES ' . number_format($s['committed'], 2)
                . ($s['shortfall'] > 0 ? ', less KES ' . number_format($s['shortfall'], 2) . ' not paid back (written off) — decide whether AC covers it' : '') . '.';
            foreach ($pay as $wallet => $amount) {
                if ($amount < 0.005) {
                    continue;
                }
                $ref = 'LINE-' . $line->id . '-' . strtoupper($wallet);
                if (DB::table('partner_requests')->where('reference', $ref)->exists()) {
                    continue;
                }
                DB::table('partner_requests')->insert(['type' => 'withdrawal', 'partner_id' => $partner->id, 'amount' => round($amount, 2), 'wallet_slug' => $wallet,
                    'reference' => $ref, 'note' => $note, 'status' => 'pending', 'requested_by' => null, 'created_at' => now(), 'updated_at' => now()]);
            }
            $line->forceFill(['status' => 'matured', 'exit_date' => now()->toDateString()])->save();
            $s['funding']?->forceFill(['status' => 'settled'])->save();
            AuditTrail::record('investments', $line->id, 'update', ['status' => 'active'], ['status' => 'matured', 'event' => 'partner_line_closed',
                'payout' => $pay, 'shortfall' => $s['shortfall']]);

            return true;
        });
    }

    /** Close every line a loan drew on (after a repayment, write-off or status change). */
    public function closeForLoan(int $loanId): void
    {
        $ids = Disbursement::where('loan_id', $loanId)->where('kind', 'cash')->whereNotNull('investment_id')->distinct()->pluck('investment_id');
        foreach (Investment::whereIn('id', $ids)->where('status', 'active')->where('return_type', 'loan_share')->get() as $line) {
            $this->closeIfDone($line);
        }
    }
}
