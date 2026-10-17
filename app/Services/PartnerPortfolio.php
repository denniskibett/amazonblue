<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\PartnerTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * What a partner sees in their account: wallet balances, what they have earned and how,
 * money committed to loans, and their statement. Read-only; staff post the entries.
 */
class PartnerPortfolio
{
    // wallet slug => label (bavix wallets on the partner)
    public const WALLETS = [
        'principal' => 'Capital',
        'interest' => 'Interest',
        'broker_fee' => 'Broker fees',
    ];

    // partner_transactions.type => label
    public const TYPES = [
        'contribution' => 'Capital added',
        'withdrawal' => 'Withdrawal',
        'repayment' => 'Capital returned',
        'profit_distribution' => 'Profit share',
        'broker_fee_earned' => 'Broker fee',
        'penalty_share' => 'Penalty share',
        'interest' => 'Interest',
    ];

    public function summary(Partner $partner): array
    {
        $wallets = [];
        foreach (self::WALLETS as $slug => $label) {
            $wallet = $partner->getWallet($slug);
            $wallets[$slug] = ['label' => $label, 'balance' => $wallet ? (float) $wallet->balanceFloat : 0.0];
        }

        $byType = $partner->ledgerEntries()
            ->selectRaw('type, count(*) as entries, sum(amount) as total')
            ->groupBy('type')->get()->keyBy('type')->toBase();

        $earningTypes = ['profit_distribution', 'broker_fee_earned', 'penalty_share', 'interest'];
        $earned = (float) $byType->only($earningTypes)->sum('total');

        $thisMonth = (float) $partner->ledgerEntries()
            ->whereIn('type', $earningTypes)
            ->where('transaction_date', '>=', now()->startOfMonth())
            ->sum('amount');

        return [
            'partner' => $partner,
            'wallets' => $wallets,
            'available' => array_sum(array_column($wallets, 'balance')),
            'earned' => $earned,
            'earned_this_month' => $thisMonth,
            'breakdown' => collect($earningTypes)
                ->map(fn ($t) => ['type' => $t, 'label' => self::TYPES[$t], 'total' => (float) ($byType[$t]->total ?? 0), 'entries' => (int) ($byType[$t]->entries ?? 0)])
                ->filter(fn ($row) => $row['entries'] > 0)->values(),
            'committed' => (float) $partner->total_committed,
            'disbursed' => (float) $partner->total_disbursed,
            'returned' => (float) $partner->total_funding_returned,
            'recent' => $partner->ledgerEntries()->latest('transaction_date')->latest('id')->limit(6)->get(),
        ];
    }

    public function statement(Partner $partner, ?string $wallet = null, int $perPage = 25): LengthAwarePaginator
    {
        return $partner->ledgerEntries()
            ->when($wallet && array_key_exists($wallet, self::WALLETS), fn ($q) => $q->where('wallet_slug', $wallet))
            ->latest('transaction_date')->latest('id')
            ->paginate($perPage)->withQueryString();
    }

    public static function label(PartnerTransaction $entry): string
    {
        return self::TYPES[$entry->type] ?? ucfirst(str_replace('_', ' ', $entry->type));
    }
}
