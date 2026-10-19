<?php

namespace App\Services;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Partner self-service (/account/partner/...): read-only views over data staff record.
 * Figures come from PartnerPortfolio (wallets + journal), investment_fundings and the loans linked
 * to the partner. Nothing here posts money or changes a calculation.
 *
 * Loans a partner funded = loans whose disbursement drew on an investment the partner funds
 * (investment_fundings → investments → disbursements.investment_id), plus, for brokers, loans.partner_id.
 */
class PartnerAccount
{
    public const EARNING_TYPES = ['profit_distribution', 'broker_fee_earned', 'penalty_share', 'interest'];

    public function partnerFor(User $user): ?Partner
    {
        return Partner::where('user_id', $user->id)->first();
    }

    /** Everything the partner home needs on top of PartnerPortfolio::summary. */
    public function home(Partner $partner): array
    {
        return [
            'investments' => $this->fundings($partner)->count(),
            'loans' => $this->fundedLoans($partner)->count(),
            'open_loans' => $this->fundedLoans($partner)->whereIn('status', ['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery'])->count(),
            'interest_earned' => (float) $partner->ledgerEntries()->whereIn('type', ['profit_distribution', 'interest', 'penalty_share'])->sum('amount'),
            'broker_fees' => (float) $partner->ledgerEntries()->where('type', 'broker_fee_earned')->sum('amount'),
            'principal_in' => (float) $partner->ledgerEntries()->where('type', 'contribution')->sum('amount'),
        ];
    }

    /** The partner's funding rows (one per investment line they fund), newest first. */
    public function fundings(Partner $partner): HasMany
    {
        return $partner->fundings()->with('investment')->latest('id');
    }

    /** Investment ids the partner funds (funding rows or contribution journal rows linked to an investment). */
    public function investmentIds(Partner $partner): Collection
    {
        return $partner->fundings()->pluck('investment_id')
            ->merge($partner->ledgerEntries()->whereNotNull('investment_id')->pluck('investment_id'))
            ->filter()->unique()->values();
    }

    public function investment(Partner $partner, int $investmentId): ?Investment
    {
        return $this->investmentIds($partner)->contains($investmentId) ? Investment::find($investmentId) : null;
    }

    /** Loans linked to the partner (see class comment). */
    public function fundedLoans(Partner $partner, ?int $investmentId = null): Builder
    {
        $investmentIds = $investmentId ? collect([$investmentId]) : $this->investmentIds($partner);
        $viaInvestment = Disbursement::query()->whereIn('investment_id', $investmentIds->all() ?: [0])->select('loan_id');

        return Loan::query()
            ->where(function ($q) use ($viaInvestment, $partner, $investmentId) {
                $q->whereIn('id', $viaInvestment);
                if (! $investmentId) {
                    $q->orWhere('partner_id', $partner->id);
                }
            });
    }

    /** Borrower as the partner may see them: initials and @handle, never ID number or phone. */
    public static function borrowerLabel(?User $user): array
    {
        if (! $user) {
            return ['initials' => '?', 'handle' => null];
        }
        $initials = collect(preg_split('/\s+/', trim((string) $user->name)))->filter()->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

        return ['initials' => $initials ?: '?', 'handle' => $user->handle ? '@' . $user->handle : null];
    }

    /** How the partner stands on one loan: repayment progress (LoanProgress) and their own journal rows for it. */
    public function loanDetail(Partner $partner, Loan $loan): array
    {
        $progress = app(LoanProgress::class)->for($loan);

        return [
            'loan' => $loan,
            'progress' => $progress,
            'borrower' => self::borrowerLabel($loan->user),
            'disbursement' => $loan->disbursements->sortBy('id')->first(),
            'investment' => $loan->disbursements->pluck('investment_id')->filter()->intersect($this->investmentIds($partner))->first(),
            'brokered' => (int) $loan->partner_id === (int) $partner->id,
            'entries' => $partner->ledgerEntries()->where('loan_id', $loan->id)->latest('transaction_date')->latest('id')->get(),
        ];
    }

