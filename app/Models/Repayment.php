<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CustomerWallets;

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
        'notes',
        'kind',                    // cash | rollover | discount | bad_debt | non_cash (CustomerWallets::KINDS)
        'created_by',
        'reversal_of_id',          // a reversal is a new row pointing at the original
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'repayment_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        // kind is stored (Stage 4 Phase 1). A form that sends no kind gets the old reading of the reference,
        // and editing only the reference re-reads it, so screens behave as before until every form picks one.
        static::saving(function (self $row) {
            if (! in_array($row->kind, CustomerWallets::KINDS, true) || ($row->isDirty('transaction') && ! $row->isDirty('kind') && $row->exists)) {
                $row->kind = CustomerWallets::kind($row->transaction);
            }
        });
        static::creating(function (self $row) {
            $row->created_by ??= auth()->id();
            // an M-Pesa receipt is recorded once, whichever way it arrives (callback or by hand; Stage 4 Phase 4)
            $code = strtoupper(trim((string) $row->transaction));
            if ($row->kind === 'cash' && preg_match('/^[A-Z0-9]{10}$/', $code) && ! $row->reversal_of_id) {
                $dup = self::where('transaction', $code)->whereNull('reversal_of_id')->first();
                if ($dup) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'transaction' => "M-Pesa receipt {$code} is already recorded (repayment #{$dup->id}, loan #{$dup->loan_id}).",
                    ]);
                }
            }
        });
        // typed by hand before Daraja's callback: the callback, when it comes, finds this row (PaymentRecorder::match);
        // one already waiting in the queue is linked now, and the borrower's claim reads "received"
        static::created(function (self $row) {
            $code = strtoupper(trim((string) $row->transaction));
            if ($row->kind !== 'cash' || ! preg_match('/^[A-Z0-9]{10}$/', $code)) {
                return;
            }
            MpesaTransaction::where('trans_id', $code)->whereIn('status', ['unmatched', 'received'])
                ->update(['status' => 'matched', 'repayment_id' => $row->id, 'loan_id' => $row->loan_id, 'note' => 'Recorded by hand', 'updated_at' => now()]);
            app(\App\Services\Mpesa\PaymentRecorder::class)->settleClaims($code, $row);
        });
    }

    // ============ RELATIONSHIPS ============

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** The row this one reverses. */
    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id')->withTrashed();
    }

    /** The row that reverses this one, if any. */
    public function reversal()
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

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