<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanType;
use App\Models\User;
use App\Models\Repayment;
use App\Models\Broker;
use App\Models\Borrower;
use App\Models\DebtRecoveryCase;
use App\Models\RecoveryStatus;
use App\Models\RecoveryPriority;
use App\Models\RecoveryCaseNote;
use App\Models\LoanCycle;
use App\Pdf\LoanPDF;
use App\Services\LoanCalculator;
use App\Services\SignatureService;
use App\Services\LoanAgreementService;
use App\Services\NplDetectionService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

use TCPDF;

class LoanController extends Controller
{
    protected $loanCalculator;
    protected $signatureService;
    protected $loanAgreementService;
    protected $nplDetectionService;

    public function __construct(
        LoanCalculator $loanCalculator,
        SignatureService $signatureService,
        LoanAgreementService $loanAgreementService,
        NplDetectionService $nplDetectionService
    ) {
        $this->loanCalculator = $loanCalculator;
        $this->signatureService = $signatureService;
        $this->loanAgreementService = $loanAgreementService;
        $this->nplDetectionService = $nplDetectionService;
    }

    /**
     * Store a newly created loan
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        Log::debug('Loan Store Request Data:', $request->all());
        
        $requestData = $request->all();
        
        // Determine which user to save signature for
        $signatureUser = null;
        if ($user->role === 'admin' || $user->role === 'teller') {
            $signatureUser = User::find($request->user_id);
        } else {
            $signatureUser = $user;
        }
        
        // Check for force create header
        $forceCreate = $request->header('X-Force-Create') === 'true';
        
        // Set default values based on user role
        if ($user->role === 'broker') {
            $requestData['broker_status'] = 1;
            $requestData['status'] = 'pending';
        } elseif (!in_array($user->role, ['admin', 'teller'])) {
            $requestData['status'] = 'pending';
            $requestData['broker_status'] = 0;
        }
        
        // Handle consent
        $requestData['consent'] = $request->has('consent') && $request->consent === '1';
        $requestData['consent_date'] = $requestData['consent'] ? now() : null;
        
        $rules = [
            'loan_type_id' => 'required|exists:loan_types,id',
            'amount' => 'required|numeric|min:1',
            'borrow_date' => 'required|date',
            'due_date' => 'nullable|date|after:borrow_date',
            'status' => 'required|in:pending,approved,disbursed,repaid',
            'reason' => 'required|string|min:10',
            'guarantor_id' => 'nullable|exists:users,id',
            'guarantor_relationship' => 'nullable|string|max:100',
            'loan_officer_id' => 'nullable|exists:users,id',
            'consent' => 'required|accepted',
        ];
        
        // User validation based on role
        if ($user->role === 'admin' || $user->role === 'teller') {
            $rules['user_id'] = 'required|exists:users,id';
            $rules['broker_status'] = 'required|in:0,1';
        } elseif ($user->role === 'broker') {
            $rules['user_id'] = [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($user) {
                    $isValidBorrower = User::where('id', $value)
                        ->where('role', 'borrower')
                        ->whereHas('borrower', function($query) use ($user) {
                            $query->where('broker_id', $user->broker->id);
                        })
                        ->exists();
                    
                    if (!$isValidBorrower) {
                        $fail('The selected borrower is not assigned to you.');
                    }
                }
            ];
        } else {
            $rules['user_id'] = 'required|in:'.$user->id;
            $requestData['user_id'] = $user->id;
            $requestData['broker_status'] = 0;
        }
        
        try {
            // Get the user ID to check
            $userId = $requestData['user_id'] ?? ($request->user_id ?? null);
            
            // Enhanced duplicate check with all active loans
            if (!$forceCreate && $userId) {
                $activeLoans = Loan::with(['loanType', 'user', 'repayments'])
                    ->where('user_id', $userId)
                    ->whereNotIn('status', ['repaid', 'rejected', 'completed'])
                    ->where(function($query) {
                        $query->where('status', 'pending')
                            ->orWhere('status', 'approved')
                            ->orWhere('status', 'disbursed')
                            ->orWhere('status', 'active');
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                if ($activeLoans->count() > 0) {
                    $activeLoansData = [];
                    $now = Carbon::now();
                    
                    foreach ($activeLoans as $existingLoan) {
                        $dueDate = $this->loanCalculator->calculateDueDate($existingLoan);
                        if (!$dueDate) {
                            $dueDate = Carbon::parse($existingLoan->borrow_date)->addDays(30);
                        }
                        
                        $daysUntilDue = $now->diffInDays($dueDate, false);
                        
                        $totalRepayments = $existingLoan->repayments ? $existingLoan->repayments->sum('amount') : 0;
                        $interest = ($existingLoan->loanType->interest_rate / 100) * $existingLoan->amount;
                        $principalPlusInterest = $existingLoan->amount + $interest;
                        $outstandingBalance = max($principalPlusInterest - $totalRepayments, 0);
                        
                        $activeLoansData[] = [
                            'id' => $existingLoan->id,
                            'amount' => $existingLoan->amount,
                            'borrow_date' => $existingLoan->borrow_date,
                            'borrow_date_formatted' => Carbon::parse($existingLoan->borrow_date)->format('M d, Y'),
                            'due_date' => $dueDate->toDateString(),
                            'due_date_formatted' => $dueDate->format('M d, Y'),
                            'status' => $existingLoan->status,
                            'status_display' => ucfirst($existingLoan->status),
                            'days_until_due' => (int)$daysUntilDue,
                            'days_until_due_text' => $daysUntilDue > 0 ? $daysUntilDue . ' days remaining' : ($daysUntilDue == 0 ? 'Due today' : abs($daysUntilDue) . ' days overdue'),
                            'loan_type' => $existingLoan->loanType ? $existingLoan->loanType->name : 'Standard Loan',
                            'interest_rate' => $existingLoan->loanType ? $existingLoan->loanType->interest_rate : 0,
                            'period' => $existingLoan->loanType ? $existingLoan->loanType->period . ' ' . $existingLoan->loanType->unit : 'N/A',
                            'total_repayments' => $totalRepayments,
                            'outstanding_balance' => max(0, $outstandingBalance),
                            'borrower_name' => $existingLoan->user ? $existingLoan->user->name : 'Unknown',
                        ];
                    }
                    
                    $message = 'This borrower already has ' . $activeLoans->count() . ' active loan(s). Would you like to create another one?';
                    
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'duplicate' => true,
                        'active_loans' => $activeLoansData,
                        'total_active' => $activeLoans->count()
                    ], 409);
                }
            }
            
            $validatedData = $request->validate($rules);
            
            // Merge all data
            $loanData = array_merge($validatedData, [
                'status' => $requestData['status'] ?? 'pending',
                'broker_status' => $requestData['broker_status'] ?? 0,
                'consent' => $requestData['consent'],
                'consent_date' => $requestData['consent_date'],
                'cycle' => 1, // Start with cycle 1
            ]);
            
            // Create the loan
            $loan = Loan::create($loanData);

            // ============ CREATE INITIAL CYCLE ============
            try {
                $initialCycle = $this->loanCalculator->createInitialCycle($loan);
                
                // Update loan with initial cycle data
                $loan->refresh();
                
                Log::info('Initial cycle created for loan #' . $loan->id, [
                    'cycle_id' => $initialCycle->id,
                    'new_balance' => $initialCycle->new_balance,
                    'due_date' => $initialCycle->due_date->format('Y-m-d')
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create initial cycle for loan #' . $loan->id, [
                    'error' => $e->getMessage()
                ]);
                // Continue - the loan was created but cycle creation failed
            }
            
            // Handle signature if provided
            if ($request->has('signature_data') && !empty($request->signature_data)) {
                $signatureResult = $this->signatureService->saveSignature($request->signature_data, $signatureUser);
                if (!$signatureResult['success']) {
                    Log::error('Signature save failed:', $signatureResult);
                }
            }
            
            // Generate agreement if consent was given
            if ($loan->consent) {
                try {
                    $this->loanAgreementService->generateLoanAgreement($loan);
                } catch (\Exception $e) {
                    Log::error('Failed to generate loan agreement:', ['error' => $e->getMessage()]);
                }
            }
            
            // Return JSON response for AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Loan created successfully.',
                    'loan' => $loan->load(['user', 'loanType', 'cycles']),
                    'redirect' => route('loans.index')
                ]);
            }
            
            return redirect()->route('loans.index')->with('success', 'Loan created successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()->withInput()->withErrors($e->errors());
            
        } catch (\Exception $e) {
            Log::error('Loan creation failed:', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create loan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create loan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Create the initial cycle for a loan
     */
    protected function createInitialCycle(Loan $loan): void
    {
        if (!$loan->loanType) {
            Log::warning('Cannot create initial cycle - loan type not found for loan #' . $loan->id);
            return;
        }

        $interestRate = $loan->loanType->interest_rate;
        $interest = ($interestRate / 100) * $loan->amount;
        $dueDate = $this->loanCalculator->calculateDueDate($loan);
        
        if (!$dueDate) {
            $dueDate = Carbon::parse($loan->borrow_date)->addDays(30);
        }

        LoanCycle::create([
            'loan_id' => $loan->id,
            'cycle_number' => 1,
            'previous_balance' => 0,
            'interest_capitalized' => $interest,
            'new_balance' => $loan->amount + $interest,
            'interest_rate' => $interestRate,
            'start_date' => $loan->borrow_date,
            'due_date' => $dueDate,
            'status' => 'active',
            'notes' => 'Initial loan cycle',
        ]);

        // Set the due date on the loan
        $loan->due_date = $dueDate;
        $loan->calculated_due_date = $dueDate;
        $loan->cycle = 1;
        $loan->save();

        Log::info('Initial cycle created for loan #' . $loan->id . ' with due date: ' . $dueDate->format('Y-m-d'));
    }

