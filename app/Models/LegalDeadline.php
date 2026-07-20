<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalDeadline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'deadline_type',
        'deadline_date',
        'description',
        'extension_date',
        'extension_reason',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'deadline_date' => 'date',
        'extension_date' => 'date',
    ];

    protected $appends = [
        'formatted_status',
        'is_overdue',
        'days_remaining',
    ];

    // ============ RELATIONSHIPS ============

    public function case()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============ ACCESSORS ============

    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'pending' => 'Pending',
            'met' => 'Met',
            'extended' => 'Extended',
            'missed' => 'Missed',
            'waived' => 'Waived',
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'pending' && $this->deadline_date->isPast();
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->status !== 'pending') {
            return 0;
        }
        return max(0, $this->deadline_date->diffInDays(now()));
    }

    // ============ SCOPES ============

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('deadline_date', '<', now());
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('status', 'pending')
            ->whereBetween('deadline_date', [today(), today()->addDays($days)]);
    }
}