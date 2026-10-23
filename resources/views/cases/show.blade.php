@extends('layouts.app')

@section('content')
@php
    // Plain array with only the fields the page script reads. Never @js() the Eloquent model:
    // its user.borrower accessors run credit scoring per field and exhaust memory, and full user
    // rows carry KYC data.
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('M d, Y') : null;
    $caseUser = $caseData->user;
    $caseLoanRow = $caseData->loan;
    $caseJs = [
        'id' => $caseData->id,
        'case_number' => $caseData->case_number,
        'user_id' => $caseData->user_id,
        'user_name' => $caseUser->name ?? null,
        'user_email' => $caseUser->email ?? null,
        'user_phone' => $caseUser->phone ?? null,
        'user_initials' => $caseUser ? collect(explode(' ', trim($caseUser->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') : '?',
        'client_type' => $caseUser?->borrower?->getRawOriginal('client_type'),
        'loan_id' => $caseData->loan_id,
        'loan_amount' => $caseLoanRow ? (float) $caseLoanRow->amount : null,
        'loan_status' => $caseLoanRow->status ?? null,
        'loan_type' => $caseLoanRow?->loanType?->name,
        'is_non_performing' => (bool) ($caseLoanRow->is_non_performing ?? false),
        'days_overdue' => $caseLoanRow->days_overdue ?? 0,
        'days_in_default' => $caseData->days_in_default,
        'total_debt_amount' => (float) $caseData->total_debt_amount,
        'remaining_balance' => (float) $caseData->remaining_balance,
        'recovery_progress' => $caseData->recovery_progress,
        'total_recovered' => (float) $caseData->actions->whereNotNull('repayment_id')->sum('amount_collected'),
        'default_date_formatted' => $fmtDate($caseData->default_date),
        'last_contact_date_formatted' => $fmtDate($caseData->last_contact_date),
        'next_action_date_formatted' => $fmtDate($caseData->next_action_date),
        'assigned_to_name' => $caseData->assignedTo->name ?? null,
        'recovery_officer' => $caseData->assignedTo->name ?? null,
        'recovery_strategy' => $caseData->recovery_strategy,
        'notes' => $caseData->getAttribute('notes'),
        'actions_count' => $caseData->actions->count(),
        'created_at_diff' => optional($caseData->created_at)->diffForHumans(),
        'updated_at_diff' => optional($caseData->updated_at)->diffForHumans(),
        'payment_plans' => collect($caseData->paymentPlans ?? [])->map(fn ($p) => [
            'id' => data_get($p, 'id'),
            'installment_frequency' => data_get($p, 'installment_frequency'),
            'status' => (string) data_get($p, 'status'),
            'installment_amount' => data_get($p, 'installment_amount'),
            'number_of_installments' => data_get($p, 'number_of_installments'),
            'total_amount' => data_get($p, 'total_amount'),
            'progress_percentage' => data_get($p, 'progress_percentage', 0),
            'remaining_balance' => data_get($p, 'remaining_balance', 0),
        ])->values(),
    ];
@endphp
<div class="container mx-auto px-4 py-6"
     x-data="caseShow({{ \Illuminate\Support\Js::from($caseJs) }})">
    
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                    <svg class="w-3 h-3 mr-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 6 10">
                        <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                    </svg>
                    Home
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-3 h-3 mx-1 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <a href="{{ route('cases.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">Recovery Cases</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-3 h-3 mx-1 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400" x-text="rc.case_number"></span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Case Header -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="rc.case_number"></h1>
                    
                    <!-- Status Badge -->
                    @php
                        $statusColors = [
                            'open' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                            'in_progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                            'negotiation' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                            'legal' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                            'recovered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                            'written_off' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            'closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        ];
                        $statusSlug = $caseData->status->slug ?? 'open';
                        $statusClass = $statusColors[$statusSlug] ?? $statusColors['open'];
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                        {{ $caseData->status->name ?? 'Open' }}
                    </span>

                    <!-- Priority Badge -->
                    @php
                        $priorityColors = [
                            'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                            'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                            'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                            'low' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                        ];
                        $prioritySlug = $caseData->priority->slug ?? 'medium';
                        $priorityClass = $priorityColors[$prioritySlug] ?? $priorityColors['medium'];
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $priorityClass }}">
                        {{ $caseData->priority->name ?? 'Medium' }}
                    </span>

                    @if($caseData->loan && $caseData->loan->is_non_performing)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                            NPL Loan
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Created <span x-text="rc.created_at_diff"></span> • 
                    Last updated <span x-text="rc.updated_at_diff"></span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->can('edit recovery cases'))
                <button @click="openEditCase()" 
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    Edit Case
                </button>
                @endif
                <a href="{{ route('cases.index') }}" 
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </div>

    <!-- Stats as the standard cards -->
    @php $cj = (array) $caseJs; @endphp
    <x-ui.metrics :items="[
      ['label' => 'Total debt', 'value' => 'KES ' . number_format((float) ($cj['total_debt_amount'] ?? 0), 2), 'icon' => 'wallet'],
      ['label' => 'Recovered', 'value' => 'KES ' . number_format((float) ($cj['total_recovered'] ?? 0), 2), 'icon' => 'hand-coins', 'tone' => 'up'],
      ['label' => 'Remaining', 'value' => 'KES ' . number_format((float) ($cj['remaining_balance'] ?? 0), 2), 'icon' => 'scale'],
      ['label' => 'Days in default', 'value' => number_format((int) ($cj['days_in_default'] ?? 0)), 'icon' => 'clock', 'tone' => ($cj['days_in_default'] ?? 0) > 90 ? 'down' : 'neutral'],
    ]" />

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Case Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Debtor Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Debtor Information</h3>
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-lg font-semibold text-gray-700 dark:text-gray-300" 
                         x-text="rc.user_initials || 'U'">
                    </div>
                    <div>
                        <p class="text-base font-medium text-gray-900 dark:text-white" x-text="rc.user_name"></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="rc.user_email"></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="rc.user_phone || 'No phone'"></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="rc.client_type ? 'Client Type: ' + rc.client_type : 'N/A'"></p>
                        <a :href="'/users/' + rc.user_id" class="mt-2 inline-block text-sm text-blue-600 hover:underline">View Full Profile →</a>
                    </div>
                </div>
            </div>

            <!-- Loan Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6" x-show="rc.loan_id">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Associated Loan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Loan ID</p>
                        <p class="text-base font-medium" x-text="'#' + String(rc.loan_id).padStart(5, '0')"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Loan Type</p>
                        <p class="text-base font-medium" x-text="rc.loan_type || 'N/A'"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Amount</p>
                        <p class="text-base font-medium" x-text="formatCurrency(rc.loan_amount)"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                              :class="{
                                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': rc.loan_status === 'disbursed',
                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': rc.loan_status === 'repaid',
                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': rc.loan_status === 'defaulted',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': rc.loan_status === 'overdue',
                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !rc.loan_status || !['disbursed','repaid','defaulted','overdue'].includes(rc.loan_status)
                              }"
                              x-text="rc.loan_status ? rc.loan_status.charAt(0).toUpperCase() + rc.loan_status.slice(1) : 'N/A'">
                        </span>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg" x-show="rc.is_non_performing">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <span class="text-sm font-medium text-red-800 dark:text-red-300">This loan is Non-Performing (NPL)</span>
                    </div>
                    <div class="mt-1 text-sm text-red-700 dark:text-red-400">
                        <span x-text="rc.days_overdue || 0"></span> days overdue • Defaulted on <span x-text="rc.default_date_formatted || 'N/A'"></span>
                    </div>
                </div>
                <a :href="'/users/' + rc.user_id + '/loans/' + rc.loan_id" class="mt-4 inline-block text-sm text-blue-600 hover:underline">View Loan Details →</a>
            </div>

            <!-- Recovery Strategy -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6" x-show="rc.recovery_strategy">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Recovery Strategy</h3>
                <p class="text-gray-700 dark:text-gray-300" x-text="rc.recovery_strategy"></p>
            </div>

            <!-- Notes -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6" x-show="rc.notes">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Notes</h3>
                <p class="text-gray-700 dark:text-gray-300" x-text="rc.notes"></p>
            </div>

            <!-- Activity Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Activity Timeline</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="rc.actions_count + ' actions'"></span>
                </div>
                
                <!-- Action Timeline - Inline -->
                <div class="flow-root">
                    @if($caseData->actions && $caseData->actions->count() > 0)
                        <ul class="-mb-8">
                            @foreach($caseData->actions as $action)
                                @php
                                    $actionColors = [
                                        'call' => 'bg-blue-500',
                                        'email' => 'bg-purple-500',
                                        'meeting' => 'bg-green-500',
                                        'letter' => 'bg-yellow-500',
                                        'payment' => 'bg-green-500',
                                        'reminder' => 'bg-orange-500',
                                        'negotiation' => 'bg-indigo-500',
                                        'legal' => 'bg-red-500',
                                        'note' => 'bg-gray-500',
                                        'other' => 'bg-gray-500',
                                    ];
                                    $actionType = $action->type ?? 'other';
                                    $actionColor = $actionColors[$actionType] ?? $actionColors['other'];
                                    
                                    $typeLabels = [
                                        'call' => 'Call',
                                        'email' => 'Email',
                                        'meeting' => 'Meeting',
                                        'letter' => 'Letter',
                                        'payment' => 'Payment',
                                        'reminder' => 'Reminder',
                                        'negotiation' => 'Negotiation',
                                        'legal' => 'Legal',
                                        'note' => 'Note',
                                        'other' => 'Other',
                                    ];
                                    $typeLabel = $typeLabels[$actionType] ?? 'Other';
                                @endphp
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-4 ring-white dark:ring-gray-800 {{ $actionColor }}">
                                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        @if($actionType === 'call')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                        @elseif($actionType === 'email')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                        @elseif($actionType === 'payment')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        @elseif($actionType === 'meeting')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                        @elseif($actionType === 'legal')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                        @else
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                        @endif
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $typeLabel }}
                                                            @if($action->user)
                                                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                                    by {{ $action->user->name }}
                                                                </span>
                                                            @endif
                                                        </p>
                                                        @if($action->description)
                                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $action->description }}</p>
                                                        @endif
                                                        @if($action->notes)
                                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $action->notes }}</p>
                                                        @endif
                                                        @if($action->amount > 0)
                                                            <p class="mt-1 text-sm font-medium text-green-600 dark:text-green-400">
                                                                Amount: KES {{ number_format($action->amount, 2) }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                    <div class="flex-shrink-0 text-right">
                                                        <time class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $action->created_at->format('M d, Y') }}
                                                            <span class="block text-xs">{{ $action->created_at->format('h:i A') }}</span>
                                                        </time>
                                                        @if($action->status)
                                                            <span class="mt-1 inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                                                {{ $action->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 
                                                                   ($action->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 
                                                                   ($action->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 
                                                                   'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300')) }}">
                                                                {{ ucfirst($action->status) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h4 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No activities recorded</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start tracking recovery actions for this case</p>
                            @if(auth()->user()->can('edit recovery cases'))
                                <button @click="openAddActionModal()" 
                                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Add First Action
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Case Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Case Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Assigned To</span>
                        <span class="text-sm font-medium" x-text="rc.assigned_to_name || 'Unassigned'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Recovery Officer</span>
                        <span class="text-sm font-medium" x-text="rc.recovery_officer || 'N/A'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Default Date</span>
                        <span class="text-sm font-medium" x-text="rc.default_date_formatted || 'N/A'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Last Contact</span>
                        <span class="text-sm font-medium" x-text="rc.last_contact_date_formatted || 'Never'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Next Action</span>
                        <span class="text-sm font-medium" x-text="rc.next_action_date_formatted || 'Not set'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Recovery Progress</span>
                        <span class="text-sm font-medium" x-text="rc.recovery_progress + '%'"></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            @if(auth()->user()->can('edit recovery cases'))
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <button @click="openAddActionModal()" 
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Action
                    </button>
                    
                    <button @click="markAsRecovered()" 
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Mark as Recovered
                    </button>
                    
                    @if(auth()->user()->can('edit recovery cases'))
                    <button @click="openWriteOffModal()" 
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Write Off Case
                    </button>
                    @endif
                </div>
            </div>
            @endif

            <!-- Payment Plans -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6" x-show="rc.payment_plans && rc.payment_plans.length > 0">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Payment Plans</h3>
                <template x-for="plan in rc.payment_plans" :key="plan.id">
                    <div class="border-b border-gray-100 dark:border-gray-700 last:border-0 py-3 last:pb-0">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium" x-text="plan.installment_frequency + ' Plan'"></span>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': plan.status === 'accepted',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': plan.status === 'proposed',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': plan.status === 'completed',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !['accepted','proposed','completed'].includes(plan.status)
                                  }"
                                  x-text="plan.status.charAt(0).toUpperCase() + plan.status.slice(1)">
                            </span>
                        </div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="plan.installment_amount + ' x ' + plan.number_of_installments + ' = KES ' + formatCurrency(plan.total_amount)"></div>
                        <div class="mt-2 w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                            <div class="bg-blue-600 h-1.5 rounded-full" :style="'width: ' + plan.progress_percentage + '%'"></div>
                        </div>
                        <div class="mt-1 text-xs text-gray-400" x-text="plan.progress_percentage + '% complete • ' + (plan.remaining_balance > 0 ? 'KES ' + formatCurrency(plan.remaining_balance) + ' remaining' : 'Fully paid')"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- Include the Case Create/Edit Modal --}}
