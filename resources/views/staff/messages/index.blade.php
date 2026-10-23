{{-- Email and SMS manager (owner, 23 Oct 2026): delivery status, the three kinds of notice, and every batch sent. --}}
@extends('layouts.app')

@php
  $kinds = \App\Services\Messaging\CollectionNotices::KINDS;
  $statusTone = ['draft' => 'gray', 'sending' => 'blue', 'sent' => 'green', 'cancelled' => 'red'];
  $card = 'rounded-2xl border border-gray-200 bg-white px-4 py-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6';
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Messages', 'sub' => 'Reminders and collection notices go out in batches you check first: pick who gets it, read the exact email, then send.', 'metrics' => false])

  {{-- can we deliver? --}}
  <section class="mb-6 grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4" aria-label="Delivery status">
    @foreach (['mail' => ['Email', 'mail'], 'sms' => ['SMS', 'phone'], 'queue' => ['Sending queue', 'refresh'], 'auto' => ['Automatic notices', 'bell']] as $key => [$label, $icon])
      @php $h = $health[$key]; @endphp
      <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
        <div class="flex items-start justify-between gap-2">
          <p class="flex items-center gap-1.5 text-theme-xs font-medium text-gray-500 dark:text-gray-400"><x-ui.icon :name="$icon" class="size-3.5" /> {{ $label }}</p>
          <x-staff.pill :tone="$h['ok'] ? 'green' : 'amber'" dot>{{ $h['ok'] ? 'Ready' : 'Not set up' }}</x-staff.pill>
        </div>
        <p class="mt-2 font-outfit text-lg font-semibold text-gray-800 dark:text-white/90">{{ $h['value'] }}@if ($key === 'queue' && ($h['pending'] || $h['failed']))<span class="ml-2 text-theme-xs font-normal text-gray-500">{{ $h['pending'] }} waiting · {{ $h['failed'] }} failed</span>@endif</p>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">{{ $h['note'] }}@if ($key === 'mail' && $h['from']) From {{ $h['from'] }}.@endif</p>
      </div>
    @endforeach
  </section>

  {{-- start a batch --}}
  <section class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3" aria-label="Start a batch">
    @foreach ($kinds as $kind => $k)
      <div class="{{ $card }} flex flex-col">
        <div class="flex items-center justify-between gap-2">
          <x-staff.pill :tone="$k['tone']" dot>{{ $k['label'] }}</x-staff.pill>
          <span class="font-outfit text-2xl font-bold text-gray-800 dark:text-white/90">{{ $counts[$kind] ?? 0 }}</span>
        </div>
        <p class="mt-3 flex-1 text-theme-sm text-gray-600 dark:text-gray-400">{{ $k['about'] }}</p>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">{{ $counts[$kind] ?? 0 }} {{ \Illuminate\Support\Str::plural('borrower', $counts[$kind] ?? 0) }} would get it now (not sent one in the last 3 days, with an email).</p>
        <a href="{{ route('staff.messages.create', ['kind' => $kind]) }}" class="mt-4 inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Choose recipients</a>
      </div>
    @endforeach
  </section>

  <x-staff.table title="Batches" caption="Message batches" :headers="['Batch', 'Channels', ['Sent · failed', 'num', 'mobile'], 'Status', 'Made by', 'Sent by', '']">
    @forelse ($batches as $b)
      <tr>
        <td data-sort-value="{{ $b->id }}">
          <a href="{{ route('staff.messages.show', $b->id) }}" class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90">#{{ $b->id }} · {{ $b->subject }}</a>
          <span class="mt-1 block"><x-staff.pill :tone="$kinds[$b->kind]['tone'] ?? 'gray'">{{ $kinds[$b->kind]['label'] ?? $b->kind }}</x-staff.pill></span>
        </td>
        <td>{{ str_replace(['email', 'sms', ','], ['Email', 'SMS', ' + '], $b->channels) }}</td>
        <td class="num" data-sort-value="{{ $b->sent }}">{{ $b->sent }} of {{ $b->recipients }}@if ($b->failed)<span class="block text-theme-xs text-error-600">{{ $b->failed }} failed</span>@endif</td>
        <td data-filter-value="{{ ucfirst($b->status) }}"><x-staff.pill :tone="$statusTone[$b->status] ?? 'gray'" dot>{{ ucfirst($b->status) }}</x-staff.pill></td>
        <td>{{ $b->created_by_name ?? '—' }}<span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($b->created_at)->format('j M, H:i') }}</span></td>
        <td>{{ $b->sent_by_name ?? '—' }}@if ($b->sent_at)<span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($b->sent_at)->format('j M, H:i') }}</span>@endif</td>
        <td class="text-right" data-actions><a href="{{ route('staff.messages.show', $b->id) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $b->status === 'draft' ? 'Review and send' : 'Open' }}</a></td>
      </tr>
    @empty
      <tr data-table-skip><td colspan="7"><x-staff.empty title="No batches yet" message="Pick a kind of notice above to choose who gets it." /></td></tr>
    @endforelse
  </x-staff.table>

  @if ($health['recent']->isNotEmpty())
    <section class="{{ $card }} mt-6">
      <h3 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Latest notices in customers' accounts</h3>
      <ul class="divide-y divide-gray-100 text-theme-sm dark:divide-gray-800">
        @foreach ($health['recent'] as $n)
          @php $d = json_decode((string) $n->data, true) ?: []; @endphp
          <li class="flex flex-wrap items-center justify-between gap-2 py-2.5">
            <span><span class="cursor-pointer font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" data-user-peek="{{ $n->user_id }}">{{ $n->name ?? 'Someone' }}</span> <span class="text-gray-500 dark:text-gray-400">· {{ $d['title'] ?? ucfirst(str_replace('_', ' ', $d['event'] ?? 'notice')) }}</span></span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($n->created_at)->diffForHumans() }}</span>
          </li>
        @endforeach
      </ul>
    </section>
  @endif
@endsection
