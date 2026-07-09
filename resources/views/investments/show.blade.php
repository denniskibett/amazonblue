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

    <!-- Investment Detail Header -->
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
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('investments.index') }}" 
                   class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View All
                </a>
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

    <!-- Investment Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">KES {{ number_format($investment->initial_amount, 2) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Value</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">KES {{ number_format($investment->current_value, 2) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Return</p>
                    <p class="text-2xl font-semibold {{ ($investment->return_percentage ?? 0) >= 15 ? 'text-green-600 dark:text-green-400' : (($investment->return_percentage ?? 0) >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ number_format($investment->return_percentage ?? 0, 1) }}%
                    </p>
                </div>
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

            <!-- Pre-Investment Financials -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pre-Investment Financials</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                </div>
            </div>

            <!-- Funding Partners -->
            @if(!empty($investment->funding_partners))
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Funding Partners</h3>
                <div class="space-y-2">
                    @foreach($investment->funding_partners as $partner)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            Partner #{{ $partner['partner_id'] ?? 'Unknown' }}
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            KES {{ number_format($partner['amount'] ?? 0, 2) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Notes -->
            @if(!empty($investment->notes))
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notes</h3>
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
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Quick Stats</h4>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Type</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($investment->type) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sector</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $investment->sector ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Stage</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($investment->stage ?? 'N/A') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Risk Rating</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $investment->risk_rating ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Expected Return</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $investment->expected_return ?? 0 }}%</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Purchase Date</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $investment->purchase_date?->format('Y-m-d') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Maturity Date</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $investment->maturity_date?->format('Y-m-d') ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Stakeholders -->
            @if(!empty($investment->stakeholders))
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Stakeholders</h4>
                @if(!empty($investment->stakeholders['directors']))
                <div class="mb-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Directors</p>
                    @foreach($investment->stakeholders['directors'] as $director)
                    <div class="text-sm text-gray-900 dark:text-white">
                        {{ $director['name'] ?? 'N/A' }}
                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $director['title'] ?? 'N/A' }})</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    // Ensure edit event works
    window.addEventListener('edit-investment', (event) => {
        // This will be handled by the create modal
        console.log('Edit investment:', event.detail);
    });
});
</script>
@endpush

@endsection