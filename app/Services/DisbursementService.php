<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Disbursement;
use App\Models\LoanCycle;
use Illuminate\Support\Facades\DB;

class DisbursementService
{
    protected $loanCalculator;

    public function __construct(LoanCalculator $loanCalculator)
    {
        $this->loanCalculator = $loanCalculator;
    }

    /**
     * Create a disbursement with processing fee
     */
    public function createDisbursement(Loan $loan, array $data, ?LoanCycle $cycle = null): Disbursement
    {
        return DB::transaction(function () use ($loan, $data, $cycle) {
            $amount = $data['amount'];
            
            // Calculate processing fee (use loan's rate or fallback to 0)
            $processingFeeRate = $loan->processing_fee_rate ?? 0;
            $processingFee = ($processingFeeRate / 100) * $amount;
            $netAmount = $amount - $processingFee;

            // Get current active cycle or create one
            if (!$cycle) {
                // LoanCalculator::createInitialCycle (this class's old copy called a method that doesn't exist)
                $cycle = $loan->getCurrentCycle() ?? $this->loanCalculator->createInitialCycle($loan);
            }

            // Create disbursement
            $disbursement = Disbursement::create([
                'loan_id' => $loan->id,
                'loan_cycle_id' => $cycle->id,
                'amount' => $amount,
                'processing_fee' => $processingFee,
                'net_amount' => $netAmount,
                'transaction' => $data['transaction'] ?? 'DISBURSEMENT',
                'kind' => $data['kind'] ?? null,
                'mode' => $data['mode'] ?? 'bank_transfer',
                'disburse_date' => $data['disburse_date'] ?? now(),
                'payment_date' => $data['payment_date'] ?? now(),
                'partner_transaction_id' => $data['partner_transaction_id'] ?? null,
                'funding_source' => $data['funding_source'] ?? null,
                'investment_id' => $data['investment_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Update loan total processing fees
            $loan->total_processing_fees = ($loan->total_processing_fees ?? 0) + $processingFee;
            $loan->save();

            // v2 (decision 12): the first real cash sent starts cycle 1, so the due date runs from that day
            if (LoanCalculator::v2() && CustomerWallets::kindOf($disbursement) === 'cash') {
                \App\Services\Loans\CycleOne::startOnDisbursement($loan, $disbursement);
            }

            return $disbursement;
        });
    }

}