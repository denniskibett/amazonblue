<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A borrower's "I've paid" (Stage 4 Phase 4): checking -> received | rejected. */
class PaymentClaim extends Model
{
    protected $fillable = ['user_id', 'loan_id', 'code', 'amount', 'phone', 'status', 'reason', 'repayment_id', 'mpesa_transaction_id', 'decided_by'];

    protected $casts = ['amount' => 'decimal:2'];

    public function loan()
    {
        return $this->belongsTo(Loan::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repayment()
    {
        return $this->belongsTo(Repayment::class)->withTrashed();
    }
}
