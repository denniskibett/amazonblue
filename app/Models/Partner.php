<?php
// app/Models/Partner.php

namespace App\Models;

use Bavix\Wallet\Traits\HasWallets;
use Bavix\Wallet\Traits\CanPay;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Interfaces\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\PartnerLedger;
use App\Support\Handle;
use Bavix\Wallet\Models\Wallet as BavixWallet;
use Illuminate\Support\Facades\DB;

class Partner extends Model implements Wallet, Customer
{
    use HasFactory, HasWallets, CanPay;

    protected $fillable = [
        'user_id',
        'business_id',       // a business partner's verified business (owner, 24 Oct 2026)
        'source_of_funds',
        'wallet_slug',
        'type',
        'account_type',      // house accounts: principal / interest / operating / reserve
        'is_house_account',
        'name',
        'email',
        'phone',
        'company_name',
        'registration_number',
        'status',
        'current_balance',
        'profit_share_rate',
        'broker_rate',
        'max_loan_to_value',
        'risk_tolerance',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'swift_code',
        'tax_id',
        'notes',
        'handle',
    ];

    protected static function booted(): void
    {
        // wallet handle (rule 9): set once from the name when missing
        static::saving(function (Partner $partner) {
            if ((! $partner->exists || array_key_exists('handle', $partner->getAttributes())) && empty($partner->handle) && ($handle = Handle::forPartner($partner))) {
                $partner->handle = $handle;
            }
        });
    }

    protected $casts = [
        'current_balance' => 'decimal:2',
        'profit_share_rate' => 'decimal:2',
        'max_loan_to_value' => 'decimal:2',
        'is_house_account' => 'boolean',
        'broker_rate' => 'decimal:2'
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The verified business behind a business partner (businesses table; one copy of the company details). */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(PartnerTransaction::class);
    }

    public function disbursements()
    {
        return $this->hasManyThrough(
            Disbursement::class,
            PartnerTransaction::class,
            'partner_id',
            'partner_transaction_id',
            'id',
            'id'
        );
    }

    public function investments()
    {
        return $this->hasManyThrough(
            Investment::class,
            PartnerTransaction::class,
            'partner_id',
            'id',
            'id',
            'investment_id'
        );
    }

    /**
     * NEW: pivot rows — one partner can fund many investments.
     */
    public function fundings()
    {
        return $this->hasMany(InvestmentFunding::class);
    }

    /**
     * NEW: many-to-many with investments through investment_fundings.
     */
    public function fundedInvestments()
    {
        return $this->belongsToMany(Investment::class, 'investment_fundings')
            ->withPivot([
                'id',
                'partner_transaction_id',
                'amount_committed',
                'amount_disbursed',
                'amount_returned',
                'status',
                'notes',
            ])
            ->withTimestamps();
    }

    // ============ ACCESSORS ============

    /**
     * What the partner can withdraw now: the principal wallet (the journal and wallet move together).
     */
    public function getAvailableBalanceAttribute()
    {
        return (float) ($this->getWallet('principal')?->balanceFloat ?? 0);
    }

    // journal (partner_transactions), not Bavix's transactions() relation which this model also has
    public function getTotalInvestedAttribute()
    {
        return (float) $this->ledgerEntries()->where('type', 'contribution')->sum('amount');
    }

    public function getTotalReturnedAttribute()
    {
        return (float) $this->ledgerEntries()->where('type', 'repayment')->sum('amount');
    }

    public function getNetPositionAttribute()
    {
        return $this->total_returned - $this->total_invested;
    }

    /**
     * Sum of all committed amounts across all pivot fundings.
     */
    public function getTotalCommittedAttribute(): float
    {
        return (float) $this->fundings()->sum('amount_committed');
    }

    /**
     * Sum of all disbursed amounts across all pivot fundings.
     */
    public function getTotalDisbursedAttribute(): float
    {
        return (float) $this->fundings()->sum('amount_disbursed');
    }

