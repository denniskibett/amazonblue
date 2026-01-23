<?php

namespace App\Http\Controllers;

use App\Models\Repayment;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // Add this for unique validation

class RepaymentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Base query
        $query = Repayment::with(['loan.borrower'])->latest();
        
        // Filter based on user role
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
        
        // Authorization check
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
        $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:1',
            'transaction' => [
                'required',
                'string',
                'max:255',
                Rule::unique('repayments')->where(function ($query) use ($request) {
                    return $query->where('loan_id', $request->loan_id);
                })
            ],
            'repayment_date' => 'required|date',
        ], [
            'transaction.unique' => 'This transaction reference has already been used for this loan.'
        ]);
    
        $loan = Loan::with('borrower')->findOrFail($request->loan_id);
        $user = auth()->user();
        
        // Authorization check
        if ($user->role === 'borrower' && $loan->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        if ($user->role === 'broker' && $loan->broker_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
    
        Repayment::create($request->all());
        $loan->updateStatusIfNeeded();
    
        return redirect()->route('users.loans.show', [
            'user' => $loan->user_id, 
            'loan' => $loan->id
        ])->with('success', 'Repayment recorded successfully.');
    }

    public function show(Repayment $repayment)
    {
        // Simple authorization check
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
        // Simple authorization check - only brokers and admin can edit
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

    public function update(Request $request, Repayment $repayment)
    {
        // Simple authorization check - only brokers and admin can update
        $user = auth()->user();
        $loan = $repayment->loan;
        
        if ($user->role === 'broker' && $loan->broker_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        if (!in_array($user->role, ['admin', 'broker'])) {
            abort(403, 'Unauthorized action.');
        }
        
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'transaction' => [
                'required',
                'string',
                Rule::unique('repayments')->ignore($repayment->id)->where(function ($query) use ($repayment) {
                    return $query->where('loan_id', $repayment->loan_id);
                })
            ],
            'repayment_date' => 'required|date',
        ], [
            'transaction.unique' => 'This transaction reference has already been used for this loan.'
        ]);

        $repayment->update($request->all());

        return redirect()->route('repayments.index')->with('success', 'Repayment updated.');
    }

    public function destroy(Repayment $repayment)
    {
        // Simple authorization check - only brokers and admin can delete
        $user = auth()->user();
        $loan = $repayment->loan;
        
        if ($user->role === 'broker' && $loan->broker_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        if (!in_array($user->role, ['admin', 'broker'])) {
            abort(403, 'Unauthorized action.');
        }
        
        $repayment->delete();
        return back()->with('success', 'Repayment deleted.');
    }
}