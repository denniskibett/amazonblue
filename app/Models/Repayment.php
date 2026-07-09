<?php
// app/Models/Repayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Repayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'amount',
        'transaction',
        'repayment_date',
        'mode',
        'partner_transaction_id',
        'investment_id'
    ];

    protected $casts = [
        'repayment_date' => 'datetime',
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

    public function getIsInvestmentReturnAttribute()
    {
        return !is_null($this->investment_id);
    }

    public function getInvestmentNameAttribute()
    {
        return $this->investment?->name ?? 'N/A';
    }

    // ============ METHODS ============

    public function allocateToPartner(Partner $partner): void
    {
        $transaction = $partner->recordRepayment($this, $this->amount);
        $this->partner_transaction_id = $transaction->id;
        $this->save();
    }
}