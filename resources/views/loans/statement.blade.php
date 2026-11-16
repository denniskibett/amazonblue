{{-- The full loan statement (owner, 16 Nov 2026): every cycle with its own disbursements and repayments.
     Same look as the one-cycle statement on the loan page; prints portrait A4 through loans._print-css. --}}
@extends('layouts.app')

@php
  $kes = fn ($n) => number_format((float) $n, 2);
  $day = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
  $kindLabel = ['rollover' => 'Rolled over', 'discount' => 'Discount', 'bad_debt' => 'Written off', 'non_cash' => 'Non-cash'];
  $status = ['active' => 'Running', 'completed' => 'Carried over', 'repaid' => 'Repaid', 'defaulted' => 'Defaulted'];
@endphp

@section('content')
<div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
  <a href="{{ route('users.loans.show', [$loan->user_id, $loan->id]) }}" class="text-sm font-medium text-brand-500 hover:underline">← Back to loan #{{ $loan->id }}</a>
  <button type="button" onclick="window.print()" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Print full statement</button>
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
        <h5 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">AmazonBlue Capital</h5>
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
    <div class="mb-8 grid grid-cols-2 gap-4 rounded-2xl border border-gray-100 bg-gray-50 p-5 sm:grid-cols-4 print:grid-cols-4 dark:border-gray-800 dark:bg-white/[0.02]">
      <div><p class="text-xs text-gray-500">Product</p><p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $loan->loanType->name ?? '—' }}</p></div>
      <div><p class="text-xs text-gray-500">Cycles</p><p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $rows->count() }}</p></div>
      <div><p class="text-xs text-gray-500">Sent to the borrower</p><p class="text-sm font-semibold tabular-nums text-gray-800 dark:text-white/90">KES {{ $kes($totalLent) }}</p></div>
      <div><p class="text-xs text-gray-500">Paid by the borrower</p><p class="text-sm font-semibold tabular-nums text-emerald-600">KES {{ $kes($totalPaid) }}</p></div>
    </div>

    @foreach ($rows as $r)
      @php $c = $r->cycle; $b = $r->b; @endphp
      <div class="az-cycle mb-6 overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-800">
        <div class="flex flex-wrap items-center justify-between gap-2 bg-gray-50 px-5 py-3 dark:bg-white/[0.02]">
          <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Cycle #{{ $c->cycle_number }}
            <span class="font-normal text-gray-500">· {{ $day($c->start_date) }} → {{ $day($c->due_date) }} · {{ rtrim(rtrim(number_format((float) $c->interest_rate, 2), '0'), '.') }}%</span></p>
          <span class="text-xs font-medium {{ $r->carried !== null ? 'text-gray-500' : ($b['final_outstanding'] > 0 ? 'text-red-600' : 'text-emerald-600') }}">
            {{ $r->carried !== null ? 'Carried over' : ($b['final_outstanding'] > 0 ? ($b['is_overdue'] ? 'Overdue' : 'Running') : ($status[$c->status] ?? 'Repaid')) }}</span>
        </div>

        <div class="grid grid-cols-2 gap-x-6 gap-y-1 px-5 py-3 text-sm sm:grid-cols-4 print:grid-cols-4">
          <div><p class="text-xs text-gray-500">Principal</p><p class="tabular-nums text-gray-800 dark:text-white/90">{{ $kes($b['principal']) }}</p></div>
          <div><p class="text-xs text-gray-500">Interest</p><p class="tabular-nums text-gray-800 dark:text-white/90">{{ $kes($b['interest']) }}</p></div>
          <div><p class="text-xs text-gray-500">Penalty</p><p class="tabular-nums text-gray-800 dark:text-white/90">{{ $kes($b['penalty']) }}</p></div>
          <div><p class="text-xs text-gray-500">Repaid</p><p class="tabular-nums text-emerald-600">{{ $kes($b['total_repayments']) }}</p></div>
        </div>

        <div class="grid grid-cols-1 gap-4 border-t border-gray-100 px-5 py-3 sm:grid-cols-2 print:grid-cols-2 dark:border-gray-800">
          @foreach ([['Disbursements', $r->disbursements, 'disburse_date'], ['Repayments', $r->repayments, 'repayment_date']] as [$title, $list, $dateCol])
            <div>
              <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $title }}</p>
              @forelse ($list as $x)
                <div class="flex items-start justify-between gap-3 border-b border-dashed border-gray-100 py-1.5 text-sm last:border-0 dark:border-gray-800">
                  <div>
                    <p class="text-gray-700 dark:text-gray-300">{{ $day($x->$dateCol) }}
                      @if ($x->kind && $x->kind !== 'cash')<span class="ml-1 rounded bg-gray-100 px-1.5 text-[11px] text-gray-600 dark:bg-gray-800">{{ $kindLabel[$x->kind] ?? $x->kind }}</span>@endif</p>
                    <p class="text-xs text-gray-400">{{ trim(ucfirst(str_replace('_', ' ', (string) $x->mode)) . ($x->transaction ? ' · ' . $x->transaction : ''), ' ·') ?: '—' }}</p>
                  </div>
                  <p class="tabular-nums text-gray-800 dark:text-white/90">{{ $kes($x->amount) }}</p>
                </div>
              @empty
                <p class="py-1.5 text-sm text-gray-400">None in this cycle.</p>
              @endforelse
            </div>
          @endforeach
        </div>

        <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 text-sm dark:border-gray-800">
          @if ($r->carried !== null)
            <span class="text-gray-500">Owed when it closed, carried into cycle #{{ $c->cycle_number + 1 }}</span>
            <span class="font-semibold tabular-nums text-gray-800 dark:text-white/90">KES {{ $kes($r->owed) }}</span>
          @else
            <span class="font-semibold text-gray-800 dark:text-white/90">Balance due (cycle #{{ $c->cycle_number }})</span>
            <span class="font-semibold tabular-nums {{ $b['final_outstanding'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">KES {{ $kes($b['final_outstanding']) }}</span>
          @endif
        </div>
      </div>
    @endforeach

    <div class="flex items-center justify-between border-t border-gray-200 pt-5 dark:border-gray-800">
      <span class="text-lg font-semibold text-gray-800 dark:text-white/90">Balance Due:</span>
      <span class="text-xl font-bold tabular-nums {{ $balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">KES {{ $kes($balance) }}</span>
    </div>
    <p class="mt-2 text-xs text-gray-400">Printed {{ now()->format('j M Y, H:i') }}. Rolled-over rows move the balance into the next cycle; no money changes hands.</p>
  </div>
</div>
@endsection

@push('scripts')
  @include('loans._print-css')
  <style>@media print { .az-cycle { break-inside: avoid; page-break-inside: avoid; } }</style>
@endpush