    /**
     * Capital still lent out: disbursed − returned on fundings that weren't cancelled. Lending doesn't move the
     * principal wallet yet (S-P1), so withdrawals of capital are capped at principal − this (Phase 3 replaces it).
     */
    public function lentOut(): float
    {
        return round(max(0, (float) $this->fundings()->where('status', '!=', 'cancelled')
            ->sum(\Illuminate\Support\Facades\DB::raw('amount_disbursed - amount_returned'))), 2);
    }

    /**
     * Sum of all returned amounts across all pivot fundings.
     */
    public function getTotalFundingReturnedAttribute(): float
    {
        return (float) $this->fundings()->sum('amount_returned');
    }

    // ============ METHODS ============

    /*
     * Money movements go through the partner journal (PartnerLedger): one partner_transactions row and
     * the matching Bavix wallet posting, atomically. Never through $this->transactions(), which is
     * Bavix's own relation, and never by editing current_balance (a legacy column, left as it is).
     */

    /**
     * Capital paid in by the partner: principal wallet + journal 'contribution'.
     */
    public function addContribution(float $amount, ?string $reference = null, ?string $notes = null, ?int $investmentId = null, ?int $investmentFundingId = null): PartnerTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Contribution must be more than zero.');
        }

        return app(PartnerLedger::class)->post(
            $this, 'principal', 'contribution', $amount,
            investmentId: $investmentId, investmentFundingId: $investmentFundingId,
            reference: $reference ?: null, notes: $notes ?? 'Contribution'
        );
    }

    /**
     * Pay money out to the partner from one of its wallets; refused if the wallet doesn't hold enough.
     */
    public function withdrawPartnerBalance(float $amount, ?string $reference = null, ?string $notes = null, string $walletSlug = 'principal'): PartnerTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Withdrawal must be more than zero.');
        }

        return DB::transaction(function () use ($amount, $reference, $notes, $walletSlug) {
            $wallet = $this->getWallet($walletSlug);
            $available = 0.0;
            if ($wallet) {
                // one withdrawal at a time per wallet, so two requests can't both pass the balance check
                BavixWallet::whereKey($wallet->getKey())->lockForUpdate()->first();
                $wallet->refresh();
                $available = (float) $wallet->balanceFloat;
            }
            if (round($amount, 4) > round($available, 4)) {
                throw new \RuntimeException(sprintf('Insufficient balance: the %s wallet holds KES %s.', str_replace('_', ' ', $walletSlug), number_format($available, 2)));
            }

            return app(PartnerLedger::class)->post(
                $this, $walletSlug, 'withdrawal', -$amount,
                reference: $reference ?: null, notes: $notes ?? 'Withdrawal'
            );
        });
    }

    /**
     * Money returned to the partner from a repayment: principal wallet + journal 'repayment'.
     */
    public function recordRepayment(Repayment $repayment, float $amount): PartnerTransaction
    {
        return app(PartnerLedger::class)->post(
            $this, 'principal', 'repayment', $amount,
            loanId: $repayment->loan_id, repaymentId: $repayment->id,
            reference: 'REP-' . $repayment->id . '-P' . $this->id,
            notes: 'Repayment from investment', transactionDate: optional($repayment->repayment_date)->toDateString()
        );
    }

    /**
     * Profit paid to the partner: interest wallet + journal 'profit_distribution'.
     */
    public function distributeProfit(float $amount, ?string $notes = null, ?int $loanId = null, ?string $reference = null): PartnerTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Profit must be more than zero.');
        }

        return app(PartnerLedger::class)->post(
            $this, 'interest', 'profit_distribution', $amount,
            loanId: $loanId, reference: $reference, notes: $notes ?? 'Profit distribution'
        );
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeHouseAccounts($query)
    {
        return $query->where('is_house_account', true);
    }

    public function scopeExternal($query)
    {
        return $query->whereIn('type', ['partner', 'business', 'investor']);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getIsHousePrincipalAttribute(): bool
    {
        return $this->is_house_account
            && $this->getWallet('principal') !== null;
    }

    public function getIsHouseInterestAttribute(): bool
    {
        return $this->is_house_account
            && $this->getWallet('interest') !== null;
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'partner' => 'External Partner',
            'business' => 'Affiliated Business',
            'company' => 'Company',
            'investor' => 'Equity Investor',
            default => ucfirst($this->type),
        };
    }

}