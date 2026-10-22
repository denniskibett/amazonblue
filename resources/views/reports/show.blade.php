{{-- resources/views/reports/show.blade.php --}}
@extends('layouts.app')

@section('content')
@php
  $chartBlocks = \App\Support\ReportCharts::blocks((array) $reportData);
  $chartNow = collect($chartBlocks)->firstWhere('key', request('chart')) ?? ($chartBlocks[0] ?? null);
  $from = request('start_date', now()->startOfMonth()->toDateString());
  $to = request('end_date', now()->toDateString());
  $exp = fn ($fmt) => route('reports.export', ['report_type' => $reportType, 'format' => $fmt, 'filters' => ['start_date' => request('start_date'), 'end_date' => request('end_date')]]);
  $btn = 'inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]';
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $exportActions = auth()->user()->can('export reports')
    ? '<a href="' . e($exp('csv')) . '" class="' . $btn . '">CSV</a><a href="' . e($exp('excel')) . '" class="' . $btn . '">Excel</a><a href="' . e($exp('pdf')) . '" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Download PDF</a>'
    : null;
@endphp
<div>
    @include('staff.desk._header', ['title' => $reportTitle, 'sub' => 'Generated ' . now()->format('j M Y, H:i') . '. Reports & Analytics', 'metrics' => false, 'actions' => $exportActions])

    <!-- Filters: the report reads the date range from the address, so the form reloads the page -->
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <label class="min-w-[160px] flex-1"><span class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">From</span><input type="date" name="start_date" value="{{ $from }}" class="{{ $input }}"></label>
        <label class="min-w-[160px] flex-1"><span class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">To</span><input type="date" name="end_date" value="{{ $to }}" class="{{ $input }}"></label>
        <div class="flex gap-2">
            <button class="inline-flex h-11 items-center gap-2 rounded-lg bg-brand-500 px-5 text-theme-sm font-medium text-white hover:bg-brand-600"><x-ui.icon name="filter" class="size-4" /> Apply</button>
            <a href="{{ route('reports.show', $reportType) }}" class="inline-flex h-11 items-center rounded-lg border border-gray-300 px-5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Reset</a>
        </div>
    </form>

    @php
      // the report's headline figures as standard cards above the report (owner, 22 Oct 2026)
      $summaryCards = collect($reportData['summary'] ?? [])->filter(fn ($v) => is_scalar($v))->map(function ($v, $k) {
          $money = preg_match('/amount|value|balance|size|inflow|outflow|cashflow|repaid|disbursed|repayments|disbursements|interest|debit|credit|outstanding|savings|earned/', $k)
              && ! preg_match('/^total_(loans|disbursements|entries|customers|submissions)$|count|days|period/', $k);
          $value = ! is_numeric($v) ? e($v) : (preg_match('/rate|percent|yield/', $k) ? number_format((float) $v, 2) . '%'
              : ($money ? 'KES ' . number_format((float) $v, 2) : number_format((float) $v, floor((float) $v) == (float) $v ? 0 : 2)));
          $icon = preg_match('/rate|percent|yield/', $k) ? 'percent' : ($money ? 'wallet' : (preg_match('/customer|borrower|user/', $k) ? 'users' : 'list'));
          return ['label' => ucfirst(str_replace('_', ' ', $k)), 'value' => $value, 'icon' => $icon];
      })->values()->take(6)->all();
    @endphp
    <x-ui.metrics :items="$summaryCards" />

    <!-- Report Content -->
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="p-5 sm:p-6">
            <div x-data="reportDisplay()" x-init="initReport({{ \Illuminate\Support\Js::from($reportData) }})">
                @if(!empty($reportData['not_available']))
                <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                    <p class="font-medium">Not available yet (no data recorded in the system):</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach($reportData['not_available'] as $item)<li>{{ $item }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <!-- Charts: drawn from this report's own figures (App\Support\ReportCharts) -->
                @if ($chartBlocks)
                <div class="mb-6 rounded-2xl border border-gray-200 px-5 pb-3 pt-5 dark:border-gray-800 sm:px-6 sm:pt-6">
                    <div class="mb-2 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $chartNow['label'] }}</h3>
                            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $reportTitle }}{{ request('start_date') ? ', ' . request('start_date') . ' to ' . request('end_date', now()->toDateString()) : '' }}</p>
                        </div>
                        @if (count($chartBlocks) > 1)
                        <div class="no-scrollbar flex max-w-full gap-0.5 overflow-x-auto rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                            @foreach ($chartBlocks as $blk)
                            <a href="{{ request()->fullUrlWithQuery(['chart' => $blk['key']]) }}"
                               class="whitespace-nowrap rounded-md px-3 py-2 text-theme-sm font-medium {{ $chartNow['key'] === $blk['key'] ? 'bg-white text-gray-900 shadow-theme-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}">{{ $blk['label'] }}</a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div id="reportChart" class="-ml-3 min-h-[320px]"></div>
                </div>
                @endif

                <!-- Table Section -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Data</h3>
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
            // the chart is drawn by the server-side partial; the tabs are links (?chart=...)
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

