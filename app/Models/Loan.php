<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    // ============ STATUS CONSTANTS ============
    
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_DISBURSED = 'disbursed';
    const STATUS_ACTIVE = 'active';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_DEFAULTED = 'defaulted';
    const STATUS_RECOVERY = 'recovery';
    const STATUS_FORBEARANCE = 'forbearance';
    const STATUS_REPAID = 'repaid';
    const STATUS_WRITTEN_OFF = 'written_off';
    const STATUS_REJECTED = 'rejected';

    const STATUS_ACTIVE_LIST = [
        self::STATUS_ACTIVE,
        self::STATUS_OVERDUE,
        self::STATUS_DISBURSED,
    ];

    const STATUS_FINAL_LIST = [
        self::STATUS_REPAID,
        self::STATUS_WRITTEN_OFF,
        self::STATUS_REJECTED,
    ];

    // ============ FILLABLE ============

    protected $fillable = [
        'user_id',
        'loan_type_id',
        'amount',
        'borrow_date',
        'status',
        'broker_status',
        'reason',
        'guarantor_id',
        'guarantor_relationship', 
        'loan_officer_id',
        'consent',
        'consent_date',
        'signature',
        'is_non_performing',
        'default_date',
        'days_overdue',
        'last_overdue_check',
        'default_triggered',
        'calculated_due_date',
        'npl_trigger_threshold',
        'cycle',
        'original_amount',
        'capitalized_interest',
        'grace_period_days',
        'grace_period_end_date',
        'grace_days_balance',
        'grace_days_earned',
        'grace_days_used',
        'days_in_default',
        'default_triggered_at',
        'recovery_started_at',
        'forbearance_until',
        'recovery_notes',
    ];

    // ============ CASTS ============

    protected $casts = [
        'amount' => 'decimal:2',
        'capitalized_interest' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'borrow_date' => 'datetime',
        'consent_date' => 'datetime',
        'default_date' => 'date',
        'calculated_due_date' => 'date',
        'last_overdue_check' => 'datetime',
        'grace_period_end_date' => 'date',
        'default_triggered_at' => 'datetime',
        'recovery_started_at' => 'datetime',
        'forbearance_until' => 'datetime',
        'consent' => 'boolean',
        'broker_status' => 'boolean',
        'is_non_performing' => 'boolean',
        'default_triggered' => 'boolean',
        'days_overdue' => 'integer',
        'npl_trigger_threshold' => 'integer',
        'cycle' => 'integer',
        'grace_period_days' => 'integer',
        'grace_days_balance' => 'integer',
        'grace_days_earned' => 'integer',
        'grace_days_used' => 'integer',
        'days_in_default' => 'integer',
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guarantor()
    {
        return $this->belongsTo(User::class, 'guarantor_id');
    }

    public function loanOfficer()
    {
        return $this->belongsTo(User::class, 'loan_officer_id');
    }

    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'user_id')->withDefault();
    }

    public function disbursements()
    {
        return $this->hasMany(Disbursement::class);
    }

    public function repayments()
    {
        return $this->hasMany(Repayment::class);
    }
    
    public function loanType()
    {
        return $this->belongsTo(LoanType::class);
    }

    public function agreementSections()
    {
        return $this->hasMany(LoanAgreementSection::class);
    }

    public function riskAssessments()
    {
        return $this->hasMany(LoanRiskAssessment::class);
    }

    public function cycles()
    {
        return $this->hasMany(LoanCycle::class)->orderBy('cycle_number', 'desc');
    }

    public function recoveryCases()
    {
        return $this->hasMany(DebtRecoveryCase::class);
    }

    // ============ STATUS CHECK METHODS ============

    public function isActive(): bool
    {
        return in_array($this->status, self::STATUS_ACTIVE_LIST);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::STATUS_FINAL_LIST);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE || 
               ($this->getDueDate() && Carbon::now()->gt($this->getDueDate()) && 
                !$this->isFinal());
    }

    public function isDefaulted(): bool
    {
        return $this->status === self::STATUS_DEFAULTED;
    }

    public function isInRecovery(): bool
    {
        return $this->status === self::STATUS_RECOVERY;
    }

    public function isInForbearance(): bool
    {
        return $this->status === self::STATUS_FORBEARANCE;
    }

    public function isPerforming(): bool
    {
        return $this->status === self::STATUS_ACTIVE || 
               $this->status === self::STATUS_DISBURSED;
    }

    // ============ NPL THRESHOLD - THE FIX ============

    /**
     * Get the NPL threshold (days overdue before default)
     * 
     * Priority:
     * 1. Use database value if set (npl_trigger_threshold)
     * 2. Calculate from loan type period + grace period
     * 3. Fallback to 30 days
     */
    public function getNplThreshold(): int
    {
        // 1. Use database value if it exists and is > 0
        if ($this->npl_trigger_threshold > 0) {
            return $this->npl_trigger_threshold;
        }

        // 2. Calculate from loan type
        if ($this->loanType) {
            $periodInDays = $this->getPeriodInDays();
            
            // Grace period = 2x the loan period, minimum 14 days, maximum 60 days
            $graceDays = max(14, min(60, $periodInDays * 2));
            
            return $periodInDays + $graceDays;
        }

        // 3. Fallback
        return 30;
    }

    /**
     * Get loan period in days
     */
    private function getPeriodInDays(): int
    {
        if (!$this->loanType) {
            return 30;
        }

        return match($this->loanType->unit) {
            'days' => $this->loanType->period,
            'weeks' => $this->loanType->period * 7,
            'months' => $this->loanType->period * 30,
            'years' => $this->loanType->period * 365,
            default => 30,
        };
    }

    /**
     * Get the due date (from database or calculated)
     */
    public function getDueDate(): ?Carbon
    {
        if ($this->calculated_due_date) {
            return Carbon::parse($this->calculated_due_date);
        }
        
        if ($this->due_date) {
            return Carbon::parse($this->due_date);
        }

        return null;
    }

    public function calculateDaysOverdue(): int
    {
        $dueDate = $this->getDueDate();
        
        if (!$dueDate) {
            return 0;
        }

        // Use diffInDays with absolute value and only if due date is in the past
        $now = Carbon::now();
        
        if ($now->lt($dueDate)) {
            return 0; // Not overdue yet
        }
        
        return (int) $now->diffInDays($dueDate); // Only positive days
    }

    /**
     * Check if loan should be defaulted based on threshold
     */
    public function shouldBeDefaulted(): bool
    {
        // Already defaulted or final status
        if ($this->default_triggered || $this->isFinal()) {
            return false;
        }

        // Must have a due date
        $dueDate = $this->getDueDate();
        if (!$dueDate) {
            return false;
        }

        // Not overdue yet
        if (Carbon::now()->lte($dueDate)) {
            return false;
        }

        $daysOverdue = $this->calculateDaysOverdue();
        $threshold = $this->getNplThreshold();

        return $daysOverdue >= $threshold;
    }

    // ============ SINGLE STATUS UPDATE METHOD ============

    /**
     * Update loan status based on current state
     * This is the ONLY method that changes loan status
     */
    public function updateStatus(): void
    {
        // Don't update final statuses
        if ($this->isFinal()) {
            return;
        }

        // Don't update if in forbearance
        if ($this->isForbearanceActive()) {
            return;
        }

        // Don't update if in recovery (handled by recovery case)
        if ($this->isInRecovery()) {
            return;
        }

        $dueDate = $this->getDueDate();
        
        // No due date - can't determine status
        if (!$dueDate) {
            return;
        }

        $daysOverdue = $this->calculateDaysOverdue();
        $threshold = $this->getNplThreshold();

        // Update days overdue
        $this->days_overdue = $daysOverdue;
        $this->last_overdue_check = now();

        // CASE 1: Not overdue
        if ($daysOverdue <= 0) {
            if (in_array($this->status, [self::STATUS_OVERDUE, self::STATUS_DEFAULTED])) {
                $this->status = self::STATUS_ACTIVE;
            }
            // Reset NPL flags if they were set
            if ($this->is_non_performing) {
                $this->is_non_performing = false;
            }
            $this->save();
            return;
        }

        // CASE 2: Check grace days
        if ($this->grace_days_balance > 0 && $this->grace_days_balance >= $daysOverdue) {
            $this->useGraceDays((int) $daysOverdue);
            if (in_array($this->status, [self::STATUS_OVERDUE, self::STATUS_DEFAULTED])) {
                $this->status = self::STATUS_ACTIVE;
            }
            $this->save();
            return;
        }

        // CASE 3: Defaulted
        if ($daysOverdue >= $threshold && !$this->default_triggered) {
            $this->markAsDefaulted("Loan overdue for {$daysOverdue} days (threshold: {$threshold} days)");
            $this->save();
            return;
        }

        // CASE 4: Overdue (but not defaulted yet)
        if ($this->status !== self::STATUS_OVERDUE) {
            $this->status = self::STATUS_OVERDUE;
            $this->save();
        }
    }

    /**
     * Mark loan as defaulted
     */
    public function markAsDefaulted(string $reason = null): void
    {
        if ($this->default_triggered) {
            return;
        }

        $this->status = self::STATUS_DEFAULTED;
        $this->default_triggered = true;
        $this->default_triggered_at = now();
        $this->default_date = now();
        $this->is_non_performing = true;
        $this->days_in_default = 0;
        
        if ($reason) {
            $this->recovery_notes = ($this->recovery_notes ? $this->recovery_notes . "\n" : '') . 
                                   "Default triggered: {$reason} on " . now()->format('Y-m-d H:i');
        }

        $this->save();

        // Create recovery case
        $this->createRecoveryCase();
    }

    // ============ RECOVERY METHODS ============

    public function startRecovery(): void
    {
        if ($this->isFinal()) {
            return;
        }

        $this->status = self::STATUS_RECOVERY;
        $this->recovery_started_at = now();
        $this->save();
    }

    public function grantForbearance(int $days, string $reason = null): void
    {
        if ($this->isFinal()) {
            return;
        }

        $this->status = self::STATUS_FORBEARANCE;
        $this->forbearance_until = now()->addDays($days);
        $this->recovery_notes = ($this->recovery_notes ? $this->recovery_notes . "\n" : '') . 
                               "Forbearance granted: {$reason} until {$this->forbearance_until->format('Y-m-d')}";
        $this->save();
    }

    public function endForbearance(): void
    {
        if ($this->isInForbearance()) {
            $this->status = self::STATUS_OVERDUE;
            $this->forbearance_until = null;
            $this->save();
        }
    }

    public function isForbearanceActive(): bool
    {
        return $this->status === self::STATUS_FORBEARANCE && 
               $this->forbearance_until && 
               Carbon::now()->lte($this->forbearance_until);
    }

    // ============ RECOVERY CASE CREATION ============

    private function createRecoveryCase(): void
    {
        // Check if recovery case already exists
        if ($this->recoveryCases()->exists()) {
            return;
        }

        $status = RecoveryStatus::where('slug', 'open')->first();
        $priority = RecoveryPriority::where('slug', 'high')->first();

        if (!$status || !$priority) {
            return;
        }

        DebtRecoveryCase::create([
            'user_id' => $this->user_id,
            'loan_id' => $this->id,
            'case_number' => 'DR-' . now()->format('Y') . '-' . str_pad(
                DebtRecoveryCase::whereYear('created_at', now()->year)->count() + 1, 
                4, 
                '0', 
                STR_PAD_LEFT
            ),
            'total_debt_amount' => $this->calculateTotalDebt(),
            'principal_outstanding' => $this->amount,
            'interest_outstanding' => $this->capitalized_interest ?? 0,
            'penalty_outstanding' => 0,
            'fees_outstanding' => 0,
            'default_date' => $this->default_date ?? now(),
            'days_in_default' => 0,
            'status_id' => $status->id,
            'priority_id' => $priority->id,
            'notes' => "Recovery case created from defaulted loan #{$this->id}",
            'created_by' => auth()->id(),
        ]);
    }

    private function calculateTotalDebt(): float
    {
        $totalRepaid = $this->repayments->sum('amount') ?? 0;
        return max(0, ($this->amount + ($this->capitalized_interest ?? 0)) - $totalRepaid);
    }

    // ============ GRACE PERIOD METHODS ============

    public function isWithinGracePeriod(): bool
    {
        if (!$this->grace_period_end_date) {
            return false;
        }
        return Carbon::now()->lte($this->grace_period_end_date);
    }

    public function getRemainingGraceDays(): int
    {
        if (!$this->grace_period_end_date) {
            return 0;
        }
        $remaining = Carbon::now()->diffInDays($this->grace_period_end_date, false);
        return max(0, (int) $remaining);
    }

    public function useGraceDays(int $daysUsed): bool
    {
        if ($this->grace_days_balance < $daysUsed) {
            return false;
        }
        
        $this->grace_days_used += $daysUsed;
        $this->grace_days_balance -= $daysUsed;
        $this->save();
        
        return true;
    }

    public function applyGracePeriod(): void
    {
        $graceDays = $this->loanType->grace_period_days ?? 0;
        if ($graceDays > 0) {
            $this->grace_period_days = $graceDays;
            $this->grace_period_end_date = Carbon::now()->addDays($graceDays);
            $this->save();
        }
    }

    // ============ ROLLOVER METHODS ============

    public function rollover(array $options = []): self
    {
        $loanCalculator = app(\App\Services\LoanCalculator::class);

        $interestToCapitalize = $loanCalculator->calculateCapitalizedInterest($this);
        $newAmount = $this->amount + $interestToCapitalize;
        $newDueDate = $loanCalculator->calculateRolloverDueDate($this);
        
        // Store original amount if not set
        if (!$this->original_amount) {
            $this->original_amount = $this->amount;
        }
        
        // Update current loan
        $this->amount = $newAmount;
        $this->cycle += 1;
        $this->borrow_date = now();
        $this->due_date = $newDueDate;
        $this->calculated_due_date = $newDueDate;
        $this->status = self::STATUS_DISBURSED;
        $this->capitalized_interest += $interestToCapitalize;
        $this->applyGracePeriod();
        $this->save();

        // Create cycle record
        $this->createCycleRecord([
            'cycle_number' => $this->cycle,
            'previous_balance' => $newAmount - $interestToCapitalize,
            'interest_capitalized' => $interestToCapitalize,
            'new_balance' => $newAmount,
            'interest_rate' => $this->loanType->interest_rate ?? 0,
            'start_date' => now(),
            'due_date' => $newDueDate,
            'status' => 'active',
            'notes' => $options['notes'] ?? 'Loan rollover',
        ]);

        return $this;
    }

    public function createCycleRecord(array $data): LoanCycle
    {
        return LoanCycle::create([
            'loan_id' => $this->id,
            'cycle_number' => $data['cycle_number'] ?? $this->cycle,
            'previous_balance' => $data['previous_balance'] ?? $this->amount,
            'interest_capitalized' => $data['interest_capitalized'] ?? 0,
            'new_balance' => $data['new_balance'] ?? $this->amount,
            'interest_rate' => $data['interest_rate'] ?? 0,
            'start_date' => $data['start_date'] ?? now(),
            'due_date' => $data['due_date'] ?? $this->due_date,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function getCurrentCycle(): ?LoanCycle
    {
        return $this->cycles()->where('status', 'active')->first();
    }

    public function getRolloverStatement(): array
    {
        return $this->cycles->map(function ($cycle) {
            return [
                'cycle' => $cycle->cycle_number,
                'date' => $cycle->start_date->format('Y-m-d'),
                'previous_balance' => $cycle->previous_balance,
                'interest_capitalized' => $cycle->interest_capitalized,
                'new_balance' => $cycle->new_balance,
                'due_date' => $cycle->due_date->format('Y-m-d'),
                'status' => $cycle->status,
                'notes' => $cycle->notes,
            ];
        })->toArray();
    }

    // ============ FINANCIAL METHODS ============

    public function getTotalRepaymentsAttribute(): float
    {
        return $this->repayments->sum('amount') ?? 0;
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return max(0, $this->amount - $this->total_repayments);
    }

    public function isFullyRepaid(): bool
    {
        return $this->outstanding_balance <= 0;
    }

    // ============ GETTERS ============

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DISBURSED => 'Disbursed',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_DEFAULTED => 'Defaulted',
            self::STATUS_RECOVERY => 'In Recovery',
            self::STATUS_FORBEARANCE => 'Forbearance',
            self::STATUS_REPAID => 'Repaid',
            self::STATUS_WRITTEN_OFF => 'Written Off',
            self::STATUS_REJECTED => 'Rejected',
            default => ucfirst($this->status ?? 'Unknown')
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_DISBURSED => 'indigo',
            self::STATUS_ACTIVE => 'green',
            self::STATUS_OVERDUE => 'orange',
            self::STATUS_DEFAULTED => 'red',
            self::STATUS_RECOVERY => 'purple',
            self::STATUS_FORBEARANCE => 'gray',
            self::STATUS_REPAID => 'emerald',
            self::STATUS_WRITTEN_OFF => 'slate',
            self::STATUS_REJECTED => 'rose',
            default => 'gray'
        };
    }

    public function getCycleDisplayAttribute(): string
    {
        if ($this->cycle <= 1) {
            return 'Original Loan';
        }
        return "Cycle {$this->cycle} (Rollover)";
    }

    public function getGraceStatusAttribute(): string
    {
        if ($this->isWithinGracePeriod()) {
            return "Grace Period Active ({$this->getRemainingGraceDays()} days remaining)";
        }
        if ($this->grace_days_balance > 0) {
            return "{$this->grace_days_balance} grace days available";
        }
        return "No grace days available";
    }

    public function getRecoveryStage()
    {
        $daysOverdue = $this->days_overdue ?? $this->calculateDaysOverdue();
        $period = $this->getPeriodInDays();

        if ($daysOverdue <= 0) {
            return 'current';
        }

        $ratio = $daysOverdue / max(1, $period);

        if ($ratio <= 0.5) {
            return 'early_overdue';
        } elseif ($ratio <= 1) {
            return 'overdue';
        } elseif ($ratio <= 2) {
            return 'serious_overdue';
        } else {
            return 'npl';
        }
    }

    public function getRecoveryStageLabel(): string
    {
        return match($this->getRecoveryStage()) {
            'current' => 'Current',
            'early_overdue' => 'Early Overdue',
            'overdue' => 'Overdue',
            'serious_overdue' => 'Seriously Overdue',
            'npl' => 'Non-Performing (NPL)',
            default => 'Unknown',
        };
    }

    public function getRecoveryStageColor(): string
    {
        return match($this->getRecoveryStage()) {
            'current' => 'green',
            'early_overdue' => 'yellow',
            'overdue' => 'orange',
            'serious_overdue' => 'orange',
            'npl' => 'red',
            default => 'gray',
        };
    }

    // ============ SCOPES ============

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::STATUS_ACTIVE_LIST);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopeDefaulted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DEFAULTED);
    }

    public function scopeNonPerforming(Builder $query): Builder
    {
        return $query->where('is_non_performing', true);
    }

    public function scopeRepaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REPAID);
    }

    /**
     * Calculate the total due amount (principal + interest - repayments)
     */
    public function calculateTotalDue(): float
    {
        $interest = 0;
        
        // Calculate interest based on loan type
        if ($this->loanType) {
            $interest = ($this->loanType->interest_rate / 100) * $this->amount;
        }
        
        // Add capitalized interest if any
        $interest += $this->capitalized_interest ?? 0;
        
        // Total due = principal + interest
        $totalDue = $this->amount + $interest;
        
        // Subtract any repayments already made
        $totalRepaid = $this->repayments->sum('amount') ?? 0;
        
        return max(0, $totalDue - $totalRepaid);
    }

    /**
     * Calculate total repaid amount
     */
    public function totalRepaid(): float
    {
        return $this->repayments->sum('amount') ?? 0;
    }

    /**
     * Calculate total penalties
     */
    public function calculatePenalties(): float
    {
        $penaltyAmount = 0;
        
        // If loan is overdue, calculate penalties
        $dueDate = $this->getDueDate();
        if ($dueDate && Carbon::now()->gt($dueDate)) {
            $daysOverdue = Carbon::now()->diffInDays($dueDate);
            
            // Get penalty rate from loan type
            $penaltyRate = $this->loanType->penalty_rate ?? 0.5; // Default 0.5%
            
            // Simple penalty calculation: penalty rate * overdue days * outstanding balance
            $outstanding = $this->getOutstandingBalanceAttribute();
            $penaltyAmount = ($penaltyRate / 100) * $daysOverdue * $outstanding;
            
            // Cap penalty at 100% of outstanding balance
            $penaltyAmount = min($penaltyAmount, $outstanding);
        }
        
        return $penaltyAmount;
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdue(): int
    {
        $dueDate = $this->getDueDate();
        if (!$dueDate || Carbon::now()->lte($dueDate)) {
            return 0;
        }
        return (int) Carbon::now()->diffInDays($dueDate);
    }

    /**
     * Calculate the due amount including penalties
     */
    public function getTotalDueWithPenalties(): float
    {
        return $this->calculateTotalDue() + $this->calculatePenalties();
    }

    // ============ BOOT ============

    protected static function boot()
    {
        parent::boot();

        // REMOVED: static::retrieved event - DO NOT update on read
        // Models should NEVER update themselves just because they were fetched
    }
}