    /**
     * Update the specified loan in storage.
     */
    public function update(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'loan_type_id'   => 'required|exists:loan_types,id',
            'amount'         => 'required|numeric|min:1',
            'borrow_date'    => 'required|date',
            'status'         => 'required|in:pending,approved,disbursed,repaid,overdue,defaulted',
            'broker_status'  => 'required|in:0,1',
            'reason'         => 'required|string|min:10',
            'guarantor_id'   => 'nullable|exists:users,id',
            'guarantor_relationship' => 'nullable|string|max:100',
            'loan_officer_id' => 'nullable|exists:users,id',
            'consent'        => 'sometimes|boolean',
            'due_date'       => 'nullable|date',
        ]);

        // Handle consent update
        if ($request->has('consent') && $request->consent === '1' && !$loan->consent) {
            $validated['consent'] = true;
            $validated['consent_date'] = now();
        } elseif (!$request->has('consent') || $request->consent !== '1') {
            $validated['consent'] = false;
            $validated['consent_date'] = null;
        }

        // Handle signature update if provided
        if ($request->has('signature_data') && !empty($request->signature_data)) {
            $signatureUser = $loan->user;
            $signatureResult = $this->signatureService->saveSignature($request->signature_data, $signatureUser);
            if (!$signatureResult['success']) {
                Log::error('Signature save failed during update:', $signatureResult);
            }
        }

        // Check if status is being changed to disbursed - update NPL fields
        if (isset($validated['status']) && $validated['status'] === 'disbursed') {
            if ($loan->loanType) {
                $calculatedDueDate = $this->loanCalculator->calculateDueDate($loan);
                if ($calculatedDueDate) {
                    $loan->calculated_due_date = $calculatedDueDate;
                    $loan->npl_trigger_threshold = $loan->getNplThreshold();
                    $validated['due_date'] = $calculatedDueDate;
                }
            }
        }

        $loan->update($validated);

        // If loan status changed to defaulted, create recovery case
        if ($request->has('status') && $request->status === 'defaulted') {
            $this->createRecoveryCaseFromLoan($loan);
        }

        // If AJAX request, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Loan updated successfully.',
                'loan' => $loan->fresh(['user', 'loanType', 'guarantor', 'loanOfficer'])
            ]);
        }

        return redirect()->route('loans.edit', $loan->id)
                        ->with('success', 'Loan updated successfully.');
    }

    /**
     * Display the specified loan
     */
    public function show($id, $loanId = null)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // ============================================================
        // BASIC DATA
        // ============================================================
        $brokers = Broker::with('user')->get();
        $borrowers = Borrower::all();
        $today = Carbon::now();

        // ============================================================
        // LOAD LOAN WITH REQUIRED RELATIONSHIPS
        // ============================================================
        $loanQuery = Loan::with([
            'disbursements',
            'repayments',
            'loanType',
            'user.borrower',
            'guarantor',
            'loanOfficer',
            'cycles'
        ]);

        try {

            if ($user->role === 'admin') {

                $loan = $loanQuery
                    ->where('user_id', $id)
                    ->where('id', $loanId)
                    ->firstOrFail();

            } elseif (in_array($user->role, ['broker', 'teller'])) {

                $loan = $loanQuery
                    ->findOrFail($loanId);

            } elseif ($user->role === 'borrower') {

                $loan = $loanQuery
                    ->where('id', $loanId)
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['approved', 'disbursed'])
                    ->firstOrFail();

            } else {

                abort(403, 'Unauthorized role');
            }

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'error' => 'Loan not found'
            ], 404);
        }

        // ============================================================
        // VALIDATE LOAN TYPE
        // ============================================================
        if (!$loan->loanType) {
            abort(422, 'Loan type is not defined for this loan');
        }

        // ============================================================
        // LOAN CALCULATOR SERVICE
        // ============================================================
        $metrics = $this->loanCalculator->calculateLoanMetrics($loan);

        // ============================================================
        // GET ACTIVE CYCLE
        // ============================================================
        $activeCycle = $this->loanCalculator->getActiveCycle($loan);

        $cycleCalculation = null;

        if ($activeCycle) {
            $cycleCalculation = $this->loanCalculator
                ->calculateCycleBalance($loan, $activeCycle);
        }

        // Always work with an array
        $cycleCalculation = $cycleCalculation ?? [];

        // ============================================================
        // CYCLE CALCULATED VALUES
        // ============================================================

        $cycleNumber = $cycleCalculation['cycle_number'] ?? $activeCycle?->cycle_number ?? 1;
        $newCycleBalance = $cycleCalculation['new_balance'] ?? 0;
        $cycleInterest = $cycleCalculation['interest'] ?? 0;
        $cycleRepayments = $cycleCalculation['total_repayments'] ?? 0;
        $outstandingAfterRepayments = $cycleCalculation['outstanding_after_repayments'] ?? 0;
        $cyclePenalty = $cycleCalculation['penalty'] ?? 0;
        $cycleOutstanding = $cycleCalculation['final_outstanding'] ?? 0;
        $cycleDaysOverdue = $cycleCalculation['days_overdue'] ?? 0;
        $cycleGraceDaysRemaining = $loan->getRemainingGraceDays();
        $cycleGraceDaysBalance = $loan->grace_days_balance ?? 0;
        $isFullyPaid = $cycleOutstanding <= 0;
        $cycleBalanceFull = $cycleCalculation['new_balance'] ?? $metrics['principal_plus_interest'] ?? 0;

        // ============================================================
        // PENALTY VALUES
        // ============================================================

        $penaltyAmount = $cycleCalculation['penalty'] ?? $metrics['penalty_amount'] ?? 0;
        $outstandingAtDue = $cycleCalculation['outstanding_at_due'] ?? $metrics['outstanding_at_due'] ?? 0;
        $daysSubjectToPenalty = $cycleCalculation['days_subject_to_penalty'] ?? 0;
        $penaltyRate = $cycleCalculation['penalty_rate'] ?? $metrics['base_penalty_rate'] ?? 0;

        // ============================================================
        // GET CLIENT TYPE
        // ============================================================
        $borrower = $loan->user->borrower;
        $broker = $borrower->broker ?? null;
        $client_type = $borrower->client_type ?? 0;

        // ============================================================
        // GET BROKER FEES
        // ============================================================
        $is_brokered = $loan->broker_status == 1;
        $brokerRate = 0;
        $penalty_rate = 0;
        $total_broker_fees = 0;

        if ($is_brokered && $broker) {

            $clientType = $borrower->client_type ?? 0;

            $brokerRate = ($clientType == 0)
                ? ($broker->interest_client ?? 0)
                : ($broker->interest_broker ?? 0);

            $penalty_rate = ($clientType == 0)
                ? ($broker->penalty_client ?? 0)
                : ($broker->penalty_broker ?? 0);

            $total_broker_fees =
                $metrics['total_broker_fees'] ?? 0;
        }

        // ============================================================
        // SIGNATURE STATUS
        // ============================================================
        $signatureStatus = $this->signatureService->checkSignature($loan->user);
        $hasSignature = $signatureStatus['hasSignature'] ?? false;
        $signatureUrl = $signatureStatus['signatureUrl'] ?? null;

        // ============================================================
        // DEFAULT STATUS
        // ============================================================
        $is_defaulted =
            $loan->isDefaulted()
            || ($metrics['is_defaulted'] ?? false);

        $days_overdue =
            $metrics['days_overdue'] ?? 0;

        $penalty_amount = $metrics['penalty_amount'] ?? 0;

        $default_threshold_days = $metrics['default_threshold_days'] ?? 30;

        // ============================================================
        // CASES / RECOVERY MODAL DATA
        // ============================================================
        $statuses = \App\Models\RecoveryStatus::all();
        $priorities = \App\Models\RecoveryPriority::all();
        $officers = User::whereIn('role', [
            'admin',
            'teller'
        ])->get();

        $actionTypes = \App\Models\ActionType::all();

        // ============================================================
        // ALL BORROWERS
        // ============================================================
        $allBorrowers = User::borrowers()
            ->with([
                'borrower',
                'loans' => function ($q) {
                    $q->whereIn('status', [
                        'disbursed',
                        'approved',
                        'overdue',
                        'defaulted'
                    ])
                    ->with([
                        'loanType',
                        'repayments'
                    ]);
                }
            ])
            ->get();

        // ============================================================
        // RECOVERY CASE
        // ============================================================
        $recoveryCase = DebtRecoveryCase::where(
            'loan_id',
            $loan->id
        )->first();

        // ============================================================
        // INTEREST RATE AND PERIOD
        // ============================================================
        $interest_rate =
            $metrics['interest_rate']
            ?? $loan->loanType->interest_rate
            ?? 0;

        $period =
            $metrics['period']
            ?? $loan->loanType->period
            ?? 0;

        $period_unit =
            $metrics['period_unit']
            ?? $loan->loanType->unit
            ?? 'days';

        // ============================================================
        // DUE DATE
        // ============================================================
        $due_date =
            $metrics['due_date'] ?? null;

        $days_late =
            $metrics['days_late'] ?? 0;

        // ============================================================
        // FINANCIAL METRICS
        // ============================================================
        $principal =
            $metrics['principal']
            ?? $loan->amount;

        $interest =
            $metrics['interest']
            ?? 0;

        $base_penalty_rate =
            $metrics['base_penalty_rate']
            ?? 0;

        $outstanding_at_due =
            $metrics['outstanding_at_due']
            ?? 0;

        // ============================================================
        // RETURN VIEW
        // ============================================================
        return view('loans.show', compact(

            // Loan
            'loan',

            // General data
            'brokers',
            'borrowers',
            'broker',
            'today',

            // Signature
            'signatureStatus',
            'hasSignature',
            'signatureUrl',

            // Recovery / cases
            'statuses',
            'priorities',
            'officers',
            'actionTypes',
            'allBorrowers',
            'recoveryCase',

            // Calculator
            'metrics',
            'activeCycle',
            'cycleCalculation',

            // Cycle
            'cycleNumber',
            'newCycleBalance',
            'cycleInterest',
            'cycleRepayments',
            'outstandingAfterRepayments',
            'cyclePenalty',
            'cycleOutstanding',
            'cycleDaysOverdue',
            'cycleGraceDaysRemaining',
            'cycleGraceDaysBalance',
            'isFullyPaid',
            'cycleBalanceFull',

            // Penalty
            'outstandingAtDue',
            'penaltyAmount',
            'daysSubjectToPenalty',
            'penaltyRate',

            // Client / broker
            'client_type',
            'is_brokered',
            'brokerRate',
            'penalty_rate',
            'total_broker_fees',

            // Default
            'is_defaulted',
            'days_overdue',
            'penalty_amount',
            'default_threshold_days',

            // Financial metrics
            'interest_rate',
            'period',
            'period_unit',
            'due_date',
            'days_late',
            'principal',
            'interest',
            'base_penalty_rate'
        ));
    }

    /**
     * Show the create loan form
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        
        $users = collect();
        $loanTypes = LoanType::all();

        if ($user->role === 'admin' || $user->role === 'teller') {
            $users = User::all();
            $loans = Loan::with('user')->where('status', '!=', 'repaid')->get();
            $all_borrower_loans = Loan::with('user')->get();
            
            $guarantors = User::where('role', 'borrower')
                ->where('id', '!=', $request->user_id ?? $user->id)
                ->get();
                
            $loanOfficers = User::whereIn('role', ['admin', 'teller'])->get();
            
        } elseif ($user->role === 'broker') {
            $users = User::whereHas('borrower', function($query) use ($user) {
                $query->where('broker_id', $user->broker->id);
            })
            ->where('role', 'borrower')
            ->get();
            $loans = Loan::with('user')->where('status', '!=', 'repaid')
                ->whereIn('user_id', $users->pluck('id'))
                ->get();
                
            $guarantors = $users->where('id', '!=', $request->user_id ?? $user->id);
            $loanOfficers = collect();
        } else {
            $loans = $user->loans()->where('status', '!=', 'repaid')->get();
            $users = collect([$user]);
            $guarantors = User::where('role', 'borrower')->where('id', '!=', $user->id)->get();
            $loanOfficers = User::whereIn('role', ['admin', 'teller'])->get();
        }

        $signatureUser = null;
        $hasExistingSignature = false;
        $existingSignatureUrl = null;

        if ($user->role === 'borrower') {
            $signatureUser = $user;
        } elseif (($user->role === 'admin' || $user->role === 'teller' || $user->role === 'broker') && $request->has('user_id')) {
            $signatureUser = User::find($request->user_id);
        }

        if ($signatureUser) {
            $signatureStatus = app(SignatureService::class)->checkSignature($signatureUser);
            $hasExistingSignature = $signatureStatus['hasSignature'];
            $existingSignatureUrl = $signatureStatus['signatureUrl'];
        }

        return view('loans.create', [
            'users' => $users,
            'loanTypes' => $loanTypes,
            'loans' => $loans,
            'all_borrower_loans' => $all_borrower_loans ?? collect(),
            'guarantors' => $guarantors,
            'loanOfficers' => $loanOfficers,
            'signatureUser' => $signatureUser,
            'hasExistingSignature' => $hasExistingSignature,
            'existingSignatureUrl' => $existingSignatureUrl,
        ]);
    }

    /**
     * Show the form for editing the specified loan.
     */
    public function edit(Loan $loan)
    {
        // Check permissions
        $user = auth()->user();
        
        if ($user->role === 'borrower' && $loan->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
        
        if ($user->role === 'broker') {
            $broker = $user->broker;
            $isClient = $loan->user->borrower && $loan->user->borrower->broker_id === $broker->id;
            if (!$isClient && $loan->user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }
        }
        
        // Load relationships
        $loan->load(['user', 'loanType', 'guarantor', 'loanOfficer']);
        
        // Get all loan types for the dropdown
        $loanTypes = LoanType::all();
        
        // Check if user has a signature
        $hasSignature = false;
        $signatureUrl = null;
        if ($loan->user && $loan->user->signature) {
            $hasSignature = true;
            $signatureUrl = asset('storage/' . $loan->user->signature);
        }
        
        // If this is an AJAX request, return JSON
        if (request()->ajax() || request()->wantsJson()) {
            // Format loan types for the dropdown
            $formattedLoanTypes = $loanTypes->map(function($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'period' => $type->period,
                    'unit' => $type->unit,
                    'interest_rate' => $type->interest_rate,
                ];
            });
            
            // Get guarantors and loan officers for the dropdowns
            $guarantors = User::where('role', 'borrower')
                ->where('id', '!=', $loan->user_id)
                ->get(['id', 'name', 'email']);
                
            $loanOfficers = User::whereIn('role', ['admin', 'teller'])
                ->get(['id', 'name', 'email']);
            
            return response()->json([
                'id' => $loan->id,
                'user_id' => $loan->user_id,
                'user_name' => $loan->user->name ?? '',
                'user_email' => $loan->user->email ?? '',
                'amount' => $loan->amount,
                'borrow_date' => $loan->borrow_date instanceof \Carbon\Carbon ? $loan->borrow_date->format('Y-m-d') : $loan->borrow_date,
                'loan_type_id' => $loan->loan_type_id,
                'loan_type_name' => $loan->loanType->name ?? '',
                'status' => $loan->status,
                'reason' => $loan->reason ?? '',
                'guarantor_id' => $loan->guarantor_id,
                'guarantor_relationship' => $loan->guarantor_relationship ?? '',
                'loan_officer_id' => $loan->loan_officer_id,
                'consent' => (bool) $loan->consent,
                'broker_status' => (string) $loan->broker_status,
                'due_date' => $loan->due_date instanceof \Carbon\Carbon ? $loan->due_date->format('Y-m-d') : $loan->due_date,
                'capitalized_interest' => $loan->capitalized_interest ?? 0,
                'cycle' => $loan->cycle ?? 1,
                'has_signature' => $hasSignature,
                'signature_url' => $signatureUrl,
                // Add dropdown data
                'loan_types' => $formattedLoanTypes,
                'guarantors' => $guarantors,
                'loan_officers' => $loanOfficers,
            ]);
        }
        
        // Otherwise return the edit view
        $guarantors = User::where('role', 'borrower')
            ->where('id', '!=', $loan->user_id)
            ->get();
        $loanOfficers = User::whereIn('role', ['admin', 'teller'])->get();
        
        return view('loans.edit', [
            'loan' => $loan,
            'loanTypes' => $loanTypes,
            'guarantors' => $guarantors,
            'loanOfficers' => $loanOfficers,
            'hasSignature' => $hasSignature,
            'signatureUrl' => $signatureUrl,
        ]);
    }

    /**
     * Delete a loan
     */
    public function destroy($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->delete();
        return redirect()->route('loans.index');
    }

    // ============ PAYMENT PLAN METHODS ============

    public function getPaymentPlanPreview(Loan $loan, Request $request)
    {
        try {
            $activeCycle = $this->loanCalculator->getActiveCycle($loan);
            
            if (!$activeCycle) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active cycle found for this loan'
                ], 404);
            }

            $cycleCalculation = $this->loanCalculator->calculateCycleBalance($loan, $activeCycle);
            
            // Current cycle figures
            $principal = $activeCycle->previous_balance > 0 ? $activeCycle->previous_balance : $loan->amount;
            $interestRate = (float) $activeCycle->interest_rate;
            $interest = $principal * ($interestRate / 100);
            $fullBalance = $principal + $interest;
            $cycleRepayments = $loan->repayments()->where('loan_cycle_id', $activeCycle->id)->sum('amount');
            $outstanding = max(0, $fullBalance - $cycleRepayments);
            
            return response()->json([
                'success' => true,
                'data' => [
                    // Current cycle figures
                    'current_principal' => $principal,
                    'current_interest_rate' => $interestRate,
                    'current_interest' => $interest,
                    'current_balance' => $fullBalance,
                    'current_repayments' => $cycleRepayments,
                    'current_penalty' => $cycleCalculation['penalty'] ?? 0,
                    'current_total' => $cycleCalculation['final_outstanding'] ?? 0,
                    'days_overdue' => $cycleCalculation['days_overdue'] ?? 0,
                    'current_cycle' => $activeCycle->cycle_number,
                    'current_due_date' => $activeCycle->due_date->format('M d, Y'),
                    'outstanding' => $outstanding,
                    
                    // Default settings
                    'interest_rate' => $loan->loanType->interest_rate ?? 20,
                    'period_days' => $loan->loanType->period ?? 30,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Payment plan preview error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or update a payment plan
     */
    public function createPaymentPlan(Loan $loan, Request $request)
    {
        try {
            $request->validate([
                'interest_rate' => 'required|numeric|min:0|max:100',
                'period_days' => 'required|integer|min:1',
                'notes' => 'nullable|string|max:500',
                'plan_type' => 'nullable|in:standard,manual',
                'manual_new_balance' => 'nullable|numeric|min:0',
            ]);

            $interestRate = $request->input('interest_rate');
            $periodDays = $request->input('period_days');
            $notes = $request->input('notes', '');
            $planType = $request->input('plan_type', 'standard');
            $manualNewBalance = $request->input('manual_new_balance');

            // Check if this is an edit or create
            $isEdit = $request->input('_method') === 'PUT' || $request->input('edit_mode') === 'true';
            
            if ($isEdit) {
                // Handle edit - update the existing payment plan cycle
                // This would update the cycle with new terms
                $cycleId = $request->input('cycle_id');
                $cycle = LoanCycle::findOrFail($cycleId);
                
                $cycle->update([
                    'interest_rate' => $interestRate,
                    'due_date' => now()->addDays($periodDays),
                    'notes' => ($cycle->notes ?? '') . "\n" . "Updated: {$notes}",
                ]);
                
                // Update loan
                $loan->forbearance_until = now()->addDays($periodDays);
                $loan->calculated_due_date = now()->addDays($periodDays);
                $loan->due_date = now()->addDays($periodDays);
                $loan->save();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Payment plan updated successfully!',
                    'data' => ['cycle' => $cycle]
                ]);
            }

            // ============ CREATE NEW PAYMENT PLAN ============
            if ($planType === 'manual' && $manualNewBalance !== null) {
                $newCycle = $loan->manualBalanceAdjustment($manualNewBalance, $notes);
                $message = 'Manual balance adjustment applied successfully! New balance: KES ' . number_format($manualNewBalance, 2);
            } else {
                $newCycle = $loan->startPaymentPlan($interestRate, $periodDays);
                $message = 'Payment plan created successfully! Penalties have been waived.';
            }

            // Add additional notes
            if ($notes && $planType !== 'manual') {
                $newCycle->notes = ($newCycle->notes ? $newCycle->notes . "\n" : '') . $notes;
                $newCycle->save();
            }

            Log::info('Payment plan created for loan #' . $loan->id, [
                'interest_rate' => $interestRate,
                'period_days' => $periodDays,
                'plan_type' => $planType,
                'manual_balance' => $manualNewBalance,
                'new_cycle' => $newCycle->cycle_number,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'cycle' => $newCycle,
                    'new_balance' => $newCycle->new_balance,
                    'cycle_number' => $newCycle->cycle_number,
                    'due_date' => $newCycle->due_date->format('Y-m-d'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment plan creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ============ ROLLOVER METHODS ============

    /**
     * Execute a loan rollover
     * Uses the loan_type_id from the loan to get period, unit, and interest_rate
     */
    public function rollover(Request $request, Loan $loan)
    {
        if (!in_array(auth()->user()->role, ['admin', 'teller'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:500',
            'interest_type' => 'required|in:simple,compound',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'period_days' => 'nullable|integer|min:1',
            'waive_penalty' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Log the loan type being used
            Log::info('Rollover requested for loan #' . $loan->id, [
                'loan_type_id' => $loan->loan_type_id,
                'loan_type_name' => $loan->loanType ? $loan->loanType->name : 'Unknown',
                'period' => $loan->loanType ? $loan->loanType->period : 'Unknown',
                'unit' => $loan->loanType ? $loan->loanType->unit : 'Unknown',
                'current_balance' => $loan->amount,
                'current_cycle' => $loan->cycle
            ]);

            $result = $this->loanCalculator->executeRollover($loan, [
                'interest_type' => $request->interest_type ?? 'compound',
                'notes' => $request->notes,
                'interest_rate' => $request->interest_rate,
                'period_days' => $request->period_days,
                'waive_penalty' => $request->waive_penalty,
            ]);

            // Log the result
            Log::info('Rollover completed for loan #' . $loan->id, [
                'new_cycle' => $result['data']['cycle_number'],
                'new_balance' => $result['data']['new_balance'],
                'new_due_date' => $result['data']['due_date'],
                'period_used' => $result['data']['period'] . ' ' . $result['data']['period_unit']
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Loan rollover failed for loan #' . $loan->id, [
                'loan_type_id' => $loan->loan_type_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rollover preview data
     */
    public function getRolloverPreview(Request $request, Loan $loan)
    {
        if (!in_array(auth()->user()->role, ['admin', 'teller', 'borrower'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            $previewData = $this->loanCalculator->getRolloverPreview($loan);
            
            return response()->json([
                'success' => true,
                'data' => $previewData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show rollover statement
     */
    public function rolloverStatement(Loan $loan)
    {
        if (!in_array(auth()->user()->role, ['admin', 'teller', 'borrower'])) {
            abort(403, 'Unauthorized action.');
        }

        $statement = $loan->getRolloverStatement();
        
        return view('loans.rollover-statement', compact('loan', 'statement'));
    }

    /**
     * Get loan cycles data for modal
     */
    public function getCycles(Loan $loan)
    {
        if (!in_array(auth()->user()->role, ['admin', 'teller', 'borrower'])) {
            abort(403, 'Unauthorized action.');
        }

        $cycles = $loan->cycles()->orderBy('cycle_number', 'asc')->get();
        
        return response()->json([
            'success' => true,
            'cycles' => $cycles->map(function($cycle) {
                return [
                    'id' => $cycle->id,
                    'cycle_number' => $cycle->cycle_number,
                    'previous_balance' => $cycle->previous_balance,
                    'interest_capitalized' => $cycle->interest_capitalized,
                    'new_balance' => $cycle->new_balance,
                    'interest_rate' => $cycle->interest_rate,
                    'start_date' => $cycle->start_date->format('Y-m-d'),
                    'due_date' => $cycle->due_date->format('Y-m-d'),
                    'status' => $cycle->status,
                    'status_label' => $cycle->status_label,
                    'notes' => $cycle->notes,
                    'is_overdue' => $cycle->isOverdue(),
                    'days_in_cycle' => $cycle->days_in_cycle,
                ];
            }),
            'total_cycles' => $cycles->count(),
            'total_capitalized' => $cycles->sum('interest_capitalized'),
            'total_balance' => $cycles->last() ? $cycles->last()->new_balance : 0,
            'current_cycle' => $loan->cycle,
            'active_cycles' => $cycles->where('status', 'active')->count(),
        ]);
    }

    // ============ FORBEARANCE METHODS ============


    public function grantForbearance(Loan $loan, Request $request)
    {
        try {
            $request->validate([
                'days' => 'required|integer|min:1|max:90',
                'reason' => 'nullable|string|max:500',
            ]);

            $days = $request->input('days', 30);
            $reason = $request->input('reason', 'Granted forbearance');

            $loan->grantForbearance($days, $reason);

            Log::info('Forbearance granted for loan #' . $loan->id, [
                'days' => $days,
                'reason' => $reason,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Forbearance granted for ' . $days . ' days.',
                'data' => [
                    'forbearance_until' => $loan->forbearance_until,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Grant forbearance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function endForbearance(Loan $loan, Request $request)
    {
        try {
            $loan->endForbearance();

            Log::info('Forbearance ended for loan #' . $loan->id, [
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Forbearance ended successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('End forbearance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ============ RECOVERY METHODS ============

    /**
     * Start recovery process for a loan
     */
    public function startRecovery(Loan $loan, Request $request)
    {
        try {
            $loan->startRecovery();

            Log::info('Recovery started for loan #' . $loan->id, [
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recovery process started successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Start recovery error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    } 

    /**
     * Create recovery case from a defaulted loan
     */
    private function createRecoveryCaseFromLoan($loan)
    {
        // Check if recovery case already exists
        $existingCase = DebtRecoveryCase::where('loan_id', $loan->id)
            ->whereHas('status', function($q) {
                $q->whereIn('slug', ['open', 'in_progress', 'negotiation', 'legal']);
            })
            ->first();

        if ($existingCase) {
            return $existingCase;
        }

        // Calculate total debt
        $totalDisbursed = $loan->disbursements->sum('amount') ?? $loan->amount;
        $totalRepaid = $loan->repayments->sum('amount') ?? 0;
        $interest = ($loan->loanType->interest_rate / 100) * $loan->amount;
        $penalty = $this->loanCalculator->calculateLoanMetrics($loan)['penalty_amount'] ?? 0;

        $totalDebt = max(0, $totalDisbursed + $interest + $penalty - $totalRepaid);

        // Get status and priority
        $status = RecoveryStatus::where('slug', 'open')->first();
        $priority = RecoveryPriority::where('slug', 'medium')->first();

        if (!$status || !$priority) {
            Log::error('Could not find recovery status or priority for loan #' . $loan->id);
            return null;
        }

        // Generate case number
        $year = now()->format('Y');
        $count = DebtRecoveryCase::whereYear('created_at', $year)->count() + 1;
        $caseNumber = 'DR-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        // Find default assignee
        $assignedTo = User::whereIn('role', ['admin', 'teller'])->first();

        // Create the recovery case
        $case = DebtRecoveryCase::create([
            'user_id' => $loan->user_id,
            'loan_id' => $loan->id,
            'case_number' => $caseNumber,
            'total_debt_amount' => $totalDebt,
            'principal_outstanding' => max(0, $loan->amount - min($totalRepaid, $loan->amount)),
            'interest_outstanding' => max(0, $interest - max(0, $totalRepaid - $loan->amount)),
            'penalty_outstanding' => $penalty,
            'fees_outstanding' => 0,
            'default_date' => $loan->default_date ?? now(),
            'days_in_default' => $loan->days_overdue ?? 0,
            'status_id' => $status->id,
            'priority_id' => $priority->id,
            'assigned_to' => $assignedTo ? $assignedTo->id : null,
            'recovery_officer' => $assignedTo ? $assignedTo->name : null,
            'notes' => "Recovery case created from loan #{$loan->id} marked as defaulted.",
            'created_by' => auth()->id(),
        ]);

        // Create initial note
        RecoveryCaseNote::create([
            'case_id' => $case->id,
            'note_type' => 'alert',
            'note' => "Case created from loan #{$loan->id} marked as defaulted. Total debt: KES " . number_format($totalDebt, 2),
            'created_by' => auth()->id(),
        ]);

        Log::info("Recovery case created from loan #{$loan->id}: {$caseNumber}");

        return $case;
    }

    // ============ PDF GENERATION ============

    /**
     * Generate PDF statement for a loan
     */
    public function generatePdf($id, $loanId = null)
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }
    
        $loanQuery = Loan::with([
            'disbursements',
            'repayments',
            'loanType',
            'user.borrower',
            'user.borrower.broker'
        ]);
    
        if ($user->role === 'admin') {
            $loan = $loanQuery->where('user_id', $id)
                        ->where('id', $loanId)
                        ->firstOrFail();
        } elseif (in_array($user->role, ['broker', 'teller'])) {
            $loan = $loanQuery->findOrFail($loanId);
        } elseif ($user->role === 'borrower') {
            $loan = $loanQuery->where('id', $loanId)
                        ->where('user_id', $user->id)
                        ->whereIn('status', ['approved', 'disbursed'])
                        ->firstOrFail();
        } else {
            abort(403, 'Unauthorized role');
        }
    
        if (!$loan->loanType) {
            abort(422, 'Loan type is not defined for this loan');
        }
    
        $metrics = $this->loanCalculator->calculateLoanMetrics($loan);
        
        $pdf = new LoanPDF($loan);
        $pdf->AddPage();
        $pdf->loanDetails(
            $metrics['borrow_date'],
            $metrics['due_date'],
            $metrics['days_late'],
            $metrics['last_repayment_date'] ?? null
        );
        
        $paymentSchedule = $this->generatePaymentSchedule(
            $loan,
            Carbon::parse($metrics['borrow_date']),
            $metrics['due_date'],
            $metrics['principal'],
            $loan->loanType->interest_rate,
            $metrics['last_repayment_date'] ?? null
        );
        $pdf->paymentSchedule($paymentSchedule);
        
        $pdf->accountSummary([
            ['label' => 'Principal Amount', 'value' => 'KES ' . number_format($metrics['principal'], 2)],
            ['label' => 'Interest Rate', 'value' => $loan->loanType->interest_rate . '%'],
            ['label' => 'Total Interest', 'value' => 'KES ' . number_format($metrics['interest'], 2)],
            ['label' => 'Total Amount Due', 'value' => 'KES ' . number_format($metrics['principal_plus_interest'], 2)],
            ['label' => 'Total Paid', 'value' => 'KES ' . number_format($metrics['total_repayments'], 2)],
            ['label' => 'Current Balance', 'value' => 'KES ' . number_format($metrics['outstanding_balance'], 2)],
            ['label' => 'Days Late', 'value' => $metrics['days_late'] > 0 ? $metrics['days_late'] . ' days' : 'On time']
        ]);
        
        $transactions = collect()
            ->merge($loan->disbursements->map(function ($disbursement) use ($metrics) {
                return (object)[
                    'date' => $disbursement->disburse_date,
                    'description' => 'Loan Disbursement',
                    'type' => 'debit',
                    'amount' => $disbursement->amount,
                    'balance' => $metrics['principal_plus_interest']
                ];
            }))
            ->merge($loan->repayments->map(function ($repayment) {
                return (object)[
                    'date' => $repayment->repayment_date,
                    'description' => 'Loan Repayment',
                    'type' => 'credit',
                    'amount' => $repayment->amount,
                    'balance' => null
                ];
            }))
            ->sortBy('date');
        
        $runningBalance = $metrics['principal_plus_interest'];
        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            if ($transaction->type === 'credit') {
                $runningBalance -= $transaction->amount;
            }
            $transaction->balance = $runningBalance;
            return $transaction;
        });
        
        $pdf->transactionHistory($transactions);
        return $pdf->Output('D', 'loan-statement-' . $loan->id . '.pdf');
    }

    /**
     * Generate payment schedule for PDF
     */
    private function generatePaymentSchedule($loan, $startDate, $dueDate, $principal, $interestRate, $lastRepaymentDate = null)
    {
        $schedule = [];
        $period = $loan->loanType->period;
        $periodUnit = $loan->loanType->unit;
        $paymentDate = clone $startDate;
        $remainingPrincipal = $principal;
        
        switch ($periodUnit) {
            case 'months':
                $monthlyInterest = ($interestRate / 100) / 12 * $principal;
                $monthlyPrincipal = $principal / $period;
                
                for ($i = 1; $i <= $period; $i++) {
                    $paymentDate->addMonth();
                    $remainingPrincipal -= $monthlyPrincipal;
                    
                    $schedule[] = [
                        'date' => $paymentDate->format('Y-m-d'),
                        'principal' => $monthlyPrincipal,
                        'interest' => $monthlyInterest,
                        'total' => $monthlyPrincipal + $monthlyInterest,
                        'payment_date' => null,
                        'status' => $paymentDate->isPast() ? 'Overdue' : 'Pending'
                    ];
                }
                break;
                
            case 'weeks':
                $weeklyInterest = ($interestRate / 100) / 52 * $principal;
                $weeklyPrincipal = $principal / $period;
                
                for ($i = 1; $i <= $period; $i++) {
                    $paymentDate->addWeek();
                    $remainingPrincipal -= $weeklyPrincipal;
                    
                    $schedule[] = [
                        'date' => $paymentDate->format('Y-m-d'),
                        'principal' => $weeklyPrincipal,
                        'interest' => $weeklyInterest,
                        'total' => $weeklyPrincipal + $weeklyInterest,
                        'payment_date' => null,
                        'status' => $paymentDate->isPast() ? 'Overdue' : 'Pending'
                    ];
                }
                break;
                
            default:
                $schedule[] = [
                    'date' => $dueDate->format('Y-m-d'),
                    'principal' => $principal,
                    'interest' => $principal * ($interestRate / 100),
                    'total' => $principal * (1 + ($interestRate / 100)),
                    'payment_date' => null,
                    'status' => $dueDate->isPast() ? 'Overdue' : 'Pending'
                ];
        }
        
        foreach ($loan->repayments as $repayment) {
            $repaymentDate = Carbon::parse($repayment->repayment_date);
            
            foreach ($schedule as &$installment) {
                $installmentDate = Carbon::parse($installment['date']);
                
                if ($repaymentDate->gte($installmentDate) && is_null($installment['payment_date'])) {
                    $installment['payment_date'] = $repayment->repayment_date;
                    $installment['status'] = 'Paid';
                    break;
                }
            }
        }
        
        return $schedule;
    }

    /**
     * Download loan agreement
     */
    public function downloadAgreement($loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $user = auth()->user();
        
        if (!in_array($user->role, ['admin', 'teller']) && 
            $loan->user_id !== $user->id && 
            !($user->role === 'broker' && $loan->broker_status == 1)) {
            abort(403, 'Unauthorized');
        }

        $filePath = $this->loanAgreementService->generateLoanAgreement($loan);
        
        return response()->download($filePath, "loan_agreement_{$loan->id}.pdf");
    }

    /**
     * Show loan agreement
     */
    public function showAgreement($loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $user = auth()->user();
        
        if (!in_array($user->role, ['admin', 'teller']) && 
            $loan->user_id !== $user->id && 
            !($user->role === 'broker' && $loan->broker_status == 1)) {
            abort(403, 'Unauthorized');
        }

        $filePath = $this->loanAgreementService->generateLoanAgreement($loan);
        
        return response()->file($filePath);
    }

    /**
     * Save signature for loan
     */
    public function saveSignature(Request $request, Loan $loan)
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        $result = $this->signatureService->saveSignature($request->signature, $loan->user);

        if ($result['success']) {
            if ($loan->consent) {
                $this->loanAgreementService->generateLoanAgreement($loan);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Signature saved successfully',
                'signatureUrl' => $result['url']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to save signature: ' . $result['error']
        ], 500);
    }

    /**
     * Give consent for loan
     */
    public function giveConsent(Loan $loan)
    {
        $loan->giveConsent();
        
        $signatureStatus = $this->signatureService->checkSignature($loan->user);
        if ($signatureStatus['hasSignature']) {
            $this->loanAgreementService->generateLoanAgreement($loan);
        }
        
        return back()->with('success', 'Consent given successfully.');
    }

    // ============ INDEX ============

    public function index()
    {
        $user = auth()->user();   
        
        $loanTypes = LoanType::all();
        $users = User::where('status', 0)->get();
        $guarantors = User::where('role', 'borrower')->where('status', 0)->get();
        $loanOfficers = User::whereIn('role', ['admin', 'teller'])->where('status', 0)->get();

        if ($user->role === 'admin') {
            $allLoans = Loan::with([
                'user.borrower',
                'loanType',
                'disbursements',
                'repayments',
                'cycles'
            ])->orderBy('borrow_date', 'desc')->get();

            $stats = $this->calculateAdminStats($allLoans);

            return view('loans.index', [
                'allLoans' => $allLoans,
                'loans' => $allLoans,
                'stats' => $stats,
                'users' => $users,
                'loanTypes' => $loanTypes,
                'guarantors' => $guarantors,
                'loanOfficers' => $loanOfficers
            ]);
        }
        elseif ($user->role === 'broker') { 
            $broker = $user->broker;

            $brokerLoans = Loan::where('broker_status', 1)
                ->whereHas('user.borrower', function ($query) use ($broker) {
                    $query->where('broker_id', $broker->id);
                })
                ->with(['loanType', 'repayments', 'user.borrower', 'cycles'])
                ->orderBy('borrow_date', 'desc')
                ->get();

            $stats = $this->calculateBrokerStats($brokerLoans, $broker);

            $users = User::whereHas('borrower', function ($query) use ($user) {
                $query->where('broker_id', $user->broker->id);
            })
            ->where('role', 'borrower')
            ->where('status', 0)
            ->get();

            return view('loans.index', [
                'brokerLoans' => $brokerLoans,
                'loans' => $brokerLoans,
                'stats' => $stats,
                'broker' => $broker,
                'users' => $users,
                'loanTypes' => $loanTypes,
                'guarantors' => $guarantors,
                'loanOfficers' => $loanOfficers
            ]);
        }
        elseif ($user->role === 'borrower') {
            $userLoans = Loan::where('user_id', $user->id)
                ->with(['loanType', 'disbursements', 'repayments', 'cycles'])
                ->orderBy('borrow_date', 'desc')
                ->get();

            $stats = [
                'total_requested' => $userLoans->sum('amount'),
                'total_disbursed' => $userLoans->sum(function($loan) {
                    return $loan->disbursements->sum('amount');
                }),
                'total_repayments' => $userLoans->sum(function($loan) {
                    return $loan->repayments->sum('amount');
                }),
                'total_repaid' => $userLoans->sum(function($loan) {
                    return $loan->repayments->sum('amount');
                }),
                'activeLoans' => $userLoans->where('status', 'disbursed')->count(),
                'repaidLoans' => $userLoans->where('status', 'repaid')->count(),
            ];

            $users = collect([$user]);
            $guarantors = User::where('role', 'borrower')->where('id', '!=', $user->id)->get();

            return view('loans.index', [
                'userLoans' => $userLoans,
                'loans' => $userLoans,
                'stats' => $stats,
                'users' => $users,
                'loanTypes' => $loanTypes,
                'guarantors' => $guarantors,
                'loanOfficers' => $loanOfficers
            ]);
        }
        elseif ($user->role === 'teller') {
            $activeLoans = Loan::with(['user', 'loanType', 'cycles'])
                ->where('status', 'active')
                ->orderBy('borrow_date', 'desc')
                ->get();

            $stats = [
                'activeLoans' => $activeLoans->count(),
                'repaidLoans' => Loan::where('status', 'pending')->count(),
                'total_repayments' => $activeLoans->sum(function($loan) {
                    return $loan->repayments->sum('amount');
                }),
                'total_penalties' => $activeLoans->sum(function($loan) {
                    return $loan->repayments->sum('penalty_amount');
                })
            ];

            return view('loans.index', [
                'activeLoans' => $activeLoans,
                'loans' => $activeLoans,
                'stats' => $stats,
                'users' => $users,
                'loanTypes' => $loanTypes,
                'guarantors' => $guarantors,
                'loanOfficers' => $loanOfficers
            ]);
        }
        else {
            $userLoans = $user->loans()
                ->with(['loanType', 'cycles'])
                ->orderBy('borrow_date', 'desc')
                ->get();

            $stats = [
                'activeLoans' => $userLoans->where('status', 'disbursed')->count(),
                'repaidLoans' => $userLoans->where('status', 'repaid')->count(),
                'total_borrowed' => $userLoans->sum('amount'),
                'total_repaid' => $userLoans->sum(function($loan) {
                    return $loan->repayments->sum('amount');
                }),
                'total_penalties' => $userLoans->sum(function($loan) {
                    return $loan->repayments->sum('penalty_amount');
                })
            ];

            return view('loans.index', [
                'userLoans' => $userLoans,
                'loans' => $userLoans,
                'stats' => $stats,
                'users' => $users,
                'loanTypes' => $loanTypes,
                'guarantors' => $guarantors,
                'loanOfficers' => $loanOfficers
            ]);
        }
    }

    /**
     * Calculate admin statistics
     */
    protected function calculateAdminStats($allLoans)
    {
        $totalInterest = 0;
        $total_penalties = 0;
        $total_broker_fees = 0;
        $total_late_loans = 0;
        $loan_days_late = [];
        $repayment_periods = [];
        $loan_details = [];
        $loan_durations = [];

        foreach ($allLoans as $loan) {
            if (!$loan->loanType || !$loan->borrow_date) {
                continue;
            }

            $metrics = $this->loanCalculator->calculateLoanMetrics($loan);
            
            $totalInterest += $metrics['interest'];
            $total_penalties += $metrics['penalty_amount'];
            $total_broker_fees += $metrics['total_broker_fees'] ?? 0;

            if ($metrics['days_late'] > 0) {
                $total_late_loans++;
                $loan_days_late[] = $metrics['days_late'];
            }

            $loan_details[] = [
                'borrow_date' => $loan->borrow_date,
                'due_date' => $metrics['due_date'],
                'principal' => $loan->amount,
                'interest' => $metrics['interest'],
                'outstanding_at_due' => $metrics['outstanding_at_due'],
                'penalty' => $metrics['penalty_amount'],
                'days_late' => $metrics['days_late']
            ];

            // Calculate loan duration
            if ($loan->borrow_date && $metrics['due_date']) {
                $loan_durations[] = $metrics['due_date']->diffInDays(Carbon::parse($loan->borrow_date));
            }

            // Calculate repayment period duration
            if ($loan->repayments->isNotEmpty()) {
                $firstRepayment = $loan->repayments->first()->repayment_date;
                $lastRepayment = $loan->repayments->last()->repayment_date;
                $repayment_periods[] = Carbon::parse($lastRepayment)
                    ->diffInDays(Carbon::parse($firstRepayment));
            }
        }

        $average_days_late = $total_late_loans > 0 
            ? array_sum($loan_days_late) / $total_late_loans 
            : 0;

        $average_repayment_days = count($repayment_periods) > 0
            ? array_sum($repayment_periods) / count($repayment_periods)
            : 0;

        $average_loan_duration = count($loan_durations) > 0
            ? array_sum($loan_durations) / count($loan_durations)
            : 0;

        return [
            'totalLoans' => $allLoans->count(),
            'total_requested' => $allLoans->sum('amount'),
            'total_disbursed' => $allLoans->sum(function($loan) {
                return $loan->disbursements->sum('amount');
            }),
            'total_repayments' => $allLoans->sum(function($loan) {
                return $loan->repayments->sum('amount');
            }),
            'total_broker_fees' => $total_broker_fees,
            'activeLoans' => $allLoans->where('status', 'disbursed')->count(),
            'repaidLoans' => $allLoans->where('status', 'repaid')->count(),
            'net_earnings' => ($totalInterest + $total_penalties) - $total_broker_fees,
            'average_days_late' => round($average_days_late, 1),
            'average_repayment_days' => round($average_repayment_days, 1),
            'total_late_loans' => $total_late_loans,
            'total_penalties' => $total_penalties,
            'loan_details' => $loan_details,
            'average_loan_duration' => round($average_loan_duration, 1),
            'total_interest' => $totalInterest,
            'total_outstanding_at_due' => array_sum(array_column($loan_details, 'outstanding_at_due'))
        ];
    }

    /**
     * Calculate broker statistics
     */
    protected function calculateBrokerStats($brokerLoans, $broker)
    {
        $totalBrokerFees = 0;
        $clientIds = [];

        foreach ($brokerLoans as $loan) {
            if (!$loan->loanType || !$loan->borrow_date) {
                continue;
            }

            $metrics = $this->loanCalculator->calculateLoanMetrics($loan);

            $borrower = $loan->user->borrower;
            $clientType = $borrower->client_type ?? 0;
            $brokerRate = ($clientType == 0) ? $broker->interest_client : $broker->interest_broker;

            $loan->broker_interest_amount = $metrics['interest'] * ($brokerRate / 100);
            $loan->broker_penalty_amount = $metrics['penalty_amount'] * ($brokerRate / 100);

            $totalBrokerFees += $loan->broker_interest_amount + $loan->broker_penalty_amount;
            $clientIds[] = $borrower->id;
        }

        return [
            'activeLoans' => $brokerLoans->where('status', 'disbursed')->count(),
            'repaidLoans' => $brokerLoans->where('status', 'repaid')->count(),
            'pendingLoans' => $brokerLoans->where('status', 'pending')->count(),
            'brokerFees' => $totalBrokerFees,
            'total_clients' => count(array_unique($clientIds)),
            'total_outstanding' => $brokerLoans->sum(function($loan) {
                return $this->loanCalculator->calculateLoanMetrics($loan)['outstanding_balance'];
            }),
        ];
    }
}