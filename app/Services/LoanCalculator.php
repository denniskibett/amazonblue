<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanType;
use App\Models\User;
use Carbon\Carbon;

class LoanCalculator
{
    /**
     * Get the effective due date for a loan
     * If due_date exists, use it. Otherwise calculate from borrow_date + period
     */
    public function getEffectiveDueDate(Loan $loan): Carbon
    {
        if ($loan->due_date) {
            return Carbon::parse($loan->due_date);
        }
        
        // Calculate from borrow_date + period
        $borrowDate = Carbon::parse($loan->borrow_date);
        $period = $loan->loanType->period ?? 30;
        $unit = $loan->loanType->unit ?? 'days';
        
        $dueDate = $borrowDate->copy();
        switch ($unit) {
            case 'days': $dueDate->addDays($period); break;
            case 'weeks': $dueDate->addWeeks($period); break;
            case 'months': $dueDate->addMonths($period); break;
            case 'years': $dueDate->addYears($period); break;
            default: $dueDate->addDays($period); break;
        }
        
        return $dueDate;
    }

    /**
     * Calculate the number of cycles that should have occurred
     */
    public function calculatePotentialCycles(Loan $loan): array
    {
        $effectiveDueDate = $this->getEffectiveDueDate($loan);
        
        $period = $loan->loanType->period ?? 30;
        $unit = $loan->loanType->unit ?? 'days';
        
        $periodDays = match($unit) {
            'days' => $period,
            'weeks' => $period * 7,
            'months' => $period * 30,
            'years' => $period * 365,
            default => $period,
        };

        $periodDisplay = $period . ' ' . $unit;
        if ($period > 1) {
            $periodDisplay = $period . ' ' . $unit;
        }

        $dueDate = $effectiveDueDate;
        $today = Carbon::now();
        
        if ($today->lte($dueDate)) {
            return [
                'missed_cycles' => 0,
                'potential_cycles' => 0,
                'days_overdue' => 0,
                'period_days' => $periodDays,
                'period_display' => $periodDisplay,
                'next_due_date' => $dueDate->format('M d, Y'),
                'current_due_date' => $dueDate->format('M d, Y'),
                'previous_due_date' => $dueDate->format('M d, Y'),
                'next_due_date_raw' => $dueDate->format('Y-m-d'),
            ];
        }

        $daysOverdue = $today->diffInDays($dueDate);
        $missedCycles = floor($daysOverdue / $periodDays);
        
        $nextDueDate = $dueDate->copy();
        switch ($unit) {
            case 'days': $nextDueDate->addDays($period); break;
            case 'weeks': $nextDueDate->addWeeks($period); break;
            case 'months': $nextDueDate->addMonths($period); break;
            case 'years': $nextDueDate->addYears($period); break;
            default: $nextDueDate->addDays($period); break;
        }

        return [
            'missed_cycles' => (int)$missedCycles,
            'potential_cycles' => (int)($missedCycles + 1),
            'days_overdue' => (int)$daysOverdue,
            'period_days' => $periodDays,
            'period_display' => $periodDisplay,
            'next_due_date' => $nextDueDate->format('M d, Y'),
            'next_due_date_raw' => $nextDueDate->format('Y-m-d'),
            'current_due_date' => $dueDate->format('M d, Y'),
            'current_due_date_raw' => $dueDate->format('Y-m-d'),
            'previous_due_date' => $dueDate->format('M d, Y'),
        ];
    }

