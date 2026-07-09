<?php
// app/Http/Controllers/InvestmentController.php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Disbursement;
use App\Models\Repayment;
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
        $investments = Investment::with(['creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($investment) {
                return [
                    'id' => $investment->id,
                    'name' => $investment->name,
                    'type' => $investment->type,
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

        return view('investments.index', compact('investments', 'stats', 'partners'));
    }

    /**
     * Store a newly created investment.
     */
    public function store(Request $request)
    {
        \Log::info('Investment store request:', $request->all());

        $validator = Validator::make($request->all(), [
            // Basic Info
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:commodity,equity,bond,real_estate,startup,infrastructure,technology,agriculture,energy,other',
            'sector' => 'nullable|string|max:100',
            'sub_sector' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            
            // Corporate Structure
            'company_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'incorporation_date' => 'nullable|date',
            'legal_structure' => 'nullable|string|in:sole_proprietorship,partnership,llc,corporation,non_profit',
            
            // Financials (Pre-Investment)
            'ebitda_pre_investment' => 'nullable|numeric|min:0',
            'revenue_pre_investment' => 'nullable|numeric|min:0',
            'net_profit_pre_investment' => 'nullable|numeric|min:0',
            'total_assets_pre_investment' => 'nullable|numeric|min:0',
            'total_liabilities_pre_investment' => 'nullable|numeric|min:0',
            
            // Financials (Current)
            'current_value' => 'required|numeric|min:0',
            'expected_return' => 'nullable|numeric|min:0|max:100',
            'actual_return' => 'nullable|numeric|min:0|max:100',
            'revenue_current' => 'nullable|numeric|min:0',
            'profit_current' => 'nullable|numeric|min:0',
            'valuation_current' => 'nullable|numeric|min:0',
            
            // Investment Metrics
            'initial_amount' => 'required|numeric|min:0',
            'irr' => 'nullable|numeric|min:0|max:100',
            'payback_period_months' => 'nullable|integer|min:0',
            'break_even_point' => 'nullable|numeric|min:0',
            
            // Dates
            'purchase_date' => 'required|date',
            'maturity_date' => 'nullable|date|after:purchase_date',
            'exit_date' => 'nullable|date|after_or_equal:purchase_date',
            
            // Risk
            'risk_rating' => 'nullable|string|in:A,AA,AAA,BBB,BB,B,C',
            'risk_factors' => 'nullable|json',
            
            // Stakeholders
            'stakeholders' => 'nullable|json',
            
            // Research
            'market_research' => 'nullable|string',
            'competitive_landscape' => 'nullable|string',
            'swot_analysis' => 'nullable|json',
            'key_assumptions' => 'nullable|string',
            
            // Tracking
            'status' => 'required|string|in:pipeline,due_diligence,active,matured,liquidated,write_off',
            'stage' => 'nullable|string|in:ideation,seed,startup,growth,expansion,mature',
            'milestones' => 'nullable|json',
            
            // Notes
            'notes' => 'nullable|string',
            
            // Partner Funding
            'funding_partner_id' => 'nullable|exists:partners,id',
            'funding_amount' => 'nullable|numeric|min:0',
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

            // Parse JSON fields
            $riskFactors = $request->filled('risk_factors') ? json_decode($request->risk_factors, true) : null;
            $stakeholders = $request->filled('stakeholders') ? json_decode($request->stakeholders, true) : null;
            $swotAnalysis = $request->filled('swot_analysis') ? json_decode($request->swot_analysis, true) : null;
            $milestones = $request->filled('milestones') ? json_decode($request->milestones, true) : null;

            // Prepare notes as array
            $notes = null;
            if ($request->filled('notes')) {
                $notes = [[
                    'date' => now()->toDateTimeString(),
                    'author' => auth()->user()->name ?? 'System',
                    'category' => 'initial',
                    'content' => $request->notes
                ]];
            }

            // Create the investment
            $investment = Investment::create([
                // Basic Info
                'name' => $request->name,
                'type' => $request->type,
                'sector' => $request->sector,
                'sub_sector' => $request->sub_sector,
                'country' => $request->country,
                'region' => $request->region,
                'city' => $request->city,
                'address' => $request->address,
                
                // Corporate Structure
                'company_name' => $request->company_name,
                'registration_number' => $request->registration_number,
                'incorporation_date' => $request->incorporation_date,
                'legal_structure' => $request->legal_structure,
                
                // Financials (Pre-Investment)
                'ebitda_pre_investment' => $request->ebitda_pre_investment,
                'revenue_pre_investment' => $request->revenue_pre_investment,
                'net_profit_pre_investment' => $request->net_profit_pre_investment,
                'total_assets_pre_investment' => $request->total_assets_pre_investment,
                'total_liabilities_pre_investment' => $request->total_liabilities_pre_investment,
                
                // Financials (Current)
                'current_value' => $request->current_value,
                'expected_return' => $request->expected_return,
                'actual_return' => $request->actual_return,
                'revenue_current' => $request->revenue_current,
                'profit_current' => $request->profit_current,
                'valuation_current' => $request->valuation_current,
                
                // Investment Metrics
                'initial_amount' => $request->initial_amount,
                'irr' => $request->irr,
                'payback_period_months' => $request->payback_period_months,
                'break_even_point' => $request->break_even_point,
                
                // Dates
                'purchase_date' => $request->purchase_date,
                'maturity_date' => $request->maturity_date,
                'exit_date' => $request->exit_date,
                
                // Risk
                'risk_rating' => $request->risk_rating,
                'risk_factors' => $riskFactors,
                
                // Stakeholders
                'stakeholders' => $stakeholders,
                
                // Research
                'market_research' => $request->market_research,
                'competitive_landscape' => $request->competitive_landscape,
                'swot_analysis' => $swotAnalysis,
                'key_assumptions' => $request->key_assumptions,
                
                // Tracking
                'status' => $request->status,
                'stage' => $request->stage,
                'milestones' => $milestones,
                
                // Notes
                'notes' => $notes,
                
                // Metadata
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Handle initial funding if partner is selected
            if ($request->filled('funding_partner_id') && $request->filled('funding_amount') && $request->funding_amount > 0) {
                $partner = Partner::find($request->funding_partner_id);
                if ($partner) {
                    // Record partner contribution
                    $partnerTransaction = $partner->addContribution(
                        $request->funding_amount,
                        'INV-' . strtoupper(uniqid()),
                        "Investment funding for {$investment->name}"
                    );

                    // Update investment funding
                    $investment->total_funding_raised = $request->funding_amount;
                    $investment->funding_partners = [[
                        'partner_id' => $partner->id,
                        'amount' => $request->funding_amount,
                        'date' => now()->toDateString(),
                        'transaction_id' => $partnerTransaction->id
                    ]];
                    $investment->save();

                    // Create disbursement
                    $investment->fundDisbursement(
                        $request->funding_amount,
                        'partner',
                        $partnerTransaction->id
                    );
                }
            }

            DB::commit();

            \Log::info('Investment created successfully:', ['id' => $investment->id]);

            return response()->json([
                'success' => true,
                'message' => 'Investment created successfully.',
                'investment' => $investment->fresh()
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
        $investment->load(['creator', 'updater', 'disbursements', 'repayments', 'partnerTransactions']);
        
        $data = [
            'id' => $investment->id,
            'name' => $investment->name,
            'type' => $investment->type,
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
            'transactions' => $investment->disbursements->map(function($d) {
                return ['type' => 'disbursement', 'date' => $d->disburse_date->format('Y-m-d'), 'amount' => $d->amount, 'reference' => $d->transaction];
            })->merge($investment->repayments->map(function($r) {
                return ['type' => 'repayment', 'date' => $r->repayment_date->format('Y-m-d'), 'amount' => $r->amount, 'reference' => $r->transaction];
            }))->sortBy('date')->values(),
        ];

        return view('investments.show', compact('investment', 'data'));
    }

    /**
     * Update the specified investment.
     */
    public function update(Request $request, Investment $investment)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:commodity,equity,bond,real_estate,startup,infrastructure,technology,agriculture,energy,other',
            'country' => 'sometimes|required|string|max:100',
            'initial_amount' => 'sometimes|required|numeric|min:0',
            'current_value' => 'sometimes|required|numeric|min:0',
            'expected_return' => 'nullable|numeric|min:0|max:100',
            'purchase_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|string|in:pipeline,due_diligence,active,matured,liquidated,write_off',
            'stage' => 'nullable|string|in:ideation,seed,startup,growth,expansion,mature',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $investment->update(array_merge(
                $request->only([
                    'name', 'type', 'sector', 'sub_sector', 'country', 'region', 'city',
                    'company_name', 'registration_number', 'incorporation_date', 'legal_structure',
                    'ebitda_pre_investment', 'revenue_pre_investment', 'net_profit_pre_investment',
                    'total_assets_pre_investment', 'total_liabilities_pre_investment',
                    'initial_amount', 'current_value', 'expected_return',
                    'purchase_date', 'maturity_date', 'risk_rating', 'status', 'stage'
                ]),
                ['updated_by' => auth()->id()]
            ));

            // Add note if provided
            if ($request->filled('notes')) {
                $investment->addNote($request->notes, 'update');
            }

            // Add update if provided
            if ($request->filled('update_content')) {
                $investment->addUpdate($request->update_content);
            }

            return response()->json([
                'success' => true,
                'message' => 'Investment updated successfully.',
                'investment' => $investment->fresh()
            ]);

        } catch (\Exception $e) {
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
            $investment->delete();
            return response()->json([
                'success' => true,
                'message' => 'Investment deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete investment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a note to an investment.
     */
    public function addNote(Request $request, Investment $investment)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'category' => 'required|string|in:research,due_diligence,meeting,financial,legal,risk,milestone,general'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $investment->addNote($request->content, $request->category);
            
            return response()->json([
                'success' => true,
                'message' => 'Note added successfully.',
                'notes' => $investment->notes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a milestone to an investment.
     */
    public function addMilestone(Request $request, Investment $investment)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'date' => 'required|date',
            'status' => 'required|string|in:pending,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $investment->addMilestone($request->description, $request->date, $request->status);
            
            return response()->json([
                'success' => true,
                'message' => 'Milestone added successfully.',
                'milestones' => $investment->milestones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add milestone: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add partner funding to an investment.
     */
    public function addFunding(Request $request, Investment $investment)
    {
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required|exists:partners,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $partner = Partner::find($request->partner_id);
            
            // Record partner contribution
            $partnerTransaction = $partner->addContribution(
                $request->amount,
                'INV-FUND-' . strtoupper(uniqid()),
                "Investment funding for {$investment->name}"
            );

            // Update investment funding
            $fundingPartners = $investment->funding_partners ?? [];
            $fundingPartners[] = [
                'partner_id' => $partner->id,
                'amount' => $request->amount,
                'date' => now()->toDateString(),
                'transaction_id' => $partnerTransaction->id
            ];
            $investment->funding_partners = $fundingPartners;
            $investment->total_funding_raised += $request->amount;
            $investment->save();

            // Create disbursement
            $investment->fundDisbursement(
                $request->amount,
                'partner',
                $partnerTransaction->id
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Partner funding added successfully.',
                'total_funding_raised' => $investment->total_funding_raised,
                'funding_partners' => $investment->funding_partners
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add funding: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get investment data for the table.
     */
    public function getData(Request $request)
    {
        $query = Investment::query();

        // Apply filters
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
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('sector', 'LIKE', "%{$search}%");
            });
        }

        $investments = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($investment) {
                return [
                    'id' => $investment->id,
                    'name' => $investment->name,
                    'type' => $investment->type,
                    'sector' => $investment->sector,
                    'country' => $investment->country,
                    'status' => $investment->status,
                    'stage' => $investment->stage,
                    'initial_amount' => $investment->initial_amount,
                    'current_value' => $investment->current_value,
                    'expected_return' => $investment->expected_return,
                    'return_percentage' => $investment->return_percentage,
                    'purchase_date' => $investment->purchase_date?->format('Y-m-d'),
                    'total_funding_raised' => $investment->total_funding_raised,
                    'total_returns' => $investment->total_returns,
                    'net_return' => $investment->net_return,
                ];
            });

        return response()->json([
            'data' => $investments,
            'count' => $investments->count()
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
            'by_type' => $investments->groupBy('type')->map->count(),
            'by_country' => $investments->groupBy('country')->map->count(),
            'by_status' => $investments->groupBy('status')->map->count(),
        ]);
    }
}