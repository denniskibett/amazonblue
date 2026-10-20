<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanCycle;
use Illuminate\Support\Carbon;

/**
 * A loan's status follows the balance of its latest cycle (Stage 4 Phase 2, AUDIT rule 8, P1 #10), engine v2.
 *
 *   owes nothing                  -> repaid (latest cycle completed, closed_on = the day it was settled)
 *   owes, penalty waived today    -> forbearance
 *   owes, in recovery / defaulted -> unchanged (collections decide; a default is set by loans:update-statuses)
 *   owes, past due                -> overdue
 *   owes, on time                 -> disbursed
 *
 * Never touches pending, approved, rejected or written_off loans. "Repaid" is never set from totals of all
 * repayments, and never without the balance at 0.
 */
class LoanStatus
{
    public const LEAVE = [Loan::STATUS_PENDING, Loan::STATUS_APPROVED, Loan::STATUS_REJECTED, Loan::STATUS_WRITTEN_OFF];

    /** What the status should be today, without saving. Null when the loan is left alone. */
    public static function decide(Loan $loan, ?string $asOf = null): ?array
    {
        if (in_array($loan->status, self::LEAVE, true)) {
            return null;
        }
        $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first();
        if (! $cycle) {
            return null;
        }
        $asOf ??= now()->toDateString();
        $b = CycleBalance::at($cycle, $asOf);
        $waived = $cycle->penalty_waived_until && $cycle->penalty_waived_until->toDateString() >= $asOf;

        if ($b['final_outstanding'] <= 0) {
            $status = Loan::STATUS_REPAID;
            $reason = 'balance 0' . ($b['settled_on'] ? ' since ' . $b['settled_on'] : '');
        } elseif (in_array($loan->status, [Loan::STATUS_RECOVERY, Loan::STATUS_DEFAULTED], true)) {
            $status = $loan->status;
            $reason = 'owes ' . number_format($b['final_outstanding'], 2) . ', in collections';
        } elseif ($waived) {
            $status = Loan::STATUS_FORBEARANCE;
            $reason = 'penalty waived until ' . $cycle->penalty_waived_until->toDateString();
        } elseif ($b['is_overdue']) {
            $status = Loan::STATUS_OVERDUE;
            $reason = $b['days_overdue'] . ' day(s) late, owes ' . number_format($b['final_outstanding'], 2);
        } else {
            $status = Loan::STATUS_DISBURSED;
            $reason = 'on time, owes ' . number_format($b['final_outstanding'], 2);
        }

        return ['status' => $status, 'reason' => $reason, 'cycle' => $cycle, 'balance' => $b];
    }

    /** Applies decide(): loan status, overdue fields, and the latest cycle's status and balance cache. */
    public static function apply(Loan $loan, ?string $asOf = null): ?array
    {
        $d = self::decide($loan, $asOf);
        if (! $d) {
            return null;
        }
        $b = $d['balance'];
        $cycle = $d['cycle'];

        $cycle->fill([
            'status' => $d['status'] === Loan::STATUS_REPAID ? 'completed' : 'active',
            'closed_on' => $d['status'] === Loan::STATUS_REPAID ? ($b['settled_on'] ?? $b['as_of']) : null,
        ] + self::cache($b));
        if ($cycle->isDirty()) {
            $cycle->save();
        }

        $loan->forceFill([
            'status' => $d['status'],
            'days_overdue' => $b['days_overdue'],
            'last_overdue_check' => now(),
            'is_non_performing' => $d['status'] !== Loan::STATUS_REPAID && ($b['is_defaulted'] || ($loan->is_non_performing && $b['is_overdue'])),
        ]);
        if ($loan->is_non_performing && ! $loan->default_date) {
            $loan->default_date = now();
        }
        if ($loan->isDirty()) {
            $loan->save();
        }

        return $d;
    }

    /** The balance cache columns of a cycle, from a CycleBalance result. */
    public static function cache(array $b): array
    {
        return [
            'closing_balance' => $b['final_outstanding'],
            'penalty_accrued' => $b['penalty'],
            'principal_paid' => $b['principal_paid'],
            'interest_paid' => $b['interest_paid'],
            'penalty_paid' => $b['penalty_paid'],
            'balance_as_of' => Carbon::parse($b['as_of'])->toDateString(),
        ];
    }
}
