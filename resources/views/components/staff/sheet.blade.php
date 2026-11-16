{{-- A right-side slide-over (owner, 16 Nov 2026), the look of staff/desk/_extend-modal. Server-rendered: the form is in
     the page, the sheet only shows it. Open with $dispatch('open-sheet', 'payout-14'); Escape, the backdrop or Cancel close it.
     With :action the body and footer sit in one form (POST, @csrf, a hidden _sheet so a failed save opens the sheet again).
     <x-staff.sheet name="terms-14" title="Edit terms" sub="Line #14" :action="route(...)">
       …fields…
       <x-slot:footer><button type="submit" …>Save</button></x-slot:footer>
     </x-staff.sheet> --}}
@props(['name', 'title', 'sub' => null, 'action' => null, 'open' => null, 'width' => 'max-w-xl'])
@php $startOpen = $open ?? (old('_sheet') === $name); @endphp
<div x-data="{ open: @js((bool) $startOpen) }" x-init="$watch('open', v => document.documentElement.classList.toggle('overflow-hidden', v))"
     @open-sheet.window="if ($event.detail === @js($name)) open = true" @close-sheets.window="open = false" @keydown.escape.window="open = false"
     data-sheet="{{ $name }}" class="contents">
  {{-- moved to <body> so a sheet opens from any tab, whatever panel holds it --}}
  <template x-teleport="body"><div class="az-sheet-root">
  <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[99999] bg-black/50" @click="open = false"></div>
  <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
       x-cloak class="az-sheet fixed right-0 top-0 z-[99999] flex h-full w-full {{ $width }} flex-col bg-white shadow-2xl dark:bg-gray-900" role="dialog" aria-modal="true" aria-label="{{ $title }}">
    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5 dark:border-gray-800">
      <div class="min-w-0">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
        @if ($sub)<p class="mt-0.5 text-theme-sm text-gray-500 dark:text-gray-400">{{ $sub }}</p>@endif
      </div>
      <button type="button" @click="open = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800" aria-label="Close"><x-ui.icon name="x" class="size-5" /></button>
    </div>
    @if ($action)<form method="POST" action="{{ $action }}" class="flex min-h-0 flex-1 flex-col" {{ $attributes->only(['x-data', 'id']) }}>@csrf<input type="hidden" name="_sheet" value="{{ $name }}">@endif
      <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6 text-theme-sm text-gray-700 dark:text-gray-300">{{ $slot }}</div>
      <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
        <button type="button" @click="open = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Cancel</button>
        {{ $footer ?? '' }}
      </div>
    @if ($action)</form>@endif
  </div>
  </div></template>
</div>
