<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Services\DisbursementService;
use App\Models\Investment;
use App\Models\InvestmentFunding;   
use App\Models\Partner;
use Illuminate\Http\Request;

class DisbursementController extends Controller
{
    protected $disbursementService;

    public function __construct(DisbursementService $disbursementService)
    {
        $this->disbursementService = $disbursementService;
    }

    public function index()
    {
        if (auth()->user()->hasAnyRole([...\App\Models\User::BACK_OFFICE_ROLES, 'teller'])) {
            $disbursements = Disbursement::with(['loan.user', 'loanCycle'])->get();
            return view('disbursements.index', compact('disbursements'));
        } else {
            $loans = auth()->user()->loans()->with('disbursements')->get();
            return view('disbursements.index', compact('loans'));
        }
    }

    public function show($id)
    {
        $disbursement = Disbursement::with(['loan', 'loanCycle'])->findOrFail($id);
        return view('disbursements.show', compact('disbursement'));
    }

    public function create(Request $request)
    {
        $loan = Loan::findOrFail($request->loan_id);
        $loans = Loan::all();
        return view('disbursements.create', compact('loans', 'loan'));
    }

    public function store(Request $request)
    {
        // every disbursement is cash to a borrower on a loan; investment-only draws (loan_id NULL) are refused
        if ($request->filled('investment_id') && !$request->filled('loan_id')) {
            return response()->json([
                'message' => 'Choose the loan this disbursement is for. A disbursement from an investment must be linked to a loan.',
                'errors' => ['loan_id' => ['A disbursement from an investment must be linked to a loan.']],
            ], 422);
        }

        $rules = [
            'amount' => 'required|numeric|min:0.01',
            'disburse_date' => 'required|date',
            'transaction' => 'required|string|max:255',
            'mode' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'funding_source' => 'nullable|in:internal,partner,mixed',
            'kind' => ['nullable', \Illuminate\Validation\Rule::in(\App\Services\CustomerWallets::KINDS)],
            'investment_id' => 'nullable|exists:investments,id',
            'partner_transaction_id' => 'nullable|exists:partner_transactions,id',
        ];

        $rules['loan_id'] = 'required|exists:loans,id';
        $rules['loan_cycle_id'] = 'nullable|exists:loan_cycles,id';

        $validated = $request->validate($rules, [
            'loan_id.required' => 'Choose the loan this disbursement is for.',
        ]);

        // ============ AUTO-DETECT FUNDING SOURCE ============
        if (empty($validated['funding_source'])) {
            if (!empty($validated['partner_transaction_id'])) {
                $validated['funding_source'] = 'partner';
            } elseif (!empty($validated['investment_id'])) {
                $investment = Investment::find($validated['investment_id']);
                $validated['funding_source'] = ($investment && !empty($investment->funding_partners))
                    ? 'partner'
                    : 'internal';
            } else {
                $validated['funding_source'] = 'internal';
            }
        }

        // ============ DEFAULT MISSING investment_id TO HOUSE PRINCIPAL ============
        if (empty($validated['investment_id']) && ($validated['funding_source'] ?? 'internal') === 'internal') {
            $houseInvestment = Investment::firstOrCreate(
                ['name' => 'General Lending Book'],
                [
                    'type' => 'other',
                    'facility_type' => 'long_term',
                    'country' => 'Kenya',
                    'initial_amount' => 0,
                    'current_value' => 0,
                    'expected_return' => 0,
                    'purchase_date' => now()->toDateString(),
                    'status' => 'active',
                    'funding_partners' => [],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            $validated['investment_id'] = $houseInvestment->id;
            $validated['funding_source'] = 'internal';
        }

        // ============ AUTO-LINK FUNDING ROW IF MISSING ============
        if (!empty($validated['investment_id'])) {
            $investment = Investment::find($validated['investment_id']);

            if ($investment && $investment->fundings()->count() === 0) {
                $housePrincipal = \App\Models\Partner::where('is_house_account', true)
                    ->where('account_type', 'principal')
                    ->first();

                if ($housePrincipal) {
                    \App\Models\InvestmentFunding::create([
                        'investment_id' => $investment->id,
                        'partner_id' => $housePrincipal->id,
                        'amount_committed' => $investment->initial_amount ?: 0,
                        'amount_disbursed' => 0,
                        'amount_returned' => 0,
                        'status' => 'active',
                        'notes' => 'Auto-linked to House Principal (fallback).',
                    ]);
                }
            }
        }

        // ============ LOAN DISBURSEMENT ============
        $loan = Loan::find($validated['loan_id']);
        $cycle = $loan->getCurrentCycle();

        // no draw beyond what partners committed to the investment (house lines are not capped)
        $investment = !empty($validated['investment_id']) ? Investment::find($validated['investment_id']) : null;
        $fundingPartnerId = !empty($validated['partner_transaction_id'])
            ? \App\Models\PartnerTransaction::find($validated['partner_transaction_id'])?->partner_id
            : null;
        try {
            $investment?->assertCanDisburse((float) $validated['amount'], $fundingPartnerId);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['amount' => [$e->getMessage()]]], 422);
        }

        // disbursement, its investment link and the funding draw succeed or fail together
        $disbursement = \Illuminate\Support\Facades\DB::transaction(function () use ($loan, $validated, $cycle, $investment, $fundingPartnerId) {
            $disbursement = $this->disbursementService->createDisbursement($loan, $validated, $cycle);

            // Attach investment_id and funding
            if ($disbursement && $investment) {
                $disbursement->update([
                    'investment_id' => $investment->id,
                    'partner_transaction_id' => $validated['partner_transaction_id'] ?? $disbursement->partner_transaction_id,
                    'funding_source' => $validated['funding_source'],
                ]);

                // Track against pivot
                $investment->recordFundingDisbursement((float) $validated['amount'], $fundingPartnerId);
            }

            return $disbursement;
        });

        // Update loan status if approved
        if ($loan->status === 'approved') {
            $loan->status = 'disbursed';
            $loan->save();
        }

        return response()->json([
            'message' => 'Disbursement created successfully!',
            'data' => $disbursement->fresh()
        ], 201);
    }

