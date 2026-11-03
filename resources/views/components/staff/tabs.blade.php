{{-- Underline tabs ("tablines", owner, 3 Nov 2026) joined to the table they switch: one white card with the tab line on
     top and the open section under it (the section's own card loses its border so the two read as one). The open tab is
     kept in the address (?tab=). Optional slot "tools" sits on the right of the tab line (filters, a date).
     <x-staff.tabs :tabs="['open' => ['Waiting', 3], 'done' => 'Decided']"> <x-staff.tab name="open">…</x-staff.tab> … </x-staff.tabs> --}}
@props(['tabs' => [], 'active' => null])
@php
  $keys = array_keys($tabs);
  $first = $active && in_array($active, $keys, true) ? $active : ($keys[0] ?? '');
  $asked = request()->query('tab');
  $start = in_array($asked, $keys, true) ? $asked : $first;
@endphp
@once<style>
  /* a section inside the tabs card: no card of its own, so the tab line and its table are one */
  .az-tabs [role="tabpanel"] > .rounded-2xl, .az-tabs [role="tabpanel"] > div > .rounded-2xl, .az-tabs [role="tabpanel"] > section { border: 0 !important; border-radius: 0 !important; background: transparent !important; box-shadow: none !important; }
  .az-tabs [role="tabpanel"] > * + * { border-top: 1px solid rgb(242 244 247); }
  .dark .az-tabs [role="tabpanel"] > * + * { border-top-color: rgb(31 41 55); }
  @media print { .az-tabs [role="tabpanel"] { display: block !important; } .az-tabs nav[role="tablist"] { display: none; } }
</style>@endonce
<div x-data="{ tab: @js($start), go(t) { this.tab = t; const u = new URL(location.href); u.searchParams.set('tab', t); history.replaceState(null, '', u); } }"
  {{ $attributes->class(['az-tabs overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]']) }}>
  <div class="flex flex-col gap-3 border-b border-gray-200 px-4 dark:border-gray-800 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
    <nav class="-mb-px flex gap-6 overflow-x-auto sm:gap-8" role="tablist">
      @foreach ($tabs as $key => $t)
        @php [$label, $count] = is_array($t) ? [$t[0], $t[1] ?? null] : [$t, null]; @endphp
        <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" @click="go(@js($key))"
          :aria-selected="tab === @js($key) ? 'true' : 'false'" aria-selected="{{ $key === $start ? 'true' : 'false' }}"
          :class="tab === @js($key) ? 'border-brand-500 text-brand-500 dark:border-brand-400 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
          class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 py-4 text-sm font-medium {{ $key === $start ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500' }}">
          {{ $label }}
          @if ($count !== null)<span class="rounded-full bg-gray-100 px-2 py-0.5 text-theme-xs font-medium text-gray-700 dark:bg-white/5 dark:text-gray-400">{{ $count }}</span>@endif
        </button>
      @endforeach
    </nav>
    @isset($tools)<div class="flex flex-wrap items-center gap-3 pb-3 lg:py-3 print:hidden">{{ $tools }}</div>@endisset
  </div>
  {{ $slot }}
</div>
