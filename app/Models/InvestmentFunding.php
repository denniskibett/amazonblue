<?php
// app/Models/InvestmentFunding.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentFunding extends Model
{
    use HasFactory;

    protected $fillable = [
        'investment_id',
        'partner_id',
        'partner_transaction_id',
        'amount_committed',
        'amount_disbursed',
        'amount_returned',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount_committed' => 'decimal:2',
        'amount_disbursed' => 'decimal:2',
        'amount_returned' => 'decimal:2',
    ];

    // ============ RELATIONSHIPS ============

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function partnerTransaction()
    {
        return $this->belongsTo(PartnerTransaction::class);
    }

    // ============ ACCESSORS ============

    public function getRemainingToDisburseAttribute(): float
    {
        return max(0, (float) $this->amount_committed - (float) $this->amount_disbursed);
    }

    public function getRemainingToReturnAttribute(): float
    {
        return max(0, (float) $this->amount_disbursed - (float) $this->amount_returned);
    }

    public function getIsSettledAttribute(): bool
    {
        return $this->status === 'settled';
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}