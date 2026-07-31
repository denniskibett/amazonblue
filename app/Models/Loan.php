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

    public function repaymentOverflows()
    {
        return $this->hasMany(RepaymentOverflow::class, 'from_loan_id');
    }

    public function repaymentOverflowsTo()
    {
        return $this->hasMany(RepaymentOverflow::class, 'to_loan_id');
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }

    public function recoveryCases()
    {
        return $this->hasMany(DebtRecoveryCase::class);
    }

    public function cycles()
    {
        return $this->hasMany(LoanCycle::class)->orderBy('cycle_number', 'desc');
    }

    // ============ STATUS CHECK METHODS ============
    
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_OVERDUE,
            self::STATUS_DISBURSED,
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE || 
               ($this->due_date && Carbon::now()->gt($this->due_date) && 
                !in_array($this->status, [self::STATUS_REPAID, self::STATUS_WRITTEN_OFF]));
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
        return !$this->isOverdue() && !$this->isDefaulted() && !$this->isInRecovery() && !$this->isInForbearance();
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

    public function calculateGraceDaysEarned(): int
    {
        if (!$this->due_date) {
            return 0;
        }

        $paidDate = $this->repayments()->latest('repayment_date')->first();
        if (!$paidDate) {
            return 0;
        }

        $paidDate = Carbon::parse($paidDate->repayment_date);
        $dueDate = Carbon::parse($this->due_date);

        if ($paidDate->lt($dueDate)) {
            return (int) $dueDate->diffInDays($paidDate);
        }

        return 0;
    }

    public function earnGraceDays(): void
    {
        $earned = $this->calculateGraceDaysEarned();
        if ($earned > 0) {
            $this->grace_days_earned += $earned;
            $this->grace_days_balance += $earned;
            $this->save();
        }
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

    public function rollover(array $options = [], $loanCalculator = null): self
    {
        // If calculator not provided, create a new instance
        if (!$loanCalculator) {
            $loanCalculator = app(\App\Services\LoanCalculator::class);
        }

        $interestToCapitalize = $loanCalculator->calculateCapitalizedInterest($this);
        $newAmount = $this->amount + $interestToCapitalize;
        $newDueDate = $loanCalculator->calculateRolloverDueDate($this);
        
        $this->createCycleRecord([
            'cycle_number' => $this->cycle + 1,
            'previous_balance' => $this->amount,
            'interest_capitalized' => $interestToCapitalize,
            'new_balance' => $newAmount,
            'interest_rate' => $this->loanType->interest_rate ?? 0,
            'start_date' => now(),
            'due_date' => $newDueDate,
            'status' => 'active',
            'notes' => $options['notes'] ?? 'Loan rollover',
        ]);
        
        if (!$this->original_amount) {
            $this->original_amount = $this->amount;
        }
        
        $this->amount = $newAmount;
        $this->cycle += 1;
        $this->borrow_date = now();
        $this->due_date = $newDueDate;
        $this->calculated_due_date = $newDueDate;
        $this->status = self::STATUS_DISBURSED;
        $this->capitalized_interest += $interestToCapitalize;
        $this->applyGracePeriod();
        $this->save();
        
        return $this;
    }

    public function getRolloverStatement(): array
    {
        $statement = [];
        $cycles = $this->cycles()->orderBy('cycle_number', 'asc')->get();
        
        foreach ($cycles as $cycle) {
            $statement[] = [
                'cycle' => $cycle->cycle_number,
                'date' => $cycle->start_date->format('Y-m-d'),
                'previous_balance' => $cycle->previous_balance,
                'interest_capitalized' => $cycle->interest_capitalized,
                'new_balance' => $cycle->new_balance,
                'due_date' => $cycle->due_date->format('Y-m-d'),
                'status' => $cycle->status,
                'notes' => $cycle->notes,
            ];
        }
        
        return $statement;
    }

    public function createCycleRecord(array $data): LoanCycle
    {
        return LoanCycle::create([
            'loan_id' => $this->id,
            'cycle_number' => $data['cycle_number'] ?? $this->cycle + 1,
            'previous_balance' => $data['previous_balance'] ?? $this->amount,
            'interest_capitalized' => $data['interest_capitalized'] ?? 0,
            'new_balance' => $data['new_balance'] ?? $this->amount,
            'interest_rate' => $data['interest_rate'] ?? $this->loanType->interest_rate ?? 0,
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

    public function completeCurrentCycle(string $status = 'completed'): void
    {
        $cycle = $this->getCurrentCycle();
        if ($cycle) {
            $cycle->update(['status' => $status]);
        }
    }

    // ============ NPL METHODS ============

    public function calculateDueDate()
    {
        if (!$this->loanType || !$this->borrow_date) {
            return null;
        }

        $borrowDate = Carbon::parse($this->borrow_date);
        $period = $this->loanType->period;
        $unit = $this->loanType->unit;

        $dueDate = $borrowDate->copy();
        switch ($unit) {
            case 'days': $dueDate->addDays($period); break;
            case 'weeks': $dueDate->addWeeks($period); break;
            case 'months': $dueDate->addMonths($period); break;
            case 'years': $dueDate->addYears($period); break;
            default: $dueDate->addDays($period); break;
        }

        return $dueDate;
    }

    public function calculateDaysOverdue()
    {
        if (!$this->calculated_due_date) {
            $this->calculated_due_date = $this->calculateDueDate();
            $this->save();
        }

        if (!$this->calculated_due_date) {
            return 0;
        }

        $dueDate = Carbon::parse($this->calculated_due_date);
        
        if (now()->lt($dueDate)) {
            return 0;
        }

        return now()->diffInDays($dueDate);
    }

    public function isNonPerformingLoan(): bool
    {
        return (bool) $this->is_non_performing;
    }

    public function wouldBeNonPerforming(): bool
    {
        if (!$this->loanType) {
            return false;
        }

        $daysOverdue = $this->calculateDaysOverdue();
        $period = $this->loanType->period;
        $nplThreshold = $period * 2;

        return $daysOverdue > $nplThreshold;
    }

    public function getNplThreshold()
    {
        if (!$this->loanType) {
            return 0;
        }
        return $this->loanType->period * 2;
    }

    public function updateNplStatus()
    {
        $daysOverdue = $this->calculateDaysOverdue();
        $threshold = $this->getNplThreshold();
        $wouldBeNpl = $daysOverdue > $threshold;

        $this->days_overdue = $daysOverdue;
        $this->npl_trigger_threshold = $threshold;
        $this->last_overdue_check = now();

        if ($wouldBeNpl && !$this->is_non_performing) {
            $this->is_non_performing = true;
            $this->default_date = now();
            $this->default_triggered = true;
            if ($this->status !== self::STATUS_DEFAULTED) {
                $this->status = self::STATUS_DEFAULTED;
            }
        } elseif (!$wouldBeNpl && $this->is_non_performing) {
            $this->is_non_performing = false;
        }

        if ($daysOverdue > 0 && !$wouldBeNpl && $this->status !== self::STATUS_OVERDUE) {
            $this->status = self::STATUS_OVERDUE;
        }

        if ($daysOverdue <= 0 && in_array($this->status, [self::STATUS_OVERDUE, self::STATUS_DEFAULTED])) {
            if (!$this->isFullyRepaid()) {
                $this->status = self::STATUS_DISBURSED;
            }
        }

        $this->save();
        return $this;
    }

    public function getRecoveryStage()
    {
        $daysOverdue = $this->days_overdue ?? $this->calculateDaysOverdue();
        $period = $this->loanType->period ?? 0;

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

    public function getRecoveryStageLabel()
    {
        $stages = [
            'current' => 'Current',
            'early_overdue' => 'Early Overdue (1-50% of period)',
            'overdue' => 'Overdue (50-100% of period)',
            'serious_overdue' => 'Seriously Overdue (100-200% of period)',
            'npl' => 'Non-Performing (NPL)',
        ];

        return $stages[$this->getRecoveryStage()] ?? 'Unknown';
    }

    public function getRecoveryStageColor()
    {
        $colors = [
            'current' => 'green',
            'early_overdue' => 'yellow',
            'overdue' => 'orange',
            'serious_overdue' => 'orange',
            'npl' => 'red',
        ];

        return $colors[$this->getRecoveryStage()] ?? 'gray';
    }

    public function getNplBadgeClass()
    {
        if ($this->is_non_performing) {
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        } elseif ($this->isOverdue()) {
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        }
        return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
    }

    public function getNplBadgeText()
    {
        if ($this->is_non_performing) {
            return 'NPL';
        } elseif ($this->isOverdue()) {
            return 'Overdue (' . $this->days_overdue . ' days)';
        }
        return 'Current';
    }

    // ============ DEFAULT MANAGEMENT ============

    public function checkOverdueStatus(): void
    {
        if (!$this->due_date) {
            return;
        }

        if (in_array($this->status, [
            self::STATUS_RECOVERY, 
            self::STATUS_FORBEARANCE, 
            self::STATUS_REPAID, 
            self::STATUS_WRITTEN_OFF
        ])) {
            return;
        }

        $daysOverdue = Carbon::now()->diffInDays($this->due_date, false);
        
        if ($daysOverdue <= 0) {
            if ($this->status === self::STATUS_OVERDUE) {
                $this->status = self::STATUS_ACTIVE;
                $this->save();
            }
            return;
        }

        $this->days_overdue = (int) $daysOverdue;
        $this->last_overdue_check = now();

        if ($this->grace_days_balance > 0 && $this->grace_days_balance >= $daysOverdue) {
            $this->useGraceDays((int) $daysOverdue);
            $this->status = self::STATUS_ACTIVE;
            $this->save();
            return;
        }

        $threshold = $this->loanType->default_threshold_days ?? 30;
        
        if ($daysOverdue >= $threshold && !$this->default_triggered) {
            $this->triggerDefault("Loan overdue for {$daysOverdue} days");
            $this->save();
            return;
        }

        if ($this->status !== self::STATUS_OVERDUE) {
            $this->status = self::STATUS_OVERDUE;
            $this->save();
        }
    }

    public function triggerDefault(string $reason = null): void
    {
        if (!$this->default_triggered) {
            $this->status = self::STATUS_DEFAULTED;
            $this->default_triggered = true;
            $this->default_triggered_at = now();
            $this->default_date = now();
            $this->is_non_performing = true;
            $this->recovery_notes = ($this->recovery_notes ? $this->recovery_notes . "\n" : '') . 
                                   "Default triggered: {$reason} on " . now()->format('Y-m-d H:i');
            $this->save();
            $this->createRecoveryCase();
        }
    }

    public function startRecovery(): void
    {
        $this->status = self::STATUS_RECOVERY;
        $this->recovery_started_at = now();
        $this->save();
    }

    public function grantForbearance(int $days, string $reason = null): void
    {
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
        return $this->isInForbearance() && 
               $this->forbearance_until && 
               Carbon::now()->lte($this->forbearance_until);
    }

    // ============ RECOVERY CASE CREATION ============

    private function createRecoveryCase(): void
    {
        $existingCase = DebtRecoveryCase::where('loan_id', $this->id)->first();
        
        if ($existingCase) {
            return;
        }

        $status = RecoveryStatus::where('slug', 'open')->first();
        $priority = RecoveryPriority::where('slug', 'high')->first();

        if (!$status || !$priority) {
            return;
        }

        $totalDebt = $this->calculateTotalDebt();

        DebtRecoveryCase::create([
            'user_id' => $this->user_id,
            'loan_id' => $this->id,
            'case_number' => 'DR-' . now()->format('Y') . '-' . str_pad(DebtRecoveryCase::whereYear('created_at', now()->year)->count() + 1, 4, '0', STR_PAD_LEFT),
            'total_debt_amount' => $totalDebt,
            'principal_outstanding' => $this->amount,
            'interest_outstanding' => $this->capitalized_interest,
            'penalty_outstanding' => 0,
            'fees_outstanding' => 0,
            'default_date' => $this->default_date ?? now(),
            'days_in_default' => $this->days_in_default ?? 0,
            'status_id' => $status->id,
            'priority_id' => $priority->id,
            'notes' => "Recovery case created from defaulted loan #{$this->id}",
            'created_by' => auth()->id(),
        ]);
    }

    private function calculateTotalDebt(): float
    {
        $totalRepaid = $this->repayments->sum('amount') ?? 0;
        $totalPenalty = 0;
        
        if ($this->default_triggered && $this->default_date) {
            $daysInDefault = Carbon::parse($this->default_date)->diffInDays(now());
            $penaltyRate = $this->loanType->penalty_rate ?? 0;
            $totalPenalty = ($penaltyRate / 100) * $this->amount * ($daysInDefault / 30);
        }
        
        return max(0, ($this->amount + $this->capitalized_interest + $totalPenalty) - $totalRepaid);
    }

    // ============ FINANCIAL METHODS ============

    public function calculateTotalDue()
    {
        $principal = $this->amount;
        $interest = ($this->loanType->interest_rate / 100) * $principal;
        $penalties = $this->calculatePenalties();
        
        return $principal + $interest + $penalties;
    }
    
    public function calculatePenalties()
    {
        if (!$this->disbursements()->exists()) {
            return 0;
        }
    
        $disbursementDate = $this->disbursements->first()->date ?? $this->borrow_date;
        $dueDate = $this->calculateDueDate();
        
        if (!$dueDate || now()->lte($dueDate)) {
            return 0;
        }
    
        $outstandingAtDueDate = $this->calculateOutstandingAtDueDate($dueDate);
        $daysLate = now()->diffInDays($dueDate);
        $penaltyRate = $this->loanType->penalty_rate / 100;
        
        return $outstandingAtDueDate * $penaltyRate * $daysLate;
    }
    
    protected function calculateOutstandingAtDueDate($dueDate)
    {
        $principal = $this->amount;
        $interest = ($this->loanType->interest_rate / 100) * $principal;
        $principalPlusInterest = $principal + $interest;
        
        $totalRepaymentsBeforeDue = $this->repayments()
            ->whereDate('repayment_date', '<=', $dueDate)
            ->sum('amount');
        
        return max($principalPlusInterest - $totalRepaymentsBeforeDue, 0);
    }
    
    public function getTotalRepaymentsAttribute()
    {
        return $this->repayments->sum('amount');
    }
    
    public function getOutstandingBalanceAttribute()
    {
        return $this->calculateTotalDue() - $this->total_repayments;
    }

    public function getBalanceAttribute()
    {
        return $this->calculateTotalDue() - $this->repayments->sum('amount');
    }

    public function updateStatusIfNeeded()
    {
        if ($this->balance <= 0 && $this->status !== self::STATUS_REPAID) {
            $this->update(['status' => self::STATUS_REPAID]);
        }
    }

    public function isFullyRepaid()
    {
        return $this->balance <= 0;
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

    public function getTotalCapitalizedInterestAttribute(): float
    {
        return $this->cycles()->sum('interest_capitalized');
    }

    public function getTotalRolloverAmountAttribute(): float
    {
        return $this->amount - ($this->original_amount ?? $this->amount);
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

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeRepaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REPAID);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REPAID);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_DISBURSED, self::STATUS_ACTIVE]);
    }

    public function scopeDisbursed($query)
    {
        return $query->where('status', self::STATUS_DISBURSED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopeNonPerforming($query)
    {
        return $query->where('is_non_performing', true);
    }

    public function scopeDefaulted($query)
    {
        return $query->where('status', self::STATUS_DEFAULTED);
    }

    public function scopeInRecovery($query)
    {
        return $query->where('status', self::STATUS_RECOVERY);
    }

    public function scopeInForbearance($query)
    {
        return $query->where('status', self::STATUS_FORBEARANCE);
    }

    // ============ BOOT ============

    public static function boot()
    {
        parent::boot();
        
        static::retrieved(function ($loan) {
            if (in_array($loan->status, ['active', 'disbursed', 'overdue'])) {
                $loan->checkOverdueStatus();
            }
        });
    }
}