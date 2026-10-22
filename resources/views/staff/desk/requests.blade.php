{{-- Requests from borrowers (extensions) and partners (funds sent, withdrawals) to decide (Stage 4 Phase 5). --}}
@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'Requests', 'sub' => 'What borrowers and partners asked for. The person who asked sees your decision.'])
  @unless ($on)
    <x-staff.alert type="warn" class="mb-6">Stored requests are off (LENDING_REQUESTS_ENABLED): requests still arrive by email only.</x-staff.alert>
  @endunless

  <section class="mb-8">
    <h2 class="mb-3 text-theme-lg font-semibold text-gray-800 dark:text-white/90">Borrowers ({{ $borrower->count() }})</h2>
    <x-staff.table caption="Open borrower requests" :headers="['Asked', 'Borrower', 'Loan', 'What', 'Their words', 'Decision']">
      @forelse ($borrower as $r)
        <tr>
          <td>{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('j M, H:i') }}</td>
          <td>{{ $r->borrower }}</td>
          <td>@if ($r->loan_id)<a class="text-brand-500 hover:underline" href="{{ route('loans.show', $r->loan_id) }}">#{{ $r->loan_id }}</a>@endif</td>
          <td>{{ ucfirst($r->type) }}</td>
          <td class="max-w-xs text-theme-xs text-gray-600 dark:text-gray-400">{{ $r->message }}</td>
          <td>
            <div class="flex flex-wrap items-center gap-2">
              @if ($r->type === 'extension')
                @can('rollover loans')
                  <button type="button" x-data @click="$dispatch('extend-request', {id: {{ (int) $r->id }}})" class="rounded-lg bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white hover:bg-brand-600">Approve and extend…</button>
                @endcan
              @endif
              <form method="POST" action="{{ route('staff.requests.borrower', $r->id) }}" class="flex items-center gap-2">@csrf<input type="hidden" name="decision" value="decline">
                <input name="note" required maxlength="500" placeholder="Note for the borrower" class="h-9 w-48 rounded-lg border border-gray-300 px-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900" aria-label="Note">
                <button class="rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium dark:border-gray-700">Decline</button></form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-theme-sm text-gray-500">Nothing waiting.</td></tr>
      @endforelse
    </x-staff.table>
  </section>

  <section>
    <h2 class="mb-3 text-theme-lg font-semibold text-gray-800 dark:text-white/90">Partners ({{ $partner->count() }})</h2>
    <x-staff.table caption="Open partner requests" :headers="['Asked', 'Partner', 'What', ['Amount', 'num'], 'Code / wallet', 'Status', 'Decision']">
      @forelse ($partner as $r)
        <tr>
          <td>{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('j M, H:i') }}</td>
          <td>{{ $r->partner }}</td>
          <td>{{ $r->type === 'funding' ? 'Sent funds' : 'Withdrawal' }}</td>
          <td class="num"><x-staff.money :amount="$r->amount" /></td>
          <td class="font-mono text-theme-xs">{{ $r->reference ?: $r->wallet_slug }}</td>
          <td>{{ $r->status === 'prepared' ? 'Waiting for a second sign-off' : 'New' }}</td>
          <td>
            @if ($r->status === 'pending')
              @can('edit partners')
                <div class="flex flex-wrap items-center gap-2">
                  <form method="POST" action="{{ route('staff.requests.partner', $r->id) }}">@csrf<input type="hidden" name="decision" value="yes">
                    <button class="rounded-lg bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white hover:bg-brand-600">{{ $r->type === 'funding' ? 'Confirm received' : 'Prepare payout' }}</button></form>
                  <form method="POST" action="{{ route('staff.requests.partner', $r->id) }}" class="flex items-center gap-2">@csrf<input type="hidden" name="decision" value="no">
                    <input name="note" required maxlength="500" placeholder="Why" class="h-9 w-40 rounded-lg border border-gray-300 px-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900" aria-label="Note">
                    <button class="rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium dark:border-gray-700">Decline</button></form>
                </div>
              @endcan
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-theme-sm text-gray-500">Nothing waiting.</td></tr>
      @endforelse
    </x-staff.table>
  </section>
  @include('staff.desk._extend-modal')
@endsection
