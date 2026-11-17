{{-- Where the money is (owner, 17 Nov 2026): a Sankey from what came in, through each facility, to where it is now.
     Server-drawn SVG (PartnerAllocation lays it out); every band and node has a tooltip, and the table under it holds
     the same figures. Colours: still out / paid to other investors / paid back / free, validated for colour blindness. --}}
@php
  $a = app(\App\Services\Partners\PartnerAllocation::class)->for($partner);
  $kes = fn ($n) => number_format((float) $n, 2);
  $W = 960;
  $H = $a['height'] + 8;
  $legend = ['out' => 'Still out on a loan', 'other' => 'Paid to other investors', 'paid' => 'Paid back to them', 'free' => 'Free in the facility'];
@endphp
<style>
  .az-sankey { --sk-out: #465FFF; --sk-paid: #0E9384; --sk-other: #7A5AF8; --sk-free: #DC6803; --sk-node: #344054; --sk-in: #98A2B3; --sk-text: #344054; --sk-muted: #667085; --sk-surface: #FFFFFF; }
  .dark .az-sankey { --sk-out: #5B73FF; --sk-paid: #0E9384; --sk-other: #7A5AF8; --sk-free: #DC6803; --sk-node: #D0D5DD; --sk-in: #667085; --sk-text: #E4E7EC; --sk-muted: #98A2B3; --sk-surface: #1D2939; }
  .az-sankey path.sk-band { opacity: .38; transition: opacity .15s }
  .az-sankey path.sk-band:hover, .az-sankey path.sk-band:focus { opacity: .7; outline: none }
  .az-sankey text { font-size: 12px; fill: var(--sk-text); paint-order: stroke; stroke: var(--sk-surface); stroke-width: 3px; stroke-linejoin: round }
  .az-sankey text.sk-sub { fill: var(--sk-muted); font-size: 11px }
  @media (prefers-reduced-motion: reduce) { .az-sankey path.sk-band { transition: none } }
</style>
<div class="az-sankey px-4 py-4 sm:px-6">
  <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
    <div>
      <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Where their money is</h3>
      <p class="text-theme-sm text-gray-500 dark:text-gray-400">From what came in, through each facility, to where it is now. KES {{ $kes($a['total']) }} in all. Hover a band for the amount.</p>
    </div>
    <ul class="flex flex-wrap gap-x-4 gap-y-1 text-theme-xs text-gray-600 dark:text-gray-300" aria-label="Legend">
      @foreach ($legend as $k => $label)<li class="inline-flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-sm" style="background: var(--sk-{{ $k }})"></span>{{ $label }}</li>@endforeach
    </ul>
  </div>

  @if (! $a['nodes'])
    <x-staff.empty title="No money recorded yet" message="Once they send money and it is lent, this shows where every shilling went." />
  @else
    <div class="max-w-full overflow-x-auto" tabindex="0" aria-label="Where their money is, as a chart">
      <svg viewBox="0 0 {{ $W }} {{ $H }}" class="min-w-[720px] w-full" role="img" aria-label="Sankey chart of where {{ $partner->name }}'s money is; the same figures are in the table below">
        @foreach ($a['links'] as $l)
          <path class="sk-band" d="{{ $l['d'] }}" tabindex="0" style="fill: var(--sk-{{ $l['kind'] === 'in' ? 'in' : $l['kind'] }})"><title>{{ $l['from'] }} → {{ $l['to'] }}: KES {{ $kes($l['value']) }}</title></path>
        @endforeach
        @foreach ($a['nodes'] as $n)
          @php $fill = in_array($n['kind'], ['source', 'facility'], true) ? 'var(--sk-node)' : 'var(--sk-' . $n['kind'] . ')'; @endphp
          <rect x="{{ $n['x'] }}" y="{{ $n['y'] }}" width="{{ $n['w'] }}" height="{{ $n['h'] }}" rx="2" style="fill: {{ $fill }}"><title>{{ $n['name'] }}: KES {{ $kes($n['value']) }}</title></rect>
          @php
            $mid = $n['y'] + $n['h'] / 2;
            [$tx, $anchor] = $n['col'] === 0 ? [$n['x'] - 8, 'end'] : [$n['x'] + $n['w'] + 8, 'start'];
          @endphp
          <text x="{{ $tx }}" y="{{ $mid - 2 }}" text-anchor="{{ $anchor }}">{{ \Illuminate\Support\Str::limit($n['name'], 34) }}</text>
          <text class="sk-sub" x="{{ $tx }}" y="{{ $mid + 12 }}" text-anchor="{{ $anchor }}">KES {{ $kes($n['value']) }}</text>
        @endforeach
      </svg>
    </div>

    <details class="mt-3">
      <summary class="cursor-pointer text-theme-sm font-medium text-brand-500">The figures as a table</summary>
      <div class="mt-2 max-w-full overflow-x-auto">
        <table class="w-full text-theme-sm">
          <caption class="sr-only">Where {{ $partner->name }}'s money is</caption>
          <thead class="bg-gray-50 text-left text-theme-xs font-medium text-gray-500 dark:bg-white/[0.02] dark:text-gray-400">
            <tr><th class="py-2 pl-3 pr-4">From</th><th class="py-2 pr-4">To</th><th class="py-2 pr-3 text-right">KES</th></tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($a['links'] as $l)
              <tr><td class="py-2 pl-3 pr-4">{{ $l['from'] }}</td><td class="py-2 pr-4">{{ $l['to'] }}</td><td class="py-2 pr-3 text-right font-outfit tabular-nums">{{ $kes($l['value']) }}</td></tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </details>
  @endif
</div>
