<?php

namespace App\Http\Controllers;

use App\Models\Borrower;
use App\Models\Broker;
use App\Models\Loan;
use App\Models\User;
use App\Models\Repayment;
use App\Models\Disbursement;
use App\Models\DebtRecoveryCase;
use App\Models\RecoveryAction;
use App\Models\LoanType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $chartData = $this->getChartData();
        $data = [];
        $currentMonthStart = Carbon::now()->startOfMonth();
        $dueLoans = $this->getDueLoans($user);

        // Prepare monthly data for chart
        $monthlyData = $this->prepareMonthlyData($chartData);

        // ============ RECOVERY DATA (All Roles) ============
        $data['activeRecoveryCases'] = DebtRecoveryCase::open()->count();
        $data['totalRecoveryDebt'] = DebtRecoveryCase::open()->sum('total_debt_amount');
        $data['urgentRecoveryCases'] = DebtRecoveryCase::urgent()->open()->count();
        
        $totalDebt = DebtRecoveryCase::sum('total_debt_amount');
        $totalRecovered = RecoveryAction::successful()->sum('amount_collected');
        $data['recoveryRate'] = $totalDebt > 0 ? round(($totalRecovered / $totalDebt) * 100, 2) : 0;
        
        $data['recoveryCases'] = DebtRecoveryCase::with(['user', 'status', 'priority'])
            ->orderBy('priority_id', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // ============ NPL & OVERDUE DATA ============
        $data['nplCount'] = Loan::where('is_non_performing', true)->count();
        $data['nplTotalDebt'] = Loan::where('is_non_performing', true)->sum('amount');
        $data['overdueCount'] = Loan::where('status', Loan::STATUS_OVERDUE)->count();
        
        // Calculate NPL recovery rate
        $nplLoans = Loan::where('is_non_performing', true)->get();
        $nplTotal = $nplLoans->sum('amount');
        $nplRecovered = 0;
        foreach ($nplLoans as $nplLoan) {
            $nplRecovered += $nplLoan->repayments->sum('amount');
        }
        $data['nplRecoveryRate'] = $nplTotal > 0 ? round(($nplRecovered / $nplTotal) * 100, 2) : 0;

        // ============================================================
        // ROLE-BASED DATA
        // ============================================================
        if ($user->hasRole('admin')) {
            $data = array_merge($data, $this->getAdminData($user, $currentMonthStart));
        } elseif ($user->hasRole('borrower')) {
            $data = array_merge($data, $this->getBorrowerData($user, $currentMonthStart));
        } elseif ($user->hasRole('broker')) {
            $data = array_merge($data, $this->getBrokerData($user, $currentMonthStart));
        } elseif ($user->hasRole('teller')) {
            $data = array_merge($data, $this->getTellerData($user, $currentMonthStart));
        }

        // Merge common data
        $data['dueLoans'] = $dueLoans;
        $data['chartData'] = $chartData;
        $data['monthlyData'] = $monthlyData;
        $data['defaultLoanType'] = $this->getDefaultLoanType();

        return view('dashboard', $data);
    }

    // ================================================================
    // ADMIN DASHBOARD DATA
    // ================================================================
    private function getAdminData($user, $currentMonthStart)
    {
        return [
            // Loan Metrics
            'totalLoans' => Loan::count(),
            'loansThisMonth' => Loan::where('created_at', '>=', $currentMonthStart)->count(),
            'completedLoans' => Loan::where('status', Loan::STATUS_REPAID)->count(),
            'completedThisMonth' => Loan::where('status', Loan::STATUS_REPAID)
                                        ->where('updated_at', '>=', $currentMonthStart)
                                        ->count(),

            // Financial Metrics
            'totalDisbursements' => Disbursement::sum('amount') ?? 0,
            'disbursementsThisMonth' => Disbursement::where('disburse_date', '>=', $currentMonthStart)
                                                ->sum('amount') ?? 0,
            'totalRepayments' => Repayment::sum('amount') ?? 0,
            'repaymentsThisMonth' => Repayment::where('created_at', '>=', $currentMonthStart)
                                            ->sum('amount') ?? 0,

            // User Metrics
            'borrowerCount' => User::role('borrower')->count(),
            'newBorrowersThisMonth' => User::role('borrower')
                                        ->where('created_at', '>=', $currentMonthStart)
                                        ->count(),
            'brokerCount' => User::role('broker')->count(),
            'tellerCount' => User::role('teller')->count(),

            // Additional Data
            'recentLoans' => Loan::with(['user', 'loanType'])->latest()->take(5)->get(),
            'todayTransactions' => Repayment::whereDate('created_at', today())->count(),

            'loanStatusData' => [
                'pending' => Loan::where('status', Loan::STATUS_PENDING)->count(),
                'approved' => Loan::where('status', Loan::STATUS_APPROVED)->count(),
                'disbursed' => Loan::where('status', Loan::STATUS_DISBURSED)->count(),
                'active' => Loan::where('status', Loan::STATUS_ACTIVE)->count(),
                'overdue' => Loan::where('status', Loan::STATUS_OVERDUE)->count(),
                'defaulted' => Loan::where('status', Loan::STATUS_DEFAULTED)->count(),
                'repaid' => Loan::where('status', Loan::STATUS_REPAID)->count(),
                'recovery' => Loan::where('status', Loan::STATUS_RECOVERY)->count(),
                'forbearance' => Loan::where('status', Loan::STATUS_FORBEARANCE)->count(),
                'rejected' => Loan::where('status', Loan::STATUS_REJECTED)->count(),
            ],
            'disbursementTrends' => $this->getDisbursementTrends(),
        ];
    }

    // ================================================================
    // BORROWER DASHBOARD DATA - Clean and Simple
    // ================================================================
    private function getBorrowerData($user, $currentMonthStart)
    {
        // Get all borrower loans with relationships
        $loans = Loan::with(['loanType', 'repayments', 'cycles'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get active loan (if any)
        $activeLoan = $loans->whereIn('status', [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])->first();

        // Calculate loan stats
        $totalBorrowed = $loans->sum('amount');
        $completedLoans = $loans->where('status', Loan::STATUS_REPAID)->count();
        $activeLoans = $loans->whereIn('status', [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE])->count();

        // Get recent transactions
        $recentTransactions = $this->getBorrowerRecentTransactions($loans);

        // Get biodata completion data
        $biodataComplete = $this->checkBiodataComplete($user);
        $missingFields = $this->getMissingBiodataFields($user);
        $completionPercentage = $this->getBiodataCompletionPercentage($user);

        // Get borrower recovery data
        $myRecoveryCases = $user->debtRecoveryCases()->with(['status', 'priority'])->get();
        $activeRecoveryCount = $user->debtRecoveryCases()->open()->count();
        $hasActiveRecovery = $activeRecoveryCount > 0;

        return [
            // Borrower specific
            'activeLoan' => $activeLoan,
            'totalBorrowed' => $totalBorrowed,
            'completedLoans' => $completedLoans,
            'activeLoans' => $activeLoans,
            'recentTransactions' => $recentTransactions,
            'biodataComplete' => $biodataComplete,
            'missingBiodataFields' => $missingFields,
            'biodataCompletionPercentage' => $completionPercentage,
            'myRecoveryCases' => $myRecoveryCases,
            'hasActiveRecovery' => $hasActiveRecovery,
            'activeRecoveryCount' => $activeRecoveryCount,

            // Defaults for other sections (not shown to borrowers)
            'totalLoans' => $loans->count(),
            'loansThisMonth' => $loans->where('created_at', '>=', $currentMonthStart)->count(),
            'totalRepayments' => $user->repayments()->sum('repayments.amount') ?? 0,
            'repaymentsThisMonth' => $user->repayments()
                                        ->where('repayments.created_at', '>=', $currentMonthStart)
                                        ->sum('repayments.amount') ?? 0,
            'totalDisbursements' => $user->disbursements()->sum('disbursements.amount') ?? 0,
            'disbursementsThisMonth' => $user->disbursements()
                                            ->where('disbursements.created_at', '>=', $currentMonthStart)
                                            ->sum('disbursements.amount') ?? 0,
            'borrowedThisMonth' => $loans->where('borrow_date', '>=', $currentMonthStart)->sum('amount') ?? 0,
            'totalInterest' => 0,
            'totalPenalty' => 0,
        ];
    }

    // ================================================================
    // BROKER DASHBOARD DATA
    // ================================================================
    private function getBrokerData($user, $currentMonthStart)
    {
        $broker = $user->broker()->first();

        if (!$broker) {
            abort(403, 'Broker profile not found');
        }

        $borrowerIds = $broker->borrowers()->pluck('user_id');

        return [
            'broker' => $broker,
            'clients' => $broker->borrowers()->count(),
            'newClientsThisMonth' => $broker->borrowers()
                                        ->where('created_at', '>=', $currentMonthStart)
                                        ->count(),
            'activeLoans' => Loan::whereIn('user_id', $borrowerIds)
                            ->where('broker_status', 1)
                            ->whereIn('status', [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE])
                            ->count(),
            'totalInterest' => $this->calculateBrokerEarnings($broker, 'interest') ?? 0,
            'totalPenalty' => $this->calculateBrokerEarnings($broker, 'penalty') ?? 0,
            'overdueLoans' => Loan::whereIn('user_id', $borrowerIds)
                            ->where('status', Loan::STATUS_OVERDUE)
                            ->get(),
        ];
    }

    // ================================================================
    // TELLER DASHBOARD DATA
    // ================================================================
    private function getTellerData($user, $currentMonthStart)
    {
        return [
            'todaysDisbursements' => Disbursement::whereDate('disburse_date', today())
                                            ->sum('amount') ?? 0,
            'monthDisbursements' => Disbursement::where('disburse_date', '>=', $currentMonthStart)
                                            ->sum('amount') ?? 0,
            'collectedRepayments' => Repayment::sum('amount') ?? 0,
            'monthRepayments' => Repayment::where('created_at', '>=', $currentMonthStart)
                                    ->sum('amount') ?? 0,
        ];
    }

    // ================================================================
    // HELPER METHODS
    // ================================================================

    private function getDefaultLoanType()
    {
        $loanType = LoanType::where('period', 20)
            ->orWhere('name', 'like', '%emergency%')
            ->orWhere('name', 'like', '%quick%')
            ->first();

        if (!$loanType) {
            $loanType = LoanType::first();
        }

        return $loanType;
    }

    private function getBorrowerRecentTransactions($loans)
    {
        $transactions = collect();

        // Get disbursements
        $disbursements = Disbursement::whereIn('loan_id', $loans->pluck('id'))
            ->select('disburse_date as date', DB::raw("'Disbursement' as type"), 'amount', DB::raw("'completed' as status"))
            ->get();

        // Get repayments
        $repayments = Repayment::whereIn('loan_id', $loans->pluck('id'))
            ->select('repayment_date as date', DB::raw("'Repayment' as type"), 'amount', DB::raw("'completed' as status"))
            ->get();

        return $disbursements->merge($repayments)
            ->sortByDesc('date')
            ->take(5);
    }

    private function checkBiodataComplete($user)
    {
        $borrower = $user->borrower;
        if (!$borrower) {
            return false;
        }

        $requiredFields = ['phone', 'email', 'id_number', 'date_of_birth', 'gender', 'nationality'];
        foreach ($requiredFields as $field) {
            if (empty($borrower->$field) && empty($user->$field)) {
                return false;
            }
        }
        return true;
    }

    private function getBiodataCompletionPercentage($user)
    {
        $borrower = $user->borrower;
        if (!$borrower) {
            return 0;
        }

        $fields = ['phone', 'email', 'id_number', 'date_of_birth', 'gender', 'nationality'];
        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($borrower->$field) || !empty($user->$field)) {
                $filled++;
            }
        }
        return round(($filled / count($fields)) * 100);
    }

    private function getMissingBiodataFields($user)
    {
        $borrower = $user->borrower;
        if (!$borrower) {
            return ['phone', 'email', 'id_number', 'date_of_birth', 'gender', 'nationality'];
        }

        $fields = ['phone', 'email', 'id_number', 'date_of_birth', 'gender', 'nationality'];
        $missing = [];
        foreach ($fields as $field) {
            if (empty($borrower->$field) && empty($user->$field)) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    private function prepareMonthlyData($chartData)
    {
        if (empty($chartData['months'])) {
            return [
                'labels' => [],
                'loanData' => [],
                'disbursementData' => [],
                'repaymentData' => []
            ];
        }

        return [
            'labels' => $chartData['months'],
            'loanData' => $chartData['loans'],
            'disbursementData' => $chartData['disbursements'],
            'repaymentData' => $chartData['repayments']
        ];
    }

    private function getChartData()
    {
        $months = [];
        $loans = [];
        $disbursements = [];
        $repayments = [];
        
        $earliestLoanDate = Loan::min('borrow_date');
        $earliestDisbursementDate = Disbursement::min('disburse_date');
        $earliestRepaymentDate = Repayment::min('repayment_date');
        
        $earliestDate = collect([
            $earliestLoanDate,
            $earliestDisbursementDate,
            $earliestRepaymentDate
        ])->filter()->min();
        
        if (!$earliestDate) {
            return [
                'months' => [],
                'loans' => [],
                'disbursements' => [],
                'repayments' => []
            ];
        }
        
        $startDate = Carbon::parse($earliestDate)->startOfMonth();
        $endDate = now()->endOfMonth();
        
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $monthName = $currentDate->format('M Y');
            $months[] = $monthName;
            $loans[] = 0;
            $disbursements[] = 0;
            $repayments[] = 0;
            $currentDate->addMonth();
        }
        
        $loanData = Loan::whereBetween('borrow_date', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(borrow_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        $disbursementData = Disbursement::whereBetween('disburse_date', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(disburse_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        $repaymentData = Repayment::whereBetween('repayment_date', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(repayment_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        $currentDate = $startDate->copy();
        foreach ($months as $index => $monthName) {
            $monthKey = $currentDate->format('Y-m');
            
            if (isset($loanData[$monthKey])) {
                $loans[$index] = (float) $loanData[$monthKey];
            }
            
            if (isset($disbursementData[$monthKey])) {
                $disbursements[$index] = (float) $disbursementData[$monthKey];
            }
            
            if (isset($repaymentData[$monthKey])) {
                $repayments[$index] = (float) $repaymentData[$monthKey];
            }
            
            $currentDate->addMonth();
        }
        
        return [
            'months' => $months,
            'loans' => $loans,
            'disbursements' => $disbursements,
            'repayments' => $repayments
        ];
    }

    private function getDisbursementTrends()
    {
        $data = [];
        $now = now();
        
        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $monthName = $date->format('M Y');
            
            $amount = Disbursement::whereYear('disburse_date', $date->year)
                ->whereMonth('disburse_date', $date->month)
                ->sum('amount');
            
            $data[] = [
                'month' => $monthName,
                'amount' => $amount ?: 0
            ];
        }
        
        return $data;
    }

    protected function calculateBrokerEarnings($broker, $type)
    {
        $borrowerIds = $broker->borrowers()->pluck('user_id');
        $loans = Loan::with(['user.broker', 'loanType', 'repayments'])
                    ->whereIn('user_id', $borrowerIds)
                    ->get();

        return $loans->sum(function ($loan) use ($broker, $type) {
            $user = $loan->user;
            $loanType = $loan->loanType;

            if ($type === 'interest') {
                if ($user->broker) {
                    return $broker->interest_client;
                }
                $loanInterest = ($loanType->interest_rate / 100) * $loan->amount;
                return ($broker->interest_broker / 100) * $loanInterest;
            }

            if ($type === 'penalty') {
                $borrowDate = Carbon::parse($loan->borrow_date);
                $dueDate = $borrowDate->copy()->add(
                    $loanType->period, 
                    $loanType->unit
                );
                $today = Carbon::today();
                $overdueDays = max(0, $today->diffInDays($dueDate, false) * -1);

                if ($overdueDays <= 0) {
                    return 0;
                }

                if ($user->broker) {
                    return $broker->penalty_client;
                }

                $totalRepayments = $loan->repayments->sum('amount');
                $penaltyAmount = ($broker->penalty_broker / 100) 
                            * ($loan->penalty_rate / 100) 
                            * $loan->amount;

                return max(0, $penaltyAmount - ($totalRepayments * $overdueDays));
            }

            return 0;
        });
    }

    private function getDueLoans($user)
    {
        $baseQuery = Loan::with(['user', 'loanType', 'cycles'])
            ->whereIn('status', ['disbursed', 'approved', 'active'])
            ->join('loan_types', 'loans.loan_type_id', '=', 'loan_types.id')
            ->select('loans.*');

        // Role-based filtering
        if ($user->hasRole('admin') || $user->hasRole('teller')) {
            // Show all loans
        } elseif ($user->hasRole('borrower')) {
            $baseQuery->where('loans.user_id', $user->id);
        } elseif ($user->hasRole('broker')) {
            $currentBrokerId = $user->broker->id ?? null;
            if ($currentBrokerId) {
                $borrowerIds = Borrower::where('broker_id', $currentBrokerId)->pluck('user_id');
                $baseQuery->whereIn('loans.user_id', $borrowerIds);
            }
        } else {
            $baseQuery->where('loans.user_id', $user->id);
        }

        $loans = $baseQuery->get();

        return $loans->map(function ($loan) {
            // Get ACTIVE cycle
            $activeCycle = $loan->cycles()
                ->where('status', 'active')
                ->first();
            
            if (!$activeCycle) {
                $activeCycle = $loan->cycles()
                    ->orderBy('cycle_number', 'desc')
                    ->first();
            }

            $today = Carbon::now()->startOfDay();

            if ($activeCycle && $activeCycle->due_date) {
                // Use active cycle data
                $dueDate = Carbon::parse($activeCycle->due_date)->startOfDay();
                $startDate = Carbon::parse($activeCycle->start_date)->startOfDay();
                
                $remainingDays = $today->diffInDays($dueDate, false);
                $cycleRepayments = $loan->repayments()
                    ->where('loan_cycle_id', $activeCycle->id)
                    ->sum('amount');
                
                $loan->total_repayments = $cycleRepayments;
                $loan->due_date = $dueDate;
                $loan->cycle_start_date = $startDate;
                $loan->remaining_days = $remainingDays;
                $loan->cycle_number = $activeCycle->cycle_number;
                $loan->new_balance = $activeCycle->new_balance;
                
                if ($remainingDays < 0) {
                    $loan->status = 'overdue';
                    $loan->overdue_days = abs($remainingDays);
                    $interval = $today->diff($dueDate);
                    $loan->overdue_period = [
                        'months' => $interval->m,
                        'days' => $interval->d
                    ];
                } else {
                    $loan->overdue_days = 0;
                    $loan->overdue_period = ['months' => 0, 'days' => 0];
                }
            } else {
                // Fallback: calculate from borrow_date
                $borrowDate = Carbon::parse($loan->borrow_date)->startOfDay();
                $dueDate = $borrowDate->copy();
                
                switch ($loan->loanType->unit ?? 'days') {
                    case 'days':
                        $dueDate->addDays($loan->loanType->period ?? 30);
                        break;
                    case 'weeks':
                        $dueDate->addWeeks($loan->loanType->period ?? 4);
                        break;
                    case 'months':
                        $dueDate->addMonths($loan->loanType->period ?? 1);
                        break;
                    default:
                        $dueDate->addDays(30);
                        break;
                }
                
                $remainingDays = $today->diffInDays($dueDate, false);
                $loan->due_date = $dueDate;
                $loan->remaining_days = $remainingDays;
                $loan->cycle_number = 1;
                
                if ($remainingDays < 0) {
                    $loan->status = 'overdue';
                    $loan->overdue_days = abs($remainingDays);
                    $interval = $today->diff($dueDate);
                    $loan->overdue_period = [
                        'months' => $interval->m,
                        'days' => $interval->d
                    ];
                }
            }

            return $loan;
        })->sortBy('remaining_days')->values();
    }
}