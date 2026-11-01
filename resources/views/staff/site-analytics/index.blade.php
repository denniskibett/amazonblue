{{-- Site analytics (owner, 1 Nov 2026): visits and pages from our own page_views table (no outside trackers), the
     home → loan funnel, one person's journey and a "How to improve" list worked out from the numbers
     (App\Services\SiteAnalytics). --}}
@extends('layouts.app')

@php
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $card = 'mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]';
  $th = 'whitespace-nowrap py-3 pr-3';
  $pct = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.') . '%';
  $ms = fn ($v) => $v === null ? '—' : ($v >= 1000 ? number_format($v / 1000, 1) . ' s' : (int) $v . ' ms');
  $deviceLabel = ['mobile' => 'Phone', 'tablet' => 'Tablet', 'desktop' => 'Computer'];
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Site analytics', 'sub' => 'How people use the website and the account pages, where they stop on the way to a loan, and what to fix first. Our own records only: no outside trackers, no IP addresses.', 'metrics' => false])

  <form method="GET" class="{{ $card }} grid grid-cols-1 gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
    <input class="{{ $input }} lg:col-span-2" type="date" data-range-end="to" name="from" value="{{ $from->toDateString() }}" title="Dates" placeholder="Dates">
    <input class="{{ $input }}" type="date" name="to" value="{{ $to->toDateString() }}" title="To">
    @if (! empty($person))<input type="hidden" name="user" value="{{ $person->id }}">@endif
    <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white hover:bg-brand-600"><x-ui.icon name="filter" class="size-4" /> Show</button>
  </form>

  @if (! $ready)
    <div class="{{ $card }} p-6 text-theme-sm text-gray-600 dark:text-gray-400">
      The page views table is not there yet. Run <code>php artisan migrate</code>, or on live
      <code>mysql &lt;database&gt; &lt; database/sql/2026_11_01_site_analytics.sql</code>. Visits are recorded from then on.
    </div>
  @else
    @unless ($tracking)
      <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-theme-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">Recording is switched off (ANALYTICS_ENABLED=false). The numbers below are from before it was switched off.</div>
    @endunless

    <x-ui.metrics :items="[
      ['label' => 'Visitors', 'value' => number_format($totals['visitors']), 'icon' => 'users', 'hint' => number_format($totals['visits']) . ' visits'],
      ['label' => 'Signed-in people', 'value' => number_format($totals['users']), 'icon' => 'user-check', 'hint' => 'customers and staff'],
      ['label' => 'Page views', 'value' => number_format($totals['views']), 'icon' => 'eye'],
      ['label' => 'Pages per visit', 'value' => $totals['per_visit'] ?? '—', 'icon' => 'layers', 'hint' => $pct($totals['bounce']) . ' leave after one page'],
      ['label' => 'On a phone', 'value' => $pct($totals['mobile']), 'icon' => 'phone', 'hint' => 'share of visits'],
    ]" />

    {{-- how to improve --}}
    <section class="{{ $card }}">
      <div class="px-4 pt-4 sm:px-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">How to improve</h3>
        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Worked out from these dates with simple rules: a step that loses more than half the people, slow pages, missing pages, one-page visits.</p>
      </div>
      <ul class="divide-y divide-gray-100 px-4 pb-2 pt-2 dark:divide-gray-800 sm:px-6">
        @foreach ($suggestions as $s)
          <li class="flex gap-3 py-3">
            <x-ui.icon :name="['down' => 'alert', 'up' => 'check-circle'][$s['tone']] ?? 'info'" :class="'mt-0.5 size-5 shrink-0 ' . (['down' => 'text-error-500', 'up' => 'text-success-500'][$s['tone']] ?? 'text-brand-500')" />
            <div>
              <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $s['title'] }}</p>
              <p class="mt-0.5 text-theme-sm text-gray-500 dark:text-gray-400">{{ $s['text'] }}</p>
            </div>
          </li>
        @endforeach
      </ul>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      {{-- daily trend --}}
      <section class="{{ $card }} xl:col-span-2">
        <div class="px-4 pt-4 sm:px-6">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Day by day</h3>
          <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Page views and visitors each day.</p>
        </div>
        <div class="max-w-full overflow-x-auto px-2 pb-2">
          <div id="chart-site-daily" class="min-w-[560px]"></div>
        </div>
      </section>

      {{-- devices and roles --}}
      <section class="{{ $card }}">
        <div class="px-4 pt-4 sm:px-6"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Devices</h3></div>
        <div class="space-y-3 px-4 py-4 sm:px-6">
          @php $dv = max(1, $devices->sum()); @endphp
          @forelse ($devices as $device => $n)
            <div>
              <div class="flex justify-between text-theme-sm"><span class="text-gray-700 dark:text-gray-300">{{ $deviceLabel[$device] ?? $device }}</span><span class="tabular-nums text-gray-500">{{ number_format($n) }} · {{ $pct($n / $dv * 100) }}</span></div>
              <div class="mt-1 h-2 rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-2 rounded-full bg-brand-500" style="width: {{ round($n / $dv * 100, 1) }}%"></div></div>
            </div>
          @empty
            <p class="text-theme-sm text-gray-500">No visits in these dates.</p>
          @endforelse
        </div>
        <div class="border-t border-gray-100 px-4 py-4 dark:border-gray-800 sm:px-6">
          <h4 class="mb-2 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Who</h4>
          <table class="w-full text-theme-sm text-gray-700 dark:text-gray-300">
            <caption class="sr-only">Visits by role</caption>
            <thead class="text-theme-xs text-gray-500"><tr><th scope="col" class="pb-1 text-left font-medium">Role</th><th scope="col" class="pb-1 text-right font-medium">People</th><th scope="col" class="pb-1 text-right font-medium">Views</th></tr></thead>
            <tbody>
              @foreach ($roles as $r)
                <tr><td class="py-1">{{ $r->role === 'visitor' ? 'Not signed in' : ucwords(str_replace('-', ' ', $r->role)) }}</td><td class="py-1 text-right tabular-nums">{{ number_format($r->visitors) }}</td><td class="py-1 text-right tabular-nums">{{ number_format($r->views) }}</td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </section>
    </div>

    {{-- funnel --}}
    <section class="{{ $card }}">
      <div class="px-4 pt-4 sm:px-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">From the home page to a loan</h3>
        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">People who opened each step in these dates. "Went on" compares with the step before; under 50% is marked. Loans applied comes from the loans themselves.</p>
      </div>
      @php $top = max(1, collect($funnel)->max('n')); @endphp
      <div class="max-w-full overflow-x-auto px-4 pb-4 pt-3 sm:px-6">
        <table class="w-full min-w-[560px] text-left text-theme-sm text-gray-700 dark:text-gray-300">
          <caption class="sr-only">Funnel steps</caption>
          <thead class="border-y border-gray-100 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
            <tr><th scope="col" class="{{ $th }}">Step</th><th scope="col" class="{{ $th }} w-1/2"></th><th scope="col" class="{{ $th }} text-right">People</th><th scope="col" class="{{ $th }} text-right">Went on</th></tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($funnel as $step)
              <tr>
                <td class="{{ $th }} font-medium text-gray-800 dark:text-white/90">{{ $step['label'] }}</td>
                <td class="py-3 pr-3"><div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-2.5 rounded-full bg-brand-500" style="width: {{ round($step['n'] / $top * 100, 1) }}%"></div></div></td>
                <td class="py-3 pr-3 text-right tabular-nums">{{ number_format($step['n']) }}</td>
                <td class="py-3 text-right">
                  @if ($step['kept'] === null)<span class="text-gray-400">—</span>
                  @else<x-staff.pill :tone="$step['kept'] < 50 ? 'red' : 'green'">{{ $pct($step['kept']) }}</x-staff.pill>@endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>

    {{-- top pages --}}
    <section class="{{ $card }}">
      <div class="px-4 pt-4 sm:px-6"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Most opened pages</h3></div>
      <div class="max-w-full overflow-x-auto px-4 pb-4 pt-3 sm:px-6">
        <table class="w-full min-w-[560px] text-left text-theme-sm text-gray-700 dark:text-gray-300">
          <caption class="sr-only">Top pages</caption>
          <thead class="border-y border-gray-100 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
            <tr><th scope="col" class="{{ $th }}">Page</th><th scope="col" class="{{ $th }} text-right">Views</th><th scope="col" class="{{ $th }} text-right">People</th><th scope="col" class="{{ $th }} text-right">Average load</th></tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($pages as $p)
              <tr>
                <td class="py-3 pr-3"><span class="font-medium text-gray-800 dark:text-white/90">{{ $p->page }}</span><span class="block text-theme-xs text-gray-500">{{ $p->example }}</span></td>
                <td class="py-3 pr-3 text-right tabular-nums">{{ number_format($p->views) }}</td>
                <td class="py-3 pr-3 text-right tabular-nums">{{ number_format($p->visitors) }}</td>
                <td @class(['py-3 text-right tabular-nums', 'font-semibold text-error-600' => $p->avg_ms > config('lending.analytics.slow_ms', 1500)])>{{ $ms($p->avg_ms) }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="py-6 text-center text-gray-500">No page views in these dates.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <section class="{{ $card }}">
        <div class="px-4 pt-4 sm:px-6"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Came from</h3><p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Other sites that sent visits.</p></div>
        <ul class="divide-y divide-gray-100 px-4 pb-3 pt-2 text-theme-sm dark:divide-gray-800 sm:px-6">
          @forelse ($referrers as $r)
            <li class="flex justify-between py-2"><span class="truncate text-gray-700 dark:text-gray-300">{{ $r->host }}</span><span class="tabular-nums text-gray-500">{{ number_format($r->visits) }}</span></li>
          @empty
            <li class="py-2 text-gray-500">None: visitors typed the address or used a bookmark.</li>
          @endforelse
        </ul>
      </section>
      <section class="{{ $card }}">
        <div class="px-4 pt-4 sm:px-6"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Campaigns</h3><p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Links tagged with utm_source / utm_medium / utm_campaign.</p></div>
        <ul class="divide-y divide-gray-100 px-4 pb-3 pt-2 text-theme-sm dark:divide-gray-800 sm:px-6">
          @forelse ($campaigns as $c)
            <li class="flex justify-between gap-3 py-2"><span class="truncate text-gray-700 dark:text-gray-300">{{ $c->source }}@if ($c->medium) · {{ $c->medium }}@endif @if ($c->campaign)<span class="text-gray-500"> · {{ $c->campaign }}</span>@endif</span><span class="tabular-nums text-gray-500">{{ number_format($c->visits) }}</span></li>
          @empty
            <li class="py-2 text-gray-500">No tagged links yet.</li>
          @endforelse
        </ul>
      </section>
      <section class="{{ $card }}">
        <div class="px-4 pt-4 sm:px-6"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Page not found</h3><p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Pages that answered "not found".</p></div>
        <ul class="divide-y divide-gray-100 px-4 pb-3 pt-2 text-theme-sm dark:divide-gray-800 sm:px-6">
          @forelse ($missing as $m)
            <li class="flex justify-between gap-3 py-2"><span class="truncate text-gray-700 dark:text-gray-300">{{ $m->path }}@if ($m->referrer)<span class="block text-theme-xs text-gray-500">from {{ $m->referrer }}</span>@endif</span><span class="tabular-nums text-gray-500">{{ number_format($m->hits) }}</span></li>
          @empty
            <li class="py-2 text-gray-500">None.</li>
          @endforelse
        </ul>
      </section>
    </div>

    {{-- one person's journey --}}
    <section class="{{ $card }}" id="journey">
      <div class="px-4 pt-4 sm:px-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">One person's journey</h3>
        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Pick someone who signed in during these dates to see their recent visits, page by page.</p>
      </div>
      <form method="GET" action="{{ route('staff.site-analytics') }}#journey" class="flex flex-col gap-3 px-4 pt-3 sm:flex-row sm:px-6">
        <input type="hidden" name="from" value="{{ $from->toDateString() }}"><input type="hidden" name="to" value="{{ $to->toDateString() }}">
        <select name="user" class="{{ $input }} sm:max-w-md" onchange="this.form.submit()">
          <option value="">Choose a person ({{ $people->count() }})</option>
          @foreach ($people as $p)
            <option value="{{ $p->id }}" @selected($person?->id === (int) $p->id)>{{ $p->name }}{{ $p->phone ? ' · ' . $p->phone : '' }} — {{ $p->views }} views</option>
          @endforeach
        </select>
        <noscript><button class="h-11 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white">Show</button></noscript>
      </form>
      <div class="px-4 pb-4 pt-3 sm:px-6">
        @if ($person)
          <p class="mb-3 text-theme-sm text-gray-700 dark:text-gray-300"><span class="font-medium text-gray-800 dark:text-white/90">{{ $person->name }}</span> · {{ $person->getRoleNames()->implode(', ') ?: 'no role' }} · last {{ $journey->sum(fn ($s) => $s->pages->count()) }} pages</p>
          <div class="space-y-3">
            @forelse ($journey as $s)
              <details class="rounded-xl border border-gray-200 dark:border-gray-800" @if ($loop->first) open @endif>
                <summary class="flex cursor-pointer flex-wrap items-center gap-x-3 gap-y-1 px-4 py-3 text-theme-sm">
                  <span class="font-medium text-gray-800 dark:text-white/90">{{ $s->start->format('j M Y, H:i') }}</span>
                  <span class="text-gray-500">{{ $s->pages->count() }} page{{ $s->pages->count() === 1 ? '' : 's' }} · {{ $s->start->diffForHumans($s->end, true) }} · {{ $deviceLabel[$s->device] ?? $s->device }}@if ($s->from) · from {{ $s->from }}@endif</span>
                </summary>
                <ol class="divide-y divide-gray-100 border-t border-gray-100 text-theme-sm dark:divide-gray-800 dark:border-gray-800">
                  @foreach ($s->pages as $pv)
                    <li class="flex flex-wrap justify-between gap-2 px-4 py-2">
                      <span><span class="tabular-nums text-gray-500">{{ \Illuminate\Support\Carbon::parse($pv->occurred_at)->format('H:i:s') }}</span>
                        <span class="ml-2 text-gray-800 dark:text-white/90">{{ $pv->route_name ?? $pv->path }}</span>
                        <span class="ml-1 text-theme-xs text-gray-500">{{ $pv->path }}</span>
                        @if ((int) $pv->status === 404)<x-staff.pill tone="red">not found</x-staff.pill>@endif</span>
                      <span class="tabular-nums text-gray-500">{{ $ms($pv->response_ms) }}</span>
                    </li>
                  @endforeach
                </ol>
              </details>
            @empty
              <p class="text-theme-sm text-gray-500">No pages recorded for this person.</p>
            @endforelse
          </div>
        @endif
      </div>
    </section>

    <p class="mb-6 text-theme-xs text-gray-500 dark:text-gray-400">A visit = one browser session; a visitor = a signed-in person, or a guest's session (signing in starts a new session). Not recorded: bots, files and downloads, JSON calls, M-Pesa callbacks and "view as" previews. Kept {{ $keepDays }} days.</p>
  @endif
@endsection

@if ($ready)
  @push('scripts')
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('chart-site-daily');
    if (!el || typeof ApexCharts === 'undefined') return;
    var days = @js($days);
    var dark = document.documentElement.classList.contains('dark') || document.body.classList.contains('dark');
    new ApexCharts(el, {
      chart: { type: 'area', height: 300, fontFamily: 'Outfit, sans-serif', toolbar: { show: false }, zoom: { enabled: false } },
      series: [
        { name: 'Page views', data: days.map(function (d) { return d.views; }) },
        { name: 'Visitors', data: days.map(function (d) { return d.visitors; }) }
      ],
      xaxis: { categories: days.map(function (d) { return d.date; }), type: 'category', tickAmount: Math.min(days.length, 10),
        labels: { rotate: 0, style: { colors: dark ? '#98A2B3' : '#667085' }, formatter: function (v) { if (!v) return v; var p = String(v).split('-'); return p[2] + '/' + p[1]; } } },
      yaxis: { labels: { style: { colors: dark ? '#98A2B3' : '#667085' } }, min: 0, forceNiceScale: true },
      colors: ['#465FFF', '#12B76A'],
      stroke: { curve: 'smooth', width: 2 },
      fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0 } },
      dataLabels: { enabled: false },
      grid: { borderColor: dark ? '#344054' : '#E4E7EC' },
      legend: { position: 'top', horizontalAlign: 'left', labels: { colors: dark ? '#E4E7EC' : '#344054' } },
      tooltip: { theme: dark ? 'dark' : 'light' }
    }).render();
  });
  </script>
  @endpush
@endif
