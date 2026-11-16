{{-- The full loan statement (owner, 16 Nov 2026): every cycle with its own disbursements and repayments.
     Same look as the one-cycle statement on the loan page; prints portrait A4 through loans._print-css. --}}
@extends('layouts.app')

@php
  $kes = fn ($n) => number_format((float) $n, 2);
  $day = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
@endphp

@section('content')
<div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
  <a href="{{ route('users.loans.show', [$loan->user_id, $loan->id]) }}" class="text-sm font-medium text-brand-500 hover:underline">← Back to loan #{{ $loan->id }}</a>
  <button type="button" onclick="window.print()" class="rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white hover:bg-brand-600">Print full statement</button>
</div>

<div class="az-statement rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
  <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
    <h3 class="text-xl font-medium text-gray-800 dark:text-white/90">Full Loan Statement</h3>
    <h4 class="text-base font-medium text-gray-700 dark:text-gray-400">ID : #{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}</h4>
  </div>

  <div class="p-5 xl:p-8">
    <div class="mb-8 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-400">From</span>
        <h5 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">Aimzone Ventures Capital</h5>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">G.P.O 50054 - 00100,<br>Nairobi, Kenya</p>
        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Issued On:</span>
        <span class="block text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($loan->borrow_date)->format('D, M d, Y') }}</span>
      </div>
      <div class="h-px w-full bg-gray-200 dark:bg-gray-800 sm:h-[158px] sm:w-px"></div>
      <div class="sm:text-right">
        <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-400">To</span>
        <h5 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">{{ $loan->user->name ?? 'N/A' }}</h5>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ $loan->user->phone ?? '' }}<br>{{ $loan->user->email ?? '' }}</p>
        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Due On:</span>
        <span class="block text-sm text-gray-500 dark:text-gray-400">{{ $dueDate ? \Carbon\Carbon::parse($dueDate)->format('D, M d, Y') : 'N/A' }}</span>
      </div>
    </div>

    {{-- the whole loan at a glance --}}
    <div class="mb-6 grid grid-cols-2 gap-4 rounded-2xl border border-gray-100 bg-gray-50 p-5 sm:grid-cols-5 print:grid-cols-5 dark:border-gray-800 dark:bg-white/[0.02]">
      <div><p class="text-xs text-gray-500">Product</p><p class="whitespace-nowrap text-sm font-semibold text-gray-800 dark:text-white/90">{{ $loan->loanType->name ?? '—' }}</p></div>
      <div><p class="text-xs text-gray-500">Cycles</p><p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $cycleCount }}</p></div>
      <div><p class="text-xs text-gray-500">Loan sent</p><p class="text-sm font-semibold tabular-nums text-gray-800 dark:text-white/90">{{ $kes($totals['lent']) }}</p></div>
      <div><p class="text-xs text-gray-500">Interest + penalty</p><p class="text-sm font-semibold tabular-nums text-gray-800 dark:text-white/90">{{ $kes($totals['interest'] + $totals['penalty']) }}</p></div>
      <div><p class="text-xs text-gray-500">Paid</p><p class="text-sm font-semibold tabular-nums text-emerald-600">{{ $kes($totals['paid']) }}</p></div>
    </div>

    {{-- the ledger: cycle is a column, the balance runs down (owner, 16 Nov 2026) --}}
    <div class="az-ledger mb-6 overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-800">
      <table class="w-full table-fixed whitespace-nowrap text-xs">
        <colgroup><col style="width:11%"><col style="width:6%"><col><col style="width:12%"><col style="width:12%"><col style="width:13%"></colgroup>
        <thead class="bg-gray-50 text-left dark:bg-white/[0.02]">
          <tr class="text-gray-700 dark:text-gray-400">
            <th class="px-3 py-2.5 font-medium">Date</th>
            <th class="px-2 py-2.5 font-medium">Cycle</th>
            <th class="px-2 py-2.5 font-medium">Description</th>
            <th class="px-2 py-2.5 text-right font-medium">Charges</th>
            <th class="px-2 py-2.5 text-right font-medium">Payments</th>
            <th class="px-3 py-2.5 text-right font-medium">Balance (KES)</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($lines as $i => $l)
            @php $newCycle = $i > 0 && $lines[$i - 1]->n !== $l->n; @endphp
            <tr class="az-line border-t {{ $newCycle ? 'border-gray-300 dark:border-gray-600' : 'border-gray-100 dark:border-gray-800' }}">
              <td class="whitespace-nowrap px-3 py-2 text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($l->date)->format('j M Y') }}</td>
              <td class="px-2 py-2 text-gray-500 dark:text-gray-400">#{{ $l->n }}</td>
              @php $block = $l->ref && str_contains($l->text, 'djustment'); $wide = $l->note && $l->charge == 0 && $l->payment == 0; @endphp
              <td @if ($wide) colspan="3" @endif class="w-full px-2 py-2 {{ $block ? 'whitespace-normal' : 'truncate' }} {{ $l->note ? 'italic text-gray-400' : 'text-gray-700 dark:text-gray-300' }}" @if ($l->ref) title="{{ $l->text }} · {{ $l->ref }}" @endif><span>{{ $l->text }}@if ($l->ref && ! $block)<span class="not-italic text-gray-400"> · {{ $l->ref }}</span>@endif</span>@if ($block)<span class="block text-[11px] not-italic leading-snug text-gray-400">{{ $l->ref }}</span>@endif</td>
              @unless ($wide)
              <td class="whitespace-nowrap px-2 py-2 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $l->charge > 0 ? $kes($l->charge) : '' }}</td>
              <td class="whitespace-nowrap px-2 py-2 text-right tabular-nums text-emerald-600">{{ $l->payment > 0 ? $kes($l->payment) : '' }}</td>
              @endunless
              <td class="whitespace-nowrap px-3 py-2 text-right font-medium tabular-nums text-gray-800 dark:text-white/90">{{ $kes($l->balance) }}</td>
            </tr>
          @endforeach
          <tr class="az-line border-t border-gray-200 bg-gray-50 font-semibold text-gray-800 dark:border-gray-700 dark:bg-white/[0.02] dark:text-white/90">
            <td class="px-4 py-3" colspan="3">Totals</td>
            <td class="whitespace-nowrap px-2 py-2.5 text-right tabular-nums">{{ $kes(collect($lines)->sum('charge')) }}</td>
            <td class="whitespace-nowrap px-2 py-2.5 text-right tabular-nums text-emerald-600">{{ $kes(collect($lines)->sum('payment')) }}</td>
            <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ $kes($balance) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between border-t border-gray-200 pt-5 dark:border-gray-800">
      <span class="text-lg font-semibold text-gray-800 dark:text-white/90">Balance Due:</span>
      <span class="text-xl font-bold tabular-nums {{ $balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">KES {{ $kes($balance) }}</span>
    </div>
    <p class="mt-2 text-xs text-gray-400">Printed {{ now()->format('j M Y, H:i') }}. A rollover carries the balance into the next cycle; no money changes hands.</p>
  </div>
</div>
@endsection

@push('scripts')
  @include('loans._print-css')
  <style>@media print { .az-line { break-inside: avoid; page-break-inside: avoid; } .az-statement thead { display: table-header-group; } .az-statement .az-ledger { break-inside: auto !important; page-break-inside: auto !important; } .az-statement .az-ledger table { font-size: 10.5px; } .az-statement .az-ledger td, .az-statement .az-ledger th { padding-left: 6px !important; padding-right: 6px !important; } }</style>
@endpush
