{{-- Disbursements register (owner, 22 Oct 2026): every disbursement row with filters, totals and CSV.
     Reached from Reports & Analytics; the sidebar keeps only the Disbursement queue. --}}
@extends('layouts.app')

@php
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $kindTone = ['cash' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500',
               'rollover' => 'bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400',
               'discount' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
               'bad_debt' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
               'non_cash' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300'];
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Disbursements register', 'sub' => 'Every disbursement recorded, newest first.', 'metrics' => false,
    'actions' => \Illuminate\Support\Facades\Route::has('reports.index') && auth()->user()->can('view reports') ? '<a href="' . route('reports.index') . '" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Reports &amp; Analytics</a>' : null])

  <x-ui.metrics :items="[
    ['label' => 'Rows in this filter', 'value' => number_format($totals->n ?? 0), 'icon' => 'list'],
    ['label' => 'Amount', 'value' => 'KES ' . number_format((float) ($totals->amount ?? 0), 2), 'icon' => 'send'],
    ['label' => 'Fees taken', 'value' => 'KES ' . number_format((float) ($totals->fees ?? 0), 2), 'icon' => 'coins'],
  ]" />

  <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <form method="GET" class="grid grid-cols-1 gap-3 border-b border-gray-200 p-5 dark:border-gray-800 sm:grid-cols-2 lg:grid-cols-6">
      <input class="{{ $input }} lg:col-span-2" type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Borrower, phone, loan # or reference">
      <input class="{{ $input }}" type="date" data-range-end="to" name="from" value="{{ $f['from'] ?? '' }}" title="Dates" placeholder="Dates">
      <input class="{{ $input }}" type="date" name="to" value="{{ $f['to'] ?? '' }}" title="To">
      <select class="{{ $input }}" name="kind">
        <option value="">Cash and book entries</option>
        <option value="cash" @selected(($f['kind'] ?? '') === 'cash')>Cash only</option>
        <option value="non_cash" @selected(($f['kind'] ?? '') === 'non_cash')>Book entries only</option>
      </select>
      <div class="flex gap-2">
        <button class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white hover:bg-brand-600"><x-ui.icon name="filter" class="size-4" /> Filter</button>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" title="Download CSV" class="inline-flex size-11 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5"><x-ui.icon name="download" class="size-4" /></a>
      </div>
    </form>

    @if ($disbursements->isEmpty())
      <x-staff.empty title="No disbursements" message="Nothing matches this filter." />
    @else
      <div class="overflow-x-auto">
        <table data-table data-table-page="25" data-table-search="Search this list" class="min-w-full text-theme-sm">
          <thead class="border-b border-gray-200 text-left text-theme-xs uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:text-gray-400">
            <tr>
              <th class="px-5 py-3 font-medium">Date</th><th class="px-5 py-3 font-medium">Loan</th><th class="px-5 py-3 font-medium">Borrower</th>
              <th class="px-5 py-3 text-right font-medium">Amount</th><th class="px-5 py-3 text-right font-medium">Fee</th>
              <th class="px-5 py-3 font-medium" data-filter="Mode">Mode</th><th class="px-5 py-3 font-medium" data-filter="Kind">Kind</th><th class="px-5 py-3 font-medium">Reference</th><th class="px-5 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($disbursements as $d)
              @php $kind = \App\Services\CustomerWallets::kindOf($d); @endphp
              <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                <td class="whitespace-nowrap px-5 py-3 text-gray-700 dark:text-gray-300">{{ $d->disburse_date?->format('j M Y') }}</td>
                <td class="px-5 py-3"><a class="font-medium text-brand-500 hover:underline" href="{{ route('loans.show', $d->loan_id) }}">#{{ $d->loan_id }}</a><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $d->loan->loanType->name ?? '' }}</span></td>
                <td class="px-5 py-3 text-gray-800 dark:text-white/90"><span class="cursor-pointer hover:text-brand-500" data-user-peek="{{ $d->loan->user_id ?? '' }}">{{ $d->loan->user->name ?? '' }}</span></td>
                <td class="whitespace-nowrap px-5 py-3 text-right font-medium text-gray-800 dark:text-white/90">{{ number_format((float) $d->amount, 2) }}</td>
                <td class="whitespace-nowrap px-5 py-3 text-right text-gray-600 dark:text-gray-400">{{ (float) $d->processing_fee ? number_format((float) $d->processing_fee, 2) : '' }}</td>
                <td class="px-5 py-3 capitalize text-gray-600 dark:text-gray-400">{{ str_replace('_', ' ', (string) $d->mode) }}</td>
                <td class="px-5 py-3"><span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-theme-xs font-medium {{ $kindTone[$kind] ?? $kindTone['non_cash'] }}">{{ str_replace('_', ' ', $kind) }}</span></td>
                <td class="max-w-[220px] truncate px-5 py-3 font-mono text-theme-xs text-gray-500 dark:text-gray-400" title="{{ $d->transaction }}">{{ $d->transaction }}@if ($d->bank_ref && $d->mpesa_ref)<span class="block">M-Pesa {{ $d->mpesa_ref }}</span>@endif</td>
                <td class="px-5 py-3 text-right"><a href="{{ route('disbursements.show', $d->id) }}" class="text-gray-500 hover:text-brand-500 dark:text-gray-400" title="Open"><x-ui.icon name="eye" class="size-4" /></a></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection
