<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

class Loan extends Model
{
    use HasFactory;

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
        'signature'
    ];

    protected $dates = [
        'borrow_date',
        'consent_date'
    ];

    protected $casts = [
        'borrow_date' => 'datetime',
        'consent_date' => 'datetime',
        'consent' => 'boolean',
        'amount' => 'decimal:2'
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

    // Relationships
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

    // Scopes
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
        return $query->where('status', 'disbursed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    // Existing financial methods remain the same
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
        $dueDate = $this->calculateDueDate($disbursementDate);
        
        if (now()->lte($dueDate)) {
            return 0;
        }
    
        $outstandingAtDueDate = $this->calculateOutstandingAtDueDate($dueDate);
        $daysLate = now()->diffInDays($dueDate);
        $penaltyRate = $this->loanType->penalty_rate / 100;
        
        return $outstandingAtDueDate * $penaltyRate * $daysLate;
    }
    
    protected function calculateDueDate($disbursementDate)
    {
        $period = $this->loanType->period;
        $unit = $this->loanType->unit;
        
        $dueDate = \Carbon\Carbon::parse($disbursementDate);
        
        switch ($unit) {
            case 'days':
                return $dueDate->addDays($period);
            case 'weeks':
                return $dueDate->addWeeks($period);
            case 'months':
                return $dueDate->addMonths($period);
            case 'years':
                return $dueDate->addYears($period);
            default:
                return $dueDate;
        }
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