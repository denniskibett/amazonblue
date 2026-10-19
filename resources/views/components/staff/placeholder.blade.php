{{-- Marks a feature that is designed but not built yet. Same props as x-az.placeholder: dependency, title, stage (4) --}}
@props(['dependency', 'title' => null, 'stage' => 4])
<div {{ $attributes->class(['rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-5 text-theme-sm text-gray-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400']) }} role="note">
  <span class="inline-block rounded-full bg-az-ink-blue px-2.5 py-1 text-theme-xs font-semibold text-white">Coming in Stage {{ $stage }}: {{ $dependency }}</span>
  @if ($title)<p class="mt-3 font-semibold text-gray-800 dark:text-white/90">{{ $title }}</p>@endif
  @if (! $slot->isEmpty())<p class="mt-1">{{ $slot }}</p>@endif
</div>
