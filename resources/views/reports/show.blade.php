{{-- resources/views/reports/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    ← Back to Reports
                </a>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-white">{{ $reportTitle }}</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Generated on {{ now()->format('F j, Y g:i A') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="exportReport('csv')" class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </button>
            <button @click="exportReport('excel')" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Excel
            </button>
            <button @click="exportReport('pdf')" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Export PDF
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-wrap items-end gap-4" x-data="reportFilters()" x-init="init()">
            @foreach($filters as $filter)
                @if($filter['type'] === 'date')
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $filter['label'] }}</label>
                    <input type="date" x-model="filters['{{ $filter['id'] }}']" @change="applyFilters()" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                </div>
                @elseif($filter['type'] === 'select')
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $filter['label'] }}</label>
                    <select x-model="filters['{{ $filter['id'] }}']" @change="applyFilters()" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                        @foreach($filter['options'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            @endforeach
            <div class="flex items-center gap-2">
                <button @click="resetFilters()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Reset</button>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4">
            <div x-data="reportDisplay()" x-init="initReport(@json($reportData))">
                <!-- Summary Cards -->
                @if(isset($reportData['summary']))
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    @foreach($reportData['summary'] as $key => $value)
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ str_replace('_', ' ', $key) }}</p>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white mt-1">
                            @if(is_numeric($value) && strpos($key, 'rate') === false && strpos($key, 'percentage') === false && strpos($key, 'yield') === false)
                                @if($value > 1000)
                                    KES {{ is_numeric($value) ? number_format($value, 2) : (is_array($value) ? json_encode($value) : $value) }}
                                @else
                                    {{ number_format($value) }}
                                @endif
                            @else
                                @if(is_numeric($value))
                                    {{ number_format($value, 2) }}%
                                @else
                                    {{ is_array($value) ? json_encode($value) : $value }}
                                @endif
                            @endif
                        </p>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Chart Section -->
                <div class="mb-6 rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
                    <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
                        <div class="w-full">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 font-outfit">
                                {{ $reportTitle }} - Charts
                            </h3>
                            <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400 font-outfit">
                                Visual representation of {{ strtolower($reportTitle) }}
                            </p>
                        </div>
                        <div class="flex items-start w-full gap-3 sm:justify-end">
                            @if(count($availableCharts) > 0)
                            <div class="inline-flex w-fit items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                                @foreach($availableCharts as $chart)
                                <button 
                                    @click="loadChart('{{ $chart['value'] }}')" 
                                    :class="selectedChart === '{{ $chart['value'] }}' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'" 
                                    class="px-3 py-2 font-medium rounded-md text-theme-sm hover:text-gray-900 dark:hover:text-white font-outfit">
                                    {{ $chart['label'] }}
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="max-w-full overflow-x-auto custom-scrollbar">
                        <div id="chartContainer" class="-ml-4 min-w-[700px] pl-2">
                            <!-- Dynamic chart loading -->
                            @if(isset($activeChart) && view()->exists('partials.chart.' . $activeChart))
                                @include('partials.chart.' . $activeChart, ['reportData' => $reportData])
                            @else
                                @include('partials.chart.' . ($availableCharts[0]['value'] ?? 'customer-balance'), ['reportData' => $reportData])
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Data Table</h3>
                    @php
                        $tableName = str_replace('_', '-', $reportType);
                    @endphp
                    @include('partials.table.table-reports-' . $tableName, ['data' => $reportData])
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function reportFilters() {
    return {
        filters: {
            start_date: '',
            end_date: '',
            status: 'all',
            loan_type: 'all',
            amount_range: 'all',
            gender: 'all',
            role: 'all'
        },
        init() {
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth(), 1);
            this.filters.start_date = start.toISOString().split('T')[0];
            this.filters.end_date = now.toISOString().split('T')[0];
        },
        applyFilters() {
            this.$dispatch('filter-applied', this.filters);
        },
        resetFilters() {
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth(), 1);
            this.filters.start_date = start.toISOString().split('T')[0];
            this.filters.end_date = now.toISOString().split('T')[0];
            this.filters.status = 'all';
            this.filters.loan_type = 'all';
            this.filters.amount_range = 'all';
            this.filters.gender = 'all';
            this.filters.role = 'all';
            this.applyFilters();
        }
    }
}

