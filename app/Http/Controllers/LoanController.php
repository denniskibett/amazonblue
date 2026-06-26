<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanType;
use App\Models\User;
use App\Models\Repayment;
use App\Models\Broker;
use App\Models\Borrower;
use App\Pdf\LoanPDF;
use App\Services\LoanCalculator;
use App\Services\SignatureService;
use App\Services\LoanAgreementService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use TCPDF;

class LoanController extends Controller
{
    protected $loanCalculator;
    protected $signatureService;
    protected $loanAgreementService;

    public function __construct(
        LoanCalculator $loanCalculator,
        SignatureService $signatureService,
        LoanAgreementService $loanAgreementService
    ) {
        $this->loanCalculator = $loanCalculator;
        $this->signatureService = $signatureService;
        $this->loanAgreementService = $loanAgreementService;
    }


    public function index()
    {
        $user = auth()->user();   
        
        // Fetch users based on role for the create modal
        $loanTypes = LoanType::all();
        
        // Default data for other roles
        $users = User::where('status', 0)->get();

        $guarantors = User::where('role', 'borrower')
            ->where('status', 0)
            ->get();

        $loanOfficers = User::whereIn('role', ['admin', 'teller'])
            ->where('status', 0)
            ->get();

        if ($user->role === 'admin') {
            // Get all loans with relationships
            $allLoans = Loan::with([
                'user.borrower',
                'loanType',
                'disbursements',
                'repayments'
            ])->orderBy('borrow_date', 'desc')->get();

            // Initialize statistics
            $totalInterest = 0;
            $total_penalties = 0;
            $total_broker_fees = 0;
            $total_late_loans = 0;
            $loan_days_late = [];
            $repayment_periods = [];
            $loan_details = [];
            $loan_durations = [];

            foreach ($allLoans as $loan) {
                // Skip loans missing critical data
                if (!$loan->loanType || !$loan->borrow_date) {
                    continue;
                }

                // Calculate base values
                $interest = ($loan->loanType->interest_rate / 100) * $loan->amount;
                $totalInterest += $interest;
                $principalPlusInterest = $loan->amount + $interest;

                // Calculate due date
                $disbursementDate = \Carbon\Carbon::parse($loan->borrow_date);
                $dueDate = $disbursementDate->copy();
                switch ($loan->loanType->unit) {
                    case 'days': $dueDate->addDays($loan->loanType->period); break;
                    case 'weeks': $dueDate->addWeeks($loan->loanType->period); break;
                    case 'months': $dueDate->addMonths($loan->loanType->period); break;
                    case 'years': $dueDate->addYears($loan->loanType->period); break;
                }

                // Calculate loan duration
                if ($loan->borrow_date && $dueDate) {
                    $loan_durations[] = $dueDate->diffInDays($disbursementDate);
                }

                // Calculate repayments before due date
                $repaymentsBeforeDue = $loan->repayments
                    ->filter(function($repayment) use ($dueDate) {
                        return \Carbon\Carbon::parse($repayment->repayment_date)->lt($dueDate);
                    })
                    ->sum('amount');

                // Calculate outstanding amount at due date
                $outstandingAtDueDate = max($principalPlusInterest - $repaymentsBeforeDue, 0);

                // Calculate penalty
                $penalty = $outstandingAtDueDate * ($loan->loanType->penalty_rate / 100);
                $total_penalties += $penalty;

                // Calculate broker fees with null checks
                $loanUser = $loan->user; // Renamed to avoid conflict
                $borrower = $loanUser ? $loanUser->borrower : null;
                $broker = $borrower ? $borrower->broker : null;
                if ($loan->broker_status == 1 && $broker) {
                    $clientType = $borrower ? ($borrower->client_type ?? 0) : 0;
                    $brokerRate = ($clientType == 0) ? $broker->interest_client : $broker->interest_broker;
                    $total_broker_fees += $outstandingAtDueDate * ($brokerRate / 100);
                }

                // Calculate days late (fixed calculation)
                $daysLate = max(0, $dueDate->diffInDays(now(), false));
                if ($daysLate > 0) {
                    $total_late_loans++;
                    $loan_days_late[] = $daysLate;
                }

                // Store loan details for stats
                $loan_details[] = [
                    'borrow_date' => $loan->borrow_date,
                    'due_date' => $dueDate,
                    'principal' => $loan->amount,
                    'interest' => $interest,
                    'outstanding_at_due' => $outstandingAtDueDate,
                    'penalty' => $penalty,
                    'days_late' => $daysLate
                ];

                // Calculate repayment period duration
                if ($loan->repayments->isNotEmpty()) {
                    $firstRepayment = $loan->repayments->first()->repayment_date;
                    $lastRepayment = $loan->repayments->last()->repayment_date;
                    $repayment_periods[] = \Carbon\Carbon::parse($lastRepayment)
                        ->diffInDays(\Carbon\Carbon::parse($firstRepayment));
                }
            }

            // Calculate averages
            $average_days_late = $total_late_loans > 0 
                ? array_sum($loan_days_late) / $total_late_loans 
                : 0;

            $average_repayment_days = count($repayment_periods) > 0
                ? array_sum($repayment_periods) / count($repayment_periods)
                : 0;

            $average_loan_duration = count($loan_durations) > 0
                ? array_sum($loan_durations) / count($loan_durations)
                : 0;

            // Prepare stats
            $stats = [
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

            // Fetch users for admin role
            $users = User::all();
            $guarantors = User::where('role', 'borrower')->get();
            $loanOfficers = User::whereIn('role', ['admin', 'teller'])->get();

            return view('loans.index', [
                'allLoans' => $allLoans,
                'stats' => $stats,
                'users' => $users, // Added
                'loanTypes' => $loanTypes, // Added
                'guarantors' => $guarantors, // Added
                'loanOfficers' => $loanOfficers // Added
            ]);
        }
        elseif ($user->role === 'broker') { 
            $broker = $user->broker;

            // Fetch loans with broker status and client relationship
            $brokerLoans = Loan::where('broker_status', 1)
                ->whereHas('user.borrower', function ($query) use ($broker) {
                    $query->where('broker_id', $broker->id);
                })
                ->with(['loanType', 'repayments', 'user.borrower'])
                ->orderBy('borrow_date', 'desc')
                ->get();

            $totalBrokerFees = 0;
            $clientIds = [];

            foreach ($brokerLoans as $loan) {
                // Skip invalid loans
                if (!$loan->loanType || !$loan->borrow_date) {
                    continue;
                }

                // ======================
                // 1. DATE CALCULATIONS
                // ======================
                $dueDate = Carbon::parse($loan->borrow_date);
                $dueDate->add($loan->loanType->period, $loan->loanType->unit);
                $loan->due_date = $dueDate; // For Blade display

                // ======================
                // 2. REPAYMENT PROCESSING
                // ======================
                $repayments = $loan->repayments
                    ->filter(fn($r) => $r->repayment_date !== null)
                    ->sortBy('repayment_date');

                $totalRepayments = $repayments->sum('amount');
                $loan->total_repayments = $totalRepayments; // For Blade

                // ======================
                // 3. INTEREST CALCULATION
                // ======================
                $interest = ($loan->loanType->interest_rate / 100) * $loan->amount;
                $loan->total_interest = $interest; // For Blade

                // ======================
                // 4. PENALTY CALCULATION
                // ======================
                $totalPenalty = 0;
                $outstandingAtDueDate = max(($loan->amount + $interest) - 
                    $repayments->filter(fn($r) => Carbon::parse($r->repayment_date)->lt($dueDate))
                    ->sum('amount'), 0);

                if ($outstandingAtDueDate > 0) {
                    $currentBalance = $outstandingAtDueDate;
                    $currentDate = $dueDate->copy();
                    $endDate = ($loan->status === 'repaid' && $repayments->isNotEmpty()) 
                        ? Carbon::parse($repayments->last()->repayment_date) 
                        : now();

                    while ($currentDate->lte($endDate)) {
                        if ($currentBalance <= 0) break;
                        
                        $dailyPayment = $repayments
                            ->filter(fn($r) => Carbon::parse($r->repayment_date)->isSameDay($currentDate))
                            ->sum('amount');
                        
                        $currentBalance = max($currentBalance - $dailyPayment, 0);
                        
                        if ($currentBalance > 0) {
                            $totalPenalty += ($loan->loanType->penalty_rate / 100) * $currentBalance;
                        }
                        
                        $currentDate->addDay();
                    }
                }

                // ======================
                // 5. BROKER COMMISSIONS
                // ======================
                $borrower = $loan->user->borrower;
                $clientType = $borrower->client_type ?? 0;
                $brokerRate = ($clientType == 0) ? $broker->interest_client : $broker->interest_broker;

                $loan->broker_interest_amount = $interest * ($brokerRate / 100);
                $loan->broker_penalty_amount = $totalPenalty * ($brokerRate / 100);

                // ======================
                // 6. TOTAL AMOUNT DUE
                // ======================
                $loan->total_due = max(
                    ($loan->amount + $interest + $totalPenalty) - $totalRepayments,
                    0
                );

                // ======================
                // 7. TRACK TOTALS
                // ======================
                $totalBrokerFees += $loan->broker_interest_amount + $loan->broker_penalty_amount;
                $clientIds[] = $borrower->id;
            }

            // Prepare statistics
            $stats = [
                'activeLoans' => $brokerLoans->where('status', 'disbursed')->count(),
                'repaidLoans' => $brokerLoans->where('status', 'repaid')->count(),
                'pendingLoans' => $brokerLoans->where('status', 'pending')->count(),
                'brokerFees' => $totalBrokerFees,
                'total_clients' => count(array_unique($clientIds)),
                'total_outstanding' => $brokerLoans->sum('total_due'),
            ];

            // Fetch users for broker role
            $users = User::whereHas('borrower', function ($query) use ($user) {
            $query->where('broker_id', $user->broker->id);
                })
                ->where('role', 'borrower')
                ->where('status', 0)
                ->get();
            

            return view('loans.index', [
                'brokerLoans' => $brokerLoans,
                'stats' => $stats,
                'broker' => $broker,
                'users' => $users, // Added
                'loanTypes' => $loanTypes, // Added
                'guarantors' => $guarantors, // Added
                'loanOfficers' => $loanOfficers // Added
            ]);
        }
        elseif ($user->role === 'borrower') {
            $userLoans = Loan::where('user_id', $user->id)
                ->with(['loanType', 'disbursements', 'repayments'])
                ->orderBy('borrow_date', 'desc')
                ->get();

            // Initialize empty stats array for borrower view compatibility
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

            // Fetch data for borrower
            $users = collect([$user]);
            $guarantors = User::where('role', 'borrower')->where('id', '!=', $user->id)->get();
            $loanOfficers = User::whereIn('role', ['admin', 'teller'])->get();

            return view('loans.index', [
                'userLoans' => $userLoans,
                'stats' => $stats,
                'users' => $users, // Added
                'loanTypes' => $loanTypes, // Added
                'guarantors' => $guarantors, // Added
                'loanOfficers' => $loanOfficers // Added
            ]);
        }
        elseif ($user->role === 'teller') {
            $activeLoans = Loan::with(['user', 'loanType'])
                ->where('status', 'active')
                ->orderBy('borrow_date', 'desc')
                ->get();

            // Teller stats (similar to broker view)
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

            // Fetch data for teller
            $users = User::all();
            $guarantors = User::where('role', 'borrower')->get();
            $loanOfficers = User::whereIn('role', ['admin', 'teller'])->get();

            return view('loans.index', [
                'activeLoans' => $activeLoans,
                'stats' => $stats,
                'users' => $users, // Added
                'loanTypes' => $loanTypes, // Added
                'guarantors' => $guarantors, // Added
                'loanOfficers' => $loanOfficers // Added
            ]);
        }
        else {
            $userLoans = $user->loans()
                ->with('loanType')
                ->orderBy('borrow_date', 'desc')
                ->get();

            // Calculate borrower statistics
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
                'stats' => $stats,
                'users' => $users, // Added
                'loanTypes' => $loanTypes, // Added
                'guarantors' => $guarantors, // Added
                'loanOfficers' => $loanOfficers // Added
            ]);
        }
    }

    public function getUserLoans(Loan $loan)
    {
        return $loan->user->loans()
            ->where('id', '!=', $loan->id)
            ->whereIn('status', ['approved', 'disbursed'])
            ->get();
    }

    public function show($id, $loanId = null)
    {
        $user = auth()->user();
        $brokers = Broker::with('user')->get();
        $borrowers = Borrower::all();
        
        if (!$user) {
            abort(403, 'Unauthorized');
        }
        
        $loanQuery = Loan::with([
            'disbursements', 
            'repayments', 
            'loanType', 
            'user.borrower',
            'guarantor',
            'loanOfficer'
        ]);
        
        try {
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
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Loan not found'], 404);
        }

        if (!$loan->loanType) {
            abort(422, 'Loan type is not defined for this loan');
        }

        // Use LoanCalculator service for all metrics
        $metrics = $this->loanCalculator->calculateLoanMetrics($loan);
        
        $borrower = $loan->user->borrower;
        $broker = $borrower->broker ?? null;

        // Get signature status
        $signatureStatus = $this->signatureService->checkSignature($loan->user);

        return view('loans.show', array_merge([
            'loan' => $loan,
            'loanType' => $loan->loanType,
            'brokers' => $brokers,
            'borrowers' => $borrowers,
            'broker' => $broker,
            'today' => now(),
            'diff' => now()->diff($metrics['due_date']),
            'disbursementDate' => $metrics['borrow_date'],
            'signatureStatus' => $signatureStatus,
            'hasSignature' => $signatureStatus['hasSignature'],
            'signatureUrl' => $signatureStatus['signatureUrl'],
        ], $metrics));
    }

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
            // BORROWER - FIXED: Use authenticated user
            $loans = $user->loans()->where('status', '!=', 'repaid')->get();
            $users = collect([$user]);
            $guarantors = User::where('role', 'borrower')->where('id', '!=', $user->id)->get();
            $loanOfficers = User::whereIn('role', ['admin', 'teller'])->get();
        }

        // FIXED: Simple signature check logic
        $signatureUser = null;
        $hasExistingSignature = false;
        $existingSignatureUrl = null;

        if ($user->role === 'borrower') {
            // For borrower, always check their own signature
            $signatureUser = $user;
        } elseif (($user->role === 'admin' || $user->role === 'teller' || $user->role === 'broker') && $request->has('user_id')) {
            // For admin/teller/broker, check selected user's signature
            $signatureUser = User::find($request->user_id);
        }

        // Check signature if we have a user
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

