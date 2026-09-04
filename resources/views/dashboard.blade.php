{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')

<div class="grid grid-cols-12 gap-4 md:gap-6">
    
    {{-- ============================================================ --}}
    {{-- BORROWER DASHBOARD - Clean & Simple                         --}}
    {{-- ============================================================ --}}
    @hasrole('borrower')
    
    {{-- Profile Completion Alert --}}
    @if(!$biodataComplete)
    <div class="col-span-12">
        <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-6 shadow-sm dark:border-yellow-800 dark:bg-yellow-900/20">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">
                        Complete Your Profile to Borrow
                    </h3>
                    <div class="mt-2">
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-yellow-700 dark:text-yellow-400 mb-1">
                                <span>Profile Completion</span>
                                <span>{{ $biodataCompletionPercentage }}%</span>
                            </div>
                            <div class="w-full bg-yellow-200 rounded-full h-2 dark:bg-yellow-800">
                                <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $biodataCompletionPercentage }}%"></div>
                            </div>
                        </div>
                        
                        <p class="text-sm text-yellow-700 dark:text-yellow-400 mb-2">
                            <strong>Missing {{ count($missingBiodataFields) }} fields:</strong>
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                            @foreach($missingBiodataFields as $field)
                            <div class="flex items-center text-sm text-yellow-700 dark:text-yellow-400">
                                <svg class="h-4 w-4 mr-2 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span>{{ ucfirst(str_replace('_', ' ', $field)) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                            Complete Profile Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Welcome Section --}}
    <div class="col-span-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">
                        👋 Welcome Back, {{ auth()->user()->name }}!
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Here's a snapshot of your loan activity
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                        @if(isset($activeLoan) && $activeLoan) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                        @else bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 @endif">
                        @if(isset($activeLoan) && $activeLoan)
                            ● Active Loan
                        @else
                            ○ No Active Loan
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="col-span-12">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {{-- Active Loans --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v1m0 1c-1.11 0-2.08-.402-2.599-1M12 16v1m0-1v-1m0 1c1.11 0 2.08-.402 2.599-1M12 4a8 8 0 100 16 8 8 0 000-16z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Active Loans</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-white/90">
                            {{ $activeLoans ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Total Borrowed --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Borrowed</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-white/90">
                            KES {{ number_format($totalBorrowed ?? 0, 2) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Repaid Loans --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Repaid Loans</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-white/90">
                            {{ $completedLoans ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Active Loan Details --}}
    @if(isset($activeLoan) && $activeLoan)
    <div class="col-span-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    📋 Your Active Loan
                </h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                    @if($activeLoan->status === 'overdue') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                    @elseif($activeLoan->status === 'disbursed' || $activeLoan->status === 'active') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 @endif">
                    {{ ucfirst($activeLoan->status) }}
                </span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Loan ID</p>
                    <p class="font-semibold text-gray-800 dark:text-white/90">
                        #{{ str_pad($activeLoan->id, 5, '0', STR_PAD_LEFT) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Amount</p>
                    <p class="font-semibold text-gray-800 dark:text-white/90">
                        KES {{ number_format($activeLoan->amount, 2) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Interest Rate</p>
                    <p class="font-semibold text-gray-800 dark:text-white/90">
                        {{ $activeLoan->loanType->interest_rate ?? 10 }}%
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Due Date</p>
                    <p class="font-semibold text-gray-800 dark:text-white/90">
                        {{ $activeLoan->due_date ? \Carbon\Carbon::parse($activeLoan->due_date)->format('M d, Y') : 'N/A' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Outstanding Balance</p>
                    <p class="font-semibold text-red-600 dark:text-red-400">
                        KES {{ number_format($activeLoan->outstanding_balance ?? $activeLoan->amount, 2) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Cycle</p>
                    <p class="font-semibold text-gray-800 dark:text-white/90">
                        #{{ $activeLoan->cycle ?? 1 }}
                    </p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('loans.show', $activeLoan->id) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    View Details
                </a>
                <a href="{{ route('repayments.create', ['loan_id' => $activeLoan->id]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    Make Payment
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Quick Actions --}}
    <div class="col-span-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-4">
                🚀 Quick Actions
            </h3>
            <div class="flex flex-wrap gap-3">
                {{-- Borrow Button - Only show if borrower has no active loan or can apply --}}
                @if(!isset($activeLoan) || !$activeLoan || $activeLoan->status === 'repaid')
                <a href="{{ route('loans.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Apply for New Loan
                </a>
                @else
                <button disabled 
                   class="inline-flex items-center px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Active Loan Exists
                </button>
                @endif
                
                <a href="{{ route('loans.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    View All Loans
                </a>
                
                <a href="{{ route('profile.show') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    View Profile
                </a>
            </div>
        </div>
    </div>

    {{-- Recent Activity --}}
    @if(isset($recentTransactions) && $recentTransactions->isNotEmpty())
    <div class="col-span-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-4">
                📅 Recent Activity
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Type</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($recentTransactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($transaction->date)->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-white/90">
                                {{ $transaction->type }}
                            </td>
                            <td class="px-4 py-2 text-right font-medium text-gray-800 dark:text-white/90">
                                KES {{ number_format($transaction->amount, 2) }}
                            </td>
                            <td class="px-4 py-2">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                    @if($transaction->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                    @elseif($transaction->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                    {{ ucfirst($transaction->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Borrower Recovery Cases --}}
    @if(isset($hasActiveRecovery) && $hasActiveRecovery)
    <div class="col-span-12">
        <div class="rounded-2xl border border-red-200 bg-red-50 p-6 dark:border-red-800 dark:bg-red-900/20">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-red-800 dark:text-red-300">
                        ⚠️ Active Recovery Case
                    </h3>
                    <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                        You have {{ $activeRecoveryCount }} active recovery case(s). Please contact support for assistance.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('cases.my') }}" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            View Recovery Cases
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ============================================================ --}}
    {{-- END BORROWER DASHBOARD                                        --}}
    {{-- ============================================================ --}}
    
    @endhasrole


    {{-- ============================================================ --}}
    {{-- ADMIN DASHBOARD                                              --}}
    {{-- ============================================================ --}}
    @hasrole('admin')
    
    {{-- Admin dashboard content --}}
    <div class="col-span-12 space-y-12 xl:col-span-12">
        @include('partials.metric-group.metric-group-01', [
            'totalLoans' => $totalLoans ?? 0,
            'loansThisMonth' => $loansThisMonth ?? 0,
            'completedLoans' => $completedLoans ?? 0,
            'completedThisMonth' => $completedThisMonth ?? 0,
            'totalDisbursements' => $totalDisbursements ?? 0,
            'disbursementsThisMonth' => $disbursementsThisMonth ?? 0,
            'totalRepayments' => $totalRepayments ?? 0,
            'repaymentsThisMonth' => $repaymentsThisMonth ?? 0,
            'borrowerCount' => $borrowerCount ?? 0,
            'newBorrowersThisMonth' => $newBorrowersThisMonth ?? 0,
            'brokerCount' => $brokerCount ?? 0,
            'tellerCount' => $tellerCount ?? 0,
        ])
    </div>

    <div class="col-span-12">
        @include('partials.chart.monthly-loans', [
            'monthlyData' => $monthlyData ?? []
        ])
    </div>

    <div class="col-span-12 xl:col-span-12">
        @include('partials.table.table-due-loans', ['dueLoans' => $dueLoans ?? collect()])
    </div>

    {{-- Admin recovery section --}}
    <div class="col-span-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Admin Dashboard</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Admin overview and system statistics
            </p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Recovery</p>
                    <p class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $activeRecoveryCases ?? 0 }}</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Recovery Debt</p>
                    <p class="text-xl font-bold text-gray-800 dark:text-white/90">KES {{ number_format($totalRecoveryDebt ?? 0, 2) }}</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Recovery Rate</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $recoveryRate ?? 0 }}%</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <p class="text-sm text-gray-500 dark:text-gray-400">NPL Loans</p>
                    <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ $nplCount ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>
    
    @endhasrole


    {{-- ============================================================ --}}
    {{-- TELLER DASHBOARD                                             --}}
    {{-- ============================================================ --}}
    @hasrole('teller')
    
    <div class="col-span-12">
        @include('partials.top-card-group', [
            'todaysDisbursements' => $todaysDisbursements ?? 0,
            'monthDisbursements' => $monthDisbursements ?? 0,
            'collectedRepayments' => $collectedRepayments ?? 0,
            'monthRepayments' => $monthRepayments ?? 0
        ])
    </div>

    <div class="col-span-12 xl:col-span-12">
        @include('partials.table.table-due-loans', ['dueLoans' => $dueLoans ?? collect()])
    </div>
    
    @endhasrole


    {{-- ============================================================ --}}
    {{-- BROKER DASHBOARD                                             --}}
    {{-- ============================================================ --}}
    @hasrole('broker')
    
    <div class="col-span-12 space-y-12 xl:col-span-12">
        @include('partials.metric-group.metric-group-01', [
            'totalLoans' => $totalLoans ?? 0,
            'loansThisMonth' => $loansThisMonth ?? 0,
            'completedLoans' => $completedLoans ?? 0,
            'completedThisMonth' => $completedThisMonth ?? 0,
            'totalDisbursements' => $totalDisbursements ?? 0,
            'disbursementsThisMonth' => $disbursementsThisMonth ?? 0,
            'totalRepayments' => $totalRepayments ?? 0,
            'repaymentsThisMonth' => $repaymentsThisMonth ?? 0,
            'borrowerCount' => $borrowerCount ?? 0,
            'newBorrowersThisMonth' => $newBorrowersThisMonth ?? 0,
            'brokerCount' => $brokerCount ?? 0,
            'tellerCount' => $tellerCount ?? 0,
            'totalBorrowed' => $totalBorrowed ?? 0,
            'borrowedThisMonth' => $borrowedThisMonth ?? 0,
            'broker' => $broker ?? null,
            'clients' => $clients ?? 0,
            'newClientsThisMonth' => $newClientsThisMonth ?? 0,
            'activeLoans' => $activeLoans ?? 0,
            'totalInterest' => $totalInterest ?? 0,
            'totalPenalty' => $totalPenalty ?? 0,
            'overdueLoans' => $overdueLoans ?? collect(),
        ])
    </div>

    <div class="col-span-12">
        @include('partials.chart.monthly-loans', [
            'monthlyData' => $monthlyData ?? []
        ])
    </div>

    <div class="col-span-12 xl:col-span-12">
        @include('partials.table.table-due-loans', ['dueLoans' => $dueLoans ?? collect()])
    </div>

    {{-- Broker-specific section --}}
    <div class="col-span-12">
        @include('partials.media-card', [
            'broker' => $broker ?? null,
            'clients' => $clients ?? 0,
            'activeLoans' => $activeLoans ?? 0
        ])
    </div>
    
    @endhasrole

</div>
@endsection