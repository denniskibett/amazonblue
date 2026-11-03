{{-- Cash and growth (owner, 3 Nov 2026): cash per the ledger, the 12-week cash forecast from the loan book (and with
     lending kept at the recent pace), and lending, collections and new-loan interest by month with a 3-month projection.
     A model, not a promise: the assumptions are shown at the bottom and live in config/lending.php › forecast. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]';
  $th = 'px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400';
  $td = 'px-4 py-3 text-sm text-gray-700 dark:text-gray-300';
  $kes = fn ($v) => number_format((float) $v, 0);
  $weeks = collect($forecast['weeks']);
  $a = $forecast['assumptions'];
  $in30 = $weeks->take(4)->sum('in_book');
  $end = $weeks->last();
  $low = $weeks->min('cash_pace');
  $month = fn ($m) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $m)->format('M Y');

  // chart: projected cash, two lines over 12 weeks
  $W = 1000; $H = 260; $pad = 40;
  $vals = $weeks->pluck('cash_book')->merge($weeks->pluck('cash_pace'))->push($cash ?? 0);
  $max = max(1, $vals->max()); $min = min(0, $vals->min());
  $x = fn ($i) => $pad + $i * (($W - 2 * $pad) / max(1, $weeks->count() - 1));
  $y = fn ($v) => $H - $pad - (($v - $min) / max(1, $max - $min)) * ($H - 2 * $pad);
  $line = fn ($key) => $weeks->values()->map(fn ($w, $i) => round($x($i), 1) . ',' . round($y($w[$key]), 1))->implode(' ');
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Cash and growth', 'sub' => 'Where cash stands, what the loan book should bring in over the next 12 weeks, and whether lending and collections are growing.', 'metrics' => false])

  <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
      ['Cash now', $cash !== null ? 'KES ' . $kes($cash) : '—', 'Bank and M-Pesa per the ledger (gl-cash)'],
      ['Expected in, next 4 weeks', 'KES ' . $kes($in30), 'From the loan book, weighted by how late each loan is'],
      ['Cash in 12 weeks', 'KES ' . $kes($end['cash_book'] ?? 0), 'If we stop lending · KES ' . $kes($end['cash_pace'] ?? 0) . ' at the recent pace'],
      ['Lowest point ahead', 'KES ' . $kes($low), 'At the recent lending pace: keep this above what you need on hand'],
    ] as [$label, $value, $sub])
      <div class="{{ $card }} p-5">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
        <p class="mt-3 text-title-sm font-semibold text-gray-800 dark:text-white/90">{{ $value }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $sub }}</p>
      </div>
    @endforeach
  </div>

  <div class="{{ $card }} mb-6 p-5 sm:p-6">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Cash over the next 12 weeks</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Starting from cash now. Weeks start on Monday.</p>
      </div>
      <div class="flex gap-4 text-xs text-gray-600 dark:text-gray-400">
        <span class="flex items-center gap-2"><span class="inline-block h-0.5 w-5 bg-brand-500"></span>Loan book only, no new lending</span>
        <span class="flex items-center gap-2"><span class="inline-block h-0.5 w-5 border-t-2 border-dashed border-warning-500"></span>Keep lending at KES {{ $kes($a['weekly_pace']) }} a week</span>
      </div>
    </div>
    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Projected cash over 12 weeks">
      @foreach ([0, 0.25, 0.5, 0.75, 1] as $t)
        @php $v = $min + ($max - $min) * $t; @endphp
        <line x1="{{ $pad }}" x2="{{ $W - $pad }}" y1="{{ $y($v) }}" y2="{{ $y($v) }}" stroke="currentColor" class="text-gray-100 dark:text-gray-800" />
        <text x="{{ $pad - 6 }}" y="{{ $y($v) + 4 }}" text-anchor="end" font-size="11" fill="currentColor" class="text-gray-400">{{ number_format($v / 1000, 0) }}k</text>
      @endforeach
      @if ($min < 0)<line x1="{{ $pad }}" x2="{{ $W - $pad }}" y1="{{ $y(0) }}" y2="{{ $y(0) }}" stroke="#F04438" stroke-dasharray="4 4" />@endif
      <polyline points="{{ $line('cash_book') }}" fill="none" stroke="#2B4FD8" stroke-width="2.5" />
      <polyline points="{{ $line('cash_pace') }}" fill="none" stroke="#F79009" stroke-width="2" stroke-dasharray="6 5" />
      @foreach ($weeks->values() as $i => $w)
        <text x="{{ $x($i) }}" y="{{ $H - 12 }}" text-anchor="middle" font-size="11" fill="currentColor" class="text-gray-500">{{ $w['from']->format('j M') }}</text>
      @endforeach
    </svg>
  </div>

  <div class="{{ $card }} mb-6 overflow-hidden">
    <div class="border-b border-gray-100 p-5 dark:border-gray-800 sm:px-6"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Week by week</h3></div>
    <div class="w-full overflow-x-auto">
      <table class="w-full min-w-[820px]">
        <thead class="border-b border-gray-100 dark:border-gray-800"><tr>
          <th class="{{ $th }} pl-6">Week of</th><th class="{{ $th }} text-right">Expected in (book)</th><th class="{{ $th }} text-right">Committed out</th><th class="{{ $th }} text-right">Cash, book only</th>
          <th class="{{ $th }} text-right">New lending out</th><th class="{{ $th }} text-right">It comes back</th><th class="{{ $th }} pr-6 text-right">Cash at recent pace</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @foreach ($weeks as $w)
            <tr>
              <td class="{{ $td }} pl-6">{{ $w['from']->format('j M Y') }}</td>
              <td class="{{ $td }} text-right">{{ $kes($w['in_book']) }}</td>
              <td class="{{ $td }} text-right">{{ $w['out_committed'] ? $kes($w['out_committed']) : '—' }}</td>
              <td class="{{ $td }} text-right font-medium">{{ $kes($w['cash_book']) }}</td>
              <td class="{{ $td }} text-right">{{ $kes($w['out_pace']) }}</td>
              <td class="{{ $td }} text-right">{{ $w['in_pace'] ? $kes($w['in_pace']) : '—' }}</td>
              <td class="{{ $td }} pr-6 text-right font-medium {{ $w['cash_pace'] < 0 ? 'text-error-600' : '' }}">{{ $kes($w['cash_pace']) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="{{ $card }} mb-6 overflow-hidden">
    <div class="flex flex-col justify-between gap-2 border-b border-gray-100 p-5 dark:border-gray-800 sm:flex-row sm:items-center sm:px-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Growth by month</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Cash lent and collected. The next three months grow at {{ $projection['rate'] }}% a month, the rate that turns the three months before into the last three.</p>
      </div>
      <span class="rounded-full px-3 py-1 text-sm font-medium {{ $projection['rate'] >= 0 ? 'bg-success-50 text-success-600' : 'bg-error-50 text-error-600' }}">{{ $projection['rate'] >= 0 ? 'Growing' : 'Shrinking' }} · {{ $projection['rate'] }}% a month</span>
    </div>
    <div class="w-full overflow-x-auto">
      <table class="w-full min-w-[820px]">
        <thead class="border-b border-gray-100 dark:border-gray-800"><tr>
          <th class="{{ $th }} pl-6">Month</th><th class="{{ $th }} text-right">Loans sent</th><th class="{{ $th }} text-right">New borrowers</th><th class="{{ $th }} text-right">Lent</th>
          <th class="{{ $th }} text-right">Collected</th><th class="{{ $th }} text-right">Net cash</th><th class="{{ $th }} text-right">Interest on new loans</th><th class="{{ $th }} pr-6 text-right">Collected vs month before</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @foreach ($months as $m)
            <tr>
              <td class="{{ $td }} pl-6">{{ $month($m['month']) }}@if ($m['partial']) <span class="text-xs text-gray-500">so far</span>@endif</td>
              <td class="{{ $td }} text-right">{{ $m['loans'] }}</td>
              <td class="{{ $td }} text-right">{{ $m['new_borrowers'] }}</td>
              <td class="{{ $td }} text-right">{{ $kes($m['lent']) }}</td>
              <td class="{{ $td }} text-right">{{ $kes($m['collected']) }}</td>
              <td class="{{ $td }} text-right {{ $m['net'] < 0 ? 'text-error-600' : 'text-success-600' }}">{{ $kes($m['net']) }}</td>
              <td class="{{ $td }} text-right">{{ $kes($m['income']) }}</td>
              <td class="{{ $td }} pr-6 text-right">{{ $m['growth'] === null ? '—' : ($m['growth'] > 0 ? '+' : '') . $m['growth'] . '%' }}</td>
            </tr>
          @endforeach
          @foreach ($projection['months'] as $m)
            <tr class="bg-gray-50 italic dark:bg-white/[0.02]">
              <td class="{{ $td }} pl-6">{{ $month($m['month']) }} <span class="text-xs not-italic text-gray-500">projected</span></td>
              <td class="{{ $td }} text-right">—</td><td class="{{ $td }} text-right">—</td>
              <td class="{{ $td }} text-right">{{ $kes($m['lent']) }}</td>
              <td class="{{ $td }} text-right">{{ $kes($m['collected']) }}</td>
              <td class="{{ $td }} text-right">{{ $kes($m['collected'] - $m['lent']) }}</td>
              <td class="{{ $td }} text-right">{{ $kes($m['income']) }}</td>
              <td class="{{ $td }} pr-6 text-right">{{ $projection['rate'] }}%</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="{{ $card }} p-5 sm:p-6">
      <h3 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">The loan book today</h3>
      @foreach (['current' => 'Not yet due', 'late_1_30' => '1–30 days late', 'late_31_90' => '31–90 days late', 'late_90' => 'Over 90 days late', 'forbearance' => 'Forbearance'] as $k => $label)
        <div class="flex justify-between border-t border-gray-100 py-2.5 text-sm first:border-0 dark:border-gray-800">
          <span class="text-gray-500 dark:text-gray-400">{{ $label }} · expect {{ (int) round($a['collect'][$k] * 100) }}% back</span>
          <span class="font-medium text-gray-800 dark:text-white/90">KES {{ $kes($forecast['book'][$k]) }}</span>
        </div>
      @endforeach
    </div>
    <div class="{{ $card }} p-5 sm:p-6">
      <h3 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">How the forecast is made</h3>
      <ul class="list-disc space-y-1.5 pl-5 text-sm text-gray-600 dark:text-gray-400">
        <li>What each open loan owes today comes back with the chance shown on the left: on its due date if it isn't due yet, otherwise spread over the next {{ $a['late_spread_weeks'] }} weeks.</li>
        <li>Applications waiting (KES {{ $kes($a['pending']) }}) go out next week at the recent approval rate of {{ (int) round($a['approval_rate'] * 100) }}%.</li>
        <li>The second line keeps lending KES {{ $kes($a['weekly_pace']) }} a week (the last 8 weeks' average). Each shilling lent has brought back {{ $a['return_ratio'] }} over the whole book, and comes back about {{ $a['return_lag_weeks'] }} weeks later.</li>
        <li>Partner payouts, salaries and other costs are not in it yet.</li>
        <li>The chances are set in config/lending.php › forecast (FORECAST_COLLECT_* in .env). Check them against what actually comes in each month.</li>
      </ul>
    </div>
  </div>
@endsection
