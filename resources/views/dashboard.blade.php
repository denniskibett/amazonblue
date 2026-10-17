{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')

<div class="grid grid-cols-12 gap-4 md:gap-6">
    


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
@include('partials.modal.flash-alert')
@endsection