<?php
// app/Models/Disbursement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Disbursement extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'amount',
        'transaction',
        'mode',
        'disburse_date',
        'payment_date',
        'partner_transaction_id',
        'funding_source',
        'investment_id'
    ];

    protected $casts = [
        'disburse_date' => 'datetime',
        'payment_date' => 'datetime',
        'amount' => 'decimal:2'
    ];

    // ============ RELATIONSHIPS ============

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function partnerTransaction()
    {
        return $this->belongsTo(PartnerTransaction::class);
    }

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    // ============ ACCESSORS ============

    public function getFundingSourceLabelAttribute()
    {
        $sources = [
            'internal' => 'Internal Funds',
            'partner' => 'Partner Funds',
            'mixed' => 'Mixed Funds'
        ];
        return $sources[$this->funding_source] ?? ucfirst($this->funding_source);
    }

    public function getPartnerNameAttribute()
    {
        return $this->partnerTransaction?->partner?->name ?? 'N/A';
    }

    public function getIsInvestmentAttribute()
    {
        return !is_null($this->investment_id);
    }

    public function getInvestmentNameAttribute()
    {
        return $this->investment?->name ?? 'N/A';
    }
}