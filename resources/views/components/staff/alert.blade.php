{{-- Inline message. Same props as x-az.alert: type info | ok | warn | err (success / warning / error accepted), title --}}
@props(['type' => 'info', 'title' => null])
@php
  $type = ['success' => 'ok', 'warning' => 'warn', 'error' => 'err', 'danger' => 'err'][$type] ?? $type;
  $tone = [
    'ok' => 'border-success-500/30 bg-success-50 text-success-800 dark:bg-success-500/15 dark:text-success-400',
    'warn' => 'border-orange-500/30 bg-orange-50 text-orange-800 dark:bg-orange-500/15 dark:text-orange-400',
    'err' => 'border-error-500/30 bg-error-50 text-error-800 dark:bg-error-500/15 dark:text-error-400',
  ][$type] ?? 'border-brand-500/30 bg-brand-50 text-brand-800 dark:bg-brand-500/15 dark:text-brand-400';
  $path = [
    'ok' => 'M5 12l4 4 10-10',
    'err' => 'M12 8v5M12 16h.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
    'warn' => 'M12 8v5M12 16h.01M12 3l10 18H2z',
  ][$type] ?? 'M12 11v6M12 7h.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z';
@endphp
<div {{ $attributes->class(["flex items-start gap-3 rounded-xl border p-4 text-theme-sm {$tone}"]) }} role="{{ in_array($type, ['err', 'warn']) ? 'alert' : 'status' }}">
  <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $path }}" /></svg>
  <div class="min-w-0">
    @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
    <div class="opacity-90">{{ $slot }}</div>
  </div>
</div>
