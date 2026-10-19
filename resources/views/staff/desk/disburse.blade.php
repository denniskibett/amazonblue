{{-- Teller · disburse. Posts to the existing disbursements.store (JSON) by AJAX; the controller decides
     funding source, house default, processing fee, investment draw and the loan status. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
  $default = $loan->original_amount ?: $loan->amount;
  $ra = $loan->getLatestRiskAssessment();
  $invOptions = $investments->mapWithKeys(fn ($i) => [$i->id => $i->name . ($i->id === $houseId ? ' (house default)' : '')])->all();
@endphp

@section('content')
  @include('staff.desk._header', [
    'title' => 'Disburse loan #' . $loan->id,
    'sub' => ($loan->user->name ?? 'Borrower') . ' · ' . ($loan->loanType->name ?? 'loan'),
    'actions' => '<a href="' . route('staff.disbursements.queue') . '" class="text-theme-sm font-medium text-brand-500 hover:underline">← Back to queue</a>',
  ])
  @include('staff.desk._ajax')

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
    <section class="{{ $card }} min-w-0 lg:col-span-3">
      @if ($loan->status !== 'approved')
        <x-staff.alert type="warn" title="This loan is {{ $loan->status }}">Only approved loans are disbursed from this screen.</x-staff.alert>
      @else
        <form method="POST" action="{{ route('disbursements.store') }}" data-ajax-form data-done="{{ route('staff.disbursements.queue', ['done' => 'disbursed']) }}" class="space-y-4">
          @csrf
          <input type="hidden" name="loan_id" value="{{ $loan->id }}">
          @if ($cycle)<input type="hidden" name="loan_cycle_id" value="{{ $cycle->id }}">@endif
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-staff.field name="amount" label="Amount (KES)" type="number" step="0.01" min="0.01" :value="number_format($default, 2, '.', '')" required
              hint="Principal{{ $loan->original_amount ? '' : ' (no original amount recorded: loan amount used)' }}." />
            <x-staff.field name="disburse_date" label="Disbursement date" type="date" :value="now()->toDateString()" required />
          </div>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-staff.field name="mode" label="How the money was sent" type="select" :options="['mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank transfer', 'cash' => 'Cash', 'cheque' => 'Cheque']" value="mpesa" />
            <x-staff.field name="transaction" label="Transaction reference" required placeholder="e.g. M-Pesa code" maxlength="255" />
          </div>
          <x-staff.field name="investment_id" label="Funded from (investment)" type="select" :options="$invOptions" :value="$houseId" placeholder="House default (General Lending Book)" id="f-investment" />
          <div class="rounded-xl bg-gray-50 p-4 text-theme-sm dark:bg-white/[0.03]">
            <p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Partner(s) on this investment</p>
            <p class="mt-1 font-medium text-gray-800 dark:text-white/90" id="partner-names">—</p>
          </div>
          <x-staff.field name="notes" label="Notes" type="textarea" placeholder="Optional" />
          <div data-errors hidden class="rounded-lg border border-error-500/30 bg-error-50 p-3 text-theme-sm text-error-800 dark:bg-error-500/15 dark:text-error-400" role="alert"></div>
          <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-60 sm:w-auto">Record disbursement</button>
        </form>
      @endif
    </section>

    <aside class="min-w-0 space-y-6 lg:col-span-2">
      <section class="{{ $card }}">
        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Loan</h2>
        <dl class="divide-y divide-gray-100 text-theme-sm dark:divide-gray-800">
          <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Status</dt><dd><x-staff.status-badge :status="$loan->status" /></dd></div>
          <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Loan amount</dt><dd><x-staff.money :amount="$loan->amount" /></dd></div>
          <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Principal</dt><dd>@if ($loan->original_amount)<x-staff.money :amount="$loan->original_amount" />@else — @endif</dd></div>
          <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Processing fee rate</dt><dd class="font-outfit">{{ rtrim(rtrim(number_format((float) $loan->processing_fee_rate, 2), '0'), '.') ?: '0' }}%</dd></div>
          <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Current cycle</dt><dd>{{ $cycle ? '#' . $cycle->cycle_number . ', due ' . \Illuminate\Support\Carbon::parse($cycle->due_date)->format('j M Y') : 'None (one is created)' }}</dd></div>
          <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Assessed limit</dt><dd>@if ($ra && $ra->limit_amount !== null)<x-staff.money :amount="$ra->limit_amount" />@else — @endif</dd></div>
          <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Phone</dt><dd>{{ $loan->user->phone ?? '—' }}</dd></div>
        </dl>
      </section>
      <x-staff.placeholder dependency="M-Pesa B2C payout and maker-checker">
        Money is sent outside the system today; this screen records it. Sending by M-Pesa from here and a second approver are Stage 4.
      </x-staff.placeholder>
    </aside>
  </div>

  @push('scripts')
  <script>
    (() => {
      const partners = @json($partners);
      const select = document.getElementById('f-investment');
      const out = document.getElementById('partner-names');
      if (!select || !out) return;
      const show = () => {
        const names = partners[select.value] || [];
        out.textContent = select.value ? (names.length ? names.join(', ') : 'No partner funding recorded (house)') : 'House Account (default)';
      };
      select.addEventListener('change', show);
      show();
    })();
  </script>
  @endpush
@endsection
