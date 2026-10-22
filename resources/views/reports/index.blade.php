{{-- Reports & Analytics (owner, 22 Oct 2026): TailAdmin analytics layout. Charts on top (cash in and out,
     PAR aging, book by product), then the report library grouped the way a lender files its reports. --}}
@extends('layouts.app')

@php
  // Standard lender reporting: each report sits in one section and has the cadence it is usually run at.
  $library = [
    'Portfolio quality' => ['icon' => 'pie', 'note' => 'How healthy the book is: what is owed, how late, and what to expect in.', 'ids' => [
      'loan_portfolio' => 'Weekly', 'active_loans' => 'Daily', 'defaulted_loans' => 'Weekly', 'expected_repayments' => 'Daily']],
    'Lending activity' => ['icon' => 'send', 'note' => 'Money out and loans closed.', 'ids' => [
      'loan_disbursement' => 'Daily', 'completed_loans' => 'Monthly']],
    'Customers' => ['icon' => 'users', 'note' => 'Who borrows, what they owe and hold.', 'ids' => [
      'customer_reports' => 'Monthly', 'customer_balance_summary' => 'Monthly', 'customer_statistics' => 'Monthly', 'customer_savings' => 'Monthly']],
    'Finance and accounting' => ['icon' => 'scale', 'note' => 'Cash, the journal and the overall position.', 'ids' => [
      'cashflow' => 'Monthly', 'journal_entries' => 'Monthly', 'group_summary' => 'Monthly']],
    'Regulatory' => ['icon' => 'shield', 'note' => 'Credit bureau and compliance returns.', 'ids' => [
      'crb_report' => 'Monthly', 'cic_reports' => 'Quarterly']],
  ];
  $icons = ['loan_portfolio' => 'briefcase', 'active_loans' => 'trending-up', 'defaulted_loans' => 'alert', 'expected_repayments' => 'calendar',
            'loan_disbursement' => 'send', 'completed_loans' => 'check-circle', 'customer_reports' => 'users', 'customer_balance_summary' => 'wallet',
            'customer_statistics' => 'chart', 'customer_savings' => 'coins', 'cashflow' => 'banknote', 'journal_entries' => 'file', 'group_summary' => 'layers',
            'crb_report' => 'shield', 'cic_reports' => 'file'];
  $byId = collect($reports)->keyBy('id');
  $listed = collect($library)->flatMap(fn ($s) => array_keys($s['ids']))->all();
  $other = $byId->except($listed);
  if ($other->isNotEmpty()) {
    $library['Other'] = ['icon' => 'list', 'note' => '', 'ids' => $other->map(fn () => 'On demand')->all()];
  }
  $u = auth()->user();
  $pages = array_filter([
    ['Portfolio', 'Owed today, PAR and income from the ledger', 'staff.portfolio', 'pie', $u->can('view reports')],
    ['Disbursements register', 'Every disbursement, filterable and exportable', 'disbursements.index', 'send', $u->can('view disbursements')],
    ['End of day', 'Today\'s cash in and out by till', 'staff.end-of-day', 'clock', $u->canAny(['create repayments', 'create disbursements', 'view reports'])],
    ['Wallets and reconciliation', 'Ledger against wallets and M-Pesa', 'staff.wallets', 'scale', $u->canAny(['reconcile wallets', 'view house reconciliation'])],
  ], fn ($p) => $p[4] && \Illuminate\Support\Facades\Route::has($p[2]));
  $freqTone = ['Daily' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400', 'Weekly' => 'bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400',
               'Monthly' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300', 'Quarterly' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
               'On demand' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300'];
  $card = 'rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]';
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Reports & Analytics', 'sub' => 'Cash, portfolio quality and the standard reports, as of ' . now()->format('j M Y') . '.', 'metrics' => 'reports'])

  @if ($analytics)
    <div class="mb-6 grid grid-cols-12 gap-4 md:gap-6">
      {{-- Cash out vs cash in --}}
      <div class="col-span-12 {{ $card }} px-5 pt-5 sm:px-6 sm:pt-6 xl:col-span-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Cash out and cash in</h3>
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Money sent to borrowers and money received, last 12 months. Rollovers and discounts are not cash.</p>
          </div>
          <div class="flex gap-4 text-theme-sm">
            <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400"><span class="size-2.5 rounded-full bg-brand-500"></span>Disbursed <b class="text-gray-800 dark:text-white/90">{{ number_format(array_sum($analytics['monthly']['disbursed'])) }}</b></span>
            <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400"><span class="size-2.5 rounded-full bg-brand-200"></span>Collected <b class="text-gray-800 dark:text-white/90">{{ number_format(array_sum($analytics['monthly']['collected'])) }}</b></span>
          </div>
        </div>
        <div class="-ml-4 mt-2 min-h-[310px]" id="chartCash"></div>
      </div>

      {{-- PAR aging donut --}}
      <div class="col-span-12 {{ $card }} p-5 sm:p-6 xl:col-span-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Portfolio at risk</h3>
        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">What open loans owe, by days late.</p>
        <div class="mx-auto mt-2 flex min-h-[230px] justify-center" id="chartAging"></div>
        <ul class="mt-2 space-y-2">
          @foreach ($analytics['aging'] as $i => $a)
            <li class="flex items-center justify-between text-theme-sm">
              <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400"><span class="size-2.5 rounded-full" style="background: {{ ['#12B76A', '#36BFFA', '#FDB022', '#F97316', '#F04438'][$i] }}"></span>{{ $a['bucket'] }} <span class="text-gray-400">({{ $a['loans'] }})</span></span>
              <span class="font-medium text-gray-800 dark:text-white/90">{{ number_format($a['owed']) }} <span class="ml-1 text-theme-xs text-gray-400">{{ $a['share'] }}%</span></span>
            </li>
          @endforeach
        </ul>
      </div>

      {{-- Book by product --}}
      <div class="col-span-12 {{ $card }} px-5 pt-5 sm:px-6 sm:pt-6 xl:col-span-7">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Owed by loan product</h3>
        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Open balance per product, largest first.</p>
        <div class="-ml-2 min-h-[280px]" id="chartProducts"></div>
      </div>

      {{-- Loans by status --}}
      <div class="col-span-12 {{ $card }} p-5 sm:p-6 xl:col-span-5">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Loans by status</h3>
        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Every loan on file.</p>
        @php $stTotal = max(1, array_sum($analytics['status'])); arsort($analytics['status']); @endphp
        <ul class="mt-5 space-y-4">
          @foreach ($analytics['status'] as $st => $n)
            <li>
              <div class="mb-1.5 flex justify-between text-theme-sm"><span class="capitalize text-gray-600 dark:text-gray-400">{{ str_replace('_', ' ', $st) }}</span><span class="font-medium text-gray-800 dark:text-white/90">{{ number_format($n) }}</span></div>
              <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-2 rounded-full bg-brand-500" style="width: {{ round($n / $stTotal * 100, 1) }}%"></div></div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  {{-- Live pages --}}
  @if ($pages)
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
      @foreach ($pages as [$label, $hint, $route, $icon])
        <a href="{{ route($route) }}" class="group flex items-center gap-4 {{ $card }} p-4 hover:border-brand-300 dark:hover:border-brand-800">
          <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300"><x-ui.icon :name="$icon" class="size-5" /></span>
          <span class="min-w-0">
            <span class="block text-theme-sm font-medium text-gray-800 group-hover:text-brand-500 dark:text-white/90">{{ $label }}</span>
            <span class="block truncate text-theme-xs text-gray-500 dark:text-gray-400">{{ $hint }}</span>
          </span>
        </a>
      @endforeach
    </div>
  @endif

  {{-- Report library --}}
  <div class="{{ $card }}" x-data="{ q: '', section: 'all' }">
    <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:px-6 2xl:flex-row 2xl:items-center 2xl:justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Report library</h3>
        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Open a report to filter by date, see its chart and export to CSV, Excel or PDF.</p>
      </div>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="no-scrollbar flex gap-0.5 overflow-x-auto rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
          @foreach (array_merge(['all' => 'All'], array_combine(array_keys($library), array_keys($library))) as $k => $label)
            <button type="button" @click="section = @js($k)"
                    :class="section === @js($k) ? 'bg-white text-gray-900 shadow-theme-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 dark:text-gray-400'"
                    class="whitespace-nowrap rounded-md px-3 py-2 text-theme-sm font-medium hover:text-gray-900 dark:hover:text-white">{{ $label }}</button>
          @endforeach
        </div>
        <div class="relative">
          <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><x-ui.icon name="search" class="size-4" /></span>
          <input type="search" x-model="q" placeholder="Find a report"
                 class="h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-9 pr-3 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 sm:w-56" />
        </div>
      </div>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
      @foreach ($library as $section => $s)
        <section class="px-5 py-5 sm:px-6" x-show="section === 'all' || section === @js($section)">
          <div class="mb-4 flex items-center gap-3">
            <span class="flex size-9 items-center justify-center rounded-lg bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400"><x-ui.icon :name="$s['icon']" class="size-5" /></span>
            <div>
              <h4 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $section }}</h4>
              @if ($s['note'])<p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $s['note'] }}</p>@endif
            </div>
          </div>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-4">
            @foreach ($s['ids'] as $id => $freq)
              @continue(! $byId->has($id))
              @php $r = $byId[$id]; $name = $id === 'loan_disbursement' ? 'Disbursement register' : $r['name']; @endphp
              <a href="{{ route('reports.show', $id) }}"
                 x-show="! q || @js(mb_strtolower($name . ' ' . $r['description'])).includes(q.toLowerCase())"
                 class="group flex items-start gap-3 rounded-xl border border-gray-200 p-4 hover:border-brand-300 hover:bg-gray-50 dark:border-gray-800 dark:hover:border-brand-800 dark:hover:bg-white/[0.02]">
                <x-ui.icon :name="$icons[$id] ?? 'file'" class="mt-0.5 size-5 shrink-0 text-gray-500 group-hover:text-brand-500 dark:text-gray-400" />
                <span class="min-w-0 flex-1">
                  <span class="block text-theme-sm font-medium text-gray-800 group-hover:text-brand-500 dark:text-white/90">{{ $name }}</span>
                  <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">{{ $r['description'] }}</span>
                </span>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $freqTone[$freq] ?? $freqTone['Monthly'] }}">{{ $freq }}</span>
              </a>
            @endforeach
          </div>
        </section>
      @endforeach
    </div>
  </div>
