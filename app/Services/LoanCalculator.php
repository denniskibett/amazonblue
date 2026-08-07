<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanCycle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LoanCalculator
{
    /**
     * Calculate the due date for a loan based on its loan type period
     * This is the FIRST due date from the borrow date
     */
    public function calculateDueDate(Loan $loan): ?Carbon
    {
        if (!$loan->loanType) {
            Log::warning('Loan type not found for loan #' . $loan->id);
            return null;
        }

        if (!$loan->borrow_date) {
            Log::warning('Borrow date not found for loan #' . $loan->id);
            return null;
        }

        $borrowDate = Carbon::parse($loan->borrow_date);
        $period = (int) $loan->loanType->period;
        $unit = $loan->loanType->unit;

        $dueDate = $borrowDate->copy();
        
        switch ($unit) {
            case 'days':
                $dueDate->addDays($period);
                break;
            case 'weeks':
                $dueDate->addWeeks($period);
                break;
            case 'months':
                $dueDate->addMonths($period);
                break;
            case 'years':
                $dueDate->addYears($period);
                break;
            default:
                $dueDate->addDays($period);
        }

        return $dueDate;
    }

    /**
     * Get the ACTIVE cycle or the latest cycle
     */
    public function getActiveCycle(Loan $loan): ?LoanCycle
    {
        // First try to get the active cycle
        $activeCycle = $loan->cycles()
            ->where('status', 'active')
            ->first();
        
        if ($activeCycle) {
            return $activeCycle;
        }
        
        // If no active cycle, get the latest cycle
        return $loan->cycles()
            ->orderBy('cycle_number', 'desc')
            ->first();
    }

    /**
     * Get the previous due date (the due date of the ACTIVE cycle)
     */
    public function getPreviousDueDate(Loan $loan): Carbon
    {
        // Get the active cycle
        $activeCycle = $this->getActiveCycle($loan);
        
        if ($activeCycle && $activeCycle->due_date) {
            return Carbon::parse($activeCycle->due_date);
        }
        
        // Fallback: get the latest cycle
        $latestCycle = $loan->cycles()
            ->orderBy('cycle_number', 'desc')
            ->first();
        
        if ($latestCycle && $latestCycle->due_date) {
            return Carbon::parse($latestCycle->due_date);
        }
        
        // Ultimate fallback: calculate from borrow_date
        $dueDate = $this->calculateDueDate($loan);
        if ($dueDate) {
            return $dueDate;
        }
        
        return Carbon::parse($loan->borrow_date)->addDays(30);
    }

    /**
     * Calculate the next due date for a rollover
     * CRITICAL: This adds ONE period from loan_types to the ACTIVE cycle's due date
     */
    public function calculateRolloverDueDate(Loan $loan): Carbon
    {
        if (!$loan->loanType) {
            throw new \Exception('Loan type not found for loan #' . $loan->id);
        }

        $period = (int) $loan->loanType->period;
        $unit = $loan->loanType->unit;
        
        // Get the active cycle's due date
        $previousDueDate = $this->getPreviousDueDate($loan);
        
        $newDueDate = $previousDueDate->copy();
        
        // Add one period from loan_types to the previous due date
        switch ($unit) {
            case 'days':
                $newDueDate->addDays($period);
                break;
            case 'weeks':
                $newDueDate->addWeeks($period);
                break;
            case 'months':
                $newDueDate->addMonths($period);
                break;
            case 'years':
                $newDueDate->addYears($period);
                break;
            default:
                $newDueDate->addDays($period);
        }

        Log::info('calculateRolloverDueDate', [
            'loan_id' => $loan->id,
            'period' => $period,
            'unit' => $unit,
            'previous_due_date' => $previousDueDate->format('Y-m-d'),
            'new_due_date' => $newDueDate->format('Y-m-d')
        ]);

        return $newDueDate;
    }

    /**
     * Calculate the new balance for a cycle
     * This handles the complete math for a loan cycle
     * 
     * Formula:
     * 1. Interest = Principal × (Interest Rate / 100)
     * 2. New Balance = Principal + Interest + Processing Fee
     * 3. Apply Repayments (in date order)
     * 4. Calculate Penalties (only if overdue and after grace period)
     * 5. Final Outstanding = Outstanding + Penalties
     */
    public function calculateCycleBalance(Loan $loan, LoanCycle $cycle, Carbon $calculationDate = null): array
    {
        if (!$loan->loanType) {
            throw new \Exception('Loan type not found for loan #' . $loan->id);
        }

        $calculationDate = $calculationDate ?? Carbon::now();
        $loanType = $loan->loanType;
        
        // Get cycle values
        $principal = $cycle->previous_balance > 0 ? $cycle->previous_balance : $loan->amount;
        $interestRate = (float) $cycle->interest_rate ?: (float) $loanType->interest_rate;
        $penaltyRate = (float) $loanType->penalty_rate;
        $gracePeriodDays = (int) ($loanType->grace_period_days ?? 0);
        $processingFeeRate = (float) ($loanType->processing_fee_rate ?? 0);
        
        // Step 1: Calculate Interest
        $interest = $principal * ($interestRate / 100);
        
        // Step 2: Calculate Processing Fee (if any)
        $processingFee = $principal * ($processingFeeRate / 100);
        
        // Step 3: Calculate New Balance (Principal + Interest + Processing Fee)
        $newBalance = $principal + $interest + $processingFee;
        
        // Step 4: Get Repayments for this cycle (in date order)
        $repayments = $loan->repayments()
            ->where('loan_cycle_id', $cycle->id)
            ->orderBy('repayment_date', 'asc')
            ->get();
        
        // Step 5: Apply Repayments (in date order)
        $outstanding = $newBalance;
        $totalRepayments = 0;
        $lastRepaymentDate = null;
        
        foreach ($repayments as $repayment) {
            $outstanding = max(0, $outstanding - $repayment->amount);
            $totalRepayments += $repayment->amount;
            $lastRepaymentDate = Carbon::parse($repayment->repayment_date);
        }
        
        // Step 6: Calculate Penalties (only if overdue and after grace period)
        $penalty = 0;
        $daysOverdue = 0;
        $daysSubjectToPenalty = 0;
        $isOverdue = false;
        
        $dueDate = Carbon::parse($cycle->due_date);
        
        // Check if we're past the due date and have outstanding balance
        if ($calculationDate->gt($dueDate) && $outstanding > 0) {
            $daysOverdue = (int) $calculationDate->diffInDays($dueDate);
            
            // Apply grace period
            $daysSubjectToPenalty = max(0, $daysOverdue - $gracePeriodDays);
            
            if ($daysSubjectToPenalty > 0) {
                $isOverdue = true;
                // Simple interest on original outstanding
                $penalty = $outstanding * ($penaltyRate / 100) * $daysSubjectToPenalty;
            }
        }
        
        // Step 7: Final Outstanding
        $finalOutstanding = max(0, $outstanding + $penalty);
        
        // Determine if loan is fully repaid
        $isFullyRepaid = $finalOutstanding <= 0;
        
        // Determine status for this cycle
        $cycleStatus = 'active';
        if ($isFullyRepaid) {
            $cycleStatus = 'completed';
        } elseif ($isOverdue && $daysSubjectToPenalty > 0) {
            $cycleStatus = 'overdue';
        }
        
        return [
            'principal' => $principal,
            'interest_rate' => $interestRate,
            'interest' => $interest,
            'processing_fee_rate' => $processingFeeRate,
            'processing_fee' => $processingFee,
            'new_balance' => $newBalance,
            'total_repayments' => $totalRepayments,
            'outstanding_after_repayments' => $outstanding,
            'days_overdue' => $daysOverdue,
            'grace_period_days' => $gracePeriodDays,
            'days_subject_to_penalty' => $daysSubjectToPenalty,
            'penalty_rate' => $penaltyRate,
            'penalty' => $penalty,
            'final_outstanding' => $finalOutstanding,
            'is_overdue' => $isOverdue,
            'is_fully_repaid' => $isFullyRepaid,
            'cycle_status' => $cycleStatus,
            'last_repayment_date' => $lastRepaymentDate,
            'due_date' => $dueDate,
            'calculation_date' => $calculationDate,
        ];
    }

    /**
     * Create the initial cycle for a loan
     */
    public function createInitialCycle(Loan $loan): LoanCycle
    {
        if (!$loan->loanType) {
            throw new \Exception('Loan type not found for loan #' . $loan->id);
        }

        $loanType = $loan->loanType;
        $interestRate = (float) $loanType->interest_rate;
        $interest = $loan->amount * ($interestRate / 100);
        $dueDate = $this->calculateDueDate($loan);
        
        if (!$dueDate) {
            throw new \Exception('Could not calculate due date for loan #' . $loan->id);
        }

        // Check if cycle already exists
        $existingCycle = $loan->cycles()
            ->where('cycle_number', 1)
            ->first();

        if ($existingCycle) {
            Log::info('Cycle #1 already exists for loan #' . $loan->id);
            return $existingCycle;
        }

        // Create Cycle #1
        $cycle = LoanCycle::create([
            'loan_id' => $loan->id,
            'cycle_number' => 1,
            'previous_balance' => 0,
            'interest_capitalized' => $interest,
            'new_balance' => $loan->amount + $interest,
            'interest_rate' => $interestRate,
            'start_date' => $loan->borrow_date,
            'due_date' => $dueDate,
            'status' => 'active',
            'notes' => 'Initial loan cycle - ' . $loanType->name,
        ]);

        // Update the loan
        $loan->cycle = 1;
        $loan->due_date = $dueDate;
        $loan->calculated_due_date = $dueDate;
        $loan->original_amount = $loan->amount;
        $loan->capitalized_interest = $interest;
        $loan->save();

        Log::info('Initial cycle created for loan #' . $loan->id, [
            'loan_type_id' => $loan->loan_type_id,
            'loan_type_name' => $loanType->name,
            'cycle_number' => 1,
            'interest' => $interest,
            'new_balance' => $loan->amount + $interest,
            'due_date' => $dueDate->format('Y-m-d')
        ]);

        return $cycle;
    }

    /**
     * Execute a loan rollover
     * This creates a new cycle and marks the previous one as completed
     */
    public function executeRollover(Loan $loan, array $options = []): array
    {
        if (!in_array($loan->status, ['active', 'overdue', 'disbursed'])) {
            throw new \Exception('This loan is not eligible for rollover.');
        }

        if ($loan->isDefaulted()) {
            throw new \Exception('This loan is defaulted. Please resolve recovery case first.');
        }

        if ($loan->isInForbearance()) {
            throw new \Exception('This loan is in forbearance. Cannot rollover.');
        }

        if (!$loan->loanType) {
            throw new \Exception('Loan type not found for loan #' . $loan->id);
        }

        $notes = $options['notes'] ?? null;
        $loanType = $loan->loanType;
        
        // Get the active cycle
        $activeCycle = $this->getActiveCycle($loan);

        if (!$activeCycle) {
            throw new \Exception('No active cycle found for loan #' . $loan->id);
        }

        // Calculate the final outstanding for the current cycle
        $cycleCalculation = $this->calculateCycleBalance($loan, $activeCycle);
        
        // The final outstanding becomes the principal for the next cycle
        $newPrincipal = $cycleCalculation['final_outstanding'];
        
        // If the loan is fully repaid, mark it as such
        if ($newPrincipal <= 0) {
            $activeCycle->update(['status' => 'completed']);
            $loan->status = 'repaid';
            $loan->save();
            
            return [
                'success' => true,
                'message' => 'Loan is fully repaid. No rollover needed.',
                'data' => [
                    'is_repaid' => true,
                    'final_outstanding' => 0,
                ]
            ];
        }

        // Get the next cycle number
        $newCycleNumber = $activeCycle->cycle_number + 1;
        
        // Calculate interest for the new cycle using loan_type interest rate
        $interestRate = (float) $loanType->interest_rate;
        $interest = $newPrincipal * ($interestRate / 100);
        $newBalance = $newPrincipal + $interest;
        
        // CRITICAL: Calculate the new due date from the ACTIVE cycle's due date + period
        $newDueDate = $this->calculateRolloverDueDate($loan);
        
        // CRITICAL: Start date is the ACTIVE cycle's due date
        $newStartDate = Carbon::parse($activeCycle->due_date);
        
        // Mark the active cycle as completed
        $activeCycle->update(['status' => 'completed']);
        
        // Create the new cycle (status = active)
        $newCycle = LoanCycle::create([
            'loan_id' => $loan->id,
            'cycle_number' => $newCycleNumber,
            'previous_balance' => $newPrincipal,
            'interest_capitalized' => $interest,
            'new_balance' => $newBalance,
            'interest_rate' => $interestRate,
            'start_date' => $newStartDate,
            'due_date' => $newDueDate,
            'status' => 'active',
            'notes' => $notes ?? 'Loan rollover - Cycle ' . $newCycleNumber . ' (' . $loanType->name . ')',
        ]);

        // Update the loan
        $loan->amount = $newBalance;
        $loan->cycle = $newCycleNumber;
        $loan->due_date = $newDueDate;
        $loan->calculated_due_date = $newDueDate;
        $loan->status = Loan::STATUS_DISBURSED;
        $loan->capitalized_interest = ($loan->capitalized_interest ?? 0) + $interest;
        $loan->days_overdue = 0;
        $loan->is_non_performing = false;
        $loan->default_triggered = false;
        $loan->save();

        Log::info('Rollover executed for loan #' . $loan->id, [
            'loan_type_id' => $loan->loan_type_id,
            'loan_type_name' => $loanType->name,
            'new_cycle' => $newCycleNumber,
            'previous_balance' => $activeCycle->new_balance,
            'new_principal' => $newPrincipal,
            'interest' => $interest,
            'new_balance' => $newBalance,
            'new_due_date' => $newDueDate->format('Y-m-d'),
            'new_start_date' => $newStartDate->format('Y-m-d')
        ]);

        return [
            'success' => true,
            'message' => "Loan rolled over successfully. New balance: KES " . number_format($newBalance, 2),
            'data' => [
                'cycle' => $newCycle,
                'new_balance' => $newBalance,
                'cycle_number' => $newCycleNumber,
                'due_date' => $newDueDate->format('Y-m-d'),
                'due_date_formatted' => $newDueDate->format('M d, Y'),
                'start_date' => $newStartDate->format('Y-m-d'),
                'start_date_formatted' => $newStartDate->format('M d, Y'),
                'interest_capitalized' => $interest,
                'previous_balance' => $newPrincipal,
                'interest_rate' => $interestRate,
                'period' => (int) $loanType->period,
                'period_unit' => $loanType->unit,
                'period_display' => $this->getPeriodDisplay($loan),
                'loan_type_id' => $loan->loan_type_id,
                'loan_type_name' => $loanType->name,
                'cycle_calculation' => $cycleCalculation,
            ]
        ];
    }

    /**
     * Get the loan type period display
     */
    public function getPeriodDisplay(Loan $loan): string
    {
        if (!$loan->loanType) {
            return 'N/A';
        }
        
        $period = (int) $loan->loanType->period;
        $unit = $loan->loanType->unit;
        
        // Pluralize the unit if period > 1
        $unitDisplay = $unit;
        if ($period > 1) {
            switch ($unit) {
                case 'day': $unitDisplay = 'days'; break;
                case 'week': $unitDisplay = 'weeks'; break;
                case 'month': $unitDisplay = 'months'; break;
                case 'year': $unitDisplay = 'years'; break;
            }
        }
        
        return $period . ' ' . $unitDisplay;
    }

    /**
     * Get rollover preview data
     */
    public function getRolloverPreview(Loan $loan): array
    {
        if (!$loan->loanType) {
            throw new \Exception('Loan type not found for loan #' . $loan->id);
        }

        $loanType = $loan->loanType;

        // Get the active cycle
        $activeCycle = $this->getActiveCycle($loan);

        if (!$activeCycle) {
            throw new \Exception('No active cycle found for this loan');
        }

        // Calculate the cycle balance
        $cycleCalculation = $this->calculateCycleBalance($loan, $activeCycle);
        
        $newPrincipal = $cycleCalculation['final_outstanding'];
        $interestRate = (float) $loanType->interest_rate;
        $interest = $newPrincipal * ($interestRate / 100);
        $newBalance = $newPrincipal + $interest;
        $newDueDate = $this->calculateRolloverDueDate($loan);
        $newStartDate = Carbon::parse($activeCycle->due_date);
        
        return [
            'loan_type_id' => $loan->loan_type_id,
            'loan_type_name' => $loanType->name,
            'period' => (int) $loanType->period,
            'period_unit' => $loanType->unit,
            'period_display' => $this->getPeriodDisplay($loan),
            'interest_rate' => $interestRate,
            
            // Current cycle info
            'current_cycle' => $activeCycle->cycle_number,
            'current_due_date' => Carbon::parse($activeCycle->due_date)->format('M d, Y'),
            'current_balance' => $activeCycle->new_balance,
            'outstanding_after_repayments' => $cycleCalculation['outstanding_after_repayments'],
            'days_overdue' => $cycleCalculation['days_overdue'],
            'days_subject_to_penalty' => $cycleCalculation['days_subject_to_penalty'],
            'penalty' => $cycleCalculation['penalty'],
            'final_outstanding' => $cycleCalculation['final_outstanding'],
            'is_overdue' => $cycleCalculation['is_overdue'],
            
            // Next cycle info
            'new_cycle' => $activeCycle->cycle_number + 1,
            'new_start_date' => $newStartDate->format('M d, Y'),
            'new_due_date' => $newDueDate->format('M d, Y'),
            'new_due_date_raw' => $newDueDate->format('Y-m-d'),
            'interest_to_capitalize' => $interest,
            'new_balance' => $newBalance,
            
            // Summary
            'original_principal' => $loan->original_amount ?? $loan->amount,
            'total_capitalized_interest' => $loan->capitalized_interest ?? 0,
            'cycle_calculation' => $cycleCalculation,
        ];
    }

    /**
     * Calculate loan metrics for display
     * Uses the active cycle's data for accurate display
     */
    public function calculateLoanMetrics(Loan $loan): array
    {
        if (!$loan->loanType || !$loan->borrow_date) {
            throw new \InvalidArgumentException('Loan type or borrow date missing');
        }

        $loanType = $loan->loanType;
        $principal = $loan->amount;
        $interestRate = (float) $loanType->interest_rate;
        $period = (int) $loanType->period;
        $periodUnit = $loanType->unit;
        $basePenaltyRate = (float) $loanType->penalty_rate;
        $borrowDate = Carbon::parse($loan->borrow_date);
        
        // Get the active cycle
        $activeCycle = $this->getActiveCycle($loan);
        
        if ($activeCycle && $activeCycle->due_date) {
            $dueDate = Carbon::parse($activeCycle->due_date);
        } else {
            // Fallback: calculate from borrow_date (only for new loans without cycles)
            $dueDate = $this->calculateDueDate($loan);
            if (!$dueDate) {
                $dueDate = $borrowDate->copy()->addDays(30);
            }
        }

        // Calculate cycle balance
        if ($activeCycle) {
            $cycleCalculation = $this->calculateCycleBalance($loan, $activeCycle);
            $interest = $cycleCalculation['interest'];
            $totalRepayments = $cycleCalculation['total_repayments'];
            $penaltyAmount = $cycleCalculation['penalty'];
            $daysLate = $cycleCalculation['days_subject_to_penalty'];
            $outstandingBalance = $cycleCalculation['final_outstanding'];
            $outstandingAtDueDate = $cycleCalculation['outstanding_after_repayments'];
            $principalPlusInterest = $cycleCalculation['new_balance'];
            $totalDue = $cycleCalculation['new_balance'] + $penaltyAmount;
        } else {
            // Fallback: calculate without cycle
            $interest = $principal * ($interestRate / 100);
            $principalPlusInterest = $principal + $interest;
            $repayments = $loan->repayments->sortBy('repayment_date');
            $lastRepaymentDate = $repayments->isNotEmpty() 
                ? Carbon::parse($repayments->last()->repayment_date) 
                : null;
            $totalRepayments = $repayments->sum('amount');
            $outstandingAtDueDate = max($principalPlusInterest - $totalRepayments, 0);
            $penaltyAmount = 0;
            $daysLate = 0;
            $outstandingBalance = max(0, $principalPlusInterest - $totalRepayments);
            $totalDue = $principalPlusInterest;
        }

        // Get last repayment date
        $lastRepayment = $loan->repayments()->orderBy('repayment_date', 'desc')->first();
        $lastRepaymentDate = $lastRepayment ? Carbon::parse($lastRepayment->repayment_date) : null;

        // Calculate broker fees
        $brokerFees = 0;
        $brokerRate = 0;
        $brokerPenaltyFees = 0;
        $penaltyRate = 0;
        $totalBrokerFees = 0;
        $isBrokered = false;
        
        if ($loan->broker_status == 1 && $loan->user && $loan->user->borrower && $loan->user->borrower->broker) {
            $isBrokered = true;
            $borrower = $loan->user->borrower;
            $broker = $borrower->broker;
            $clientType = $borrower->client_type ?? 0;
            
            $brokerRate = ($clientType == 0) 
                ? $broker->interest_client 
                : $broker->interest_broker;
            $brokerFees = $interest * ($brokerRate / 100);
            
            $penaltyRate = ($clientType == 0) 
                ? $broker->penalty_client 
                : $broker->penalty_broker;
            $brokerPenaltyFees = $penaltyAmount * ($penaltyRate / 100);
            
            $totalBrokerFees = $brokerFees + $brokerPenaltyFees;
        }

        // Calculate all final amounts
        $netEarnings = ($interest + $penaltyAmount) - $totalBrokerFees;
        $pl = $netEarnings - max(0, $totalRepayments - $principal);

        return [
            'loan_type_id' => $loan->loan_type_id,
            'loan_type_name' => $loanType->name,
            'period' => $period,
            'period_unit' => $periodUnit,
            'period_display' => $this->getPeriodDisplay($loan),
            'principal' => $principal,
            'interest' => $interest,
            'interest_rate' => $interestRate,
            'borrow_date' => $borrowDate,
            'due_date' => $dueDate,
            'last_repayment_date' => $lastRepaymentDate,
            'days_late' => $daysLate,
            'base_penalty_rate' => $basePenaltyRate,
            'penalty_rate' => $penaltyRate,
            'penalty_amount' => $penaltyAmount,
            'is_brokered' => $isBrokered,
            'broker_fees' => $brokerFees,
            'brokerRate' => $brokerRate,
            'broker_penalty_fees' => $brokerPenaltyFees,
            'total_broker_fees' => $totalBrokerFees,
            'client_type' => $loan->user->borrower->client_type ?? 0,
            'total_repayments' => $totalRepayments,
            'principal_plus_interest' => $principalPlusInterest,
            'outstanding_balance' => $outstandingBalance,
            'outstanding_at_due' => $outstandingAtDueDate,
            'total_due' => $totalDue,
            'net_earnings' => $netEarnings,
            'pl' => $pl,
            'is_overdue' => $daysLate > 0,
            'is_repaid' => $loan->status === 'repaid',
            'active_cycle' => $activeCycle ? [
                'number' => $activeCycle->cycle_number,
                'start_date' => $activeCycle->start_date->format('M d, Y'),
                'due_date' => $activeCycle->due_date->format('M d, Y'),
                'balance' => $activeCycle->new_balance,
            ] : null,
        ];
    }
}