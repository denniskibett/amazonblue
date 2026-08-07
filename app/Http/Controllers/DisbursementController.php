<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Services\DisbursementService;
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
        if (auth()->user()->role === 'admin') {
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
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:0.01',
            'disburse_date' => 'required|date',
            'transaction' => 'required|string|max:255',
            'mode' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $loan = Loan::find($validated['loan_id']);
        $cycle = $loan->getCurrentCycle();

        $disbursement = $this->disbursementService->createDisbursement($loan, $validated, $cycle);

        // Update loan status if approved
        if ($loan->status === 'approved') {
            $loan->status = 'disbursed';
            $loan->save();
        }

        return response()->json([
            'message' => 'Disbursement created successfully!',
            'data' => $disbursement
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
        ]);

        // Recalculate processing fee
        $loan = $disbursement->loan;
        $processingFeeRate = $loan->processing_fee_rate ?? 0;
        $validated['processing_fee'] = ($processingFeeRate / 100) * $validated['amount'];
        $validated['net_amount'] = $validated['amount'] - $validated['processing_fee'];

        $disbursement->update($validated);

        return response()->json([
            'message' => 'Disbursement updated successfully!',
            'data' => $disbursement
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