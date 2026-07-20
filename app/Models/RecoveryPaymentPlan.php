<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryPaymentPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'total_amount',
        'down_payment',
        'installment_amount',
        'number_of_installments',
        'installment_frequency',
        'start_date',
        'end_date',
        'status',
        'agreed_by_debtor',
        'agreed_date',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'agreed_date' => 'date',
        'agreed_by_debtor' => 'boolean',
    ];

    protected $appends = [
        'formatted_status',
        'total_paid',
        'remaining_balance',
        'progress_percentage',
        'formatted_frequency',
    ];

    // ============ RELATIONSHIPS ============

    public function case()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }

    public function installments()
    {
        return $this->hasMany(RecoveryInstallment::class, 'payment_plan_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ============ ACCESSORS ============

    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'draft' => 'Draft',
            'proposed' => 'Proposed',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'defaulted' => 'Defaulted',
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getTotalPaidAttribute()
    {
        return $this->installments()->sum('paid_amount');
    }

    public function getRemainingBalanceAttribute()
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->total_amount <= 0) {
            return 0;
        }
        return round(($this->total_paid / $this->total_amount) * 100, 2);
    }

    public function getFormattedFrequencyAttribute()
    {
        $frequencies = [
            'weekly' => 'Weekly',
            'bi_weekly' => 'Bi-Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
        ];
        return $frequencies[$this->installment_frequency] ?? ucfirst($this->installment_frequency);
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['proposed', 'accepted']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}