public function store(Request $request)
{
    $user = auth()->user();
    
    \Log::debug('Loan Store Request Data:', $request->all());
    
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
        'due_date' => 'required|date|after:borrow_date',
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
                $now = \Carbon\Carbon::now();
                
                foreach ($activeLoans as $existingLoan) {
                    // Calculate due date
                    $dueDate = \Carbon\Carbon::parse($existingLoan->borrow_date);
                    if ($existingLoan->loanType) {
                        $dueDate->add($existingLoan->loanType->period, $existingLoan->loanType->unit);
                    }
                    
                    // Calculate days until due
                    $daysUntilDue = $now->diffInDays($dueDate, false);
                    
                    // Calculate total repayments
                    $totalRepayments = $existingLoan->repayments ? $existingLoan->repayments->sum('amount') : 0;
                    $interest = ($existingLoan->loanType->interest_rate / 100) * $existingLoan->amount;
                    $principalPlusInterest = $existingLoan->amount + $interest;
                    $outstandingBalance = max($principalPlusInterest - $totalRepayments, 0);
                    
                    $activeLoansData[] = [
                        'id' => $existingLoan->id,
                        'amount' => $existingLoan->amount,
                        'borrow_date' => $existingLoan->borrow_date,
                        'borrow_date_formatted' => \Carbon\Carbon::parse($existingLoan->borrow_date)->format('M d, Y'),
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
        ]);
        
        $loan = Loan::create($loanData);
        
        // Handle signature if provided
        if ($request->has('signature_data') && !empty($request->signature_data)) {
            $signatureResult = $this->signatureService->saveSignature($request->signature_data, $signatureUser);
            if (!$signatureResult['success']) {
                \Log::error('Signature save failed:', $signatureResult);
            }
        }
        
        // Generate agreement if consent was given
        if ($loan->consent) {
            try {
                $this->loanAgreementService->generateLoanAgreement($loan);
            } catch (\Exception $e) {
                \Log::error('Failed to generate loan agreement:', ['error' => $e->getMessage()]);
            }
        }
        
        // Return JSON response for AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Loan created successfully.',
                'loan' => $loan->load(['user', 'loanType']),
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
        \Log::error('Loan creation failed:', [
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

    public function edit(Loan $loan)
    {
        $loanTypes = LoanType::all();
        $guarantors = User::where('role', 'borrower')
            ->where('id', '!=', $loan->user_id)
            ->get();
        $loanOfficers = User::whereIn('role', ['admin', 'teller'])->get();
        
        return view('loans.edit', [
            'loan' => $loan,
            'loanTypes' => $loanTypes,
            'guarantors' => $guarantors,
            'loanOfficers' => $loanOfficers
        ]);
    }
    
    public function update(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'loan_type_id'   => 'required|exists:loan_types,id',
            'amount'         => 'required|numeric|min:1',
            'borrow_date'    => 'required|date',
            'status'         => 'required|in:pending,approved,disbursed,repaid',
            'broker_status'  => 'required|in:0,1',
            'admin_notes'    => 'nullable|string',
            'guarantor_id' => 'nullable|exists:users,id',
            'guarantor_relationship' => 'nullable|string|max:100',
            'loan_officer_id' => 'nullable|exists:users,id',
            'consent' => 'sometimes|boolean',
        ]);

        // Handle consent update
        if ($request->has('consent') && $request->consent === '1' && !$loan->consent) {
            $validated['consent'] = true;
            $validated['consent_date'] = now();
        } elseif (!$request->has('consent') || $request->consent !== '1') {
            $validated['consent'] = false;
            $validated['consent_date'] = null;
        }
    
        $loan->update($validated);
        return redirect()->route('loans.edit', $loan->id)
                         ->with('success', 'Loan updated successfully.');
    }
    
    public function saveSignature(Request $request, Loan $loan)
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        $result = $this->signatureService->saveSignature($request->signature, $loan->user);

        if ($result['success']) {
            // If consent was already given, generate agreement
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

    public function giveConsent(Loan $loan)
    {
        $loan->giveConsent();
        
        // Generate agreement if signature exists
        $signatureStatus = $this->signatureService->checkSignature($loan->user);
        if ($signatureStatus['hasSignature']) {
            $this->loanAgreementService->generateLoanAgreement($loan);
        }
        
        return back()->with('success', 'Consent given successfully.');
    }
    
    public function destroy($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->delete();
        return redirect()->route('loans.index');
    }
    
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
    
        // Use LoanCalculator service for metrics
        $metrics = $this->loanCalculator->calculateLoanMetrics($loan);
        
        // Generate PDF
        $pdf = new LoanPDF($loan);
        $pdf->AddPage();
        $pdf->loanDetails(
            $metrics['borrow_date'],
            $metrics['due_date'],
            $metrics['days_late'],
            $metrics['last_repayment_date'] ?? null
        );
        
        // Generate payment schedule
        $paymentSchedule = $this->generatePaymentSchedule(
            $loan,
            Carbon::parse($metrics['borrow_date']),
            $metrics['due_date'],
            $metrics['principal'],
            $loan->loanType->interest_rate,
            $metrics['last_repayment_date'] ?? null
        );
        $pdf->paymentSchedule($paymentSchedule);
        
        // Prepare account summary
        $pdf->accountSummary([
            ['label' => 'Principal Amount', 'value' => 'KES ' . number_format($metrics['principal'], 2)],
            ['label' => 'Interest Rate', 'value' => $loan->loanType->interest_rate . '%'],
            ['label' => 'Total Interest', 'value' => 'KES ' . number_format($metrics['interest'], 2)],
            ['label' => 'Total Amount Due', 'value' => 'KES ' . number_format($metrics['principal_plus_interest'], 2)],
            ['label' => 'Total Paid', 'value' => 'KES ' . number_format($metrics['total_repayments'], 2)],
            ['label' => 'Current Balance', 'value' => 'KES ' . number_format($metrics['outstanding_balance'], 2)],
            ['label' => 'Days Late', 'value' => $metrics['days_late'] > 0 ? $metrics['days_late'] . ' days' : 'On time']
        ]);
        
        // Prepare transactions
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
        
        // Calculate running balances
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

    public function downloadAgreement($loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $user = auth()->user();
        
        // Authorization check
        if (!in_array($user->role, ['admin', 'teller']) && 
            $loan->user_id !== $user->id && 
            !($user->role === 'broker' && $loan->broker_status == 1)) {
            abort(403, 'Unauthorized');
        }

        $filePath = $this->loanAgreementService->generateLoanAgreement($loan);
        
        return response()->download($filePath, "loan_agreement_{$loan->id}.pdf");
    }

    public function showAgreement($loanId)
    {
        $loan = Loan::findOrFail($loanId);
        $user = auth()->user();
        
        // Authorization check
        if (!in_array($user->role, ['admin', 'teller']) && 
            $loan->user_id !== $user->id && 
            !($user->role === 'broker' && $loan->broker_status == 1)) {
            abort(403, 'Unauthorized');
        }

        $filePath = $this->loanAgreementService->generateLoanAgreement($loan);
        
        return response()->file($filePath);
    }

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
        
        // Mark paid installments
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

  
}