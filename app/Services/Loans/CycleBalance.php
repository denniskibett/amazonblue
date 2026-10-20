<?php

namespace App\Services\Loans;

use App\Models\LoanCycle;
use App\Models\Repayment;
use Illuminate\Support\Carbon;

/**
 * One cycle's balance on a given day (Stage 4 Phase 2, lending.engine = v2). The single source for every
 * "owed", rollover, status and restatement figure.
 *
 * The rules (AUDIT rule 5 and the owner's decisions of 20 Oct 2026):
 *  - The cycle lends its principal; interest is the cycle rate on that principal, charged once (flat).
 *  - Payments on or before the due date reduce the balance; no penalty runs while the cycle is on time.
 *  - From the day after the due date (after any grace days, and after any waiver), penalty is charged each day
 *    at the penalty rate on what is still unpaid of principal + interest (never on penalty), until that is
 *    paid. A day's penalty is charged before that day's payments, so paying the balance shown on a day clears it.
 *  - Every payment clears penalty first, then interest, then principal. Anything beyond that is an overpayment.
 *  - Cap (lending.penalty.cap = in_duplum): the cycle's penalty stops once it equals the principal still unpaid
 *    on the due date.
 *
 * Works in whole cents, so the figures add up exactly. A reversal (negative row) is netted against the
 * payments of its day; reversals are dated like the row they reverse.
 */
class CycleBalance
{
    /**
     * @param array{principal: float, interest_rate: float, start: string, due: string, penalty_rate: float,
     *              grace_days: int, threshold_days: int, waived_until: ?string, cap: string} $terms
     * @param array<int, array{date: string, amount: float}> $payments
     */
    public static function compute(array $terms, array $payments, string $asOf): array
    {
        $due = Carbon::parse($terms['due'])->startOfDay();
        $asOfDay = Carbon::parse($asOf)->startOfDay();
        $waived = $terms['waived_until'] ? Carbon::parse($terms['waived_until'])->startOfDay() : null;

        $principal = self::cents($terms['principal']);
        $interest = (int) round($principal * (float) $terms['interest_rate'] / 100);

        // one net amount per day, up to the as-of day
        $byDay = [];
        foreach ($payments as $p) {
            $day = Carbon::parse($p['date'])->toDateString();
            if ($day <= $asOfDay->toDateString()) {
                $byDay[$day] = ($byDay[$day] ?? 0) + self::cents($p['amount']);
            }
        }
        ksort($byDay);

        $s = [
            'p' => $principal, 'i' => $interest, 'pen' => 0,               // unpaid
            'paid_p' => 0, 'paid_i' => 0, 'paid_pen' => 0, 'over' => 0,
            'accrued' => 0, 'days' => 0, 'last_penalty_day' => null, 'settled_on' => null,
        ];
        $apply = function (int $amount, string $day) use (&$s) {
            if ($amount <= 0) {
                return;
            }
            foreach ([['pen', 'paid_pen'], ['i', 'paid_i'], ['p', 'paid_p']] as [$bucket, $paid]) {
                $take = min($amount, $s[$bucket]);
                $s[$bucket] -= $take;
                $s[$paid] += $take;
                $amount -= $take;
            }
            $s['over'] += $amount;
            if ($s['settled_on'] === null && $s['p'] + $s['i'] + $s['pen'] === 0) {
                $s['settled_on'] = $day;
            }
        };

        // on time: everything paid up to and including the due date
        $paidByDue = 0;
        foreach ($byDay as $day => $amount) {
            if ($day <= $due->toDateString()) {
                $apply($amount, $day);
                $paidByDue += $amount;
            }
        }
        $owedAtDue = $s['p'] + $s['i'];
        $cap = $terms['cap'] === 'in_duplum' ? $s['p'] : PHP_INT_MAX;

        // late: one day at a time, penalty first, then that day's payments
        $rate = (float) $terms['penalty_rate'];
        $grace = (int) $terms['grace_days'];
        for ($d = $due->copy()->addDay(), $n = 1; $d->lte($asOfDay); $d->addDay(), $n++) {
            $day = $d->toDateString();
            $base = $s['p'] + $s['i'];
            if ($base > 0 && $n > $grace && ! ($waived && $d->lte($waived)) && $rate > 0 && $s['accrued'] < $cap) {
                $charge = min((int) round($base * $rate / 100), $cap - $s['accrued']);
                $s['pen'] += $charge;
                $s['accrued'] += $charge;
                $s['days']++;
                $s['last_penalty_day'] = $day;
            }
            if (isset($byDay[$day])) {
                $apply($byDay[$day], $day);
            }
        }

        $closing = $s['p'] + $s['i'] + $s['pen'];
        $late = $closing > 0 && $asOfDay->gt($due);
        $daysOverdue = $late ? (int) $due->diffInDays($asOfDay) : 0;

        return [
            'principal' => $principal / 100.0,
            'interest' => $interest / 100.0,
            'full_balance' => ($principal + $interest) / 100.0,
            'repayments_before_due' => $paidByDue / 100.0,
            'repayments_after_due' => (array_sum($byDay) - $paidByDue) / 100.0,
            'total_repayments' => array_sum($byDay) / 100.0,
            'outstanding_at_due' => max(0, $owedAtDue) / 100.0,
            'penalty' => $s['accrued'] / 100.0,
            'penalty_capped' => $terms['cap'] === 'in_duplum' && $s['accrued'] >= $cap && $cap > 0,
            'days_subject_to_penalty' => $s['days'],
            'last_penalty_day' => $s['last_penalty_day'],
            'principal_paid' => $s['paid_p'] / 100.0,
            'interest_paid' => $s['paid_i'] / 100.0,
            'penalty_paid' => $s['paid_pen'] / 100.0,
            'unpaid_principal' => $s['p'] / 100.0,
            'unpaid_interest' => $s['i'] / 100.0,
            'unpaid_penalty' => $s['pen'] / 100.0,
            'overpaid' => $s['over'] / 100.0,
            'final_outstanding' => $closing / 100.0,
            'settled_on' => $closing === 0 ? $s['settled_on'] : null,
            'days_overdue' => $daysOverdue,
            'is_overdue' => $late,
            'is_fully_repaid' => $closing === 0,
            'is_defaulted' => $late && $daysOverdue >= (int) $terms['threshold_days'],
            'default_threshold_days' => (int) $terms['threshold_days'],
            'penalty_rate' => $rate,
            'grace_period_days' => $grace,
            'interest_rate' => (float) $terms['interest_rate'],
            'penalty_waived_until' => $terms['waived_until'],
            'as_of' => $asOfDay->toDateString(),
        ];
    }

