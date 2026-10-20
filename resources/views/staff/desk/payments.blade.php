{{-- Cashier · M-Pesa payments (Stage 4 Phase 4): unmatched payments to assign, borrowers' claims to confirm, today's
     matched payments, and the statement reconciliation. Every money move goes through Services\Mpesa\PaymentRecorder. --}}
@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'M-Pesa payments', 'sub' => 'Payments M-Pesa reported that need a person, borrowers\' "I\'ve paid" claims, and the statement check.'])

  @unless ($mpesaOn)
    <x-staff.alert type="warn" class="mb-6">Automatic recording is off (MPESA_ENABLED). Payments Safaricom reports are kept here for you to assign.</x-staff.alert>
  @endunless

  <section class="mb-8">
    <h2 class="mb-3 text-theme-lg font-semibold text-gray-800 dark:text-white/90">Not matched to a loan ({{ $unmatched->count() }})</h2>
    @if ($unmatched->isEmpty())
      <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><x-staff.empty title="Nothing waiting" message="Every M-Pesa payment is matched to a loan." /></div>
    @else
      <x-staff.table caption="Unmatched M-Pesa payments" :headers="['Receipt', 'Received', ['Amount', 'num'], 'Account no.', 'Phone', 'Why', 'Record on loan']">
        @foreach ($unmatched as $t)
          <tr>
            <td class="font-mono">{{ $t->trans_id }}</td>
            <td>{{ optional($t->trans_time ?? $t->created_at)->format('j M, H:i') }}</td>
            <td class="num"><x-staff.money :amount="$t->amount" /></td>
            <td>{{ $t->bill_ref ?: '—' }}</td>
            <td>{{ $t->maskedMsisdn() ?: '—' }}</td>
            <td class="text-theme-xs text-gray-500">{{ $t->note }}</td>
            <td>
              <form method="POST" action="{{ route('staff.payments.assign', $t) }}" class="flex items-center gap-2">
                @csrf
                <input name="loan_id" type="number" min="1" required placeholder="Loan #" value="{{ preg_replace('/\D/', '', (string) $t->bill_ref) }}"
                  class="h-9 w-24 rounded-lg border border-gray-300 px-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900" aria-label="Loan number for {{ $t->trans_id }}">
                <button class="rounded-lg bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white hover:bg-brand-600">Record</button>
              </form>
            </td>
          </tr>
        @endforeach
      </x-staff.table>
    @endif
  </section>

  <section class="mb-8">
    <h2 class="mb-3 text-theme-lg font-semibold text-gray-800 dark:text-white/90">Borrowers' "I've paid" claims ({{ $claims->count() }})</h2>
    @if ($claims->isEmpty())
      <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><x-staff.empty title="No claims waiting" /></div>
    @else
      <x-staff.table caption="Payment claims being checked" :headers="['Code', 'Borrower', 'Loan', ['Amount', 'num'], 'Told us', 'Decision']">
        @foreach ($claims as $c)
          <tr>
            <td class="font-mono">{{ $c->code }}</td>
            <td>{{ $c->user->name ?? '—' }}</td>
            <td>#{{ $c->loan_id }}</td>
            <td class="num"><x-staff.money :amount="$c->amount" /></td>
            <td>{{ $c->created_at->format('j M, H:i') }}</td>
            <td>
              <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('staff.payments.claim', $c) }}">@csrf<input type="hidden" name="decision" value="match">
                  <button class="rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium hover:border-brand-500 dark:border-gray-700">Match now</button></form>
                <form method="POST" action="{{ route('staff.payments.claim', $c) }}" class="flex items-center gap-2">@csrf<input type="hidden" name="decision" value="reject">
                  <input name="reason" required maxlength="200" placeholder="Why (the borrower sees it)" class="h-9 w-56 rounded-lg border border-gray-300 px-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900" aria-label="Reason for rejecting {{ $c->code }}">
                  <button class="rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium text-error-600 hover:border-error-500 dark:border-gray-700">Not found</button></form>
              </div>
            </td>
          </tr>
        @endforeach
      </x-staff.table>
    @endif
  </section>

  <section class="mb-8">
    <h2 class="mb-3 text-theme-lg font-semibold text-gray-800 dark:text-white/90">Check against the M-Pesa statement</h2>
    <form method="POST" action="{{ route('staff.payments.reconcile') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
      @csrf
      <label class="text-theme-sm text-gray-700 dark:text-gray-300" for="statement">M-Pesa organisation statement (CSV)</label>
      <input id="statement" name="statement" type="file" accept=".csv,text/csv" required class="text-theme-sm">
      <button class="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-brand-600">Check</button>
    </form>
    @if ($recon)
      <div class="mt-4 space-y-3 text-theme-sm">
        <p>{{ $recon['statement_rows'] }} payment(s) on the statement{{ $recon['from'] ? ', ' . $recon['from'] . ' to ' . $recon['to'] : '' }}: <b>{{ $recon['ok'] }}</b> recorded exactly.</p>
        @foreach (['on_statement_not_recorded' => 'On the statement, not recorded', 'amount_differs' => 'Amount differs', 'recorded_not_on_statement' => 'Recorded as M-Pesa, not on the statement'] as $key => $label)
          <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-800">
            <b>{{ $label }}: {{ count($recon[$key]) }}</b>
            @foreach ($recon[$key] as $row)
              <div class="font-mono text-theme-xs text-gray-600 dark:text-gray-400">{{ collect($row)->map(fn ($v, $k) => $k . ' ' . $v)->implode(' · ') }}</div>
            @endforeach
          </div>
        @endforeach
      </div>
    @endif
  </section>

  <section>
    <h2 class="mb-3 text-theme-lg font-semibold text-gray-800 dark:text-white/90">Recently matched</h2>
    <x-staff.table caption="Matched M-Pesa payments" :headers="['Receipt', 'Type', ['Amount', 'num'], 'Loan', 'Repayment', 'How']">
      @forelse ($matched as $t)
        <tr><td class="font-mono">{{ $t->trans_id }}</td><td>{{ strtoupper($t->type) }}</td><td class="num"><x-staff.money :amount="$t->amount" /></td>
          <td>#{{ $t->loan_id }}</td><td>#{{ $t->repayment_id }}</td><td class="text-theme-xs text-gray-500">{{ $t->note }}</td></tr>
      @empty
        <tr><td colspan="6" class="text-theme-sm text-gray-500">None yet.</td></tr>
      @endforelse
    </x-staff.table>
  </section>
@endsection
