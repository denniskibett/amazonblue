<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'assigned_to',
        'title',
        'description',
        'priority_id',
        'status',
        'due_date',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'priority_name',
        'formatted_status',
        'is_overdue',
    ];

    // ============ RELATIONSHIPS ============

    public function case()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function priority()
    {
        return $this->belongsTo(RecoveryPriority::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============ ACCESSORS ============

    public function getPriorityNameAttribute()
    {
        return $this->priority ? $this->priority->name : null;
    }

    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getIsOverdueAttribute()
    {
        return $this->status !== 'completed' && 
               $this->due_date && 
               $this->due_date->isPast();
    }

    // ============ SCOPES ============

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress'])
            ->where('due_date', '<', now());
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }
}