(function () {
  const blk = @json($chartNow);
  if (!blk) return;
  let chart = null, was = null;
  const short = v => Math.abs(v) >= 1e6 ? (v / 1e6).toFixed(1) + 'M' : Math.abs(v) >= 1e3 ? Math.round(v / 1e3) + 'k' : Math.round(v * 100) / 100;
  const full = v => Number(v).toLocaleString(undefined, { maximumFractionDigits: 2 });
  function draw() {
    if (typeof ApexCharts === 'undefined') return;
    const dark = document.body.classList.contains('dark');
    const fg = dark ? '#98A2B3' : '#667085', grid = dark ? '#1D2939' : '#E4E7EC';
    const palette = ['#2B4FD8', '#A9BCF4', '#12B76A', '#FDB022', '#F04438', '#36BFFA', '#7A5AF8', '#F97316'];
    const base = { fontFamily: 'Outfit, sans-serif', foreColor: fg, toolbar: { show: false }, background: 'transparent', height: 320 };
    let o;
    if (blk.type === 'donut') {
      o = { chart: { ...base, type: 'donut' }, series: blk.series[0].data, labels: blk.labels, colors: palette,
            legend: { position: 'right', labels: { colors: fg } }, stroke: { width: 0 }, dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '68%', labels: { show: true, value: { color: dark ? '#fff' : '#1D2939', formatter: v => full(v) },
              total: { show: true, label: 'Total', color: fg, formatter: w => full(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) } } } } },
            tooltip: { theme: dark ? 'dark' : 'light', y: { formatter: full } } };
    } else {
      const horizontal = blk.type === 'bar' && blk.labels.length > 6;
      o = { chart: { ...base, type: blk.type === 'area' ? 'area' : 'bar' }, series: blk.series, colors: palette,
            xaxis: { categories: blk.labels, axisBorder: { show: false }, axisTicks: { show: false }, labels: horizontal ? { formatter: short } : { rotate: -30, hideOverlappingLabels: true }, tickAmount: blk.type === 'area' ? 10 : undefined },
            yaxis: { labels: { formatter: horizontal ? (v => String(v).slice(0, 28)) : short } },
            plotOptions: { bar: { horizontal, columnWidth: '45%', barHeight: '60%', borderRadius: 4, borderRadiusApplication: 'end' } },
            stroke: blk.type === 'area' ? { curve: 'smooth', width: 2 } : { show: true, width: 2, colors: ['transparent'] },
            fill: blk.type === 'area' ? { type: 'gradient', gradient: { opacityFrom: 0.45, opacityTo: 0 } } : {},
            dataLabels: { enabled: false }, legend: { show: blk.series.length > 1, position: 'top', horizontalAlign: 'left', labels: { colors: fg } },
            grid: { borderColor: grid, strokeDashArray: 4 }, tooltip: { theme: dark ? 'dark' : 'light', y: { formatter: full } } };
    }
    if (chart) chart.destroy();
    chart = new ApexCharts(document.querySelector('#reportChart'), o); chart.render();
  }
  document.addEventListener('DOMContentLoaded', draw);
  new MutationObserver(() => { const d = document.body.classList.contains('dark'); if (was !== null && d !== was) draw(); was = d; })
    .observe(document.body, { attributes: true, attributeFilter: ['class'] });
})();
</script>
@endpush
@endsection