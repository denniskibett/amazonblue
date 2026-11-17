{{-- Where the money is (owner, 17 Nov 2026): a Sankey from what came in, through each facility, to where it is now.
     Drawn with ECharts (owner, 17 Nov 2026; loaded on this page only, public/assets/vendor/echarts-5.5.1.min.js) from
     PartnerAllocation's flows; hover a band or a node for the amount, and the table under it holds the same figures.
     Colours: still out / paid to other investors / paid back / free, validated for colour blindness. --}}
@php
  $a = app(\App\Services\Partners\PartnerAllocation::class)->for($partner);
  $kes = fn ($n) => number_format((float) $n, 2);
  $chart = ['nodes' => collect($a['nodes'])->map(fn ($n) => ['name' => $n['name'], 'kind' => $n['kind'], 'value' => $n['value']])->values(),
            'links' => collect($a['links'])->map(fn ($l) => ['source' => $l['from'], 'target' => $l['to'], 'value' => $l['value'], 'kind' => $l['kind']])->values()];
  $height = max(320, min(640, 44 * collect($a['nodes'])->groupBy('col')->map->count()->max()));
  $legend = ['out' => 'Still out on a loan', 'other' => 'Paid to other investors', 'paid' => 'Paid back to them', 'free' => 'Free in the facility'];
@endphp
<style>
  .az-sankey { --sk-out: #465FFF; --sk-paid: #0E9384; --sk-other: #7A5AF8; --sk-free: #DC6803; --sk-node: #344054; --sk-in: #98A2B3; --sk-text: #344054; --sk-muted: #667085; --sk-surface: #FFFFFF; }
  .dark .az-sankey { --sk-out: #5B73FF; --sk-paid: #0E9384; --sk-other: #7A5AF8; --sk-free: #DC6803; --sk-node: #D0D5DD; --sk-in: #667085; --sk-text: #E4E7EC; --sk-muted: #98A2B3; --sk-surface: #1D2939; }
</style>
<div class="az-sankey px-4 py-4 sm:px-6">
  <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
    <div>
      <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Where their money is</h3>
      <p class="text-theme-sm text-gray-500 dark:text-gray-400">From what came in, through each facility, to where it is now. KES {{ $kes($a['total']) }} in all. Hover a band or a box for the amount.</p>
    </div>
    <ul class="flex flex-wrap gap-x-4 gap-y-1 text-theme-xs text-gray-600 dark:text-gray-300" aria-label="Legend">
      @foreach ($legend as $k => $label)<li class="inline-flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-sm" style="background: var(--sk-{{ $k }})"></span>{{ $label }}</li>@endforeach
    </ul>
  </div>

  @if (! $a['nodes'])
    <x-staff.empty title="No money recorded yet" message="Once they send money and it is lent, this shows where every shilling went." />
  @else
    @once<script defer src="{{ asset('assets/vendor/echarts-5.5.1.min.js') }}"></script>@endonce
    <div class="max-w-full overflow-x-auto">
      <div class="min-w-[720px]" style="height: {{ $height }}px" role="img" aria-label="Sankey chart of where {{ $partner->name }}'s money is; the same figures are in the table below"
        data-sankey
        x-data="{ chart: null, data: @js($chart),
          colours() { const c = getComputedStyle(this.$el); const v = (k) => c.getPropertyValue('--sk-' + k).trim();
            return { out: v('out'), paid: v('paid'), other: v('other'), free: v('free'), source: v('node'), facility: v('node'), in: v('in'), text: v('text'), muted: v('muted'), surface: v('surface') }; },
          draw() {
            if (!window.echarts || this.$el.clientWidth === 0) return false;
            const k = this.colours();
            const kes = (n) => 'KES ' + Number(n).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            this.chart = this.chart || window.echarts.init(this.$el, null, { renderer: 'svg' });
            this.chart.setOption({
              animation: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
              tooltip: { trigger: 'item', backgroundColor: k.surface, borderColor: k.muted, textStyle: { color: k.text },
                formatter: (p) => p.dataType === 'edge' ? p.data.source + ' → ' + p.data.target + '<br><b>' + kes(p.data.value) + '</b>' : p.name + '<br><b>' + kes(p.value) + '</b>' },
              series: [{ type: 'sankey', left: 8, right: 200, top: 8, bottom: 8, nodeWidth: 12, nodeGap: 14, nodeAlign: 'justify', layoutIterations: 32, draggable: false,
                emphasis: { focus: 'adjacency' },
                label: { color: k.text, fontSize: 12, formatter: (p) => p.name + '\n{v|' + kes(p.value) + '}', rich: { v: { color: k.muted, fontSize: 11, lineHeight: 16 } } },
                lineStyle: { curveness: 0.5, opacity: 0.4 },
                data: this.data.nodes.map((n) => ({ name: n.name, value: n.value, itemStyle: { color: k[n.kind] || k.out, borderWidth: 0 } })),
                links: this.data.links.map((l) => ({ source: l.source, target: l.target, value: l.value, lineStyle: { color: k[l.kind] || k.in } })) }],
            });
            return true;
          } }"
        x-init="const tryDraw = () => { if (!draw()) setTimeout(tryDraw, 120) }; tryDraw();
          new ResizeObserver(() => { if (chart) chart.resize(); else draw(); }).observe($el);"></div>
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
