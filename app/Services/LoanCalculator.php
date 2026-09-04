<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Investment;
use App\Models\Partner;
use App\Models\PartnerTransaction;
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
     * 
     * @param Loan $loan
     * @param int|null $customPeriodDays - Optional custom period in days
     * @return Carbon
     */
    public function calculateRolloverDueDate(Loan $loan, ?int $customPeriodDays = null): Carbon
    {
        if (!$loan->loanType) {
            throw new \Exception('Loan type not found for loan #' . $loan->id);
        }

        // Use custom period if provided, otherwise use loan type period
        if ($customPeriodDays !== null) {
            $period = $customPeriodDays;
            $unit = 'days';
        } else {
            $period = (int) $loan->loanType->period;
            $unit = $loan->loanType->unit;
        }
        
        // Get the active cycle's due date
        $previousDueDate = $this->getPreviousDueDate($loan);
        
        $newDueDate = $previousDueDate->copy();
        
        // Add one period from loan_types to the previous due date
        switch ($unit) {
            case 'day':
            case 'days':
                $newDueDate->addDays($period);
                break;
            case 'week':
            case 'weeks':
                $newDueDate->addWeeks($period);
                break;
            case 'month':
            case 'months':
                $newDueDate->addMonths($period);
                break;
            case 'year':
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
     * FIXED FORMULA - Using previous_balance as the source of truth:
     * 
     * Cycle #1: principal = loan->amount (since previous_balance = 0)
     * Cycle #2+: principal = previous_balance (which came from previous cycle's new_balance)
     * 
     * 1. Principal = previous_balance > 0 ? previous_balance : loan->amount
     * 2. Interest = Principal × (Interest Rate / 100)
     * 3. Full Balance = Principal + Interest + Processing Fee
     * 4. Repayments BEFORE due date reduce the outstanding at due
     * 5. outstandingAtDue = Full Balance - repaymentsBeforeDue
     * 6. Penalty = outstandingAtDue × (penaltyRate / 100) × daysSubjectToPenalty
     * 7. Repayments AFTER due date reduce the penalty
     * 8. outstandingAfterDue = max(0, penalty - repaymentsAfterDue)
     * 9. Final Outstanding = outstandingAtDue + outstandingAfterDue
     */


public function calculateCycleBalance(Loan $loan, LoanCycle $cycle, Carbon $calculationDate = null, array $options = []): array
{
    if (!$loan->loanType) {
        throw new \Exception('Loan type not found for loan #' . $loan->id);
    }

    $calculationDate = $calculationDate ?? Carbon::now();
    $loanType = $loan->loanType;
    $today = Carbon::now()->startOfDay();
    
    // ============ CHECK IF THIS IS A PAYMENT PLAN CYCLE ============
    $isPaymentPlan = str_contains($cycle->notes ?? '', 'PAYMENT PLAN') ||
                     str_contains($cycle->notes ?? '', 'Penalties WAIVED') ||
                     $cycle->interest_rate === 0.0 ||
                     $loan->status === 'forbearance';
    
    // ============ FIX: USE CYCLE'S INTEREST RATE DIRECTLY ============
    // Check if the cycle has an explicit interest_rate set
    // Use strict comparison to handle 0 correctly
    $interestRate = (float) $cycle->interest_rate;
    
    // Only fallback to loan type if the cycle's interest_rate is NULL (not set)
    // NOT if it's 0 (which is a valid value for payment plans)
    if ($cycle->interest_rate === null) {
        $interestRate = (float) $loanType->interest_rate;
    }
    
    $penaltyRate = (float) $loanType->penalty_rate;
    $gracePeriodDays = (int) ($loanType->grace_period_days ?? 0);
    $processingFeeRate = (float) ($loanType->processing_fee_rate ?? 0);
    
    // Use NPL trigger threshold
    $defaultThresholdDays = (int) (
        $loan->npl_trigger_threshold
        ?: $loan->getNplThreshold()
    );
    
    // ============ PRINCIPAL = PREVIOUS_BALANCE OR LOAN AMOUNT ============
    $principal = $cycle->previous_balance > 0 ? $cycle->previous_balance : $loan->amount;
    
    // Store for reference
    $previousBalance = $cycle->previous_balance;
    $newBalanceFromDb = $cycle->new_balance;
    
    // ============ CALCULATE INTEREST ============
    $interest = $principal * ($interestRate / 100);
    
    // ============ CALCULATE PROCESSING FEE ============
    // Skip processing fees for payment plans
    $processingFee = $isPaymentPlan ? 0 : ($principal * ($processingFeeRate / 100));
    
    // ============ CALCULATE FULL BALANCE ============
    $fullBalance = $principal + $interest + $processingFee;
    
    // ============ GET REPAYMENTS FOR THIS CYCLE ============
    $repayments = $loan->repayments()
        ->where('loan_cycle_id', $cycle->id)
        ->orderBy('repayment_date', 'asc')
        ->get();
    
    $dueDate = Carbon::parse($cycle->due_date)->startOfDay();
    
    // ============ REPAYMENTS BEFORE DUE DATE ============
    $repaymentsBeforeDue = $repayments
        ->where('repayment_date', '<=', $dueDate)
        ->sum('amount');
    
    // ============ OUTSTANDING AT DUE DATE ============
    $outstandingAtDue = max(0, $fullBalance - $repaymentsBeforeDue);
    
    // ============ CALCULATE PENALTY ============
    $penalty = 0;
    $daysOverdue = 0;
    $daysSubjectToPenalty = 0;
    $isOverdue = false;
    $isDefaulted = false;
    
    // ============ SKIP PENALTIES FOR PAYMENT PLAN ============
    if (!$isPaymentPlan) {
        if ($today->gt($dueDate) && $outstandingAtDue > 0) {
            $daysOverdue = (int) $dueDate->diffInDays($today);
            $daysSubjectToPenalty = max(0, $daysOverdue - $gracePeriodDays);
            
            if ($daysSubjectToPenalty > 0 && $penaltyRate > 0) {
                $isOverdue = true;
                $penalty = $outstandingAtDue * ($penaltyRate / 100) * $daysSubjectToPenalty;
                
                if ($daysOverdue >= $defaultThresholdDays) {
                    $isDefaulted = true;
                }
            }
        }
    }
    
    // ============ REPAYMENTS AFTER DUE DATE ============
    $repaymentsAfterDue = $repayments
        ->where('repayment_date', '>', $dueDate)
        ->sum('amount');
    
    // ============ OUTSTANDING AFTER DUE ============
    $outstandingAfterDue = max(0, $penalty - $repaymentsAfterDue);
    
    // ============ FINAL OUTSTANDING ============
    $finalOutstanding = max(0, $outstandingAtDue + $outstandingAfterDue);
    
    // ============ PARTNER FEES ============
    $partnerData = $this->calculatePartnerReturns($loan, [
        'interest' => $interest,
        'penalty' => $penalty,
        'principal' => $principal,
        'outstanding_at_due' => $outstandingAtDue,
        'full_balance' => $fullBalance,
        'total_repayments' => $repayments->sum('amount'),
    ]);
    
    // Determine if loan is fully repaid
    $isFullyRepaid = $finalOutstanding <= 0;
    
    // Determine status for this cycle
    $cycleStatus = 'active';
    if ($isFullyRepaid) {
        $cycleStatus = 'completed';
    } elseif ($isDefaulted) {
        $cycleStatus = 'defaulted';
    } elseif ($isOverdue && $daysSubjectToPenalty > 0) {
        $cycleStatus = 'overdue';
    }
    
    $totalRepayments = $repayments->sum('amount');
    $lastRepayment = $repayments->last();
    $lastRepaymentDate = $lastRepayment ? Carbon::parse($lastRepayment->repayment_date) : null;
    
    // ============ PL CALCULATION ============
    $partnerPrincipalReturn = $partnerData['principal_returned'] ?? 0;
    $partnerInterestShare = $partnerData['interest_share'] ?? 0;
    $partnerPenaltyShare = $partnerData['penalty_share'] ?? 0;
    $totalPartnerFees = $partnerData['total_partner_fees'] ?? 0;
    
    $netEarnings = ($interest + $penalty) - ($partnerInterestShare + $partnerPenaltyShare);
    $principalRecovered = $totalRepayments > $interest ? $totalRepayments - $interest : 0;
    $principalLost = max(0, $principal - $principalRecovered);
    $pl = $netEarnings - $principalLost;
    
    return [
        'cycle_id' => $cycle->id,
        'cycle_number' => $cycle->cycle_number,
        'principal' => $principal,
        'previous_balance' => $previousBalance,
        'new_balance_from_db' => $newBalanceFromDb,
        'interest_rate' => $interestRate,
        'interest' => $interest,
        'processing_fee_rate' => $processingFeeRate,
        'processing_fee' => $processingFee,
        'full_balance' => $fullBalance,
        'database_new_balance' => $cycle->new_balance,
        'total_repayments' => $totalRepayments,
        'repayments_before_due' => $repaymentsBeforeDue,
        'repayments_after_due' => $repaymentsAfterDue,
        'outstanding_at_due' => $outstandingAtDue,
        'outstanding_after_due' => $outstandingAfterDue,
        'days_overdue' => $daysOverdue,
        'grace_period_days' => $gracePeriodDays,
        'days_subject_to_penalty' => $daysSubjectToPenalty,
        'penalty_rate' => $penaltyRate,
        'penalty' => $penalty,
        'final_outstanding' => $finalOutstanding,
        'is_overdue' => $isOverdue,
        'is_fully_repaid' => $isFullyRepaid,
        'is_defaulted' => $isDefaulted,
        'default_threshold_days' => $defaultThresholdDays,
        'cycle_status' => $cycleStatus,
        'last_repayment_date' => $lastRepaymentDate,
        'due_date' => $dueDate,
        'start_date' => Carbon::parse($cycle->start_date),
        'calculation_date' => $calculationDate,
        'interest_capitalized' => $cycle->interest_capitalized,
        'partner_data' => $partnerData,
        'net_earnings' => $netEarnings,
        'pl' => $pl,
        'principal_recovered' => $principalRecovered,
        'principal_lost' => $principalLost,
        'is_payment_plan' => $isPaymentPlan,
        'penalty_waived' => $isPaymentPlan,
    ];
}

    /**
     * Calculate partner returns with SEPARATE PRINCIPAL RETURN
     * 
     * Approach 2: Principal returned separately from interest/profits
     * - Principal goes back to partner through 'withdrawal' transactions
     * - Interest/profits go to partner through 'profit_distribution' transactions
     * - Both are tracked separately in partner_transactions
     */
    public function calculatePartnerReturns(Loan $loan, array $cycleData): array
    {
        $totalPartnerFees = 0;
        $partnerBreakdown = [];
        $principalReturned = 0;
        $interestShare = 0;
        $penaltyShare = 0;
        
        $interest = $cycleData['interest'] ?? 0;
        $penalty = $cycleData['penalty'] ?? 0;
        $principal = $cycleData['principal'] ?? 0;
        $totalRepayments = $cycleData['total_repayments'] ?? 0;
        
        // Check if loan is brokered
        if (!$loan->broker_status || $loan->broker_status != 1) {
            return [
                'total_partner_fees' => 0,
                'partner_breakdown' => [],
                'is_brokered' => false,
                'principal_returned' => 0,
                'interest_share' => 0,
                'penalty_share' => 0,
                'principal_remaining' => $principal,
            ];
        }
        
        // Get the borrower's broker
        $borrower = $loan->user?->borrower;
        if (!$borrower) {
            return [
                'total_partner_fees' => 0,
                'partner_breakdown' => [],
                'is_brokered' => false,
                'error' => 'Borrower not found',
                'principal_returned' => 0,
                'interest_share' => 0,
                'penalty_share' => 0,
                'principal_remaining' => $principal,
            ];
        }
        
        $broker = $borrower->broker;
        if (!$broker) {
            return [
                'total_partner_fees' => 0,
                'partner_breakdown' => [],
                'is_brokered' => true,
                'error' => 'Broker not found',
                'principal_returned' => 0,
                'interest_share' => 0,
                'penalty_share' => 0,
                'principal_remaining' => $principal,
            ];
        }
        
        // Get client type for rate determination
        $clientType = $borrower->client_type ?? 0;
        
        // Calculate broker fees from broker rates
        $brokerInterestRate = ($clientType == 0) 
            ? $broker->interest_client 
            : $broker->interest_broker;
        $brokerPenaltyRate = ($clientType == 0) 
            ? $broker->penalty_client 
            : $broker->penalty_broker;
        
        // Broker share of interest and penalty
        $brokerInterestShare = $interest * ($brokerInterestRate / 100);
        $brokerPenaltyShare = $penalty * ($brokerPenaltyRate / 100);
        $brokerTotalShare = $brokerInterestShare + $brokerPenaltyShare;
        
        // ============ SEPARATE PRINCIPAL RETURN ============
        // Calculate how much principal has been returned to the broker/partner
        // This comes from the loan repayments that exceed interest
        $principalFromRepayments = max(0, $totalRepayments - $interest);
        $principalReturned = min($principal, $principalFromRepayments);
        $principalRemaining = max(0, $principal - $principalReturned);
        
        $partnerBreakdown[] = [
            'partner_id' => $broker->id,
            'partner_name' => $broker->name ?? 'Unknown Broker',
            'type' => 'broker',
            'client_type' => $clientType == 0 ? 'Client' : 'Broker',
            'interest_rate' => $brokerInterestRate,
            'interest_share' => $brokerInterestShare,
            'penalty_rate' => $brokerPenaltyRate,
            'penalty_share' => $brokerPenaltyShare,
            'total_share' => $brokerTotalShare,
            // Separate principal tracking
            'principal_contributed' => $principal,
            'principal_returned' => $principalReturned,
            'principal_remaining' => $principalRemaining,
            'principal_return_percentage' => $principal > 0 ? ($principalReturned / $principal) * 100 : 0,
        ];
        
        $totalPartnerFees = $brokerTotalShare;
        $interestShare = $brokerInterestShare;
        $penaltyShare = $brokerPenaltyShare;
        
        // ============ CHECK FOR INVESTMENT PARTNERS ============
        // Get active investments linked to this broker
        $investments = Investment::whereHas('partnerTransactions', function($query) use ($broker) {
            $query->where('partner_id', $broker->id)
                  ->where('type', 'contribution');
        })->where('status', 'active')->get();
        
        foreach ($investments as $investment) {
            $profitShareRate = $investment->partner?->profit_share_rate ?? 0;
            
            if ($profitShareRate > 0) {
                // Partner gets a share of the interest and penalty
                $partnerInterestShare = $interest * ($profitShareRate / 100);
                $partnerPenaltyShare = $penalty * ($profitShareRate / 100);
                $partnerTotalShare = $partnerInterestShare + $partnerPenaltyShare;
                
                // Calculate principal return for investment partner
                $investmentPrincipal = $investment->initial_amount ?? 0;
                $investmentPrincipalReturned = $this->calculateInvestmentPrincipalReturned($investment, $loan);
                $investmentPrincipalRemaining = max(0, $investmentPrincipal - $investmentPrincipalReturned);
                
                $totalPartnerFees += $partnerTotalShare;
                $interestShare += $partnerInterestShare;
                $penaltyShare += $partnerPenaltyShare;
                
                $partnerBreakdown[] = [
                    'partner_id' => $investment->partner_id,
                    'partner_name' => $investment->partner?->name ?? 'Unknown Partner',
                    'type' => 'investment_partner',
                    'profit_share_rate' => $profitShareRate,
                    'investment_amount' => $investmentPrincipal,
                    'expected_return' => $investment->expected_return,
                    'interest_share' => $partnerInterestShare,
                    'penalty_share' => $partnerPenaltyShare,
                    'total_share' => $partnerTotalShare,
                    // Separate principal tracking for investment
                    'principal_contributed' => $investmentPrincipal,
                    'principal_returned' => $investmentPrincipalReturned,
                    'principal_remaining' => $investmentPrincipalRemaining,
                    'principal_return_percentage' => $investmentPrincipal > 0 ? ($investmentPrincipalReturned / $investmentPrincipal) * 100 : 0,
                ];
            }
        }
        
        return [
            'total_partner_fees' => $totalPartnerFees,
            'partner_breakdown' => $partnerBreakdown,
            'is_brokered' => true,
            'broker_id' => $broker->id,
            'broker_name' => $broker->name,
            'client_type' => $clientType,
            // Separate principal return data
            'principal_returned' => $principalReturned,
            'principal_remaining' => $principalRemaining,
            'principal_return_percentage' => $principal > 0 ? ($principalReturned / $principal) * 100 : 0,
            'interest_share' => $interestShare,
            'penalty_share' => $penaltyShare,
            'interest_share_total' => $brokerInterestShare,
            'penalty_share_total' => $brokerPenaltyShare,
        ];
    }

    /**
     * Calculate how much principal has been returned to an investment partner
     */
    public function calculateInvestmentPrincipalReturned(Investment $investment, Loan $loan): float
    {
        // Get all partner transactions for this investment that are principal returns
        $principalReturned = $investment->partnerTransactions()
            ->where('type', 'withdrawal')
            ->where('loan_id', $loan->id)
            ->sum('amount');
            
        return $principalReturned;
    }

    /**
     * Create the initial cycle for a loan
     * FIXED: previous_balance = 0, new_balance = loan amount + interest
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

        // ============ CREATE CYCLE #1 ============
        // previous_balance = 0 (no previous cycle)
        // new_balance = loan->amount + interest (this becomes the previous_balance for cycle #2)
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
            'principal_used' => $loan->amount,
            'interest' => $interest,
            'new_balance' => $loan->amount + $interest,
            'due_date' => $dueDate->format('Y-m-d')
        ]);

        return $cycle;
    }

    /**
     * Execute a loan rollover
     * This creates a new cycle and marks the previous one as completed
     * 
     * FIXED: 
     * - Start date = previous cycle's due date (not today)
     * - Previous balance = previous cycle's new_balance
     */
    public function executeRollover(Loan $loan, array $options = []): array
    {
        if (!in_array($loan->status, ['active', 'overdue', 'disbursed'])) {
            throw new \Exception('This loan is not eligible for rollover.');
        }

        if ($loan->isDefaulted()) {
            throw new \Exception('This loan is defaulted. Please resolve recovery case first.');
        }

        if (!$loan->loanType) {
            throw new \Exception('Loan type not found for loan #' . $loan->id);
        }

        $notes = $options['notes'] ?? null;
        $loanType = $loan->loanType;
        
        // ============ CUSTOM OVERRIDES ============
        $customInterestRate = $options['interest_rate'] ?? null;
        $customPeriodDays = $options['period_days'] ?? null;
        $customDueDate = $options['due_date'] ?? null;
        $customStartDate = $options['start_date'] ?? null;
        $waivePenalty = $options['waive_penalty'] ?? false;
        
        // Get the active cycle
        $activeCycle = $this->getActiveCycle($loan);

        if (!$activeCycle) {
            throw new \Exception('No active cycle found for loan #' . $loan->id);
        }

        // ============ CALCULATE THE FINAL OUTSTANDING ============
        $cycleCalculation = $this->calculateCycleBalance($loan, $activeCycle);
        
        // ============ FIX: NEW PRINCIPAL = PREVIOUS CYCLE'S NEW_BALANCE ============
        $newPrincipal = $activeCycle->new_balance;
        
        // If the loan is fully repaid, mark it as such
        if ($newPrincipal <= 0) {
            $activeCycle->update(['status' => 'completed']);
            $loan->status = 'repaid';
            $loan->save();
            $this->returnPrincipalToPartners($loan);
            
            return [
                'success' => true,
                'message' => 'Loan is fully repaid. Principal returned to partners.',
                'data' => ['is_repaid' => true]
            ];
        }

        // Get the next cycle number
        $newCycleNumber = $activeCycle->cycle_number + 1;
        
        // ============ USE CUSTOM INTEREST RATE OR DEFAULT ============
        $interestRate = $customInterestRate ?? (float) $loanType->interest_rate;
        
        // ============ CALCULATE INTEREST ON NEW PRINCIPAL ============
        $interest = $newPrincipal * ($interestRate / 100);
        $newBalance = $newPrincipal + $interest;
        
        // ============ FIX: START DATE = PREVIOUS CYCLE'S DUE DATE ============
        $newStartDate = $customStartDate 
            ? Carbon::parse($customStartDate) 
            : Carbon::parse($activeCycle->due_date);
        
        // ============ CALCULATE NEW DUE DATE ============
        if ($customDueDate) {
            $newDueDate = Carbon::parse($customDueDate);
        } else {
            // Add the period to the start date (which is the previous due date)
            $period = $customPeriodDays ?? (int) $loanType->period;
            $unit = $loanType->unit;
            
            $newDueDate = $newStartDate->copy();
            switch ($unit) {
                case 'day':
                case 'days':
                    $newDueDate->addDays($period);
                    break;
                case 'week':
                case 'weeks':
                    $newDueDate->addWeeks($period);
                    break;
                case 'month':
                case 'months':
                    $newDueDate->addMonths($period);
                    break;
                case 'year':
                case 'years':
                    $newDueDate->addYears($period);
                    break;
                default:
                    $newDueDate->addDays($period);
            }
        }
        
        // ============ MARK ACTIVE CYCLE AS COMPLETED ============
        $activeCycle->update(['status' => 'completed']);
        
        // ============ BUILD NOTES ============
        $cycleNotes = $notes ?? 'Loan rollover - Cycle ' . $newCycleNumber;
        if ($customInterestRate) {
            $cycleNotes .= ' (Custom rate: ' . $customInterestRate . '%)';
        }
        if ($customPeriodDays) {
            $cycleNotes .= ' (Custom period: ' . $customPeriodDays . ' days)';
        }
        if ($waivePenalty) {
            $cycleNotes .= ' - PENALTIES WAIVED (Payment Plan)';
        }
        
        // ============ CREATE NEW CYCLE ============
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
            'notes' => $cycleNotes,
        ]);

        // ============ UPDATE LOAN ============
        $loan->amount = $newBalance;
        $loan->cycle = $newCycleNumber;
        $loan->due_date = $newDueDate;
        $loan->calculated_due_date = $newDueDate;
        $loan->status = $waivePenalty ? Loan::STATUS_FORBEARANCE : Loan::STATUS_DISBURSED;
        $loan->capitalized_interest = ($loan->capitalized_interest ?? 0) + $interest;
        $loan->days_overdue = 0;
        $loan->is_non_performing = false;
        $loan->default_triggered = false;
        $loan->forbearance_until = $waivePenalty ? $newDueDate : null;
        $loan->save();

        Log::info('Rollover executed for loan #' . $loan->id, [
            'loan_type_id' => $loan->loan_type_id,
            'interest_rate_used' => $interestRate,
            'new_cycle' => $newCycleNumber,
            'previous_balance' => $newPrincipal,
            'interest_calculated' => $interest,
            'new_balance' => $newBalance,
            'start_date' => $newStartDate->format('Y-m-d'),
            'new_due_date' => $newDueDate->format('Y-m-d'),
            'waive_penalty' => $waivePenalty,
        ]);

        // ============ FIX: Include period and period_unit in return ============
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
                'interest_rate_used' => $interestRate,
                'previous_balance' => $newPrincipal,
                'waive_penalty' => $waivePenalty,
                'cycle_calculation' => $cycleCalculation,
                // ============ ADD THESE TWO LINES ============
                'period' => (int) $loanType->period,
                'period_unit' => $loanType->unit,
                // ============ END ADD ============
            ]
        ];
    }

    /**
     * Return principal to partners when loan is fully repaid
     */
    public function returnPrincipalToPartners(Loan $loan): void
    {
        if (!$loan->broker_status || $loan->broker_status != 1) {
            return;
        }

        $borrower = $loan->user?->borrower;
        if (!$borrower) {
            return;
        }

        $broker = $borrower->broker;
        if (!$broker) {
            return;
        }

        // Calculate principal to return
        $totalRepayments = $loan->repayments()->sum('amount');
        $interest = $loan->capitalized_interest ?? 0;
        $principalReturned = max(0, $totalRepayments - $interest);
        $principalToReturn = min($loan->original_amount ?? $loan->amount, $principalReturned);

        if ($principalToReturn > 0) {
            // Return principal to broker
            $broker->withdraw(
                $principalToReturn,
                'PRINCIPAL-RETURN-' . $loan->id,
                "Principal return for loan #{$loan->id}"
            );

            Log::info('Principal returned to broker', [
                'loan_id' => $loan->id,
                'broker_id' => $broker->id,
                'principal_returned' => $principalToReturn,
            ]);
        }
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

        // Calculate the cycle balance using new formula
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
            
            // NEW FORMULA VALUES - Using previous_balance as principal
            'principal' => $cycleCalculation['principal'],
            'outstanding_at_due' => $cycleCalculation['outstanding_at_due'],
            'repayments_before_due' => $cycleCalculation['repayments_before_due'],
            'repayments_after_due' => $cycleCalculation['repayments_after_due'],
            'outstanding_after_due' => $cycleCalculation['outstanding_after_due'],
            'days_overdue' => $cycleCalculation['days_overdue'],
            'days_subject_to_penalty' => $cycleCalculation['days_subject_to_penalty'],
            'penalty' => $cycleCalculation['penalty'],
            'final_outstanding' => $cycleCalculation['final_outstanding'],
            'is_overdue' => $cycleCalculation['is_overdue'],
            'is_defaulted' => $cycleCalculation['is_defaulted'],
            'default_threshold_days' => $cycleCalculation['default_threshold_days'],
            
            // Partner data with separate principal return
            'partner_data' => $cycleCalculation['partner_data'],
            'net_earnings' => $cycleCalculation['net_earnings'],
            'pl' => $cycleCalculation['pl'],
            'principal_returned' => $cycleCalculation['principal_recovered'] ?? 0,
            'principal_remaining' => $cycleCalculation['principal_lost'] ?? 0,
            
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
     * Uses the active cycle's data for accurate display if available
     */

    public function calculateLoanMetrics(Loan $loan): array
    {
        if (!$loan->loanType || !$loan->borrow_date) {
            throw new \InvalidArgumentException('Loan type or borrow date missing');
        }

        $loanType = $loan->loanType;
        $loanAmount = $loan->amount;
        $basePenaltyRate = (float) $loanType->penalty_rate;
        $gracePeriodDays = (int) ($loanType->grace_period_days ?? 0);
        $borrowDate = Carbon::parse($loan->borrow_date);
        $today = Carbon::now()->startOfDay();
        
        // ============ GET THE ACTIVE CYCLE ============
        $activeCycle = $this->getActiveCycle($loan);
        $dueDate = null;
        $cycleCalculation = null;
        $interest = 0;
        $principalPlusInterest = 0;
        $totalRepayments = 0;
        $repaymentsBeforeDue = 0;
        $repaymentsAfterDue = 0;
        $outstandingAtDue = 0;
        $outstandingAfterDue = 0;
        $outstandingBalance = 0;
        $totalDue = 0;
        $penaltyAmount = 0;
        $daysLate = 0;
        $daysOverdue = 0;
        $isDefaulted = false;
        $partnerData = [];
        $netEarnings = 0;
        $pl = 0;
        $principalReturned = 0;
        $principalRemaining = 0;
        $cyclePrincipal = 0;
        $interestRate = 0;
        $period = 0;
        $periodUnit = '';
        
        // ============ GET DUE DATE ============
        if ($activeCycle && $activeCycle->due_date) {
            $dueDate = Carbon::parse($activeCycle->due_date)->startOfDay();
        } else {
            $dueDate = $this->calculateDueDate($loan);
            if (!$dueDate) {
                $dueDate = $borrowDate->copy()->addDays(30);
            }
            $dueDate = $dueDate->startOfDay();
        }
        
        // ============ GET THE CYCLE PRINCIPAL ============
        if ($activeCycle) {
            $cyclePrincipal = $activeCycle->previous_balance > 0 
                ? $activeCycle->previous_balance 
                : $loanAmount;
        } else {
            $cyclePrincipal = $loanAmount;
        }
        
        // ============ FIX: USE CYCLE'S INTEREST RATE ============
        // Check if loan is in forbearance or payment plan
        $isForbearance = $loan->status === 'forbearance' || 
                        $loan->isInForbearance() ||
                        ($activeCycle && str_contains($activeCycle->notes ?? '', 'PAYMENT PLAN'));
        
        if ($activeCycle) {
            // Use the cycle's interest rate directly
            $interestRate = (float) $activeCycle->interest_rate;
            
            // If cycle interest rate is 0 or null, use loan type rate (but only if not in forbearance)
            if ($interestRate === 0.0 && !$isForbearance) {
                $interestRate = (float) $loanType->interest_rate;
            } elseif ($interestRate === 0.0 && $isForbearance) {
                // Keep it as 0 for forbearance
                $interestRate = 0;
            } elseif ($interestRate === null) {
                $interestRate = (float) $loanType->interest_rate;
            }
            
            // Get period from cycle or loan type
            $period = (int) ($activeCycle->period ?? $loanType->period);
            $periodUnit = $loanType->unit;
        } else {
            $interestRate = (float) $loanType->interest_rate;
            $period = (int) $loanType->period;
            $periodUnit = $loanType->unit;
        }
        
        // ============ CALCULATE INTEREST ============
        // If in forbearance/payment plan AND interest rate is 0, interest is 0
        if ($isForbearance && $interestRate == 0) {
            $interest = 0;
        } else {
            $interest = $cyclePrincipal * ($interestRate / 100);
        }
        
        $principalPlusInterest = $cyclePrincipal + $interest;
        
        // ============ GET REPAYMENTS ============
        if ($activeCycle) {
            $cycleRepayments = $loan->repayments()
                ->where('loan_cycle_id', $activeCycle->id)
                ->get();
            
            $totalRepayments = $cycleRepayments->sum('amount');
            
            $repaymentsBeforeDue = $cycleRepayments
                ->where('repayment_date', '<=', $dueDate)
                ->sum('amount');
            
            $repaymentsAfterDue = $cycleRepayments
                ->where('repayment_date', '>', $dueDate)
                ->sum('amount');
            
            $outstandingAtDue = max(0, $principalPlusInterest - $repaymentsBeforeDue);
        } else {
            $totalRepayments = $loan->repayments()->sum('amount');
            $repaymentsBeforeDue = $loan->repayments()
                ->whereDate('repayment_date', '<=', $dueDate)
                ->sum('amount');
            $repaymentsAfterDue = $loan->repayments()
                ->whereDate('repayment_date', '>', $dueDate)
                ->sum('amount');
            $outstandingAtDue = max(0, $principalPlusInterest - $repaymentsBeforeDue);
        }
        
        // ============ CALCULATE DAYS OVERDUE ============
        $daysOverdue = 0;
        if ($today->gt($dueDate)) {
            $daysOverdue = (int) $dueDate->diffInDays($today);
        }
        
        // ============ CALCULATE PENALTIES ============
        $defaultThresholdDays = (int) (
            $loan->npl_trigger_threshold
            ?: $loan->getNplThreshold()
        );
        
        $penaltyAmount = 0;
        $daysLate = 0;
        $isDefaulted = false;
        
        // ============ SKIP PENALTIES FOR FORBEARANCE ============
        if (!$isForbearance) {
            if ($daysOverdue > 0 && $outstandingAtDue > 0 && $basePenaltyRate > 0) {
                $daysSubjectToPenalty = max(0, $daysOverdue - $gracePeriodDays);
                $daysLate = $daysSubjectToPenalty;
                
                if ($daysSubjectToPenalty > 0) {
                    $dailyPenaltyRate = $basePenaltyRate / 100;
                    $penaltyAmount = $outstandingAtDue * $dailyPenaltyRate * $daysSubjectToPenalty;
                    
                    if ($daysOverdue >= $defaultThresholdDays) {
                        $isDefaulted = true;
                    }
                }
            }
        }
        
        // ============ OUTSTANDING AFTER DUE ============
        $outstandingAfterDue = max(0, $penaltyAmount - $repaymentsAfterDue);
        
        // ============ DEFAULTED LOAN HANDLING ============
        if ($loan->status === 'defaulted' || $isDefaulted) {
            if ($penaltyAmount == 0 && $outstandingAtDue > 0 && $basePenaltyRate > 0 && !$isForbearance) {
                $daysSubjectToPenalty = max(0, $daysOverdue - $gracePeriodDays);
                $dailyPenaltyRate = $basePenaltyRate / 100;
                $penaltyAmount = $outstandingAtDue * $dailyPenaltyRate * $daysSubjectToPenalty;
                $daysLate = $daysSubjectToPenalty;
                $outstandingAfterDue = $penaltyAmount;
            }
        }
        
        // ============ CALCULATE FINAL AMOUNTS ============
        $finalOutstanding = max(0, $outstandingAtDue + $outstandingAfterDue);
        $totalDue = $principalPlusInterest + $penaltyAmount;
        $outstandingBalance = max(0, $totalDue - $totalRepayments);
        
        // ============ CALCULATE PARTNER RETURNS ============
        $partnerData = $this->calculatePartnerReturns($loan, [
            'interest' => $interest,
            'penalty' => $penaltyAmount,
            'principal' => $cyclePrincipal,
            'outstanding_at_due' => $outstandingAtDue,
            'total_repayments' => $totalRepayments,
        ]);
        
        $totalPartnerFees = $partnerData['total_partner_fees'] ?? 0;
        $principalReturned = $partnerData['principal_returned'] ?? 0;
        $principalRemaining = $partnerData['principal_remaining'] ?? 0;
        $partnerInterestShare = $partnerData['interest_share'] ?? 0;
        $partnerPenaltyShare = $partnerData['penalty_share'] ?? 0;
        
        // ============ NET EARNINGS & PL ============
        $netEarnings = ($interest + $penaltyAmount) - ($partnerInterestShare + $partnerPenaltyShare);
        $principalRecovered = $totalRepayments > $interest ? $totalRepayments - $interest : 0;
        $principalLost = max(0, $cyclePrincipal - $principalRecovered);
        $pl = $netEarnings - $principalLost;

        // Get last repayment date
        $lastRepayment = $loan->repayments()->orderBy('repayment_date', 'desc')->first();
        $lastRepaymentDate = $lastRepayment ? Carbon::parse($lastRepayment->repayment_date) : null;

        // Calculate broker fees for backward compatibility
        $brokerFees = 0;
        $brokerRate = 0;
        $brokerPenaltyFees = 0;
        $penaltyRate = 0;
        $totalBrokerFees = 0;
        $isBrokered = false;
        
        if ($partnerData['is_brokered'] ?? false) {
            $isBrokered = true;
            foreach ($partnerData['partner_breakdown'] as $partner) {
                if ($partner['type'] === 'broker') {
                    $brokerFees = $partner['interest_share'] ?? 0;
                    $brokerRate = $partner['interest_rate'] ?? 0;
                    $brokerPenaltyFees = $partner['penalty_share'] ?? 0;
                    $totalBrokerFees = $partner['total_share'] ?? 0;
                    break;
                }
            }
        }

        // ============ BUILD CYCLE CALCULATION FOR DISPLAY ============
        $cycleCalculation = [
            'cycle_number' => $activeCycle->cycle_number ?? 1,
            'principal' => $cyclePrincipal,
            'previous_balance' => $activeCycle->previous_balance ?? 0,
            'interest' => $interest,
            'interest_rate' => $interestRate,
            'full_balance' => $principalPlusInterest,
            'repayments_before_due' => $repaymentsBeforeDue,
            'repayments_after_due' => $repaymentsAfterDue,
            'outstanding_at_due' => $outstandingAtDue,
            'outstanding_after_due' => $outstandingAfterDue,
            'penalty' => $penaltyAmount,
            'penalty_rate' => $basePenaltyRate,
            'days_overdue' => $daysOverdue,
            'days_subject_to_penalty' => $daysLate,
            'final_outstanding' => $finalOutstanding,
            'total_repayments' => $totalRepayments,
            'outstanding_after_repayments' => $finalOutstanding,
            'partner_data' => $partnerData,
            'net_earnings' => $netEarnings,
            'pl' => $pl,
            'principal_recovered' => $principalRecovered,
            'principal_lost' => $principalLost,
            'is_payment_plan' => $isForbearance,
            'penalty_waived' => $isForbearance,
        ];

        return [
            'loan_type_id' => $loan->loan_type_id,
            'loan_type_name' => $loanType->name,
            'period' => $period,
            'period_unit' => $periodUnit,
            'period_display' => $this->getPeriodDisplay($loan),
            'principal' => $cyclePrincipal,
            'interest' => $interest,
            'interest_rate' => $interestRate,
            'principal_plus_interest' => $principalPlusInterest,
            'borrow_date' => $borrowDate,
            'due_date' => $dueDate,
            'last_repayment_date' => $lastRepaymentDate,
            'days_late' => $daysLate,
            'days_overdue' => $daysOverdue,
            'grace_period_days' => $gracePeriodDays,
            'base_penalty_rate' => $basePenaltyRate,
            'penalty_rate' => $penaltyRate,
            'penalty_amount' => $penaltyAmount,
            'repayments_before_due' => $repaymentsBeforeDue,
            'repayments_after_due' => $repaymentsAfterDue,
            'outstanding_at_due' => $outstandingAtDue,
            'outstanding_after_due' => $outstandingAfterDue,
            'final_outstanding' => $finalOutstanding,
            'is_brokered' => $isBrokered,
            'broker_fees' => $brokerFees,
            'brokerRate' => $brokerRate,
            'broker_penalty_fees' => $brokerPenaltyFees,
            'total_broker_fees' => $totalBrokerFees,
            'client_type' => $loan->user->borrower->client_type ?? 0,
            'partner_data' => $partnerData,
            'net_earnings' => $netEarnings,
            'pl' => $pl,
            'principal_returned' => $principalReturned,
            'principal_remaining' => $principalRemaining,
            'principal_recovered' => $principalRecovered,
            'principal_lost' => $principalLost,
            'partner_interest_share' => $partnerInterestShare,
            'partner_penalty_share' => $partnerPenaltyShare,
            'total_repayments' => $totalRepayments,
            'outstanding_balance' => $outstandingBalance,
            'total_due' => $totalDue,
            'is_overdue' => $daysOverdue > 0,
            'is_repaid' => $loan->status === 'repaid',
            'is_defaulted' => ($loan->status === 'defaulted' || $isDefaulted),
            'default_threshold_days' => $defaultThresholdDays,
            'active_cycle' => $activeCycle ? [
                'id' => $activeCycle->id,
                'number' => $activeCycle->cycle_number,
                'start_date' => $activeCycle->start_date->format('M d, Y'),
                'due_date' => $activeCycle->due_date->format('M d, Y'),
                'balance' => $activeCycle->new_balance,
                'previous_balance' => $activeCycle->previous_balance,
                'interest_capitalized' => $activeCycle->interest_capitalized,
            ] : null,
            'cycle_calculation' => $cycleCalculation,
            'penalty_breakdown' => [
                'days_overdue' => $daysOverdue,
                'grace_period_days' => $gracePeriodDays,
                'days_subject_to_penalty' => $daysLate,
                'daily_rate' => $basePenaltyRate . '%',
                'outstanding_at_due' => $outstandingAtDue,
                'penalty_amount' => $penaltyAmount,
                'repayments_after_due' => $repaymentsAfterDue,
                'outstanding_after_due' => $outstandingAfterDue,
                'default_threshold' => $defaultThresholdDays,
                'is_defaulted' => ($loan->status === 'defaulted' || $isDefaulted),
            ],
        ];
    }

    /**
     * Distribute partner returns when a repayment is made
     * This handles the separate principal return logic
     */
    public function distributeRepaymentToPartners(Loan $loan, float $repaymentAmount, float $interestPortion = null): array
    {
        if (!$loan->broker_status || $loan->broker_status != 1) {
            return [
                'distributed' => false,
                'message' => 'Loan is not brokered',
            ];
        }

        $borrower = $loan->user?->borrower;
        if (!$borrower) {
            return [
                'distributed' => false,
                'message' => 'Borrower not found',
            ];
        }

        $broker = $borrower->broker;
        if (!$broker) {
            return [
                'distributed' => false,
                'message' => 'Broker not found',
            ];
        }

        // Calculate interest portion of repayment
        if ($interestPortion === null) {
            // Calculate based on current cycle
            $activeCycle = $this->getActiveCycle($loan);
            if ($activeCycle) {
                $cycleCalc = $this->calculateCycleBalance($loan, $activeCycle);
                $interestPortion = $cycleCalc['interest'] ?? 0;
            } else {
                $interestPortion = $repaymentAmount * 0.1; // Fallback
            }
        }

        // Determine principal portion
        $principalPortion = max(0, $repaymentAmount - $interestPortion);

        // Get partner share rates
        $clientType = $borrower->client_type ?? 0;
        $brokerInterestRate = ($clientType == 0) 
            ? $broker->interest_client 
            : $broker->interest_broker;
        
        // Calculate partner's share of interest
        $partnerInterestShare = $interestPortion * ($brokerInterestRate / 100);

        // Create partner transaction for interest share
        $interestTransaction = $broker->distributeProfit(
            $partnerInterestShare,
            "Interest share for loan #{$loan->id} repayment"
        );

        // Return principal portion to partner
        $principalTransaction = null;
        if ($principalPortion > 0) {
            $principalTransaction = $broker->withdraw(
                $principalPortion,
                'PRINCIPAL-RETURN-' . $loan->id . '-' . time(),
                "Principal return for loan #{$loan->id} repayment"
            );
        }

        Log::info('Distributed repayment to partner', [
            'loan_id' => $loan->id,
            'broker_id' => $broker->id,
            'repayment_amount' => $repaymentAmount,
            'interest_portion' => $interestPortion,
            'principal_portion' => $principalPortion,
            'partner_interest_share' => $partnerInterestShare,
        ]);

        return [
            'distributed' => true,
            'broker_id' => $broker->id,
            'broker_name' => $broker->name,
            'repayment_amount' => $repaymentAmount,
            'interest_portion' => $interestPortion,
            'principal_portion' => $principalPortion,
            'partner_interest_share' => $partnerInterestShare,
            'interest_transaction_id' => $interestTransaction->id,
            'principal_transaction_id' => $principalTransaction?->id,
            'client_type' => $clientType,
        ];
    }
}