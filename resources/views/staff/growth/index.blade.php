{{-- Growth (10 Nov 2026): this site's numbers from the Sharet growth hub (App\Services\Growth\GrowthHub), its SEO
     checks, and a small ad center (App\Services\Growth\AdCenter). Read-only. --}}
@extends('layouts.app')

@php
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $card = 'mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]';
  $h3 = 'text-lg font-semibold text-gray-800 dark:text-white/90';
  $sub = 'mt-1 text-theme-sm text-gray-500 dark:text-gray-400';
  $th = 'whitespace-nowrap py-3 pr-3';
  $thead = 'border-y border-gray-100 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400';
  $pct = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.') . '%';
  $states = [
    'not_connected' => ['Not connected', 'Growth reads this site\'s numbers from Sharet with the Sharet API key saved under Integrations › Sharet Pay. Save a key there (it needs the read scope) and this page fills in.', true],
    'unauthorized' => ['Sharet did not accept the key', 'The saved Sharet API key was refused (revoked, expired or mistyped). Paste a current key under Integrations › Sharet Pay.', true],
    'forbidden' => ['This key cannot read Growth', 'The key works but is not allowed to read this site\'s numbers: it needs the read scope, and its Sharet business must own the Sharet Loans site. Ask Sharet to check the key\'s business.', true],
    'unavailable' => ['Growth is resting', 'Sharet\'s growth service is not answering right now. Try again in a few minutes.', false],
    'timeout' => ['Sharet took too long', 'The request to Sharet timed out. Try again in a minute.', false],
    'error' => ['Could not load Growth', 'Sharet answered with something unexpected. Try again later; if it keeps happening, tell the Sharet team.', false],
  ];
  $toneIcon = ['up' => ['check-circle', 'text-success-500'], 'down' => ['alert', 'text-error-500'], 'info' => ['info', 'text-brand-500']];
  $level = ['red' => 'red', 'amber' => 'amber', 'green' => 'green'];
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Growth', 'sub' => 'Visits, conversions and search health for loans.sharet.africa from the Sharet growth hub, and what to advertise next. No cookies and no personal data: the tracker counts anonymous visits.', 'metrics' => false])

  <form method="GET" class="{{ $card }} flex flex-wrap items-center gap-2 p-4" aria-label="Period">
    <span class="mr-2 text-theme-sm text-gray-500 dark:text-gray-400">Period</span>
    @foreach (\App\Services\Growth\GrowthHub::PERIODS as $p)
      <button name="days" value="{{ $p }}" @class([
        'inline-flex h-10 items-center rounded-lg px-4 text-theme-sm font-medium',
        'bg-brand-500 text-white' => $p === $days,
        'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5' => $p !== $days,
      ]) @if ($p === $days) aria-current="true" @endif>Last {{ $p }} days</button>
    @endforeach
    <span class="ml-auto text-theme-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($summary['from'])->format('j M') }} – {{ \Illuminate\Support\Carbon::parse($summary['to'])->format('j M Y') }}</span>
  </form>

  @if ($summary['state'] !== 'ok')
    @php [$stTitle, $stText, $toSettings] = $states[$summary['state']] ?? $states['error']; @endphp
    <div class="{{ $card }} flex gap-3 p-6" role="status">
      <x-ui.icon name="info" class="mt-0.5 size-5 shrink-0 text-brand-500" />
      <div>
        <p class="font-medium text-gray-800 dark:text-white/90">{{ $stTitle }}</p>
        <p class="mt-1 text-theme-sm text-gray-600 dark:text-gray-400">{{ $stText }}</p>
        @if ($summary['message'])<p class="mt-1 text-theme-xs text-gray-500">Sharet said: {{ \Illuminate\Support\Str::limit($summary['message'], 200) }}</p>@endif
        @if ($toSettings && Route::has('staff.integrations') && auth()->user()->canAny(['manage integrations', 'access system settings']))
          <a href="{{ route('staff.integrations') }}" class="mt-3 inline-flex h-10 items-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white hover:bg-brand-600">Open Integrations</a>
        @endif
      </div>
    </div>
  @elseif (! $site)
    <div class="{{ $card }} p-6 text-theme-sm text-gray-600 dark:text-gray-400" role="status">Sharet answered, but has no Growth site for this key yet. Ask Sharet to link the Sharet Loans site to this key's business.</div>
  @else
    @php $t = $site['totals']; @endphp
    <x-ui.metrics :items="[
      ['label' => 'Visits', 'value' => number_format($t['visits']), 'icon' => 'users', 'hint' => 'one visitor, one day'],
      ['label' => 'Page views', 'value' => number_format($t['page_views']), 'icon' => 'eye'],
      ['label' => 'Conversions', 'value' => number_format($t['conversions']), 'icon' => 'check-circle', 'hint' => 'sign-ups, applications, enquiries'],
      ['label' => 'Conversion rate', 'value' => $pct($t['conversion_rate']), 'icon' => 'percent'],
      ['label' => 'Organic / paid', 'value' => $pct($t['organic_share']) . ' / ' . $pct($t['paid_share']), 'icon' => 'trending-up', 'hint' => 'share of visits'],
    ]" />

    @if ($t['visits'] === 0)
      <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-theme-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
        No visits recorded in this period yet. The tracker runs on the live public pages only; numbers appear here a few minutes after the first visits.
      </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <section class="{{ $card }} xl:col-span-2">
        <div class="px-4 pt-4 sm:px-6"><h3 class="{{ $h3 }}">Day by day</h3><p class="{{ $sub }}">Visits, page views and conversions each day.</p></div>
        <div class="max-w-full overflow-x-auto px-2 pb-2"><div id="chart-growth-daily" class="min-w-[560px]"></div></div>
      </section>
      <section class="{{ $card }}">
        <div class="px-4 pt-4 sm:px-6"><h3 class="{{ $h3 }}">Conversions</h3><p class="{{ $sub }}">What people did after landing.</p></div>
        <ul class="divide-y divide-gray-100 px-4 pb-3 pt-2 text-theme-sm dark:divide-gray-800 sm:px-6">
          @forelse ($site['conversions'] as $c)
            <li class="flex justify-between py-2"><span class="text-gray-700 dark:text-gray-300">{{ $c['label'] ?? $c['name'] ?? '—' }}</span><span class="tabular-nums text-gray-500">{{ number_format((int) ($c['count'] ?? 0)) }}</span></li>
          @empty
            <li class="py-2 text-gray-500">No conversions in this period.</li>
          @endforelse
        </ul>
      </section>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <section class="{{ $card }}">
        <div class="px-4 pt-4 sm:px-6"><h3 class="{{ $h3 }}">Channels</h3></div>
        <div class="max-w-full overflow-x-auto px-4 pb-4 pt-3 sm:px-6">
          <table class="w-full text-left text-theme-sm text-gray-700 dark:text-gray-300">
            <caption class="sr-only">Visits by channel</caption>
            <thead class="{{ $thead }}"><tr><th scope="col" class="{{ $th }}">Channel</th><th scope="col" class="{{ $th }} text-right">Visits</th><th scope="col" class="{{ $th }} text-right">Share</th><th scope="col" class="{{ $th }} text-right">Conv.</th><th scope="col" class="{{ $th }} text-right">Rate</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @forelse ($site['channels'] as $c)
                <tr><td class="py-2 pr-3">{{ $c['label'] ?? $c['channel'] ?? '—' }}</td><td class="py-2 pr-3 text-right tabular-nums">{{ number_format((int) ($c['visits'] ?? 0)) }}</td><td class="py-2 pr-3 text-right tabular-nums">{{ $pct($c['share'] ?? null) }}</td><td class="py-2 pr-3 text-right tabular-nums">{{ number_format((int) ($c['conversions'] ?? 0)) }}</td><td class="py-2 text-right tabular-nums">{{ $pct($c['conversion_rate'] ?? null) }}</td></tr>
              @empty
                <tr><td colspan="5" class="py-6 text-center text-gray-500">No visits in this period.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
      <section class="{{ $card }}">
        <div class="px-4 pt-4 sm:px-6"><h3 class="{{ $h3 }}">Sources and campaigns</h3><p class="{{ $sub }}">From utm_source / utm_medium / utm_campaign, or the referring site.</p></div>
        <div class="max-w-full overflow-x-auto px-4 pb-4 pt-3 sm:px-6">
          <table class="w-full text-left text-theme-sm text-gray-700 dark:text-gray-300">
            <caption class="sr-only">Visits by source</caption>
            <thead class="{{ $thead }}"><tr><th scope="col" class="{{ $th }}">Source</th><th scope="col" class="{{ $th }} text-right">Visits</th><th scope="col" class="{{ $th }} text-right">Conv.</th><th scope="col" class="{{ $th }} text-right">Rate</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @forelse ($site['sources'] as $s)
                <tr>
                  <td class="py-2 pr-3">{{ $s['source'] ?? 'direct' }}@if (! empty($s['medium'])) <span class="text-gray-500">/ {{ $s['medium'] }}</span>@endif @if (! empty($s['campaign']))<span class="block text-theme-xs text-gray-500">{{ $s['campaign'] }}</span>@endif</td>
                  <td class="py-2 pr-3 text-right tabular-nums">{{ number_format((int) ($s['visits'] ?? 0)) }}</td><td class="py-2 pr-3 text-right tabular-nums">{{ number_format((int) ($s['conversions'] ?? 0)) }}</td><td class="py-2 text-right tabular-nums">{{ $pct($s['conversion_rate'] ?? null) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="py-6 text-center text-gray-500">No sources in this period.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <section class="{{ $card }}">
      <div class="px-4 pt-4 sm:px-6"><h3 class="{{ $h3 }}">Landing pages</h3><p class="{{ $sub }}">The first page of each visit.</p></div>
      <div class="max-w-full overflow-x-auto px-4 pb-4 pt-3 sm:px-6">
        <table class="w-full min-w-[560px] text-left text-theme-sm text-gray-700 dark:text-gray-300">
          <caption class="sr-only">Landing pages</caption>
          <thead class="{{ $thead }}"><tr><th scope="col" class="{{ $th }}">Page</th><th scope="col" class="{{ $th }} text-right">Visits</th><th scope="col" class="{{ $th }} text-right">Conversions</th><th scope="col" class="{{ $th }} text-right">Rate</th></tr></thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($site['landing_pages'] as $l)
              <tr><td class="py-2 pr-3 font-medium text-gray-800 dark:text-white/90">{{ $l['path'] ?? '—' }}</td><td class="py-2 pr-3 text-right tabular-nums">{{ number_format((int) ($l['visits'] ?? 0)) }}</td><td class="py-2 pr-3 text-right tabular-nums">{{ number_format((int) ($l['conversions'] ?? 0)) }}</td><td class="py-2 text-right tabular-nums">{{ $pct($l['conversion_rate'] ?? null) }}</td></tr>
            @empty
              <tr><td colspan="4" class="py-6 text-center text-gray-500">No landing pages in this period.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="{{ $card }}">
      <div class="flex flex-wrap items-start justify-between gap-3 px-4 pt-4 sm:px-6">
        <div>
          <h3 class="{{ $h3 }}">Search health (SEO)</h3>
          <p class="{{ $sub }}">Sharet checks the sitemap pages every morning.@if ($site['seo']['checked_at']) Last checked {{ \Illuminate\Support\Carbon::parse($site['seo']['checked_at'])->timezone('Africa/Nairobi')->format('j M, H:i') }}.@endif @if ($site['seo']['sitemap_urls'] !== null) {{ $site['seo']['sitemap_urls'] }} pages in the sitemap.@endif</p>
        </div>
        <div class="flex gap-2">
          @foreach (['red' => 'To fix', 'amber' => 'To improve', 'green' => 'Good'] as $lv => $lbl)
            <x-staff.pill :tone="$lv">{{ $lbl }}: {{ (int) ($site['seo']['counts'][$lv] ?? 0) }}</x-staff.pill>
          @endforeach
        </div>
      </div>
      <ul class="divide-y divide-gray-100 px-4 pb-3 pt-2 text-theme-sm dark:divide-gray-800 sm:px-6">
        @forelse (array_slice($site['seo']['fix_first'], 0, 10) as $f)
          <li class="flex gap-3 py-2">
            <x-staff.pill :tone="$level[$f['level'] ?? ''] ?? 'gray'">{{ ucfirst($f['level'] ?? 'note') }}</x-staff.pill>
            <span><span class="text-gray-800 dark:text-white/90">{{ $f['text'] ?? '' }}</span><span class="block break-all text-theme-xs text-gray-500">{{ $f['url'] ?? '' }}</span></span>
          </li>
        @empty
          <li class="py-2 text-gray-500">{{ $site['seo']['checked_at'] ? 'Nothing to fix first.' : 'Not checked yet.' }}</li>
        @endforelse
      </ul>
    </section>
  @endif

  {{-- ============ AD CENTER ============ --}}
  <h2 class="mb-4 mt-8 text-xl font-semibold text-gray-800 dark:text-white/90" id="ads">Ad center</h2>

  @if ($site)
    <section class="{{ $card }}">
      <div class="px-4 pt-4 sm:px-6"><h3 class="{{ $h3 }}">What to push</h3><p class="{{ $sub }}">From this period's numbers. Comparisons need {{ \App\Services\Growth\AdCenter::MIN_VISITS }}+ visits and {{ \App\Services\Growth\AdCenter::MIN_CONVERSIONS }}+ conversions.</p></div>
      <ul class="divide-y divide-gray-100 px-4 pb-2 pt-2 dark:divide-gray-800 sm:px-6">
        @foreach ($push['notes'] as $n)
          @php [$ic, $col] = $toneIcon[$n['tone']] ?? $toneIcon['info']; @endphp
          <li class="flex gap-3 py-3">
            <x-ui.icon :name="$ic" :class="'mt-0.5 size-5 shrink-0 ' . $col" />
            <div><p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $n['title'] }}</p><p class="mt-0.5 text-theme-sm text-gray-500 dark:text-gray-400">{{ $n['text'] }}</p></div>
          </li>
        @endforeach
      </ul>
    </section>
  @endif

  <section class="{{ $card }}">
    <div class="px-4 pt-4 sm:px-6">
      <h3 class="{{ $h3 }}">Ready ad copy</h3>
      <p class="{{ $sub }}">Matches what we offer today: logbook loans on motorbikes and e-motorcycles the rider owns, and emergency cash to M-Pesa.</p>
    </div>
    <div class="px-4 pt-3 sm:px-6">
      <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-theme-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
        <p class="font-medium">Every ad must stay true</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">@foreach ($rules as $rule)<li>{{ $rule }}</li>@endforeach</ul>
      </div>
    </div>
    <div class="grid grid-cols-1 gap-4 p-4 sm:p-6 lg:grid-cols-2">
      @foreach ($copy as $key => $ad)
        <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800" data-ad="{{ $key }}">
          <h4 class="font-semibold text-gray-800 dark:text-white/90">{{ $ad['label'] }}</h4>
          @foreach ($ad['parts'] as $part => $value)
            <p class="mt-3 text-theme-xs font-medium uppercase tracking-wide text-gray-500">{{ $part }}</p>
            @if (is_array($value))
              <ul class="mt-1 space-y-0.5 text-theme-sm text-gray-700 dark:text-gray-300">
                @foreach ($value as $line)<li>{{ $line }} @if ($part === 'Headlines' || $part === 'Descriptions')<span class="text-theme-xs text-gray-400">({{ mb_strlen($line) }})</span>@endif</li>@endforeach
              </ul>
            @else
              <p class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">{{ $value }}</p>
            @endif
          @endforeach
          <p class="mt-3 text-theme-xs font-medium uppercase tracking-wide text-gray-500">Link</p>
          <input class="{{ $input }} mt-1 h-10 text-theme-xs" readonly value="{{ $ad['link'] }}" aria-label="{{ $ad['label'] }} link" onclick="this.select()">
        </article>
      @endforeach
    </div>
  </section>

  <section class="{{ $card }}" id="utm">
    <div class="px-4 pt-4 sm:px-6"><h3 class="{{ $h3 }}">Campaign link builder</h3><p class="{{ $sub }}">Tag every ad and post so it shows up under Sources and campaigns. Lowercase, no spaces.</p></div>
    <form method="GET" action="{{ route('staff.growth') }}#utm" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 sm:p-6 lg:grid-cols-3">
      <input type="hidden" name="days" value="{{ $days }}">
      <label class="text-theme-sm text-gray-700 dark:text-gray-300 lg:col-span-3">Page
        <select name="page" class="{{ $input }} mt-1">
          @foreach ($pages as $label => $url)<option value="{{ $url }}" @selected($builder['page'] === $url)>{{ $label }} · {{ $url }}</option>@endforeach
        </select>
      </label>
      <label class="text-theme-sm text-gray-700 dark:text-gray-300">Source <input class="{{ $input }} mt-1" name="source" value="{{ $builder['source'] }}" placeholder="facebook" required></label>
      <label class="text-theme-sm text-gray-700 dark:text-gray-300">Medium <input class="{{ $input }} mt-1" name="medium" value="{{ $builder['medium'] }}" placeholder="paid-social" required></label>
      <label class="text-theme-sm text-gray-700 dark:text-gray-300">Campaign <input class="{{ $input }} mt-1" name="campaign" value="{{ $builder['campaign'] }}" placeholder="boda-logbook-nov" required></label>
      <label class="text-theme-sm text-gray-700 dark:text-gray-300">Content (optional) <input class="{{ $input }} mt-1" name="content" value="{{ $builder['content'] }}" placeholder="video-a"></label>
      <div class="flex items-end"><button class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white hover:bg-brand-600">Build link</button></div>
    </form>
    @if ($built)
      <div class="px-4 pb-6 sm:px-6">
        <p class="text-theme-xs font-medium uppercase tracking-wide text-gray-500">Your link</p>
        <input class="{{ $input }} mt-1" readonly value="{{ $built }}" aria-label="Campaign link" onclick="this.select()" data-built-link>
      </div>
    @elseif ($builderError)
      <p class="px-4 pb-6 text-theme-sm text-error-600 sm:px-6">Choose one of our pages and fill in source, medium and campaign.</p>
    @endif
  </section>
