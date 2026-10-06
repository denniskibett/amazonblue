<?php
// app/Models/Investment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'sector', 'sub_sector',
        'facility_type',
        'duration_days',
        'interest_rate',
        'country', 'region', 'city', 'address',
        'company_name', 'registration_number', 'incorporation_date', 'legal_structure',
        'ebitda_pre_investment', 'revenue_pre_investment', 'net_profit_pre_investment',
        'total_assets_pre_investment', 'total_liabilities_pre_investment',
        'current_value', 'expected_return', 'actual_return',
        'revenue_current', 'profit_current', 'valuation_current',
        'initial_amount', 'irr', 'payback_period_months', 'break_even_point',
        'purchase_date', 'maturity_date', 'exit_date',
        'risk_rating', 'risk_factors',
        'stakeholders',
        'pitch_deck_path', 'financial_model_path', 'due_diligence_path', 'legal_docs',
        'market_research', 'competitive_landscape', 'swot_analysis', 'key_assumptions',
        'notes',
        'status', 'stage', 'milestones',
        'total_funding_raised', 'funding_partners',
        'updates',
        'created_by', 'updated_by'
    ];

    protected $casts = [
        'ebitda_pre_investment' => 'decimal:2',
        'revenue_pre_investment' => 'decimal:2',
        'net_profit_pre_investment' => 'decimal:2',
        'total_assets_pre_investment' => 'decimal:2',
        'total_liabilities_pre_investment' => 'decimal:2',
        'current_value' => 'decimal:2',
        'expected_return' => 'decimal:2',
        'actual_return' => 'decimal:2',
        'revenue_current' => 'decimal:2',
        'profit_current' => 'decimal:2',
        'valuation_current' => 'decimal:2',
        'initial_amount' => 'decimal:2',
        'irr' => 'decimal:2',
        'break_even_point' => 'decimal:2',
        'total_funding_raised' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'duration_days' => 'integer',
        'risk_factors' => 'array',
        'stakeholders' => 'array',
        'legal_docs' => 'array',
        'swot_analysis' => 'array',
        'notes' => 'array',
        'milestones' => 'array',
        'funding_partners' => 'array',
        'updates' => 'array',
        'incorporation_date' => 'date',
        'purchase_date' => 'date',
        'maturity_date' => 'date',
        'exit_date' => 'date'
    ];

    // ============ RELATIONSHIPS ============

    public function disbursements()
    {
        return $this->hasMany(Disbursement::class);
    }

    public function repayments()
    {
        return $this->hasMany(Repayment::class);
    }

    public function partnerTransactions()
    {
        return $this->hasMany(PartnerTransaction::class);
    }

    /**
     * NEW: pivot rows — one investment can be funded by many partners.
     */
    public function fundings()
    {
        return $this->hasMany(InvestmentFunding::class);
    }

    /**
     * NEW: many-to-many with partners through investment_fundings.
     */
    public function fundedPartners()
    {
        return $this->belongsToMany(Partner::class, 'investment_fundings')
            ->withPivot([
                'id',
                'partner_transaction_id',
                'amount_committed',
                'amount_disbursed',
                'amount_returned',
                'status',
                'notes',
            ])
            ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============ FACILITY TYPE ============

    public function getIsShortTermAttribute(): bool
    {
        return $this->facility_type === 'short_term';
    }

    public function getIsLongTermAttribute(): bool
    {
        return $this->facility_type === 'long_term';
    }

    public function getFacilityTypeLabelAttribute(): string
    {
        return $this->facility_type === 'short_term'
            ? 'Short-Term Facility'
            : 'Long-Term Investment';
    }

    public function scopeShortTerm($query)
    {
        return $query->where('facility_type', 'short_term');
    }

    public function scopeLongTerm($query)
    {
        return $query->where('facility_type', 'long_term');
    }

    /**
     * Get the Partner models that are funding this investment
     * (either via the new pivot or the legacy funding_partners JSON).
     */
    public function fundingPartnerModels()
    {
        // Prefer the new pivot
        $pivotPartners = $this->fundedPartners()->get();
        if ($pivotPartners->isNotEmpty()) {
            return $pivotPartners;
        }

        // Fallback to legacy JSON
        $partnerIds = collect($this->funding_partners ?? [])
            ->pluck('partner_id')
            ->filter()
            ->unique()
            ->values();

        if ($partnerIds->isEmpty()) {
            return Partner::whereRaw('1 = 0')->get();
        }

        return Partner::whereIn('id', $partnerIds)->get();
    }

    // ============ AGGREGATE ACCESSORS ============

    public function getTotalFundingAttribute()
    {
        // Prefer disbursements (real money out)
        return $this->disbursements()->sum('amount');
    }

    /**
     * Total committed by all partners.
     */
    public function getCommittedAmountAttribute(): float
    {
        // 1. Prefer pivot
        $pivotSum = (float) $this->fundings()->sum('amount_committed');
        if ($pivotSum > 0) {
            return $pivotSum;
        }

        // 2. Fallback to legacy JSON
        $legacySum = (float) collect($this->funding_partners ?? [])->sum('amount');
        if ($legacySum > 0) {
            return $legacySum;
        }

        // 3. Ultimate fallback: initial amount
        return (float) $this->initial_amount;
    }

    /**
     * Total disbursed against this investment.
     */
    public function getDisbursedAmountAttribute(): float
    {
        return (float) $this->disbursements()->sum('amount');
    }

    /**
     * Remaining amount that can still be disbursed from the facility.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->committed_amount - $this->disbursed_amount);
    }

    public function getTotalReturnsAttribute()
    {
        return $this->repayments()->sum('amount');
    }

    public function getNetReturnAttribute()
    {
        return $this->total_returns - $this->total_funding;
    }

    // ============ OTHER ACCESSORS ============

    public function getReturnPercentageAttribute()
    {
        if ($this->initial_amount <= 0) return 0;
        return (($this->current_value - $this->initial_amount) / $this->initial_amount) * 100;
    }

    public function getProfitLossAttribute()
    {
        return $this->current_value - $this->initial_amount;
    }

    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'pipeline' => 'Pipeline',
            'due_diligence' => 'Due Diligence',
            'active' => 'Active',
            'matured' => 'Matured',
            'liquidated' => 'Liquidated',
            'write_off' => 'Write Off'
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getStakeholdersListAttribute()
    {
        if (!$this->stakeholders) return [];
        return $this->stakeholders;
    }

    public function getPartnersListAttribute()
    {
        if (!$this->stakeholders || !isset($this->stakeholders['partners'])) return [];
        return collect($this->stakeholders['partners'])->map(function($partner) {
            $partnerModel = Partner::find($partner['partner_id'] ?? null);
            return [
                'name' => $partnerModel->name ?? 'Unknown',
                'amount' => $partner['amount'] ?? 0,
                'percentage' => $partner['percentage'] ?? 0
            ];
        });
    }

    public function getDirectorsListAttribute()
    {
        if (!$this->stakeholders || !isset($this->stakeholders['directors'])) return [];
        return $this->stakeholders['directors'];
    }

    public function getLatestNotesAttribute()
    {
        if (!$this->notes) return [];
        return collect($this->notes)->sortByDesc('date')->take(5)->values();
    }

    // ============ METHODS ============

    public function addNote(string $content, string $category = 'general'): void
    {
        $notes = $this->notes ?? [];
        $notes[] = [
            'date' => now()->toDateTimeString(),
            'author' => auth()->user()->name ?? 'System',
            'category' => $category,
            'content' => $content
        ];
        $this->notes = $notes;
        $this->save();
    }

    public function addMilestone(string $description, string $date, string $status = 'pending'): void
    {
        $milestones = $this->milestones ?? [];
        $milestones[] = [
            'date' => $date,
            'description' => $description,
            'status' => $status
        ];
        $this->milestones = $milestones;
        $this->save();
    }

    public function addUpdate(string $update): void
    {
        $updates = $this->updates ?? [];
        $updates[] = [
            'date' => now()->toDateTimeString(),
            'update' => $update,
            'author' => auth()->user()->name ?? 'System'
        ];
        $this->updates = $updates;
        $this->save();
    }

    /**
     * Add partner funding via the NEW pivot.
     */
    public function addPartnerFunding(int $partnerId, float $amount, string $transactionId = null): InvestmentFunding
    {
        $partner = Partner::find($partnerId);
        if (!$partner) {
            throw new \Exception('Partner not found');
        }

        $partnerTransaction = $partner->addContribution(
            $amount,
            $transactionId,
            "Investment funding for {$this->name}"
        );

        $funding = InvestmentFunding::create([
            'investment_id' => $this->id,
            'partner_id' => $partnerId,
            'partner_transaction_id' => $partnerTransaction->id,
            'amount_committed' => $amount,
            'amount_disbursed' => 0,
            'amount_returned' => 0,
            'status' => 'active',
            'notes' => "Funding added for {$this->name}",
        ]);

        // Keep totals in sync (legacy column)
        $this->total_funding_raised = ($this->total_funding_raised ?? 0) + $amount;
        $this->save();

        // Also mirror in legacy funding_partners JSON for backward compatibility
        $fundingPartners = $this->funding_partners ?? [];
        $fundingPartners[] = [
            'partner_id' => $partnerId,
            'amount' => $amount,
            'date' => now()->toDateString(),
            'transaction_id' => $partnerTransaction->id,
            'funding_id' => $funding->id,
        ];
        $this->funding_partners = $fundingPartners;
        $this->save();

        return $funding;
    }

    /**
     * Record a disbursement against a specific funding row (proportional accounting).
     * Falls back to the first active funding if $partnerId is null.
     */
    public function recordFundingDisbursement(float $amount, ?int $partnerId = null): void
    {
        $query = $this->fundings()->active();

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        $funding = $query->first();

        if ($funding) {
            $funding->amount_disbursed = (float) $funding->amount_disbursed + $amount;

            // Auto-settle if fully disbursed
            if ($funding->amount_disbursed >= $funding->amount_committed) {
                $funding->status = 'settled';
            }

            $funding->save();
        }
    }

    /**
     * Record a repayment return against a specific funding row.
     */
    public function recordFundingReturn(float $amount, ?int $partnerId = null): void
    {
        $query = $this->fundings()->active();

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        $funding = $query->first();

        if ($funding) {
            $funding->amount_returned = (float) $funding->amount_returned + $amount;
            $funding->save();
        }
    }

    public function fundDisbursement(float $amount, string $fundingSource = 'internal', ?int $partnerTransactionId = null): Disbursement
    {
        $disbursement = $this->disbursements()->create([
            'loan_id' => null,
            'amount' => $amount,
            'transaction' => 'INV-DISB-' . strtoupper(uniqid()),
            'mode' => 'investment',
            'disburse_date' => now(),
            'payment_date' => now(),
            'partner_transaction_id' => $partnerTransactionId,
            'funding_source' => $fundingSource,
            'investment_id' => $this->id,
        ]);

        // Update the pivot for whatever partner(s) are funding this
        if ($partnerTransactionId) {
            $pt = PartnerTransaction::find($partnerTransactionId);
            if ($pt) {
                $this->recordFundingDisbursement($amount, $pt->partner_id);
            }
        } else {
            $this->recordFundingDisbursement($amount);
        }

        return $disbursement;
    }

    public function recordRepayment(float $amount, string $mode = 'bank_transfer', ?int $partnerTransactionId = null): Repayment
    {
        $repayment = $this->repayments()->create([
            'loan_id' => null,
            'amount' => $amount,
            'transaction' => 'INV-REP-' . strtoupper(uniqid()),
            'repayment_date' => now(),
            'mode' => $mode,
            'partner_transaction_id' => $partnerTransactionId,
            'investment_id' => $this->id,
        ]);

        // Track the return against the pivot
        if ($partnerTransactionId) {
            $pt = PartnerTransaction::find($partnerTransactionId);
            if ($pt) {
                $this->recordFundingReturn($amount, $pt->partner_id);
            }
        }

        return $repayment;
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySector($query, $sector)
    {
        return $query->where('sector', $sector);
    }

    public function scopePipeline($query)
    {
        return $query->whereIn('status', ['pipeline', 'due_diligence']);
    }

    public function scopeInAfrica($query)
    {
        return $query->where('region', 'Africa');
    }

    public function scopeHighReturn($query, $minReturn = 15)
    {
        return $query->where('expected_return', '>=', $minReturn);
    }

    public function scopePreInvestmentEbitda($query, $minEbitda = 0)
    {
        return $query->where('ebitda_pre_investment', '>=', $minEbitda);
    }
}