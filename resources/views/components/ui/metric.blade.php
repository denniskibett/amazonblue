{{-- A compact metric card in the earlier dashboard style (owner, 22 Oct 2026): the figure on top, then the label
     with a small icon on the left and the change badge or a short note on the right. Outfit font, amounts to .00.
     size: md (up to four in a row) or sm (five or six in a row). --}}
@props(['label', 'value', 'icon' => 'chart', 'hint' => null, 'delta' => null, 'tone' => 'neutral', 'href' => null, 'size' => 'md', 'color' => null])
@php
  $badge = ['up' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500', 'down' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
    'warn' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400', 'neutral' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400'][$tone] ?? '';
  $tag = $href ? 'a' : 'div';
  // each kind of figure has its own colour, on the icon chip and the note pill (owner, 22 Oct 2026)
  $accents = [
    'brand' => ['chip' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400', 'pill' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300'],
    'blue' => ['chip' => 'bg-blue-light-50 text-blue-light-500 dark:bg-blue-light-500/15 dark:text-blue-light-400', 'pill' => 'bg-blue-light-50 text-blue-light-700 dark:bg-blue-light-500/15 dark:text-blue-light-300'],
    'green' => ['chip' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500', 'pill' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'],
    'amber' => ['chip' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400', 'pill' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
    'red' => ['chip' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500', 'pill' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400'],
    'purple' => ['chip' => 'bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400', 'pill' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300'],
    'orange' => ['chip' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400', 'pill' => 'bg-orange-50 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300'],
  ];
  $byIcon = ['wallet' => 'brand', 'pie' => 'brand', 'briefcase' => 'brand', 'layers' => 'brand', 'scale' => 'brand', 'list' => 'brand', 'file' => 'brand', 'key' => 'brand',
    'send' => 'blue', 'inbox' => 'blue', 'mail' => 'blue', 'phone' => 'blue', 'refresh' => 'blue', 'search' => 'blue', 'banknote' => 'blue',
    'hand-coins' => 'green', 'check-circle' => 'green', 'check' => 'green', 'user-check' => 'green', 'trending-up' => 'green', 'building' => 'green',
    'coins' => 'amber', 'percent' => 'amber', 'star' => 'amber', 'calendar' => 'amber', 'clock' => 'orange',
    'alert' => 'red', 'trending-down' => 'red', 'x-circle' => 'red', 'shield' => 'purple', 'users' => 'purple', 'user' => 'purple'];
  $accent = $accents[$color ?? ($byIcon[$icon] ?? 'brand')] ?? $accents['brand'];
  $figure = $size === 'sm' ? 'text-base 2xl:text-xl' : 'text-2xl 2xl:text-title-sm';
  // the currency reads smaller than the figure, as on the earlier dashboard
  $shown = preg_replace('/^KES\s*/', '<span class="mr-1 text-xs font-medium text-gray-400">KES</span>', (string) $value);
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'block min-w-0 rounded-2xl border border-gray-200 bg-white font-outfit ' . ($size === 'sm' ? 'p-4' : 'p-5') . ' dark:border-gray-800 dark:bg-white/[0.03]' . ($href ? ' transition hover:border-brand-300 dark:hover:border-brand-800' : '')]) }}>
  <h4 class="truncate font-bold tabular-nums text-gray-800 dark:text-white/90 {{ $figure }}" title="{{ strip_tags((string) $value) }}">{!! $shown !!}</h4>
  <p class="mt-2 flex items-center gap-1.5 text-theme-sm text-gray-700 dark:text-gray-400">
    <span class="flex size-6 shrink-0 items-center justify-center rounded-md {{ $accent['chip'] }}"><x-ui.icon :name="$icon" class="size-3.5" /></span>
    <span class="line-clamp-2 leading-snug" title="{{ $label }}">{{ $label }}</span>
  </p>
  @if (($delta !== null && $delta !== '') || $hint)
    <div class="mt-1.5 flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-1">
      @if ($delta !== null && $delta !== '')
        <span class="flex shrink-0 items-center gap-0.5 rounded-full px-1.5 py-0.5 text-theme-xs font-medium {{ $badge }}">
          @if ($tone === 'up')<x-ui.icon name="arrow-up" class="size-3" />@elseif ($tone === 'down')<x-ui.icon name="arrow-down" class="size-3" />@endif
          {{ $delta }}
        </span>
      @endif
      @if ($hint)<span class="max-w-full rounded-md px-2 py-0.5 text-theme-xs font-medium leading-snug {{ $accent['pill'] }}" title="{{ $hint }}">{{ $hint }}</span>@endif
    </div>
  @endif
</{{ $tag }}>