    /** Earnings from the journal: totals by type, and by month (newest first) split by type. */
    public function earnings(Partner $partner, ?int $year = null): array
    {
        $rows = $partner->ledgerEntries()
            ->whereIn('type', self::EARNING_TYPES)
            ->when($year, fn ($q) => $q->whereYear('transaction_date', $year))
            ->get(['type', 'amount', 'transaction_date']);

        $byType = collect(self::EARNING_TYPES)->map(fn ($t) => [
            'type' => $t,
            'label' => PartnerPortfolio::TYPES[$t],
            'total' => (float) $rows->where('type', $t)->sum('amount'),
            'entries' => $rows->where('type', $t)->count(),
        ])->values();

        $byMonth = $rows->groupBy(fn ($r) => Carbon::parse($r->transaction_date)->format('Y-m'))
            ->sortKeysDesc()
            ->map(fn ($group, $month) => [
                'month' => Carbon::createFromFormat('Y-m-d', $month . '-01'),
                'total' => (float) $group->sum('amount'),
                'types' => collect(self::EARNING_TYPES)->mapWithKeys(fn ($t) => [$t => (float) $group->where('type', $t)->sum('amount')])->all(),
            ])->values();

        $years = $partner->ledgerEntries()->whereIn('type', self::EARNING_TYPES)->pluck('transaction_date')
            ->map(fn ($d) => (int) Carbon::parse($d)->format('Y'))->unique()->sortDesc()->values();

        return [
            'total' => (float) $rows->sum('amount'),
            'by_type' => $byType,
            'by_month' => $byMonth,
            'years' => $years,
        ];
    }

    /** Journal rows for the statement and its CSV, filtered by wallet and date range. */
    public function statementQuery(Partner $partner, ?string $wallet, ?string $from, ?string $to): HasMany
    {
        return $partner->ledgerEntries()
            ->when($wallet && array_key_exists($wallet, PartnerPortfolio::WALLETS), fn ($q) => $q->where('wallet_slug', $wallet))
            ->when($from, fn ($q) => $q->whereDate('transaction_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('transaction_date', '<=', $to))
            ->latest('transaction_date')->latest('id');
    }

    /**
     * What the partner can ask to withdraw, keyed by wallet slug (Bavix, read-only).
     * Capital (S-P1, interim until Phase 3): lending never moves the principal wallet, so the capital still
     * lent out (investment_fundings disbursed − returned) is taken off; `wallet` keeps the raw balance.
     */
    public function withdrawable(Partner $partner): array
    {
        $wallets = [];
        foreach (PartnerPortfolio::WALLETS as $slug => $label) {
            $balance = (float) ($partner->getWallet($slug)?->balanceFloat ?? 0);
            $wallets[$slug] = ['label' => $label, 'balance' => $balance, 'wallet' => $balance, 'lent_out' => 0.0];
        }

        $lentOut = $partner->lentOut();
        $wallets['principal']['lent_out'] = $lentOut;
        $wallets['principal']['balance'] = max(0, round($wallets['principal']['wallet'] - $lentOut, 2));
        $wallets['principal']['label'] = 'Capital not lent out';

        return $wallets;
    }

    /** Where a payout would go, from the partner record: bank first, else M-Pesa on the registered phone. */
    public static function payoutMethod(Partner $partner): ?array
    {
        if ($partner->bank_account_number) {
            return [
                'type' => 'bank',
                'label' => trim(($partner->bank_name ?: 'Bank') . ' •••• ' . substr((string) $partner->bank_account_number, -4)),
                'name' => $partner->bank_account_name ?: $partner->name,
            ];
        }
        if ($partner->phone) {
            $digits = preg_replace('/\D/', '', (string) $partner->phone);

            return ['type' => 'mpesa', 'label' => 'M-Pesa ' . substr($digits, 0, 4) . ' ••• ' . substr($digits, -3), 'name' => $partner->name];
        }

        return null;
    }

    public static function entryIsOut(PartnerTransaction $entry): bool
    {
        return $entry->type === 'withdrawal' || (float) $entry->amount < 0;
    }
}
