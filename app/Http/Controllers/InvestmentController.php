<?php
// app/Http/Controllers/InvestmentController.php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Disbursement;
use App\Models\Repayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InvestmentController extends Controller
{
    /**
     * Display a listing of investments.
     */
    public function index()
    {
        $investments = Investment::with([
                'creator',
                'updater',
                'disbursements',
                'repayments',
                'partnerTransactions',
                'fundings.partner',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($investment) {
                return [
                    'id' => $investment->id,
                    'name' => $investment->name,
                    'type' => $investment->type,
                    'facility_type' => $investment->facility_type,
                    'duration_days' => $investment->duration_days,
                    'interest_rate' => $investment->interest_rate,
                    'sector' => $investment->sector,
                    'country' => $investment->country,
                    'status' => $investment->status,
                    'stage' => $investment->stage,
                    'initial_amount' => $investment->initial_amount,
                    'current_value' => $investment->current_value,
                    'expected_return' => $investment->expected_return,
                    'ebitda_pre_investment' => $investment->ebitda_pre_investment,
                    'return_percentage' => $investment->return_percentage,
                    'purchase_date' => $investment->purchase_date?->format('Y-m-d'),
                    'created_at' => $investment->created_at?->format('Y-m-d H:i:s'),
                    'total_funding_raised' => $investment->total_funding_raised,
                    'total_returns' => $investment->total_returns,
                    'net_return' => $investment->net_return,
                    'company_name' => $investment->company_name,
                    'committed_amount' => $investment->committed_amount,
                    'disbursed_amount' => $investment->disbursed_amount,
                    'remaining_amount' => $investment->remaining_amount,
                ];
            });

        $stats = [
            'total' => $investments->count(),
            'active' => $investments->where('status', 'active')->count(),
            'pipeline' => $investments->whereIn('status', ['pipeline', 'due_diligence'])->count(),
            'total_value' => $investments->sum('current_value'),
            'total_invested' => $investments->sum('initial_amount'),
            'avg_return' => $investments->avg('return_percentage') ?? 0,
        ];

        $partners = Partner::active()->get(['id', 'name', 'email', 'current_balance']);
        $users = User::whereIn('role', ['admin', 'borrower'])->get(['id', 'name', 'email']);

        return view('investments.index', compact('investments', 'stats', 'partners', 'users'));
    }

    /**
     * Store a newly created investment.
     */
    public function store(Request $request)
    {
        \Log::info('Investment store request:', $request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:commodity,equity,bond,real_estate,startup,infrastructure,technology,agriculture,energy,other',
            'facility_type' => 'nullable|in:short_term,long_term',
            'duration_days' => 'nullable|integer|min:1',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'sector' => 'nullable|string|max:100',
            'sub_sector' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'incorporation_date' => 'nullable|date',
            'legal_structure' => 'nullable|string|in:sole_proprietorship,partnership,llc,corporation,non_profit',
            'ebitda_pre_investment' => 'nullable|numeric|min:0',
            'revenue_pre_investment' => 'nullable|numeric|min:0',
            'net_profit_pre_investment' => 'nullable|numeric|min:0',
            'total_assets_pre_investment' => 'nullable|numeric|min:0',
            'total_liabilities_pre_investment' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'expected_return' => 'nullable|numeric|min:0|max:100',
            'actual_return' => 'nullable|numeric|min:0|max:100',
            'revenue_current' => 'nullable|numeric|min:0',
            'profit_current' => 'nullable|numeric|min:0',
            'valuation_current' => 'nullable|numeric|min:0',
            'initial_amount' => 'required|numeric|min:0',
            'irr' => 'nullable|numeric|min:0|max:100',
            'payback_period_months' => 'nullable|integer|min:0',
            'break_even_point' => 'nullable|numeric|min:0',
            'purchase_date' => 'required|date',
            'maturity_date' => 'nullable|date|after_or_equal:purchase_date',
            'exit_date' => 'nullable|date|after_or_equal:purchase_date',
            'risk_rating' => 'nullable|string|in:A,AA,AAA,BBB,BB,B,C',
            'risk_factors' => 'nullable|json',
            'stakeholders' => 'nullable|json',
            'market_research' => 'nullable|string',
            'competitive_landscape' => 'nullable|string',
            'swot_analysis' => 'nullable|json',
            'key_assumptions' => 'nullable|string',
            'status' => 'nullable|string|in:pipeline,due_diligence,active,matured,liquidated,write_off',
            'stage' => 'nullable|string|in:ideation,seed,startup,growth,expansion,mature',
            'milestones' => 'nullable|json',
            'notes' => 'nullable|string',
            // Legacy single funding
            'funding_partner_id' => 'nullable|exists:partners,id',
            'funding_amount' => 'nullable|numeric|min:0',
            // NEW: multi-partner funding array
            'fundings' => 'nullable|array',
            'fundings.*.partner_id' => 'required_with:fundings|exists:partners,id',
            'fundings.*.amount_committed' => 'required_with:fundings|numeric|min:0',
            'fundings.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::error('Investment validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $riskFactors = $request->filled('risk_factors') ? json_decode($request->risk_factors, true) : null;
            $stakeholders = $request->filled('stakeholders') ? json_decode($request->stakeholders, true) : null;
            $swotAnalysis = $request->filled('swot_analysis') ? json_decode($request->swot_analysis, true) : null;
            $milestones = $request->filled('milestones') ? json_decode($request->milestones, true) : null;

            $notes = null;
            if ($request->filled('notes')) {
                $notes = [[
                    'date' => now()->toDateTimeString(),
                    'author' => auth()->user()->name ?? 'System',
                    'category' => 'initial',
                    'content' => $request->notes
                ]];
            }

            // Determine facility_type
            $facilityType = $request->facility_type;
            if (!$facilityType) {
                $facilityType = ($request->filled('duration_days') && $request->filled('interest_rate'))
                    ? 'short_term'
                    : 'long_term';
            }

            // Pre-populate defaults
            $initialAmount = $request->initial_amount;
            $currentValue = $request->current_value;
            if (!$currentValue || $currentValue <= 0) {
                $currentValue = $initialAmount;
            }

            $status = $request->status ?: ($facilityType === 'short_term' ? 'active' : 'pipeline');
            $purchaseDate = $request->purchase_date ?: now()->toDateString();

            $maturityDate = $request->maturity_date;
            if (!$maturityDate && $facilityType === 'short_term' && $request->duration_days) {
                $maturityDate = \Carbon\Carbon::parse($purchaseDate)
                    ->addDays((int) $request->duration_days)
                    ->toDateString();
            }

            $expectedReturn = $request->expected_return;
            if (!$expectedReturn && $facilityType === 'short_term' && $request->interest_rate) {
                $expectedReturn = $request->interest_rate;
            }

            $country = $request->country ?: 'Kenya';

            $investment = Investment::create([
                'name' => $request->name,
                'type' => $request->type,
                'facility_type' => $facilityType,
                'duration_days' => $request->duration_days,
                'interest_rate' => $request->interest_rate,
                'sector' => $request->sector,
                'sub_sector' => $request->sub_sector,
                'country' => $country,
                'region' => $request->region,
                'city' => $request->city,
                'address' => $request->address,
                'company_name' => $request->company_name,
                'registration_number' => $request->registration_number,
                'incorporation_date' => $request->incorporation_date,
                'legal_structure' => $request->legal_structure,
                'ebitda_pre_investment' => $request->ebitda_pre_investment,
                'revenue_pre_investment' => $request->revenue_pre_investment,
                'net_profit_pre_investment' => $request->net_profit_pre_investment,
                'total_assets_pre_investment' => $request->total_assets_pre_investment,
                'total_liabilities_pre_investment' => $request->total_liabilities_pre_investment,
                'current_value' => $currentValue,
                'expected_return' => $expectedReturn,
                'actual_return' => $request->actual_return,
                'revenue_current' => $request->revenue_current,
                'profit_current' => $request->profit_current,
                'valuation_current' => $request->valuation_current,
                'initial_amount' => $initialAmount,
                'irr' => $request->irr,
                'payback_period_months' => $request->payback_period_months,
                'break_even_point' => $request->break_even_point,
                'purchase_date' => $purchaseDate,
                'maturity_date' => $maturityDate,
                'exit_date' => $request->exit_date,
                'risk_rating' => $request->risk_rating,
                'risk_factors' => $riskFactors,
                'stakeholders' => $stakeholders,
                'market_research' => $request->market_research,
                'competitive_landscape' => $request->competitive_landscape,
                'swot_analysis' => $swotAnalysis,
                'key_assumptions' => $request->key_assumptions,
                'status' => $status,
                'stage' => $request->stage,
                'milestones' => $milestones,
                'notes' => $notes,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // ============ HANDLE MULTI-PARTNER FUNDING (new pivot) ============
            $fundingsInput = $request->input('fundings', []);
            $totalCommitted = 0;

            foreach ($fundingsInput as $row) {
                if (empty($row['partner_id']) || empty($row['amount_committed']) || $row['amount_committed'] <= 0) {
                    continue;
                }

                $partner = Partner::find($row['partner_id']);
                if (!$partner) {
                    continue;
                }

                $partnerTransaction = $partner->addContribution(
                    $row['amount_committed'],
                    'INV-' . strtoupper(uniqid()),
                    "Investment funding for {$investment->name}"
                );

                InvestmentFunding::create([
                    'investment_id' => $investment->id,
                    'partner_id' => $partner->id,
                    'partner_transaction_id' => $partnerTransaction->id,
                    'amount_committed' => $row['amount_committed'],
                    'amount_disbursed' => 0,
                    'amount_returned' => 0,
                    'status' => 'active',
                    'notes' => $row['notes'] ?? "Initial funding for {$investment->name}",
                ]);

                $totalCommitted += (float) $row['amount_committed'];
            }

            // Legacy single-partner funding (still supported for backward compat)
            if ($totalCommitted <= 0 && $request->filled('funding_partner_id') && $request->filled('funding_amount') && $request->funding_amount > 0) {
                $partner = Partner::find($request->funding_partner_id);
                if ($partner) {
                    $partnerTransaction = $partner->addContribution(
                        $request->funding_amount,
                        'INV-' . strtoupper(uniqid()),
                        "Investment funding for {$investment->name}"
                    );

                    InvestmentFunding::create([
                        'investment_id' => $investment->id,
                        'partner_id' => $partner->id,
                        'partner_transaction_id' => $partnerTransaction->id,
                        'amount_committed' => $request->funding_amount,
                        'amount_disbursed' => 0,
                        'amount_returned' => 0,
                        'status' => 'active',
                        'notes' => "Initial funding for {$investment->name}",
                    ]);

                    $totalCommitted += (float) $request->funding_amount;
                }
            }

            if ($totalCommitted > 0) {
                $investment->total_funding_raised = $totalCommitted;
                $investment->funding_partners = $investment->fundings()
                    ->with('partner')
                    ->get()
                    ->map(fn ($f) => [
                        'partner_id' => $f->partner_id,
                        'amount' => (float) $f->amount_committed,
                        'date' => $f->created_at?->toDateString(),
                        'transaction_id' => $f->partner_transaction_id,
                        'funding_id' => $f->id,
                    ])
                    ->toArray();
                $investment->save();
            }

            DB::commit();

            \Log::info('Investment created successfully:', ['id' => $investment->id]);

            return response()->json([
                'success' => true,
                'message' => $facilityType === 'short_term'
                    ? 'Short-term facility created successfully.'
                    : 'Investment created successfully.',
                'investment' => $investment->fresh(['fundings.partner'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Investment creation failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create investment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified investment.
     */
    public function show(Investment $investment)
    {
        $investment->load([
            'creator',
            'updater',
            'disbursements',
            'repayments',
            'partnerTransactions',
            'fundings.partner',
        ]);

        $data = [
            'id' => $investment->id,
            'name' => $investment->name,
            'type' => $investment->type,
            'facility_type' => $investment->facility_type,
            'duration_days' => $investment->duration_days,
            'interest_rate' => $investment->interest_rate,
            'sector' => $investment->sector,
            'sub_sector' => $investment->sub_sector,
            'country' => $investment->country,
            'region' => $investment->region,
            'city' => $investment->city,
            'address' => $investment->address,
            'company_name' => $investment->company_name,
            'registration_number' => $investment->registration_number,
            'incorporation_date' => $investment->incorporation_date?->format('Y-m-d'),
            'legal_structure' => $investment->legal_structure,
            'directors' => $investment->stakeholders['directors'] ?? [],
            'board_members' => $investment->stakeholders['board'] ?? [],
            'advisors' => $investment->stakeholders['advisors'] ?? [],
            'partners' => $investment->partners_list,
            'ebitda_pre_investment' => $investment->ebitda_pre_investment,
            'revenue_pre_investment' => $investment->revenue_pre_investment,
            'net_profit_pre_investment' => $investment->net_profit_pre_investment,
            'total_assets_pre_investment' => $investment->total_assets_pre_investment,
            'total_liabilities_pre_investment' => $investment->total_liabilities_pre_investment,
            'initial_amount' => $investment->initial_amount,
            'current_value' => $investment->current_value,
            'expected_return' => $investment->expected_return,
            'actual_return' => $investment->actual_return,
            'revenue_current' => $investment->revenue_current,
            'profit_current' => $investment->profit_current,
            'valuation_current' => $investment->valuation_current,
            'return_percentage' => $investment->return_percentage,
            'profit_loss' => $investment->profit_loss,
            'irr' => $investment->irr,
            'payback_period_months' => $investment->payback_period_months,
            'break_even_point' => $investment->break_even_point,
            'purchase_date' => $investment->purchase_date?->format('Y-m-d'),
            'maturity_date' => $investment->maturity_date?->format('Y-m-d'),
            'exit_date' => $investment->exit_date?->format('Y-m-d'),
            'risk_rating' => $investment->risk_rating,
            'risk_factors' => $investment->risk_factors,
            'status' => $investment->status,
            'stage' => $investment->stage,
            'swot_analysis' => $investment->swot_analysis,
            'market_research' => $investment->market_research,
            'competitive_landscape' => $investment->competitive_landscape,
            'key_assumptions' => $investment->key_assumptions,
            'milestones' => $investment->milestones,
            'notes' => $investment->notes,
            'updates' => $investment->updates,
            'total_funding_raised' => $investment->total_funding_raised,
            'funding_partners' => $investment->funding_partners,
            'created_at' => $investment->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $investment->updated_at?->format('Y-m-d H:i:s'),
            'created_by' => $investment->creator?->name,
            'updated_by' => $investment->updater?->name,
            'total_disbursements' => $investment->disbursements->sum('amount'),
            'total_repayments' => $investment->repayments->sum('amount'),
            'net_position' => $investment->net_return,

            // NEW — aggregates
            'committed_amount' => $investment->committed_amount,
            'disbursed_amount' => $investment->disbursed_amount,
            'remaining_amount' => $investment->remaining_amount,

            // NEW — pivot rows, ready for the modal
            'fundings' => $investment->fundings->map(fn ($f) => [
                'id' => $f->id,
                'partner_id' => $f->partner_id,
                'partner_name' => $f->partner?->name,
                'amount_committed' => (float) $f->amount_committed,
                'amount_disbursed' => (float) $f->amount_disbursed,
                'amount_returned' => (float) $f->amount_returned,
                'status' => $f->status,
                'notes' => $f->notes,
            ])->values(),
        ];

        $partners = Partner::active()->get(['id', 'name', 'email', 'current_balance']);
        $users = User::whereIn('role', ['admin', 'borrower'])->get(['id', 'name', 'email']);

        return view('investments.show', compact('investment', 'data', 'partners', 'users'));
    }

    /**
     * Update the specified investment.
     */
    public function update(Request $request, Investment $investment)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:commodity,equity,bond,real_estate,startup,infrastructure,technology,agriculture,energy,other',
            'facility_type' => 'nullable|in:short_term,long_term',
            'duration_days' => 'nullable|integer|min:1',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'country' => 'sometimes|required|string|max:100',
            'initial_amount' => 'sometimes|required|numeric|min:0',
            'current_value' => 'sometimes|required|numeric|min:0',
            'expected_return' => 'nullable|numeric|min:0|max:100',
            'purchase_date' => 'sometimes|required|date',
            'maturity_date' => 'nullable|date',
            'status' => 'sometimes|required|string|in:pipeline,due_diligence,active,matured,liquidated,write_off',
            'stage' => 'nullable|string|in:ideation,seed,startup,growth,expansion,mature',
            'notes' => 'nullable|string',
            // Pivot updates
            'fundings' => 'nullable|array',
            'fundings.*.partner_id' => 'required_with:fundings|exists:partners,id',
            'fundings.*.amount_committed' => 'required_with:fundings|numeric|min:0',
            'fundings.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $investment->update(array_merge(
                $request->only([
                    'name', 'type', 'facility_type', 'duration_days', 'interest_rate',
                    'sector', 'sub_sector', 'country', 'region', 'city',
                    'company_name', 'registration_number', 'incorporation_date', 'legal_structure',
                    'ebitda_pre_investment', 'revenue_pre_investment', 'net_profit_pre_investment',
                    'total_assets_pre_investment', 'total_liabilities_pre_investment',
                    'initial_amount', 'current_value', 'expected_return',
                    'purchase_date', 'maturity_date', 'exit_date',
                    'risk_rating', 'status', 'stage'
                ]),
                ['updated_by' => auth()->id()]
            ));

            // ============ SYNC PIVOT FUNDINGS ============
            // Only if the modal sent a fundings array
            if ($request->has('fundings') && is_array($request->input('fundings'))) {
                $incoming = collect($request->input('fundings'))
                    ->filter(fn ($row) => !empty($row['partner_id']) && !empty($row['amount_committed']))
                    ->values();

                // Remove fundings that are no longer present and have no disbursements
                $keepIds = $incoming->pluck('id')->filter()->toArray();
                $investment->fundings()
                    ->whereNotIn('id', $keepIds)
                    ->where('amount_disbursed', 0)
                    ->where('amount_returned', 0)
                    ->delete();

                // Upsert incoming
                foreach ($incoming as $row) {
                    if (!empty($row['id'])) {
                        // Update existing
                        $funding = InvestmentFunding::where('investment_id', $investment->id)
                            ->where('id', $row['id'])
                            ->first();

                        if ($funding) {
                            $funding->update([
                                'partner_id' => $row['partner_id'],
                                'amount_committed' => $row['amount_committed'],
                                'notes' => $row['notes'] ?? $funding->notes,
                            ]);
                            continue;
                        }
                    }

                    // Create new pivot row
                    $partner = Partner::find($row['partner_id']);
                    if (!$partner) continue;

                    $partnerTransaction = $partner->addContribution(
                        $row['amount_committed'],
                        'INV-' . strtoupper(uniqid()),
                        "Additional funding for {$investment->name}"
                    );

                    InvestmentFunding::create([
                        'investment_id' => $investment->id,
                        'partner_id' => $partner->id,
                        'partner_transaction_id' => $partnerTransaction->id,
                        'amount_committed' => $row['amount_committed'],
                        'amount_disbursed' => 0,
                        'amount_returned' => 0,
                        'status' => 'active',
                        'notes' => $row['notes'] ?? "Additional funding for {$investment->name}",
                    ]);
                }

                // Refresh totals & legacy JSON
                $investment->refresh();
                $investment->total_funding_raised = (float) $investment->fundings()->sum('amount_committed');
                $investment->funding_partners = $investment->fundings()
                    ->with('partner')
                    ->get()
                    ->map(fn ($f) => [
                        'partner_id' => $f->partner_id,
                        'amount' => (float) $f->amount_committed,
                        'date' => $f->created_at?->toDateString(),
                        'transaction_id' => $f->partner_transaction_id,
                        'funding_id' => $f->id,
                    ])
                    ->toArray();
                $investment->save();
            }

            if ($request->filled('notes')) {
                $investment->addNote($request->notes, 'update');
            }

            if ($request->filled('update_content')) {
                $investment->addUpdate($request->update_content);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Investment updated successfully.',
                'investment' => $investment->fresh(['fundings.partner'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update investment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified investment.
     */
    public function destroy(Investment $investment)
    {
        try {
            DB::beginTransaction();

            // Detach pivot rows (cascade will handle it if FK is set, but be explicit)
            $investment->fundings()->delete();

            $investment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Investment deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete investment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get investment data for the table & disbursement modal.
     */
    public function getData(Request $request)
    {
        $query = Investment::query();

        if ($request->filled('facility_type')) {
            $query->where('facility_type', $request->facility_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('sector', 'LIKE', "%{$search}%");
            });
        }

        $investments = $query->with(['fundings.partner'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($investment) {
                // Prefer pivot partners, fallback to legacy JSON
                $partnerModels = $investment->fundedPartners()
                    ->get()
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'amount_committed' => (float) $p->pivot->amount_committed,
                    ])
                    ->values()
                    ->toArray();

                if (empty($partnerModels)) {
                    $partnerModels = $investment->fundingPartnerModels()
                        ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
                        ->values()
                        ->toArray();
                }

                return [
                    'id' => $investment->id,
                    'name' => $investment->name,
                    'type' => $investment->type,
                    'facility_type' => $investment->facility_type,
                    'facility_type_label' => $investment->facility_type === 'short_term'
                        ? 'Short-Term'
                        : 'Long-Term',
                    'duration_days' => $investment->duration_days,
                    'interest_rate' => $investment->interest_rate,
                    'sector' => $investment->sector,
                    'country' => $investment->country,
                    'status' => $investment->status,
                    'stage' => $investment->stage,
                    'initial_amount' => $investment->initial_amount,
                    'current_value' => $investment->current_value,
                    'expected_return' => $investment->expected_return,
                    'return_percentage' => $investment->return_percentage,
                    'purchase_date' => $investment->purchase_date?->format('Y-m-d'),
                    'maturity_date' => $investment->maturity_date?->format('Y-m-d'),
                    'total_funding_raised' => $investment->total_funding_raised,
                    'total_returns' => $investment->total_returns,
                    'net_return' => $investment->net_return,
                    'company_name' => $investment->company_name,

                    // NEW — aggregates for the disbursement modal
                    'committed_amount' => $investment->committed_amount,
                    'disbursed_amount' => $investment->disbursed_amount,
                    'remaining_amount' => $investment->remaining_amount,

                    // Partners for auto-populating chips
                    'funding_partner_models' => $partnerModels,

                    // Full pivot rows (useful for edit modal)
                    'fundings' => $investment->fundings->map(fn ($f) => [
                        'id' => $f->id,
                        'partner_id' => $f->partner_id,
                        'partner_name' => $f->partner?->name,
                        'amount_committed' => (float) $f->amount_committed,
                        'amount_disbursed' => (float) $f->amount_disbursed,
                        'amount_returned' => (float) $f->amount_returned,
                        'status' => $f->status,
                        'notes' => $f->notes,
                    ])->values(),
                ];
            });

        return response()->json([
            'data' => $investments,
            'count' => $investments->count(),
        ]);
    }

    /**
     * Get investment statistics.
     */
    public function getStats()
    {
        $investments = Investment::all();

        return response()->json([
            'total' => $investments->count(),
            'active' => $investments->where('status', 'active')->count(),
            'pipeline' => $investments->whereIn('status', ['pipeline', 'due_diligence'])->count(),
            'total_value' => $investments->sum('current_value'),
            'total_invested' => $investments->sum('initial_amount'),
            'avg_return' => $investments->avg('return_percentage') ?? 0,
            'total_committed' => $investments->sum(fn ($i) => $i->committed_amount),
            'total_disbursed' => $investments->sum(fn ($i) => $i->disbursed_amount),
            'total_remaining' => $investments->sum(fn ($i) => $i->remaining_amount),
            'by_type' => $investments->groupBy('type')->map->count(),
            'by_facility_type' => $investments->groupBy('facility_type')->map->count(),
            'by_country' => $investments->groupBy('country')->map->count(),
            'by_status' => $investments->groupBy('status')->map->count(),
        ]);
    }
}