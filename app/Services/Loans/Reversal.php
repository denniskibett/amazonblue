<?php

namespace App\Services\Loans;

use App\Models\Loan;
use App\Models\Repayment;
use App\Services\CustomerWallets;
use App\Services\LoanCalculator;

/**
 * A reversal entry (Stage 4 Phase 1, moved here in Phase 5 so a checker can approve it): a new repayment for
 * minus the amount, dated like the original, pointing at it. The original stays.
 */
class Reversal
{
    public function reverse(Repayment $original, string $reason): Repayment
    {
        if ($original->reversal_of_id !== null || Repayment::where('reversal_of_id', $original->id)->exists()) {
            throw new \DomainException('Repayment #' . $original->id . ' is already part of a reversal.');
        }
        $reversal = Repayment::create([
            'loan_id' => $original->loan_id,
            'loan_cycle_id' => $original->loan_cycle_id,
            'amount' => -1 * (float) $original->amount,
            'processing_fee' => -1 * (float) $original->processing_fee,
            'net_amount' => $original->getRawOriginal('net_amount') === null ? null : -1 * (float) $original->getRawOriginal('net_amount'),
            'transaction' => mb_substr('REVERSAL #' . $original->id . ' ' . $original->transaction, 0, 255),
            'kind' => CustomerWallets::kindOf($original),
            'mode' => $original->mode,
            'repayment_date' => $original->repayment_date,
            'investment_id' => $original->investment_id,
            'reversal_of_id' => $original->id,
            'notes' => $reason,
        ]);
        if (LoanCalculator::v2() && ($loan = Loan::find($original->loan_id))) {
            LoanStatus::apply($loan);
        }

        return $reversal;
    }
}
