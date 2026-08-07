<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Repayment;
use App\Models\LoanCycle;
use Illuminate\Support\Facades\DB;

class RepaymentService
{
    /**
     * Create a repayment with processing fee
     */
    public function createRepayment(Loan $loan, array $data, ?LoanCycle $cycle = null): Repayment
    {
        return DB::transaction(function () use ($loan, $data, $cycle) {
            $amount = $data['amount'];
            
            // Calculate processing fee (use loan's rate or fallback to 0)
            $processingFeeRate = $loan->processing_fee_rate ?? 0;
            $processingFee = ($processingFeeRate / 100) * $amount;
            $netAmount = $amount - $processingFee;

            // Get current active cycle
            if (!$cycle) {
                $cycle = $loan->getCurrentCycle();
            }

            // If still no cycle, use the latest cycle
            if (!$cycle) {
                $cycle = $loan->cycles()->latest()->first();
            }

            // Create repayment
            $repayment = Repayment::create([
                'loan_id' => $loan->id,
                'loan_cycle_id' => $cycle ? $cycle->id : null,
                'amount' => $amount,
                'processing_fee' => $processingFee,
                'net_amount' => $netAmount,
                'transaction' => $data['transaction'] ?? 'REPAYMENT',
                'repayment_date' => $data['repayment_date'] ?? now(),
                'mode' => $data['mode'] ?? 'bank_transfer',
                'partner_transaction_id' => $data['partner_transaction_id'] ?? null,
                'investment_id' => $data['investment_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Update loan total processing fees
            $loan->total_processing_fees = ($loan->total_processing_fees ?? 0) + $processingFee;
            $loan->save();

            // Check if loan is fully repaid
            $this->checkLoanRepaymentStatus($loan);

            return $repayment;
        });
    }

    /**
     * Check if loan is fully repaid and update status
     */
    protected function checkLoanRepaymentStatus(Loan $loan): void
    {
        $totalDisbursed = $loan->disbursements()->sum('amount');
        $totalRepaid = $loan->repayments()->sum('amount');
        $outstandingBalance = $totalDisbursed - $totalRepaid;

        if ($outstandingBalance <= 0 && $loan->status !== Loan::STATUS_REPAID) {
            $loan->status = Loan::STATUS_REPAID;
            $loan->save();

            // Complete the current cycle
            $currentCycle = $loan->getCurrentCycle();
            if ($currentCycle) {
                $currentCycle->update(['status' => 'completed']);
            }
        }
    }
}