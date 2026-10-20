{{-- Teller · end of day. What one teller entered on one day (from the audit trail), real cash vs book entries.
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

  <form method="GET" class="mb-6 flex flex-wrap items-end gap-3 print:hidden">
    <x-staff.field name="date" label="Day" type="date" :value="$date->toDateString()" />
    @if ($tellers->isNotEmpty())
      <x-staff.field name="teller" label="Staff member" type="select" :options="$tellers->pluck('name', 'id')->all()" :value="$teller->id ?? null" />
    @endif
    <button class="h-11 rounded-lg bg-brand-500 px-5 text-theme-sm font-medium text-white hover:bg-brand-600">Show</button>
  </form>

  <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="{{ $card }}"><p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Cash received</p><p class="mt-1 text-xl"><x-staff.money :amount="$totals['received_cash']" tone="in" /></p><p class="text-theme-xs text-gray-500">of <x-staff.money :amount="$totals['received_all']" tone="muted" /> recorded</p></div>
    <div class="{{ $card }}"><p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Cash paid out</p><p class="mt-1 text-xl"><x-staff.money :amount="-$totals['disbursed_cash']" tone="out" /></p><p class="text-theme-xs text-gray-500">of <x-staff.money :amount="$totals['disbursed_all']" tone="muted" /> recorded (net of fees)</p></div>
    <div class="{{ $card }}"><p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Net cash</p><p class="mt-1 text-xl"><x-staff.money :amount="$net" tone="auto" sign /></p><p class="text-theme-xs text-gray-500">received − paid out</p></div>
    <div class="{{ $card }}"><p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Cash in by mode</p>
      <dl class="mt-1 space-y-0.5 text-theme-sm">
        @forelse ($byMode as $mode => $sum)
          <div class="flex justify-between gap-2"><dt class="capitalize text-gray-500">{{ str_replace('_', ' ', $mode) }}</dt><dd><x-staff.money :amount="$sum" :currency="false" /></dd></div>
        @empty
          <p class="text-gray-500">—</p>
        @endforelse
      </dl>
    </div>
  </div>

  <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Payments received ({{ $repayments->count() }})</h2>
  @if ($repayments->isEmpty())
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><x-staff.empty title="No payments entered" message="Nothing recorded by this person on this day." /></div>
  @else
    <div class="mb-6"><x-staff.table caption="Payments received" :headers="['#', 'Borrower', 'Loan', 'Mode', 'Reference', 'Kind', ['Amount', 'num']]">
      @foreach ($repayments as $p)
        @php $k = $kind($p); @endphp
        <tr @class(['opacity-60 line-through' => $p->deleted_at])>
          <td>{{ $p->id }}</td>
          <td>{{ $p->loan->user->name ?? '—' }}</td>
          <td>#{{ $p->loan_id }}</td>
          <td>{{ $p->mode ?: '—' }}</td>
          <td>{{ $p->transaction ?: '—' }}</td>
          <td>{{ $p->deleted_at ? 'Reversed' : ($k === 'cash' ? 'Cash' : 'Book (' . str_replace('_', ' ', $k) . ')') }}</td>
          <td class="num"><x-staff.money :amount="$p->amount" :tone="$k === 'cash' && ! $p->deleted_at ? 'in' : 'muted'" /></td>
        </tr>
      @endforeach
    </x-staff.table></div>
  @endif

  <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Disbursements ({{ $disbursements->count() }})</h2>
  @if ($disbursements->isEmpty())
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><x-staff.empty title="No disbursements entered" /></div>
  @else
    <div class="mb-6"><x-staff.table caption="Disbursements" :headers="['#', 'Borrower', 'Loan', 'Mode', 'Reference', 'Kind', ['Amount', 'num'], ['Sent (net)', 'num']]">
      @foreach ($disbursements as $d)
        @php $k = $kind($d); @endphp
        <tr>
          <td>{{ $d->id }}</td>
          <td>{{ $d->loan->user->name ?? '—' }}</td>
          <td>#{{ $d->loan_id }}</td>
          <td>{{ $d->mode ?: '—' }}</td>
          <td>{{ $d->transaction ?: '—' }}</td>
          <td>{{ $k === 'cash' ? 'Cash' : 'Book (' . str_replace('_', ' ', $k) . ')' }}</td>
          <td class="num"><x-staff.money :amount="$d->amount" tone="muted" /></td>
          <td class="num"><x-staff.money :amount="(float) $d->net_amount ?: $d->amount" :tone="$k === 'cash' ? 'out' : 'muted'" /></td>
        </tr>
      @endforeach
    </x-staff.table></div>
  @endif

  <p class="mb-6 text-theme-xs text-gray-500">“Entered by” comes from the audit log (create rows since the audit trail was switched on). Book entries (rollover, discount, write-off, broker fee …) are recorded but are not money.</p>

  <x-staff.placeholder class="print:hidden" dependency="cash-up table" title="Close the day">
    Counting the till, recording the difference and locking the day need a cash-up table and a supervisor sign-off (Stage 4).
  </x-staff.placeholder>
@endsection
