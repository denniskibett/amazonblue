<?php
// app/Models/Partner.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'company_name',
        'registration_number',
        'type',
        'status',
        'total_contribution',
        'total_withdrawn',
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
        'total_contribution' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'profit_share_rate' => 'decimal:2',
        'max_loan_to_value' => 'decimal:2'
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
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

    // ============ METHODS ============

    public function addContribution(float $amount, string $reference = null, string $notes = null): PartnerTransaction
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

    public function withdraw(float $amount, string $reference = null, string $notes = null): PartnerTransaction
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

        return $this->transactions()->create([
            'type' => 'repayment',
            'amount' => $amount,
            'balance_after' => $this->current_balance,
            'reference' => $repayment->transaction,
            'loan_id' => $repayment->loan_id,
            'repayment_id' => $repayment->id,
            'notes' => "Repayment from investment",
            'transaction_date' => now()
        ]);
    }

    public function distributeProfit(float $amount, string $notes = null): PartnerTransaction
    {
        $this->current_balance += $amount;
        $this->save();

        return $this->transactions()->create([
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
}