@endsection

@if ($analytics)
@push('scripts')
<script>
(function () {
  const data = @json($analytics);
  const charts = [];
  const kes = v => 'KES ' + Math.round(v).toLocaleString();
  const short = v => v >= 1e6 ? (v / 1e6).toFixed(1) + 'M' : v >= 1e3 ? Math.round(v / 1e3) + 'k' : v;

  function render() {
    if (typeof ApexCharts === 'undefined') return;
    charts.splice(0).forEach(c => c.destroy());
    const dark = document.body.classList.contains('dark');
    const fg = dark ? '#98A2B3' : '#667085';
    const grid = dark ? '#1D2939' : '#E4E7EC';
    const base = { fontFamily: 'Outfit, sans-serif', foreColor: fg, toolbar: { show: false }, background: 'transparent' };
    const tooltip = { theme: dark ? 'dark' : 'light', y: { formatter: kes } };

    charts.push(new ApexCharts(document.querySelector('#chartCash'), {
      chart: { ...base, type: 'bar', height: 310 },
      series: [{ name: 'Disbursed', data: data.monthly.disbursed }, { name: 'Collected', data: data.monthly.collected }],
      colors: ['#2B4FD8', '#A9BCF4'],
      plotOptions: { bar: { columnWidth: '45%', borderRadius: 5, borderRadiusApplication: 'end' } },
      dataLabels: { enabled: false }, legend: { show: false },
      stroke: { show: true, width: 3, colors: ['transparent'] },
      xaxis: { categories: data.monthly.labels, axisBorder: { show: false }, axisTicks: { show: false } },
      yaxis: { labels: { formatter: short } },
      grid: { borderColor: grid, strokeDashArray: 4, yaxis: { lines: { show: true } } },
      tooltip,
    }));

    charts.push(new ApexCharts(document.querySelector('#chartAging'), {
      chart: { ...base, type: 'donut', height: 240 },
      series: data.aging.map(a => a.owed), labels: data.aging.map(a => a.bucket),
      colors: ['#12B76A', '#36BFFA', '#FDB022', '#F97316', '#F04438'],
      legend: { show: false }, dataLabels: { enabled: false }, stroke: { width: 0 },
      plotOptions: { pie: { donut: { size: '72%', labels: { show: true,
        value: { color: dark ? '#FFFFFF' : '#1D2939', fontSize: '20px', fontWeight: 600, formatter: v => short(+v) },
        total: { show: true, label: 'Owed', color: fg, formatter: w => short(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) } } } } },
      tooltip,
    }));

    charts.push(new ApexCharts(document.querySelector('#chartProducts'), {
      chart: { ...base, type: 'bar', height: 280 },
      series: [{ name: 'Owed', data: data.products.map(p => p.owed) }],
      colors: ['#2B4FD8'],
      plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
      dataLabels: { enabled: false },
      xaxis: { categories: data.products.map(p => p.product), labels: { formatter: short }, axisBorder: { show: false }, axisTicks: { show: false } },
      grid: { borderColor: grid, strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
      tooltip,
    }));
    charts.forEach(c => c.render());
  }

  document.addEventListener('DOMContentLoaded', render);
  let was = null;
  new MutationObserver(() => { const d = document.body.classList.contains('dark'); if (was !== null && d !== was) render(); was = d; }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
})();
</script>
@endpush
@endif