@endsection

@if ($site)
  @push('scripts')
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('chart-growth-daily');
    if (!el || typeof ApexCharts === 'undefined') return;
    var days = @js($site['daily']);
    var dark = document.documentElement.classList.contains('dark') || document.body.classList.contains('dark');
    new ApexCharts(el, {
      chart: { type: 'area', height: 300, fontFamily: 'Outfit, sans-serif', toolbar: { show: false }, zoom: { enabled: false } },
      series: [
        { name: 'Visits', data: days.map(function (d) { return d.visits || 0; }) },
        { name: 'Page views', data: days.map(function (d) { return d.page_views || 0; }) },
        { name: 'Conversions', data: days.map(function (d) { return d.conversions || 0; }) }
      ],
      xaxis: { categories: days.map(function (d) { return d.date; }), type: 'category', tickAmount: Math.min(days.length, 10),
        labels: { rotate: 0, style: { colors: dark ? '#98A2B3' : '#667085' }, formatter: function (v) { if (!v) return v; var p = String(v).split('-'); return p[2] + '/' + p[1]; } } },
      yaxis: { labels: { style: { colors: dark ? '#98A2B3' : '#667085' } }, min: 0, forceNiceScale: true },
      colors: ['#465FFF', '#98A2B3', '#12B76A'],
      stroke: { curve: 'smooth', width: 2 },
      fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0 } },
      dataLabels: { enabled: false },
      grid: { borderColor: dark ? '#344054' : '#E4E7EC' },
      legend: { position: 'top', horizontalAlign: 'left', labels: { colors: dark ? '#E4E7EC' : '#344054' } },
      tooltip: { theme: dark ? 'dark' : 'light' }
    }).render();
  });
  </script>
  @endpush
@endif
