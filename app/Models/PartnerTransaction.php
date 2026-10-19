<?php
// app/Models/PartnerTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        // without these two, create() silently dropped them: rows fell back to the 'principal' default
        // and PartnerLedger's duplicate check (reference + wallet_slug) never matched
        'wallet_slug',
        'investment_funding_id',
        'type',
        'amount',
        'balance_after',
        'reference',
        'loan_id',
        'repayment_id',
        'investment_id',
        'notes',
        'transaction_date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:4', // columns are decimal(19,4)
        'balance_after' => 'decimal:4',
        'transaction_date' => 'date'
    ];

    // ============ RELATIONSHIPS ============

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function repayment()
    {
        return $this->belongsTo(Repayment::class);
    }

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    public function disbursements()
    {
        return $this->hasMany(Disbursement::class, 'partner_transaction_id');
    }

    // ============ ACCESSORS ============

    public function getFormattedTypeAttribute()
    {
        $types = [
            'contribution' => 'Contribution',
            'withdrawal' => 'Withdrawal',
            'profit_distribution' => 'Profit Distribution',
            'bonus' => 'Bonus',
            'repayment' => 'Repayment'
        ];
        return $types[$this->type] ?? ucfirst($this->type);
    }

    public function getIsCreditAttribute()
    {
        return in_array($this->type, ['contribution', 'profit_distribution', 'bonus', 'repayment']);
    }

    public function getIsDebitAttribute()
    {
        return in_array($this->type, ['withdrawal']);
    }

    public function getTransactionTypeAttribute()
    {
        if ($this->investment_id) return 'Investment';
        if ($this->loan_id) return 'Loan';
        return 'General';
    }

    public function getRelatedNameAttribute()
    {
        if ($this->investment_id) {
            return $this->investment?->name ?? 'Unknown Investment';
        }
        if ($this->loan_id) {
            return $this->loan?->user?->name ?? 'Unknown Borrower';
        }
        return 'N/A';
    }
}