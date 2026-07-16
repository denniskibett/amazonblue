<?php

namespace App\Http\Controllers;

use App\Models\Borrower;
use App\Models\Broker;
use App\Models\Loan;
use App\Models\User;
use App\Models\Repayment;
use App\Models\Disbursement;
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

        switch ($user->role) {
            case 'admin':
                $data = [
                    // Loan Metrics
                    'totalLoans' => Loan::count(),
                    'loansThisMonth' => Loan::where('created_at', '>=', $currentMonthStart)->count(),
                    'completedLoans' => Loan::completed()->count(),
                    'completedThisMonth' => Loan::completed()
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
                    'borrowerCount' => User::borrowers()->count(),
                    'newBorrowersThisMonth' => User::borrowers()
                                                ->where('created_at', '>=', $currentMonthStart)
                                                ->count(),
                    'brokerCount' => User::brokers()->count(),
                    'tellerCount' => User::tellers()->count(),

                    // Additional Data
                    'chartData' => $chartData,
                    'monthlyData' => $monthlyData,
                    'recentLoans' => Loan::with(['user', 'loanType'])->latest()->take(5)->get(),
                    'dueLoans' => $dueLoans,
                    'todayTransactions' => Repayment::whereDate('created_at', today())->count(),

                    'loanStatusData' => [
                        'pending' => Loan::where('status', 'pending')->count(),
                        'disbursed' => Loan::where('status', 'disbursed')->count(),
                        'approved' => Loan::where('status', 'approved')->count(),
                        'rejected' => Loan::where('status', 'rejected')->count(),
                        'repaid' => Loan::where('status', 'repaid')->count(),
                    ],
                    'disbursementTrends' => $this->getDisbursementTrends(),
                ];
                break;

            case 'borrower':
                // Get biodata completion data
                $biodataComplete = $user->hasCompleteBiodata();
                $missingFields = $user->getMissingBiodataFields();
                $completionPercentage = $user->getBiodataCompletionPercentage();
                
                $data = [
                    'totalLoans' => $user->loans()->count(),
                    'loansThisMonth' => $user->loans()
                                            ->where('created_at', '>=', $currentMonthStart)
                                            ->count(),
                    'totalRepayments' => $user->repayments()->sum('repayments.amount') ?? 0,
                    'repaymentsThisMonth' => $user->repayments()
                                                ->where('repayments.created_at', '>=', $currentMonthStart)
                                                ->sum('repayments.amount') ?? 0,
                    'totalDisbursements' => $user->disbursements()->sum('disbursements.amount') ?? 0,
                    'disbursementsThisMonth' => $user->disbursements()
                                                    ->where('disbursements.created_at', '>=', $currentMonthStart)
                                                    ->sum('disbursements.amount') ?? 0,
                    'totalBorrowed' => $user->loans()->sum('amount') ?? 0,
                    'borrowedThisMonth' => $user->loans()
                                            ->where('borrow_date', '>=', $currentMonthStart)
                                            ->sum('amount') ?? 0,
                    'dueLoans' => $dueLoans,
                    'chartData' => $chartData,
                    'monthlyData' => $monthlyData,
                    'biodataComplete' => $biodataComplete,
                    'missingBiodataFields' => $missingFields,
                    'biodataCompletionPercentage' => $completionPercentage,
                ];
                break;

            case 'broker':
                $broker = $user->broker()->first();

                if (!$broker) {
                    abort(403, 'Broker profile not found');
                }

                $borrowerIds = $broker->borrowers()->pluck('user_id');

                $data = [
                    'broker' => $broker,
                    'clients' => $broker->borrowers()->count(),
                    'newClientsThisMonth' => $broker->borrowers()
                                                ->where('created_at', '>=', $currentMonthStart)
                                                ->count(),
                    'activeLoans' => Loan::whereIn('user_id', $borrowerIds)
                                    ->where('broker_status', 1)
                                    ->active()
                                    ->count(),
                    'totalInterest' => $this->calculateBrokerEarnings($broker, 'interest') ?? 0,
                    'totalPenalty' => $this->calculateBrokerEarnings($broker, 'penalty') ?? 0,
                    'dueLoans' => $dueLoans,
                    'overdueLoans' => $dueLoans->filter(function($loan) {
                        return $loan->status === 'overdue';
                    }),
                    'chartData' => $chartData,
                    'monthlyData' => $monthlyData,
                ];
                break;

            case 'teller':
                $data = [
                    'todaysDisbursements' => Disbursement::whereDate('disburse_date', today())
                                                    ->sum('amount') ?? 0,
                    'monthDisbursements' => Disbursement::where('disburse_date', '>=', $currentMonthStart)
                                                    ->sum('amount') ?? 0,
                    'collectedRepayments' => Repayment::sum('amount') ?? 0,
                    'monthRepayments' => Repayment::where('created_at', '>=', $currentMonthStart)
                                            ->sum('amount') ?? 0,
                    'dueLoans' => $dueLoans,
                    'chartData' => $chartData,
                    'monthlyData' => $monthlyData,
                ];
                break;
            
            default:
                $data = [
                    'dueLoans' => $dueLoans,
                    'chartData' => $chartData,
                    'monthlyData' => $monthlyData,
                ];
                break;
        }

        // Ensure all required variables exist for the view
        $data = array_merge([
            'totalLoans' => 0,
            'loansThisMonth' => 0,
            'completedLoans' => 0,
            'completedThisMonth' => 0,
            'totalDisbursements' => 0,
            'disbursementsThisMonth' => 0,
            'totalRepayments' => 0,
            'repaymentsThisMonth' => 0,
            'borrowerCount' => 0,
            'newBorrowersThisMonth' => 0,
            'brokerCount' => 0,
            'tellerCount' => 0,
            'totalBorrowed' => 0,
            'borrowedThisMonth' => 0,
            'chartData' => ['months' => [], 'loans' => [], 'disbursements' => [], 'repayments' => []],
            'monthlyData' => ['labels' => [], 'loanData' => [], 'disbursementData' => [], 'repaymentData' => []],
            'recentLoans' => collect(),
            'dueLoans' => collect(),
            'todayTransactions' => 0,
            'loanStatusData' => ['pending' => 0, 'disbursed' => 0, 'approved' => 0, 'rejected' => 0, 'repaid' => 0],
            'disbursementTrends' => [],
            'biodataComplete' => false,
            'biodataCompletionPercentage' => 0,
            'missingBiodataFields' => [],
            'broker' => null,
            'clients' => 0,
            'newClientsThisMonth' => 0,
            'activeLoans' => 0,
            'totalInterest' => 0,
            'totalPenalty' => 0,
            'overdueLoans' => collect(),
            'todaysDisbursements' => 0,
            'monthDisbursements' => 0,
            'collectedRepayments' => 0,
            'monthRepayments' => 0,
        ], $data);

        return view('dashboard', $data);
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
        $baseQuery = Loan::with(['user', 'loanType'])
            ->whereIn('status', ['disbursed', 'approved'])
            ->join('loan_types', 'loans.loan_type_id', '=', 'loan_types.id')
            ->select('loans.*');

        switch ($user->role) {
            case 'admin':
            case 'teller':
                // Get all loans with status disbursed or approved
                break;

            case 'borrower':
                $baseQuery->where('loans.user_id', $user->id);
                break;

            case 'broker':
                $currentBrokerId = $user->broker->id ?? null;
                if ($currentBrokerId) {
                    $borrowerIds = Borrower::where('broker_id', $currentBrokerId)
                        ->pluck('user_id');
                    $baseQuery->whereIn('loans.user_id', $borrowerIds);
                }
                break;
        }

        $loans = $baseQuery->get();

        return $loans->map(function ($loan) {
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

            $today = Carbon::now()->startOfDay();
            $remainingDays = $today->diffInDays($dueDate, false);

            $loan->due_date = $dueDate;
            $loan->remaining_days = $remainingDays;
            $loan->status = $remainingDays < 0 ? 'overdue' : 'disbursed';
            $loan->overdue_days = $remainingDays < 0 ? abs($remainingDays) : 0;

            if ($remainingDays < 0) {
                $interval = $today->diff($dueDate);
                $loan->overdue_period = [
                    'months' => $interval->m,
                    'days' => $interval->d
                ];
            }

            return $loan;
        })->sortBy('remaining_days')->values();
    }
}