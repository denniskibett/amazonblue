{{-- Local-only gallery of the staff components (x-staff.*). Route: /staff/_components. See docs/stage2/README.md --}}
@extends('layouts.app')

@php
  $loanStatuses = array_keys(array_diff_key(config('az.loan_statuses'), ['active' => 1]));
  $timeline = [
    ['label' => 'Applied', 'detail' => 'Self-service application', 'date' => now()->subDays(12), 'done' => true],
    ['label' => 'Approved', 'detail' => 'By the manager', 'date' => now()->subDays(11), 'done' => true],
    ['label' => 'Disbursed', 'detail' => 'KES 10,000.00 by M-Pesa', 'date' => now()->subDays(11), 'done' => true],
    ['label' => 'Overdue', 'detail' => '4 days late', 'date' => now()->subDays(1), 'done' => false, 'tone' => 'red'],
    ['label' => 'Repaid', 'detail' => null, 'date' => null, 'done' => false],
  ];
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
  $h2 = 'mb-4 text-base font-semibold text-gray-800 dark:text-white/90';
@endphp

@section('content')
  <div class="mb-6">
    <h1 class="text-title-sm font-semibold text-gray-800 dark:text-white/90">Components</h1>
    <p class="text-theme-sm text-gray-500 dark:text-gray-400">Staff side (<code>x-staff.*</code>). Local only. Customer versions: <code>/_components</code>.</p>
  </div>

  <div class="grid grid-cols-1 gap-6">
    <section class="{{ $card }}">
      <h2 class="{{ $h2 }}">x-staff.status-badge</h2>
      <div class="flex flex-wrap gap-2">
        @foreach ($loanStatuses as $s)<x-staff.status-badge :status="$s" />@endforeach
      </div>
      <div class="mt-4 flex flex-wrap gap-2">
        @foreach (array_keys(config('az.payment_statuses')) as $s)<x-staff.status-badge :status="$s" type="payment" />@endforeach
      </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <section class="{{ $card }}">
        <h2 class="{{ $h2 }}">x-staff.money</h2>
        <dl class="divide-y divide-gray-100 text-theme-sm dark:divide-gray-800">
          <div class="flex justify-between py-2"><dt class="text-gray-500">Default</dt><dd><x-staff.money amount="12500" /></dd></div>
          <div class="flex justify-between py-2"><dt class="text-gray-500">sign, tone=auto (in)</dt><dd><x-staff.money amount="10000" sign tone="auto" /></dd></div>
          <div class="flex justify-between py-2"><dt class="text-gray-500">tone=auto (out)</dt><dd><x-staff.money amount="-2700.5" tone="auto" /></dd></div>
          <div class="flex justify-between py-2"><dt class="text-gray-500">tone=muted, no currency</dt><dd><x-staff.money amount="0" tone="muted" :currency="false" /></dd></div>
        </dl>
      </section>
      <section class="{{ $card }}">
        <h2 class="{{ $h2 }}">x-staff.timeline</h2>
        <x-staff.timeline :items="$timeline" />
      </section>
    </div>

    <section class="{{ $card }}">
      <h2 class="{{ $h2 }}">x-staff.table</h2>
      <x-staff.table caption="Loans" :headers="['Loan', 'Borrower', 'Disbursed', 'Status', ['Principal', 'num'], ['Balance', 'num']]">
        <tr><td>#00412</td><td>Amina Wanjiru</td><td>8 Oct 2026</td><td><x-staff.status-badge status="disbursed" /></td><td class="num"><x-staff.money amount="10000" /></td><td class="num"><x-staff.money amount="5400" /></td></tr>
        <tr><td>#00398</td><td>Brian Otieno</td><td>12 Sep 2026</td><td><x-staff.status-badge status="overdue" /></td><td class="num"><x-staff.money amount="8000" /></td><td class="num"><x-staff.money amount="9600" tone="out" /></td></tr>
        <tr><td>#00377</td><td>Grace Muthoni</td><td>—</td><td><x-staff.status-badge status="rejected" /></td><td class="num"><x-staff.money amount="15000" /></td><td class="num"><x-staff.money amount="0" tone="muted" /></td></tr>
      </x-staff.table>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <section class="{{ $card }}">
        <h2 class="{{ $h2 }}">x-staff.alert</h2>
        <div class="space-y-3">
          <x-staff.alert type="info" title="Info">3 applications are waiting for review.</x-staff.alert>
          <x-staff.alert type="ok" title="Repayment recorded">KES 3,000.00 on loan #00412.</x-staff.alert>
          <x-staff.alert type="warn" title="Check before approving">The borrower has a loan in forbearance.</x-staff.alert>
          <x-staff.alert type="err" title="Could not disburse">The house wallet does not hold enough.</x-staff.alert>
        </div>
      </section>
      <section class="{{ $card }}">
        <h2 class="{{ $h2 }}">x-staff.empty</h2>
        <x-staff.empty title="No overdue loans" message="Loans that pass their due date without full payment show up here.">
          <a href="#" class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">All loans</a>
        </x-staff.empty>
      </section>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <section class="{{ $card }}">
        <h2 class="{{ $h2 }}">x-staff.field</h2>
        <div class="space-y-4">
          <x-staff.field name="demo_amount" label="Amount (KES)" type="number" required hint="Between 1,000 and 50,000" placeholder="10000" />
          <x-staff.field name="demo_type" label="Loan type" type="select" :options="['1' => 'Emergency 10 days', '2' => 'Quick loan 1 month']" placeholder="Choose one" />
          <x-staff.field name="demo_ref" label="M-Pesa reference" value="QJK" error="M-Pesa references are 10 characters." required />
          <x-staff.field name="demo_note" label="Note" type="textarea" />
        </div>
      </section>
      <section class="{{ $card }}">
        <h2 class="{{ $h2 }}">x-staff.confirm</h2>
        <div class="flex flex-wrap gap-3">
          <x-staff.confirm id="demo-reject" action="#" trigger="Reject loan" title="Reject loan #00377?" confirm="Reject" tone="danger">
            The borrower is told by SMS and email.
            <x-slot:fields><x-staff.field name="rejection_reason" label="Reason" type="textarea" required /></x-slot:fields>
          </x-staff.confirm>
          <x-staff.confirm id="demo-approve" action="#" trigger="Approve" title="Approve loan #00412?" confirm="Approve">It moves to the disbursement queue.</x-staff.confirm>
        </div>
        <h2 class="{{ $h2 }} mt-6">x-staff.placeholder</h2>
        <x-staff.placeholder dependency="M-Pesa C2B callback" title="Auto-matched repayments">Repayments arrive here without typing them in.</x-staff.placeholder>
      </section>
    </div>
  </div>
@endsection