    /**
     * Calculate projected rollovers with both Simple and Compound interest
     */
    public function calculateProjectedRollovers(Loan $loan, int $maxCycles = 10): array
    {
        $effectiveDueDate = $this->getEffectiveDueDate($loan);
        $period = $loan->loanType->period ?? 30;
        $unit = $loan->loanType->unit ?? 'days';
        $interestRate = $loan->loanType->interest_rate ?? 0;
        $originalPrincipal = $loan->original_amount ?? $loan->amount;
        $currentAmount = $loan->amount;
        
        $projections = [];
        $currentDueDate = $effectiveDueDate->copy();
        $currentCycle = $loan->cycle;
        
        $cycleInfo = $this->calculatePotentialCycles($loan);
        $missedCycles = $cycleInfo['missed_cycles'] ?? 0;
        $startCycle = $currentCycle + $missedCycles;
        
        // Simple interest: always based on original principal
        $simpleInterestPerCycle = ($interestRate / 100) * $originalPrincipal;
        $simpleAmount = $originalPrincipal;
        
        // Compound interest: starts from current amount, grows each cycle
        $compoundAmount = $loan->amount;
        
        for ($i = 0; $i < $maxCycles; $i++) {
            $cycleNumber = $startCycle + $i + 1;
            
            // --- SIMPLE INTEREST ---
            // Simple interest is always the same (based on original principal)
            $simpleInterestAmount = $simpleInterestPerCycle;
            $simplePreviousBalance = $simpleAmount;
            $simpleNewAmount = $simpleAmount + $simpleInterestAmount;
            
            // --- COMPOUND INTEREST ---
            // Compound interest is calculated on the current balance
            $compoundInterestAmount = ($interestRate / 100) * $compoundAmount;
            $compoundPreviousBalance = $compoundAmount;
            $compoundNewAmount = $compoundAmount + $compoundInterestAmount;
            
            // Calculate next due date
            $nextDueDate = $currentDueDate->copy();
            switch ($unit) {
                case 'days': $nextDueDate->addDays($period); break;
                case 'weeks': $nextDueDate->addWeeks($period); break;
                case 'months': $nextDueDate->addMonths($period); break;
                case 'years': $nextDueDate->addYears($period); break;
                default: $nextDueDate->addDays($period); break;
            }
            
            $projections[] = [
                'cycle_number' => $cycleNumber,
                'due_date' => $nextDueDate->format('M d, Y'),
                'due_date_raw' => $nextDueDate->format('Y-m-d'),
                'period_display' => $period . ' ' . $unit,
                // Simple Interest
                'simple_previous_balance' => $simplePreviousBalance,
                'simple_interest' => $simpleInterestAmount,
                'simple_new_balance' => $simpleNewAmount,
                // Compound Interest
                'compound_previous_balance' => $compoundPreviousBalance,
                'compound_interest' => $compoundInterestAmount,
                'compound_new_balance' => $compoundNewAmount,
            ];
            
            // Update for next cycle
            $simpleAmount = $simpleNewAmount;
            $compoundAmount = $compoundNewAmount;  // <-- THIS IS THE KEY! It compounds
            $currentDueDate = $nextDueDate;
        }
        
        return $projections;
    }

    /**
     * Calculate interest for a specific cycle type
     */
    public function calculateCycleInterest(Loan $loan, string $interestType = 'compound'): float
    {
        if (!$loan->loanType) {
            return 0;
        }

        $interestRate = $loan->loanType->interest_rate;
        
        if ($interestType === 'simple') {
            // Simple interest: based on original principal
            $originalPrincipal = $loan->original_amount ?? $loan->amount;
            return ($interestRate / 100) * $originalPrincipal;
        } else {
            // Compound interest: based on current amount
            return ($interestRate / 100) * $loan->amount;
        }
    }

    /**
     * Calculate new balance after rollover with specified interest type
     */
    public function calculateRolloverNewBalance(Loan $loan, string $interestType = 'compound'): float
    {
        $interest = $this->calculateCycleInterest($loan, $interestType);
        return $loan->amount + $interest;
    }

    /**
     * Calculate new due date for rollover (one cycle at a time)
     */
    public function calculateRolloverDueDate(Loan $loan): Carbon
    {
        $period = $loan->loanType->period ?? 30;
        $unit = $loan->loanType->unit ?? 'days';
        
        $effectiveDueDate = $this->getEffectiveDueDate($loan);
        $newDueDate = $effectiveDueDate->copy();
        
        switch ($unit) {
            case 'days': $newDueDate->addDays($period); break;
            case 'weeks': $newDueDate->addWeeks($period); break;
            case 'months': $newDueDate->addMonths($period); break;
            case 'years': $newDueDate->addYears($period); break;
            default: $newDueDate->addDays($period); break;
        }
        
        return $newDueDate;
    }

    /**
     * Get rollover preview data
     */
    public function getRolloverPreview(Loan $loan): array
    {
        $effectiveDueDate = $this->getEffectiveDueDate($loan);
        $previousDueDate = $effectiveDueDate->format('M d, Y');
        
        $newDueDate = $this->calculateRolloverDueDate($loan);
        $cycleInfo = $this->calculatePotentialCycles($loan);
        
        // Calculate both interest types
        $simpleInterest = $this->calculateCycleInterest($loan, 'simple');
        $compoundInterest = $this->calculateCycleInterest($loan, 'compound');
        
        $simpleNewBalance = $loan->amount + $simpleInterest;
        $compoundNewBalance = $loan->amount + $compoundInterest;
        
        $projections = $this->calculateProjectedRollovers($loan, 5);
        
        return [
            'current_balance' => $loan->amount,
            'current_cycle' => $loan->cycle,
            'new_cycle' => $loan->cycle + 1,
            'new_due_date' => $newDueDate->format('M d, Y'),
            'new_due_date_raw' => $newDueDate->format('Y-m-d'),
            'previous_due_date' => $previousDueDate,
            'interest_rate' => $loan->loanType->interest_rate ?? 0,
            'period' => $loan->loanType->period ?? 30,
            'period_unit' => $loan->loanType->unit ?? 'days',
            'grace_days_balance' => $loan->grace_days_balance,
            'previous_balance' => $loan->amount,
            'original_principal' => $loan->original_amount ?? $loan->amount,
            // Simple Interest
            'simple_interest' => $simpleInterest,
            'simple_new_balance' => $simpleNewBalance,
            // Compound Interest
            'compound_interest' => $compoundInterest,
            'compound_new_balance' => $compoundNewBalance,
            // Cycle info
            'missed_cycles' => $cycleInfo['missed_cycles'],
            'potential_cycles' => $cycleInfo['potential_cycles'],
            'days_overdue' => $cycleInfo['days_overdue'],
            'period_display' => $cycleInfo['period_display'],
            'next_due_date_if_rolled' => $cycleInfo['next_due_date'],
            'current_due_date' => $cycleInfo['current_due_date'],
            'projections' => $projections,
        ];
    }

