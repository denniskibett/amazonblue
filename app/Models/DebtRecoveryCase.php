<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtRecoveryCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'loan_id',
        'case_number',
        'total_debt_amount',
        'principal_outstanding',
        'interest_outstanding',
        'penalty_outstanding',
        'fees_outstanding',
        'default_date',
        'days_in_default',
        'status_id',
        'priority_id',
        'assigned_to',
        'last_contact_date',
        'next_action_date',
        'recovery_strategy',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_debt_amount' => 'decimal:2',
        'principal_outstanding' => 'decimal:2',
        'interest_outstanding' => 'decimal:2',
        'penalty_outstanding' => 'decimal:2',
        'fees_outstanding' => 'decimal:2',
        'default_date' => 'date',
        'last_contact_date' => 'date',
        'next_action_date' => 'date',
        'days_in_default' => 'integer',
    ];

    protected $appends = [
        'status_name',
        'priority_name',
        'formatted_total_debt',
        'recovery_progress',
        'remaining_balance',
        'current_phase',
        'phase_label',
        'phase_color',
        'phase_progress',
        'days_in_phase',
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function debtor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function status()
    {
        return $this->belongsTo(RecoveryStatus::class);
    }

    public function priority()
    {
        return $this->belongsTo(RecoveryPriority::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function actions()
    {
        return $this->hasMany(RecoveryAction::class, 'case_id');
    }

    public function paymentPlans()
    {
        return $this->hasMany(RecoveryPaymentPlan::class, 'case_id');
    }

    public function activePaymentPlan()
    {
        return $this->hasOne(RecoveryPaymentPlan::class, 'case_id')
            ->whereIn('status', ['proposed', 'accepted']);
    }

    public function documents()
    {
        return $this->hasMany(RecoveryDocument::class, 'case_id');
    }

    public function notes()
    {
        return $this->hasMany(RecoveryCaseNote::class, 'case_id');
    }

    public function legalProceedings()
    {
        return $this->hasMany(LegalProceeding::class, 'case_id');
    }

    public function legalDeadlines()
    {
        return $this->hasMany(LegalDeadline::class, 'case_id');
    }

    public function communications()
    {
        return $this->hasMany(Communication::class, 'case_id');
    }

    public function timelines()
    {
        return $this->hasMany(RecoveryTimeline::class, 'case_id');
    }

    public function tasks()
    {
        return $this->hasMany(RecoveryTask::class, 'case_id');
    }

    public function agencyAssignments()
    {
        return $this->hasMany(AgencyCaseAssignment::class, 'case_id');
    }

    public function skipTracingRecords()
    {
        return $this->hasMany(SkipTracing::class, 'case_id');
    }

    public function creditBureauReports()
    {
        return $this->hasMany(CreditBureauReport::class, 'case_id');
    }

    public function financialAssessments()
    {
        return $this->hasMany(FinancialAssessment::class, 'case_id');
    }

    // ============ ACCESSORS ============

    public function getStatusNameAttribute()
    {
        return $this->status ? $this->status->name : null;
    }

    public function getPriorityNameAttribute()
    {
        return $this->priority ? $this->priority->name : null;
    }

    public function getFormattedTotalDebtAttribute()
    {
        return 'KES ' . number_format($this->total_debt_amount, 2);
    }

    public function getRecoveryProgressAttribute()
    {
        return $this->calculateProgress();
    }

    public function getRemainingBalanceAttribute()
    {
        return max(0, $this->total_debt_amount - $this->getTotalRecovered());
    }

    // ============ PHASE ACCESSORS ============

    public function getCurrentPhaseAttribute()
    {
        return $this->getCurrentPhase();
    }

    public function getPhaseLabelAttribute()
    {
        return $this->getPhaseLabel();
    }

    public function getPhaseColorAttribute()
    {
        return $this->getPhaseColor();
    }

    public function getPhaseProgressAttribute()
    {
        return $this->getPhaseProgress();
    }

    public function getDaysInPhaseAttribute()
    {
        return $this->getDaysInPhase();
    }

    // ============ SCOPES ============

    public function scopeOpen($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->whereIn('slug', ['open', 'in_progress', 'negotiation', 'legal']);
        });
    }

    public function scopeClosed($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->whereIn('slug', ['recovered', 'written_off', 'closed']);
        });
    }

    public function scopeUrgent($query)
    {
        return $query->whereHas('priority', function ($q) {
            $q->where('slug', 'urgent');
        });
    }

    public function scopeOverdue($query, $days = 30)
    {
        return $query->where('days_in_default', '>=', $days)
            ->whereHas('status', function ($q) {
                $q->whereIn('slug', ['open', 'in_progress']);
            });
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByPhase($query, $phase)
    {
        $statusMap = [
            'initial_contact' => ['open'],
            'active_recovery' => ['in_progress'],
            'negotiation' => ['negotiation'],
            'legal_action' => ['legal'],
            'resolved' => ['recovered', 'written_off', 'closed'],
        ];

        $statuses = $statusMap[$phase] ?? [];
        if (empty($statuses)) {
            return $query;
        }

        return $query->whereHas('status', function ($q) use ($statuses) {
            $q->whereIn('slug', $statuses);
        });
    }

    // ============ HELPER METHODS ============

    public function getTotalRecovered()
    {
        return $this->actions()
            ->where('outcome', 'successful')
            ->sum('amount_collected');
    }

    public function getLastAction()
    {
        return $this->actions()->latest()->first();
    }

    public function getActivePaymentPlan()
    {
        return $this->paymentPlans()
            ->whereIn('status', ['proposed', 'accepted'])
            ->latest()
            ->first();
    }

    public function canBeClosed()
    {
        return $this->getRemainingBalance() <= 0 || 
               ($this->status && $this->status->slug === 'recovered');
    }

    public function generateCaseNumber()
    {
        $year = now()->format('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'DR-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function getTimelineEvents()
    {
        $events = collect();

        // Add actions
        foreach ($this->actions as $action) {
            $events->push([
                'date' => $action->action_date,
                'type' => 'action',
                'description' => $action->actionType->name ?? 'Action',
                'data' => $action,
            ]);
        }

        // Add communications
        foreach ($this->communications as $comm) {
            $events->push([
                'date' => $comm->sent_at ?? $comm->created_at,
                'type' => 'communication',
                'description' => $comm->communicationType->name ?? 'Communication',
                'data' => $comm,
            ]);
        }

        // Add notes
        foreach ($this->notes as $note) {
            $events->push([
                'date' => $note->created_at,
                'type' => 'note',
                'description' => $note->note_type,
                'data' => $note,
            ]);
        }

        // Sort by date
        return $events->sortBy('date')->values();
    }

    // ============ PHASE METHODS ============

    /**
     * Get the current phase of the recovery process
     */
    public function getCurrentPhase()
    {
        if (!$this->status) {
            return 'unknown';
        }

        $phaseMap = [
            'open' => 'initial_contact',
            'in_progress' => 'active_recovery',
            'negotiation' => 'negotiation',
            'legal' => 'legal_action',
            'recovered' => 'resolved',
            'written_off' => 'resolved',
            'closed' => 'resolved',
        ];

        return $phaseMap[$this->status->slug] ?? 'unknown';
    }

    /**
     * Get the phase label for UI display
     */
    public function getPhaseLabel()
    {
        $labels = [
            'initial_contact' => 'Initial Contact',
            'active_recovery' => 'Active Recovery',
            'negotiation' => 'Negotiation',
            'legal_action' => 'Legal Action',
            'resolved' => 'Resolved',
            'unknown' => 'Unknown',
        ];

        $phase = $this->getCurrentPhase();
        return $labels[$phase] ?? 'Unknown';
    }

    /**
     * Get the phase color for UI (Tailwind color name)
     */
    public function getPhaseColor()
    {
        $colors = [
            'initial_contact' => 'blue',
            'active_recovery' => 'yellow',
            'negotiation' => 'orange',
            'legal_action' => 'red',
            'resolved' => 'green',
            'unknown' => 'gray',
        ];

        $phase = $this->getCurrentPhase();
        return $colors[$phase] ?? 'gray';
    }

    /**
     * Get the phase progress percentage (0-100)
     */
    public function getPhaseProgress()
    {
        $phaseProgress = [
            'initial_contact' => 25,
            'active_recovery' => 50,
            'negotiation' => 75,
            'legal_action' => 90,
            'resolved' => 100,
            'unknown' => 0,
        ];

        $phase = $this->getCurrentPhase();
        return $phaseProgress[$phase] ?? 0;
    }

    /**
     * Get the number of days the case has been in the current phase
     */
    public function getDaysInPhase()
    {
        $phaseStartDates = [
            'initial_contact' => $this->created_at,
            'active_recovery' => $this->getPhaseStartDate('in_progress'),
            'negotiation' => $this->getPhaseStartDate('negotiation'),
            'legal_action' => $this->getPhaseStartDate('legal'),
            'resolved' => $this->updated_at,
        ];

        $phase = $this->getCurrentPhase();
        $startDate = $phaseStartDates[$phase] ?? $this->created_at;

        if (!$startDate) {
            return 0;
        }

        return $startDate->diffInDays(now());
    }

    /**
     * Get the date when a specific phase started
     */
    private function getPhaseStartDate($statusSlug)
    {
        // Check if there's a status change log
        $statusLog = $this->notes()
            ->where('note_type', 'alert')
            ->where('note', 'like', "%Status changed from%to '{$statusSlug}'%")
            ->first();

        if ($statusLog) {
            return $statusLog->created_at;
        }

        // Check if the current status matches
        if ($this->status && $this->status->slug === $statusSlug) {
            // Try to find the first action after case creation in this status
            $firstAction = $this->actions()
                ->where('created_at', '>=', $this->created_at)
                ->orderBy('created_at')
                ->first();

            return $firstAction ? $firstAction->created_at : $this->created_at;
        }

        return null;
    }

    /**
     * Calculate overall recovery progress
     */
    private function calculateProgress()
    {
        // Progress based on status
        $statusProgress = $this->getPhaseProgress();

        // If we have a payment plan, add additional progress
        $planProgress = 0;
        if ($activePlan = $this->getActivePaymentPlan()) {
            $planProgress = $activePlan->progress_percentage ?? 0;
            // Weight payment plan progress (40% of total)
            $planProgress = $planProgress * 0.4;
        }

        // If we have recovered amount, add that progress
        $recoveredProgress = 0;
        if ($this->total_debt_amount > 0) {
            $recoveredProgress = ($this->getTotalRecovered() / $this->total_debt_amount) * 100;
            // Weight recovered amount (30% of total)
            $recoveredProgress = $recoveredProgress * 0.3;
        }

        // Weight status progress (30% of total)
        $statusWeighted = $statusProgress * 0.3;

        $totalProgress = $statusWeighted + $planProgress + $recoveredProgress;

        return min(100, round($totalProgress));
    }

    /**
     * Get the phase description for the current phase
     */
    public function getPhaseDescription()
    {
        $descriptions = [
            'initial_contact' => 'Initial contact has been established with the debtor. Notifications sent and basic information gathered.',
            'active_recovery' => 'Active recovery efforts are underway. This includes phone calls, field visits, skip tracing, and contacting references.',
            'negotiation' => 'Negotiating a payment plan or settlement arrangement with the debtor. Financial assessment in progress.',
            'legal_action' => 'Legal proceedings have been initiated. Court filings, hearings, and legal notices are being processed.',
            'resolved' => 'Case has been resolved. The debt has either been fully recovered or written off.',
            'unknown' => 'Case status is unknown or not yet determined.',
        ];

        $phase = $this->getCurrentPhase();
        return $descriptions[$phase] ?? 'No description available.';
    }

    /**
     * Get all phase steps with their status for a timeline display
     */
    public function getPhaseTimeline()
    {
        $phases = [
            'initial_contact' => 'Initial Contact',
            'active_recovery' => 'Active Recovery',
            'negotiation' => 'Negotiation',
            'legal_action' => 'Legal Action',
            'resolved' => 'Resolution'
        ];

        $currentPhase = $this->getCurrentPhase();
        $foundCurrent = false;
        $timeline = [];

        foreach ($phases as $phaseKey => $phaseName) {
            $isCurrent = ($phaseKey === $currentPhase);
            $isCompleted = !$isCurrent && !$foundCurrent;
            $isUpcoming = !$isCurrent && $foundCurrent;

            $timeline[] = [
                'key' => $phaseKey,
                'name' => $phaseName,
                'status' => $isCompleted ? 'completed' : ($isCurrent ? 'current' : 'upcoming'),
                'date' => $this->getPhaseDate($phaseKey),
                'description' => $this->getPhaseDescriptionByKey($phaseKey),
                'is_completed' => $isCompleted,
                'is_current' => $isCurrent,
                'is_upcoming' => $isUpcoming,
            ];

            if ($isCurrent) {
                $foundCurrent = true;
            }
        }

        return $timeline;
    }

    /**
     * Get the date for a specific phase
     */
    private function getPhaseDate($phaseKey)
    {
        $dates = [
            'initial_contact' => $this->created_at,
            'active_recovery' => $this->getPhaseStartDate('in_progress'),
            'negotiation' => $this->getPhaseStartDate('negotiation'),
            'legal_action' => $this->getPhaseStartDate('legal'),
            'resolved' => $this->updated_at,
        ];

        return $dates[$phaseKey] ?? null;
    }

    /**
     * Get description for a specific phase key
     */
    private function getPhaseDescriptionByKey($phaseKey)
    {
        $descriptions = [
            'initial_contact' => 'Initial contact established. Notifications sent.',
            'active_recovery' => 'Active recovery efforts underway.',
            'negotiation' => 'Negotiating payment plan.',
            'legal_action' => 'Legal proceedings initiated.',
            'resolved' => 'Case resolved.',
        ];

        return $descriptions[$phaseKey] ?? '';
    }

    /**
     * Check if the case is in a specific phase
     */
    public function isInPhase($phaseKey)
    {
        return $this->getCurrentPhase() === $phaseKey;
    }

    /**
     * Check if the case has reached a specific phase
     */
    public function hasReachedPhase($phaseKey)
    {
        $phaseOrder = ['initial_contact', 'active_recovery', 'negotiation', 'legal_action', 'resolved'];
        $currentIndex = array_search($this->getCurrentPhase(), $phaseOrder);
        $targetIndex = array_search($phaseKey, $phaseOrder);

        return $currentIndex !== false && $targetIndex !== false && $currentIndex >= $targetIndex;
    }

    /**
     * Get recovery statistics summary
     */
    public function getRecoveryStats()
    {
        $totalActions = $this->actions()->count();
        $successfulActions = $this->actions()->where('outcome', 'successful')->count();
        $totalRecovered = $this->getTotalRecovered();
        $remaining = $this->getRemainingBalance();

        return [
            'total_debt' => $this->total_debt_amount,
            'recovered' => $totalRecovered,
            'remaining' => $remaining,
            'recovery_percentage' => $this->total_debt_amount > 0 
                ? round(($totalRecovered / $this->total_debt_amount) * 100, 2) 
                : 0,
            'total_actions' => $totalActions,
            'successful_actions' => $successfulActions,
            'success_rate' => $totalActions > 0 
                ? round(($successfulActions / $totalActions) * 100, 2) 
                : 0,
            'days_in_default' => $this->days_in_default,
            'current_phase' => $this->getCurrentPhase(),
            'phase_label' => $this->getPhaseLabel(),
            'phase_progress' => $this->getPhaseProgress(),
        ];
    }

    /**
     * Get the recovery status badge class for UI
     */
    public function getStatusBadgeClass()
    {
        $badges = [
            'open' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'in_progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            'negotiation' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
            'legal' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            'recovered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'written_off' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        ];

        if (!$this->status) {
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        }

        return $badges[$this->status->slug] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }

    /**
     * Get the priority badge class for UI
     */
    public function getPriorityBadgeClass()
    {
        $badges = [
            'low' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
            'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        ];

        if (!$this->priority) {
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        }

        return $badges[$this->priority->slug] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
}