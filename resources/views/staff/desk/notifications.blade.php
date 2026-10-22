{{-- Notifications (owner, 22 Oct 2026): everything that needs this person, newest first, from App\Support\StaffNotifications. --}}
@extends('layouts.app')

@php
  $types = collect($items)->groupBy('type')->map->count();
  $unread = collect($items)->where('unread', true)->count();
  $tones = \App\Support\StaffNotifications::TONES;
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Notifications', 'sub' => $unread ? $unread . ' new since you last looked.' : 'You are up to date.', 'metrics' => false,
    'actions' => $unread ? '<form method="POST" action="' . route('staff.notifications.read') . '">' . csrf_field() . '<button class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Mark all as read</button></form>' : null])

  <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
    <div class="mb-4"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">All notifications</h3></div>
    @if (empty($items))
      <x-staff.empty title="Nothing to tell you" message="New applications, reviews, payments and requests for your role show up here." />
    @else
      <div class="w-full overflow-x-auto">
        <table class="min-w-full" data-table data-table-search="Search notifications" data-table-page="25">
          <thead>
            <tr class="border-y border-gray-100 dark:border-gray-800">
              <th class="py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400">Notification</th>
              <th class="py-3 text-left text-theme-xs font-medium text-gray-500 dark:text-gray-400" data-filter="type">Type</th>
              <th class="py-3 text-right text-theme-xs font-medium text-gray-500 dark:text-gray-400">When</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($items as $n)
              <tr class="{{ $n['unread'] ? 'bg-brand-25 dark:bg-brand-500/[0.04]' : '' }}">
                <td class="py-3 pr-4">
                  <a href="{{ $n['href'] ?? '#' }}" class="flex items-start gap-3">
                    <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full {{ $tones[$n['tone']] ?? $tones['brand'] }}"><x-ui.icon :name="$n['icon']" class="size-4" /></span>
                    <span class="min-w-0">
                      <span class="flex items-center gap-2 text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $n['title'] }}@if ($n['unread'])<span class="size-2 rounded-full bg-brand-500" title="New"></span>@endif</span>
                      <span class="block text-theme-sm text-gray-500 dark:text-gray-400">{{ $n['line'] }}</span>
                    </span>
                  </a>
                </td>
                <td class="py-3 text-theme-sm text-gray-600 dark:text-gray-400" data-filter-value="{{ $n['type'] }}">{{ $n['type'] }}</td>
                <td class="whitespace-nowrap py-3 text-right text-theme-xs text-gray-500 dark:text-gray-400" data-sort-value="{{ $n['at']->timestamp }}" title="{{ $n['at']->format('j M Y H:i') }}">{{ $n['at']->diffForHumans() }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection
