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
use App\Services\CustomerWallets;
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

    /** Stage 4 Phase 7: under engine v2 reports read the cycles and cash, not loans.amount. */
    private function v2(): bool
    {
        return \App\Services\LoanCalculator::v2();
    }

    private ?\App\Services\LedgerReports $reports = null;

    private function rep(): \App\Services\LedgerReports
    {
        return $this->reports ??= app(\App\Services\LedgerReports::class);
    }

    private function customerBalance($user): float
    {
        return $this->v2() ? (float) $user->loans->sum(fn ($l) => $this->rep()->owed($l))
            : (float) ($user->loans->sum('amount') - $user->repayments->sum('amount'));
    }

    public function index()
    {
        $reports = $this->getAvailableReports();
        try {
            $analytics = \App\Support\ReportAnalytics::get();
        } catch (\Throwable $e) {
            report($e);
            $analytics = null;
        }

        return view('reports.index', compact('reports', 'analytics'));
    }

    public function show($reportType)
    {
        if (!in_array($reportType, array_column($this->getAvailableReports(), 'id'), true)) {
            abort(404, 'Report not found');
        }

        // Generate report data (date filters from the query string, e.g. ?start_date=2026-01-01&end_date=2026-01-31)
        $reportData = $this->generateReport($reportType, array_filter(request()->only(['start_date', 'end_date'])));
        $filters = $this->getReportFilters($reportType);
        
        // Determine available charts for this report type
        $availableCharts = $this->getAvailableChartsForReport($reportType);
        
        return view('reports.show', [
            'reportType' => $reportType,
            'reportData' => $reportData,
            'filters' => $filters,
            'reportTitle' => $this->getReportTitle($reportType),
            'availableCharts' => $availableCharts,
            'defaultChart' => $availableCharts[0]['value'] ?? 'default'
        ]);
    }

    public function export(Request $request)
    {
        $reportType = $request->input('report_type');
        $format = $request->input('format', 'csv');
        $filters = array_filter((array) $request->input('filters', []), 'is_scalar');
        if (!in_array($reportType, array_column($this->getAvailableReports(), 'id'), true)) {
            abort(404, 'Report not found');
        }
        
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

    private function getAvailableChartsForReport($reportType)
    {
        $charts = [
            'customer_reports' => [
                ['label' => 'Customer Balance', 'value' => 'customer-balance'],
                ['label' => 'Role Distribution', 'value' => 'role-distribution']
            ],
            'customer_balance_summary' => [
                ['label' => 'Outstanding Balance', 'value' => 'outstanding-balance'],
                ['label' => 'Repayment %', 'value' => 'repayment-percentage']
            ],
            'customer_statistics' => [
                ['label' => 'Gender Distribution', 'value' => 'gender-distribution'],
                ['label' => 'Age Distribution', 'value' => 'age-distribution'],
                ['label' => 'Behavior', 'value' => 'customer-behavior']
            ],
            'customer_savings' => [
                ['label' => 'Savings Balance', 'value' => 'savings-balance']
            ],
            'loan_portfolio' => [
                ['label' => 'Status Distribution', 'value' => 'status-distribution'],
                ['label' => 'By Loan Type', 'value' => 'loan-type-distribution'],
                ['label' => 'By Size', 'value' => 'loan-size-distribution']
            ],
            'active_loans' => [
                ['label' => 'Amount vs Balance', 'value' => 'amount-vs-balance'],
                ['label' => 'Repayment %', 'value' => 'active-repayment-percentage']
            ],
            'completed_loans' => [
                ['label' => 'Principal vs Interest', 'value' => 'principal-vs-interest'],
                ['label' => 'Duration', 'value' => 'duration']
            ],
            'defaulted_loans' => [
                ['label' => 'Default Breakdown', 'value' => 'default-breakdown'],
                ['label' => 'Recovery Status', 'value' => 'recovery-status']
            ],
            'expected_repayments' => [
                ['label' => 'Expected Repayments', 'value' => 'expected-repayments'],
                ['label' => 'Status Breakdown', 'value' => 'repayment-status-breakdown']
            ],
            'loan_disbursement' => [
                ['label' => 'Disbursement Amounts', 'value' => 'disbursement-amounts'],
                ['label' => 'By Channel', 'value' => 'disbursement-channel']
            ],
            'group_summary' => [
                ['label' => 'Loan Status', 'value' => 'group-loan-status'],
                ['label' => 'Financial Summary', 'value' => 'financial-summary'],
                ['label' => 'Performance Metrics', 'value' => 'performance-metrics']
            ],
            'cashflow' => [
                ['label' => 'Cashflow Trend', 'value' => 'cashflow-trend'],
                ['label' => 'Running Balance', 'value' => 'running-balance']
            ],
            'journal_entries' => [
                ['label' => 'Journal Entries', 'value' => 'journal-entries']
            ],
            'cic_reports' => [
                ['label' => 'Credit Score', 'value' => 'credit-score-distribution'],
                ['label' => 'Bureau Listings', 'value' => 'bureau-listings']
            ],
            'crb_report' => [
                ['label' => 'Default Amounts', 'value' => 'default-amounts'],
                ['label' => 'Default vs Cleared', 'value' => 'default-vs-cleared']
            ]
        ];

        return $charts[$reportType] ?? [
            ['label' => 'Default', 'value' => 'default']
        ];
    }

    // ============================================================
    // REPORT GENERATION METHODS (LIGHTWEIGHT - JUST DATA)
    // ============================================================

    private function generateCustomerReport($filters)
    {
        $users = User::with(['roles', 'borrower', 'loans', 'repayments'])->get();
        
        return [
            'summary' => [
                'total_customers' => $users->count(),
                'active_customers' => $users->where('status', 0)->count(),
                'borrowers' => $users->filter(fn ($u) => $u->hasRole('borrower'))->count(),
                'brokers' => $users->filter(fn ($u) => $u->hasRole('broker'))->count(),
            ],
            'customers' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->roles->pluck('name')->implode(', '),
                    'status' => $user->status ? 'Active' : 'Inactive',
                    'gender' => $user->gender,
                    'dob' => $user->dob,
                    'nationality' => $user->nationality,
                    'marital_status' => $user->marital_status,
                    'education' => $user->education,
                    'total_loans' => $user->loans->count(),
                    // v2 (Stage 4 Phase 7): cash lent and repaid, and what is owed today; never loans.amount
                    'total_borrowed' => $this->v2() ? $user->loans->sum(fn ($l) => $this->rep()->lent($l->id)) : $user->loans->sum('amount'),
                    'total_repaid' => $this->v2() ? $user->loans->sum(fn ($l) => $this->rep()->repaid($l->id)) : $user->repayments->sum('amount'),
                    'balance' => $this->v2() ? $user->loans->sum(fn ($l) => $this->rep()->owed($l)) : $user->loans->sum('amount') - $user->repayments->sum('amount'),
                    'completion_percentage' => $user->getBiodataCompletionPercentage()
                ];
            })
        ];
    }

    private function generateCicReport($filters)
    {
        return [
            'summary' => [
                'total_submissions' => Loan::count(),
                'active_listings' => Loan::whereIn('status', ['disbursed', 'approved'])->count(),
                'default_listings' => Loan::whereIn('status', ['defaulted', 'recovery', 'written_off'])->count(),
                'cleared_listings' => Loan::where('status', 'repaid')->count(),
            ],
            // From the system's own 4Cs risk assessments; empty until assessments exist
            'credit_scores' => $this->getCreditScoreDistribution(),
            // No CRB/CIC submission or dispute records exist in the system yet: nothing to report
            'cic_compliance' => [],
            'bureau_data' => $this->getBureauData(),
            'not_available' => ['CIC compliance and dispute figures', 'Credit bureau submissions (TransUnion, Creditinfo, CRB)'],
        ];
    }

    private function generateLoanPortfolioReport($filters)
    {
        $loans = Loan::with(['loanType', 'user', 'repayments', 'disbursements'])->get();
        
        return [
            'summary' => [
                'total_loans' => $loans->count(),
                'total_portfolio_value' => $this->v2() ? $loans->sum(fn ($l) => $this->rep()->owed($l)) : $loans->sum('amount'),
                'average_loan_size' => $this->v2() ? ($loans->average(fn ($l) => (float) ($l->original_amount ?? $l->amount)) ?? 0) : ($loans->average('amount') ?? 0),
                'average_interest_rate' => $loans->average('loanType.interest_rate') ?? 0,
                'performing_loans' => $loans->whereIn('status', ['approved', 'disbursed'])->count(),
                'non_performing_loans' => $loans->filter(fn ($l) => $l->is_non_performing || in_array($l->status, ['defaulted', 'recovery', 'written_off']))->count(),
            ],
            'status_breakdown' => [
                'pending' => $loans->where('status', 'pending')->count(),
                'approved' => $loans->where('status', 'approved')->count(),
                'disbursed' => $loans->where('status', 'disbursed')->count(),
                'rejected' => $loans->where('status', 'rejected')->count(),
                'repaid' => $loans->where('status', 'repaid')->count(),
                'written_off' => $loans->where('status', 'written_off')->count(),
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
    }

    private function generateActiveLoansReport($filters)
    {
        $loans = Loan::with(['user', 'loanType', 'repayments', 'disbursements'])
            ->whereIn('status', ['approved', 'disbursed'])
            ->get();
        
        return [
            'summary' => [
                'total_active' => $loans->count(),
                'total_portfolio' => $this->v2() ? $loans->sum(fn ($l) => $this->rep()->owed($l)) : $loans->sum('amount'),
                'overdue_loans' => $loans->filter(function ($loan) {
                    $dueDate = Carbon::parse($loan->borrow_date);
                    $dueDate->add($loan->loanType->period, $loan->loanType->unit);
                    return $dueDate->isPast();
                })->count(),
                'average_balance' => $this->v2() ? ($loans->average(fn ($l) => $this->rep()->owed($l)) ?? 0) : ($loans->average('amount') ?? 0),
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
                'total_amount_repaid' => $this->v2() ? $loans->sum(fn ($l) => $this->rep()->repaid($l->id)) : $loans->sum('amount'),
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
        // v2: defaulted means defaulted, in recovery or written off (it listed rejected applications before)
        $loans = Loan::with(['user', 'loanType', 'repayments'])
            ->whereIn('status', $this->v2() ? ['defaulted', 'recovery', 'written_off'] : ['rejected'])
            ->get();
        
        return [
            'summary' => [
                'total_defaulted' => $loans->count(),
                'total_defaulted_amount' => $this->v2() ? $loans->sum(fn ($l) => $this->rep()->owed($l) + $this->rep()->repaid($l->id)) : $loans->sum('amount'),
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
                    'remaining_balance' => $this->v2() ? $this->rep()->owed($loan) : $loan->amount - $loan->repayments->sum('amount'),
                    'default_reason' => $loan->admin_notes ?? 'Unknown',
                    'recovery_status' => $loan->repayments->sum('amount') > 0 ? 'Partial Recovery' : 'No Recovery'
                ];
            })
        ];
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
        $defaultedLoans = Loan::with(['user', 'loanType'])
            ->where('status', 'rejected')
            ->get();
            
        $repaidLoans = Loan::with(['user'])
            ->where('status', 'repaid')
            ->get();
        
        return [
            'summary' => [
                'total_defaults' => $defaultedLoans->count(),
                'total_default_amount' => $this->v2() ? $defaultedLoans->sum(fn ($l) => $this->rep()->owed($l)) : $defaultedLoans->sum('amount'),
                'total_cleared' => $repaidLoans->count(),
                'total_cleared_amount' => $this->v2() ? $repaidLoans->sum(fn ($l) => $this->rep()->repaid($l->id)) : $repaidLoans->sum('amount'),
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

    private function generateGroupSummaryReport($filters)
    {
        $loans = Loan::with(['loanType', 'user', 'repayments', 'disbursements'])->get();
        
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
                // money actually sent: rollover / carry-forward entries are not cash; net of fees when recorded
                'real_cash_disbursed' => (float) CustomerWallets::whereCash(Disbursement::query())
                    ->whereBetween('disburse_date', [$startDate, $endDate])
                    ->sum(DB::raw(CustomerWallets::DISBURSED_CASH_SQL)),
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
                'total_outstanding' => $users->sum(fn ($user) => $this->customerBalance($user)),
                'customers_with_balance' => $users->filter(fn ($user) => $this->customerBalance($user) > 0)->count(),
                'customers_zero_balance' => $users->filter(fn ($user) => $this->customerBalance($user) <= 0)->count(),
                // recorded repayments include rollovers, discounts and write-offs; real cash does not
                'recorded_repayments' => (float) Repayment::sum('amount'),
                'real_cash_repaid' => (float) CustomerWallets::whereCash(Repayment::query())->sum('amount'),
                'real_cash_disbursed' => (float) CustomerWallets::whereCash(Disbursement::query())->sum(DB::raw(CustomerWallets::DISBURSED_CASH_SQL)),
            ],
            'customers' => $users->map(function ($user) {
                $totalLoans = $this->v2() ? $user->loans->sum(fn ($l) => $this->rep()->lent($l->id)) : $user->loans->sum('amount');
                $totalRepaid = $this->v2() ? $user->loans->sum(fn ($l) => $this->rep()->repaid($l->id)) : $user->repayments->sum('amount');
                $balance = $this->customerBalance($user);
                
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
        // The system has no savings product or savings ledger, so there is nothing real to show.
        // (This report used to show 10% of each customer's loans with a random "last activity" date.)
        return [
            'summary' => [
                'savings' => 'Not available yet',
            ],
            'savings' => [],
            'not_available' => ['Customer savings: no savings accounts are recorded in the system'],
        ];
    }

    private function generateCashflowReport($filters)
    {
        $startDate = Carbon::parse($filters['start_date'] ?? Carbon::now()->startOfMonth())->startOfDay();
        $endDate = Carbon::parse($filters['end_date'] ?? Carbon::now()->endOfMonth())->endOfDay();

        // Inflows = money received from borrowers (repayments); outflows = money sent (disbursements).
        // Recorded totals include rollovers, discounts and write-offs; "real cash" excludes them
        // (CustomerWallets cash rules) and counts disbursements net of fees when recorded.
        $repayments = Repayment::query()->whereBetween('repayment_date', [$startDate->toDateString(), $endDate->toDateString()]);
        $disbursements = Disbursement::query()->whereBetween('disburse_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $recordedInflows = (float) (clone $repayments)->sum('amount');
        $recordedOutflows = (float) (clone $disbursements)->sum('amount');

        $cashIn = CustomerWallets::whereCash(clone $repayments)
            ->selectRaw('DATE(repayment_date) as d, SUM(amount) as total')->groupBy('d')->pluck('total', 'd');
        $cashOut = CustomerWallets::whereCash(clone $disbursements)
            ->selectRaw('DATE(disburse_date) as d, SUM(' . CustomerWallets::DISBURSED_CASH_SQL . ') as total')->groupBy('d')->pluck('total', 'd');

        $dailyData = [];
        $runningBalance = 0;
        for ($day = $startDate->copy(); $day <= $endDate; $day->addDay()) {
            $key = $day->format('Y-m-d');
            $in = (float) ($cashIn[$key] ?? 0);
            $out = (float) ($cashOut[$key] ?? 0);
            $runningBalance += $in - $out;
            $dailyData[] = [
                'date' => $key,
                'inflows' => $in,
                'outflows' => $out,
                'net' => $in - $out,
                'running_balance' => $runningBalance,
            ];
        }

        $totalInflows = (float) $cashIn->sum();
        $totalOutflows = (float) $cashOut->sum();
        $activeDays = collect($dailyData)->filter(fn ($d) => $d['inflows'] > 0 || $d['outflows'] > 0);

        return [
            'summary' => [
                'real_cash_inflows' => $totalInflows,
                'real_cash_outflows' => $totalOutflows,
                'net_cashflow' => $totalInflows - $totalOutflows,
                'recorded_repayments' => $recordedInflows,
                'recorded_disbursements' => $recordedOutflows,
                'days_of_activity' => $activeDays->count(),
            ],
            'daily_data' => $dailyData,
            'peak_inflow_day' => collect($dailyData)->sortByDesc('inflows')->first(),
            'peak_outflow_day' => collect($dailyData)->sortByDesc('outflows')->first()
        ];
    }

    private function generateCustomerStatisticsReport($filters)
    {
        $users = User::with(['loans', 'repayments'])->get();
        
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
                'gender' => [
                    'male' => $users->where('gender', 'male')->count(),
                    'female' => $users->where('gender', 'female')->count(),
                    'other' => $users->where('gender', 'other')->count(),
                ],
                'age_groups' => [
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
                ],
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

    /**
     * Risk category distribution from the system's own loan risk assessments (latest per loan).
     * Empty when there are no assessments: the view then says "not available yet".
     */
    private function getCreditScoreDistribution()
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('loan_risk_assessments', 'risk_category')) {
                return [];
            }
            $latest = DB::table('loan_risk_assessments')->selectRaw('MAX(id) as id')->groupBy('loan_id');
            $rows = DB::table('loan_risk_assessments')
                ->whereIn('id', $latest)
                ->whereNotNull('risk_category')
                ->selectRaw('risk_category, COUNT(*) as c')
                ->groupBy('risk_category')
                ->pluck('c', 'risk_category');
            $total = $rows->sum();

            return $rows->map(fn ($c) => [
                'count' => (int) $c,
                'percentage' => $total > 0 ? round($c / $total * 100, 1) : 0,
            ])->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Credit bureau submissions: the system has no CRB integration or submission records yet.
     */
    private function getBureauData()
    {
        return [];
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
        $reportType = preg_replace('/[^a-z0-9_]/i', '', (string) $reportType) ?: 'report';
        $filename = $reportType . '_' . date('Y-m-d') . '.csv';

        // The report's main list: the first entry that is a list of rows (loans, customers, daily_data, ...)
        $rows = [];
        foreach ((array) $data as $key => $value) {
            if ($key === 'summary') {
                continue;
            }
            $value = $value instanceof \Illuminate\Support\Collection ? $value->all() : $value;
            if (is_array($value) && $value && array_is_list($value) && is_array(reset($value))) {
                $rows = array_map(fn ($row) => $this->flattenArray($row), $value);
                break;
            }
        }
        // No list (or an error): export the summary as key/value rows instead
        if (!$rows) {
            $rows = [];
            foreach ($this->flattenArray((array) ($data['summary'] ?? $data)) as $k => $v) {
                $rows[] = ['item' => $k, 'value' => $v];
            }
        }

        $headers = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $h) {
                $headers[$h] = true;
            }
        }
        $headers = array_keys($headers);

        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($h) => is_bool($row[$h] ?? null) ? (int) $row[$h] : ($row[$h] ?? ''), $headers));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function flattenArray($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            if ($value instanceof \Illuminate\Support\Collection) {
                $value = $value->all();
            }
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }
            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }
            if (is_array($value)) {
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