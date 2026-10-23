{{-- Teller · disburse. Posts to the existing disbursements.store (JSON) by AJAX; the controller decides
     funding source, house default, processing fee, investment draw and the loan status. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
  $default = $loan->original_amount ?: $loan->amount;
  $ra = $loan->getLatestRiskAssessment();
@endphp

@section('content')
  @include('partials.ui.partner-funding-js')
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
          {{-- mode and references; bank → M-Pesa keeps both (owner, 23 Oct 2026) --}}
          <x-staff.pay-refs direction="out" mode="bank_to_mpesa" date-field="disburse_date" />
          {{-- who brought the client and whose money it is (owner, 23 Oct 2026) --}}
          @php
            $brokerNow = $loan->broker_status && $loan->partner_id ? (int) $loan->partner_id : ($ownPartner?->id ?? 0);
            $lineOpts = $openLines->mapWithKeys(fn ($o) => [$o->line->id => ($o->partner?->name ?? 'Partner') . ' · ' . (\App\Services\Partners\PartnerLines::TERMS[$o->line->facility_type] ?? 'Line') . ' #' . $o->line->id
              . ' · KES ' . number_format($o->room, 2) . ' free' . ($o->line->maturity_date ? ' · back by ' . $o->line->maturity_date->format('j M Y') : '')])->all();
          @endphp
          <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800" x-data="disburseFunding(@js(['house' => $houseId, 'maturity' => $defaultMaturity, 'read' => route('staff.mpesa.read-sms')]))">
            <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Who is behind this loan</p>
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label for="f-broker" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Brought in by (broker)</label>
                <select id="f-broker" name="broker_partner_id" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                  <option value="0" @selected($brokerNow === 0)>Nobody: our own client</option>
                  @foreach ($partnerList as $pid => $pname)<option value="{{ $pid }}" @selected($brokerNow === (int) $pid)>{{ $pname }}</option>@endforeach
                </select>
                <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                  @if ($ownPartner)This is {{ $ownPartner->name }}'s own loan: they pay the full rate and their broker share of each repayment comes back to them.
                  @else The broker earns their share of the interest and penalty on every repayment.@endif
                </p>
              </div>
              <div>
                <span class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Funded by</span>
                <div class="flex flex-wrap gap-2 text-theme-sm">
                  <input type="hidden" name="funding_mode" :value="mode">
                  @foreach (['house' => 'Our money', 'line' => 'A partner line', 'new' => 'New partner money'] as $k => $l)
                    <button type="button" @click="mode = '{{ $k }}'" :class="mode === '{{ $k }}' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'"
                      class="rounded-lg border px-3 py-2 font-medium" @if ($k === 'line' && ! $lineOpts) disabled title="No partner line has money free" @endif>{{ $l }}</button>
                  @endforeach
                </div>
                <input type="hidden" name="investment_id" :value="house" :disabled="mode !== 'house'">
              </div>
            </div>

            <div x-show="mode === 'line'" x-cloak class="mt-4">
              <label for="f-line" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Partner line</label>
              <select id="f-line" name="investment_id" :disabled="mode !== 'line'" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                @foreach ($lineOpts as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach
              </select>
            </div>

            <div x-show="mode === 'new'" x-cloak class="mt-4 space-y-4">
              <div>
                <label for="f-sms" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Paste the M-Pesa message</label>
                <textarea id="f-sms" rows="3" x-model="sms" @input.debounce.500ms="read()" placeholder="e.g. TJN4H7K2LM Confirmed. Ksh50,000.00 received from …"
                  class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                <p class="mt-1 text-theme-xs" :class="readOk ? 'text-success-600' : 'text-amber-700 dark:text-amber-400'" x-text="readMsg" x-show="readMsg"></p>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label for="f-np" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Partner who sent it</label>
                  <select id="f-np" name="new_partner_id" :disabled="mode !== 'new'" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Choose…</option>
                    @foreach ($partnerList as $pid => $pname)<option value="{{ $pid }}">{{ $pname }}</option>@endforeach
                  </select>
                </div>
                <div>
                  <label for="f-na" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Amount received (KES)</label>
                  <input id="f-na" name="new_amount" type="number" step="0.01" min="1" x-model="amount" :disabled="mode !== 'new'" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                  <label for="f-nc" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">M-Pesa or bank code</label>
                  <input id="f-nc" name="new_code" x-model="code" maxlength="40" :disabled="mode !== 'new'" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm uppercase text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div>
                  <label for="f-nd" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Date received</label>
                  <input id="f-nd" name="new_date" type="date" x-model="date" :disabled="mode !== 'new'" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <span class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">How long</span>
                  <input type="hidden" name="new_term" :value="term" :disabled="mode !== 'new'">
                  <div class="flex gap-2 text-theme-sm">
                    <button type="button" @click="term = 'short_term'; maturity = defaults.maturity" :class="term === 'short_term' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Short term</button>
                    <button type="button" @click="term = 'long_term'; maturity = ''" :class="term === 'long_term' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Long term</button>
                  </div>
                  <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400" x-text="term === 'short_term' ? 'For this loan (and any left over for the next). Closes and pays out when its loans are repaid.' : 'Lends again as it comes back, across products, until the pay-back date. Earns their share of the interest.'"></p>
                </div>
                <div>
                  <label for="f-nm" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Pay back by</label>
                  <input id="f-nm" name="new_maturity" type="date" x-model="maturity" :disabled="mode !== 'new'" :required="mode === 'new' && term === 'long_term'" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                  <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">Short term defaults to the loan's due date; change it if you agreed another.</p>
                </div>
              </div>
            </div>
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

@endsection