    /**
     * Calculate loan metrics for a given loan
     */
    public function calculateLoanMetrics(Loan $loan)
    {
        // 1. Get loan parameters with validation
        if (!$loan->loanType || !$loan->borrow_date) {
            throw new \InvalidArgumentException('Loan type or borrow date missing');
        }

        $principal = $loan->amount;
        $interestRate = $loan->loanType->interest_rate;
        $period = $loan->loanType->period;
        $periodUnit = $loan->loanType->unit;
        $basePenaltyRate = $loan->loanType->penalty_rate;
        $borrowDate = Carbon::parse($loan->borrow_date);
        
        // 2. Calculate due date
        $dueDate = $borrowDate->copy();
        switch ($periodUnit) {
            case 'days': $dueDate->addDays($period); break;
            case 'weeks': $dueDate->addWeeks($period); break;
            case 'months': $dueDate->addMonths($period); break;
            case 'years': $dueDate->addYears($period); break;
        }

        // 3. Calculate interest and principal plus interest
        $interest = $principal * ($interestRate / 100);
        $principalPlusInterest = $principal + $interest;

        // 4. Get sorted repayments
        $repayments = $loan->repayments->sortBy('repayment_date');
        $lastRepaymentDate = $repayments->isNotEmpty() 
            ? Carbon::parse($repayments->last()->repayment_date) 
            : null;

        // 5. Calculate repayments before and after due date
        $repaymentsBeforeDue = $repayments->filter(function($repayment) use ($dueDate) {
            return Carbon::parse($repayment->repayment_date)->lt($dueDate);
        })->sum('amount');

        $repaymentsAfterDue = $repayments->filter(function($repayment) use ($dueDate) {
            return Carbon::parse($repayment->repayment_date)->gte($dueDate);
        })->sum('amount');

        $totalRepayments = $repaymentsBeforeDue + $repaymentsAfterDue;

        // 6. Calculate outstanding at due date
        $outstandingAtDueDate = max($principalPlusInterest - $repaymentsBeforeDue, 0);

        // 7. Calculate penalty
        $penaltyAmount = 0;
        $daysLate = 0;

        if ($outstandingAtDueDate > 0) {
            $currentBalance = $outstandingAtDueDate;
            $currentDate = $dueDate->copy();
            $endDate = ($loan->status === 'repaid' && $repayments->isNotEmpty()) 
                ? Carbon::parse($repayments->last()->repayment_date) 
                : now();

            $repaymentsByDate = $repayments->filter(function($repayment) use ($dueDate) {
                return Carbon::parse($repayment->repayment_date)->gte($dueDate);
            })->groupBy(function($repayment) {
                return Carbon::parse($repayment->repayment_date)->toDateString();
            });

            $currentDate->addDay();

            while ($currentDate->lte($endDate)) {
                if ($currentBalance <= 0) break;
                
                $dateKey = $currentDate->toDateString();
                $dailyRepayment = $repaymentsByDate->has($dateKey) 
                    ? $repaymentsByDate->get($dateKey)->sum('amount') 
                    : 0;

                $currentBalance = max($currentBalance - $dailyRepayment, 0);
                
                if ($currentBalance > 0) {
                    $penaltyAmount += ($basePenaltyRate / 100) * $outstandingAtDueDate;
                    $daysLate++;
                }
                
                $currentDate->addDay();
            }
        }

        // 8. Calculate broker fees
        $brokerFees = 0;
        $brokerRate = 0;
        $brokerPenaltyFees = 0;
        $penaltyRate = 0;
        $totalBrokerFees = 0;
        $isBrokered = false;
        
        if ($loan->broker_status == 1 && $loan->user->borrower && $loan->user->borrower->broker) {
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

        // 9. Calculate all final amounts
        $totalDue = $principalPlusInterest + $penaltyAmount;
        $outstandingBalance = max(0, $totalDue - $totalRepayments);
        $netEarnings = ($interest + $penaltyAmount) - $totalBrokerFees;
        $pl = $netEarnings - max(0, $totalRepayments - $principal);

        return [
            'principal' => $principal,
            'interest' => $interest,
            'interest_rate' => $interestRate,
            'period' => $period,
            'period_unit' => $periodUnit,
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
            'total_repayments_before_due' => $repaymentsBeforeDue,
            'total_repayments_after_due' => $repaymentsAfterDue,
            'total_repayments' => $totalRepayments,
            'principal_plus_interest' => $principalPlusInterest,
            'outstanding_balance' => $outstandingBalance,
            'outstanding_at_due' => $outstandingAtDueDate,
            'total_due' => $totalDue,
            'net_earnings' => $netEarnings,
            'pl' => $pl,
            'is_overdue' => $daysLate > 0,
            'is_repaid' => $loan->status === 'repaid',
        ];
    }

    /**
     * Get grace period status for a loan
     */
    public function getGracePeriodStatus(Loan $loan): array
    {
        $isWithinGrace = $loan->isWithinGracePeriod();
        $remainingDays = $loan->getRemainingGraceDays();
        
        return [
            'is_within_grace_period' => $isWithinGrace,
            'remaining_days' => $remainingDays,
            'grace_days_balance' => $loan->grace_days_balance,
            'grace_days_earned' => $loan->grace_days_earned,
            'grace_days_used' => $loan->grace_days_used,
            'status_text' => $isWithinGrace 
                ? "Grace Period Active ({$remainingDays} days remaining)"
                : ($loan->grace_days_balance > 0 
                    ? "{$loan->grace_days_balance} grace days available"
                    : "No grace days available"),
        ];
    }

    /**
     * Get loan status summary
     */
    public function getStatusSummary(Loan $loan): array
    {
        return [
            'status' => $loan->status,
            'status_label' => $loan->status_label,
            'status_color' => $loan->status_color,
            'is_active' => $loan->isActive(),
            'is_overdue' => $loan->isOverdue(),
            'is_defaulted' => $loan->isDefaulted(),
            'is_in_recovery' => $loan->isInRecovery(),
            'is_in_forbearance' => $loan->isInForbearance(),
            'is_performing' => $loan->isPerforming(),
            'cycle_display' => $loan->cycle_display,
        ];
    }

    // ============ EXISTING METHODS ============

    public function getDueLoans(User $user)
    {
        $baseQuery = Loan::with(['borrower', 'loanType'])
            ->where('status', 'disbursed')
            ->join('loan_types', 'loans.loan_type_id', '=', 'loan_types.id');

        switch ($user->role) {
            case 'borrower':
                $baseQuery->where('loans.user_id', $user->id);
                break;
            case 'broker':
                $borrowerIds = $user->broker->borrowers()->pluck('user_id');
                $baseQuery->whereIn('loans.user_id', $borrowerIds);
                break;
        }

        return $baseQuery->get()->map(function ($loan) {
            $borrowDate = Carbon::parse($loan->borrow_date)->startOfDay();
            $dueDate = $borrowDate->copy();

            switch ($loan->loanType->unit) {
                case 'days': $dueDate->addDays($loan->loanType->period); break;
                case 'weeks': $dueDate->addWeeks($loan->loanType->period); break;
                case 'months': $dueDate->addMonths($loan->loanType->period); break;
            }

            $today = Carbon::now()->startOfDay();
            $remainingDays = $today->diffInDays($dueDate, false);

            $loan->due_date = $dueDate;
            $loan->remaining_days = $remainingDays;
            $loan->status = $remainingDays < 0 ? 'overdue' : 'disbursed';
            $loan->overdue_days = $remainingDays < 0 ? abs($remainingDays) : 0;

            if ($remainingDays < 0) {
                $interval = $today->diff($dueDate);
                $loan->overdue_period = ['months' => $interval->m, 'days' => $interval->d];
            }

            return $loan;
        })->sortBy('remaining_days');
    }

    public function getLoanStats(User $user, string $period = 'month')
    {
        $currentPeriod = $this->getPeriodStart($period);

        return [
            'totalLoans' => $user->loans()->count(),
            'loansThisPeriod' => $user->loans()
                ->where('created_at', '>=', $currentPeriod)
                ->count(),
            'completedLoans' => $user->loans()->repaid()->count(),
            'completedThisPeriod' => $user->loans()
                ->repaid()
                ->where('updated_at', '>=', $currentPeriod)
                ->count(),
            'totalBorrowed' => $user->loans()->sum('amount'),
            'borrowedThisPeriod' => $user->loans()
                ->where('borrow_date', '>=', $currentPeriod)
                ->sum('amount'),
            'activeLoans' => $user->loans()->active()->count(),
        ];
    }

    protected function getPeriodStart(string $period)
    {
        return match($period) {
            'day' => Carbon::today(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
    }
}