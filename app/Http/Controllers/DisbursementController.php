<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Loan;
use Illuminate\Http\Request;

class DisbursementController extends Controller
{
    public function index()
    {
        if (auth()->user()->role === 'admin') {
            $disbursements = Disbursement::with(['loan.user'])->get();
            return view('disbursements.index', compact('disbursements'));
        } else {
            $loans = auth()->user()->loans()->with('disbursements')->get();
            return view('disbursements.index', compact('loans'));
        }
    }

    public function show($id)
    {
        $disbursement = Disbursement::findOrFail($id);
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
        ]);

        $disbursement = Disbursement::create($validated);

        // Update loan status if approved
        $loan = Loan::find($validated['loan_id']);
        if ($loan && $loan->status === 'approved') {
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
        ]);

        $disbursement->update($validated);

        return response()->json([
            'message' => 'Disbursement updated successfully!',
            'data' => $disbursement
        ], 200);
    }

    public function destroy($id)
    {
        $disbursement = Disbursement::findOrFail($id);
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