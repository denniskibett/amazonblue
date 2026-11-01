<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Repayment;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\LoanCalculator;
use Illuminate\Support\Facades\DB;

/**
 * A short payment accepted as paid in full (owner, 1 Nov 2026). Borrowers often pay KES 200, 400 or 2,000 less than the
 * cycle asks. When the principal + interest still unpaid is at or under lending.shortfall.max and some cash came in on
 * the cycle, staff may close it: what is owed today (including any penalty on the gap) is booked as a discount
 * (kind discount, mode book: no cash, nobody earns a share), and the loan status is worked out again.
 */
class ShortfallDiscount
{
    public const REFERENCE = 'SHORTFALL DISCOUNT';

    public static function limit(): float
    {
        return (float) config('lending.shortfall.max', 2000);
    }

    /** [cycle, gap (principal + interest unpaid), owed (incl. penalty), cash paid, ok] for a loan's open cycle; null when none. */
    public static function check(Loan $loan): ?array
    {
        if (! LoanCalculator::v2() || ! in_array($loan->status, [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_FORBEARANCE], true)) {
            return null;
        }
        $cycle = $loan->getCurrentCycle() ?? $loan->cycles()->orderByDesc('cycle_number')->first();
        if (! $cycle) {
            return null;
        }
        $b = CycleBalance::at($cycle);
        $gap = round($b['unpaid_principal'] + $b['unpaid_interest'], 2);
        $cash = (float) Repayment::where('loan_cycle_id', $cycle->id)->where(fn ($q) => $q->whereNull('kind')->orWhere('kind', 'cash'))->sum('amount');

        return [
            'cycle' => $cycle, 'gap' => $gap, 'owed' => (float) $b['final_outstanding'], 'cash' => $cash,
            'ok' => $b['final_outstanding'] > 0.004 && $gap <= self::limit() + 0.004 && $cash > 0,
        ];
    }

    public static function accept(Loan $loan, string $reason, User $by): Repayment
    {
        $c = self::check($loan);
        if (! $c || ! $c['ok']) {
            throw new \DomainException('Only a cycle that has been paid in cash and is short by at most KES '
                . number_format(self::limit()) . ' can be accepted as paid in full.');
        }

        return DB::transaction(function () use ($loan, $c, $reason, $by) {
            $old = ['status' => $loan->status, 'owed' => $c['owed']];
            $note = 'Short payment of ' . number_format($c['owed'], 2) . ' accepted as paid in full by ' . $by->name
                . ' on ' . now()->toDateString() . '. Reason: ' . $reason;
            $row = Repayment::create(['loan_id' => $loan->id, 'loan_cycle_id' => $c['cycle']->id, 'amount' => $c['owed'],
                'repayment_date' => now()->toDateString(), 'transaction' => self::REFERENCE . ': cycle ' . $c['cycle']->cycle_number,
                'kind' => 'discount', 'mode' => 'book', 'created_by' => $by->id, 'notes' => $note]);
            $d = LoanStatus::apply($loan->fresh());
            AuditTrail::record('loans', $loan->id, 'update', $old, [
                'event' => 'shortfall_discount', 'repayment_id' => $row->id, 'amount' => $c['owed'], 'gap' => $c['gap'],
                'status' => $d['status'] ?? $loan->status, 'reason' => $reason,
            ]);

            return $row;
        });
    }
}
