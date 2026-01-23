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

        // Helper function to filter out specific transaction types
        $filterTransactions = function($query) {
            return $query->whereNotIn('transaction', ['ROLL OVER', 'CREDIT DISCOUNT']);
        };

        logger('Auth check:', [
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'user' => auth()->user()
        ]);

        switch ($user->role) {
            case 'admin':
                $data = [
                    // Loan Metrics (unchanged)
                    'totalLoans' => Loan::count(),
                    'loansThisMonth' => Loan::where('created_at', '>=', $currentMonthStart)->count(),
                    'completedLoans' => Loan::completed()->count(),
                    'completedThisMonth' => Loan::completed()
                                            ->where('updated_at', '>=', $currentMonthStart)
                                            ->count(),

                    // Financial Metrics - FILTERED
                    'totalDisbursements' => Disbursement::sum('amount'),
                    'disbursementsThisMonth' => Disbursement::where('disburse_date', '>=', $currentMonthStart)
                                                        // ->whereNotIn('transaction', ['ROLL OVER', 'CREDIT DISCOUNT'])
                                                        ->sum('amount'),
                    'totalRepayments' => Repayment::sum('amount'),
                    'repaymentsThisMonth' => Repayment::where('created_at', '>=', $currentMonthStart)
                                                    // ->whereNotIn('transaction', ['ROLL OVER', 'CREDIT DISCOUNT'])
                                                    ->sum('amount'),

                    // User Metrics (unchanged)
                    'borrowerCount' => User::borrowers()->count(),
                    'newBorrowersThisMonth' => User::borrowers()
                                                ->where('created_at', '>=', $currentMonthStart)
                                                ->count(),
                    'brokerCount' => User::brokers()->count(),
                    'tellerCount' => User::tellers()->count(),

                    // Additional Data
                    'chartData' => $chartData,
                    'recentLoans' => Loan::with(['user', 'loanType'])->latest()->take(5)->get(),
                    'dueLoans' => $dueLoans,
                    'todayTransactions' => Repayment::whereDate('created_at', today())
                                                // ->whereNotIn('transaction', ['ROLL OVER', 'CREDIT DISCOUNT'])
                                                ->count(),

                    'loanStatusData' => [
                        'pending' => Loan::where('status', 'pending')->count(),
                        'disbursed' => Loan::where('status', 'disbursed')->count(),
                        'approved' => Loan::where('status', 'approved')->count(),
                        'rejected' => Loan::where('status', 'rejected')->count(),
                    ],
                    'disbursementTrends' => $this->getDisbursementTrends(),
                ];
                break;

                case 'borrower':
                    $data = [
                        // Loan metrics
                        'totalLoans' => $user->loans()->count(),
                        'loansThisMonth' => $user->loans()
                                                ->where('created_at', '>=', $currentMonthStart)
                                                ->count(),
                        
                        // Repayment metrics - FILTERED
                        'totalRepayments' => $user->repayments()
                                                // ->whereNotIn('repayments.transaction', ['ROLL OVER', 'CREDIT DISCOUNT', 'BAD DEBT'])
                                                ->sum('repayments.amount'),
                        'repaymentsThisMonth' => $user->repayments()
                                                    ->where('repayments.created_at', '>=', $currentMonthStart)
                                                    // ->whereNotIn('repayments.transaction', ['ROLL OVER', 'CREDIT DISCOUNT', 'BAD DEBT'])
                                                    ->sum('repayments.amount'),
                        
                        // Disbursement metrics - FIXED to use disbursements table
                        'totalDisbursements' => $user->disbursements()
                                                    // ->whereNotIn('disbursements.transaction', ['ROLL OVER', 'CREDIT DISCOUNT', 'BAD DEBT'])
                                                    ->sum('disbursements.amount'),
                        'disbursementsThisMonth' => $user->disbursements()
                                                        ->where('disbursements.created_at', '>=', $currentMonthStart)
                                                        // ->whereNotIn('disbursements.transaction', ['ROLL OVER', 'CREDIT DISCOUNT', 'BAD DEBT'])
                                                        ->sum('disbursements.amount'),
                        
                        // Loan status metrics
                        'totalBorrowed' => $user->loans()->sum('amount'),
                        'borrowedThisMonth' => $user->loans()
                                                ->where('borrow_date', '>=', $currentMonthStart)
                                                ->sum('amount'),
                        'dueLoans' => $dueLoans,

                        // Biodata completion
                        'biodataComplete' => $user->hasCompleteBiodata(),
                        'missingBiodataFields' => $user->getMissingBiodataFields(),
                        'biodataCompletionPercentage' => $user->getBiodataCompletionPercentage(),
                    ];
                    break;
                case 'broker':
                // Get the authenticated user's broker profile
                $broker = $user->broker()->firstOrFail();

                // Add null check for broker profile
                if (!$broker) {
                    abort(403, 'Broker profile not found');
                }

                $borrowerIds = $broker->borrowers()->pluck('user_id');

                $data = [
                    // Client Metrics (unchanged)
                    'broker' => $broker,
                    'clients' => $broker->borrowers()->count(),
                    'newClientsThisMonth' => $broker->borrowers()
                                                ->where('created_at', '>=', $currentMonthStart)
                                                ->count(),
                    
                    // Loan Metrics: Add broker_status condition
                    'activeLoans' => Loan::whereIn('user_id', $borrowerIds)
                                    ->where('broker_status', 1)
                                    ->active()
                                    ->count(),
                    
                    // Earnings Calculations - FILTERED
                    'totalInterest' => $this->calculateBrokerEarnings($broker, 'interest'),
                    'totalPenalty' => $this->calculateBrokerEarnings($broker, 'penalty'),
                    
                    // Loan Status: Add broker_status condition
                    'dueLoans' => $dueLoans->where('status', 'disbursed')->where('broker_status', 1),
                    'overdueLoans' => $dueLoans->where('status', 'overdue')->where('broker_status', 1),
                ];
                break;

            case 'teller':
                $data = [
                    'todaysDisbursements' => Disbursement::whereDate('disburse_date', today())
                                                    ->whereNotIn('transaction', ['ROLL OVER', 'CREDIT DISCOUNT'])
                                                    ->sum('amount'),
                    'monthDisbursements' => Disbursement::where('disburse_date', '>=', $currentMonthStart)
                                                    ->whereNotIn('transaction', ['ROLL OVER', 'CREDIT DISCOUNT'])
                                                    ->sum('amount'),
                    'collectedRepayments' => Repayment::whereNotIn('transaction', ['ROLL OVER', 'CREDIT DISCOUNT'])->sum('amount'),
                    'monthRepayments' => Repayment::where('created_at', '>=', $currentMonthStart)
                                            ->whereNotIn('transaction', ['ROLL OVER', 'CREDIT DISCOUNT'])
                                            ->sum('amount'),
                    'dueLoans' => $dueLoans,
                ];
                break;
        }
        
        return view('dashboard', $data);
    }

    // Update the getChartData method to also filter out these transactions
    private function getChartData()
    {
        $months = [];
        $loans = [];
        $disbursements = [];
        $repayments = [];
        
        // Get the earliest date from all three data sources
        $earliestLoanDate = Loan::min('borrow_date');
        $earliestDisbursementDate = Disbursement::min('disburse_date');
        $earliestRepaymentDate = Repayment::min('repayment_date');
        
        // Find the earliest date among all sources
        $earliestDate = collect([
            $earliestLoanDate,
            $earliestDisbursementDate,
            $earliestRepaymentDate
        ])->filter()->min();
        
        // If no data exists, return empty arrays
        if (!$earliestDate) {
            return [
                'months' => [],
                'loans' => [],
                'disbursements' => [],
                'repayments' => []
            ];
        }
        
        $startDate = now()->subMonths(5)->startOfMonth();
        $endDate = now()->endOfMonth();
        
        // Adjust start date if we have older data
        if ($earliestDate < $startDate) {
            $startDate = Carbon::parse($earliestDate)->startOfMonth();
        }
        
        // Generate all months in the range
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $monthName = $currentDate->format('M Y');
            $months[] = $monthName;
            
            // Initialize data for this month
            $loans[] = 0;
            $disbursements[] = 0;
            $repayments[] = 0;
            
            $currentDate->addMonth();
        }
        
        // Get loan data grouped by month - FILTERED
        $loanData = Loan::whereBetween('borrow_date', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(borrow_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        // Get disbursement data grouped by month - FILTERED
        $disbursementData = Disbursement::whereBetween('disburse_date', [$startDate, $endDate])
            // ->whereNotIn('transaction', ['ROLL OVER', 'CREDIT DISCOUNT', 'BAD DEBT'])
            ->selectRaw('DATE_FORMAT(disburse_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        // Get repayment data grouped by month - FILTERED
        $repaymentData = Repayment::whereBetween('repayment_date', [$startDate, $endDate])
            // ->whereNotIn('transaction', ['ROLL OVER', 'CREDIT DISCOUNT', 'BAD DEBT'])
            ->selectRaw('DATE_FORMAT(repayment_date, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        // Fill the data arrays with actual values
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
                // Broker client: fixed interest
                if ($user->broker) {
                    return $broker->interest_client;
                }
                // Non-broker: percentage of loan interest
                $loanInterest = ($loanType->interest_rate / 100) * $loan->amount;
                return ($broker->interest_broker / 100) * $loanInterest;
            }

            if ($type === 'penalty') {
                // Calculate due date and overdue days
                $borrowDate = \Carbon\Carbon::parse($loan->borrow_date);
                $dueDate = $borrowDate->copy()->add(
                    $loanType->period, 
                    $loanType->unit
                );
                $today = \Carbon\Carbon::today();
                $overdueDays = max(0, $today->diffInDays($dueDate, false) * -1);

                if ($overdueDays <= 0) {
                    return 0; // No penalty if not overdue
                }

                // Broker client: fixed penalty
                if ($user->broker) {
                    return $broker->penalty_client;
                }

                // Non-broker: commission-based penalty
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
        $baseQuery = Loan::with(['borrower', 'loanType'])
            ->where('status', 'disbursed')
            ->join('loan_types', 'loans.loan_type_id', '=', 'loan_types.id')
            ->select('loans.*');

        switch ($user->role) {
            case 'admin':
            case 'teller':
                break;

            case 'borrower':
                $baseQuery->where('loans.user_id', $user->id);
                break;

            case 'broker':
                $currentBrokerId = $user->broker->id;
                $borrowerIds = Borrower::where('broker_id', $currentBrokerId)
                    ->pluck('user_id');
                $baseQuery->whereIn('loans.user_id', $borrowerIds);
                break;
        }

        return $baseQuery->get()->map(function ($loan) {
            $borrowDate = Carbon::parse($loan->borrow_date)->startOfDay();
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
        })->sortBy('remaining_days'); // Sort by remaining days (ascending)
    }


 
}
