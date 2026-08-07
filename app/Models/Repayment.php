<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Repayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_id',
        'loan_cycle_id',          // NEW: Link to cycle
        'amount',
        'processing_fee',          // NEW: Track fee
        'net_amount',              // NEW: Amount after fee
        'transaction',
        'repayment_date',
        'mode',
        'partner_transaction_id',
        'investment_id',
        'bavix_transaction_id',    // NEW: Link to Bavix
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'repayment_date' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function loanCycle()
    {
        return $this->belongsTo(LoanCycle::class);
    }

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    // ============ ACCESSORS ============

    public function getNetAmountAttribute(): float
    {
        return $this->amount - $this->processing_fee;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'KES ' . number_format($this->amount, 2);
    }

    public function getFormattedProcessingFeeAttribute(): string
    {
        return 'KES ' . number_format($this->processing_fee, 2);
    }

    // ============ SCOPES ============

    public function scopeByCycle($query, $cycleId)
    {
        return $query->where('loan_cycle_id', $cycleId);
    }

    public function scopeByLoan($query, $loanId)
    {
        return $query->where('loan_id', $loanId);
    }

    // ============ METHODS ============

    public function calculateProcessingFee(float $rate): float
    {
        return ($rate / 100) * $this->amount;
    }

    public function recordBavixTransaction(string $transactionId): void
    {
        $this->bavix_transaction_id = $transactionId;
        $this->save();
    }
}