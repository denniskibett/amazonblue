<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\PartnerTransaction;
use Illuminate\Support\Facades\DB;

class PartnerLedger
{
    /**
     * Post one economic event to a partner's wallet AND the journal, atomically.
     *
     * @param  string  $walletSlug  principal|interest|broker_fee|investment_tracker
     * @param  string  $type        one of the allowed journal types
     * @param  float   $amount      signed from partner perspective (+credit, -debit), 4dp
     */
    public function post(
        Partner $partner,
        string $walletSlug,
        string $type,
        float $amount,
        ?int $loanId = null,
        ?int $repaymentId = null,
        ?int $investmentId = null,
        ?int $investmentFundingId = null,
        ?string $reference = null,
        ?string $notes = null,
        ?string $transactionDate = null
    ): PartnerTransaction {
        $reference = $reference ?: 'TX-' . $partner->id . '-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4));

        return DB::transaction(function () use (
            $partner, $walletSlug, $type, $amount, $loanId, $repaymentId,
            $investmentId, $investmentFundingId, $reference, $notes, $transactionDate
        ) {
            $wallet = $partner->getWallet($walletSlug)
                ?? $partner->createWallet([
                    'name' => ucfirst(str_replace('_', ' ', $walletSlug)),
                    'slug' => $walletSlug,
                ]);

            $minor = (int) round($amount * 100);

            if ($minor >= 0) {
                $wallet->deposit($minor, ['reference' => $reference, 'description' => $notes ?? $type]);
            } else {
                $wallet->withdraw(abs($minor), ['reference' => $reference, 'description' => $notes ?? $type]);
            }

            return PartnerTransaction::create([
                'partner_id'            => $partner->id,
                'wallet_slug'           => $walletSlug,
                'type'                  => $type,
                'amount'                => $amount,
                'balance_after'         => (float) $wallet->refresh()->balance / 100,
                'loan_id'               => $loanId,
                'repayment_id'          => $repaymentId,
                'investment_id'         => $investmentId,
                'investment_funding_id' => $investmentFundingId,
                'reference'             => $reference,
                'notes'                 => $notes,
                'transaction_date'      => $transactionDate ?? now()->toDateString(),
            ]);
        });
    }
}