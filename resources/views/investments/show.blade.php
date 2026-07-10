{{-- resources/views/investments/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="{{ route('investments.index') }}" 
           class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Investments
        </a>
    </div>

    <!-- Investment Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $investment->name }}</h1>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ ucfirst($investment->type) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="{
                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': '{{ $investment->status }}' === 'pipeline' || '{{ $investment->status }}' === 'due_diligence',
                              'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': '{{ $investment->status }}' === 'active',
                              'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': '{{ $investment->status }}' === 'matured',
                              'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': '{{ $investment->status }}' === 'liquidated',
                              'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': '{{ $investment->status }}' === 'write_off'
                          }">
                        {{ ucfirst(str_replace('_', ' ', $investment->status)) }}
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $investment->country }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">| User: {{ $investment->user?->name ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="window.dispatchEvent(new CustomEvent('edit-investment', { detail: { investment: @json($data) } }))" 
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Investment
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Initial Amount</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white">KES {{ number_format($investment->initial_amount, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Value</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white">KES {{ number_format($investment->current_value, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expected Return</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($investment->expected_return ?? 0, 2) }}%</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Return</p>
            <p class="text-2xl font-semibold {{ ($investment->return_percentage ?? 0) >= 15 ? 'text-green-600 dark:text-green-400' : (($investment->return_percentage ?? 0) >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400') }}">
                {{ number_format($investment->return_percentage ?? 0, 2) }}%
            </p>
        </div>
    </div>

    <!-- Investment Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Investment Name</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</p>
                        <p class="text-gray-900 dark:text-white">{{ ucfirst($investment->type) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sector</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->sector ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sub-Sector</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->sub_sector ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Country</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->country }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Region</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->region ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">City</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->city ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">User</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->user?->name ?? 'N/A' }}</p>
                    </div>
                </div>
                @if($investment->address)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</p>
                    <p class="text-gray-900 dark:text-white mt-1">{{ $investment->address }}</p>
                </div>
                @endif
            </div>

            <!-- Company Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Company Details</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Company Name</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->company_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Registration Number</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->registration_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Incorporation Date</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->incorporation_date?->format('Y-m-d') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Legal Structure</p>
                        <p class="text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $investment->legal_structure ?? 'N/A')) }}</p>
                    </div>
                </div>
            </div>

            <!-- Financial Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Financial Details</h3>
                
                <!-- Pre-Investment Financials -->
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">Pre-Investment Financials</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">EBITDA</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->ebitda_pre_investment ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenue</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->revenue_pre_investment ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Profit</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->net_profit_pre_investment ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Assets</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->total_assets_pre_investment ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Liabilities</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->total_liabilities_pre_investment ?? 0, 2) }}</p>
                    </div>
                </div>

                <!-- Investment Metrics -->
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">Investment Metrics</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Initial Amount</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->initial_amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Value</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->current_value, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expected Return</p>
                        <p class="text-gray-900 dark:text-white">{{ number_format($investment->expected_return ?? 0, 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Actual Return</p>
                        <p class="text-gray-900 dark:text-white">{{ number_format($investment->actual_return ?? 0, 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Revenue</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->revenue_current ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Profit</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->profit_current ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Valuation</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->valuation_current ?? 0, 2) }}</p>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">Performance Metrics</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">IRR</p>
                        <p class="text-gray-900 dark:text-white">{{ number_format($investment->irr ?? 0, 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Payback Period</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->payback_period_months ?? 0 }} months</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Break Even Point</p>
                        <p class="text-gray-900 dark:text-white">KES {{ number_format($investment->break_even_point ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Dates -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Dates</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Purchase Date</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->purchase_date?->format('Y-m-d') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Maturity Date</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->maturity_date?->format('Y-m-d') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Exit Date</p>
                        <p class="text-gray-900 dark:text-white">{{ $investment->exit_date?->format('Y-m-d') ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Risk Assessment -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Risk Assessment</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Risk Rating</p>
                        <p class="text-gray-900 dark:text-white">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': '{{ $investment->risk_rating }}' === 'AAA' || '{{ $investment->risk_rating }}' === 'AA' || '{{ $investment->risk_rating }}' === 'A',
                                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': '{{ $investment->risk_rating }}' === 'BBB' || '{{ $investment->risk_rating }}' === 'BB',
                                      'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': '{{ $investment->risk_rating }}' === 'B' || '{{ $investment->risk_rating }}' === 'C'
                                  }">
                                {{ $investment->risk_rating ?? 'N/A' }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stage</p>
                        <p class="text-gray-900 dark:text-white">{{ ucfirst($investment->stage ?? 'N/A') }}</p>
                    </div>
                </div>

                <!-- Risk Factors -->
                @if(!empty($investment->risk_factors))
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3">Risk Factors</h4>
                <div class="space-y-3">
                    @foreach($investment->risk_factors as $risk)
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-gray-800 dark:text-white">{{ $risk['factor'] ?? 'N/A' }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': '{{ $risk['severity'] }}' === 'Low',
                                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': '{{ $risk['severity'] }}' === 'Medium',
                                      'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': '{{ $risk['severity'] }}' === 'High' || '{{ $risk['severity'] }}' === 'Critical'
                                  }">
                                {{ $risk['severity'] ?? 'N/A' }}
                            </span>
                        </div>
                        @if(!empty($risk['mitigation']))
                        <p class="text-sm text-gray-600 dark:text-gray-400">Mitigation: {{ $risk['mitigation'] }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- SWOT Analysis - FIXED to handle both string and array values -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">SWOT Analysis</h3>
                @php
                    $swot = $investment->swot_analysis ?? [];
                    // Convert string values to arrays if needed
                    $swotStrengths = isset($swot['strengths']) ? (is_array($swot['strengths']) ? $swot['strengths'] : explode(',', $swot['strengths'])) : [];
                    $swotWeaknesses = isset($swot['weaknesses']) ? (is_array($swot['weaknesses']) ? $swot['weaknesses'] : explode(',', $swot['weaknesses'])) : [];
                    $swotOpportunities = isset($swot['opportunities']) ? (is_array($swot['opportunities']) ? $swot['opportunities'] : explode(',', $swot['opportunities'])) : [];
                    $swotThreats = isset($swot['threats']) ? (is_array($swot['threats']) ? $swot['threats'] : explode(',', $swot['threats'])) : [];
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Strengths -->
                    <div class="border border-green-200 dark:border-green-800 rounded-lg overflow-hidden">
                        <div class="bg-green-50 dark:bg-green-900/20 px-3 py-2 border-b border-green-200 dark:border-green-800">
                            <h5 class="text-sm font-medium text-green-700 dark:text-green-300">Strengths</h5>
                        </div>
                        <div class="p-3">
                            @if(!empty($swotStrengths))
                                @foreach($swotStrengths as $strength)
                                <div class="flex items-start gap-2 py-1">
                                    <span class="text-green-500 mt-1">●</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ trim($strength) }}</span>
                                </div>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No strengths listed</p>
                            @endif
                        </div>
                    </div>

                    <!-- Weaknesses -->
                    <div class="border border-red-200 dark:border-red-800 rounded-lg overflow-hidden">
                        <div class="bg-red-50 dark:bg-red-900/20 px-3 py-2 border-b border-red-200 dark:border-red-800">
                            <h5 class="text-sm font-medium text-red-700 dark:text-red-300">Weaknesses</h5>
                        </div>
                        <div class="p-3">
                            @if(!empty($swotWeaknesses))
                                @foreach($swotWeaknesses as $weakness)
                                <div class="flex items-start gap-2 py-1">
                                    <span class="text-red-500 mt-1">●</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ trim($weakness) }}</span>
                                </div>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No weaknesses listed</p>
                            @endif
                        </div>
                    </div>

                    <!-- Opportunities -->
                    <div class="border border-blue-200 dark:border-blue-800 rounded-lg overflow-hidden">
                        <div class="bg-blue-50 dark:bg-blue-900/20 px-3 py-2 border-b border-blue-200 dark:border-blue-800">
                            <h5 class="text-sm font-medium text-blue-700 dark:text-blue-300">Opportunities</h5>
                        </div>
                        <div class="p-3">
                            @if(!empty($swotOpportunities))
                                @foreach($swotOpportunities as $opportunity)
                                <div class="flex items-start gap-2 py-1">
                                    <span class="text-blue-500 mt-1">●</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ trim($opportunity) }}</span>
                                </div>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No opportunities listed</p>
                            @endif
                        </div>
                    </div>

                    <!-- Threats -->
                    <div class="border border-yellow-200 dark:border-yellow-800 rounded-lg overflow-hidden">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 px-3 py-2 border-b border-yellow-200 dark:border-yellow-800">
                            <h5 class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Threats</h5>
                        </div>
                        <div class="p-3">
                            @if(!empty($swotThreats))
                                @foreach($swotThreats as $threat)
                                <div class="flex items-start gap-2 py-1">
                                    <span class="text-yellow-500 mt-1">●</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ trim($threat) }}</span>
                                </div>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No threats listed</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stakeholders -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Stakeholders</h3>
                @if(!empty($investment->stakeholders))
                    @if(!empty($investment->stakeholders['directors']))
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Directors</h4>
                        <div class="space-y-2">
                            @foreach($investment->stakeholders['directors'] as $director)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $director['name'] ?? 'N/A' }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $director['title'] ?? 'N/A' }}</span>
                                </div>
                                @if(!empty($director['shareholding']))
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $director['shareholding'] }}%</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(!empty($investment->stakeholders['board']))
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Board Members</h4>
                        <div class="space-y-2">
                            @foreach($investment->stakeholders['board'] as $member)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $member['name'] ?? 'N/A' }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $member['title'] ?? 'N/A' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(!empty($investment->stakeholders['advisors']))
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Advisors</h4>
                        <div class="space-y-2">
                            @foreach($investment->stakeholders['advisors'] as $advisor)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $advisor['name'] ?? 'N/A' }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $advisor['role'] ?? 'N/A' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @else
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No stakeholders listed</p>
                @endif
            </div>

            <!-- Milestones -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Milestones</h3>
                @if(!empty($investment->milestones))
                <div class="space-y-3">
                    @foreach($investment->milestones as $milestone)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $milestone['description'] ?? 'N/A' }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $milestone['date'] ?? 'N/A' }}</span>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="{
                                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': '{{ $milestone['status'] }}' === 'pending',
                                  'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': '{{ $milestone['status'] }}' === 'in_progress',
                                  'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': '{{ $milestone['status'] }}' === 'completed',
                                  'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': '{{ $milestone['status'] }}' === 'cancelled'
                              }">
                            {{ ucfirst(str_replace('_', ' ', $milestone['status'] ?? 'pending')) }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No milestones set</p>
                @endif
            </div>

            <!-- Research & Analysis -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Research & Analysis</h3>
                @if($investment->market_research)
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Market Research</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $investment->market_research }}</p>
                </div>
                @endif

                @if($investment->competitive_landscape)
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Competitive Landscape</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $investment->competitive_landscape }}</p>
                </div>
                @endif

                @if($investment->key_assumptions)
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Key Assumptions</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $investment->key_assumptions }}</p>
                </div>
                @endif
            </div>

            <!-- Notes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notes</h3>
                @if(!empty($investment->notes))
                <div class="space-y-3">
                    @foreach($investment->notes as $note)
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $note['author'] ?? 'System' }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $note['date'] ?? '' }}</span>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $note['content'] ?? '' }}</p>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ strtoupper($note['category'] ?? '') }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No notes available</p>
                @endif
            </div>

            <!-- Updates -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Updates</h3>
                @if(!empty($investment->updates))
                <div class="space-y-3">
                    @foreach($investment->updates as $update)
                    <div class="flex items-start gap-3 py-2 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex-shrink-0 mt-1">
                            <span class="inline-block w-2 h-2 rounded-full bg-blue-500"></span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $update['update'] ?? '' }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $update['author'] ?? 'System' }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $update['date'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No updates available</p>
                @endif
            </div>

            <!-- Funding Partners -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Funding Partners</h3>
                @if(!empty($investment->funding_partners))
                <div class="space-y-2">
                    @foreach($investment->funding_partners as $partner)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Partner #{{ $partner['partner_id'] ?? 'Unknown' }}</span>
                        <div class="text-right">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">KES {{ number_format($partner['amount'] ?? 0, 2) }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 block">{{ $partner['date'] ?? '' }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Funding Raised</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">KES {{ number_format($investment->total_funding_raised ?? 0, 2) }}</span>
                    </div>
                </div>
                @else
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No funding partners</p>
                @endif
            </div>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Quick Stats</h4>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': '{{ $investment->status }}' === 'pipeline' || '{{ $investment->status }}' === 'due_diligence',
                                      'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': '{{ $investment->status }}' === 'active',
                                      'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': '{{ $investment->status }}' === 'matured',
                                      'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': '{{ $investment->status }}' === 'liquidated',
                                      'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': '{{ $investment->status }}' === 'write_off'
                                  }">
                                {{ ucfirst(str_replace('_', ' ', $investment->status)) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Stage</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($investment->stage ?? 'N/A') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Risk Rating</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': '{{ $investment->risk_rating }}' === 'AAA' || '{{ $investment->risk_rating }}' === 'AA' || '{{ $investment->risk_rating }}' === 'A',
                                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': '{{ $investment->risk_rating }}' === 'BBB' || '{{ $investment->risk_rating }}' === 'BB',
                                      'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': '{{ $investment->risk_rating }}' === 'B' || '{{ $investment->risk_rating }}' === 'C'
                                  }">
                                {{ $investment->risk_rating ?? 'N/A' }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Return</p>
                        <p class="text-sm font-medium {{ ($investment->return_percentage ?? 0) >= 15 ? 'text-green-600 dark:text-green-400' : (($investment->return_percentage ?? 0) >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ number_format($investment->return_percentage ?? 0, 2) }}%
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">IRR</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($investment->irr ?? 0, 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Payback Period</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $investment->payback_period_months ?? 0 }} months</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Funding Raised</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">KES {{ number_format($investment->total_funding_raised ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Returns</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">KES {{ number_format($investment->total_returns ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Net Position</p>
                        <p class="text-sm font-medium {{ ($investment->net_return ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            KES {{ number_format($investment->net_return ?? 0, 2) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Created</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $investment->created_at?->format('Y-m-d H:i') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Last Updated</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $investment->updated_at?->format('Y-m-d H:i') ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Created/Updated By -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Audit Info</h4>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Created By</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $investment->creator?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Last Updated By</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $investment->updater?->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Transaction Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Transaction Summary</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Disbursements</span>
                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                            KES {{ number_format($investment->total_disbursements ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Repayments</span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">
                            KES {{ number_format($investment->total_repayments ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Net Position</span>
                            <span class="text-sm font-semibold {{ ($investment->net_position ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                KES {{ number_format($investment->net_position ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
@include('partials.modal.investments-create-modal', ['partners' => $partners ?? [], 'users' => $users ?? []])

<!-- Alert Modal -->
@include('partials.modal.alert-modal')

<!-- Delete Modal -->
@include('partials.modal.delete')

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    // Delete handler
    window.deleteInvestment = function(id, name) {
        window.dispatchEvent(new CustomEvent('delete-investment', {
            detail: { id, name }
        }));
    };
});
</script>
@endpush

<style>
[x-cloak] {
    display: none !important;
}
</style>
@endsection