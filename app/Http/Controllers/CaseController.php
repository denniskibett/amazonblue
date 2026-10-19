<?php

namespace App\Http\Controllers;

use App\Models\DebtRecoveryCase;
use App\Models\User;
use App\Models\Loan;
use App\Models\Borrower;
use App\Models\RecoveryAction;
use App\Models\RecoveryPaymentPlan;
use App\Models\RecoveryInstallment;
use App\Models\RecoveryStatus;
use App\Models\RecoveryPriority;
use App\Models\ActionType;
use App\Models\RecoveryCaseNote;
use App\Models\Repayment;
use App\Services\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CaseController extends Controller
{
    /**
     * Display a listing of recovery cases.
     */
    public function index(Request $request)
    {
        $query = DebtRecoveryCase::with(['user', 'status', 'priority', 'assignedTo']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $status = RecoveryStatus::where('slug', $request->status)->first();
            if ($status) {
                $query->where('status_id', $status->id);
            }
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority !== 'all') {
            $priority = RecoveryPriority::where('slug', $request->priority)->first();
            if ($priority) {
                $query->where('priority_id', $priority->id);
            }
        }

        // Filter by assigned officer
        if ($request->has('assigned_to') && $request->assigned_to !== 'all') {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Role-based filtering
        $user = Auth::user();
        if ($user->hasRole('borrower')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('broker')) {
            $broker = $user->broker;
            if ($broker) {
                $borrowerIds = Borrower::where('broker_id', $broker->id)->pluck('user_id');
                $query->whereIn('user_id', $borrowerIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Order
        $cases = $query->orderBy('priority_id', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Filter options
        $statuses = RecoveryStatus::all();
        $priorities = RecoveryPriority::all();
        $officers = User::role(['admin', 'manager', 'recovery-officer', 'teller'])->get();

        // Statistics
        $stats = [
            'total' => DebtRecoveryCase::count(),
            'open' => DebtRecoveryCase::open()->count(),
            'in_progress' => DebtRecoveryCase::whereHas('status', function($q) {
                $q->where('slug', 'in_progress');
            })->count(),
            'negotiation' => DebtRecoveryCase::whereHas('status', function($q) {
                $q->where('slug', 'negotiation');
            })->count(),
            'legal' => DebtRecoveryCase::whereHas('status', function($q) {
                $q->where('slug', 'legal');
            })->count(),
            'recovered' => DebtRecoveryCase::whereHas('status', function($q) {
                $q->where('slug', 'recovered');
            })->count(),
            'closed' => DebtRecoveryCase::whereHas('status', function($q) {
                $q->whereIn('slug', ['closed', 'written_off']);
            })->count(),
        ];

        // ============ GET DATA FOR MODAL ============
        
        // Get ALL borrowers (for manual case creation)
        $borrowers = User::borrowers()
            ->with(['borrower', 'loans' => function($q) {
                $q->whereIn('status', ['disbursed', 'approved', 'overdue', 'defaulted'])
                  ->with(['loanType', 'repayments']);
            }])
            ->get();

        // Get borrowers with NPL/overdue loans (for automated case creation)
        $nplBorrowers = User::borrowers()
            ->whereHas('loans', function($q) {
                $q->join('loan_types', 'loans.loan_type_id', '=', 'loan_types.id')
                  ->whereIn('loans.status', ['disbursed', 'approved', 'overdue', 'defaulted'])
                  ->where(function($sub) {
                      $sub->whereRaw('DATE_ADD(loans.borrow_date, INTERVAL loan_types.period DAY) < NOW()')
                          ->orWhere('loans.status', 'defaulted')
                          ->orWhere('loans.is_non_performing', true);
                  })
                  ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM repayments WHERE repayments.loan_id = loans.id) < loans.amount');
            })
            ->whereDoesntHave('debtRecoveryCases', function($q) {
                $q->whereHas('status', function($sq) {
                    $sq->whereIn('slug', ['open', 'in_progress', 'negotiation', 'legal']);
                });
            })
            ->with(['borrower', 'loans' => function($q) {
                $q->join('loan_types', 'loans.loan_type_id', '=', 'loan_types.id')
                  ->whereIn('loans.status', ['disbursed', 'approved', 'overdue', 'defaulted'])
                  ->where(function($sub) {
                      $sub->whereRaw('DATE_ADD(loans.borrow_date, INTERVAL loan_types.period DAY) < NOW()')
                          ->orWhere('loans.status', 'defaulted')
                          ->orWhere('loans.is_non_performing', true);
                  })
                  ->select('loans.*', 'loan_types.period', 'loan_types.unit', 'loan_types.interest_rate', 'loan_types.penalty_rate')
                  ->with(['repayments'])
                  ->orderBy('loans.borrow_date', 'desc');
            }])
            ->get();

        // Get action types for the create modal
        $actionTypes = ActionType::all();

        return view('cases.index', compact(
            'cases', 
            'statuses', 
            'priorities', 
            'officers', 
            'stats',
            'borrowers',
            'nplBorrowers',
            'actionTypes'
        ));
    }

    public function create(Request $request)
    {
        if (! Auth::user()->can('create recovery cases')) {
            abort(403, 'Unauthorized action.');
        }

        // Get ALL borrowers (for manual case creation)
        $borrowers = User::borrowers()
            ->with(['borrower', 'loans' => function($q) {
                $q->whereIn('status', ['disbursed', 'approved', 'overdue', 'defaulted']);
            }])
            ->get();

        // Get borrowers with NPL loans only (for automated case creation)
        $nplBorrowers = User::borrowers()
            ->whereHas('loans', function($q) {
                $q->where('is_non_performing', true)
                  ->orWhere('status', 'defaulted');
            })
            ->whereDoesntHave('debtRecoveryCases', function($q) {
                $q->whereHas('status', function($sq) {
                    $sq->whereIn('slug', ['open', 'in_progress', 'negotiation', 'legal']);
                });
            })
            ->with(['borrower', 'loans' => function($q) {
                $q->where('is_non_performing', true)
                  ->orWhere('status', 'defaulted');
            }])
            ->get();

        $statuses = RecoveryStatus::all();
        $priorities = RecoveryPriority::all();
        $officers = User::role(['admin', 'manager', 'recovery-officer', 'teller'])->get();
        $actionTypes = ActionType::all();

        return view('cases.create', compact(
            'borrowers', 
            'nplBorrowers', 
            'statuses', 
            'priorities', 
            'officers', 
            'actionTypes'
        ));
    }
    
    /**
     * Store a newly created recovery case.
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('create recovery cases')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'loan_id' => 'nullable|exists:loans,id',
            'total_debt_amount' => 'required|numeric|min:0.01',
            'principal_outstanding' => 'nullable|numeric|min:0',
            'interest_outstanding' => 'nullable|numeric|min:0',
            'penalty_outstanding' => 'nullable|numeric|min:0',
            'fees_outstanding' => 'nullable|numeric|min:0',
            'default_date' => 'required|date',
            'status_id' => 'required|exists:recovery_statuses,id',
            'priority_id' => 'required|exists:recovery_priorities,id',
            'assigned_to' => 'nullable|exists:users,id',
            'recovery_strategy' => 'nullable|string',
            'notes' => 'nullable|string',
            'initial_action_type' => 'nullable|exists:action_types,id',
            'initial_action_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if user already has an active recovery case
            $existingCase = DebtRecoveryCase::where('user_id', $request->user_id)
                ->whereHas('status', function($q) {
                    $q->whereIn('slug', ['open', 'in_progress', 'negotiation', 'legal']);
                })
                ->exists();

            if ($existingCase) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user already has an active recovery case.'
                ], 409);
            }

            // Get the user to generate case number
            $user = User::find($request->user_id);
            $caseNumber = $this->generateCaseNumber();

            // Calculate default date from loan if not provided
            $defaultDate = $request->default_date;
            $daysInDefault = 0;
            if ($request->loan_id) {
                $loan = Loan::find($request->loan_id);
                if ($loan && $loan->default_date) {
                    $defaultDate = $loan->default_date;
                    $daysInDefault = Carbon::parse($loan->default_date)->diffInDays(now());
                } elseif ($loan && $loan->days_overdue) {
                    $daysInDefault = $loan->days_overdue;
                    $defaultDate = now()->subDays($daysInDefault);
                }
            }

            $case = DebtRecoveryCase::create([
                'user_id' => $request->user_id,
                'loan_id' => $request->loan_id,
                'case_number' => $caseNumber,
                'total_debt_amount' => $request->total_debt_amount,
                'principal_outstanding' => $request->principal_outstanding ?? 0,
                'interest_outstanding' => $request->interest_outstanding ?? 0,
                'penalty_outstanding' => $request->penalty_outstanding ?? 0,
                'fees_outstanding' => $request->fees_outstanding ?? 0,
                'default_date' => $defaultDate ?? now(),
                'days_in_default' => $daysInDefault ?: Carbon::parse($defaultDate ?? now())->diffInDays(now()),
                'status_id' => $request->status_id,
                'priority_id' => $request->priority_id,
                'assigned_to' => $request->assigned_to,
                'recovery_strategy' => $request->recovery_strategy,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'recovery_officer' => $request->assigned_to ? User::find($request->assigned_to)?->name : null,
            ]);

            // Create initial action if provided
            if ($request->filled('initial_action_type')) {
                RecoveryAction::create([
                    'case_id' => $case->id,
                    'action_type_id' => $request->initial_action_type,
                    'action_date' => now(),
                    'performed_by' => Auth::id(),
                    'notes' => $request->initial_action_notes ?? 'Case created',
                    'outcome' => 'pending',
                ]);
            }

            // Create case note
            $noteMessage = 'Case created by ' . Auth::user()->name . ' for ' . ($user ? $user->name : 'Unknown');
            if ($request->loan_id) {
                $loan = Loan::find($request->loan_id);
                if ($loan && $loan->is_non_performing) {
                    $noteMessage .= ' from NPL loan #' . $loan->id . ' (' . ($loan->days_overdue ?? 0) . ' days overdue)';
                }
            }
            RecoveryCaseNote::create([
                'case_id' => $case->id,
                'note_type' => 'general',
                'note' => $noteMessage,
                'created_by' => Auth::id(),
            ]);

            // Return JSON response for AJAX requests
            return response()->json([
                'success' => true,
                'message' => 'Recovery case created successfully.',
                'case' => $case->load(['user', 'status', 'priority'])
            ]);

        } catch (\Exception $e) {
            Log::error('Recovery case creation failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create recovery case: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate default date from loan borrow date and loan type period
     */
    private function calculateDefaultDateFromLoan($loan)
    {
        if (!$loan || !$loan->borrow_date) {
            return null;
        }

        try {
            $borrowDate = Carbon::parse($loan->borrow_date);
            
            // If loan has a loan type with period, calculate due date
            if ($loan->loanType && $loan->loanType->period > 0) {
                $dueDate = $borrowDate->copy();
                switch ($loan->loanType->unit) {
                    case 'days':
                        $dueDate->addDays($loan->loanType->period);
                        break;
                    case 'weeks':
                        $dueDate->addWeeks($loan->loanType->period);
                        break;
                    case 'months':
                        $dueDate->addMonths($loan->loanType->period);
                        break;
                    case 'years':
                        $dueDate->addYears($loan->loanType->period);
                        break;
                    default:
                        $dueDate->addDays($loan->loanType->period);
                        break;
                }
                
                // If due date is in the past, default date is the due date
                if (now()->gt($dueDate)) {
                    return $dueDate->format('Y-m-d');
                }
                
                // If not overdue, use borrow date + 30 days as default
                return $borrowDate->addDays(30)->format('Y-m-d');
            }
            
            // Fallback: borrow date + 30 days
            return $borrowDate->addDays(30)->format('Y-m-d');
            
        } catch (\Exception $e) {
            Log::warning('Error calculating default date: ' . $e->getMessage());
            return null;
        }
    }

/**
 * Display the specified recovery case.
 */
public function show(string $id)
{
    $caseData = DebtRecoveryCase::with([
        'user',
        'user.borrower',
        'loan',
        'loan.loanType',
        'status',
        'priority',
        'assignedTo',
        'actions',
        'actions.actionType',
        'actions.performedBy',
        'paymentPlans',
        'paymentPlans.installments',
        'documents',
        'notes',
        'notes.createdBy',
        'legalProceedings',
        'legalDeadlines',
        'communications',
        'communications.communicationType',
    ])->findOrFail($id);

    // Check permission
    $user = Auth::user();
    if ($user->hasRole('borrower') && $caseData->user_id !== $user->id) {
        abort(403, 'Unauthorized action.');
    }
    if ($user->hasRole('broker')) {
        $broker = $user->broker;
        if ($broker) {
            $borrowerIds = Borrower::where('broker_id', $broker->id)->pluck('user_id');
            if (!$borrowerIds->contains($caseData->user_id)) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    // FIX: Ensure paymentPlans is a collection, not a string
    if (!($caseData->paymentPlans instanceof \Illuminate\Support\Collection) && is_string($caseData->paymentPlans)) {
        $decoded = json_decode($caseData->paymentPlans, true);
        if (is_array($decoded)) {
            $caseData->paymentPlans = collect($decoded);
        } else {
            $caseData->paymentPlans = collect();
        }
    }

    // Get NPL info if case is from a loan
    $nplInfo = null;
    if ($caseData->loan) {
        $recoveryStage = 'unknown';
        $stageLabel = 'Unknown';
        $stageColor = 'gray';
        
        try {
            if (method_exists($caseData->loan, 'getRecoveryStage')) {
                $recoveryStage = $caseData->loan->getRecoveryStage();
            } else {
                $daysOverdue = $caseData->loan->days_overdue ?? 0;
                $period = $caseData->loan->loanType->period ?? 30;
                $ratio = $daysOverdue / max(1, $period);
                
                if ($daysOverdue <= 0) {
                    $recoveryStage = 'current';
                } elseif ($ratio <= 0.5) {
                    $recoveryStage = 'early_overdue';
                } elseif ($ratio <= 1) {
                    $recoveryStage = 'overdue';
                } elseif ($ratio <= 2) {
                    $recoveryStage = 'serious_overdue';
                } else {
                    $recoveryStage = 'npl';
                }
            }
        } catch (\Exception $e) {
            $recoveryStage = 'unknown';
        }
        
        try {
            if (method_exists($caseData->loan, 'getRecoveryStageLabel')) {
                $stageLabel = $caseData->loan->getRecoveryStageLabel();
            } else {
                $stageMap = [
                    'current' => 'Current',
                    'early_overdue' => 'Early Overdue',
                    'overdue' => 'Overdue',
                    'serious_overdue' => 'Seriously Overdue',
                    'npl' => 'Non-Performing (NPL)',
                    'unknown' => 'Unknown',
                ];
                $stageLabel = $stageMap[$recoveryStage] ?? 'Unknown';
            }
        } catch (\Exception $e) {
            $stageLabel = 'Unknown';
        }
        
        try {
            if (method_exists($caseData->loan, 'getRecoveryStageColor')) {
                $stageColor = $caseData->loan->getRecoveryStageColor();
            } else {
                $colorMap = [
                    'current' => 'green',
                    'early_overdue' => 'yellow',
                    'overdue' => 'orange',
                    'serious_overdue' => 'orange',
                    'npl' => 'red',
                    'unknown' => 'gray',
                ];
                $stageColor = $colorMap[$recoveryStage] ?? 'gray';
            }
        } catch (\Exception $e) {
            $stageColor = 'gray';
        }
        
        $nplInfo = [
            'is_npl' => $caseData->loan->is_non_performing ?? false,
            'days_overdue' => $caseData->loan->days_overdue ?? 0,
            'threshold' => $caseData->loan->npl_trigger_threshold ?? 0,
            'default_date' => $caseData->loan->default_date,
            'recovery_stage' => $recoveryStage,
            'stage_label' => $stageLabel,
            'stage_color' => $stageColor,
        ];
    }

    // Get related data
    $actionTypes = ActionType::all();
    $statuses = RecoveryStatus::all();
    $priorities = RecoveryPriority::all();
    $officers = User::role(['admin', 'manager', 'recovery-officer', 'teller'])->get();

    // ============ ADD DATA FOR CASES MODAL ============
    // Get all borrowers for the modal - DO NOT add to with() array
    $borrowers = User::borrowers()
        ->with(['borrower', 'loans' => function($q) {
            $q->whereIn('status', ['disbursed', 'approved', 'overdue', 'defaulted'])
              ->with(['loanType', 'repayments']);
        }])
        ->get();

    // Build timeline manually
    $timeline = $this->getCaseTimeline($caseData);

    return view('cases.show', compact(
        'caseData',
        'actionTypes',
        'statuses',
        'priorities',
        'officers',
        'timeline',
        'nplInfo',
        'borrowers'  // <-- Pass to view
    ));
}

    /**
     * Show the form for editing the specified recovery case.
     */
    public function edit(string $id)
    {
        if (! Auth::user()->can('edit recovery cases')) {
            abort(403, 'Unauthorized action.');
        }

        $case = DebtRecoveryCase::with(['user', 'status', 'priority', 'assignedTo', 'loan'])->findOrFail($id);
        
        $statuses = RecoveryStatus::all();
        $priorities = RecoveryPriority::all();
        $officers = User::role(['admin', 'manager', 'recovery-officer', 'teller'])->get();

        return view('cases.edit', compact('case', 'statuses', 'priorities', 'officers'));
    }

    /**
     * Update the specified recovery case.
     */
    public function update(Request $request, string $id)
    {
        if (! Auth::user()->can('edit recovery cases')) {
            abort(403, 'Unauthorized action.');
        }

        $case = DebtRecoveryCase::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'total_debt_amount' => 'nullable|numeric|min:0',
            'principal_outstanding' => 'nullable|numeric|min:0',
            'interest_outstanding' => 'nullable|numeric|min:0',
            'penalty_outstanding' => 'nullable|numeric|min:0',
            'fees_outstanding' => 'nullable|numeric|min:0',
            'default_date' => 'nullable|date',
            'status_id' => 'nullable|exists:recovery_statuses,id',
            'priority_id' => 'nullable|exists:recovery_priorities,id',
            'assigned_to' => 'nullable|exists:users,id',
            'recovery_strategy' => 'nullable|string',
            'notes' => 'nullable|string',
            'last_contact_date' => 'nullable|date',
            'next_action_date' => 'nullable|date',
            'recovery_officer' => 'nullable|string|max:100',
            'recovery_contact' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Writing off has its own action (reason, amount, loan status), so the edit form cannot do it.
        if ($request->filled('status_id') && (int) $request->status_id !== (int) $case->status_id
            && RecoveryStatus::whereKey($request->status_id)->value('slug') === 'written_off') {
            $msg = 'Use "Write off" on the case page to write a case off.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'errors' => ['status_id' => [$msg]]], 422);
            }
            return redirect()->back()->withErrors(['status_id' => $msg])->withInput();
        }

        try {
            $oldStatus = $case->status->slug ?? 'unknown';

            $updateData = $request->only([
                'total_debt_amount',
                'principal_outstanding',
                'interest_outstanding',
                'penalty_outstanding',
                'fees_outstanding',
                'default_date',
                'status_id',
                'priority_id',
                'assigned_to',
                'recovery_strategy',
                'notes',
                'last_contact_date',
                'next_action_date',
                'recovery_officer',
                'recovery_contact',
            ]);

            if ($request->has('default_date') && $request->default_date) {
                $updateData['days_in_default'] = Carbon::parse($request->default_date)->diffInDays(now());
            }

            $case->update($updateData);

            // Update recovery officer name if assigned
            if ($request->has('assigned_to')) {
                $updateData['recovery_officer'] = $request->assigned_to ? User::find($request->assigned_to)?->name : null;
                $case->update(['recovery_officer' => $updateData['recovery_officer']]);
            }

            // Log status change
            if ($request->has('status_id') && $case->status) {
                $newStatus = $case->status->slug ?? 'unknown';
                if ($oldStatus !== $newStatus) {
                    RecoveryCaseNote::create([
                        'case_id' => $case->id,
                        'note_type' => 'alert',
                        'note' => "Status changed from '{$oldStatus}' to '{$newStatus}' by " . Auth::user()->name,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // If case is recovered, update associated loan
            if ($request->has('status_id')) {
                $newStatus = RecoveryStatus::find($request->status_id);
                if ($newStatus && $newStatus->slug === 'recovered' && $case->loan) {
                    $case->loan->update([
                        'status' => Loan::STATUS_REPAID,
                        'is_non_performing' => false,
                    ]);
                }
            }

            $case->updated_by = Auth::id();
            $case->save();

            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recovery case updated successfully.',
                    'case' => $case->load(['user', 'status', 'priority'])
                ]);
            }

            return redirect()->route('cases.show', $case)
                ->with('success', 'Recovery case updated successfully.');

        } catch (\Exception $e) {
            Log::error('Recovery case update failed: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update recovery case: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to update recovery case: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mark case as recovered.
     */
    public function markAsRecovered(string $id)
    {
        if (! Auth::user()->can('edit recovery cases')) {
            abort(403, 'Unauthorized action.');
        }

        $case = DebtRecoveryCase::findOrFail($id);

        try {
            $recoveredStatus = RecoveryStatus::where('slug', 'recovered')->first();
            if (!$recoveredStatus) {
                throw new \Exception('Recovered status not found.');
            }

            $case->update([
                'status_id' => $recoveredStatus->id,
                'updated_by' => Auth::id(),
            ]);

            // Update associated loan
            if ($case->loan) {
                $case->loan->update([
                    'status' => Loan::STATUS_REPAID,
                    'is_non_performing' => false,
                ]);
            }

            RecoveryCaseNote::create([
                'case_id' => $case->id,
                'note_type' => 'alert',
                'note' => "Case marked as recovered by " . Auth::user()->name,
                'created_by' => Auth::id(),
            ]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Case marked as recovered successfully.'
                ]);
            }

            return redirect()->route('cases.show', $case)
                ->with('success', 'Case marked as recovered successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to mark case as recovered: ' . $e->getMessage());
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark case as recovered: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to mark case as recovered: ' . $e->getMessage());
        }
    }

    /**
     * Mark case as written off.
     */
    public function markAsWrittenOff(Request $request, string $id)
    {
        if (! Auth::user()->can('write off loan')) {
            abort(403, 'Only admins can write off cases.');
        }

        $case = DebtRecoveryCase::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'write_off_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $writtenOffStatus = RecoveryStatus::where('slug', 'written_off')->first();
            if (!$writtenOffStatus) {
                throw new \Exception('Written off status not found.');
            }

            // A write-off is not a payment: no Repayment is created. The outstanding debt stays on the
            // case as the written-off amount and the loan is marked written_off (not repaid).
            DB::transaction(function () use ($case, $request, $writtenOffStatus) {
                $amount = (float) $case->total_debt_amount;
                $reason = $request->write_off_reason;
                $oldLoanStatus = $case->loan->status ?? null;
                $oldCaseStatus = $case->status_id;
                $summary = 'WRITTEN OFF ' . number_format($amount, 2) . ' on ' . now()->toDateString()
                    . ' by ' . Auth::user()->name . '. Reason: ' . $reason;

                $case->update([
                    'status_id' => $writtenOffStatus->id,
                    'notes' => ($case->notes ? $case->notes . "\n\n" : '') . $summary,
                    'updated_by' => Auth::id(),
                ]);

                if ($case->loan) {
                    $case->loan->update(['status' => Loan::STATUS_WRITTEN_OFF]);
                }

                RecoveryCaseNote::create([
                    'case_id' => $case->id,
                    'note_type' => 'alert',
                    'note' => $summary,
                    'created_by' => Auth::id(),
                ]);

                $otherType = ActionType::where('slug', 'other')->value('id');
                if ($otherType) {
                    RecoveryAction::create([
                        'case_id' => $case->id,
                        'action_type_id' => $otherType,
                        'action_date' => now(),
                        'performed_by' => Auth::id(),
                        'outcome' => 'failed',
                        'amount_collected' => 0,
                        'notes' => $summary,
                    ]);
                }

                AuditTrail::record('debt_recovery_cases', $case->id, 'update',
                    ['status_id' => $oldCaseStatus, 'loan_status' => $oldLoanStatus],
                    ['event' => 'write_off', 'status_id' => $writtenOffStatus->id, 'loan_id' => $case->loan_id,
                     'loan_status' => Loan::STATUS_WRITTEN_OFF, 'written_off_amount' => $amount, 'reason' => $reason]);
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Case written off successfully.'
                ]);
            }

            return redirect()->route('cases.show', $case)
                ->with('success', 'Case written off successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to write off case: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to write off case: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to write off case: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified recovery case.
     */
    public function destroy(string $id)
    {
        if (! Auth::user()->can('delete recovery cases')) {
            abort(403, 'Only admins can delete cases.');
        }

        $case = DebtRecoveryCase::findOrFail($id);

        try {
            $case->delete();

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recovery case deleted successfully.'
                ]);
            }

            return redirect()->route('cases.index')
                ->with('success', 'Recovery case deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Recovery case deletion failed: ' . $e->getMessage());
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete recovery case: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to delete recovery case: ' . $e->getMessage());
        }
    }

    /**
     * Display borrower's own recovery cases.
     */
    public function myCases()
    {
        $user = Auth::user();
        
        if (! $user->hasRole('borrower')) {
            // Staff: "my cases" = the cases assigned to me (borrowers never reach the staff routes).
            return redirect()->route('cases.index', ['assigned_to' => $user->id]);
        }

        $cases = DebtRecoveryCase::with(['status', 'priority', 'loan'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = [
            'total' => $cases->total(),
            'active' => DebtRecoveryCase::where('user_id', $user->id)
                ->whereHas('status', function($q) {
                    $q->whereIn('slug', ['open', 'in_progress', 'negotiation', 'legal']);
                })->count(),
            'recovered' => DebtRecoveryCase::where('user_id', $user->id)
                ->whereHas('status', function($q) {
                    $q->where('slug', 'recovered');
                })->count(),
            'written_off' => DebtRecoveryCase::where('user_id', $user->id)
                ->whereHas('status', function($q) {
                    $q->where('slug', 'written_off');
                })->count(),
        ];

        return view('cases.my', compact('cases', 'stats'));
    }

    /**
     * Add action to a case.
     */
    public function addAction(Request $request, string $id)
    {
        if (! Auth::user()->can('edit recovery cases')) {
            abort(403, 'Unauthorized action.');
        }

        $case = DebtRecoveryCase::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'action_type_id' => 'required|exists:action_types,id',
            'action_date' => 'required|date',
            'contact_person' => 'nullable|string|max:255',
            'contact_relationship' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'outcome' => 'nullable|in:successful,partial,failed,promise_to_pay,no_answer,wrong_number,refused,pending',
            'promised_amount' => 'nullable|numeric|min:0',
            'promised_date' => 'nullable|date',
            'amount_collected' => 'nullable|numeric|min:0',
            'collection_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'follow_up_notes' => 'nullable|string',
        ]);
        // A collection becomes a Repayment, so it needs a loan and a payment reference.
        $validator->after(function ($v) use ($request, $case) {
            if ((float) $request->amount_collected > 0) {
                if (!$case->loan) {
                    $v->errors()->add('amount_collected', 'This case has no loan to post the collection to.');
                }
                if (!trim((string) $request->collection_reference)) {
                    $v->errors()->add('collection_reference', 'Enter the payment reference (M-Pesa code, bank ref, receipt no.) for the amount collected.');
                }
            }
        });

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
          $action = DB::transaction(function () use ($request, $case) {
            $action = RecoveryAction::create([
                'case_id' => $case->id,
                'action_type_id' => $request->action_type_id,
                'action_date' => $request->action_date,
                'performed_by' => Auth::id(),
                                'contact_person' => $request->contact_person,
                'contact_relationship' => $request->contact_relationship,
                'contact_phone' => $request->contact_phone,
                'contact_email' => $request->contact_email,
                'outcome' => $request->outcome,
                'promised_amount' => $request->promised_amount,
                'promised_date' => $request->promised_date,
                'amount_collected' => $request->amount_collected,
                'notes' => $request->notes,
                'follow_up_date' => $request->follow_up_date,
                'follow_up_notes' => $request->follow_up_notes,
            ]);

            // Create case note
            RecoveryCaseNote::create([
                'case_id' => $case->id,
                'note_type' => 'action',
                'note' => "Action added: " . ($action->actionType->name ?? 'Unknown') . " by " . Auth::user()->name . ($request->notes ? ": " . $request->notes : ""),
                'created_by' => Auth::id(),
            ]);

            // Update last contact date if applicable
            if ($request->action_date) {
                $case->update([
                    'last_contact_date' => $request->action_date,
                ]);
            }

            // Update next action date if follow up is scheduled
            if ($request->follow_up_date) {
                $case->update([
                    'next_action_date' => $request->follow_up_date,
                ]);
            }

            // Amount collected = money received: post it as a real Repayment on the loan through the
            // normal staff repayment path (wallet sync, cycle and loan status handled there), then
            // lower the case debt by what was posted.
            if ((float) $request->amount_collected > 0) {
                $repayment = $this->postCollectionAsRepayment($case, $action, (float) $request->amount_collected,
                    trim((string) $request->collection_reference), $request->action_date);

                $oldDebt = (float) $case->total_debt_amount;
                $remaining = $oldDebt - (float) $repayment->amount;
                $case->update([
                    'total_debt_amount' => max(0, $remaining),
                    'updated_by' => Auth::id(),
                ]);

                // Fully collected: close the case as recovered (the loan's own status was set by the repayment path)
                if ($remaining <= 0) {
                    $recoveredStatus = RecoveryStatus::where('slug', 'recovered')->first();
                    if ($recoveredStatus) {
                        $case->update(['status_id' => $recoveredStatus->id]);
                    }
                }

                RecoveryCaseNote::create([
                    'case_id' => $case->id,
                    'note_type' => 'action',
                    'note' => 'Collected ' . number_format((float) $repayment->amount, 2) . ' (ref ' . $repayment->transaction
                        . ') posted as repayment #' . $repayment->id . ' by ' . Auth::user()->name,
                    'created_by' => Auth::id(),
                ]);

                AuditTrail::record('debt_recovery_cases', $case->id, 'update',
                    ['total_debt_amount' => $oldDebt],
                    ['event' => 'recovery_collection', 'total_debt_amount' => max(0, $remaining), 'loan_id' => $case->loan_id,
                     'repayment_id' => $repayment->id, 'recovery_action_id' => $action->id, 'amount' => (float) $repayment->amount,
                     'reference' => $repayment->transaction]);
            }

            return $action;
          });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Action added successfully.',
                    'action' => $action->load('actionType', 'performedBy')
                ]);
            }

            return redirect()->route('cases.show', $case)
                ->with('success', 'Action added successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to add action: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add action: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to add action: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Add note to a case.
     */
    public function addNote(Request $request, string $id)
    {
        $case = DebtRecoveryCase::findOrFail($id);

        // Check permission
        $user = Auth::user();
        if ($user->hasRole('borrower') && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'note_type' => 'required|in:general,action,alert,legal,negotiation,payment',
            'note' => 'required|string',
            'is_private' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $note = RecoveryCaseNote::create([
                'case_id' => $case->id,
                'note_type' => $request->note_type,
                'note' => $request->note,
                'created_by' => Auth::id(),
                'is_private' => $request->is_private ?? false,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Note added successfully.',
                    'note' => $note->load('createdBy')
                ]);
            }

            return redirect()->route('cases.show', $case)
                ->with('success', 'Note added successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to add note: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add note: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to add note: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update action status.
     */
    public function updateAction(Request $request, string $id, string $actionId)
    {
        if (! Auth::user()->can('edit recovery cases')) {
            abort(403, 'Unauthorized action.');
        }

        $case = DebtRecoveryCase::findOrFail($id);
        $action = RecoveryAction::where('case_id', $case->id)->findOrFail($actionId);

        $validator = Validator::make($request->all(), [
            'outcome' => 'nullable|in:successful,partial,failed,promise_to_pay,no_answer,wrong_number,refused,pending',
            'promised_amount' => 'nullable|numeric|min:0',
            'promised_date' => 'nullable|date',
            'amount_collected' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'follow_up_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $action->update([
                'outcome' => $request->outcome,
                'promised_amount' => $request->promised_amount,
                'promised_date' => $request->promised_date,
                'notes' => $request->notes,
                'follow_up_date' => $request->follow_up_date,
                'follow_up_notes' => $request->follow_up_notes,
            ]);
            // amount_collected is not editable here: a collection is posted once, as a Repayment, when
            // the action is added (addAction). Corrections go through the repayment itself.

            // Update next action date if follow up is scheduled
            if ($request->follow_up_date) {
                $case->update([
                    'next_action_date' => $request->follow_up_date,
                ]);
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Action updated successfully.',
                    'action' => $action
                ]);
            }

            return redirect()->route('cases.show', $case)
                ->with('success', 'Action updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update action: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update action: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to update action: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get statistics for recovery cases.
     */
    public function stats(Request $request)
    {
        if (! Auth::user()->can('view recovery cases')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Overall stats
            $overallStats = [
                'total_cases' => DebtRecoveryCase::count(),
                'total_debt_amount' => DebtRecoveryCase::sum('total_debt_amount'),
                'cases_by_status' => [],
                'cases_by_priority' => [],
            ];

            // Cases by status
            $statusStats = RecoveryStatus::withCount('recoveryCases')->get();
            foreach ($statusStats as $status) {
                $overallStats['cases_by_status'][$status->slug] = [
                    'name' => $status->name,
                    'count' => $status->recovery_cases_count,
                    'color' => $status->color ?? 'gray',
                ];
            }

            // Cases by priority
            $priorityStats = RecoveryPriority::withCount('recoveryCases')->get();
            foreach ($priorityStats as $priority) {
                $overallStats['cases_by_priority'][$priority->slug] = [
                    'name' => $priority->name,
                    'count' => $priority->recovery_cases_count,
                    'color' => $priority->color ?? 'gray',
                ];
            }

            // Monthly trend
            $monthlyStats = DebtRecoveryCase::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count, SUM(total_debt_amount) as amount')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get();

            // Officer performance
            $officerStats = User::role(['admin', 'manager', 'recovery-officer', 'teller'])
                ->withCount(['assignedRecoveryCases', 'assignedRecoveryCases as recovered_count' => function($q) {
                    $q->whereHas('status', function($sq) {
                        $sq->where('slug', 'recovered');
                    });
                }])
                ->get()
                ->map(function ($officer) {
                    return [
                        'name' => $officer->name,
                        'total_assigned' => $officer->assigned_recovery_cases_count,
                        'recovered' => $officer->recovered_count,
                        'recovery_rate' => $officer->assigned_recovery_cases_count > 0 
                            ? round(($officer->recovered_count / $officer->assigned_recovery_cases_count) * 100, 2) 
                            : 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'overall' => $overallStats,
                    'monthly_trend' => $monthlyStats,
                    'officer_performance' => $officerStats,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get recovery stats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recovery stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Route cases.stats (GET cases/stats): JSON statistics for recovery cases.
     */
    public function getStats(Request $request)
    {
        return $this->stats($request);
    }

    /**
     * Route cases.data (GET cases/{case}/data): one case as JSON (case, borrower, loan, actions, notes,
     * collections posted as repayments) for refreshing the case page without a reload.
     */
    public function getCaseData(string $id)
    {
        $case = DebtRecoveryCase::with([
            'user:id,name,email,phone',
            'loan',
            'status',
            'priority',
            'assignedTo:id,name',
            'actions' => fn ($q) => $q->latest('action_date'),
            'actions.actionType',
            'actions.performedBy:id,name',
            'actions.repayment',
        ])->findOrFail($id);
        // "notes" is both a column and a relation on cases; load the note rows explicitly
        $caseNotes = $case->notes()->with('createdBy:id,name')->latest()->get();

        $collected = (float) $case->actions->whereNotNull('repayment_id')->sum('amount_collected');

        return response()->json([
            'success' => true,
            'data' => [
                'case' => $case,
                'case_notes' => $caseNotes,
                'summary' => [
                    'total_debt_amount' => (float) $case->total_debt_amount,
                    'collected_via_repayments' => $collected,
                    'actions_count' => $case->actions->count(),
                    'notes_count' => $caseNotes->count(),
                    'status' => $case->status->slug ?? null,
                    'loan_status' => $case->loan->status ?? null,
                ],
            ],
        ]);
    }

    /**
     * Post a recovery collection as a Repayment through the same path staff use to record repayments
     * (RepaymentController@store: cycle completion and loan status are decided there, unchanged).
     * mode = 'recovery', transaction = the reference entered on the action. Links the action to it.
     */
    private function postCollectionAsRepayment(DebtRecoveryCase $case, RecoveryAction $action, float $amount, string $reference, $date): Repayment
    {
        $loan = $case->loan;
        if (!$loan) {
            throw new \RuntimeException('This case has no loan to post the collection to.');
        }
        $cycle = $loan->getCurrentCycle() ?? $loan->cycles()->latest()->first();
        if (!$cycle) {
            throw new \RuntimeException('Loan #' . $loan->id . ' has no repayment cycle to post the collection to.');
        }

        $payload = [
            'loan_id' => $loan->id,
            'loan_cycle_id' => $cycle->id,
            'amount' => $amount,
            'repayment_date' => Carbon::parse($date ?: now())->toDateString(),
            'transaction' => $reference,
            'mode' => 'recovery',
        ];
        $subRequest = Request::create(route('repayments.store'), 'POST', $payload);
        $subRequest->setUserResolver(fn () => Auth::user());

        $response = app(RepaymentController::class)->store($subRequest);
        $repaymentId = data_get($response->getData(true), 'repayment.id');
        if (!$repaymentId) {
            throw new \RuntimeException('The repayment could not be recorded.');
        }

        $repayment = Repayment::findOrFail($repaymentId);
        $action->update(['repayment_id' => $repayment->id]);

        return $repayment;
    }

    /**
     * Generate a unique case number.
     */
    private function generateCaseNumber()
    {
        $prefix = 'RC';
        $year = date('Y');
        $month = date('m');
        $count = DebtRecoveryCase::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;
        
        return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get case timeline.
     */
    private function getCaseTimeline($case)
    {
        $timeline = [];

        // Add case creation
        $timeline[] = [
            'date' => $case->created_at,
            'type' => 'creation',
            'title' => 'Case Created',
            'description' => "Case {$case->case_number} was created",
            'user' => $case->createdBy ? $case->createdBy->name : 'System',
        ];

        // Add actions - SAFE: Check if it's iterable
        if (isset($case->actions) && (is_array($case->actions) || $case->actions instanceof \Traversable)) {
            foreach ($case->actions as $action) {
                $timeline[] = [
                    'date' => $action->action_date ?? now(),
                    'type' => 'action',
                    'title' => isset($action->actionType) ? ($action->actionType->name ?? 'Action') : 'Action',
                    'description' => $action->notes ?? 'Action performed',
                    'user' => isset($action->performedBy) ? $action->performedBy->name : 'Unknown',
                    'outcome' => $action->outcome ?? null,
                ];
            }
        }

        // Add notes - SAFE: Check if it's iterable
        if (isset($case->notes) && (is_array($case->notes) || $case->notes instanceof \Traversable)) {
            foreach ($case->notes as $note) {
                $timeline[] = [
                    'date' => $note->created_at ?? now(),
                    'type' => 'note',
                    'title' => isset($note->note_type) ? ucfirst($note->note_type) . ' Note' : 'Note',
                    'description' => $note->note ?? '',
                    'user' => isset($note->createdBy) ? $note->createdBy->name : 'Unknown',
                ];
            }
        }

        // Add payment plans - SAFE: Check if it's iterable and not a string
        if (isset($case->paymentPlans) && 
            (is_array($case->paymentPlans) || $case->paymentPlans instanceof \Traversable) && 
            !is_string($case->paymentPlans)) {
            foreach ($case->paymentPlans as $plan) {
                $timeline[] = [
                    'date' => $plan->created_at ?? now(),
                    'type' => 'payment_plan',
                    'title' => 'Payment Plan Created',
                    'description' => "Total amount: " . ($plan->total_amount ?? 0) . ", Installments: " . ($plan->installment_count ?? 0),
                    'user' => isset($plan->createdBy) ? $plan->createdBy->name : 'Unknown',
                ];
            }

            // Add installment payments - SAFE: Check if installments is iterable
            foreach ($case->paymentPlans as $plan) {
                if (isset($plan->installments) && 
                    (is_array($plan->installments) || $plan->installments instanceof \Traversable) && 
                    !is_string($plan->installments)) {
                    foreach ($plan->installments as $installment) {
                        if (isset($installment->status) && $installment->status === 'paid') {
                            $paidDate = $installment->paid_date ?? $installment->due_date ?? now();
                            $timeline[] = [
                                'date' => $paidDate,
                                'type' => 'payment',
                                'title' => 'Installment Paid',
                                'description' => "Amount: " . ($installment->amount ?? 0) . " paid on " . ($paidDate instanceof \Carbon\Carbon ? $paidDate->format('Y-m-d') : 'Unknown'),
                                'user' => 'System',
                            ];
                        }
                    }
                }
            }
        }

        // Add status changes (from notes) - SAFE: Check if notes is iterable
        if (isset($case->notes) && (is_array($case->notes) || $case->notes instanceof \Traversable)) {
            foreach ($case->notes as $note) {
                if (isset($note->note) && strpos($note->note, 'Status changed from') !== false) {
                    $timeline[] = [
                        'date' => $note->created_at ?? now(),
                        'type' => 'status_change',
                        'title' => 'Status Changed',
                        'description' => $note->note ?? '',
                        'user' => isset($note->createdBy) ? $note->createdBy->name : 'Unknown',
                    ];
                }
            }
        }

        // Sort by date descending
        usort($timeline, function ($a, $b) {
            $dateA = $a['date'] ?? now();
            $dateB = $b['date'] ?? now();
            return $dateB <=> $dateA;
        });

        return $timeline;
    }

    /**
     * Export cases to CSV.
     */
    public function export(Request $request)
    {
        if (! Auth::user()->can('view recovery cases')) {
            abort(403, 'Unauthorized action.');
        }

        $query = DebtRecoveryCase::with(['user', 'status', 'priority', 'assignedTo']);

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $status = RecoveryStatus::where('slug', $request->status)->first();
            if ($status) {
                $query->where('status_id', $status->id);
            }
        }

        if ($request->has('priority') && $request->priority !== 'all') {
            $priority = RecoveryPriority::where('slug', $request->priority)->first();
            if ($priority) {
                $query->where('priority_id', $priority->id);
            }
        }

        $cases = $query->get();

        $headers = [
            'Case Number',
            'Borrower',
            'Total Debt',
            'Status',
            'Priority',
            'Assigned To',
            'Default Date',
            'Days in Default',
            'Created At',
        ];

        $filename = 'recovery_cases_' . date('Y-m-d_His') . '.csv';
        
        $handle = fopen('php://output', 'w');
        fputcsv($handle, $headers);

        foreach ($cases as $case) {
            fputcsv($handle, [
                $case->case_number,
                $case->user ? $case->user->name : 'Unknown',
                $case->total_debt_amount,
                $case->status ? $case->status->name : 'Unknown',
                $case->priority ? $case->priority->name : 'Unknown',
                $case->assignedTo ? $case->assignedTo->name : 'Unassigned',
                $case->default_date ? $case->default_date->format('Y-m-d') : '',
                $case->days_in_default ?? 0,
                $case->created_at ? $case->created_at->format('Y-m-d H:i') : '',
            ]);
        }

        fclose($handle);

        return response()->stream(function() use ($handle) {
            // Stream already handled above
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}