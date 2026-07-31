<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoanCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'cycle_number', 'previous_balance',
        'interest_capitalized', 'new_balance', 'interest_rate',
        'start_date', 'due_date', 'status', 'notes'
    ];

    protected $casts = [
        'previous_balance' => 'decimal:2',
        'interest_capitalized' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'completed' => 'Completed',
            'defaulted' => 'Defaulted',
            'repaid' => 'Repaid',
            default => ucfirst($this->status ?? 'Unknown')
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'completed' => 'blue',
            'defaulted' => 'red',
            'repaid' => 'emerald',
            default => 'gray'
        };
    }

    // Calculate days in this cycle
    public function getDaysInCycleAttribute(): int
    {
        $start = Carbon::parse($this->start_date);
        $end = $this->status === 'active' ? now() : Carbon::parse($this->due_date);
        return $start->diffInDays($end);
    }

    // Check if cycle is overdue
    public function isOverdue(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        return Carbon::now()->gt($this->due_date);
    }
}