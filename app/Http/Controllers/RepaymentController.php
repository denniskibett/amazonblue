<?php

namespace App\Http\Controllers;

use App\Services\CustomerWallets;
use App\Models\Repayment;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\RepaymentService;
use Carbon\Carbon;
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
        
        if ($user->hasRole('broker')) {
            // loans have no broker_id: a broker sees the repayments of the borrowers linked to them
            $broker = $user->broker()->first();
            $ids = $broker ? \App\Models\Borrower::where('broker_id', $broker->id)->pluck('user_id') : collect();
            $query->whereHas('loan', fn ($q) => $q->whereIn('user_id', $ids));
        } elseif ($user->hasRole('teller')) {
            $query->whereHas('loan', function($q) {
                $q->where('status', 'active');
            });
        } elseif ($user->hasRole('borrower')) {
            $query->whereHas('loan', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
        
        $repayments = $query->limit(1000)->get();
        return view('repayments.index', compact('repayments'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->can('create repayments'), 403, 'You are not allowed to record or change repayments.');
        $user = auth()->user();
        $loan_id = $request->get('loan_id');
        $loan = Loan::with('borrower')->findOrFail($loan_id);
        
        if ($user->hasRole('borrower') && $loan->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        if ($user->hasRole('broker') && $loan->broker_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('repayments.create', compact('loan'));
    }
    

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('create repayments'), 403, 'You are not allowed to record or change repayments.');
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            // the cycle must belong to this loan (G3; the UI offers only the loan's cycles, the server checks too)
            'loan_cycle_id' => ['required', \Illuminate\Validation\Rule::exists('loan_cycles', 'id')->where('loan_id', (int) $request->input('loan_id'))],
            'amount' => 'required|numeric|min:0',
            'repayment_date' => 'required|date',
            'transaction' => 'nullable|string',
            'mode' => 'nullable|string',
            // what the row is; left out, it is read from the reference as before (Repayment::booted)
            'kind' => ['nullable', \Illuminate\Validation\Rule::in(\App\Services\CustomerWallets::KINDS)],
        ]);

        // Create repayment
        $repayment = Repayment::create($validated);

        // v2 (Stage 4 Phase 2): status follows the cycle balance, never totals of all repayments
        if (\App\Services\LoanCalculator::v2()) {
            \App\Services\Loans\LoanStatus::apply(Loan::findOrFail($validated['loan_id']));

            return response()->json(['success' => true, 'message' => 'Repayment created successfully.', 'repayment' => $repayment]);
        }

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
        abort_unless(auth()->user()->can('edit repayments'), 403, 'You are not allowed to record or change repayments.');
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'repayment_date' => 'required|date',
            'transaction' => 'required|string|max:255|unique:repayments,transaction,' . $repayment->id . ',id,loan_id,' . $repayment->loan_id,
            'mode' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'kind' => ['nullable', \Illuminate\Validation\Rule::in(\App\Services\CustomerWallets::KINDS)],
        ]);

        // Recalculate processing fee
        $loan = $repayment->loan;
        $processingFeeRate = $loan->processing_fee_rate ?? 0;
        $validated['processing_fee'] = ($processingFeeRate / 100) * $validated['amount'];
        $validated['net_amount'] = $validated['amount'] - $validated['processing_fee'];

        $repayment->update($validated);
        if (\App\Services\LoanCalculator::v2()) {
            \App\Services\Loans\LoanStatus::apply($repayment->loan);
        }

        return response()->json([
            'message' => 'Repayment updated successfully!',
            'data' => $repayment
        ], 200);
    }

    public function show(Repayment $repayment)
    {
        $user = auth()->user();
        $loan = $repayment->loan;
        
        if ($user->hasRole('borrower') && $loan->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        if ($user->hasRole('broker') && $loan->broker_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('repayments.show', compact('repayment'));
    }

    public function edit(Repayment $repayment)
    {
        abort_unless(auth()->user()->can('edit repayments'), 403, 'You are not allowed to record or change repayments.');
        $user = auth()->user();
        $loan = $repayment->loan;
        
        if ($user->hasRole('broker') && $loan->broker_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
        
        if (!$user->hasAnyRole(['admin', 'broker'])) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('repayments.edit', compact('repayment'));
    }

    public function destroy(Repayment $repayment)
    {
        abort_unless(auth()->user()->can('delete repayments'), 403, 'You are not allowed to record or change repayments.');
        $user = auth()->user();
        $loan = $repayment->loan;
        
        // with maker-checker on, the permission plus a second person replaces the admin-role check (Stage 4 Phase 5)
        if (! \App\Services\Approvals::enabled()) {
            if ($user->hasRole('broker') && $loan->broker_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
            if (!$user->hasAnyRole(['admin', 'broker'])) {
                abort(403, 'Unauthorized action.');
            }
        }

        if ($repayment->reversal()->exists()) {
            return response()->json(['message' => 'Repayment #' . $repayment->id . ' has already been reversed.'], 422);
        }
        if (config('lending.ledger.reversals') && $repayment->reversal_of_id !== null && ! $repayment->created_at?->isToday()) {
            return response()->json(['message' => 'A reversal can only be undone on the day it was entered. Record the payment again instead.'], 422);
        }

        $loanId = $repayment->loan_id;
        $cycleId = $repayment->loan_cycle_id;

        // Entered before today: post a reversal and keep the original (switch lending.ledger.reversals).
        // Entered today, or with the switch off: delete it as before.
        if (config('lending.ledger.reversals') && $repayment->reversal_of_id === null && ! $repayment->created_at?->isToday()) {
            $reason = (string) (request('reason') ?: 'Reversed by ' . $user->name);
            $approvals = app(\App\Services\Approvals::class);
            if ($approvals->needsSecond($user, 'reversal', (float) $repayment->amount)) {
                $approvals->request($user, 'reversal', 'repayments', $repayment->id, (float) $repayment->amount, ['reason' => $reason]);

                return response()->json(['message' => 'Reversal sent for a second approval. Nothing changes until someone else approves it.',
                    'pending_approval' => true, 'reversal_id' => null], 202);
            }
            $reversal = app(\App\Services\Loans\Reversal::class)->reverse($repayment, $reason);
        } else {
            $repayment->delete();
        }

        if (\App\Services\LoanCalculator::v2()) {
            \App\Services\Loans\LoanStatus::apply(Loan::findOrFail($loanId));

            return response()->json([
                'message' => isset($reversal) ? 'Repayment reversed (reversal #' . $reversal->id . ').' : 'Repayment deleted successfully!',
                'reversal_id' => isset($reversal) ? $reversal->id : null,
            ], 200);
        }
        
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
                    $loan->status = Loan::STATUS_DISBURSED;
                }
                $loan->save();
            }
        }
        
        return response()->json([
            'message' => isset($reversal) ? 'Repayment reversed (reversal #' . $reversal->id . ').' : 'Repayment deleted successfully!',
            'reversal_id' => isset($reversal) ? $reversal->id : null,
        ], 200);
    }

}