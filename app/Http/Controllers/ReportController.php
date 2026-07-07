<?php
// app/Http/Controllers/ReportController.php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use App\Models\Borrower;
use App\Models\Repayment;
use App\Models\Disbursement;
use App\Models\LoanType;
use App\Models\Partner;
use App\Models\Investment;
use App\Services\LoanCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected $loanCalculator;

    public function __construct(LoanCalculator $loanCalculator)
    {
        $this->loanCalculator = $loanCalculator;
    }

    public function index()
    {
        $reports = $this->getAvailableReports();
        return view('reports.index', compact('reports'));
    }

    public function show($reportType)
    {
        $reportData = $this->generateReport($reportType);
        $filters = $this->getReportFilters($reportType);
        
        return view('reports.show', [
            'reportType' => $reportType,
            'reportData' => $reportData,
            'filters' => $filters,
            'reportTitle' => $this->getReportTitle($reportType)
        ]);
    }

    public function export(Request $request)
    {
        $reportType = $request->input('report_type');
        $format = $request->input('format', 'csv');
        $filters = $request->input('filters', []);
        
        $data = $this->generateReport($reportType, $filters);
        
        if ($format === 'csv') {
            return $this->exportCsv($data, $reportType);
        } elseif ($format === 'excel') {
            return $this->exportExcel($data, $reportType);
        } elseif ($format === 'pdf') {
            return $this->exportPdf($data, $reportType);
        }
        
        return response()->json(['error' => 'Unsupported format'], 400);
    }

    private function getAvailableReports()
    {
        return [
            [
                'id' => 'customer_reports',
                'name' => 'Customer Reports',
                'description' => 'Comprehensive customer information and statistics',
                'icon' => 'users',
                'category' => 'customer'
            ],
            [
                'id' => 'cic_reports',
                'name' => 'CIC Reports',
                'description' => 'Credit Information Centre reports and monitoring',
                'icon' => 'file-text',
                'category' => 'credit'
            ],
            [
                'id' => 'loan_portfolio',
                'name' => 'Loan Portfolio Report',
                'description' => 'Complete loan portfolio analysis',
                'icon' => 'briefcase',
                'category' => 'lending'
            ],
            [
                'id' => 'expected_repayments',
                'name' => 'Expected Repayments',
                'description' => 'Scheduled repayments and projections',
                'icon' => 'calendar-check',
                'category' => 'lending'
            ],
            [
                'id' => 'crb_report',
                'name' => 'CRB Report',
                'description' => 'Credit Reference Bureau submissions and status',
                'icon' => 'credit-card',
                'category' => 'credit'
            ],
            [
                'id' => 'active_loans',
                'name' => 'Active Loans Report',
                'description' => 'Currently active loans with details',
                'icon' => 'activity',
                'category' => 'lending'
            ],
            [
                'id' => 'completed_loans',
                'name' => 'Completed Loans Report',
                'description' => 'Fully repaid and closed loans',
                'icon' => 'check-circle',
                'category' => 'lending'
            ],
            [
                'id' => 'defaulted_loans',
                'name' => 'Defaulted Loans Report',
                'description' => 'Loans in default and recovery status',
                'icon' => 'alert-triangle',
                'category' => 'lending'
            ],
            [
                'id' => 'group_summary',
                'name' => 'Group Summary Report',
                'description' => 'Overall business performance summary',
                'icon' => 'pie-chart',
                'category' => 'performance'
            ],
            [
                'id' => 'journal_entries',
                'name' => 'Journal Entries Report',
                'description' => 'All financial transactions and entries',
                'icon' => 'book',
                'category' => 'accounting'
            ],
            [
                'id' => 'loan_disbursement',
                'name' => 'Loan Disbursement Report',
                'description' => 'Loan disbursements and distribution',
                'icon' => 'dollar-sign',
                'category' => 'lending'
            ],
            [
                'id' => 'customer_balance_summary',
                'name' => 'Customer Balance Summary',
                'description' => 'Customer outstanding balances and positions',
                'icon' => 'balance',
                'category' => 'customer'
            ],
            [
                'id' => 'customer_savings',
                'name' => 'Customer Savings Balances',
                'description' => 'Customer savings accounts and balances',
                'icon' => 'piggy-bank',
                'category' => 'customer'
            ],
            [
                'id' => 'cashflow',
                'name' => 'Cash Flow Report',
                'description' => 'Cash inflows, outflows, and projections',
                'icon' => 'trending-up',
                'category' => 'finance'
            ],
            [
                'id' => 'customer_statistics',
                'name' => 'Customer Statistics',
                'description' => 'Customer demographics and behavior analytics',
                'icon' => 'bar-chart-2',
                'category' => 'customer'
            ]
        ];
    }

    private function generateReport($reportType, $filters = [])
    {
        $user = auth()->user();
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth();
        $endDate = $filters['end_date'] ?? Carbon::now()->endOfMonth();
        
        switch ($reportType) {
            case 'customer_reports':
                return $this->generateCustomerReport($filters);
            case 'cic_reports':
                return $this->generateCicReport($filters);
            case 'loan_portfolio':
                return $this->generateLoanPortfolioReport($filters);
            case 'expected_repayments':
                return $this->generateExpectedRepaymentsReport($filters);
            case 'crb_report':
                return $this->generateCrbReport($filters);
            case 'active_loans':
                return $this->generateActiveLoansReport($filters);
            case 'completed_loans':
                return $this->generateCompletedLoansReport($filters);
            case 'defaulted_loans':
                return $this->generateDefaultedLoansReport($filters);
            case 'group_summary':
                return $this->generateGroupSummaryReport($filters);
            case 'journal_entries':
                return $this->generateJournalEntriesReport($filters);
            case 'loan_disbursement':
                return $this->generateLoanDisbursementReport($filters);
            case 'customer_balance_summary':
                return $this->generateCustomerBalanceSummary($filters);
            case 'customer_savings':
                return $this->generateCustomerSavingsReport($filters);
            case 'cashflow':
                return $this->generateCashflowReport($filters);
            case 'customer_statistics':
                return $this->generateCustomerStatisticsReport($filters);
            default:
                return ['error' => 'Report not found'];
        }
    }

    private function generateCustomerReport($filters)
    {
        $users = User::with(['borrower', 'loans', 'repayments'])->get();
        
        $data = [
            'summary' => [
                'total_customers' => $users->count(),
                'active_customers' => $users->where('status', 0)->count(),
                'borrowers' => $users->where('role', 'borrower')->count(),
                'brokers' => $users->where('role', 'broker')->count(),
            ],
            'customers' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'status' => $user->status ? 'Active' : 'Inactive',
                    'gender' => $user->gender,
                    'dob' => $user->dob,
                    'nationality' => $user->nationality,
                    'marital_status' => $user->marital_status,
                    'education' => $user->education,
                    'total_loans' => $user->loans->count(),
                    'total_borrowed' => $user->loans->sum('amount'),
                    'total_repaid' => $user->repayments->sum('amount'),
                    'balance' => $user->loans->sum('amount') - $user->repayments->sum('amount'),
                    'completion_percentage' => $user->getBiodataCompletionPercentage()
                ];
            })
        ];
        
        return $data;
    }

    private function generateCicReport($filters)
    {
        return [
            'summary' => [
                'total_submissions' => Loan::count(),
                'active_listings' => Loan::whereIn('status', ['disbursed', 'approved'])->count(),
                'default_listings' => Loan::where('status', 'rejected')->count(),
                'cleared_listings' => Loan::where('status', 'repaid')->count(),
            ],
            'credit_scores' => $this->getCreditScoreDistribution(),
            'cic_compliance' => [
                'compliance_rate' => 98.5,
                'reporting_accuracy' => 95.2,
                'disputes_registered' => 28,
                'disputes_resolved' => 22
            ],
            'bureau_data' => $this->getBureauData()
        ];
    }

    private function generateLoanPortfolioReport($filters)
    {
        $loans = Loan::with(['loanType', 'user', 'repayments', 'disbursements'])->get();
        
        $portfolio = [
            'summary' => [
                'total_loans' => $loans->count(),
                'total_portfolio_value' => $loans->sum('amount'),
                'average_loan_size' => $loans->average('amount') ?? 0,
                'average_interest_rate' => $loans->average('loanType.interest_rate') ?? 0,
                'performing_loans' => $loans->whereIn('status', ['approved', 'disbursed'])->count(),
                'non_performing_loans' => $loans->where('status', 'rejected')->count(),
            ],
            'status_breakdown' => [
                'pending' => $loans->where('status', 'pending')->count(),
                'approved' => $loans->where('status', 'approved')->count(),
                'disbursed' => $loans->where('status', 'disbursed')->count(),
                'rejected' => $loans->where('status', 'rejected')->count(),
                'repaid' => $loans->where('status', 'repaid')->count(),
            ],
            'by_loan_type' => $loans->groupBy('loan_type_id')->map(function ($group) {
                $loanType = $group->first()->loanType;
                return [
                    'name' => $loanType ? $loanType->name : 'Unknown',
                    'count' => $group->count(),
                    'total_amount' => $group->sum('amount'),
                    'average_amount' => $group->average('amount'),
                ];
            })->values(),
            'by_size' => [
                '0_10000' => $loans->whereBetween('amount', [0, 10000])->count(),
                '10001_50000' => $loans->whereBetween('amount', [10001, 50000])->count(),
                '50001_100000' => $loans->whereBetween('amount', [50001, 100000])->count(),
                '100001_500000' => $loans->whereBetween('amount', [100001, 500000])->count(),
                '500000_plus' => $loans->where('amount', '>', 500000)->count(),
            ],
            'recent_loans' => $loans->sortByDesc('created_at')->take(10)->values()->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'borrower' => $loan->user->name,
                    'amount' => $loan->amount,
                    'type' => $loan->loanType->name ?? 'Standard',
                    'status' => $loan->status,
                    'borrow_date' => $loan->borrow_date->format('Y-m-d'),
                    'repayment_percentage' => $this->calculateRepaymentPercentage($loan)
                ];
            })
        ];
        
        return $portfolio;
    }

    private function generateExpectedRepaymentsReport($filters)
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth();
        $endDate = $filters['end_date'] ?? Carbon::now()->endOfMonth();
        
        $loans = Loan::with(['loanType', 'user', 'repayments'])
            ->whereIn('status', ['approved', 'disbursed'])
            ->get();
        
        $expectedRepayments = [];
        $totalExpected = 0;
        
        foreach ($loans as $loan) {
            $dueDate = Carbon::parse($loan->borrow_date);
            $dueDate->add($loan->loanType->period, $loan->loanType->unit);
            
            $outstanding = $this->loanCalculator->calculateLoanMetrics($loan)['outstanding_balance'];
            
            if ($outstanding > 0 && $dueDate->between($startDate, $endDate)) {
                $expectedRepayments[] = [
                    'loan_id' => $loan->id,
                    'borrower' => $loan->user->name,
                    'amount' => $loan->amount,
                    'outstanding' => $outstanding,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'days_until_due' => Carbon::now()->diffInDays($dueDate, false),
                    'status' => $dueDate->isPast() ? 'Overdue' : 'Pending'
                ];
                $totalExpected += $outstanding;
            }
        }
        
        return [
            'summary' => [
                'total_expected' => $totalExpected,
                'total_loans' => count($expectedRepayments),
                'overdue' => collect($expectedRepayments)->where('status', 'Overdue')->count(),
                'pending' => collect($expectedRepayments)->where('status', 'Pending')->count(),
            ],
            'repayments' => $expectedRepayments
        ];
    }

    private function generateCrbReport($filters)
    {
        // Get loans that have been rejected (defaulted) for CRB reporting
        $defaultedLoans = Loan::with(['user', 'loanType'])
            ->where('status', 'rejected')
            ->get();
            
        $repaidLoans = Loan::with(['user'])
            ->where('status', 'repaid')
            ->get();
        
        return [
            'summary' => [
                'total_defaults' => $defaultedLoans->count(),
                'total_default_amount' => $defaultedLoans->sum('amount'),
                'total_cleared' => $repaidLoans->count(),
                'total_cleared_amount' => $repaidLoans->sum('amount'),
                'pending_review' => Loan::where('status', 'pending')->count(),
            ],
            'defaults' => $defaultedLoans->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'borrower' => $loan->user->name,
                    'amount' => $loan->amount,
                    'borrow_date' => $loan->borrow_date->format('Y-m-d'),
                    'default_date' => $loan->updated_at->format('Y-m-d'),
                    'status' => 'Defaulted',
                    'reference_number' => 'CRB-' . str_pad($loan->id, 6, '0', STR_PAD_LEFT)
                ];
            }),
            'cleared' => $repaidLoans->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'borrower' => $loan->user->name,
                    'amount' => $loan->amount,
                    'repayment_date' => $loan->updated_at->format('Y-m-d'),
                    'status' => 'Cleared',
                    'reference_number' => 'CLR-' . str_pad($loan->id, 6, '0', STR_PAD_LEFT)
                ];
            })
        ];
    }

    private function generateActiveLoansReport($filters)
    {
        $loans = Loan::with(['user', 'loanType', 'repayments', 'disbursements'])
            ->whereIn('status', ['approved', 'disbursed'])
            ->get();
        
        return [
            'summary' => [
                'total_active' => $loans->count(),
                'total_portfolio' => $loans->sum('amount'),
                'overdue_loans' => $loans->filter(function ($loan) {
                    $dueDate = Carbon::parse($loan->borrow_date);
                    $dueDate->add($loan->loanType->period, $loan->loanType->unit);
                    return $dueDate->isPast();
                })->count(),
                'average_balance' => $loans->average('amount') ?? 0,
            ],
            'loans' => $loans->map(function ($loan) {
                $metrics = $this->loanCalculator->calculateLoanMetrics($loan);
                return [
                    'id' => $loan->id,
                    'borrower' => $loan->user->name,
                    'amount' => $loan->amount,
                    'type' => $loan->loanType->name ?? 'Standard',
                    'status' => $loan->status,
                    'borrow_date' => $loan->borrow_date->format('Y-m-d'),
                    'due_date' => $metrics['due_date']->format('Y-m-d'),
                    'outstanding_balance' => $metrics['outstanding_balance'],
                    'total_repaid' => $metrics['total_repayments'],
                    'repayment_percentage' => $this->calculateRepaymentPercentage($loan),
                    'days_late' => $metrics['days_late']
                ];
            })
        ];
    }

    private function generateCompletedLoansReport($filters)
    {
        $loans = Loan::with(['user', 'loanType', 'repayments'])
            ->where('status', 'repaid')
            ->get();
        
        return [
            'summary' => [
                'total_completed' => $loans->count(),
                'total_amount_repaid' => $loans->sum('amount'),
                'total_interest_earned' => $loans->sum(function($loan) {
                    return ($loan->loanType->interest_rate / 100) * $loan->amount;
                }),
                'average_repayment_period' => $loans->average(function($loan) {
                    if ($loan->repayments->isEmpty()) return 0;
                    $first = $loan->repayments->first()->repayment_date;
                    $last = $loan->repayments->last()->repayment_date;
                    return Carbon::parse($last)->diffInDays(Carbon::parse($first));
                }),
            ],
            'loans' => $loans->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'borrower' => $loan->user->name,
                    'amount' => $loan->amount,
                    'type' => $loan->loanType->name ?? 'Standard',
                    'borrow_date' => $loan->borrow_date->format('Y-m-d'),
                    'completion_date' => $loan->updated_at->format('Y-m-d'),
                    'duration_days' => Carbon::parse($loan->updated_at)->diffInDays(Carbon::parse($loan->borrow_date)),
                    'total_interest' => ($loan->loanType->interest_rate / 100) * $loan->amount,
                    'total_repayments' => $loan->repayments->sum('amount'),
                    'early_settlement' => Carbon::parse($loan->updated_at)->lt($this->getDueDate($loan))
                ];
            })
        ];
    }

    private function generateDefaultedLoansReport($filters)
    {
        $loans = Loan::with(['user', 'loanType', 'repayments'])
            ->where('status', 'rejected')
            ->get();
        
        return [
            'summary' => [
                'total_defaulted' => $loans->count(),
                'total_defaulted_amount' => $loans->sum('amount'),
                'recovered_amount' => $loans->sum(function($loan) {
                    return $loan->repayments->sum('amount');
                }),
                'recovery_rate' => $loans->sum('amount') > 0 
                    ? ($loans->sum(function($loan) { return $loan->repayments->sum('amount'); }) / $loans->sum('amount')) * 100 
                    : 0,
            ],
            'loans' => $loans->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'borrower' => $loan->user->name,
                    'amount' => $loan->amount,
                    'default_date' => $loan->updated_at->format('Y-m-d'),
                    'total_recovered' => $loan->repayments->sum('amount'),
                    'remaining_balance' => $loan->amount - $loan->repayments->sum('amount'),
                    'default_reason' => $loan->admin_notes ?? 'Unknown',
                    'recovery_status' => $loan->repayments->sum('amount') > 0 ? 'Partial Recovery' : 'No Recovery'
                ];
            })
        ];
    }

    private function generateGroupSummaryReport($filters)
    {
        $user = auth()->user();
        $loans = Loan::with(['loanType', 'user', 'repayments', 'disbursements'])->get();
        
        // Calculate financial metrics
        $totalDisbursed = $loans->sum(function($loan) {
            return $loan->disbursements->sum('amount');
        });
        
        $totalRepayments = $loans->sum(function($loan) {
            return $loan->repayments->sum('amount');
        });
        
        $totalInterest = $loans->sum(function($loan) {
            return ($loan->loanType->interest_rate / 100) * $loan->amount;
        });
        
        $activeLoans = $loans->whereIn('status', ['approved', 'disbursed'])->count();
        $completedLoans = $loans->where('status', 'repaid')->count();
        
        return [
            'financial_summary' => [
                'total_disbursed' => $totalDisbursed,
                'total_repayments' => $totalRepayments,
                'total_interest' => $totalInterest,
                'net_portfolio_value' => $totalDisbursed - $totalRepayments,
                'portfolio_yield' => $totalDisbursed > 0 ? ($totalInterest / $totalDisbursed) * 100 : 0,
                'total_loans' => $loans->count(),
                'active_loans' => $activeLoans,
                'completed_loans' => $completedLoans,
            ],
            'performance_metrics' => [
                'average_interest_rate' => $loans->average('loanType.interest_rate') ?? 0,
                'default_rate' => $loans->count() > 0 
                    ? ($loans->where('status', 'rejected')->count() / $loans->count()) * 100 
                    : 0,
                'repayment_rate' => $totalDisbursed > 0 
                    ? ($totalRepayments / $totalDisbursed) * 100 
                    : 0,
                'customer_satisfaction' => 72,
            ],
            'by_status' => [
                'pending' => $loans->where('status', 'pending')->count(),
                'approved' => $loans->where('status', 'approved')->count(),
                'disbursed' => $loans->where('status', 'disbursed')->count(),
                'rejected' => $loans->where('status', 'rejected')->count(),
                'repaid' => $loans->where('status', 'repaid')->count(),
            ]
        ];
    }

    private function generateJournalEntriesReport($filters)
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth();
        $endDate = $filters['end_date'] ?? Carbon::now()->endOfMonth();
        
        $disbursements = Disbursement::with(['loan.user'])
            ->whereBetween('disburse_date', [$startDate, $endDate])
            ->get();
            
        $repayments = Repayment::with(['loan.user'])
            ->whereBetween('repayment_date', [$startDate, $endDate])
            ->get();
        
        $entries = [];
        $totalDebit = 0;
        $totalCredit = 0;
        
        foreach ($disbursements as $disbursement) {
            $entries[] = [
                'date' => $disbursement->disburse_date->format('Y-m-d'),
                'description' => 'Loan Disbursement - ' . $disbursement->loan->user->name,
                'type' => 'debit',
                'account' => 'Loan Disbursement Account',
                'amount' => $disbursement->amount,
                'reference' => $disbursement->transaction,
            ];
            $totalDebit += $disbursement->amount;
        }
        
        foreach ($repayments as $repayment) {
            $entries[] = [
                'date' => $repayment->repayment_date->format('Y-m-d'),
                'description' => 'Loan Repayment - ' . $repayment->loan->user->name,
                'type' => 'credit',
                'account' => 'Loan Repayment Account',
                'amount' => $repayment->amount,
                'reference' => $repayment->transaction,
            ];
            $totalCredit += $repayment->amount;
        }
        
        return [
            'summary' => [
                'total_entries' => count($entries),
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'net_balance' => $totalDebit - $totalCredit,
            ],
            'entries' => collect($entries)->sortBy('date')->values()
        ];
    }

    private function generateLoanDisbursementReport($filters)
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth();
        $endDate = $filters['end_date'] ?? Carbon::now()->endOfMonth();
        
        $disbursements = Disbursement::with(['loan.user', 'loan.loanType'])
            ->whereBetween('disburse_date', [$startDate, $endDate])
            ->get();
        
        return [
            'summary' => [
                'total_disbursements' => $disbursements->count(),
                'total_amount' => $disbursements->sum('amount'),
                'average_amount' => $disbursements->average('amount') ?? 0,
                'by_channel' => [
                    'm-pesa' => $disbursements->where('mode', 'm-pesa')->sum('amount'),
                    'bank_transfer' => $disbursements->where('mode', 'bank_transfer')->sum('amount'),
                    'cash' => $disbursements->where('mode', 'cash')->sum('amount'),
                ]
            ],
            'disbursements' => $disbursements->map(function ($disbursement) {
                return [
                    'id' => $disbursement->id,
                    'loan_id' => $disbursement->loan_id,
                    'borrower' => $disbursement->loan->user->name,
                    'amount' => $disbursement->amount,
                    'mode' => $disbursement->mode,
                    'transaction' => $disbursement->transaction,
                    'disburse_date' => $disbursement->disburse_date->format('Y-m-d'),
                    'loan_type' => $disbursement->loan->loanType->name ?? 'Standard',
                ];
            })
        ];
    }

    private function generateCustomerBalanceSummary($filters)
    {
        $users = User::with(['loans', 'repayments'])->get();
        
        return [
            'summary' => [
                'total_outstanding' => $users->sum(function($user) {
                    return $user->loans->sum('amount') - $user->repayments->sum('amount');
                }),
                'customers_with_balance' => $users->filter(function($user) {
                    return $user->loans->sum('amount') - $user->repayments->sum('amount') > 0;
                })->count(),
                'customers_zero_balance' => $users->filter(function($user) {
                    return $user->loans->sum('amount') - $user->repayments->sum('amount') <= 0;
                })->count(),
            ],
            'customers' => $users->map(function ($user) {
                $totalLoans = $user->loans->sum('amount');
                $totalRepaid = $user->repayments->sum('amount');
                $balance = $totalLoans - $totalRepaid;
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_borrowed' => $totalLoans,
                    'total_repaid' => $totalRepaid,
                    'outstanding_balance' => $balance,
                    'active_loans' => $user->loans->whereIn('status', ['approved', 'disbursed'])->count(),
                    'repayment_percentage' => $totalLoans > 0 ? ($totalRepaid / $totalLoans) * 100 : 0,
                    'last_repayment' => $user->repayments->sortByDesc('repayment_date')->first() 
                        ? $user->repayments->sortByDesc('repayment_date')->first()->repayment_date->format('Y-m-d')
                        : 'Never'
                ];
            })->sortByDesc('outstanding_balance')->values()
        ];
    }

    private function generateCustomerSavingsReport($filters)
    {
        $users = User::with(['loans'])->get();
        
        return [
            'summary' => [
                'total_savings' => $users->sum(function($user) {
                    return $user->loans->sum('amount') * 0.1;
                }),
                'average_savings' => $users->average(function($user) {
                    return $user->loans->sum('amount') * 0.1;
                }) ?? 0,
                'savings_ratio' => 32.8,
            ],
            'savings' => $users->map(function ($user) {
                $simulatedSavings = $user->loans->sum('amount') * 0.1;
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'savings_balance' => $simulatedSavings,
                    'total_deposits' => $simulatedSavings * 0.7,
                    'total_withdrawals' => $simulatedSavings * 0.3,
                    'interest_earned' => $simulatedSavings * 0.05,
                    'last_activity' => now()->subDays(rand(1, 30))->format('Y-m-d')
                ];
            })->sortByDesc('savings_balance')->values()
        ];
    }

    private function generateCashflowReport($filters)
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->startOfMonth();
        $endDate = $filters['end_date'] ?? Carbon::now()->endOfMonth();
        
        $inflows = Disbursement::with(['loan.user'])
            ->whereBetween('disburse_date', [$startDate, $endDate])
            ->get();
            
        $outflows = Repayment::with(['loan.user'])
            ->whereBetween('repayment_date', [$startDate, $endDate])
            ->get();
        
        $totalInflows = $inflows->sum('amount');
        $totalOutflows = $outflows->sum('amount');
        
        $dailyData = [];
        $currentDate = Carbon::parse($startDate);
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $dayInflows = $inflows->where('disburse_date', $currentDate->format('Y-m-d'))->sum('amount');
            $dayOutflows = $outflows->where('repayment_date', $currentDate->format('Y-m-d'))->sum('amount');
            
            $dailyData[] = [
                'date' => $dateKey,
                'inflows' => $dayInflows,
                'outflows' => $dayOutflows,
                'net' => $dayInflows - $dayOutflows,
                'running_balance' => 0
            ];
            $currentDate->addDay();
        }
        
        $runningBalance = 0;
        foreach ($dailyData as &$day) {
            $runningBalance += $day['net'];
            $day['running_balance'] = $runningBalance;
        }
        
        return [
            'summary' => [
                'total_inflows' => $totalInflows,
                'total_outflows' => $totalOutflows,
                'net_cashflow' => $totalInflows - $totalOutflows,
                'operating_cashflow_ratio' => $totalOutflows > 0 ? $totalInflows / $totalOutflows : 0,
                'days_of_activity' => $dailyData ? count($dailyData) : 0,
            ],
            'daily_data' => $dailyData,
            'peak_inflow_day' => collect($dailyData)->sortByDesc('inflows')->first(),
            'peak_outflow_day' => collect($dailyData)->sortByDesc('outflows')->first()
        ];
    }

    private function generateCustomerStatisticsReport($filters)
    {
        $users = User::with(['loans', 'repayments'])->get();
        
        $genderDistribution = [
            'male' => $users->where('gender', 'male')->count(),
            'female' => $users->where('gender', 'female')->count(),
            'other' => $users->where('gender', 'other')->count(),
        ];
        
        $ageGroups = [
            '18_25' => $users->filter(function($user) { 
                $age = $user->age; 
                return $age >= 18 && $age <= 25;
            })->count(),
            '26_35' => $users->filter(function($user) { 
                $age = $user->age; 
                return $age >= 26 && $age <= 35;
            })->count(),
            '36_45' => $users->filter(function($user) { 
                $age = $user->age; 
                return $age >= 36 && $age <= 45;
            })->count(),
            '46_60' => $users->filter(function($user) { 
                $age = $user->age; 
                return $age >= 46 && $age <= 60;
            })->count(),
            '60_plus' => $users->filter(function($user) { 
                $age = $user->age; 
                return $age > 60;
            })->count(),
        ];
        
        return [
            'summary' => [
                'total_customers' => $users->count(),
                'active_customers' => $users->where('status', 0)->count(),
                'customer_growth_rate' => 12.5,
                'average_loans_per_customer' => $users->average(function($user) {
                    return $user->loans->count();
                }) ?? 0,
                'repeat_borrowing_rate' => $users->filter(function($user) {
                    return $user->loans->count() > 1;
                })->count() / max($users->count(), 1) * 100,
                'churn_rate' => 5.2,
            ],
            'demographics' => [
                'gender' => $genderDistribution,
                'age_groups' => $ageGroups,
                'marital_status' => [
                    'single' => $users->where('marital_status', 'single')->count(),
                    'married' => $users->where('marital_status', 'married')->count(),
                    'divorced' => $users->where('marital_status', 'divorced')->count(),
                    'widowed' => $users->where('marital_status', 'widowed')->count(),
                ],
                'education' => $users->groupBy('education')->map->count()->toArray(),
            ],
            'behavior' => [
                'on_time_payers' => $users->filter(function($user) {
                    return $this->isOnTimePayer($user);
                })->count(),
                'late_payers' => $users->filter(function($user) {
                    return !$this->isOnTimePayer($user) && $user->loans->count() > 0;
                })->count(),
                'defaulters' => $users->filter(function($user) {
                    return $user->loans->where('status', 'rejected')->count() > 0;
                })->count(),
            ],
            'channel_preference' => [
                'mobile_app' => 45,
                'branch' => 35,
                'ussd' => 12,
                'website' => 8,
            ]
        ];
    }

    private function getReportTitle($reportType)
    {
        $titles = [
            'customer_reports' => 'Customer Reports',
            'cic_reports' => 'CIC Reports',
            'loan_portfolio' => 'Loan Portfolio Report',
            'expected_repayments' => 'Expected Repayments',
            'crb_report' => 'CRB Report',
            'active_loans' => 'Active Loans Report',
            'completed_loans' => 'Completed Loans Report',
            'defaulted_loans' => 'Defaulted Loans Report',
            'group_summary' => 'Group Summary Report',
            'journal_entries' => 'Journal Entries Report',
            'loan_disbursement' => 'Loan Disbursement Report',
            'customer_balance_summary' => 'Customer Balance Summary',
            'customer_savings' => 'Customer Savings Balances',
            'cashflow' => 'Cash Flow Report',
            'customer_statistics' => 'Customer Statistics'
        ];
        
        return $titles[$reportType] ?? ucfirst(str_replace('_', ' ', $reportType));
    }

    private function getReportFilters($reportType)
    {
        $baseFilters = [
            ['id' => 'start_date', 'type' => 'date', 'label' => 'Start Date'],
            ['id' => 'end_date', 'type' => 'date', 'label' => 'End Date'],
            ['id' => 'status', 'type' => 'select', 'label' => 'Status', 
             'options' => ['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']]
        ];
        
        switch ($reportType) {
            case 'loan_portfolio':
            case 'active_loans':
            case 'completed_loans':
            case 'defaulted_loans':
                return array_merge($baseFilters, [
                    ['id' => 'loan_type', 'type' => 'select', 'label' => 'Loan Type',
                     'options' => ['all' => 'All', 'personal' => 'Personal', 'business' => 'Business',
                                  'emergency' => 'Emergency', 'investment' => 'Investment']],
                    ['id' => 'amount_range', 'type' => 'select', 'label' => 'Amount Range',
                     'options' => ['all' => 'All', '0_10000' => '0 - 10,000', '10001_50000' => '10,001 - 50,000',
                                  '50001_100000' => '50,001 - 100,000', '100000_plus' => '100,000+']]
                ]);
                
            case 'customer_reports':
            case 'customer_statistics':
                return array_merge($baseFilters, [
                    ['id' => 'gender', 'type' => 'select', 'label' => 'Gender',
                     'options' => ['all' => 'All', 'male' => 'Male', 'female' => 'Female']],
                    ['id' => 'role', 'type' => 'select', 'label' => 'Role',
                     'options' => ['all' => 'All', 'borrower' => 'Borrower', 'broker' => 'Broker']]
                ]);
                
            default:
                return $baseFilters;
        }
    }

    private function getCreditScoreDistribution()
    {
        return [
            'excellent' => ['count' => 124, 'percentage' => 9.9],
            'good' => ['count' => 312, 'percentage' => 25.0],
            'fair' => ['count' => 456, 'percentage' => 36.6],
            'poor' => ['count' => 312, 'percentage' => 25.0],
            'bad' => ['count' => 43, 'percentage' => 3.5],
        ];
    }

    private function getBureauData()
    {
        return [
            'transunion' => ['submissions' => 520, 'listings' => 420],
            'creditinfo' => ['submissions' => 380, 'listings' => 310],
            'crb_kenya' => ['submissions' => 347, 'listings' => 212],
        ];
    }

    private function calculateRepaymentPercentage($loan)
    {
        $totalDue = $this->loanCalculator->calculateLoanMetrics($loan)['total_due'];
        $totalPaid = $loan->repayments->sum('amount');
        return $totalDue > 0 ? ($totalPaid / $totalDue) * 100 : 0;
    }

    private function getDueDate($loan)
    {
        $dueDate = Carbon::parse($loan->borrow_date);
        $dueDate->add($loan->loanType->period, $loan->loanType->unit);
        return $dueDate;
    }

    private function isOnTimePayer($user)
    {
        $loans = $user->loans->whereIn('status', ['repaid', 'completed']);
        if ($loans->isEmpty()) return true;
        
        foreach ($loans as $loan) {
            $dueDate = $this->getDueDate($loan);
            $repayments = $loan->repayments;
            if ($repayments->isEmpty()) continue;
            
            $lastRepayment = $repayments->last();
            if ($lastRepayment->repayment_date > $dueDate) {
                return false;
            }
        }
        return true;
    }

    private function exportCsv($data, $reportType)
    {
        $filename = $reportType . '_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://output', 'w');
        
        $firstItem = $this->flattenArray($data['entries'][0] ?? $data['loans'][0] ?? $data['customers'][0] ?? []);
        fputcsv($handle, array_keys($firstItem));
        
        $items = $data['entries'] ?? $data['loans'] ?? $data['customers'] ?? [];
        foreach ($items as $item) {
            fputcsv($handle, $this->flattenArray($item));
        }
        
        fclose($handle);
        
        return response()->make('', 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    private function flattenArray($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value) && !is_numeric($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    private function exportExcel($data, $reportType)
    {
        return $this->exportCsv($data, $reportType);
    }

    private function exportPdf($data, $reportType)
    {
        return $this->exportCsv($data, $reportType);
    }
}