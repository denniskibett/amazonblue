@extends('layouts.app')

@section('content')

    @include('partials.metric-group.metric-group-02', ['stats' => $stats])

    {{-- @include('partials.chart.chart-03') --}}

    {{-- @include('partials.chart.requested-vs-disbursed', ['stats' => $stats, 'loanLists' => $loanLists]) --}}


    {{-- @include('partials.chart.loan-funnel', ['stats' => $stats]) --}}
    {{-- @include('partials.chart.loan-status', ['stats' => $stats]) --}}

    {{-- @include('partials.chart.repayments-trend', ['stats' => $stats])
    @include('partials.chart.active-vs-repaid', ['stats' => $stats])
    @include('partials.chart.loan-durations', ['stats' => $stats])
    @include('partials.chart.average-days-late', ['stats' => $stats])
    @include('partials.chart.interest-penalties-brokerfees', ['stats' => $stats])
    @include('partials.chart.net-earnings', ['stats' => $stats])
    @include('partials.chart.outstanding-at-due', ['stats' => $stats])
    @include('partials.chart.days-late', ['stats' => $stats])
    @include('partials.chart.average-repayment-period', ['stats' => $stats])
    @include('partials.chart.3layer-funnel', ['stats' => $stats]) --}}


    @php
        $loansToDisplay = [];
        if (auth()->user()->hasAnyRole(\App\Models\User::BACK_OFFICE_ROLES)) {
            $loansToDisplay = $allLoans ?? [];
        } elseif (auth()->user()->hasRole('broker')) {
            $loansToDisplay = $brokerLoans ?? [];
        } elseif (auth()->user()->hasRole('teller')) {
            $loansToDisplay = $activeLoans ?? [];
        } else {
            $loansToDisplay = $userLoans ?? [];
        }
    @endphp

    @include('partials.table.table-loans', [
        'loans' => $loansToDisplay,
        'showUserColumn' => auth()->user()->hasAnyRole([...\App\Models\User::BACK_OFFICE_ROLES, 'broker', 'teller']),
        'showCreateButton' => auth()->user()->can('create loans'),
        'context' => 'loans-index',
        
    ])

    

@endsection