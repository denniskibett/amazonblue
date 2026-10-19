{{-- Steps of a journey. Same item shape as x-az.timeline (LoanProgress): label, detail, date, done, current, tone=red --}}
@props(['items' => []])
<ol {{ $attributes->class(['relative space-y-5']) }}>
  @foreach ($items as $step)
    @php
      $red = ($step['tone'] ?? null) === 'red';
      $done = (bool) ($step['done'] ?? false);
      $current = ! $done && ($step['current'] ?? false);
      $date = ! empty($step['date']) ? \Illuminate\Support\Carbon::parse($step['date'])->format('j M Y') : null;
    @endphp
    <li class="relative flex gap-3">
      @unless ($loop->last)
        <span @class(['absolute left-[13px] top-8 -bottom-4 w-0.5', 'bg-az-sage-d' => $done && ! $red, 'bg-gray-200 dark:bg-gray-700' => ! $done || $red]) aria-hidden="true"></span>
      @endunless
      <span @class([
        'relative z-1 flex h-7 w-7 flex-none items-center justify-center rounded-full text-theme-xs font-semibold',
        'bg-az-rose-d text-white' => $red,
        'bg-az-sage-d text-white' => $done && ! $red,
        'bg-brand-500 text-white ring-4 ring-brand-100 dark:ring-brand-500/20' => $current && ! $red,
        'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' => ! $done && ! $current && ! $red,
      ])>@if ($done && ! $red)<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>@else{{ $loop->iteration }}@endif</span>
      <div class="min-w-0 pt-0.5">
        <p @class(['text-theme-sm font-medium', 'text-az-rose-d' => $red, 'text-gray-800 dark:text-white/90' => ! $red])>{{ $step['label'] ?? '' }}@if ($date) <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $date }}</span>@endif</p>
        @if (! empty($step['detail']))<p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $step['detail'] }}</p>@endif
      </div>
    </li>
  @endforeach
</ol>
