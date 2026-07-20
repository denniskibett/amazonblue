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
use Illuminate\Http\Request;
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
        if ($user->role === 'borrower') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'broker') {
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
        $officers = User::whereIn('role', ['admin', 'teller'])->get();

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
        if (!in_array(Auth::user()->role, ['admin', 'teller'])) {
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
        $officers = User::whereIn('role', ['admin', 'teller'])->get();
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
        if (!in_array(Auth::user()->role, ['admin', 'teller'])) {
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
    $case = DebtRecoveryCase::with([
        'user',
        'user.borrower',
        'loan',
        'loan.loanType',
        'status',
        'priority',
        'assignedTo',
        'actions' => function($query) {
            $query->with(['actionType', 'performedBy'])
                  ->orderBy('created_at', 'desc');
        },
        'paymentPlans',
        'paymentPlans.installments',
        'documents',
        'notes',
        'notes.createdBy',
        'legalProceedings',
        'legalDeadlines',
        'communications',
        'communications.communicationType',
        'tasks',
        'tasks.assignedTo',
        'tasks.priority',
    ])->findOrFail($id);

    // Check permission
    $user = Auth::user();
    if ($user->role === 'borrower' && $case->user_id !== $user->id) {
        abort(403, 'Unauthorized action.');
    }
    if ($user->role === 'broker') {
        $broker = $user->broker;
        if ($broker) {
            $borrowerIds = Borrower::where('broker_id', $broker->id)->pluck('user_id');
            if (!$borrowerIds->contains($case->user_id)) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    // Get NPL info if case is from a loan
    $nplInfo = null;
    if ($case->loan) {
        try {
            $nplInfo = [
                'is_npl' => $case->loan->is_non_performing ?? false,
                'days_overdue' => $case->loan->days_overdue ?? 0,
                'threshold' => $case->loan->npl_trigger_threshold ?? 0,
                'default_date' => $case->loan->default_date,
                'recovery_stage' => $case->loan->getRecoveryStage(),
                'stage_label' => $case->loan->getRecoveryStageLabel(),
                'stage_color' => $case->loan->getRecoveryStageColor(),
            ];
        } catch (\Exception $e) {
            $nplInfo = [
                'is_npl' => $case->loan->is_non_performing ?? false,
                'days_overdue' => $case->loan->days_overdue ?? 0,
                'threshold' => $case->loan->npl_trigger_threshold ?? 0,
                'default_date' => $case->loan->default_date,
                'recovery_stage' => 'unknown',
                'stage_label' => 'Unknown',
                'stage_color' => 'gray',
            ];
        }
    }

    // Get related data
    $actionTypes = ActionType::all();
    $statuses = RecoveryStatus::all();
    $priorities = RecoveryPriority::all();
    $officers = User::whereIn('role', ['admin', 'teller'])->get();

    // Build timeline manually
    $timeline = $this->getCaseTimeline($case);

    // Prepare case data for Alpine.js with proper formatting
    $caseData = $this->prepareCaseDataForView($case);

    return view('cases.show', compact(
        'case',
        'caseData',
        'actionTypes',
        'statuses',
        'priorities',
        'officers',
        'timeline',
        'nplInfo'
    ));
}

/**
 * Prepare case data for the view with proper formatting for Alpine.js
 */
private function prepareCaseDataForView($case)
{
    return [
        'id' => $case->id,
        'case_number' => $case->case_number,
        'user_id' => $case->user_id,
        'user_name' => $case->user->name ?? 'N/A',
        'user_email' => $case->user->email ?? 'N/A',
        'user_phone' => $case->user->phone ?? null,
        'user_initials' => $this->getInitials($case->user->name ?? 'User'),
        'client_type' => $case->user->borrower->formatted_client_type ?? null,
        'loan_id' => $case->loan_id,
        'loan_type' => $case->loan->loanType->name ?? null,
        'loan_amount' => (float) ($case->loan->amount ?? 0),
        'loan_status' => $case->loan->status ?? null,
        'is_non_performing' => $case->loan->is_non_performing ?? false,
        'days_overdue' => $case->loan->days_overdue ?? 0,
        'default_date_formatted' => $case->default_date ? $case->default_date->format('M d, Y') : null,
        'last_contact_date_formatted' => $case->last_contact_date ? $case->last_contact_date->format('M d, Y') : null,
        'next_action_date_formatted' => $case->next_action_date ? $case->next_action_date->format('M d, Y') : null,
        'total_debt_amount' => (float) $case->total_debt_amount,
        'total_recovered' => (float) $this->getTotalRecovered($case),
        'remaining_balance' => (float) $this->getRemainingBalance($case),
        'recovery_progress' => (float) ($case->recovery_progress ?? 0),
        'days_in_default' => (int) ($case->days_in_default ?? 0),
        'assigned_to_name' => $case->assignedTo->name ?? null,
        'recovery_officer' => $case->recovery_officer ?? null,
        'recovery_strategy' => $case->recovery_strategy ?? null,
        'notes' => $case->notes ?? null,
        'status_id' => $case->status_id,
        'status_slug' => $case->status->slug ?? null,
        'priority_id' => $case->priority_id,
        'priority_slug' => $case->priority->slug ?? null,
        'actions' => $case->actions->map(function($action) {
            return [
                'id' => $action->id,
                'action_type' => $action->actionType->slug ?? 'other',
                'action_type_label' => $action->actionType->name ?? 'Action',
                'description' => $action->notes ?? $action->description ?? null,
                'outcome' => $action->outcome,
                'amount_collected' => (float) ($action->amount_collected ?? 0),
                'created_at' => $action->created_at?->toISOString(),
                'created_at_diff' => $action->created_at?->diffForHumans(),
                'next_action_date' => $action->follow_up_date?->format('M d, Y'),
            ];
        })->toArray(),
        'actions_count' => $case->actions->count(),
        'payment_plans' => $case->paymentPlans->map(function($plan) {
            return [
                'id' => $plan->id,
                'installment_frequency' => $plan->installment_frequency ?? 'Monthly',
                'number_of_installments' => $plan->number_of_installments ?? 0,
                'installment_amount' => (float) ($plan->installment_amount ?? 0),
                'total_amount' => (float) ($plan->total_amount ?? 0),
                'status' => $plan->status ?? 'proposed',
                'remaining_balance' => (float) ($plan->remaining_balance ?? 0),
                'progress_percentage' => (float) ($plan->progress_percentage ?? 0),
            ];
        })->toArray(),
        'created_at' => $case->created_at?->toISOString(),
        'created_at_diff' => $case->created_at?->diffForHumans(),
        'updated_at' => $case->updated_at?->toISOString(),
        'updated_at_diff' => $case->updated_at?->diffForHumans(),
    ];
}

/**
 * Get initials from a name
 */
private function getInitials($name)
{
    if (empty($name)) return 'U';
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

    /**
     * Show the form for editing the specified recovery case.
     */
    public function edit(string $id)
    {
        if (!in_array(Auth::user()->role, ['admin', 'teller'])) {
            abort(403, 'Unauthorized action.');
        }

        $case = DebtRecoveryCase::with(['user', 'status', 'priority', 'assignedTo', 'loan'])->findOrFail($id);
        
        $statuses = RecoveryStatus::all();
        $priorities = RecoveryPriority::all();
        $officers = User::whereIn('role', ['admin', 'teller'])->get();

        return view('cases.edit', compact('case', 'statuses', 'priorities', 'officers'));
    }

    /**
     * Update the specified recovery case.
     */
    public function update(Request $request, string $id)
    {
        if (!in_array(Auth::user()->role, ['admin', 'teller'])) {
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
        if (!in_array(Auth::user()->role, ['admin', 'teller'])) {
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
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Only admins can write off cases.');
        }

        $case = DebtRecoveryCase::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'write_off_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $writtenOffStatus = RecoveryStatus::where('slug', 'written_off')->first();
            if (!$writtenOffStatus) {
                throw new \Exception('Written off status not found.');
            }

            $case->update([
                'status_id' => $writtenOffStatus->id,
                'notes' => ($case->notes ? $case->notes . "\n\n" : '') . "WRITTEN OFF: " . $request->write_off_reason,
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
                'note' => "Case written off by " . Auth::user()->name . ". Reason: " . $request->write_off_reason,
                'created_by' => Auth::id(),
            ]);

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
        if (Auth::user()->role !== 'admin') {
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
        
        if ($user->role !== 'borrower') {
            abort(403, 'Only borrowers can view their recovery cases.');
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
        if (!in_array(Auth::user()->role, ['admin', 'teller'])) {
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
            $action = RecoveryAction::create([
                'case_id' => $case->id,
                'action_type_id' => $request->action_type_id,
                'action_date' => $request->action_date,
                'performed_by' => Auth::id(),
                'contact_person' => $request->contact_person,
                'contact_relationship' => $request->contact_relationship,
                'contact_phone' => $request->contact_phone,
                'contact_email' => $request->contact_email,
                'outcome' => $request->outcome ?? 'pending',
                'promised_amount' => $request->promised_amount,
                'promised_date' => $request->promised_date,
                'amount_collected' => $request->amount_collected ?? 0,
                'notes' => $request->notes,
                'follow_up_date' => $request->follow_up_date,
                'follow_up_notes' => $request->follow_up_notes,
            ]);

            // Update case last contact date
            $case->update([
                'last_contact_date' => now(),
                'next_action_date' => $request->follow_up_date ?? $case->next_action_date,
            ]);

            // If amount collected, update total debt
            if ($request->amount_collected > 0) {
                $newTotal = max(0, $case->total_debt_amount - $request->amount_collected);
                $case->update(['total_debt_amount' => $newTotal]);

                // Check if fully recovered
                if ($newTotal <= 0) {
                    $recoveredStatus = RecoveryStatus::where('slug', 'recovered')->first();
                    if ($recoveredStatus) {
                        $case->update(['status_id' => $recoveredStatus->id]);
                        // Update associated loan
                        if ($case->loan) {
                            $case->loan->update([
                                'status' => Loan::STATUS_REPAID,
                                'is_non_performing' => false,
                            ]);
                        }
                    }
                }
            }

            // Create note
            RecoveryCaseNote::create([
                'case_id' => $case->id,
                'note_type' => 'action',
                'note' => "Action recorded: " . ($action->actionType->name ?? 'Unknown') . " by " . Auth::user()->name,
                'created_by' => Auth::id(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Action recorded successfully.',
                    'action' => $action->load('actionType')
                ]);
            }

            return redirect()->route('cases.show', $case)
                ->with('success', 'Action recorded successfully.');

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
     * Generate a unique case number.
     */
    private function generateCaseNumber()
    {
        $year = now()->format('Y');
        $count = DebtRecoveryCase::whereYear('created_at', $year)->count() + 1;
        return 'DR-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Build case timeline from events.
     */
    private function getCaseTimeline($case)
    {
        $events = collect();

        // Add actions
        if ($case->actions && $case->actions->count() > 0) {
            foreach ($case->actions as $action) {
                // Safely get action type name
                $actionTypeName = 'Action';
                $actionTypeSlug = 'other';
                
                try {
                    if ($action->actionType && is_object($action->actionType)) {
                        $actionTypeName = $action->actionType->name ?? 'Action';
                        $actionTypeSlug = $action->actionType->slug ?? 'other';
                    } elseif (is_string($action->action_type_id) || is_int($action->action_type_id)) {
                        // If it's just an ID, try to find the type
                        $actionType = ActionType::find($action->action_type_id);
                        if ($actionType) {
                            $actionTypeName = $actionType->name ?? 'Action';
                            $actionTypeSlug = $actionType->slug ?? 'other';
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback to default
                    $actionTypeName = 'Action';
                    $actionTypeSlug = 'other';
                }
                
                $events->push([
                    'date' => $action->action_date ?? now(),
                    'type' => 'action',
                    'description' => $actionTypeName,
                    'icon' => $this->getActionIcon($actionTypeSlug),
                    'color' => $this->getActionColor($actionTypeSlug),
                    'data' => $action,
                ]);
            }
        }

        // Add notes
        if ($case->notes && $case->notes->count() > 0) {
            foreach ($case->notes as $note) {
                $noteType = $note->note_type ?? 'general';
                $events->push([
                    'date' => $note->created_at ?? now(),
                    'type' => 'note',
                    'description' => ucfirst(str_replace('_', ' ', $noteType)),
                    'icon' => 'fa-sticky-note',
                    'color' => 'gray',
                    'data' => $note,
                ]);
            }
        }

        // Sort by date (newest first)
        return $events->sortByDesc('date')->values();
    }

    /**
     * Get icon for action type.
     */
    private function getActionIcon($slug)
    {
        $icons = [
            'phone_call' => 'fa-phone',
            'sms' => 'fa-sms',
            'email' => 'fa-envelope',
            'visit' => 'fa-building',
            'letter' => 'fa-file-alt',
            'legal_notice' => 'fa-gavel',
            'negotiation' => 'fa-handshake',
            'payment_arrangement' => 'fa-file-contract',
            'field_visit' => 'fa-map-marker-alt',
            'other' => 'fa-ellipsis-h',
        ];
        return $icons[$slug] ?? 'fa-ellipsis-h';
    }

    /**
     * Get color for action type.
     */
    private function getActionColor($slug)
    {
        $colors = [
            'phone_call' => 'blue',
            'sms' => 'indigo',
            'email' => 'purple',
            'visit' => 'green',
            'letter' => 'gray',
            'legal_notice' => 'red',
            'negotiation' => 'yellow',
            'payment_arrangement' => 'emerald',
            'field_visit' => 'teal',
            'other' => 'gray',
        ];
        return $colors[$slug] ?? 'gray';
    }

    /**
     * Get case data for AJAX requests.
     */
    public function getCaseData(string $id)
    {
        $case = DebtRecoveryCase::with(['user', 'status', 'priority', 'loan'])
            ->findOrFail($id);

        $user = Auth::user();
        if ($user->role === 'borrower' && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json([
            'case' => $case,
            'debtor' => $case->user,
            'status' => $case->status,
            'priority' => $case->priority,
            'total_recovered' => $this->getTotalRecovered($case),
            'remaining_balance' => $this->getRemainingBalance($case),
            'last_action' => $case->actions()->latest()->first(),
            'active_payment_plan' => $this->getActivePaymentPlan($case),
            'npl_info' => $case->loan ? [
                'is_npl' => $case->loan->is_non_performing,
                'days_overdue' => $case->loan->days_overdue,
            ] : null,
        ]);
    }

    /**
     * Get total recovered amount.
     */
    private function getTotalRecovered($case)
    {
        return $case->actions()->where('outcome', 'successful')->sum('amount_collected');
    }

    /**
     * Get remaining balance.
     */
    private function getRemainingBalance($case)
    {
        return max(0, $case->total_debt_amount - $this->getTotalRecovered($case));
    }

    /**
     * Get active payment plan.
     */
    private function getActivePaymentPlan($case)
    {
        return $case->paymentPlans()
            ->whereIn('status', ['proposed', 'accepted'])
            ->latest()
            ->first();
    }

    /**
     * Export cases to CSV.
     */
    public function export(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin', 'teller'])) {
            abort(403, 'Unauthorized action.');
        }

        $query = DebtRecoveryCase::with(['user', 'status', 'priority', 'assignedTo', 'loan']);

        if ($request->has('status') && $request->status !== 'all') {
            $status = RecoveryStatus::where('slug', $request->status)->first();
            if ($status) {
                $query->where('status_id', $status->id);
            }
        }

        $cases = $query->get();

        $filename = 'recovery_cases_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($cases) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'Case Number',
                'Debtor',
                'Debtor Phone',
                'Debtor Email',
                'Associated Loan ID',
                'NPL Status',
                'Days Overdue',
                'Total Debt',
                'Principal',
                'Interest',
                'Penalty',
                'Fees',
                'Status',
                'Priority',
                'Default Date',
                'Days in Default',
                'Assigned To',
                'Created At',
                'Last Contact',
            ]);

            foreach ($cases as $case) {
                fputcsv($handle, [
                    $case->case_number,
                    $case->user->name,
                    $case->user->phone ?? 'N/A',
                    $case->user->email,
                    $case->loan_id ?? 'N/A',
                    $case->loan && $case->loan->is_non_performing ? 'Yes' : 'No',
                    $case->loan ? $case->loan->days_overdue : 'N/A',
                    number_format($case->total_debt_amount, 2),
                    number_format($case->principal_outstanding, 2),
                    number_format($case->interest_outstanding, 2),
                    number_format($case->penalty_outstanding, 2),
                    number_format($case->fees_outstanding, 2),
                    $case->status->name ?? 'N/A',
                    $case->priority->name ?? 'N/A',
                    $case->default_date ? $case->default_date->format('Y-m-d') : 'N/A',
                    $case->days_in_default,
                    $case->assignedTo->name ?? 'Unassigned',
                    $case->created_at ? $case->created_at->format('Y-m-d H:i') : 'N/A',
                    $case->last_contact_date ? $case->last_contact_date->format('Y-m-d') : 'N/A',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get statistics for dashboard widget.
     */
    public function getStats()
    {
        $stats = [
            'total' => DebtRecoveryCase::count(),
            'open' => DebtRecoveryCase::open()->count(),
            'urgent' => DebtRecoveryCase::urgent()->count(),
            'total_debt' => DebtRecoveryCase::sum('total_debt_amount'),
            'recovered' => RecoveryAction::where('outcome', 'successful')->sum('amount_collected'),
            'recovery_rate' => 0,
            'npl_cases' => DebtRecoveryCase::whereHas('loan', function($q) {
                $q->where('is_non_performing', true);
            })->count(),
        ];

        if ($stats['total_debt'] > 0) {
            $stats['recovery_rate'] = round(($stats['recovered'] / $stats['total_debt']) * 100, 2);
        }

        return response()->json($stats);
    }

    /**
     * Generate case data for the show view with proper formatting.
     */
    public function getCaseDataForView(string $id)
    {
        $case = DebtRecoveryCase::with([
            'user',
            'user.borrower',
            'loan',
            'loan.loanType',
            'status',
            'priority',
            'assignedTo',
            'actions' => function($query) {
                $query->with(['actionType', 'performedBy'])
                      ->orderBy('created_at', 'desc');
            },
            'paymentPlans',
            'notes',
        ])->findOrFail($id);

        $user = Auth::user();
        if ($user->role === 'borrower' && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        // Build formatted data for Alpine
        $formattedData = [
            'id' => $case->id,
            'case_number' => $case->case_number,
            'user_id' => $case->user_id,
            'user_name' => $case->user->name ?? 'N/A',
            'user_email' => $case->user->email ?? 'N/A',
            'user_phone' => $case->user->phone ?? null,
            'user_initials' => $case->user->getInitialsAttribute() ?? 'U',
            'client_type' => $case->user->borrower->formatted_client_type ?? null,
            'loan_id' => $case->loan_id,
            'loan_type' => $case->loan->loanType->name ?? null,
            'loan_amount' => $case->loan->amount ?? 0,
            'loan_status' => $case->loan->status ?? null,
            'is_non_performing' => $case->loan->is_non_performing ?? false,
            'days_overdue' => $case->loan->days_overdue ?? 0,
            'default_date_formatted' => $case->default_date ? $case->default_date->format('M d, Y') : null,
            'last_contact_date_formatted' => $case->last_contact_date ? $case->last_contact_date->format('M d, Y') : null,
            'next_action_date_formatted' => $case->next_action_date ? $case->next_action_date->format('M d, Y') : null,
            'total_debt_amount' => (float) $case->total_debt_amount,
            'total_recovered' => (float) $this->getTotalRecovered($case),
            'remaining_balance' => (float) $this->getRemainingBalance($case),
            'recovery_progress' => (float) $case->recovery_progress,
            'days_in_default' => $case->days_in_default ?? 0,
            'assigned_to_name' => $case->assignedTo->name ?? null,
            'recovery_officer' => $case->recovery_officer ?? null,
            'recovery_strategy' => $case->recovery_strategy ?? null,
            'notes' => $case->notes ?? null,
            'status_id' => $case->status_id,
            'status_slug' => $case->status->slug ?? null,
            'priority_id' => $case->priority_id,
            'priority_slug' => $case->priority->slug ?? null,
            'actions' => $case->actions->map(function($action) {
                return [
                    'id' => $action->id,
                    'action_type' => $action->actionType->slug ?? 'other',
                    'action_type_label' => $action->actionType->name ?? 'Action',
                    'description' => $action->notes ?? $action->description ?? null,
                    'outcome' => $action->outcome,
                    'amount_collected' => (float) $action->amount_collected,
                    'created_at' => $action->created_at?->toISOString(),
                    'created_at_diff' => $action->created_at?->diffForHumans(),
                    'next_action_date' => $action->follow_up_date?->format('M d, Y'),
                ];
            }),
            'actions_count' => $case->actions->count(),
            'payment_plans' => $case->paymentPlans->map(function($plan) {
                return [
                    'id' => $plan->id,
                    'installment_frequency' => $plan->installment_frequency,
                    'number_of_installments' => $plan->number_of_installments,
                    'installment_amount' => $plan->installment_amount,
                    'total_amount' => (float) $plan->total_amount,
                    'status' => $plan->status,
                    'remaining_balance' => (float) $plan->remaining_balance,
                    'progress_percentage' => $plan->progress_percentage,
                ];
            }),
            'created_at' => $case->created_at?->toISOString(),
            'created_at_diff' => $case->created_at?->diffForHumans(),
            'updated_at' => $case->updated_at?->toISOString(),
            'updated_at_diff' => $case->updated_at?->diffForHumans(),
        ];

        return response()->json($formattedData);
    }
}