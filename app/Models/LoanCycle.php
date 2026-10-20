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
        'start_date', 'due_date', 'status', 'notes',
        // Stage 4 Phase 2: the cycle's own terms and its balance cache (see the 2026_10_20_000201 migration)
        'principal', 'penalty_rate', 'grace_days', 'default_threshold_days', 'penalty_waived_until',
        'closing_balance', 'penalty_accrued', 'principal_paid', 'interest_paid', 'penalty_paid', 'balance_as_of', 'closed_on',
    ];

    protected $casts = [
        'previous_balance' => 'decimal:2',
        'interest_capitalized' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'principal' => 'decimal:2',
        'penalty_rate' => 'decimal:3',
        'penalty_waived_until' => 'date',
        'closing_balance' => 'decimal:2',
        'penalty_accrued' => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'interest_paid' => 'decimal:2',
        'penalty_paid' => 'decimal:2',
        'balance_as_of' => 'date',
        'closed_on' => 'date',
    ];

    /**
     * What this cycle lends: the stored principal, else what was carried in (cycles 2+), else cycle 1's
     * new_balance less its interest. Never loans.amount, which changes after a rollover.
     */
    public function lentPrincipal(): float
    {
        if ($this->principal !== null) {
            return (float) $this->principal;
        }
        if ((float) $this->previous_balance > 0) {
            return (float) $this->previous_balance;
        }

        return max(0.0, (float) $this->new_balance - (float) $this->interest_capitalized);
    }

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