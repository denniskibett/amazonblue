{{-- Teller · end of day. What one teller entered on one day (created_by, else the audit trail), real cash vs book entries.
     Read-only and printable; closing the day (cash-up) is Stage 4. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]';
  $kind = fn ($row) => \App\Services\CustomerWallets::kindOf($row);
  $net = $totals['received_cash'] - $totals['disbursed_cash'];
@endphp

@section('content')
  <style>@media print { aside, header, .print\:hidden { display: none !important; } main, body { background: #fff !important; } }</style>
  @include('staff.desk._header', [
    'title' => 'End of day',
    'sub' => ($teller->name ?? 'Teller') . ' · ' . $date->format('l j F Y'),
    'actions' => '<button type="button" onclick="window.print()" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Print</button>',
  ])

  <form method="GET" class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-4 print:hidden dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
    <x-staff.field name="date" label="Day" type="date" :value="$date->toDateString()" />
    @if ($tellers->isNotEmpty())
      <x-staff.field name="teller" label="Staff member" type="select" :options="$tellers->pluck('name', 'id')->all()" :value="$teller->id ?? null" />
    @endif
    <button class="h-11 rounded-lg bg-brand-500 px-5 text-theme-sm font-medium text-white hover:bg-brand-600">Show</button>
  </form>

  @php
    $K = fn ($v) => \App\Support\PageMetrics::kes((float) $v);
    $modes = collect($byMode)->map(fn ($v, $m) => ucfirst(str_replace('_', ' ', $m)) . ' ' . number_format((float) $v, 2))->implode(' · ');
  @endphp
  <x-ui.metrics :cols="4" :items="[
    ['label' => 'Cash received', 'value' => $K($totals['received_cash']), 'icon' => 'hand-coins', 'hint' => 'of ' . number_format((float) $totals['received_all'], 2) . ' recorded'],
    ['label' => 'Cash paid out', 'value' => $K($totals['disbursed_cash']), 'icon' => 'send', 'hint' => 'of ' . number_format((float) $totals['disbursed_all'], 2) . ' recorded'],
    ['label' => 'Net cash', 'value' => $K($net), 'icon' => 'scale', 'hint' => 'received less paid out', 'tone' => $net < 0 ? 'down' : 'neutral'],
    ['label' => 'Cash in by mode', 'value' => (string) count($byMode) . (count($byMode) === 1 ? ' mode' : ' modes'), 'icon' => 'phone', 'hint' => $modes ?: 'none today'],
  ]" />

  @php
    $modeName = fn ($m) => ['mpesa' => 'M-Pesa', 'cash' => 'Cash', 'bank' => 'Bank', 'cheque' => 'Cheque'][$m] ?? ($m ? ucfirst(str_replace('_', ' ', $m)) : '—');
  @endphp
  <div class="mb-6 space-y-6">
    <x-staff.table title="Payments received" caption="Payments received" :headers="['Borrower', 'Payment', 'Mode · reference', 'Kind', ['Amount', 'num']]">
      @forelse ($repayments as $p)
        @php $k = $kind($p); @endphp
        <tr @class(['opacity-60' => $p->deleted_at])>
          <td data-sort-value="{{ $p->loan->user->name ?? '' }}"><x-staff.person :user="$p->loan->user ?? null" :sub="'Loan #' . $p->loan_id" /></td>
          <td><span class="font-medium text-gray-800 dark:text-white/90">#{{ $p->id }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ optional($p->created_at)->format('H:i') }}</span></td>
          <td data-filter-value="{{ $modeName($p->mode) }}">{{ $modeName($p->mode) }}<span class="block font-mono text-theme-xs text-gray-500 dark:text-gray-400">{{ $p->transaction ?: '—' }}</span></td>
          <td data-filter-value="{{ $p->deleted_at ? 'Reversed' : ($k === 'cash' ? 'Cash' : 'Book') }}">
            @if ($p->deleted_at)<x-staff.pill tone="red">Reversed</x-staff.pill>
            @elseif ($k === 'cash')<x-staff.pill tone="green" dot>Cash</x-staff.pill>
            @else<x-staff.pill tone="gray">Book · {{ str_replace('_', ' ', $k) }}</x-staff.pill>@endif
          </td>
          <td class="num" data-sort-value="{{ (float) $p->amount }}"><x-staff.money :amount="$p->amount" :tone="$k === 'cash' && ! $p->deleted_at ? 'in' : 'muted'" :class="$p->deleted_at ? 'line-through' : ''" /></td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="5"><x-staff.empty title="No payments entered" message="Nothing recorded by this person on this day." /></td></tr>
      @endforelse
    </x-staff.table>

    <x-staff.table title="Disbursements" caption="Disbursements" :headers="['Borrower', 'Disbursement', 'Mode · reference', 'Kind', ['Amount', 'num'], ['Sent (net)', 'num']]">
      @forelse ($disbursements as $d)
        @php $k = $kind($d); @endphp
        <tr>
          <td data-sort-value="{{ $d->loan->user->name ?? '' }}"><x-staff.person :user="$d->loan->user ?? null" :sub="'Loan #' . $d->loan_id" /></td>
          <td><span class="font-medium text-gray-800 dark:text-white/90">#{{ $d->id }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ optional($d->created_at)->format('H:i') }}</span></td>
          <td data-filter-value="{{ $modeName($d->mode) }}">{{ $modeName($d->mode) }}<span class="block font-mono text-theme-xs text-gray-500 dark:text-gray-400">{{ $d->transaction ?: '—' }}</span></td>
          <td data-filter-value="{{ $k === 'cash' ? 'Cash' : 'Book' }}">@if ($k === 'cash')<x-staff.pill tone="blue" dot>Cash</x-staff.pill>@else<x-staff.pill tone="gray">Book · {{ str_replace('_', ' ', $k) }}</x-staff.pill>@endif</td>
          <td class="num" data-sort-value="{{ (float) $d->amount }}"><x-staff.money :amount="$d->amount" tone="muted" /></td>
          <td class="num"><x-staff.money :amount="(float) $d->net_amount ?: $d->amount" :tone="$k === 'cash' ? 'out' : 'muted'" /></td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="6"><x-staff.empty title="No disbursements entered" /></td></tr>
      @endforelse
    </x-staff.table>
  </div>

  <p class="mb-6 text-theme-xs text-gray-500">“Entered by” is the person who recorded each row (older rows: the audit log’s create entry). Book entries (rollover, discount, write-off, broker fee …) are recorded but are not money.</p>

  {{-- Close the day (Stage 4 Phase 5): count, record the variance, a manager approves --}}
  <section class="rounded-2xl border border-gray-200 bg-white px-4 py-5 print:hidden dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
    <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Close the day</h2>
    @if ($close)
      @php $exp = json_decode($close->expected, true) ?: []; $cnt = json_decode($close->counted, true) ?: []; @endphp
      <dl class="grid grid-cols-2 gap-2 text-theme-sm sm:grid-cols-4">
        @foreach (array_unique(array_merge(array_keys($exp), array_keys($cnt))) as $m)
          <div><dt class="capitalize text-gray-500">{{ str_replace('_', ' ', $m) }}</dt><dd>expected <x-staff.money :amount="$exp[$m] ?? 0" :currency="false" /> · counted <x-staff.money :amount="$cnt[$m] ?? 0" :currency="false" /></dd></div>
        @endforeach
      </dl>
      <p class="mt-2 text-theme-sm">Variance <x-staff.money :amount="$close->variance" tone="auto" sign /> · {{ $close->status === 'approved' ? 'approved' : 'waiting for a manager' }}@if ($close->note) · {{ $close->note }}@endif</p>
      @if ($close->status !== 'approved' && (int) $close->teller_id !== (int) auth()->id() && auth()->user()->can('view reports'))
        <form method="POST" action="{{ route('staff.end-of-day.approve', $close->id) }}" class="mt-3">@csrf
          <button class="rounded-lg bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white hover:bg-brand-600">Approve cash-up</button></form>
      @endif
    @elseif ((int) $teller?->id === (int) auth()->id())
      <form method="POST" action="{{ route('staff.end-of-day.close') }}" class="space-y-3">
        @csrf
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
        <p class="text-theme-sm text-gray-500">Count what you hold for each way you were paid today. Cash paid out comes off "cash".</p>
        <div class="flex flex-wrap gap-3">
          @foreach (array_unique(array_merge(['cash', 'mpesa'], array_keys($byMode->all()))) as $m)
            <label class="text-theme-sm"><span class="mb-1 block capitalize text-gray-500">{{ str_replace('_', ' ', $m) }}</span>
              <input name="counted[{{ $m }}]" type="number" step="0.01" min="0" class="h-10 w-36 rounded-lg border border-gray-300 px-2 dark:border-gray-700 dark:bg-gray-900"></label>
          @endforeach
        </div>
        <input name="note" maxlength="500" placeholder="Note (optional)" class="h-10 w-full max-w-md rounded-lg border border-gray-300 px-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900">
        <button class="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-brand-600">Close the day</button>
      </form>
    @else
      <p class="text-theme-sm text-gray-500">Not closed yet. The teller closes their own day.</p>
    @endif
  </section>
@endsection
