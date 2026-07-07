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
            <button @click="exportReport('csv')" 
                    class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </button>
            <button @click="exportReport('excel')" 
                    class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Excel
            </button>
            <button @click="exportReport('pdf')" 
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors">
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
                    <input type="date" 
                           x-model="filters['{{ $filter['id'] }}']" 
                           @change="applyFilters()"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                </div>
                @elseif($filter['type'] === 'select')
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $filter['label'] }}</label>
                    <select x-model="filters['{{ $filter['id'] }}']" 
                            @change="applyFilters()"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                        @foreach($filter['options'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            @endforeach
            
            <div class="flex items-center gap-2">
                <button @click="resetFilters()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Reset
                </button>
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
                            @if(is_numeric($value) && strpos($key, 'rate') === false)
                                @if($value > 1000)
                                    KES {{ number_format($value, 2) }}
                                @else
                                    {{ number_format($value) }}
                                @endif
                            @else
                                {{ number_format($value, 2) }}%
                            @endif
                        </p>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Chart Section -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Chart View</h3>
                    <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                        <canvas id="reportChart"></canvas>
                    </div>
                </div>

                <!-- Table Section -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Data Table</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    @php
                                        $headers = [];
                                        if(isset($reportData['entries']) && count($reportData['entries']) > 0) {
                                            $headers = array_keys((array)$reportData['entries'][0]);
                                        } elseif(isset($reportData['loans']) && count($reportData['loans']) > 0) {
                                            $headers = array_keys((array)$reportData['loans'][0]);
                                        } elseif(isset($reportData['customers']) && count($reportData['customers']) > 0) {
                                            $headers = array_keys((array)$reportData['customers'][0]);
                                        }
                                    @endphp
                                    @foreach($headers as $header)
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ str_replace('_', ' ', $header) }}
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @php
                                    $items = $reportData['entries'] ?? $reportData['loans'] ?? $reportData['customers'] ?? [];
                                @endphp
                                @foreach($items as $item)
                                <tr>
                                    @foreach($headers as $header)
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                        {{ $item[$header] ?? '-' }}
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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
            // Set default dates
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth(), 1);
            this.filters.start_date = start.toISOString().split('T')[0];
            this.filters.end_date = now.toISOString().split('T')[0];
        },
        applyFilters() {
            // Filter the report data
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
        chart: null,
        initReport(data) {
            this.reportData = data;
            this.$nextTick(() => {
                this.renderChart();
            });
        },
        renderChart() {
            const canvas = document.getElementById('reportChart');
            if (!canvas) return;
            
            // Simple chart rendering using canvas
            const ctx = canvas.getContext('2d');
            const data = this.reportData;
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw placeholder chart
            ctx.fillStyle = '#e5e7eb';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#6b7280';
            ctx.font = '14px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('Chart visualization would be rendered here', canvas.width/2, canvas.height/2);
        },
        exportReport(format) {
            const url = new URL('{{ route("reports.export") }}', window.location.origin);
            url.searchParams.append('report_type', '{{ $reportType }}');
            url.searchParams.append('format', format);
            
            // Add filters
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