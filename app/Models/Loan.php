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
        // New fields
        'guarantor_id',
        'guarantor_relationship', 
        'loan_officer_id',
        'consent',
        'consent_date',
        'signature',
        // NPL Fields
        'is_non_performing',
        'default_date',
        'days_overdue',
        'last_overdue_check',
        'default_triggered',
        'calculated_due_date',
        'npl_trigger_threshold',
    ];

    protected $dates = [
        'borrow_date',
        'consent_date',
        'default_date',
        'calculated_due_date',
        'last_overdue_check',
    ];

    protected $casts = [
        'borrow_date' => 'datetime',
        'consent_date' => 'datetime',
        'default_date' => 'date',
        'calculated_due_date' => 'date',
        'last_overdue_check' => 'datetime',
        'consent' => 'boolean',
        'broker_status' => 'boolean',
        'is_non_performing' => 'boolean',
        'default_triggered' => 'boolean',
        'amount' => 'decimal:2',
        'days_overdue' => 'integer',
        'npl_trigger_threshold' => 'integer',
    ];

    // Define constants for loan statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_DISBURSED = 'disbursed';
    const STATUS_ACTIVE = 'active';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REPAID = 'repaid';
    const STATUS_DEFAULTED = 'defaulted';

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

    // ============ NPL METHODS ============

    /**
     * Calculate the due date for this loan based on loan type
     */
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

    /**
     * Calculate days overdue
     */
    public function calculateDaysOverdue()
    {
        // Calculate due date if not already calculated
        if (!$this->calculated_due_date) {
            $this->calculated_due_date = $this->calculateDueDate();
            $this->save();
        }

        if (!$this->calculated_due_date) {
            return 0;
        }

        $dueDate = Carbon::parse($this->calculated_due_date);
        
        // If due date is in the future, not overdue
        if (now()->lt($dueDate)) {
            return 0;
        }

        return now()->diffInDays($dueDate);
    }

    /**
     * Check if loan is non-performing (NPL)
     * NPL = Overdue > 2 × period (more than double the loan period)
     */
    public function isNonPerforming()
    {
        if (!$this->loanType) {
            return false;
        }

        $daysOverdue = $this->calculateDaysOverdue();
        $period = $this->loanType->period;
        $nplThreshold = $period * 2; // More than double

        return $daysOverdue > $nplThreshold;
    }

    /**
     * Check if loan is overdue
     */
    public function isOverdue()
    {
        return $this->calculateDaysOverdue() > 0;
    }

    /**
     * Get the NPL threshold (2 × period)
     */
    public function getNplThreshold()
    {
        if (!$this->loanType) {
            return 0;
        }
        return $this->loanType->period * 2;
    }

    /**
     * Update NPL status
     */
    public function updateNplStatus()
    {
        $daysOverdue = $this->calculateDaysOverdue();
        $threshold = $this->getNplThreshold();
        $isNpl = $daysOverdue > $threshold;

        $this->days_overdue = $daysOverdue;
        $this->npl_trigger_threshold = $threshold;
        $this->last_overdue_check = now();

        if ($isNpl && !$this->is_non_performing) {
            // Loan has become NPL
            $this->is_non_performing = true;
            $this->default_date = now();
            $this->default_triggered = true;
            if ($this->status !== self::STATUS_DEFAULTED) {
                $this->status = self::STATUS_DEFAULTED;
            }
        } elseif (!$isNpl && $this->is_non_performing) {
            // Loan is no longer NPL (shouldn't happen often, but handle it)
            $this->is_non_performing = false;
        }

        // Update status if overdue but not NPL
        if ($daysOverdue > 0 && !$isNpl && $this->status !== self::STATUS_OVERDUE) {
            $this->status = self::STATUS_OVERDUE;
        }

        // If not overdue and status is overdue or defaulted, revert to disbursed
        if ($daysOverdue <= 0 && in_array($this->status, [self::STATUS_OVERDUE, self::STATUS_DEFAULTED])) {
            if (!$this->isFullyRepaid()) {
                $this->status = self::STATUS_DISBURSED;
            }
        }

        $this->save();
        return $this;
    }

    /**
     * Get the recovery stage based on overdue days
     */
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

    /**
     * Get human-readable recovery stage label
     */
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

    /**
     * Get the recovery stage color
     */
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

    /**
     * Get the NPL badge class for UI
     */
    public function getNplBadgeClass()
    {
        if ($this->is_non_performing) {
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        } elseif ($this->isOverdue()) {
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        }
        return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
    }

    /**
     * Get NPL badge text
     */
    public function getNplBadgeText()
    {
        if ($this->is_non_performing) {
            return 'NPL';
        } elseif ($this->isOverdue()) {
            return 'Overdue (' . $this->days_overdue . ' days)';
        }
        return 'Current';
    }

    // ============ EXISTING METHODS ============

    // Pivot table methods for modular data
    public function updateAgreementSection($sectionType, $data)
    {
        return $this->agreementSections()->updateOrCreate(
            ['section_type' => $sectionType],
            ['content' => json_encode($data), 'updated_at' => now()]
        );
    }

    public function getAgreementSection($sectionType)
    {
        $section = $this->agreementSections()->where('section_type', $sectionType)->first();
        return $section ? json_decode($section->content, true) : [];
    }

    public function updateRiskAssessment($assessmentData)
    {
        return $this->riskAssessments()->create([
            'character_score' => $assessmentData['character_score'] ?? null,
            'capacity_score' => $assessmentData['capacity_score'] ?? null,
            'capital_score' => $assessmentData['capital_score'] ?? null,
            'conditions_score' => $assessmentData['conditions_score'] ?? null,
            'overall_score' => $assessmentData['overall_score'] ?? null,
            'assessed_by' => auth()->id(),
            'assessment_notes' => $assessmentData['notes'] ?? null
        ]);
    }

    public function getLatestRiskAssessment()
    {
        return $this->riskAssessments()->latest()->first();
    }

    // Consent management
    public function giveConsent()
    {
        $this->update([
            'consent' => true,
            'consent_date' => now()
        ]);
    }

    // Guarantor management
    public function setGuarantor($guarantorId, $relationship)
    {
        $this->update([
            'guarantor_id' => $guarantorId,
            'guarantor_relationship' => $relationship
        ]);
    }

    public function getGuarantorInfo()
    {
        if (!$this->guarantor_id) return null;

        return [
            'guarantor' => $this->guarantor,
            'relationship' => $this->guarantor_relationship,
            'is_borrower' => $this->guarantor->borrower()->exists()
        ];
    }

    // 4Cs Assessment Methods
    public function assess4cs()
    {
        $borrower = $this->borrower;
        
        if (!$borrower) {
            return false;
        }

        $characterScore = $this->calculateCharacterScore();
        $capacityScore = $this->calculateCapacityScore();
        $capitalScore = $this->calculateCapitalScore();
        $conditionsScore = $this->calculateConditionsScore();
        
        $overallScore = round(($characterScore + $capacityScore + $capitalScore + $conditionsScore) / 4);

        return $this->updateRiskAssessment([
            'character_score' => $characterScore,
            'capacity_score' => $capacityScore,
            'capital_score' => $capitalScore,
            'conditions_score' => $conditionsScore,
            'overall_score' => $overallScore,
            'notes' => 'Automated 4Cs assessment'
        ]);
    }

    private function calculateCharacterScore()
    {
        $userLoans = $this->user->loans()->where('id', '!=', $this->id)->get();
        
        if ($userLoans->isEmpty()) return 70; // New customer
        
        $repaidLoans = $userLoans->where('status', self::STATUS_REPAID)->count();
        $totalLoans = $userLoans->count();
        $repaymentRate = $totalLoans > 0 ? ($repaidLoans / $totalLoans) * 100 : 0;
        
        if ($repaymentRate >= 95) return 100;
        if ($repaymentRate >= 80) return 80;
        if ($repaymentRate >= 60) return 60;
        return 40;
    }

    private function calculateCapacityScore()
    {
        $borrower = $this->borrower;
        if (!$borrower || !$borrower->net_salary || $borrower->net_salary <= 0) return 50;
        
        $score = 70; // Base score
        
        // Employment type bonus
        if ($borrower->income_type === 'employed') $score += 10;
        if ($borrower->income_type === 'self_employed') $score += 5;
        
        // Salary-based scoring
        if ($borrower->net_salary >= 100000) $score += 20;
        elseif ($borrower->net_salary >= 50000) $score += 10;
        elseif ($borrower->net_salary >= 25000) $score += 5;
        
        return min(100, $score);
    }

    private function calculateCapitalScore()
    {
        $score = 60; // Base score
        
        // Check if has guarantor (adds security)
        if ($this->guarantor_id) $score += 20;
        
        // Check if borrower has complete profile
        if ($this->user->hasCompletePersonalInfo()) $score += 10;
        
        return min(100, $score);
    }

    private function calculateConditionsScore()
    {
        $score = 80; // Base assuming stable conditions
        
        // Loan purpose considerations
        $businessPurposes = ['business', 'investment'];
        $emergencyPurposes = ['emergency', 'medical', 'education'];
        
        if (in_array(strtolower($this->reason), $businessPurposes)) {
            $score -= 10; // Higher risk for business purposes
        } elseif (in_array(strtolower($this->reason), $emergencyPurposes)) {
            $score += 5; // Lower risk for emergency purposes
        }
        
        return max(40, min(100, $score));
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

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
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

    // ============ FINANCIAL METHODS ============

    public function getInterestAttribute()
    {
        $borrower = $this->user->borrower;

        if (!$borrower || !$borrower->broker) {
            return 0;
        }

        return $borrower->client_type == 0
            ? $borrower->broker->interest_client
            : $borrower->broker->interest_broker;
    }

    public function getPenaltyAttribute()
    {
        $borrower = $this->user->borrower;

        if (!$borrower || !$borrower->broker) {
            return 0;
        }

        return $borrower->client_type == 0
            ? $borrower->broker->penalty_client
            : $borrower->broker->penalty_broker;
    }

    public function totalDue()
    {
        return $this->amount + ($this->amount * $this->interest_rate / 100);
    }

    public function totalRepaid()
    {
        return $this->repayments->sum('amount');
    }

    public function totalPenalty()
    {
        return $this->repayments->sum('penalty_amount');
    }

    public function repaymentPercentage()
    {
        $totalDue = $this->totalDue();
        if ($totalDue <= 0) return 100;
        
        return min(100, ($this->totalRepaid() / $totalDue) * 100);
    }

    public function totalInterest()
    {
        return $this->amount * $this->interest_rate / 100;
    }

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
}