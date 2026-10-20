<?php

namespace App\Services\Loans;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\LoanCycle;
use Illuminate\Support\Carbon;

/**
 * Cycle 1 of a loan (engine v2, Stage 4 Phase 2).
 *
 * Decision 12 (owner): cycle 1 starts when the money is sent. The first cash disbursement re-dates cycle 1
 * (start = disbursement date, due = one product period later), but only while nothing has happened on it yet:
 * no repayment on the cycle and no later cycle. It also records the product terms on the cycle.
 */
class CycleOne
{
    public static function startOnDisbursement(Loan $loan, Disbursement $disbursement): ?LoanCycle
    {
        $loan->loadMissing('loanType');
        $cycle = LoanCycle::where('loan_id', $loan->id)->where('cycle_number', 1)->first();
        if (! $cycle || ! $loan->loanType
            || LoanCycle::where('loan_id', $loan->id)->where('cycle_number', '>', 1)->exists()
            || \App\Models\Repayment::withTrashed()->where('loan_cycle_id', $cycle->id)->exists()
            || Disbursement::where('loan_id', $loan->id)->where('id', '<', $disbursement->id)->where('kind', 'cash')->exists()) {
            return null;
        }

        $start = Carbon::parse($disbursement->disburse_date)->startOfDay();
        $cycle->fill(self::terms($loan) + [
            'principal' => $cycle->lentPrincipal(),
            'start_date' => $start->toDateString(),
            'due_date' => Period::add($start, (int) $loan->loanType->period, (string) $loan->loanType->unit)->toDateString(),
        ]);
        if ($cycle->isDirty()) {
            $cycle->save();
        }

        return $cycle;
    }

    /** The product's penalty terms, recorded on a cycle when it is created. */
    public static function terms(Loan $loan): array
    {
        $type = $loan->loanType;

        return [
            'penalty_rate' => (float) ($type->penalty_rate ?? 0),
            'grace_days' => (int) ($type->grace_period_days ?? 0),
            'default_threshold_days' => (int) ($loan->npl_trigger_threshold ?: ($type->default_threshold_days ?? 30)),
        ];
    }
}
