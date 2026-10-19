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

  <form method="GET" action="{{ route('staff.repayments.receive') }}" class="{{ $card }} mb-6 flex flex-col gap-3 sm:flex-row sm:items-end" role="search">
    <div class="min-w-0 flex-1">
      <label for="q" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Loan number, borrower name, phone, email or ID number</label>
      <input id="q" name="q" value="{{ $q }}" class="{{ $input }}" placeholder="e.g. 545 or Amina" autocomplete="off">
    </div>
    <button class="h-11 rounded-lg bg-brand-500 px-5 text-theme-sm font-medium text-white hover:bg-brand-600">Search</button>
  </form>

  @if ($q !== '')
    <section class="mb-6">
      @if ($results->isEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
          <x-staff.empty title="No open loan found" message="Only disbursed, overdue, defaulted, recovery or forbearance loans take payments." />
        </div>
      @else
        <x-staff.table caption="Search results" :headers="['Loan', 'Borrower', 'Product', 'Status', ['Amount', 'num'], '']">
          @foreach ($results as $r)
            <tr @class(['bg-brand-50/50 dark:bg-brand-500/10' => $loan && $loan->id === $r->id])>
              <td class="font-medium">#{{ $r->id }}</td>
              <td>{{ $r->user->name ?? '—' }}<span class="block text-theme-xs text-gray-500">{{ $r->user->phone ?? '' }}</span></td>
              <td>{{ $r->loanType->name ?? '—' }}</td>
              <td><x-staff.status-badge :status="$r->status" /></td>
              <td class="num"><x-staff.money :amount="$r->amount" /></td>
              <td class="text-right"><a href="{{ route('staff.repayments.receive', ['q' => $q, 'loan' => $r->id]) }}" class="inline-flex rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600">Select</a></td>
            </tr>
          @endforeach
        </x-staff.table>
      @endif
    </section>
  @endif

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
      <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Recent payments on this loan</h2>
      @if ($recent->isEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><x-staff.empty title="No payments yet" /></div>
      @else
        <x-staff.table caption="Recent payments" :headers="['#', 'Date', 'Cycle', 'Mode', 'Reference', ['Amount', 'num'], '']">
          @foreach ($recent as $p)
            <tr>
              <td>{{ $p->id }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($p->repayment_date)->format('j M Y') }}</td>
              <td>{{ optional($cycles->firstWhere('id', $p->loan_cycle_id))->cycle_number ?? '—' }}</td>
              <td>{{ $p->mode ?: '—' }}</td>
              <td>{{ $p->transaction ?: '—' }}</td>
              <td class="num"><x-staff.money :amount="$p->amount" /></td>
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
  @elseif ($q === '')
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <x-staff.empty title="Search for a loan to start" message="Payments are recorded against one of the loan's own cycles." />
    </div>
  @endif
@endsection