function reportDisplay() {
    return {
        reportData: {},
        selectedChart: '{{ $availableCharts[0]['value'] ?? 'customer-balance' }}',
        chartInstance: null,
        
        initReport(data) {
            this.reportData = data;
            console.log('Report data loaded:', data);
            this.$nextTick(() => {
                this.loadChart(this.selectedChart);
            });
        },
        
        loadChart(chartType) {
            this.selectedChart = chartType;
            console.log('Loading chart:', chartType);
            
            // Update active button state
            document.querySelectorAll('[x-on\\:click^="loadChart"]').forEach(btn => {
                const isActive = btn.textContent.trim().toLowerCase().replace(/\s+/g, '-') === chartType;
                if (isActive) {
                    btn.classList.add('shadow-theme-xs', 'text-gray-900', 'dark:text-white', 'bg-white', 'dark:bg-gray-800');
                    btn.classList.remove('text-gray-500', 'dark:text-gray-400');
                } else {
                    btn.classList.remove('shadow-theme-xs', 'text-gray-900', 'dark:text-white', 'bg-white', 'dark:bg-gray-800');
                    btn.classList.add('text-gray-500', 'dark:text-gray-400');
                }
            });
            
            // Load chart partial
            const container = document.getElementById('chartContainer');
            if (!container) return;
            
            // Show loading
            container.innerHTML = '<div class="text-center py-10 text-gray-500 font-outfit">Loading chart...</div>';
            
            // Use Alpine to load the chart partial
            if (window.Alpine) {
                // Get the chart partial HTML
                const chartName = this.getChartName(chartType);
                const partialPath = `partials.chart.${chartName}`;
                
                // Load via fetch
                fetch(`/api/chart-partial/${chartName}`)
                    .then(response => response.text())
                    .then(html => {
                        container.innerHTML = html;
                        // Initialize the chart
                        setTimeout(() => {
                            const initFn = window[`initChart${chartName.replace(/-/g, '')}`];
                            if (typeof initFn === 'function') {
                                initFn(this.reportData);
                            }
                        }, 100);
                    })
                    .catch(() => {
                        // Fallback: try to load via Blade include
                        this.loadChartFallback(chartType, container);
                    });
            } else {
                // Fallback for no Alpine
                this.loadChartFallback(chartType, container);
            }
        },
        
        getChartName(chartType) {
            const chartMap = {
                'customer-balance': 'customer-balance',
                'role-distribution': 'role-distribution',
                'outstanding-balance': 'outstanding-balance',
                'repayment-percentage': 'repayment-percentage',
                'gender-distribution': 'gender-distribution',
                'age-distribution': 'age-distribution',
                'customer-behavior': 'customer-behavior',
                'savings-balance': 'savings-balance',
                'status-distribution': 'status-distribution',
                'loan-type-distribution': 'loan-type-distribution',
                'loan-size-distribution': 'loan-size-distribution',
                'amount-vs-balance': 'amount-vs-balance',
                'active-repayment-percentage': 'active-repayment-percentage',
                'principal-vs-interest': 'principal-vs-interest',
                'duration': 'duration',
                'default-breakdown': 'default-breakdown',
                'recovery-status': 'recovery-status',
                'expected-repayments': 'expected-repayments',
                'repayment-status-breakdown': 'repayment-status-breakdown',
                'disbursement-amounts': 'disbursement-amounts',
                'disbursement-channel': 'disbursement-channel',
                'group-loan-status': 'group-loan-status',
                'financial-summary': 'financial-summary',
                'performance-metrics': 'performance-metrics',
                'cashflow-trend': 'cashflow-trend',
                'running-balance': 'running-balance',
                'journal-entries': 'journal-entries',
                'credit-score-distribution': 'credit-score-distribution',
                'bureau-listings': 'bureau-listings',
                'default-amounts': 'default-amounts',
                'default-vs-cleared': 'default-vs-cleared'
            };
            return chartMap[chartType] || chartType;
        },
        
        loadChartFallback(chartType, container) {
            // Direct rendering fallback
            const chartName = this.getChartName(chartType);
            const data = this.reportData;
            
            // Create a clean chart container
            const chartDiv = document.createElement('div');
            chartDiv.id = 'reportChart';
            chartDiv.className = 'w-full';
            container.innerHTML = '';
            container.appendChild(chartDiv);
            
            // Call the appropriate render function
            const renderFn = window[`render${chartName.replace(/-/g, '')}Chart`];
            if (typeof renderFn === 'function') {
                renderFn(chartDiv, data);
            } else {
                container.innerHTML = `<div class="text-center py-10 text-gray-500 font-outfit">Chart "${chartType}" not available</div>`;
            }
        },
        
        exportReport(format) {
            const url = new URL('{{ route("reports.export") }}', window.location.origin);
            url.searchParams.append('report_type', '{{ $reportType }}');
            url.searchParams.append('format', format);
            const filters = this.$root.querySelector('[x-data="reportFilters()"]')?.__x?.$data?.filters || {};
            Object.entries(filters).forEach(([key, value]) => {
                if (value) url.searchParams.append('filters[' + key + ']', value);
            });
            window.open(url, '_blank');
        }
    }
}
</script>
@endpush
@endsection