@include('partials.modal.cases-create-modal')

{{-- Include Alert Modal --}}
@include('partials.modal.alert-modal')

{{-- Add action / Write off dialogs (listen for open-add-action / open-write-off) --}}
@include('partials.recovery.case-action-modals')

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('caseShow', (caseData) => ({
        // the case lives in rc: `case` is a reserved word in an Alpine expression
        rc: caseData || {},
        
        init() {
        },
        
        formatCurrency(amount) {
            if (amount === undefined || amount === null) return 'KES 0.00';
            return 'KES ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
        
        openEditCase() {
            window.dispatchEvent(new CustomEvent('edit-case', {
                detail: { case: this.rc }
            }));
        },
        
        openAddActionModal() {
            window.dispatchEvent(new CustomEvent('open-add-action', {
                detail: { caseId: this.rc.id }
            }));
        },
        
        async markAsRecovered() {
            if (await window.uiConfirm('Mark this case as recovered?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/cases/' + this.rc.id + '/recover';
                form.style.display = 'none';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrfInput);
                
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PATCH';
                form.appendChild(methodInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        },
        
        openWriteOffModal() {
            window.dispatchEvent(new CustomEvent('open-write-off', {
                detail: { caseId: this.rc.id }
            }));
        }
    }));
});
</script>
@endpush