    public function update(Request $request, Disbursement $disbursement)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'disburse_date' => 'required|date',
            'transaction' => 'required|string|max:255',
            'mode' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'investment_id' => 'nullable|exists:investments,id',
            'partner_transaction_id' => 'nullable|exists:partner_transactions,id',
            'funding_source' => 'nullable|in:internal,partner,mixed',
            'kind' => ['nullable', \Illuminate\Validation\Rule::in(\App\Services\CustomerWallets::KINDS)],
        ]);

        // Recalculate processing fee for loan disbursements
        if ($disbursement->loan_id) {
            $loan = $disbursement->loan;
            $processingFeeRate = $loan->processing_fee_rate ?? 0;
            $validated['processing_fee'] = ($processingFeeRate / 100) * $validated['amount'];
            $validated['net_amount'] = $validated['amount'] - $validated['processing_fee'];
        }

        $disbursement->update($validated);

        return response()->json([
            'message' => 'Disbursement updated successfully!',
            'data' => $disbursement->fresh()
        ], 200);
    }


    public function destroy($id)
    {
        $disbursement = Disbursement::findOrFail($id);
        
        // Reverse the processing fee from loan total
        $loan = $disbursement->loan;
        $loan->total_processing_fees = max(0, ($loan->total_processing_fees ?? 0) - $disbursement->processing_fee);
        $loan->save();

        $disbursement->delete();

        return response()->json([
            'message' => 'Disbursement deleted successfully!'
        ], 200);
    }

    public function edit(Disbursement $disbursement)
    {
        $disbursement->load('loan');
        return view('disbursements.edit', compact('disbursement'));
    }
}