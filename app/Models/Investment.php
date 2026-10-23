<?php
// app/Models/Investment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'sector', 'sub_sector',
        'facility_type',
        'return_type',   // house | loan_share | fixed_rate (Stage 4 Phase 3)
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
     * Add partner funding: an investment_fundings row, plus the partner's contribution through the
     * partner journal (principal wallet + partner_transactions row linked to this investment/funding).
     */
    public function addPartnerFunding(int $partnerId, float $amount, ?string $reference = null, ?string $notes = null): InvestmentFunding
    {
        $partner = Partner::find($partnerId);
        if (! $partner) {
            throw new \InvalidArgumentException('Partner not found.');
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Funding amount must be more than zero.');
        }

        return DB::transaction(function () use ($partner, $amount, $reference, $notes) {
            $funding = InvestmentFunding::create([
                'investment_id' => $this->id,
                'partner_id' => $partner->id,
                'amount_committed' => $amount,
                'amount_disbursed' => 0,
                'amount_returned' => 0,
                'status' => 'active',
                'notes' => $notes ?: "Funding for {$this->name}",
            ]);

            $journal = $partner->addContribution(
                $amount,
                $reference ?: 'INV-' . $this->id . '-F' . $funding->id,
                "Investment funding for {$this->name}",
                $this->id,
                $funding->id
            );
            $funding->update(['partner_transaction_id' => $journal->id]);

            $this->syncLegacyFunding();

            return $funding->fresh();
        });
    }

    /**
     * Keep the legacy duplicates (total_funding_raised, funding_partners JSON) in step with the fundings.
     */
    public function syncLegacyFunding(): void
    {
        $fundings = $this->fundings()->get();
        $this->total_funding_raised = (float) $fundings->sum('amount_committed');
        $this->funding_partners = $fundings->map(fn ($f) => [
            'partner_id' => $f->partner_id,
            'amount' => (float) $f->amount_committed,
            'date' => $f->created_at?->toDateString(),
            'transaction_id' => $f->partner_transaction_id,
            'funding_id' => $f->id,
        ])->values()->toArray();
        $this->save();
    }

    /**
     * Funded by the house account only (or not linked to any partner yet, which the disbursement screen
     * links to the house): AC lends its own money here, so there is no commitment to draw against.
     */
    public function isHouseLine(): bool
    {
        $partnerIds = $this->fundings()->where('status', '<>', 'cancelled')->pluck('partner_id')->unique();

        return $partnerIds->isEmpty()
            || Partner::whereIn('id', $partnerIds)->where('is_house_account', false)->doesntExist();
    }

    /**
     * What partners committed and has not been drawn yet (active fundings).
     */
    public function remainingCommitted(?int $partnerId = null): float
    {
        return (float) $this->fundings()->active()
            ->when($partnerId, fn ($q) => $q->where('partner_id', $partnerId))
            ->get()->sum(fn ($f) => $f->remaining_to_disburse);
    }

    /**
     * Refuse a draw larger than what partners committed and haven't drawn (house lines are not capped).
     */
    public function assertCanDisburse(float $amount, ?int $partnerId = null): void
    {
        if ($this->isHouseLine()) {
            return;
        }
        // a closed or matured partner line lends no more (owner, 23 Oct 2026)
        if ($this->status !== 'active' || ($this->maturity_date && \Illuminate\Support\Carbon::parse($this->maturity_date)->endOfDay()->isPast() && $this->return_type === 'loan_share')) {
            throw new \DomainException(sprintf('Investment "%s" is closed or past its pay-back date, so it can\'t fund new loans.', $this->name));
        }
        $remaining = $this->remainingCommitted($partnerId);
        if (round($amount, 2) > round($remaining, 2)) {
            throw new \DomainException(sprintf(
                'KES %s is more than the KES %s still committed to investment "%s". Add funding first or reduce the amount.',
                number_format($amount, 2), number_format($remaining, 2), $this->name
            ));
        }
    }

    /**
     * Record a draw against the fundings: a committed row with room for it first, otherwise (house
     * line only) an open house row with no commitment. A committed row settles when fully drawn; an
     * open row (committed 0) never auto-settles, so tracking doesn't stop after the first draw.
     */
    public function recordFundingDisbursement(float $amount, ?int $partnerId = null): void
    {
        $this->assertCanDisburse($amount, $partnerId);

        $fundings = $this->fundings()->active()
            ->when($partnerId, fn ($q) => $q->where('partner_id', $partnerId))
            ->orderBy('id')->lockForUpdate()->get();

        $funding = $fundings->first(fn ($f) => (float) $f->amount_committed > 0 && round($f->remaining_to_disburse, 2) >= round($amount, 2))
            ?? $fundings->first(fn ($f) => (float) $f->amount_committed <= 0);

        if (! $funding && $this->isHouseLine()) {
            $house = Partner::where('is_house_account', true)->orderByRaw("account_type = 'principal' desc")->orderBy('id')->first();
            if (! $house) {
                return;
            }
            $funding = InvestmentFunding::create([
                'investment_id' => $this->id,
                'partner_id' => $house->id,
                'amount_committed' => 0,
                'amount_disbursed' => 0,
                'amount_returned' => 0,
                'status' => 'active',
                'notes' => 'Open house line: AC lends its own money, no commitment to draw against.',
            ]);
        }
        // no single row has room: spread the draw over the committed rows in order (the cap check passed)
        $parts = $funding ? [[$funding, $amount]] : [];
        if (! $funding) {
            $left = $amount;
            foreach ($fundings->filter(fn ($f) => (float) $f->amount_committed > 0) as $f) {
                $take = min($left, $f->remaining_to_disburse);
                if ($take > 0) {
                    $parts[] = [$f, $take];
                    $left = round($left - $take, 2);
                }
            }
        }

        foreach ($parts as [$row, $part]) {
            $row->amount_disbursed = (float) $row->amount_disbursed + $part;
            if ((float) $row->amount_committed > 0 && (float) $row->amount_disbursed >= (float) $row->amount_committed && ! $row->recycles()) {
                $row->status = 'settled';
            }
            $row->save();
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

    /**
     * A disbursement funded by this investment. It must be to a loan: disbursements.loan_id is required
     * (cash goes to a borrower), so an investment-only draw is refused with a clear message.
     */
    public function fundDisbursement(float $amount, string $fundingSource = 'internal', ?int $partnerTransactionId = null, ?int $loanId = null): Disbursement
    {
        $loan = $loanId ? Loan::find($loanId) : null;
        if (! $loan) {
            throw new \InvalidArgumentException('A disbursement must be linked to a loan: choose the loan this investment money is lent on.');
        }

        return DB::transaction(function () use ($amount, $fundingSource, $partnerTransactionId, $loan) {
            $partnerId = $partnerTransactionId ? PartnerTransaction::find($partnerTransactionId)?->partner_id : null;
            $this->assertCanDisburse($amount, $partnerId);

            $disbursement = $this->disbursements()->create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'transaction' => 'INV-DISB-' . strtoupper(uniqid()),
                'mode' => 'investment',
                'disburse_date' => now(),
                'payment_date' => now(),
                'partner_transaction_id' => $partnerTransactionId,
                'funding_source' => $fundingSource,
                'investment_id' => $this->id,
            ]);

            $this->recordFundingDisbursement($amount, $partnerId);

            return $disbursement;
        });
    }

    /**
     * A repayment returned to this investment; like disbursements it must belong to a loan.
     */
    public function recordRepayment(float $amount, string $mode = 'bank_transfer', ?int $partnerTransactionId = null, ?int $loanId = null): Repayment
    {
        $loan = $loanId ? Loan::find($loanId) : null;
        if (! $loan) {
            throw new \InvalidArgumentException('A repayment must be linked to a loan: choose the loan this money was repaid on.');
        }

        return DB::transaction(function () use ($amount, $mode, $partnerTransactionId, $loan) {
            $repayment = $this->repayments()->create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'transaction' => 'INV-REP-' . strtoupper(uniqid()),
                'repayment_date' => now(),
                'mode' => $mode,
                'partner_transaction_id' => $partnerTransactionId,
                'investment_id' => $this->id,
            ]);

            // Track the return against the pivot
            if ($partnerTransactionId && ($pt = PartnerTransaction::find($partnerTransactionId))) {
                $this->recordFundingReturn($amount, $pt->partner_id);
            }

            return $repayment;
        });
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