{{-- Teller · receive payment. Loan search -> the loan's own cycles -> amount / reference / mode, posted by AJAX
     to the existing repayments.store (RepaymentController@store, unchanged). Reversal = existing destroy + reason. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $defaultCycle = $cycles->firstWhere('status', 'active') ?? $cycles->last();
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Receive payment', 'sub' => 'Find the loan, pick the cycle the money is for, and record it.'])
  @include('staff.desk._ajax')

  {{-- search and its results in one card (owner, 22 Oct 2026) --}}
  <section class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <form method="GET" action="{{ route('staff.repayments.receive') }}" class="flex flex-col gap-3 px-4 pt-4 pb-4 sm:flex-row sm:items-end sm:px-6 sm:pt-5" role="search">
      <div class="min-w-0 flex-1">
        <label for="q" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Loan number, borrower name, phone, email or ID number</label>
        <div class="relative">
          <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><x-ui.icon name="search" class="size-4" /></span>
          <input id="q" name="q" value="{{ $q }}" class="{{ $input }} pl-11" placeholder="e.g. 545 or Amina" autocomplete="off" autofocus>
        </div>
      </div>
      <button class="h-11 rounded-lg bg-brand-500 px-5 text-theme-sm font-medium text-white hover:bg-brand-600">Search</button>
    </form>
    <div class="border-t border-gray-100 dark:border-gray-800">
      @if ($q === '')
        @unless ($loan)
          <x-staff.empty title="Search for a loan to start" message="Payments are recorded against one of the loan's own cycles." />
        @else
          <p class="px-4 py-3 text-theme-sm text-gray-500 dark:text-gray-400 sm:px-6">Recording a payment on loan #{{ $loan->id }}. Search again to pick another loan.</p>
        @endunless
      @elseif ($results->isEmpty())
        <x-staff.empty title="No open loan found" message="Only disbursed, overdue, defaulted, recovery or forbearance loans take payments." />
      @else
        <div class="px-4 pb-3 sm:px-6">
          <p class="py-3 text-theme-sm text-gray-500 dark:text-gray-400">{{ $results->count() }} {{ \Illuminate\Support\Str::plural('loan', $results->count()) }} found for “{{ $q }}”</p>
          <div class="max-w-full overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-theme-sm text-gray-700 dark:text-gray-300 [&_td]:whitespace-nowrap [&_td]:py-3 [&_td]:pr-4 [&_td:last-child]:pr-0">
              <caption class="sr-only">Search results</caption>
              <thead class="border-y border-gray-100 dark:border-gray-800"><tr>
                @foreach (['Borrower', 'Loan · product', 'Status', 'Amount', ''] as $h)<th scope="col" @class(['py-3 pr-4 text-theme-xs font-medium text-gray-500 dark:text-gray-400', 'text-right' => $h === 'Amount'])>{{ $h }}</th>@endforeach
              </tr></thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($results as $r)
                  <tr @class(['bg-brand-50/50 dark:bg-brand-500/10' => $loan && $loan->id === $r->id])>
                    <td><x-staff.person :user="$r->user" :sub="$r->user->phone ?? null" /></td>
                    <td><span class="font-medium text-gray-800 dark:text-white/90">Loan #{{ $r->id }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $r->loanType->name ?? '—' }}</span></td>
                    <td><x-staff.status-badge :status="$r->status" /></td>
                    <td class="text-right"><x-staff.money :amount="$r->amount" class="text-gray-800 dark:text-white/90" /></td>
                    <td class="text-right">
                      @if ($loan && $loan->id === $r->id)
                        <x-staff.pill tone="brand">Selected</x-staff.pill>
                      @else
                        <a href="{{ route('staff.repayments.receive', ['q' => $q, 'loan' => $r->id]) }}" class="inline-flex rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600">Select</a>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif
    </div>
  </section>

  @if ($loan)
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
      <section class="{{ $card }} min-w-0 lg:col-span-3">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
          <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Loan #{{ $loan->id }} · {{ $loan->user->name ?? '' }}</h2>
          <x-staff.status-badge :status="$loan->status" />
        </div>

        @if (! in_array($loan->status, \App\Http\Controllers\Staff\CashDeskController::REPAYABLE, true))
          <x-staff.alert type="warn">This loan is {{ $loan->status }}; payments are taken only on open loans.</x-staff.alert>
        @elseif ($cycles->isEmpty())
          <x-staff.alert type="warn">This loan has no cycles, so a payment cannot be recorded against it. Open the loan page.</x-staff.alert>
        @elseif (auth()->user()->cannot('create repayments'))
          <x-staff.alert type="info" title="Read-only">You can see the loan but not record payments.</x-staff.alert>
        @else
          <form method="POST" action="{{ route('repayments.store') }}" data-ajax-form data-done="{{ route('staff.repayments.receive', ['loan' => $loan->id, 'done' => 'repaid']) }}" class="space-y-5">
            @csrf
            <input type="hidden" name="loan_id" value="{{ $loan->id }}">
            <fieldset>
              <legend class="mb-2 text-theme-sm font-medium text-gray-700 dark:text-gray-400">Cycle this payment is for <span class="text-error-500">*</span></legend>
              <div class="space-y-2">
                @foreach ($cycles as $c)
                  <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:border-gray-800 dark:has-[:checked]:bg-brand-500/10">
                    <input type="radio" name="loan_cycle_id" value="{{ $c->id }}" class="h-4 w-4 accent-brand-500" @checked($defaultCycle && $defaultCycle->id === $c->id) required>
                    <span class="min-w-0 flex-1">
                      <span class="block text-theme-sm font-medium text-gray-800 dark:text-white/90">Cycle {{ $c->cycle_number }} · due {{ \Illuminate\Support\Carbon::parse($c->due_date)->format('j M Y') }}</span>
                      <span class="block text-theme-xs text-gray-500">Balance <x-staff.money :amount="$c->new_balance" tone="muted" /> · paid <x-staff.money :amount="$c->paid_so_far" tone="muted" /></span>
                    </span>
                    <x-staff.status-badge :status="$c->status === 'completed' ? 'repaid' : ($c->status === 'active' ? 'disbursed' : $c->status)" :label="ucfirst($c->status)" />
                  </label>
                @endforeach
              </div>
            </fieldset>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <x-staff.field name="amount" label="Amount (KES)" type="number" step="0.01" min="0.01" required />
              <x-staff.field name="repayment_date" label="Date received" type="date" :value="now()->toDateString()" required />
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <x-staff.field name="mode" label="Mode" type="select" :options="['mpesa' => 'M-Pesa', 'cash' => 'Cash', 'bank' => 'Bank deposit', 'cheque' => 'Cheque']" value="mpesa" />
              <x-staff.field name="transaction" label="Reference" required placeholder="M-Pesa code, receipt or slip number" />
            </div>
            <div data-errors hidden class="rounded-lg border border-error-500/30 bg-error-50 p-3 text-theme-sm text-error-800 dark:bg-error-500/15 dark:text-error-400" role="alert"></div>
            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-60 sm:w-auto">Record payment</button>
          </form>
        @endif
      </section>

      <aside class="min-w-0 space-y-6 lg:col-span-2">
        <x-staff.placeholder dependency="six-account split" title="Split preview">
          How this payment divides between principal, interest, penalty, fees and the partner and house accounts. The split rules are Stage 4; today the payment is recorded against the cycle as one amount.
        </x-staff.placeholder>
        <x-staff.placeholder dependency="payment confirmations inbox" title="Payment confirmations from borrowers">
          Borrowers' payment confirmations arrive by email and are not stored in the system. Check the inbox, then record the payment here. Storing them with an approval step is Stage 4.
        </x-staff.placeholder>
      </aside>
    </div>

    <section class="mt-6">
      @if ($recent->isEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><x-staff.empty title="No payments yet on this loan" /></div>
      @else
        <x-staff.table title="Recent payments on this loan" caption="Recent payments" :headers="['Payment', 'Cycle', 'Mode', 'Reference', ['Amount', 'num'], '']">
          @foreach ($recent as $p)
            <tr>
              <td data-sort-value="{{ \Illuminate\Support\Carbon::parse($p->repayment_date)->timestamp }}"><span class="font-medium text-gray-800 dark:text-white/90">#{{ $p->id }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($p->repayment_date)->format('j M Y') }}</span></td>
              <td>Cycle {{ optional($cycles->firstWhere('id', $p->loan_cycle_id))->cycle_number ?? '—' }}</td>
              <td><x-staff.pill :tone="$p->mode === 'mpesa' ? 'green' : 'gray'">{{ ['mpesa' => 'M-Pesa', 'cash' => 'Cash', 'bank' => 'Bank', 'cheque' => 'Cheque'][$p->mode] ?? ($p->mode ?: '—') }}</x-staff.pill></td>
              <td class="font-mono text-theme-xs">{{ $p->transaction ?: '—' }}</td>
              <td class="num"><x-staff.money :amount="$p->amount" class="text-gray-800 dark:text-white/90" /></td>
              <td class="text-right">
                @can('delete repayments')
                  <x-staff.confirm id="reverse-{{ $p->id }}" :action="route('staff.repayments.reverse', $p)" title="Reverse payment #{{ $p->id }}?"
                    trigger="Reverse" confirm="Reverse payment" tone="danger"
                    trigger-class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">
                    KES {{ number_format($p->amount, 2) }} ({{ $p->transaction ?: 'no reference' }}) is removed through the existing repayment delete. The cycle and loan status are re-checked by that path. Your reason goes to the audit log.
                    <x-slot:fields>
                      <x-staff.field name="reason" id="reason-{{ $p->id }}" label="Reason" type="textarea" required minlength="5" maxlength="500" />
                    </x-slot:fields>
                  </x-staff.confirm>
                @endcan
              </td>
            </tr>
          @endforeach
        </x-staff.table>
        <p class="mt-2 text-theme-xs text-gray-500">Reversal deletes the row today (the audit log keeps the old values and your reason). Proper reversal entries that keep the original are Stage 4.</p>
      @endif
    </section>
  @endif
@endsection
