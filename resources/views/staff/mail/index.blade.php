{{-- Staff mail list (owner, 16 Nov 2026): the TailAdmin inbox, read live from the mail server; nothing is stored. --}}
@extends('layouts.app')

@php
  $label = $folders[$folder]['label'] ?? 'Inbox';
  $when = fn ($d) => $d ? ($d->isToday() ? $d->format('H:i') : ($d->isCurrentYear() ? $d->format('j M') : $d->format('j M Y'))) : '';
  $btn = 'flex h-10 items-center justify-center rounded-lg border border-gray-300 px-3 text-gray-500 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5';
  $listArgs = fn ($extra = []) => array_filter(array_merge(['folder' => $folder === 'inbox' ? null : $folder, 'q' => $q ?: null], $extra));
@endphp

@section('content')
  @include('staff.desk._header', ['title' => $label, 'sub' => 'Your ' . auth()->user()->email . ' mailbox.', 'metrics' => false])

  <div class="flex flex-col gap-5 xl:flex-row">
    @include('staff.mail._folders')

    <section class="flex min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
             x-data="{ picked: [], all: @js(array_column($items, 'uid')), more: false,
                       toggleAll() { this.picked = this.picked.length === this.all.length ? [] : [...this.all] } }">
      <form method="POST" action="{{ route('staff.mail.bulk', $folder) }}" x-ref="bulk">
        @csrf
        <input type="hidden" name="page" value="{{ $page }}">
        <input type="hidden" name="q" value="{{ $q }}">
        <input type="hidden" name="action" x-ref="action">
        <template x-for="uid in picked" :key="uid"><input type="hidden" name="uids[]" :value="uid"></template>
      </form>

      {{-- Toolbar --}}
      <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-2">
          <label class="{{ $btn }} cursor-pointer" title="Select all">
            <input type="checkbox" class="size-4 rounded border-gray-300 text-brand-500" :checked="all.length && picked.length === all.length" @change="toggleAll()" aria-label="Select all">
          </label>
          @php $act = fn ($a) => "\$refs.action.value='{$a}'; \$refs.bulk.submit()"; @endphp
          <button type="button" class="{{ $btn }}" title="Mark as read" :disabled="! picked.length" @click="{{ $act('read') }}"><x-ui.icon name="mail" class="size-5" /></button>
          <button type="button" class="{{ $btn }}" title="{{ $folder === 'trash' ? 'Delete for good' : 'Delete' }}" :disabled="! picked.length"
                  @click="{{ $folder === 'trash' ? "if (confirm('Delete the selected messages for good? This cannot be undone.')) { " . $act('delete') . ' }' : $act('delete') }}"><x-ui.icon name="trash" class="size-5" /></button>
          @if ($folder !== 'archive')
            <button type="button" class="{{ $btn }}" title="Archive" :disabled="! picked.length" @click="{{ $act('archive') }}"><x-ui.icon name="archive" class="size-5" /></button>
          @endif
          <div class="relative" @click.outside="more = false">
            <button type="button" class="{{ $btn }}" title="More" @click="more = ! more"><span class="text-lg leading-none">&#8942;</span></button>
            <div x-show="more" x-cloak class="absolute left-0 z-20 mt-2 w-48 rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
              @foreach (array_filter(['unread' => 'Mark as unread', 'spam' => $folder !== 'spam' ? 'Move to Spam' : null, 'inbox' => $folder !== 'inbox' ? 'Move to Inbox' : null]) as $a => $t)
                <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-theme-sm text-gray-700 hover:bg-gray-100 disabled:opacity-40 dark:text-gray-300 dark:hover:bg-white/5"
                        :disabled="! picked.length" @click="{{ $act($a) }}">{{ $t }}</button>
              @endforeach
              <a href="{{ route('staff.mail.index', $listArgs(['page' => $page > 1 ? $page : null])) }}" class="block rounded-lg px-3 py-2 text-theme-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">Refresh</a>
            </div>
          </div>
        </div>
        <form method="GET" action="{{ route('staff.mail.index') }}" class="relative">
          @if ($folder !== 'inbox')<input type="hidden" name="folder" value="{{ $folder }}">@endif
          <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><x-ui.icon name="search" class="size-4" /></span>
          <input type="search" name="q" value="{{ $q }}" placeholder="Search messages..." maxlength="100"
                 class="h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-9 pr-3 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 sm:w-72" />
        </form>
      </div>

      {{-- Messages --}}
      @if (! $items)
        <div class="flex flex-1 flex-col items-center justify-center gap-3 px-6 py-16 text-center">
          <span class="flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400"><x-ui.icon name="inbox" class="size-6" /></span>
          <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $q ? 'No messages match "' . $q . '"' : 'No messages in ' . $label }}</p>
          @if ($q)<a href="{{ route('staff.mail.index', $listArgs(['q' => null])) }}" class="text-theme-sm text-brand-500 hover:underline">Clear the search</a>@endif
        </div>
      @else
        <ul class="flex-1 divide-y divide-gray-100 dark:divide-gray-800">
          @foreach ($items as $m)
            @php $href = route('staff.mail.show', array_filter(['folder' => $folder, 'uid' => $m['uid'], 'q' => $q ?: null])); @endphp
            <li @class(['flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-white/[0.03] sm:px-6', 'bg-gray-50/60 dark:bg-white/[0.02]' => $m['seen']])
                :class="picked.includes({{ $m['uid'] }}) ? '!bg-brand-50 dark:!bg-brand-500/[0.08]' : ''">
              <input type="checkbox" class="size-4 shrink-0 rounded border-gray-300 text-brand-500" value="{{ $m['uid'] }}" x-model.number="picked" aria-label="Select message">
              <span class="shrink-0 {{ $m['flagged'] ? 'text-warning-500' : 'text-gray-300 dark:text-gray-600' }}" title="{{ $m['flagged'] ? 'Starred' : '' }}"><x-ui.icon name="star" class="size-4" /></span>
              <a href="{{ $href }}" class="flex min-w-0 flex-1 items-center gap-4">
                <span @class(['hidden w-44 shrink-0 truncate text-theme-sm md:block', 'font-semibold text-gray-800 dark:text-white/90' => ! $m['seen'], 'text-gray-700 dark:text-gray-300' => $m['seen']])
                      title="{{ $m['from_email'] }}">{{ $folder === 'sent' || $folder === 'drafts' ? 'To: ' . ($m['to'] ?: '(no one)') : ($m['from_name'] ?: '(unknown)') }}</span>
                <span class="min-w-0 flex-1">
                  <span class="block truncate text-theme-sm text-gray-800 dark:text-white/90 md:hidden">{{ $m['from_name'] }}</span>
                  <span class="block truncate text-theme-sm">
                    <span @class(['text-gray-800 dark:text-white/90', 'font-semibold' => ! $m['seen'], 'font-medium' => $m['seen']])>{{ $m['subject'] !== '' ? $m['subject'] : '(no subject)' }}</span>
                    @if ($m['snippet'] !== '')<span class="text-gray-500 dark:text-gray-400"> &mdash; {{ $m['snippet'] }}</span>@endif
                  </span>
                </span>
                @if ($m['answered'])<span class="hidden shrink-0 text-theme-xs text-gray-400 lg:inline" title="Replied">Replied</span>@endif
                <span class="w-16 shrink-0 text-right text-theme-xs text-gray-500 dark:text-gray-400" title="{{ $m['date']?->format('j M Y H:i') }}">{{ $when($m['date']) }}</span>
              </a>
            </li>
          @endforeach
        </ul>
      @endif

      {{-- Footer --}}
      <div class="flex items-center justify-between border-t border-gray-200 px-4 py-4 dark:border-gray-800 sm:px-6">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
          Showing <span class="font-medium text-gray-800 dark:text-white/90">{{ $from }}&ndash;{{ $to }}</span> of <span class="font-medium text-gray-800 dark:text-white/90">{{ number_format($total) }}</span>
        </p>
        <div class="flex items-center gap-2">
          @if ($page > 1)
            <a href="{{ route('staff.mail.index', $listArgs(['page' => $page - 1 > 1 ? $page - 1 : null])) }}" class="{{ $btn }}" title="Newer" aria-label="Newer"><x-ui.icon name="chevron-right" class="size-4 rotate-180" /></a>
          @else
            <span class="{{ $btn }} opacity-40" aria-hidden="true"><x-ui.icon name="chevron-right" class="size-4 rotate-180" /></span>
          @endif
          @if ($page < $pages)
            <a href="{{ route('staff.mail.index', $listArgs(['page' => $page + 1])) }}" class="{{ $btn }}" title="Older" aria-label="Older"><x-ui.icon name="chevron-right" class="size-4" /></a>
          @else
            <span class="{{ $btn }} opacity-40" aria-hidden="true"><x-ui.icon name="chevron-right" class="size-4" /></span>
          @endif
        </div>
      </div>
    </section>
  </div>
@endsection
