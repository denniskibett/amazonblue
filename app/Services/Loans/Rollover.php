<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\LoanCycle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rollover (extension) under engine v2 (Stage 4 Phase 2, AUDIT rule 6 and the owner's decisions of 20 Oct 2026).
 *
 * preview() and execute() run the same plan(), so what a borrower or staff member is shown is what is booked.
 *  - The new principal is everything still owed on the rollover day: principal + interest + penalty up to and
 *    including that day, after that day's payments.
 *  - The new cycle starts on the rollover day if the cycle is late (the last penalty day), otherwise on the due
 *    date, and is due one product period later (or after a custom number of days, or on a custom date).
 *  - Rate: cycles 2 to lending.rollover.same_rate_cycles (4) keep the previous cycle's rate. From the next cycle
 *    staff must set a rate (or use a payment plan).
 *  - The old cycle is closed (completed, closed_on, balance cache); it can never be rolled again.
 *  - Runs in one transaction with the loan and the cycle locked.
 */
class Rollover
{
    public const ROLLABLE = [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE];

    /**
     * @param array{interest_rate?: ?float, period_days?: ?int, due_date?: ?string, waive_penalty?: ?bool, notes?: ?string, date?: ?string} $options
     */
    public function plan(Loan $loan, array $options = []): array
    {
        $loan->loadMissing('loanType');
        $type = $loan->loanType ?? throw new \DomainException('Loan type not found for loan #' . $loan->id);
        if (! in_array($loan->status, self::ROLLABLE, true)) {
            throw new \DomainException('This loan is not eligible for an extension (status ' . $loan->status . ').');
        }
        if ($loan->default_triggered) {
            throw new \DomainException('This loan is defaulted. Resolve the recovery case first.');
        }

        $cycle = $loan->cycles()->orderByDesc('cycle_number')->first()
            ?? throw new \DomainException('Loan #' . $loan->id . ' has no cycle to extend.');
        if ($cycle->status !== 'active') {
            throw new \DomainException('Cycle ' . $cycle->cycle_number . ' is already closed; it can\'t be extended again.');
        }

        $day = Carbon::parse($options['date'] ?? now())->startOfDay();
        $balance = CycleBalance::at($cycle, $day->toDateString());
        if ($balance['final_outstanding'] <= 0) {
            throw new \DomainException('Nothing is owed on this cycle, so there is nothing to extend.');
        }

        $number = $cycle->cycle_number + 1;
        $previousRate = (float) ($cycle->interest_rate ?? $type->interest_rate);
        $askedRate = isset($options['interest_rate']) && $options['interest_rate'] !== '' ? (float) $options['interest_rate'] : null;
        $sameRateUpTo = (int) config('lending.rollover.same_rate_cycles', 4);
        if ($number <= $sameRateUpTo) {
            if ($askedRate !== null && abs($askedRate - $previousRate) > 0.0001) {
                throw new \DomainException("Cycles 2 to {$sameRateUpTo} keep the previous rate ({$previousRate}%).");
            }
            $rate = $previousRate;
        } else {
            $rate = $askedRate ?? throw new \DomainException("Cycle {$number}: set a new rate, or start a payment plan.");
        }

        $due = Carbon::parse($cycle->due_date)->startOfDay();
        $start = $day->gt($due) ? $day->copy() : $due->copy();
        if (! empty($options['due_date'])) {
            $newDue = Carbon::parse($options['due_date'])->startOfDay();
        } elseif (! empty($options['period_days'])) {
            $newDue = $start->copy()->addDays((int) $options['period_days']);
        } else {
            $newDue = Period::add($start, (int) $type->period, (string) $type->unit);
        }
        if ($newDue->lte($start)) {
            throw new \DomainException('The new due date must be after the new start date.');
        }

        $principal = round($balance['final_outstanding'], 2);
        $interest = round($principal * $rate / 100, 2);
        $waive = (bool) ($options['waive_penalty'] ?? false);

        return [
            'loan_id' => $loan->id,
            'cycle' => $cycle,
            'balance' => $balance,
            'rollover_date' => $day->toDateString(),
            'new_cycle' => $number,
            'principal' => $principal,
            'interest_rate' => $rate,
            'interest' => $interest,
            'new_balance' => round($principal + $interest, 2),
            'start_date' => $start->toDateString(),
            'due_date' => $newDue->toDateString(),
            'waive_penalty' => $waive,
            'penalty_rate' => (float) $type->penalty_rate,
            'grace_days' => (int) ($type->grace_period_days ?? 0),
            'threshold_days' => (int) ($loan->npl_trigger_threshold ?: ($type->default_threshold_days ?? 30)),
            'notes' => trim(($options['notes'] ?? '') ?: ('Extension: cycle ' . $number))
                . ($waive ? ' - PENALTIES WAIVED (this cycle)' : ''),
        ];
    }

    public function preview(Loan $loan, array $options = []): array
    {
        return $this->plan($loan, $options);
    }

    /** Books the plan. Returns the plan plus the new cycle. */
    public function execute(Loan $loan, array $options = []): array
    {
        return DB::transaction(function () use ($loan, $options) {
            $locked = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            LoanCycle::where('loan_id', $loan->id)->lockForUpdate()->get();
            $plan = $this->plan($locked, $options);
            $old = $plan['cycle'];
            $b = $plan['balance'];

            $old->update([
                'status' => 'completed',
                'closed_on' => $plan['rollover_date'],
                'closing_balance' => $b['final_outstanding'],
                'penalty_accrued' => $b['penalty'],
                'principal_paid' => $b['principal_paid'],
                'interest_paid' => $b['interest_paid'],
                'penalty_paid' => $b['penalty_paid'],
                'balance_as_of' => $plan['rollover_date'],
            ]);

            $new = LoanCycle::create([
                'loan_id' => $locked->id,
                'cycle_number' => $plan['new_cycle'],
                'previous_balance' => $plan['principal'],
                'principal' => $plan['principal'],
                'interest_capitalized' => $plan['interest'],
                'new_balance' => $plan['new_balance'],
                'interest_rate' => $plan['interest_rate'],
                'penalty_rate' => $plan['penalty_rate'],
                'grace_days' => $plan['grace_days'],
                'default_threshold_days' => $plan['threshold_days'],
                'penalty_waived_until' => $plan['waive_penalty'] ? $plan['due_date'] : null,
                'start_date' => $plan['start_date'],
                'due_date' => $plan['due_date'],
                'status' => 'active',
                'notes' => $plan['notes'],
            ]);

            $locked->forceFill([
                'cycle' => $plan['new_cycle'],
                'due_date' => $plan['due_date'],
                'calculated_due_date' => $plan['due_date'],
                'status' => $plan['waive_penalty'] ? Loan::STATUS_FORBEARANCE : Loan::STATUS_DISBURSED,
                'forbearance_until' => $plan['waive_penalty'] ? $plan['due_date'] : null,
                'capitalized_interest' => (float) ($locked->capitalized_interest ?? 0) + $plan['interest'],
                'days_overdue' => 0,
                'is_non_performing' => false,
            ])->save();
            LoanSync::fromCycles($locked);

            return $plan + ['created' => $new];
        });
    }
}
