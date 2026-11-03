{{-- Sidebar (owner, 22 Oct 2026): grouped by the job staff do, TailAdmin style, every link shown only with the
     permission its page needs. Groups fold away and remember it per browser. --}}
@php
  $is = fn (...$patterns) => request()->routeIs(...$patterns);
  $groups = \App\Support\NavMenu::groups(Auth::user());
@endphp
<aside
  :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
  class="sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-gray-900 lg:static lg:translate-x-0"
>
  <div :class="sidebarToggle ? 'justify-center' : 'justify-between'" class="sidebar-header flex items-center gap-2 pb-7 pt-8">
    <a href="{{ route('dashboard') }}" class="flex items-center">
      <span class="logo" :class="sidebarToggle ? 'hidden' : 'flex items-center'">
        <img class="h-7 w-auto dark:hidden" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures">
        <img class="hidden h-7 w-auto dark:block" src="{{ asset('assets/images/logo/aimzone-horizontal-white.svg') }}" alt="Aimzone Ventures">
      </span>
      <span class="logo-icon" :class="sidebarToggle ? 'lg:block' : 'hidden'">
        <img class="h-9 w-9 dark:hidden" src="{{ asset('assets/images/logo/aimzone-icon-blue.svg') }}" alt="Aimzone Ventures">
        <img class="hidden h-9 w-9 dark:block" src="{{ asset('assets/images/logo/aimzone-icon-white.svg') }}" alt="Aimzone Ventures">
      </span>
    </a>
  </div>

  <div class="no-scrollbar flex flex-col overflow-y-auto pb-10 duration-300 ease-linear">
    <nav x-data="{ closed: $persist([]).as('sidebar-closed') }">
      @foreach ($groups as $title => $items)
        @php $hasActive = collect($items)->contains(fn ($i) => $is(...$i[4])); @endphp
        <div class="mb-5">
          <button type="button" class="mb-3 flex w-full items-center justify-between text-xs uppercase leading-[20px] tracking-wide text-gray-400"
                  @click="closed.includes(@js($title)) ? closed = closed.filter(t => t !== @js($title)) : closed.push(@js($title))"
                  :aria-expanded="! closed.includes(@js($title))">
            <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">{{ $title }}</span>
            <span :class="sidebarToggle ? 'lg:hidden' : ''"><x-ui.icon name="chevron-down" class="size-4 transition-transform" ::class="closed.includes({{ json_encode($title) }}) ? '-rotate-90' : ''" /></span>
            <span :class="sidebarToggle ? 'mx-auto lg:block' : 'hidden'" class="hidden h-px w-6 bg-gray-200 dark:bg-gray-800"></span>
          </button>
          <ul class="flex flex-col gap-1" x-show="{{ $hasActive ? 'true' : 'false' }} || ! closed.includes(@js($title)) || sidebarToggle">
            @foreach ($items as [$label, $route, $icon, $visible, $patterns, $badge])
              @php $active = $is(...$patterns); @endphp
              <li>
                <a href="{{ route($route) }}" class="menu-item group {{ $active ? 'menu-item-active' : 'menu-item-inactive' }}" title="{{ $label }}">
                  <x-ui.icon :name="$icon" class="size-6 shrink-0 {{ $active ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
                  <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ $label }}</span>
                  @if ($badge)
                    <span class="ml-auto rounded-full bg-orange-50 px-2 py-0.5 text-theme-xs font-medium text-orange-600 dark:bg-orange-500/15 dark:text-orange-400" :class="sidebarToggle ? 'lg:hidden' : ''">{{ $badge }}</span>
                  @endif
                </a>
              </li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </nav>
  </div>
</aside>