    /** The terms of a stored cycle: its own snapshot first, then the product (for cycles from before the snapshot). */
    public static function termsFor(LoanCycle $cycle): array
    {
        $loan = $cycle->loan()->withTrashed()->with('loanType')->first();
        $type = $loan?->loanType;
        $notes = strtoupper((string) $cycle->notes);
        // payment-plan cycles from before penalty_waived_until existed: waived for that cycle only
        $legacyPlan = str_contains($notes, 'PAYMENT PLAN') || str_contains($notes, 'PENALTIES WAIVED');

        return [
            'principal' => $cycle->lentPrincipal(),
            'interest_rate' => $cycle->interest_rate !== null ? (float) $cycle->interest_rate : (float) ($type->interest_rate ?? 0),
            'start' => Carbon::parse($cycle->start_date)->toDateString(),
            'due' => Carbon::parse($cycle->due_date)->toDateString(),
            'penalty_rate' => $cycle->penalty_rate !== null ? (float) $cycle->penalty_rate : (float) ($type->penalty_rate ?? 0),
            'grace_days' => $cycle->grace_days ?? (int) ($type->grace_period_days ?? 0),
            'threshold_days' => $cycle->default_threshold_days
                ?? (int) ($loan?->npl_trigger_threshold ?: ($type->default_threshold_days ?? 30)),
            'waived_until' => $cycle->penalty_waived_until?->toDateString() ?? ($legacyPlan ? '9999-12-31' : null),
            'cap' => (string) config('lending.penalty.cap', 'in_duplum'),
        ];
    }

    /** The payments booked to a cycle (deleted rows excluded; reversals included, they net out). */
    public static function paymentsFor(LoanCycle $cycle): array
    {
        return Repayment::where('loan_cycle_id', $cycle->id)->orderBy('repayment_date')->orderBy('id')
            ->get(['repayment_date', 'amount'])
            ->map(fn ($r) => ['date' => Carbon::parse($r->repayment_date)->toDateString(), 'amount' => (float) $r->amount])
            ->all();
    }

    public static function at(LoanCycle $cycle, ?string $asOf = null): array
    {
        return self::compute(self::termsFor($cycle), self::paymentsFor($cycle), $asOf ?? now()->toDateString())
            + ['cycle_id' => $cycle->id, 'cycle_number' => $cycle->cycle_number];
    }

    private static function cents($amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
