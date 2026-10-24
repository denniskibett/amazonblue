{{-- Requests from borrowers (extensions) and partners (funds sent, withdrawals) to decide (Stage 4 Phase 5).
     Loans Due look (owner, 22 Oct 2026): one card per table, the title inside the card, decline notes in a dialog. --}}
@extends('layouts.app')

@php
  $btn = 'inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600';
  $ghost = 'inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Requests', 'sub' => 'What borrowers and partners asked for. The person who asked sees your decision.'])
  @unless ($on)
    <x-staff.alert type="warn" class="mb-6">Stored requests are off (LENDING_REQUESTS_ENABLED): requests still arrive by email only.</x-staff.alert>
  @endunless

  <div class="space-y-6">
    <x-staff.table title="Borrower requests" caption="Open borrower requests" :headers="['Borrower', 'Request', 'Their words', 'Asked', '']">
      @forelse ($borrower as $r)
        @php $asked = \Illuminate\Support\Carbon::parse($r->created_at); @endphp
        <tr>
          <td data-sort-value="{{ $r->borrower }}"><x-staff.person :name="$r->borrower" :id="$r->user_id" :sub="$r->loan_id ? 'Loan #' . $r->loan_id : 'No loan named'" /></td>
          <td data-filter-value="{{ ucfirst($r->type) }}">
            @php $pl = json_decode((string) $r->payload, true) ?: []; @endphp
            <x-staff.pill :tone="['extension' => 'blue', 'topup' => 'purple', 'business_change' => 'amber'][$r->type] ?? 'gray'" dot>{{ ['topup' => 'Top-up', 'business_change' => 'Business change'][$r->type] ?? ucfirst($r->type) }}</x-staff.pill>
            @if ($r->type === 'topup' && ! empty($pl['amount']))<span class="mt-1 block text-theme-xs font-medium text-gray-800 dark:text-white/90">KES {{ number_format((float) $pl['amount'], 2) }}</span>@endif
            @if ($r->loan_id)<a class="mt-1 block text-theme-xs text-brand-500 hover:underline" href="{{ route('loans.show', $r->loan_id) }}">Open loan #{{ $r->loan_id }}</a>@endif
            {{-- a verified business asking to change its details (owner, 24 Oct 2026) --}}
            @if ($r->type === 'business_change' && ($r->business_id ?? null))<a class="mt-1 block text-theme-xs text-brand-500 hover:underline" href="{{ route('business-reviews.show', $r->business_id) }}">Open the business</a>@endif
          </td>
          <td class="max-w-sm !whitespace-normal text-theme-xs text-gray-600 dark:text-gray-400"><span class="line-clamp-2" title="{{ $r->message }}">{{ $r->message ?: '—' }}</span></td>
          <td data-sort-value="{{ $asked->timestamp }}">
            <span class="text-theme-sm text-gray-700 dark:text-gray-300">{{ $asked->diffForHumans(short: true) }}</span>
            <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $asked->format('j M, H:i') }}</span>
          </td>
          <td class="text-right" data-actions>
            <div class="flex justify-end gap-2">
              @if ($r->type === 'topup')
                @can('create disbursements')
                  @php $tu = rescue(fn () => app(\App\Services\Loans\TopUp::class)->plan(\App\Models\Loan::findOrFail($r->loan_id), (float) ($pl['amount'] ?? 0), null, $r->id), null, false); @endphp
                  <x-staff.confirm id="topup-{{ $r->id }}" :action="route('staff.requests.topup', $r->id)" title="Send this top-up?" trigger="Send top-up…" confirm="Record and send" :trigger-class="$btn">
                    @if ($tu)
                      {{ $r->borrower }} gets KES {{ number_format($tu['amount'], 2) }}. The loan becomes KES {{ number_format($tu['principal'], 2) }} (KES {{ number_format($tu['owed'], 2) }} still owed + the top-up), repay KES {{ number_format($tu['new_balance'], 2) }} by {{ \Illuminate\Support\Carbon::parse($tu['due_date'])->format('j M Y') }}. Send the M-Pesa payment first, then record its code here.
                    @else
                      This loan no longer qualifies for a top-up today ({{ app(\App\Services\Loans\TopUp::class)->check(\App\Models\Loan::findOrFail($r->loan_id), null, $r->id)['why'][0] ?? 'see the loan' }}). Decline it with a note.
                    @endif
                    <x-slot:fields>
                      @if ($tu)
                        <x-staff.field name="transaction" id="tu-ref-{{ $r->id }}" label="M-Pesa code of the payment sent" required maxlength="64" />
                        <x-staff.field name="mode" id="tu-mode-{{ $r->id }}" label="Sent by" type="select" :options="['mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank transfer', 'cash' => 'Cash', 'cheque' => 'Cheque']" value="mpesa" />
                      @endif
                    </x-slot:fields>
                  </x-staff.confirm>
                @endcan
              @endif
              @if ($r->type === 'business_change')
                @can('edit users')
                  <x-staff.confirm id="reopen-{{ $r->id }}" :action="route('staff.requests.borrower', $r->id)" title="Reopen the business for changes?" trigger="Reopen for changes" confirm="Reopen" :trigger-class="$btn">
                    {{ $r->borrower }} can edit the business again and resubmit it. It can't borrow until it is approved again.
                    <x-slot:fields>
                      <input type="hidden" name="decision" value="approve">
                      <x-staff.field name="note" id="note-r-{{ $r->id }}" label="Note for the owner (optional)" type="textarea" maxlength="500" />
                    </x-slot:fields>
                  </x-staff.confirm>
                @endcan
              @endif
              @if ($r->type === 'extension')
                @can('rollover loans')
                  <button type="button" x-data @click="$dispatch('extend-request', {id: {{ (int) $r->id }}})" class="{{ $btn }}">Approve and extend…</button>
                @endcan
              @endif
              <x-staff.confirm id="decline-b-{{ $r->id }}" :action="route('staff.requests.borrower', $r->id)" title="Decline this request?" trigger="Decline" confirm="Decline" tone="danger" :trigger-class="$ghost">
                {{ $r->borrower }} sees your note on their account.
                <x-slot:fields>
                  <input type="hidden" name="decision" value="decline">
                  <x-staff.field name="note" id="note-b-{{ $r->id }}" label="Note for the borrower" type="textarea" required maxlength="500" />
                </x-slot:fields>
              </x-staff.confirm>
            </div>
          </td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="5"><x-staff.empty title="Nothing waiting" message="No borrower has an open request." /></td></tr>
      @endforelse
    </x-staff.table>

    <x-staff.table title="Partner requests" caption="Open partner requests" :headers="['Partner', 'Request', ['Amount', 'num'], 'Code / wallet', 'Status', 'Asked', '']">
      @forelse ($partner as $r)
        @php $asked = \Illuminate\Support\Carbon::parse($r->created_at); @endphp
        <tr>
          <td data-sort-value="{{ $r->partner }}"><x-staff.person :name="$r->partner" sub="Partner #{{ $r->partner_id }}" /></td>
          @php $linePay = $r->type === 'withdrawal' && str_starts_with((string) $r->reference, 'LINE-'); @endphp
          <td class="!whitespace-normal" data-filter-value="{{ $r->type === 'funding' ? 'Sent funds' : ($linePay ? 'Line payout' : 'Withdrawal') }}">
            <x-staff.pill :tone="$r->type === 'funding' ? 'green' : 'purple'" dot>{{ $r->type === 'funding' ? 'Sent funds' : ($linePay ? 'Line payout · ' . str_replace('_', ' ', $r->wallet_slug) : 'Withdrawal') }}</x-staff.pill>
            {{-- a closed partner line pays out by itself (owner, 23 Oct 2026) --}}
            @if ($linePay && $r->note)<span class="mt-1 block max-w-xs text-theme-xs text-gray-500 dark:text-gray-400">{{ $r->note }}</span>@endif
            {{-- an online subscription (owner, 24 Oct 2026): the line it opens and the signed copy --}}
            @php $sub = json_decode((string) ($r->payload ?? ''), true) ?: []; @endphp
            @if (($sub['kind'] ?? null) === 'subscription')
              <span class="mt-1 block max-w-xs text-theme-xs text-gray-500 dark:text-gray-400">
                Subscription · {{ $sub['term'] === 'long_term' ? 'long term, ' . $sub['months'] . ' months to ' . \Illuminate\Support\Carbon::parse($sub['maturity'])->format('j M Y') : 'short term' }}
                · <a class="text-brand-600 hover:underline" href="{{ route('partner.subscriptions.staff-file', $r->id) }}" target="_blank" rel="noopener">signed copy</a>
              </span>
            @endif
          </td>
          <td class="num" data-sort-value="{{ (float) $r->amount }}"><x-staff.money :amount="$r->amount" class="text-gray-800 dark:text-white/90" /></td>
          <td class="font-mono text-theme-xs text-gray-600 dark:text-gray-400">{{ $r->reference ?: $r->wallet_slug }}</td>
          <td data-filter-value="{{ $r->status === 'prepared' ? 'Second sign-off' : 'New' }}">
            @if ($r->status === 'prepared')<x-staff.pill tone="amber">Waiting for a second sign-off</x-staff.pill>@else<x-staff.pill tone="blue">New</x-staff.pill>@endif
          </td>
          <td data-sort-value="{{ $asked->timestamp }}">
            <span class="text-theme-sm text-gray-700 dark:text-gray-300">{{ $asked->diffForHumans(short: true) }}</span>
            <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $asked->format('j M, H:i') }}</span>
          </td>
          <td class="text-right" data-actions>
            @if ($r->status === 'pending')
              @can('edit partners')
                <div class="flex justify-end gap-2">
                  @if (($sub['kind'] ?? null) === 'subscription' && ! $r->reference)
                    <span class="self-center text-theme-xs text-gray-500">Waiting for the money</span>
                  @else
                  <form method="POST" action="{{ route('staff.requests.partner', $r->id) }}">@csrf<input type="hidden" name="decision" value="yes">
                    <button class="{{ $btn }}">{{ $r->type === 'funding' ? (($sub['kind'] ?? null) === 'subscription' ? 'Confirm and open line' : 'Confirm received') : 'Prepare payout' }}</button></form>
                  @endif
                  <x-staff.confirm id="decline-p-{{ $r->id }}" :action="route('staff.requests.partner', $r->id)" title="Decline this request?" trigger="Decline" confirm="Decline" tone="danger" :trigger-class="$ghost">
                    {{ $r->partner }} sees why.
                    <x-slot:fields>
                      <input type="hidden" name="decision" value="no">
                      <x-staff.field name="note" id="note-p-{{ $r->id }}" label="Why" type="textarea" required maxlength="500" />
                    </x-slot:fields>
                  </x-staff.confirm>
                </div>
              @endcan
            @endif
          </td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="7"><x-staff.empty title="Nothing waiting" message="No partner has an open request." /></td></tr>
      @endforelse
    </x-staff.table>
  </div>
  @includeIf('staff.desk._extend-modal')
@endsection
