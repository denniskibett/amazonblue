<?php
// app/Models/Investment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        // Basic Info
        'name', 'type', 'sector', 'sub_sector',
        'country', 'region', 'city', 'address',
        
        // Corporate Structure
        'company_name', 'registration_number', 'incorporation_date', 'legal_structure',
        
        // Financials (Pre-Investment)
        'ebitda_pre_investment', 'revenue_pre_investment', 'net_profit_pre_investment',
        'total_assets_pre_investment', 'total_liabilities_pre_investment',
        
        // Financials (Current)
        'current_value', 'expected_return', 'actual_return',
        'revenue_current', 'profit_current', 'valuation_current',
        
        // Investment Metrics
        'initial_amount', 'irr', 'payback_period_months', 'break_even_point',
        
        // Dates
        'purchase_date', 'maturity_date', 'exit_date',
        
        // Risk
        'risk_rating', 'risk_factors',
        
        // Stakeholders (JSON)
        'stakeholders',
        
        // Documents
        'pitch_deck_path', 'financial_model_path', 'due_diligence_path', 'legal_docs',
        
        // Research
        'market_research', 'competitive_landscape', 'swot_analysis', 'key_assumptions',
        
        // Notes (JSON)
        'notes',
        
        // Tracking
        'status', 'stage', 'milestones',
        
        // Funding
        'total_funding_raised', 'funding_partners',
        
        // Updates
        'updates',
        
        'created_by', 'updated_by'
    ];

    protected $casts = [
        // Financials
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
        
        // JSON Fields
        'risk_factors' => 'array',
        'stakeholders' => 'array',
        'legal_docs' => 'array',
        'swot_analysis' => 'array',
        'notes' => 'array',
        'milestones' => 'array',
        'funding_partners' => 'array',
        'updates' => 'array',
        
        // Dates
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============ ACCESSORS ============

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

    public function getTotalFundingAttribute()
    {
        return $this->disbursements()->sum('amount');
    }

    public function getTotalReturnsAttribute()
    {
        return $this->repayments()->sum('amount');
    }

    public function getNetReturnAttribute()
    {
        return $this->total_returns - $this->total_funding;
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

    public function addPartnerFunding(int $partnerId, float $amount, string $transactionId = null): void
    {
        $partner = Partner::find($partnerId);
        if ($partner) {
            $partnerTransaction = $partner->addContribution($amount, $transactionId, "Investment funding for {$this->name}");
            
            $fundingPartners = $this->funding_partners ?? [];
            $fundingPartners[] = [
                'partner_id' => $partnerId,
                'amount' => $amount,
                'date' => now()->toDateString(),
                'transaction_id' => $partnerTransaction->id
            ];
            $this->funding_partners = $fundingPartners;
            $this->total_funding_raised += $amount;
            $this->save();
        }
    }

    public function fundDisbursement(float $amount, string $fundingSource = 'internal', ?int $partnerTransactionId = null): Disbursement
    {
        return $this->disbursements()->create([
            'loan_id' => null,
            'amount' => $amount,
            'transaction' => 'INV-DISB-' . strtoupper(uniqid()),
            'mode' => 'investment',
            'disburse_date' => now(),
            'payment_date' => now(),
            'partner_transaction_id' => $partnerTransactionId,
            'funding_source' => $fundingSource,
            'investment_id' => $this->id
        ]);
    }

    public function recordRepayment(float $amount, string $mode = 'bank_transfer', ?int $partnerTransactionId = null): Repayment
    {
        return $this->repayments()->create([
            'loan_id' => null,
            'amount' => $amount,
            'transaction' => 'INV-REP-' . strtoupper(uniqid()),
            'repayment_date' => now(),
            'mode' => $mode,
            'partner_transaction_id' => $partnerTransactionId,
            'investment_id' => $this->id
        ]);
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