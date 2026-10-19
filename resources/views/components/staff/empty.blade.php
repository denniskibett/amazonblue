{{-- Nothing-here-yet block. Same props as x-az.empty: title, message, slot = actions --}}
@props(['title' => null, 'message' => null])
<div {{ $attributes->class(['flex flex-col items-center gap-2 px-4 py-10 text-center']) }}>
  <span class="mb-1 flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400" aria-hidden="true"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16v12H4zM4 7l2-3h12l2 3M9 11h6" /></svg></span>
  @if ($title)<p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $title }}</p>@endif
  @if ($message)<p class="max-w-md text-theme-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>@endif
  @if (! $slot->isEmpty())<div class="mt-3 flex flex-wrap justify-center gap-2">{{ $slot }}</div>@endif
</div>
