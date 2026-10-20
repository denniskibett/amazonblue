<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One M-Pesa payment as Daraja reported it (Stage 4 Phase 4). */
class MpesaTransaction extends Model
{
    protected $fillable = ['type', 'trans_id', 'checkout_request_id', 'msisdn', 'amount', 'bill_ref', 'shortcode', 'trans_time',
        'loan_id', 'repayment_id', 'status', 'note', 'payload', 'resolved_by'];

    protected $casts = ['amount' => 'decimal:2', 'trans_time' => 'datetime', 'payload' => 'array'];

    public function loan()
    {
        return $this->belongsTo(Loan::class)->withTrashed();
    }

    public function repayment()
    {
        return $this->belongsTo(Repayment::class)->withTrashed();
    }

    /** The phone with all but the last 3 digits hidden, for screens and logs. */
    public function maskedMsisdn(): string
    {
        $d = preg_replace('/\D/', '', (string) $this->msisdn);

        return strlen($d) >= 9 ? str_repeat('•', strlen($d) - 3) . substr($d, -3) : ($this->msisdn ? 'hidden' : '');
    }
}
