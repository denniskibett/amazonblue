<?php

namespace App\Services\Partners;

use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Loan;
use App\Models\PartnerTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One facility (a partner's investment line) as a plain statement (owner, 17 Nov 2026): credits when money comes in
 * (from the partner, back from a loan, what a loan earned) and debits when it goes out (lent to a loan, paid to another
 * investor, paid back to the partner), with a running balance. Then what comes next, in grey: loans still out and
 * the maturity. Read from the records the money already uses; nothing is written.
 *
 * Each row: date, what, ref, debit, credit, loan_id, kind (in / lent / back / earned / other / paid).
 */
class FacilityStatement
{
    public function __construct(private PartnerLines $lines) {}

    /** @return array{rows: Collection, next: Collection, balance: float} */
    public function for(Investment $line): array
    {
        $funding = InvestmentFunding::where('investment_id', $line->id)->whereHas('partner', fn ($q) => $q->where('is_house_account', false))->orderBy('id')->first();
        $partnerId = (int) ($funding->partner_id ?? 0);
        $rows = collect();
        $final = Loan::STATUS_FINAL_LIST;
        $day = fn ($d) => $d ? substr((string) $d, 0, 10) : null;

        // money in: what they sent for this facility
        $in = $funding ? PartnerTransaction::where('investment_funding_id', $funding->id)->where('type', 'contribution')->orderBy('id')->get() : collect();
        foreach ($in as $t) {
            $rows->push($this->row($day($t->transaction_date), 'Money in from them', $this->code($t->reference), 0, (float) $t->amount, null, 'in'));
        }
        if ($in->isEmpty() && $funding && (float) $funding->amount_committed > 0) {
            $rows->push($this->row($day($line->start_date ?? $funding->created_at), 'Money in from them', null, 0, (float) $funding->amount_committed, null, 'in'));
        }

        // lent to loans: its slices of loans, and cash it paid out alone (no slice rows); one row per loan
        $slices = DB::table('loan_fundings as f')->leftJoin('disbursements as d', 'd.id', '=', 'f.disbursement_id')
            ->where('f.investment_id', $line->id)->get(['f.loan_id', 'f.amount', 'f.settled_on', 'd.disburse_date', 'd.transaction']);
        $cash = DB::table('disbursements')->where('investment_id', $line->id)->where('kind', 'cash')->whereNull('deleted_at')
            ->whereNotIn('loan_id', $slices->pluck('loan_id')->all() ?: [0])->get(['loan_id', 'amount', 'disburse_date', 'transaction'])
            ->groupBy('loan_id')->map(fn ($ds) => (object) ['loan_id' => $ds->first()->loan_id, 'amount' => round((float) $ds->sum('amount'), 2), 'settled_on' => null,
                'disburse_date' => $ds->min('disburse_date'), 'transaction' => $ds->first()->transaction]);
        $lent = $slices->concat($cash->values())->sortBy('disburse_date')->values();
        $loans = Loan::withTrashed()->with('user:id,name')->whereIn('id', $lent->pluck('loan_id')->all() ?: [0])->get()->keyBy('id');
        $lastPaid = DB::table('repayments')->whereIn('loan_id', $lent->pluck('loan_id')->all() ?: [0])->whereNull('deleted_at')->groupBy('loan_id')
            ->selectRaw('loan_id, max(repayment_date) as last_on')->pluck('last_on', 'loan_id');
        $earned = $partnerId ? PartnerTransaction::where('partner_id', $partnerId)->whereIn('loan_id', $lent->pluck('loan_id')->all() ?: [0])
            ->whereIn('type', ['profit_distribution', 'penalty_share'])->groupBy('loan_id')->selectRaw('loan_id, sum(amount) as total, max(transaction_date) as last_on')->get()->keyBy('loan_id') : collect();
        $next = collect();
        foreach ($lent as $l) {
            $loan = $loans[$l->loan_id] ?? null;
            $who = $loan?->user?->name;
            $rows->push($this->row($day($l->disburse_date), 'Lent to loan #' . $l->loan_id . ($who ? ' · ' . $who : ''), $l->transaction, (float) $l->amount, 0, (int) $l->loan_id, 'lent'));
            if ($l->settled_on) {
                $rows->push($this->row($day($l->settled_on), 'Loan #' . $l->loan_id . ': we paid them out of it', null, 0, (float) $l->amount, (int) $l->loan_id, 'back'));
            } elseif ($loan && in_array($loan->status, $final, true)) {
                $rows->push($this->row($day($lastPaid[$l->loan_id] ?? $loan->updated_at), 'Loan #' . $l->loan_id . ' repaid: their money back', null, 0, (float) $l->amount, (int) $l->loan_id, 'back'));
            } else {
                $next->push($this->row(null, 'When loan #' . $l->loan_id . ' is repaid: their money back', null, 0, (float) $l->amount, (int) $l->loan_id, 'back'));
            }
            if (($e = $earned[$l->loan_id] ?? null) && abs((float) $e->total) >= 0.005) {
                $rows->push($this->row($day($e->last_on), 'Earned on loan #' . $l->loan_id, null, 0, round((float) $e->total, 2), (int) $l->loan_id, 'earned'));
            }
        }

        // paid other investors from this facility (owner, 15 Nov 2026: Buosti and Ruth out of Yvonne's line)
        foreach (InvestmentFunding::where('paid_from_investment_id', $line->id)->with('partner:id,name')->get() as $other) {
            $paidLine = Investment::find($other->investment_id);
            $rows->push($this->row($day($paidLine?->exit_date), 'Paid ' . ($other->partner?->name ?? 'another investor') . ' (line #' . $other->investment_id . ')', null,
                $paidLine ? $this->lines->paidOut($paidLine) : 0, 0, null, 'other'));
        }

        // paid back to them: a fixed line's payout, and a share line's payouts once it closed
        if ($partnerId) {
            $fixedReturn = (float) PartnerTransaction::where('investment_id', $line->id)->where('reference', 'FIXED-' . $line->id)->sum('amount');
            if ($fixedReturn > 0) {
                $rows->push($this->row($day($line->exit_date), 'Fixed return earned', null, 0, round($fixedReturn, 2), null, 'earned'));
            }
            $paid = PartnerTransaction::where('partner_id', $partnerId)->where('type', 'withdrawal')->where('notes', 'like', '%line #' . $line->id . ' %')->get();
            $paid = $paid->concat(PartnerTransaction::where('partner_id', $partnerId)->where('type', 'withdrawal')->where('notes', 'like', '%line #' . $line->id . ')')->get())->unique('id');
            foreach ($paid as $t) {
                $rows->push($this->row($day($t->transaction_date), 'Paid back to them', $this->code($t->reference), abs((float) $t->amount), 0, null, 'paid'));
            }
            $done = DB::table('partner_requests')->where('reference', 'like', 'LINE-' . $line->id . '-%')->where('status', 'done')->get();
            foreach ($done as $q) {
                $rows->push($this->row($day($q->paid_on ?? $q->updated_at), 'Paid back to them', $q->bank_reference, (float) $q->amount, 0, null, 'paid'));
            }
        }

        // order by day (money in first on a day), then the running balance
        $order = ['in' => 0, 'back' => 1, 'earned' => 2, 'lent' => 3, 'other' => 4, 'paid' => 5];
        $rows = $rows->sortBy(fn ($r) => ($r['date'] ?? '9999') . '-' . $order[$r['kind']])->values();
        $balance = 0.0;
        $rows = $rows->map(function ($r) use (&$balance) {
            $balance = round($balance + $r['credit'] - $r['debit'], 2);

            return $r + ['balance' => $balance];
        });

        // the maturity of a fixed line still open
        if ($line->return_type === 'fixed_return' && $line->status === 'active') {
            [, , $due] = $this->lines->fixedDue($line);
            $next->push($this->row($day($line->maturity_date), 'Matures: paid back to them', null, (float) $due, 0, null, 'paid'));
        }

        return ['rows' => $rows, 'next' => $next, 'balance' => $balance];
    }

    private function row(?string $date, string $what, ?string $ref, float $debit, float $credit, ?int $loanId, string $kind): array
    {
        return ['date' => $date, 'what' => $what, 'ref' => $ref, 'debit' => round($debit, 2), 'credit' => round($credit, 2), 'loan_id' => $loanId, 'kind' => $kind];
    }

    /** MPESA-UKH7QW21PZ → UKH7QW21PZ; PAYOUT-X-R → X. */
    private function code(?string $ref): ?string
    {
        return $ref ? preg_replace(['/^(MPESA|PAYOUT|BANK)-/', '/-R$/'], '', $ref) : null;
    }
}
