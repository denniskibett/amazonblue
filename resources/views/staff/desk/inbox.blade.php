{{-- My inbox (owner, 22 Oct 2026): TailAdmin mailbox layout. Folders on the left are the queues this
     user's permissions open; the list shows each thing waiting and links to the page where it is done. --}}
@extends('layouts.app')

@php
  $tagTone = [
    'Approval' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
    'Disbursement' => 'bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400',
    'KYC' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500',
    'Business' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500',
    'Request' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
    'Sign-off' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300',
    'Recovery' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
    'Due' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
    'M-Pesa' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500',
  ];
  $initials = fn ($n) => collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $total = collect($folders)->sum('count');
  $current = $folders[$active] ?? null;
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'My inbox', 'sub' => 'Everything waiting for you, ' . auth()->user()->name . '.'])

  @if (empty($folders))
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <x-staff.empty title="Nothing for your role" message="Your role has no work queues. Use the menu to look things up." />
    </div>
  @else
    <div class="flex flex-col gap-5 xl:flex-row">
      {{-- Folders --}}
      <aside class="w-full rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] xl:w-[280px] xl:shrink-0">
        <div class="mb-4 flex items-center justify-between rounded-lg bg-brand-500 px-4 py-3 text-white">
          <span class="flex items-center gap-2 text-theme-sm font-medium"><x-ui.icon name="inbox" class="size-5" /> Waiting for you</span>
          <span class="font-outfit text-lg font-semibold">{{ number_format($total) }}</span>
        </div>
        <p class="mb-2 px-3 text-theme-xs uppercase tracking-wide text-gray-400">Queues</p>
        <nav class="flex flex-col gap-1">
          @foreach ($folders as $key => $f)
            @php $on = $key === $active; @endphp
            <a href="{{ route('staff.inbox', ['folder' => $key]) }}"
               @class(['group flex items-center justify-between rounded-lg px-3 py-2.5 text-theme-sm font-medium',
                       'bg-brand-50 text-brand-600 dark:bg-brand-500/[0.12] dark:text-brand-400' => $on,
                       'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' => ! $on])>
              <span class="flex items-center gap-3">
                <x-ui.icon :name="$f['icon']" class="size-5 {{ $on ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 dark:text-gray-400' }}" />
                {{ $f['label'] }}
              </span>
              <span @class(['text-theme-xs', 'font-semibold' => $f['count'] > 0, 'text-gray-400' => $f['count'] === 0])>{{ $f['count'] }}</span>
            </a>
          @endforeach
        </nav>
      </aside>

      {{-- Messages --}}
      <section class="min-w-0 flex-1 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
               x-data="{ q: '' }">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
          <div class="flex items-center gap-3">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $current['label'] ?? 'Inbox' }}</h3>
            <span class="text-theme-sm text-gray-500 dark:text-gray-400">
              {{ $current ? min($current['items']->count(), $current['count']) . ' of ' . $current['count'] : '' }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <div class="relative">
              <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><x-ui.icon name="search" class="size-4" /></span>
              <input type="search" x-model="q" placeholder="Search this queue"
                     class="h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-9 pr-3 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 sm:w-64" />
            </div>
            <a href="{{ route('staff.inbox', ['folder' => $active]) }}" title="Refresh"
               class="flex size-10 items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5">
              <x-ui.icon name="refresh" class="size-4" />
            </a>
          </div>
        </div>

        @if (! $current || $current['items']->isEmpty())
          <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
            <span class="flex size-12 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500"><x-ui.icon name="check" class="size-6" /></span>
            <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">All clear</p>
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Nothing waiting in this queue.</p>
          </div>
        @else
          <ul class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($current['items'] as $m)
              <li x-show="! q || @js(mb_strtolower($m['who'] . ' ' . $m['subject'] . ' ' . $m['snippet'])).includes(q.toLowerCase())">
                <a href="{{ $m['href'] }}" class="flex items-center gap-4 px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-white/[0.03] sm:px-6">
                  <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-theme-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $initials($m['who']) ?: '?' }}</span>
                  <span class="hidden w-44 shrink-0 truncate text-theme-sm font-medium text-gray-800 dark:text-white/90 md:block">{{ $m['who'] }}</span>
                  <span class="min-w-0 flex-1">
                    <span class="block truncate text-theme-sm text-gray-800 dark:text-white/90 md:hidden">{{ $m['who'] }}</span>
                    <span class="block truncate text-theme-sm">
                      <span class="font-medium text-gray-800 dark:text-white/90">{{ $m['subject'] }}</span>
                      <span class="text-gray-500 dark:text-gray-400"> &middot; {{ $m['snippet'] }}</span>
                    </span>
                  </span>
                  @if ($m['tag'])
                    <span class="hidden shrink-0 rounded-full px-2.5 py-0.5 text-theme-xs font-medium lg:inline {{ $tagTone[$m['tag']] ?? $tagTone['Sign-off'] }}">{{ $m['tag'] }}</span>
                  @endif
                  @if ($m['amount'] !== null)
                    <span class="hidden w-28 shrink-0 text-right text-theme-sm font-medium text-gray-800 dark:text-white/90 sm:block">KES {{ number_format((float) $m['amount']) }}</span>
                  @endif
                  <span class="w-16 shrink-0 text-right text-theme-xs text-gray-500 dark:text-gray-400" title="{{ $m['when']?->format('j M Y H:i') }}">{{ $m['when'] ? ($m['when']->isToday() ? ($m['when']->format('H:i') === '00:00' ? 'Today' : $m['when']->format('H:i')) : $m['when']->format('j M')) : '' }}</span>
                </a>
              </li>
            @endforeach
          </ul>
          @if ($current['count'] > $current['items']->count())
            <div class="border-t border-gray-200 px-6 py-3 text-theme-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
              Showing the oldest {{ $current['items']->count() }}. Open the queue page for the rest.
            </div>
          @endif
        @endif
      </section>
    </div>
  @endif
@endsection
