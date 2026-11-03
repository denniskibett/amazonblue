{{-- A row of metric cards from an array of ['label', 'value', 'icon', 'hint', 'delta', 'tone', 'href'] (owner, 22 Oct 2026):
     every card stands on its own and the whole set fits one row on a desktop screen. --}}
@props(['items' => [], 'cols' => null])
@php
  $items = array_values(array_filter($items));
  $n = min(max(count($items), 1), 6);
  $grid = [1 => '', 2 => 'grid-cols-2', 3 => 'sm:grid-cols-3', 4 => 'lg:grid-cols-4',   // four cards: one row on a laptop (owner, 3 Nov 2026)
           5 => 'sm:grid-cols-3 xl:grid-cols-5', 6 => 'sm:grid-cols-3 xl:grid-cols-6 [&_.text-title-md]:!text-title-sm'][$n];
  $size = $n >= 5 ? 'sm' : 'md';
@endphp
@if ($items)
  <div {{ $attributes->merge(['class' => "mb-6 grid gap-3 md:gap-4 " . ($n === 1 ? 'grid-cols-1' : 'grid-cols-2') . " {$grid}"]) }}>
    @foreach ($items as $m)
      <x-ui.metric :label="$m['label']" :value="$m['value']" :icon="$m['icon'] ?? 'chart'" :hint="$m['hint'] ?? null" :delta="$m['delta'] ?? null" :tone="$m['tone'] ?? 'neutral'" :href="$m['href'] ?? null" :size="$size" :color="$m['color'] ?? null" />
    @endforeach
  </div>
@endif
