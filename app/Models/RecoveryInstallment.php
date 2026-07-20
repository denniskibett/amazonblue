<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryInstallment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_plan_id',
        'installment_number',
        'due_date',
        'amount',
        'paid_amount',
        'payment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    protected $appends = [
        'formatted_status',
        'remaining_amount',
        'is_overdue',
        'is_paid',
        'status_color',
    ];

    // ============ RELATIONSHIPS ============

    public function paymentPlan()
    {
        return $this->belongsTo(RecoveryPaymentPlan::class, 'payment_plan_id');
    }

    // ============ ACCESSORS ============

    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'partial' => 'Partial',
            'overdue' => 'Overdue',
            'waived' => 'Waived',
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'overdue' || 
               ($this->status === 'pending' && $this->due_date->isPast());
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'paid' || $this->paid_amount >= $this->amount;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'paid' => 'green',
            'partial' => 'blue',
            'overdue' => 'red',
            'waived' => 'gray',
        ];
        return $colors[$this->status] ?? 'gray';
    }

    // ============ SCOPES ============

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')
                  ->where('due_date', '<', now());
            });
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->whereBetween('due_date', [today(), today()->addDays($days)])
            ->where('status', 'pending');
    }

    // ============ HELPERS ============

    public function markAsPaid($paymentDate = null)
    {
        $this->update([
            'paid_amount' => $this->amount,
            'payment_date' => $paymentDate ?? now(),
            'status' => 'paid',
        ]);
    }

    public function recordPartialPayment($amount, $paymentDate = null)
    {
        $newPaid = $this->paid_amount + $amount;
        $status = $newPaid >= $this->amount ? 'paid' : 'partial';

        $this->update([
            'paid_amount' => $newPaid,
            'payment_date' => $paymentDate ?? now(),
            'status' => $status,
        ]);
    }
}