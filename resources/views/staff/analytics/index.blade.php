{{-- Lending analytics (owner, 23 Oct 2026): amounts asked, sent and repaid comfortably, the weekday / month / repeat
     heatmaps, and every borrower's behaviour profile. Outcomes are the 4Cs ones (CreditAssessment::outcome). --}}
@extends('layouts.app')

@php
  $A = \App\Services\BorrowerAnalytics::class;
  $all = $heat['all'];
  $pct = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.') . '%';
  $k = fn ($v) => $v >= 1000000 ? number_format($v / 1000000, 1) . 'M' : ($v >= 1000 ? number_format($v / 1000, 0) . 'k' : number_format($v, 0));
  // heat: green for good rates, red for bad ones; inline so any value gets its own shade
  $good = fn ($v) => $v === null ? '' : 'background: rgba(18, 183, 106, ' . round(0.08 + min(1, $v / 100) * 0.5, 2) . ')';
  $badHeat = fn ($v) => $v === null ? '' : 'background: rgba(240, 68, 56, ' . round(0.06 + min(1, $v / 40) * 0.5, 2) . ')';
  $best = collect($heat['bands'])->filter(fn ($b) => $b['judged'] >= 3)->sortByDesc(fn ($b) => ($b['on_time'] ?? 0) - 2 * ($b['bad'] ?? 0))->first();
  $wk = collect($heat['weekday']['totals']);
  $wkOn = fn ($days) => (function () use ($days, $wk) { $n = 0; $on = 0; foreach ($days as $d) { $c = $wk[$d]; $n += $c['judged']; $on += ($c['on_time'] ?? 0) * $c['judged'] / 100; } return $n ? round($on / $n * 100, 1) : null; })();
  $weekdayOn = $wkOn([1, 2, 3, 4]); $weekendOn = $wkOn([5, 6, 7]);
  $tabs = ['weekday' => 'Weekday the money went out', 'month' => 'Time of the month', 'repeat' => 'Which loan of the borrower'];
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Lending analytics', 'sub' => 'Which amounts are asked for, sent and paid back comfortably, and how each borrower behaves. Use it to decide where to lend and where to hold back.', 'metrics' => false])

  <x-ui.metrics :items="[
    ['label' => 'Loans analysed', 'value' => number_format($heat['loans']), 'icon' => 'layers', 'hint' => number_format($all['sent_n']) . ' sent · ' . number_format($all['judged']) . ' with an outcome'],
    ['label' => 'Paid on time', 'value' => $pct($all['on_time']), 'icon' => 'check-circle', 'hint' => $pct($all['late']) . ' paid late'],
    ['label' => 'Gone bad', 'value' => $pct($all['bad']), 'icon' => 'alert', 'hint' => 'written off or 30+ days late'],
    ['label' => 'Most comfortable amount', 'value' => $best['label'] ?? '—', 'icon' => 'star', 'hint' => $best ? $pct($best['on_time']) . ' on time' : 'not enough loans'],
    ['label' => 'Fri–Sun loans on time', 'value' => $pct($weekendOn), 'icon' => 'calendar', 'hint' => 'Mon–Thu: ' . $pct($weekdayOn), 'tone' => $weekendOn !== null && $weekdayOn !== null && $weekendOn < $weekdayOn - 10 ? 'down' : 'neutral'],
  ]" />

  {{-- 1. by amount --}}
  <section class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="px-4 pt-4 sm:px-6">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">By amount: asked, sent and paid back</h3>
      <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Greener cells are paid back on time more often; redder ones go bad more often. "Comfortable" = 75%+ on time and 5% or less gone bad.</p>
    </div>
    <div class="max-w-full overflow-x-auto px-4 pb-4 pt-4 sm:px-6">
      <table class="w-full min-w-[860px] text-left text-theme-sm text-gray-700 dark:text-gray-300">
        <caption class="sr-only">Loans by amount band</caption>
        <thead class="border-y border-gray-100 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
          <tr>@foreach (['Amount', 'Asked', 'Approved · rejected', 'Sent', 'On time', 'Late', 'Rolled', 'Gone bad', 'Collected', 'Verdict'] as $h)<th scope="col" @class(['whitespace-nowrap py-3 pr-3', 'text-right' => ! in_array($h, ['Amount', 'Verdict'], true)])>{{ $h }}</th>@endforeach</tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @foreach ($heat['bands'] as $b)
            @php [$v, $tone] = $A::verdict($b); @endphp
            <tr>
              <td class="whitespace-nowrap py-3 pr-3 font-medium text-gray-800 dark:text-white/90">{{ $b['label'] }}</td>
              <td class="py-3 pr-3 text-right tabular-nums">{{ $b['asked_n'] }}<span class="block text-theme-xs text-gray-500">{{ $k($b['asked']) }}</span></td>
              <td class="py-3 pr-3 text-right tabular-nums">{{ $b['approved_n'] }} · {{ $b['rejected_n'] }}</td>
              <td class="py-3 pr-3 text-right tabular-nums">{{ $b['sent_n'] }}<span class="block text-theme-xs text-gray-500">{{ $k($b['sent']) }}</span></td>
              <td class="py-3 pr-3 text-right font-semibold tabular-nums text-gray-800 dark:text-white/90" style="{{ $good($b['on_time']) }}">{{ $pct($b['on_time']) }}</td>
              <td class="py-3 pr-3 text-right tabular-nums">{{ $pct($b['late']) }}@if ($b['avg_late'])<span class="block text-theme-xs text-gray-500">avg {{ $b['avg_late'] }} days</span>@endif</td>
              <td class="py-3 pr-3 text-right tabular-nums">{{ $pct($b['rolled']) }}</td>
              <td class="py-3 pr-3 text-right font-semibold tabular-nums text-gray-800 dark:text-white/90" style="{{ $badHeat($b['bad']) }}">{{ $pct($b['bad']) }}</td>
              <td class="py-3 pr-3 text-right tabular-nums">{{ $pct($b['collected']) }}</td>
              <td class="py-3"><x-staff.pill :tone="$tone" dot>{{ $v }}</x-staff.pill></td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">Rates are out of loans with an outcome (finished, or overdue now). Collected = cash repaid ÷ cash sent, so it can pass 100% with interest.</p>
    </div>
  </section>

  {{-- 2. heatmaps by weekday / month / repeat --}}
  <section class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]" x-data="{ tab: 'weekday', metric: 'on_time' }">
    <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-4 pt-3 dark:border-gray-800 sm:px-6" role="tablist">
      @foreach ($tabs as $key => $label)
        <button type="button" role="tab" @click="tab = @js($key)" :aria-selected="tab === @js($key)"
          class="-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-theme-sm font-medium"
          :class="tab === @js($key) ? 'border-brand-500 text-brand-500 dark:border-brand-400 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">{{ $label }}</button>
      @endforeach
    </div>
    <div class="flex flex-col gap-3 px-4 pt-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
      <p class="text-theme-sm text-gray-500 dark:text-gray-400">Each cell: share of those loans that <span x-text="metric === 'on_time' ? 'were paid on time' : 'went bad'"></span>, and how many loans it is based on.</p>
      <div class="inline-flex rounded-lg bg-gray-100 p-1 dark:bg-gray-900">
        <button type="button" class="rounded-md px-3 py-1.5 text-theme-xs font-medium" :class="metric === 'on_time' ? 'bg-white text-gray-900 shadow-theme-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500'" @click="metric = 'on_time'">On time</button>
        <button type="button" class="rounded-md px-3 py-1.5 text-theme-xs font-medium" :class="metric === 'bad' ? 'bg-white text-gray-900 shadow-theme-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500'" @click="metric = 'bad'">Gone bad</button>
      </div>
    </div>
    @foreach ($tabs as $key => $label)
      @php $m = $heat[$key]; @endphp
      <div x-show="tab === @js($key)" x-cloak class="max-w-full overflow-x-auto px-4 pb-4 pt-3 sm:px-6">
        <table class="w-full min-w-[640px] border-separate border-spacing-1 text-center text-theme-sm">
          <caption class="sr-only">{{ $label }} by amount</caption>
          <thead class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
            <tr><th scope="col" class="py-2 text-left">Amount</th>@foreach ($m['labels'] as $l)<th scope="col" class="py-2">{{ $l }}</th>@endforeach</tr>
          </thead>
          <tbody>
            @foreach ($A::BANDS as $i => $band)
              <tr>
                <th scope="row" class="whitespace-nowrap py-2 pr-2 text-left text-theme-xs font-medium text-gray-700 dark:text-gray-300">{{ $band[2] }}</th>
                @foreach ($m['labels'] as $c => $l)
                  @php $cell = $m['rows'][$i][$c]; @endphp
                  <td class="rounded-lg px-2 py-2 tabular-nums" :style="metric === 'on_time' ? @js($cell['judged'] ? $good($cell['on_time']) : '') : @js($cell['judged'] ? $badHeat($cell['bad']) : '')">
                    @if ($cell['judged'])
                      <span class="block font-semibold text-gray-800 dark:text-white/90" x-text="metric === 'on_time' ? @js($pct($cell['on_time'])) : @js($pct($cell['bad']))"></span>
                      <span class="block text-[11px] text-gray-500 dark:text-gray-400">{{ $cell['judged'] }} {{ $cell['judged'] === 1 ? 'loan' : 'loans' }}</span>
                    @else
                      <span class="text-gray-300 dark:text-gray-600">·</span>
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
            <tr class="border-t">
              <th scope="row" class="py-2 pr-2 text-left text-theme-xs font-semibold text-gray-800 dark:text-white/90">All amounts</th>
              @foreach ($m['labels'] as $c => $l)
                @php $cell = $m['totals'][$c]; @endphp
                <td class="rounded-lg px-2 py-2 font-semibold tabular-nums" :style="metric === 'on_time' ? @js($cell['judged'] ? $good($cell['on_time']) : '') : @js($cell['judged'] ? $badHeat($cell['bad']) : '')">
                  <span class="block text-gray-800 dark:text-white/90" x-text="metric === 'on_time' ? @js($pct($cell['on_time'])) : @js($pct($cell['bad']))"></span>
                  <span class="block text-[11px] font-normal text-gray-500">{{ $cell['judged'] }}</span>
                </td>
              @endforeach
            </tr>
          </tbody>
        </table>
      </div>
    @endforeach
  </section>

  {{-- 3. every borrower's behaviour --}}
  <x-staff.table title="Borrower behaviour" caption="Borrower behaviour profiles" data-table-page="25"
    :headers="['Borrower', ['Loans', 'num'], ['On time', 'num'], ['Days late (avg · max)', 'num'], ['Comfortable up to', 'num'], ['Score', 'num', 'mobile'], ['Suggested limit', 'num'], 'What to know']">
    @forelse ($profiles as $p)
      <tr>
        <td data-sort-value="{{ $p->name }}"><x-staff.person :name="$p->name" :id="$p->user_id" :sub="$p->phone" /></td>
        <td class="num">{{ $p->loans }}</td>
        <td class="num" data-sort-value="{{ $p->on_time_rate ?? -1 }}">{{ $p->on_time_rate === null ? '—' : $pct($p->on_time_rate) }}</td>
        <td class="num" data-sort-value="{{ $p->avg_days_late }}">{{ rtrim(rtrim(number_format($p->avg_days_late, 1), '0'), '.') }} · {{ $p->max_days_late }}</td>
        <td class="num" data-sort-value="{{ $p->comfort_amount }}"><x-staff.money :amount="$p->comfort_amount" /></td>
        <td class="num" data-sort-value="{{ $p->score ?? -1 }}">@if ($p->score === null)<span class="text-gray-400">—</span>@else<x-staff.pill :tone="$p->score >= 80 ? 'green' : ($p->score >= 60 ? 'amber' : 'red')" class="font-outfit">{{ $p->score }}</x-staff.pill>@endif</td>
        <td class="num" data-sort-value="{{ $p->suggested_limit }}"><x-staff.money :amount="$p->suggested_limit" class="font-semibold text-gray-800 dark:text-white/90" /></td>
        <td class="!whitespace-normal" data-filter-value="{{ collect($p->flags)->pluck('text')->implode(', ') ?: 'Nothing' }}">
          <div class="flex max-w-xs flex-wrap gap-1">@forelse ($p->flags as $f)<x-staff.pill :tone="$f['tone']">{{ $f['text'] }}</x-staff.pill>@empty<span class="text-theme-xs text-gray-400">—</span>@endforelse</div>
        </td>
      </tr>
    @empty
      <tr data-table-skip><td colspan="8"><x-staff.empty title="No profiles yet" message="They are worked out every night at 02:15 (analytics:profiles)." /></td></tr>
    @endforelse
  </x-staff.table>
  <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">
    Worked out {{ $computedAt ? \Illuminate\Support\Carbon::parse($computedAt)->diffForHumans() : 'not yet' }}. Score (0–100): on-time history 40, nothing gone bad 20, few rollovers 10, how late 15, last three loans 15.
    Suggested limit: score 80+ gets 1.25 × the largest amount repaid comfortably, 60–79 gets that amount, below 60 or anything overdue or gone bad stays on the starter limit; never above the good-standing limit.
  </p>
@endsection
