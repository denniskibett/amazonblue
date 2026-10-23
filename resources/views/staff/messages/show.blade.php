{{-- One message batch (owner, 23 Oct 2026): read it, send it (that is the approval), and see each recipient's result. --}}
@extends('layouts.app')

@php
  $kinds = \App\Services\Messaging\CollectionNotices::KINDS;
  $statusTone = ['draft' => 'gray', 'sending' => 'blue', 'sent' => 'green', 'cancelled' => 'red'];
  $itemTone = ['pending' => 'gray', 'sent' => 'green', 'failed' => 'red', 'skipped' => 'amber'];
  $first = $items->first();
  $card = 'rounded-2xl border border-gray-200 bg-white px-4 py-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6';
  $owed = $items->sum('owed');
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Batch #' . $b->id . ': ' . $b->subject, 'sub' => ($kinds[$b->kind]['label'] ?? $b->kind) . ' · ' . str_replace(['email', 'sms', ','], ['Email', 'SMS', ' + '], $b->channels), 'metrics' => false,
    'actions' => '<a href="' . route('staff.messages.index') . '" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">All batches</a>'])

  <x-ui.metrics :items="[
    ['label' => 'Recipients', 'value' => (string) $b->recipients, 'icon' => 'users', 'hint' => 'KES ' . number_format($owed, 2) . ' owed'],
    ['label' => 'Sent', 'value' => (string) $b->sent, 'icon' => 'check-circle', 'hint' => $b->sent_at ? \Illuminate\Support\Carbon::parse($b->sent_at)->format('j M, H:i') : 'not yet'],
    ['label' => 'Failed', 'value' => (string) $b->failed, 'icon' => 'x-circle', 'hint' => $b->failed ? 'see the list below' : 'none'],
    ['label' => 'Status', 'value' => ucfirst($b->status), 'icon' => 'send', 'hint' => $b->sent_by_name ? 'sent by ' . $b->sent_by_name : 'made by ' . ($b->created_by_name ?? '—')],
  ]" />

  @if ($b->status === 'draft')
    <section class="{{ $card }} mb-6 grid grid-cols-1 gap-6 lg:grid-cols-5">
      <div class="lg:col-span-2">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Check, then send</h3>
        <p class="mt-1 text-theme-sm text-gray-600 dark:text-gray-400">Read the email on the right; it is what {{ $first->name ?? 'each borrower' }} will get. Every recipient gets their own amount, worked out again when it sends, so anyone who has paid since is skipped.</p>
        @unless ($health['mail']['ok'])<x-staff.alert type="warn" class="mt-4">{{ $health['mail']['note'] }}</x-staff.alert>@endunless
        @if (str_contains($b->channels, 'sms') && ! $health['sms']['ok'])<x-staff.alert type="warn" class="mt-4">{{ $health['sms']['note'] }}</x-staff.alert>@endif
        <form method="POST" action="{{ route('staff.messages.send', $b->id) }}" class="mt-4 space-y-3" data-confirm="Send {{ $b->recipients }} {{ \Illuminate\Support\Str::plural('message', $b->recipients) }} now? This cannot be undone.">
          @csrf
          <label class="flex items-start gap-2 text-theme-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="confirm" value="1" required class="mt-0.5 size-4 accent-brand-500"> I have read the wording and the list, and these {{ $b->recipients }} {{ \Illuminate\Support\Str::plural('borrower', $b->recipients) }} should get it.</label>
          <div class="flex flex-wrap gap-2">
            <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Send to {{ $b->recipients }}</button>
          </div>
        </form>
        <form method="POST" action="{{ route('staff.messages.cancel', $b->id) }}" class="mt-2" data-confirm="Cancel this draft?">
          @csrf
          <button class="text-theme-sm font-medium text-error-600 hover:underline">Cancel this draft</button>
        </form>
      </div>
      <div class="lg:col-span-3">
        @if ($first)
          <div class="mb-3 rounded-xl bg-gray-50 px-4 py-3 text-theme-sm dark:bg-white/[0.03]" x-data="{ sms: '…' }" x-init="fetch(@js(route('staff.messages.preview', ['kind' => $b->kind, 'loan' => $first->loan_id ?? $first->user_id, 'sms' => 1])), {headers: {Accept: 'application/json'}}).then(r => r.json()).then(j => sms = j.sms + ' (' + j.length + ' characters)').catch(() => sms = '')">
            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">SMS</p><p class="mt-1 text-gray-800 dark:text-white/90" x-text="sms"></p>
          </div>
          <iframe src="{{ route('staff.messages.preview', ['kind' => $b->kind, 'loan' => $first->loan_id ?? $first->user_id]) }}" class="h-[560px] w-full rounded-xl border border-gray-200 bg-white dark:border-gray-800" title="Email preview"></iframe>
        @endif
      </div>
    </section>
  @elseif ($b->status === 'sending')
    <x-staff.alert type="info" class="mb-6" title="Sending">Results fill in as each message goes out. This page refreshes every 10 seconds.</x-staff.alert>
    <script>setTimeout(() => location.reload(), 10000);</script>
    <form method="POST" action="{{ route('staff.messages.cancel', $b->id) }}" class="-mt-4 mb-6" data-confirm="Stop sending? Messages already sent stay sent.">@csrf<button class="text-theme-sm font-medium text-error-600 hover:underline">Stop sending the rest</button></form>
  @endif

  <x-staff.table title="Recipients" caption="Batch recipients" :headers="['Borrower', 'Loan', ['Owed', 'num'], 'Days late', ['Result', null, 'mobile']]">
    @foreach ($items as $i)
      <tr>
        <td data-sort-value="{{ $i->name }}"><x-staff.person :name="$i->name" :id="$i->user_id" :sub="$i->email ?: 'no email'" /></td>
        <td>@if ($i->loan_id)<a href="{{ route('loans.show', $i->loan_id) }}" class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90">Loan #{{ $i->loan_id }}</a>@endif<span class="block text-theme-xs text-gray-500 dark:text-gray-400">due {{ $i->due_date ? \Illuminate\Support\Carbon::parse($i->due_date)->format('j M Y') : '—' }}</span></td>
        <td class="num" data-sort-value="{{ $i->owed }}"><x-staff.money :amount="$i->owed" class="text-gray-800 dark:text-white/90" /></td>
        <td data-sort-value="{{ $i->days_late }}">{{ $i->days_late }}</td>
        <td data-filter-value="{{ ucfirst($i->status) }}"><x-staff.pill :tone="$itemTone[$i->status] ?? 'gray'" dot>{{ ucfirst($i->status) }}</x-staff.pill>
          @if ($i->error)<span class="mt-1 block max-w-[240px] whitespace-normal text-theme-xs text-gray-500 dark:text-gray-400">{{ $i->error }}</span>@endif
          @if ($i->sent_at)<span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($i->sent_at)->format('j M, H:i') }}</span>@endif</td>
      </tr>
    @endforeach
  </x-staff.table>
@endsection
