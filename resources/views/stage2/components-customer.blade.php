{{-- Local-only gallery of the customer components (x-az.*). Route: /_components. See docs/stage2/README.md --}}
@extends('home.layouts.account', ['title' => 'Components', 'tab' => null])

@php
  $loanStatuses = array_keys(array_diff_key(config('az.loan_statuses'), ['active' => 1]));
  $timeline = [
    ['label' => 'Applied', 'detail' => 'Application received', 'date' => now()->subDays(12), 'done' => true],
    ['label' => 'Approved', 'detail' => 'Checked by our team', 'date' => now()->subDays(11), 'done' => true],
    ['label' => 'Sent to M-Pesa', 'detail' => 'KES 10,000.00 to 0712 345 678', 'date' => now()->subDays(11), 'done' => true],
    ['label' => 'Repay', 'detail' => 'KES 5,400.00 left, due in 3 days', 'date' => null, 'done' => false, 'current' => true],
    ['label' => 'Repaid', 'detail' => 'Your limit goes up', 'date' => null, 'done' => false],
  ];
@endphp

@section('content')
  <header class="acct-head">
    <h1>Components</h1>
    <p>Customer side (<code>x-az.*</code>). Local only. Staff versions: <code>/staff/_components</code>.</p>
  </header>

  <section class="a-card">
    <div class="a-sec-h"><h2>x-az.status-badge</h2></div>
    <p class="muted" style="font-size:14px;margin-bottom:12px">Loan statuses (customer words):</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      @foreach ($loanStatuses as $s)<x-az.status-badge :status="$s" />@endforeach
    </div>
    <p class="muted" style="font-size:14px;margin:16px 0 12px">Payment states:</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      @foreach (array_keys(config('az.payment_statuses')) as $s)<x-az.status-badge :status="$s" type="payment" />@endforeach
    </div>
  </section>

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>x-az.money</h2></div>
      <div class="a-list">
        <div class="a-row"><span class="t"><b>Default</b></span><x-az.money amount="12500" /></div>
        <div class="a-row"><span class="t"><b>sign, tone=auto (in)</b></span><x-az.money amount="10000" sign tone="auto" /></div>
        <div class="a-row"><span class="t"><b>tone=auto (out)</b></span><x-az.money amount="-2700.5" tone="auto" /></div>
        <div class="a-row"><span class="t"><b>tone=muted, no currency</b></span><x-az.money amount="0" tone="muted" :currency="false" /></div>
      </div>
    </section>
    <section class="a-card">
      <div class="a-sec-h"><h2>x-az.timeline</h2></div>
      <x-az.timeline :items="$timeline" style="margin-top:10px" />
    </section>
  </div>

  <section class="a-card">
    <div class="a-sec-h"><h2>x-az.table</h2></div>
    <x-az.table caption="Payments" :headers="['Date', 'Reference', 'Method', 'Status', ['Amount', 'num']]">
      <tr><td>14 Oct 2026</td><td>QJK8X2LM4P</td><td>M-Pesa</td><td><x-az.status-badge status="received" type="payment" /></td><td class="num"><x-az.money amount="3000" /></td></tr>
      <tr><td>17 Oct 2026</td><td>QJL1C7TT9A</td><td>M-Pesa</td><td><x-az.status-badge status="pending" type="payment" /></td><td class="num"><x-az.money amount="2000" /></td></tr>
      <tr><td>18 Oct 2026</td><td>QJL4Z0RR2B</td><td>Bank</td><td><x-az.status-badge status="failed" type="payment" /></td><td class="num"><x-az.money amount="1500" /></td></tr>
    </x-az.table>
  </section>

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>x-az.alert</h2></div>
      <div style="display:grid;gap:10px">
        <x-az.alert type="info" title="Info">Your application is with our team.</x-az.alert>
        <x-az.alert type="ok" title="Payment received">KES 3,000.00 on 14 Oct.</x-az.alert>
        <x-az.alert type="warn" title="Repayment due in 3 days">KES 5,400.00 is due on 22 Oct.</x-az.alert>
        <x-az.alert type="err" title="Payment overdue">Pay now to avoid a late fee.</x-az.alert>
      </div>
    </section>
    <section class="a-card">
      <div class="a-sec-h"><h2>x-az.empty</h2></div>
      <x-az.empty title="No loans yet" message="When you borrow, your loans show up here.">
        <a class="btn btn-primary" href="#">Borrow</a>
      </x-az.empty>
    </section>
  </div>

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>x-az.field</h2></div>
      <div style="display:grid;gap:16px">
        <x-az.field name="demo_amount" label="Amount (KES)" type="number" required hint="Between 1,000 and 50,000" placeholder="10000" />
        <x-az.field name="demo_purpose" label="Purpose" type="select" :options="['school' => 'School fees', 'stock' => 'Business stock', 'other' => 'Something else']" placeholder="Choose one" />
        <x-az.field name="demo_phone" label="M-Pesa number" value="0712" error="Enter a full Safaricom number, e.g. 0712 345 678." required />
        <x-az.field name="demo_note" label="Anything we should know?" type="textarea" />
      </div>
    </section>
    <section class="a-card">
      <div class="a-sec-h"><h2>x-az.confirm</h2></div>
      <p class="muted" style="font-size:14px;margin-bottom:14px">Opens a dialog; confirming submits a form.</p>
      <div style="display:flex;flex-wrap:wrap;gap:10px">
        <x-az.confirm id="demo-confirm" action="#" trigger="Cancel application" title="Cancel this application?" confirm="Yes, cancel" tone="danger">You can apply again at any time.</x-az.confirm>
        <x-az.confirm id="demo-confirm-2" action="#" trigger="With a field" trigger-class="btn btn-secondary" title="Request a statement" confirm="Send">
          We email it within a day.
          <x-slot:fields><x-az.field name="demo_email" label="Email" type="email" required /></x-slot:fields>
        </x-az.confirm>
      </div>
      <div style="margin-top:22px">
        <div class="a-sec-h"><h2>x-az.placeholder</h2></div>
        <x-az.placeholder dependency="M-Pesa STK push" title="Pay from your phone">Approve the payment on your phone instead of typing the paybill.</x-az.placeholder>
      </div>
    </section>
  </div>
@endsection
