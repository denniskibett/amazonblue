<?php

namespace App\Http\Controllers;

use App\Models\Repayment;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\RepaymentService;
use Illuminate\Http\Request;

class RepaymentController extends Controller
{
    protected $repaymentService;

    public function __construct(RepaymentService $repaymentService)
    {
        $this->repaymentService = $repaymentService;
    }

    public function index()
    {
        $user = auth()->user();
        
        $query = Repayment::with(['loan.borrower', 'loanCycle'])->latest();
        
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
            'loan_cycle_id' => 'required|exists:loan_cycles,id',
            'amount' => 'required|numeric|min:0',
            'repayment_date' => 'required|date',
            'transaction' => 'nullable|string',
            'mode' => 'nullable|string',
        ]);

        // Create repayment
        $repayment = Repayment::create($validated);
        
        // ============ UPDATE THE CYCLE ============
        $cycle = LoanCycle::find($validated['loan_cycle_id']);
        if ($cycle) {
            $cycleRepayments = Repayment::where('loan_cycle_id', $cycle->id)->sum('amount');
            $outstanding = $cycle->new_balance - $cycleRepayments;
            
            if ($outstanding <= 0) {
                $cycle->update(['status' => 'completed']);
            }
        }
        
        // ============ UPDATE THE LOAN STATUS DIRECTLY ============
        $loan = Loan::find($validated['loan_id']);
        if ($loan) {
            // Check if all cycles are completed
            $hasActiveCycles = $loan->cycles()
                ->where('status', 'active')
                ->exists();
            
            // Check if total repayments cover the loan amount
            $totalRepaid = $loan->repayments()->sum('amount');
            $totalLoanAmount = $loan->amount;
            
            // If no active cycles OR total repayments >= loan amount
            if (!$hasActiveCycles || $totalRepaid >= $totalLoanAmount) {
                $loan->status = Loan::STATUS_REPAID;
                $loan->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Repayment created successfully.',
            'repayment' => $repayment
        ]);
    }

    public function update(Request $request, Repayment $repayment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'repayment_date' => 'required|date',
            'transaction' => 'required|string|max:255|unique:repayments,transaction,' . $repayment->id . ',id,loan_id,' . $repayment->loan_id,
            'mode' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // Recalculate processing fee
        $loan = $repayment->loan;
        $processingFeeRate = $loan->processing_fee_rate ?? 0;
        $validated['processing_fee'] = ($processingFeeRate / 100) * $validated['amount'];
        $validated['net_amount'] = $validated['amount'] - $validated['processing_fee'];

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
        
        $loanId = $repayment->loan_id;
        $cycleId = $repayment->loan_cycle_id;
        
        // Delete the repayment
        $repayment->delete();
        
        // ============ UPDATE THE CYCLE STATUS ============
        $cycle = LoanCycle::find($cycleId);
        if ($cycle) {
            $cycleRepayments = Repayment::where('loan_cycle_id', $cycleId)->sum('amount');
            $outstanding = $cycle->new_balance - $cycleRepayments;
            
            if ($outstanding > 0) {
                $cycle->update(['status' => 'active']);
            }
        }
        
        // ============ UPDATE THE LOAN STATUS ============
        $loan = Loan::find($loanId);
        if ($loan && $loan->status === Loan::STATUS_REPAID) {
            // Recalculate - if not fully repaid anymore, revert status
            $totalRepaid = $loan->repayments()->sum('amount');
            $totalLoanAmount = $loan->amount;
            
            if ($totalRepaid < $totalLoanAmount) {
                $dueDate = $loan->getDueDate();
                if ($dueDate && Carbon::now()->gt($dueDate)) {
                    $loan->status = Loan::STATUS_OVERDUE;
                } else {
                    $loan->status = Loan::STATUS_ACTIVE;
                }
                $loan->save();
            }
        }
        
        return response()->json([
            'message' => 'Repayment deleted successfully!'
        ], 200);
    }
}