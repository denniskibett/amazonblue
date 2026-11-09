<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A borrower's Sharet pay link for a loan (database/sql/2026_11_09_sharet_pay.sql). Paid = one repayment. */
class SharetPaymentRequest extends Model
{
    protected $fillable = ['sharet_id', 'loan_id', 'user_id', 'environment', 'amount', 'status', 'pay_url', 'reference', 'paid_via',
        'sharet_transaction_id', 'paid_at', 'expires_at', 'repayment_id', 'note', 'created_by', 'raw'];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'expires_at' => 'datetime', 'raw' => 'array'];

    public function loan()
    {
        return $this->belongsTo(Loan::class)->withTrashed();
    }

    public function repayment()
    {
        return $this->belongsTo(Repayment::class)->withTrashed();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
