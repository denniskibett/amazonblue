<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Repayment;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\LoanCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * A short payment accepted as the cycle's full amount (owner, 1 Nov 2026). Borrowers often pay KES 200, 400 or 2,000
 * less than the cycle asks. Nothing is written to repayments: the cycle is fitted to the cash it received, worked
 * backwards. E.g. 21,000 + 20% = 25,200 owed, 25,000 paid → the cycle's interest becomes 4,000 (exact, in
 * loan_cycles.interest_amount; the rate shown is rounded), and if the cash came after the due date the cycle gets as many
 * grace days as it was late, so no penalty is left. Allowed when the gap (principal + interest unpaid) is at most
 * lending.shortfall.max and no more than the cycle's interest, and some cash came in on the cycle.
 *
 * The first version (same day) booked the gap as a SHORTFALL DISCOUNT repayment; fitting a cycle removes any such row.
 */
class ShortfallFit
{
    /** Rows booked by the first version, removed when the cycle is fitted. */
    public const OLD_REFERENCE = 'SHORTFALL DISCOUNT';

    public static function limit(): float
    {
        return (float) config('lending.shortfall.max', 2000);
    }

    /** The plan for a loan's open (or latest) cycle; null when there is nothing to fit. 'ok' says whether it may be applied. */
    public static function check(Loan $loan): ?array
    {
        if (! LoanCalculator::v2()) {
            return null;
        }
        $cycle = $loan->getCurrentCycle() ?? $loan->cycles()->orderByDesc('cycle_number')->first();
        if (! $cycle) {
            return null;
        }
        $old = self::oldRows($cycle);
        $open = [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_FORBEARANCE];
        if (! in_array($loan->status, $open, true) && ! ($old->isNotEmpty() && $loan->status === Loan::STATUS_REPAID)) {
            return null;
        }

        $terms = CycleBalance::termsFor($cycle);
        $payments = array_values(array_filter(CycleBalance::paymentsFor($cycle), fn ($p) => ! in_array($p['id'] ?? null, $old->all(), true)));
        $today = now()->toDateString();
        $b = CycleBalance::compute($terms, $payments, $today);
        $cashRows = Repayment::where('loan_cycle_id', $cycle->id)->whereNotIn('id', $old->all())
            ->where(fn ($q) => $q->whereNull('kind')->orWhere('kind', 'cash'));
        $cash = (float) (clone $cashRows)->sum('amount');
        $lastCash = (clone $cashRows)->where('amount', '>', 0)->max('repayment_date');

        $principal = (float) $terms['principal'];
        $interest = (float) $b['interest'];
        $gap = round($principal + $interest - $cash, 2);       // what the cash leaves of principal + interest
        $plan = ['cycle' => $cycle, 'cash' => $cash, 'gap' => $gap, 'owed' => (float) $b['final_outstanding'],
            'old_interest' => $interest, 'new_interest' => round($interest - $gap, 2), 'grace_days' => (int) $terms['grace_days'],
            'old_rows' => $old->all(), 'ok' => false];
        if ($cash <= 0 || $gap <= 0.004 || $gap > self::limit() + 0.004 || $gap > $interest + 0.004) {
            return $plan;
        }

        // late cash: grace for the days it was late, so the fitted cycle owes no penalty
        $due = Carbon::parse($terms['due'])->startOfDay();
        if ($lastCash && Carbon::parse($lastCash)->startOfDay()->gt($due)) {
            $plan['grace_days'] = max($plan['grace_days'], (int) $due->diffInDays(Carbon::parse($lastCash)->startOfDay()));
        }
        $after = CycleBalance::compute(['interest_amount' => $plan['new_interest'], 'grace_days' => $plan['grace_days']] + $terms, $payments, $today);
        $plan['ok'] = $after['final_outstanding'] <= 0.004;

        return $plan;
    }

    public static function apply(Loan $loan, string $reason, User $by): array
    {
        $plan = self::check($loan);
        if (! $plan || ! $plan['ok']) {
            throw new \DomainException('Only a cycle paid in cash and short by at most KES ' . number_format(self::limit())
                . ' (and no more than its interest) can be closed at the amount paid.');
        }

        DB::transaction(function () use ($loan, $plan, $reason, $by) {
            $cycle = LoanCycle::whereKey($plan['cycle']->id)->lockForUpdate()->firstOrFail();
            $old = ['status' => $loan->status, 'interest' => $plan['old_interest'], 'grace_days' => $cycle->grace_days,
                'interest_rate' => (float) $cycle->interest_rate];
            Repayment::whereIn('id', $plan['old_rows'])->get()->each->delete();

            $principal = $cycle->lentPrincipal();
            $cycle->fill([
                'interest_amount' => $plan['new_interest'],
                'interest_capitalized' => $plan['new_interest'],
                'interest_rate' => $principal > 0 ? round($plan['new_interest'] / $principal * 100, 2) : $cycle->interest_rate,
                'new_balance' => round($principal + $plan['new_interest'], 2),
                'grace_days' => $plan['grace_days'],
            ]);
            $cycle->notes = trim($cycle->notes . ' | Closed at the amount paid (' . number_format($plan['cash'], 2) . ', short '
                . number_format($plan['gap'], 2) . ') by ' . $by->name . ' on ' . now()->toDateString() . ': ' . $reason, ' |');
            $cycle->save();

            $d = LoanStatus::apply($loan->fresh());
            AuditTrail::record('loans', $loan->id, 'update', $old, [
                'event' => 'shortfall_fit', 'cycle_id' => $cycle->id, 'interest' => $plan['new_interest'], 'gap' => $plan['gap'],
                'grace_days' => $plan['grace_days'], 'removed_rows' => $plan['old_rows'], 'status' => $d['status'] ?? $loan->status, 'reason' => $reason,
            ]);
        });

        if (RepaymentSplit::enabled()) {
            try {
                app(RepaymentSplit::class)->syncCycle((int) $plan['cycle']->id);
            } catch (\Throwable $e) {
                Log::error("Repayment split failed for cycle #{$plan['cycle']->id}", ['error' => $e->getMessage()]);
            }
        }

        return $plan;
    }

    /** Ids of SHORTFALL DISCOUNT rows the first version booked on this cycle. */
    private static function oldRows(LoanCycle $cycle): \Illuminate\Support\Collection
    {
        return Repayment::where('loan_cycle_id', $cycle->id)->where('kind', 'discount')
            ->where('transaction', 'like', self::OLD_REFERENCE . '%')->pluck('id')->map(fn ($id) => (int) $id);
    }
}
