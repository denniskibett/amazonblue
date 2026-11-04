{{-- P&L and unit economics (owner, 4 Nov 2026): where the money went, the income statement, the key lending ratios,
     collection efficiency by month, unit economics and product profitability. Cash basis from the operational
     records (App\Services\Analytics\BusinessInsights). --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
  $th = 'px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400';
  $td = 'whitespace-nowrap px-3 py-3 text-sm text-gray-700 dark:text-gray-300';
  $kes = fn ($v) => 'KES ' . number_format(abs((float) $v), 0);
  $signed = fn ($v) => ($v < 0 ? '(' : '') . $kes($v) . ($v < 0 ? ')' : '');
  $pct = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.') . '%';
  $gross = max(1, (float) $r['gross']);
  $u = $r['unit'];
  $ra = $r['ratios'];
  $eff = collect($r['efficiency']);
  $W = 600; $H = 200;
  $ex = fn ($i) => 40 + $i * (($W - 60) / max(1, $eff->count() - 1));
  $ey = fn ($v) => $H - 24 - ($v / 100) * ($H - 40);
  $tone = ['green' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500', 'amber' => 'bg-orange-50 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400',
    'red' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-500', 'gray' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300'];
  $bench = fn ($ok, $text) => '<span class="rounded-full px-2 py-0.5 text-theme-xs font-medium ' . ($ok === null ? $tone['gray'] : ($ok ? $tone['green'] : $tone['amber'])) . '">' . e($text) . '</span>';
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'P&L and unit economics', 'sub' => 'Is the business working? ' . $r['label'] . ' (' . \Illuminate\Support\Carbon::parse($r['from'])->format('j M') . ' to ' . \Illuminate\Support\Carbon::parse($r['to'])->format('j M Y') . '), cash basis.', 'metrics' => false,
    'actions' => '<form method="GET"><select name="period" onchange="this.form.submit()" class="h-11 rounded-lg border border-gray-300 bg-white px-3 text-sm dark:border-gray-700 dark:bg-gray-900">' . collect(\App\Services\Analytics\BusinessInsights::PERIODS)->map(fn ($l, $k) => '<option value="' . $k . '"' . ($k === $r['period'] ? ' selected' : '') . '>' . e($l) . '</option>')->implode('') . '</select></form>'])

  <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
    @foreach ([
      ['Profit before tax', $signed($r['profit']), ($r['opex_set'] ? 'after operating expenses' : 'before operating expenses (not set)') . ($r['prior'] > 0 ? '; ' . $kes($r['prior']) . ' of older loans shown separately' : ''), $r['profit'] >= 0 ? 'text-success-600' : 'text-error-600'],
      ['Net interest income', $kes($r['nii']), 'NIM ' . $pct($ra['nim']) . ' a year', null],
      ['Cost of funds', $pct($ra['funding_share']), 'of gross revenue paid to partners', null],
      ['PAR 30', $pct($ra['par30']), 'of KES ' . number_format($r['book'], 0) . ' owed', $ra['par30'] > 5 ? 'text-error-600' : 'text-success-600'],
      ['LTV : CAC', $u['ltv_cac'] !== null ? $u['ltv_cac'] . '×' : '—', $u['cac'] ? 'CAC KES ' . number_format($u['cac']) : 'set ANALYTICS_CAC to see it', null],
    ] as [$l, $v, $s, $c])
      <div class="{{ $card }}"><p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $l }}</p><p class="mt-2 whitespace-nowrap font-outfit text-2xl font-bold text-gray-800 dark:text-white/90">{{ $v }}</p><p class="mt-1 text-theme-xs {{ $c ?? 'text-gray-500' }}">{{ $s }}</p></div>
    @endforeach
  </div>

  <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    {{-- where the money went: revenue down to profit; subtotals start from zero --}}
    <div class="{{ $card }}">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Where the money went</h3>
      <p class="mb-4 text-theme-sm text-gray-500">From gross revenue to profit before tax.</p>
      @php
        // bar spans: subtotals run from zero; other lines move the running total. The axis spans the lowest and
        // highest point reached, so a loss (running total below zero) still draws
        $run = 0.0; $spans = [];
        foreach ($r['lines'] as [$name, $v, $kind]) {
            if (in_array($kind, ['sub', 'total'], true)) { $spans[] = [0, $v]; $run = $v; }
            else { $spans[] = [$run, $run + $v]; $run += $v; }
        }
        $lo = min(0, collect($spans)->flatten()->min()); $hi = max(1, collect($spans)->flatten()->max()); $span = max(1, $hi - $lo);
        $zero = (0 - $lo) / $span * 100;
      @endphp
      @foreach ($r['lines'] as $i => [$name, $v, $kind])
        @php
          [$a1, $a2] = $spans[$i];
          $l = (min($a1, $a2) - $lo) / $span * 100; $w = abs($a2 - $a1) / $span * 100;
          $color = in_array($kind, ['sub', 'total'], true) ? ($kind === 'sub' ? 'bg-brand-900 dark:bg-brand-400' : ($v >= 0 ? 'bg-success-600' : 'bg-error-600'))
            : ($v >= 0 ? 'bg-success-500' : (str_contains($name, 'Cost of funds') ? 'bg-orange-500' : 'bg-error-500'));
        @endphp
        <div class="grid grid-cols-[minmax(0,13rem)_1fr_7rem] items-center gap-3 py-1.5 text-theme-sm {{ in_array($kind, ['sub', 'total'], true) ? 'font-semibold text-gray-800 dark:text-white/90' : 'text-gray-600 dark:text-gray-400' }}">
          <span class="truncate" title="{{ $name }}">{{ $name }}</span>
          <span class="relative h-6 rounded bg-gray-50 dark:bg-white/[0.03]">
            @if ($lo < 0)<span class="absolute inset-y-0 w-px bg-gray-400" style="left: {{ $zero }}%"></span>@endif
            <span class="absolute top-0 h-6 rounded {{ $color }}" style="left: {{ $l }}%; width: {{ max(0.5, $w) }}%"></span>
          </span>
          <span class="text-right font-outfit {{ $v < 0 ? 'text-error-600' : '' }}">{{ $signed($v) }}</span>
        </div>
      @endforeach
      @unless ($r['opex_set'])
        <p class="mt-3 rounded-lg bg-orange-50 p-3 text-theme-xs text-orange-700 dark:bg-orange-500/10 dark:text-orange-400">Operating expenses (staff, premises, tech, marketing) aren't recorded in the system yet. Set ANALYTICS_OPEX_STAFF, _PREMISES, _TECH and _MARKETING (KES a month) in .env to include them.</p>
      @endunless
    </div>

    <div class="{{ $card }}">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Income statement</h3>
      <p class="mb-3 text-theme-sm text-gray-500">Management accounts, unaudited; % of gross revenue.</p>
      @foreach ($r['lines'] as [$name, $v, $kind])
        @php $strong = in_array($kind, ['sub', 'total'], true); @endphp
        <div class="grid grid-cols-[1fr_8rem_4rem] gap-3 py-2.5 text-theme-sm {{ $kind === 'total' ? 'border-t-2 border-gray-800 font-semibold dark:border-white/60' : ($strong ? 'border-t border-gray-300 font-semibold dark:border-gray-700' : 'border-t border-gray-100 dark:border-gray-800') }}">
          <span class="{{ $strong ? 'text-gray-800 dark:text-white/90' : 'pl-3 text-gray-600 dark:text-gray-400' }}">{{ $name }}</span>
          <span class="text-right font-outfit {{ $v < 0 ? 'text-error-600' : ($kind === 'in' || $kind === 'total' ? 'text-success-700 dark:text-success-500' : 'text-gray-800 dark:text-white/90') }}">{{ $signed($v) }}</span>
          <span class="text-right text-gray-500">{{ $pct(abs($v) / $gross * 100) }}</span>
        </div>
      @endforeach
    </div>
  </div>

  <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-5">
    <div class="{{ $card }} xl:col-span-3">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Key ratios</h3>
      <table class="mt-3 w-full"><thead><tr class="border-b border-gray-100 dark:border-gray-800"><th class="{{ $th }}">Ratio</th><th class="{{ $th }} text-right">{{ $r['label'] }}</th><th class="{{ $th }}">Benchmark</th><th class="{{ $th }}">What it means</th></tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @foreach ([
            ['Net interest margin (NIM, a year)', $ra['nim'], $ra['nim'] === null ? null : $ra['nim'] >= 30, 'Over 30%', 'What we keep from interest after partners, per shilling lent'],
            ['Cost of risk (a year)', $ra['cost_of_risk'], $ra['cost_of_risk'] === null ? null : $ra['cost_of_risk'] <= 10, 'Under 10%', 'Write-offs per shilling lent'],
            ['PAR 30', $ra['par30'], $ra['par30'] <= 5, 'Under 5%', 'Share of what is owed that is 30+ days late'],
            ['PAR 90', $ra['par90'], $ra['par90'] <= 3, 'Under 3%', 'Share 90+ days late'],
            ['NPL ratio', $ra['npl'], $ra['npl'] === null ? null : $ra['npl'] <= 5, 'Under 5%', 'Non-performing loans as a share of the book'],
            ['Cost-to-income', $ra['cost_to_income'], $ra['cost_to_income'] === null ? null : $ra['cost_to_income'] <= 50, 'Under 50%', 'Operating expenses per shilling of net interest income'],
            ['Return on the loan book (a year)', $ra['return_on_book'], $ra['return_on_book'] === null ? null : $ra['return_on_book'] > 0, 'Above 0', 'Profit per shilling owed to us'],
          ] as [$n, $v, $ok, $b, $what])
            <tr><td class="{{ $td }} font-medium">{{ $n }}</td><td class="{{ $td }} text-right font-outfit">{{ $pct($v) }}</td><td class="{{ $td }}">{!! $bench($v === null ? null : $ok, $b) !!}</td><td class="{{ $td }} text-theme-xs text-gray-500">{{ $what }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="{{ $card }} xl:col-span-2">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Unit economics</h3>
      <p class="mb-3 text-theme-sm text-gray-500">All loans since the start.</p>
      <dl class="divide-y divide-gray-100 text-theme-sm dark:divide-gray-800">
        @foreach ([['Borrowers', number_format($u['borrowers'])], ['Loans per borrower', $u['loans_per_borrower'] ?? '—'], ['Contribution per loan', $u['contribution_per_loan'] !== null ? $kes($u['contribution_per_loan']) : '—'],
          ['Lifetime value (LTV)', $u['ltv'] !== null ? $kes($u['ltv']) : '—'], ['Cost to bring a borrower (CAC)', $u['cac'] ? $kes($u['cac']) : 'not set (ANALYTICS_CAC)'],
          ['Stopped after their first loan', $pct($u['churn_after_first'])]] as [$k, $v])
          <div class="flex justify-between py-2.5"><dt class="text-gray-500">{{ $k }}</dt><dd class="font-outfit font-medium text-gray-800 dark:text-white/90">{{ $v }}</dd></div>
        @endforeach
      </dl>
    </div>
  </div>

  <div class="mb-6 {{ $card }}">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Collection efficiency</h3>
    <p class="mb-3 text-theme-sm text-gray-500">Of what new loans asked for, how much came in by the due date plus 7 days, by the month they fell due. Target 98%.</p>
    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-56 w-full" preserveAspectRatio="none" role="img" aria-label="Collection efficiency by month">
      @foreach ([0, 50, 98] as $g)<line x1="40" x2="{{ $W - 20 }}" y1="{{ $ey($g) }}" y2="{{ $ey($g) }}" stroke="{{ $g === 98 ? '#F04438' : '#E4E7EC' }}" @if ($g === 98) stroke-dasharray="6 5" @endif /><text x="34" y="{{ $ey($g) + 4 }}" font-size="11" fill="#98A2B3" text-anchor="end">{{ $g }}%</text>@endforeach
      <polyline fill="none" stroke="#465FFF" stroke-width="2.5" points="{{ $eff->values()->map(fn ($m, $i) => round($ex($i), 1) . ',' . round($ey($m['rate'] ?? 0), 1))->implode(' ') }}" />
      @foreach ($eff->values() as $i => $m)
        <circle cx="{{ $ex($i) }}" cy="{{ $ey($m['rate'] ?? 0) }}" r="4" fill="#465FFF" /><text x="{{ $ex($i) }}" y="{{ $ey($m['rate'] ?? 0) - 10 }}" font-size="11.5" fill="#344054" text-anchor="middle">{{ $pct($m['rate']) }}</text>
        <text x="{{ $ex($i) }}" y="{{ $H - 4 }}" font-size="11" fill="#98A2B3" text-anchor="middle">{{ $m['month'] }} · {{ $m['loans'] }}</text>
      @endforeach
    </svg>
  </div>

  <div class="{{ $card }}">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Product profitability</h3>
    <p class="mb-3 text-theme-sm text-gray-500">Contribution margin: interest and penalty collected in the period, less what partners earned on those loans and what was written off or waived.</p>
    <div class="overflow-x-auto"><table class="w-full min-w-[900px]">
      <thead><tr class="border-b border-gray-100 dark:border-gray-800">@foreach (['Product', 'Lent', 'Income', 'Yield on lending', 'Cost of funds', 'Written off and waived', 'Contribution margin', 'Open · 30+ days late', 'Verdict'] as $h)<th class="{{ $th }} {{ in_array($h, ['Product', 'Verdict']) ? '' : 'text-right' }}">{{ $h }}</th>@endforeach</tr></thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($r['products'] as $p)
          <tr><td class="{{ $td }} font-medium">{{ $p['product'] }}</td><td class="{{ $td }} text-right font-outfit">{{ $kes($p['lent']) }}</td><td class="{{ $td }} text-right font-outfit">{{ $kes($p['income']) }}</td>
            <td class="{{ $td }} text-right">{{ $pct($p['yield']) }}</td><td class="{{ $td }} text-right font-outfit">{{ $kes($p['cost']) }}</td><td class="{{ $td }} text-right font-outfit">{{ $kes($p['lost']) }}</td>
            <td class="{{ $td }} text-right font-outfit {{ ($p['margin'] ?? 0) < 0 ? 'text-error-600' : '' }}">{{ $pct($p['margin']) }}</td><td class="{{ $td }} text-right">{{ $p['open'] }} · {{ $pct($p['late']) }}</td>
            <td class="{{ $td }}"><span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $tone[$p['verdict'][1]] }}">{{ $p['verdict'][0] }}</span></td></tr>
        @empty
          <tr><td colspan="9" class="px-3 py-6 text-center text-sm text-gray-500">No lending or income in this period.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </div>
@endsection
