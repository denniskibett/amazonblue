<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use Carbon\Carbon;

class LoanCalculator
{
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

        // 7. Calculate penalty using the more accurate method from old controller
        $penaltyAmount = 0;
        $daysLate = 0;  // Changed from -1 to 0 since we'll adjust differently

        if ($outstandingAtDueDate > 0) {
            $currentBalance = $outstandingAtDueDate;
            $currentDate = $dueDate->copy();
            $endDate = ($loan->status === 'repaid' && $repayments->isNotEmpty()) 
                ? Carbon::parse($repayments->last()->repayment_date) 
                : now();

            // Group repayments by date for easier processing
            $repaymentsByDate = $repayments->filter(function($repayment) use ($dueDate) {
                return Carbon::parse($repayment->repayment_date)->gte($dueDate);
            })->groupBy(function($repayment) {
                return Carbon::parse($repayment->repayment_date)->toDateString();
            });

            // Skip the first day (due date) if you don't want to count it
            $currentDate->addDay();

            while ($currentDate->lte($endDate)) {
                if ($currentBalance <= 0) break;
                
                // Get repayments for this day if any
                $dateKey = $currentDate->toDateString();
                $dailyRepayment = $repaymentsByDate->has($dateKey) 
                    ? $repaymentsByDate->get($dateKey)->sum('amount') 
                    : 0;

                // Apply repayment to balance
                $currentBalance = max($currentBalance - $dailyRepayment, 0);
                
                // Add penalty for this day if balance remains
                if ($currentBalance > 0) {
                    $penaltyAmount += ($basePenaltyRate / 100) * $outstandingAtDueDate;
                    $daysLate++;
                }
                
                $currentDate->addDay();
            }
        }

        // 8. Calculate broker fees and penalties - enhanced version
            $brokerFees = 0;
            $brokerRate =0;
            $brokerPenaltyFees = 0;
            $penaltyRate = 0;
            $totalBrokerFees = 0;
            $isBrokered = false;
            
            if ($loan->broker_status == 1 && $loan->user->borrower && $loan->user->borrower->broker) {
                $isBrokered = true;
                $borrower = $loan->user->borrower;
                $broker = $borrower->broker;
                $clientType = $borrower->client_type ?? 0;
                
                // Broker interest rate
                $brokerRate = ($clientType == 0) 
                    ? $broker->interest_client 
                    : $broker->interest_broker;
                $brokerFees = $interest * ($brokerRate / 100);
                
                // Broker penalty rate
                $penaltyRate = ($clientType == 0) 
                    ? $broker->penalty_client 
                    : $broker->penalty_broker;
                $brokerPenaltyFees = $penaltyAmount * ($penaltyRate / 100);
                
                $totalBrokerFees = $brokerFees + $brokerPenaltyFees;
            }

            // 9. Calculate all final amounts
            $totalDue = $principalPlusInterest + $penaltyAmount;
            $outstandingBalance = max(0, $totalDue - $totalRepayments);
            
            // Calculate net earnings (PL) - this is what the company actually earns
            $netEarnings = ($interest + $penaltyAmount) - $totalBrokerFees;
            
            // Calculate profit/loss after repayments
            $pl = $netEarnings - max(0, $totalRepayments - $principal );

            return [
                // Basic loan info
                'principal' => $principal,
                'interest' => $interest,
                'interest_rate' => $interestRate,
                'period' => $period,
                'period_unit' => $periodUnit,
                
                // Dates
                'borrow_date' => $borrowDate,
                'due_date' => $dueDate,
                'last_repayment_date' => $lastRepaymentDate,
                'days_late' => $daysLate,
                
                // Penalty info
                'base_penalty_rate' => $basePenaltyRate,
                'penalty_rate' => $penaltyRate,
                'penalty_amount' => $penaltyAmount,
                
                // Broker info
                'is_brokered' => $isBrokered,
                'broker_fees' => $brokerFees,
                'brokerRate' => $brokerRate,
                'broker_penalty_fees' => $brokerPenaltyFees,
                'total_broker_fees' => $totalBrokerFees,
                'client_type' => $loan->user->borrower->client_type ?? 0,
                
                // Repayment info
                'total_repayments_before_due' => $repaymentsBeforeDue,
                'total_repayments_after_due' => $repaymentsAfterDue,
                'total_repayments' => $totalRepayments,
                
                // Amount calculations
                'principal_plus_interest' => $principalPlusInterest,
                'outstanding_balance' => $outstandingBalance,
                'outstanding_at_due' => $outstandingAtDueDate,
                'total_due' => $totalDue,
                'net_earnings' => $netEarnings,
                'pl' => $pl,
                
                // Status info
                'is_overdue' => $daysLate > 0,
                'is_repaid' => $loan->status === 'repaid',
            ];
        }

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
            'completedLoans' => $user->loans()->completed()->count(),
            'completedThisPeriod' => $user->loans()
                ->completed()
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