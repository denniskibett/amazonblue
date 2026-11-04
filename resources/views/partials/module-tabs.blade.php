{{-- Module tabs (owner, 4 Nov 2026): pages the sidebar groups into one module (Support\NavMenu::modules) read as one
     page: a tabline on top (the same underline tabs as x-staff.tabs) whose tabs swap the page in place, like the
     sidebar (partials.ui.swap). Only tabs this user may open. --}}
@php $module = auth()->check() && auth()->user()->isStaff() ? \App\Support\NavMenu::tabsFor(auth()->user()) : null; @endphp
@if ($module)
  <nav data-module-tabs aria-label="{{ $module[0] }}" class="-mx-6 -mt-6 mb-6 flex gap-6 overflow-x-auto border-b border-gray-200 bg-white px-6 dark:border-gray-800 dark:bg-gray-900 sm:gap-8 print:hidden">
    <span class="hidden shrink-0 items-center border-r border-gray-200 pr-6 text-theme-xs font-medium uppercase tracking-wide text-gray-400 dark:border-gray-800 sm:inline-flex">{{ $module[0] }}</span>
    @foreach ($module['tabs'] as [$label, $route, , $patterns, $badge])
      @php $on = request()->routeIs(...$patterns); @endphp
      <a href="{{ route($route) }}" @if ($on) aria-current="page" @endif
        class="-mb-px inline-flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 py-4 text-sm font-medium {{ $on ? 'border-brand-500 text-brand-500 dark:border-brand-400 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
        {{ $label }}@if ($badge)<span class="rounded-full bg-gray-100 px-2 py-0.5 text-theme-xs font-medium text-gray-700 dark:bg-white/5 dark:text-gray-400">{{ $badge }}</span>@endif
      </a>
    @endforeach
  </nav>
@endif
