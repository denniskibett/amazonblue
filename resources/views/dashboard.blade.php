@extends('layouts.app')

@section('content')
    <div class="grid grid-cols-12 gap-4 md:gap-6">
        <!-- Profile Completion Alert for Borrowers -->
        @if(auth()->user()->role === 'borrower' && !$biodataComplete)
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
                            
                            <p class="text-sm text-yellow-700 dark:text-yellow-400 mb-3">
                                You need to complete your bio data before you can apply for loans. Missing fields:
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($missingBiodataFields as $field)
                                <div class="flex items-center text-sm text-yellow-700 dark:text-yellow-400">
                                    <svg class="h-4 w-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    {{ $field }}
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

        <!-- Profile Completion Success for Borrowers -->
        @if(auth()->user()->role === 'borrower' && $biodataComplete)
        <div class="col-span-12">
            <div class="rounded-2xl border border-green-200 bg-green-50 p-6 shadow-sm dark:border-green-800 dark:bg-green-900/20">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-green-800 dark:text-green-300">
                            Profile Complete - Ready to Borrow!
                        </h3>
                        <p class="text-sm text-green-700 dark:text-green-400 mt-1">
                            Your profile is 100% complete. You can now apply for loans.
                        </p>
                        <div class="mt-4 flex gap-3">
                            <a href="{{ route('loans.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                Apply for Loan
                            </a>
                            <a href="{{ route('profile.show') }}" class="inline-flex items-center px-4 py-2 border border-green-600 text-green-600 text-sm font-medium rounded-lg hover:bg-green-50 dark:hover:bg-green-900/30 transition-colors">
                                View Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="col-span-12 space-y-12 xl:col-span-12">

          @include('partials.metric-group.metric-group-01', [
              // Common metrics
              'totalLoans' => $totalLoans ?? 0 ,
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

              // Additional borrower-specific
              'totalBorrowed' => $totalBorrowed ?? 0,
              'borrowedThisMonth' => $borrowedThisMonth ?? 0,

              // Broker-specific
              'broker' => $broker ?? null,
              'clients' => $clients ?? 0,
              'newClientsThisMonth' => $newClientsThisMonth ?? 0,
              'activeLoans' => $activeLoans ?? 0,
              'totalInterest' => $totalInterest ?? 0,
              'totalPenalty' => $totalPenalty ?? 0,
              'overdueLoans' => $overdueLoans ?? collect(),

              // Teller-specific
              'todaysDisbursements' => $todaysDisbursements ?? 0,
              'monthDisbursements' => $monthDisbursements ?? 0,
              'collectedRepayments' => $collectedRepayments ?? 0,
              'monthRepayments' => $monthRepayments ?? 0,

              // Shared lists
              'recentLoans' => $recentLoans ?? collect(),
              'todayTransactions' => $todayTransactions ?? 0,
              'loanStatusData' => $loanStatusData ?? [],
              'disbursementTrends' => $disbursementTrends ?? [],
              'dueLoans' => $dueLoans ?? collect(),

              // New fields for profile completion
              'biodataComplete' => $biodataComplete ?? false,
              'biodataCompletionPercentage' => $biodataCompletionPercentage ?? 0,
              'missingBiodataFields' => $missingBiodataFields ?? [],
          ])

        </div>

        <div class="col-span-12">
            @include('partials.chart.monthly-loans', [
                'monthlyData' => $monthlyData ?? []
            ])
        </div>

        <div class="col-span-12 xl:col-span-12">
            @include('partials.table.table-01', ['dueLoans' => $dueLoans ?? collect()])
        </div>

        <!-- Add Table  -->
        
        {{-- 
        <div class="col-span-12">
            @include('partials.chart.chart-03', [
                'disbursementTrends' => $disbursementTrends ?? []
            ])
        </div> --}}

        {{-- <div class="col-span-12 xl:col-span-5">
            @include('partials.map-01')
        </div>

        <div class="col-span-12 xl:col-span-7">
            @include('partials.table.table-01', ['dueLoans' => $dueLoans])
        </div> --}}

        @if(auth()->user()->role === 'broker')
            <div class="col-span-12">
                @include('partials.media-card', [
                    'broker' => $broker ?? null,
                    'clients' => $clients ?? 0,
                    'activeLoans' => $activeLoans ?? 0
                ])
            </div>
        @endif

        @if(auth()->user()->role === 'teller')
            <div class="col-span-12">
                @include('partials.top-card-group', [
                    'todaysDisbursements' => $todaysDisbursements ?? 0,
                    'monthDisbursements' => $monthDisbursements ?? 0,
                    'collectedRepayments' => $collectedRepayments ?? 0,
                    'monthRepayments' => $monthRepayments ?? 0
                ])
            </div>
        @endif

        {{-- <div class="col-span-12">
            @include('partials.watchlist')
        </div>
        <div class="col-span-12">
            @include('partials.upcoming-schedule')
        </div> --}}
    </div>
@endsection