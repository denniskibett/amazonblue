<?php
// app/Models/Partner.php

namespace App\Models;

use Bavix\Wallet\Traits\HasWallets;
use Bavix\Wallet\Traits\CanPay;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Interfaces\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model implements Wallet, Customer
{
    use HasFactory, HasWallets, CanPay;

    protected $fillable = [
        'user_id',
        'type',              
        'is_house_account',  
        'name',
        'email',
        'phone',
        'company_name',
        'registration_number',
        'status',
        'current_balance',
        'profit_share_rate',
        'max_loan_to_value',
        'risk_tolerance',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'swift_code',
        'tax_id',
        'notes'
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'profit_share_rate' => 'decimal:2',
        'max_loan_to_value' => 'decimal:2',
        'is_house_account' => 'boolean'
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
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

    public function getAvailableBalanceAttribute()
    {
        $used = $this->transactions()
            ->where('type', 'contribution')
            ->sum('amount') - $this->transactions()
            ->where('type', 'repayment')
            ->sum('amount');

        return $this->current_balance - $used;
    }

    public function getTotalInvestedAttribute()
    {
        return $this->transactions()
            ->where('type', 'contribution')
            ->sum('amount');
    }

    public function getTotalReturnedAttribute()
    {
        return $this->transactions()
            ->where('type', 'repayment')
            ->sum('amount');
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
     * Sum of all returned amounts across all pivot fundings.
     */
    public function getTotalFundingReturnedAttribute(): float
    {
        return (float) $this->fundings()->sum('amount_returned');
    }

    // ============ METHODS ============

    public function addContribution(float $amount, ?string $reference = null, ?string $notes = null)
    {
        $this->total_contribution += $amount;
        $this->current_balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'contribution',
            'amount' => $amount,
            'balance_after' => $this->current_balance,
            'reference' => $reference,
            'notes' => $notes,
            'transaction_date' => now()
        ]);
    }

    public function withdrawPartnerBalance(float $amount, ?string $reference = null, ?string $notes = null)
    {
        if ($amount > $this->current_balance) {
            throw new \Exception('Insufficient balance');
        }

        $this->total_withdrawn += $amount;
        $this->current_balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'withdrawal',
            'amount' => -$amount,
            'balance_after' => $this->current_balance,
            'reference' => $reference,
            'notes' => $notes,
            'transaction_date' => now()
        ]);
    }

    public function recordRepayment(Repayment $repayment, float $amount): PartnerTransaction
    {
        $this->current_balance += $amount;
        $this->save();

        return PartnerTransaction::create([
            'type' => 'repayment',
            'partner_id' => $this->id,
            'amount' => $amount,
            'balance_after' => $this->current_balance,
            'reference' => $repayment->transaction,
            'loan_id' => $repayment->loan_id,
            'repayment_id' => $repayment->id,
            'notes' => "Repayment from investment",
            'transaction_date' => now()
        ]);
    }

    public function distributeProfit(float $amount, ?string $notes = null): PartnerTransaction
    {
        $this->current_balance += $amount;
        $this->save();

        return PartnerTransaction::create([
            'partner_id' => $this->id,
            'type' => 'profit_distribution',
            'amount' => $amount,
            'balance_after' => $this->current_balance,
            'notes' => $notes,
            'transaction_date' => now()
        ]);
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