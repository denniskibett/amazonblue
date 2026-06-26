<?php

namespace App\Http\Controllers;

use App\Models\Repayment;
use App\Models\Loan;
use Illuminate\Http\Request;

class RepaymentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $query = Repayment::with(['loan.borrower'])->latest();
        
        if ($user->role === 'broker') {
            $query->whereHas('loan', function($q) use ($user) {
                $q->where('broker_id', $user->id);
            });
        } elseif ($user->role === 'teller') {
            $query->whereHas('loan', function($q) {
                $q->where('status', 'active');
            });
        } elseif ($user->role === 'borrower') {
            $query->whereHas('loan', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
        
        $repayments = $query->paginate(10);
        return view('repayments.index', compact('repayments'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $loan_id = $request->get('loan_id');
        $loan = Loan::with('borrower')->findOrFail($loan_id);
        
        if ($user->role === 'borrower' && $loan->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        if ($user->role === 'broker' && $loan->broker_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('repayments.create', compact('loan'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:0.01',
            'repayment_date' => 'required|date',
            'transaction' => 'required|string|max:255|unique:repayments,transaction,NULL,id,loan_id,' . $request->loan_id,
            'mode' => 'nullable|string|max:100',
        ]);

        $repayment = Repayment::create($validated);

        // Check if loan is fully repaid
        $loan = Loan::find($validated['loan_id']);
        $totalRepayments = $loan->repayments()->sum('amount');
        $totalDue = $loan->amount + ($loan->interest ?? 0) + ($loan->penalty_amount ?? 0);
        
        if ($totalRepayments >= $totalDue) {
            $loan->status = 'repaid';
            $loan->save();
        }

        return response()->json([
            'message' => 'Repayment created successfully!',
            'data' => $repayment
        ], 201);
    }

    public function update(Request $request, Repayment $repayment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'repayment_date' => 'required|date',
            'transaction' => 'required|string|max:255|unique:repayments,transaction,' . $repayment->id . ',id,loan_id,' . $repayment->loan_id,
            'mode' => 'nullable|string|max:100',
        ]);

        $repayment->update($validated);

        return response()->json([
            'message' => 'Repayment updated successfully!',
            'data' => $repayment
        ], 200);
    }

    public function show(Repayment $repayment)
    {
        $user = auth()->user();
        $loan = $repayment->loan;
        
        if ($user->role === 'borrower' && $loan->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        if ($user->role === 'broker' && $loan->broker_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('repayments.show', compact('repayment'));
    }

    public function edit(Repayment $repayment)
    {
        $user = auth()->user();
        $loan = $repayment->loan;
        
        if ($user->role === 'broker' && $loan->broker_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        if (!in_array($user->role, ['admin', 'broker'])) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('repayments.edit', compact('repayment'));
    }

    public function destroy(Repayment $repayment)
    {
        $user = auth()->user();
        $loan = $repayment->loan;
        
        if ($user->role === 'broker' && $loan->broker_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        if (!in_array($user->role, ['admin', 'broker'])) {
            abort(403, 'Unauthorized action.');
        }
        
        $repayment->delete();
        
        return response()->json([
            'message' => 'Repayment deleted successfully!'
        ], 200);
    }
}