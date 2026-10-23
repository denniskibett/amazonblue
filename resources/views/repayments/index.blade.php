{{-- Repayments list (owner, 22 Oct 2026): TailAdmin table, newest first, up to 1000 rows paged in the browser. --}}
@extends('layouts.app')

@section('content')
  @php
    $u = auth()->user();
    $actions = $u->hasAnyRole(['admin', 'broker', 'teller']) && \Illuminate\Support\Facades\Route::has('staff.repayments.receive')
      ? '<a href="' . route('staff.repayments.receive') . '" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Receive payment</a>' : null;
  @endphp
  @include('staff.desk._header', ['title' => 'Repayments', 'sub' => 'Every repayment recorded, newest first.', 'metrics' => false, 'actions' => $actions])

  <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="max-w-full overflow-x-auto">
      <table data-table data-table-page="25" data-table-search="Search repayments" class="min-w-full text-left text-theme-sm">
        <thead class="border-b border-gray-200 text-theme-xs uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:text-gray-400">
          <tr>
            <th class="px-5 py-3 font-medium">Date</th>
            <th class="px-5 py-3 font-medium">Borrower</th>
            <th class="px-5 py-3 font-medium">Loan</th>
            <th class="px-5 py-3 text-right font-medium">Amount</th>
            <th class="px-5 py-3 font-medium" data-filter="Kind">Kind</th>
            <th class="px-5 py-3 font-medium">Reference</th>
            <th class="px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse ($repayments as $r)
            @php $kind = \App\Services\CustomerWallets::kindOf($r); $who = $r->loan->borrower ?? $r->loan->user ?? null; @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
              <td class="whitespace-nowrap px-5 py-3 text-gray-700 dark:text-gray-300" data-sort-value="{{ optional($r->repayment_date)->format('Y-m-d H:i') }}">{{ optional($r->repayment_date)->format('j M Y') }}</td>
              <td class="px-5 py-3">
                <p class="font-medium text-gray-800 dark:text-white/90">{{ $who->name ?? '' }}</p>
                <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $who->phone ?? $who->email ?? '' }}</p>
              </td>
              <td class="px-5 py-3">@if ($r->loan_id)<a class="text-brand-500 hover:underline" href="{{ route('loans.show', $r->loan_id) }}">#{{ $r->loan_id }}</a>@endif</td>
              <td class="whitespace-nowrap px-5 py-3 text-right font-medium text-gray-800 dark:text-white/90" data-sort-value="{{ $r->amount }}">{{ number_format((float) $r->amount, 2) }}</td>
              <td class="px-5 py-3 capitalize text-gray-600 dark:text-gray-400">{{ str_replace('_', ' ', $kind) }}</td>
              <td class="max-w-[220px] truncate px-5 py-3 font-mono text-theme-xs text-gray-500 dark:text-gray-400" title="{{ $r->transaction }}">{{ $r->transaction }}@if ($r->bank_ref && $r->mpesa_ref)<span class="block">bank {{ $r->bank_ref }}</span>@endif</td>
              <td class="px-5 py-3 text-right" data-actions>
                <a href="{{ route('repayments.show', $r->id) }}">View</a>
                @if ($u->hasAnyRole(['admin', 'teller']))<a href="{{ route('repayments.edit', $r->id) }}">Edit</a>@endif
                @if ($u->hasRole('admin'))
                  <form action="{{ route('repayments.destroy', $r->id) }}" method="POST" data-confirm="Delete this repayment? It is reversed in the books and kept in the audit log." data-confirm-danger>
                    @csrf @method('DELETE')
                    <button type="submit" class="!text-error-600">Delete</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr data-table-skip><td colspan="7" class="px-5 py-10 text-center text-gray-500 dark:text-gray-400">No repayments yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
