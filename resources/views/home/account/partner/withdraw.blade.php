{{-- Withdrawal request: up to the chosen wallet's balance, paid to the payout method on the partner record.
     Emails staff + audit row only; staff pay out and record it (Partner::withdrawPartnerBalance). --}}
@extends('home.layouts.account', ['title' => 'Withdraw', 'tab' => 'home', 'area' => 'partner'])

@php
  $available = collect($wallets)->filter(fn ($w) => $w['balance'] > 0);
  $default = old('wallet', $available->keys()->first() ?? 'principal');
  $total = array_sum(array_column($wallets, 'balance'));
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.partner.home') }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>Home</a>
    <div class="acct-head">
      <h1>Withdraw</h1>
      <p>Ask our team to pay out part of your balance.</p>
    </div>
  </div>

  @if ($sent = session('partner_sent'))
    <x-az.alert type="ok" :title="$sent['title']">{{ $sent['body'] }}</x-az.alert>
  @endif

  <div class="a-stats p-three">
    @foreach ($wallets as $w)
      <div class="a-stat"><span>{{ $w['label'] }}</span><b><x-az.money :amount="$w['balance']" /></b></div>
    @endforeach
  </div>
  @if ($wallets['principal']['lent_out'] > 0)
    <p class="muted" style="font-size:12.5px">Capital KES {{ number_format($wallets['principal']['wallet'], 2) }}, of which KES {{ number_format($wallets['principal']['lent_out'], 2) }} is lent out to borrowers and comes back as they repay.</p>
  @endif

  <div class="a-grid two">
    <section class="a-card">
      @if ($total <= 0)
        <x-az.empty title="Nothing to withdraw yet" message="Your wallets are at KES 0.00. Earnings and capital you add show here once our team records them.">
          <a class="btn btn-primary" href="{{ route('account.partner.funds') }}">Add funds</a>
        </x-az.empty>
      @elseif (! $payout)
        <x-az.alert type="warn" title="No payout details on file">We need a bank account or M-Pesa number in your name before we can pay you. Message us on WhatsApp to add one.</x-az.alert>
      @else
        <div class="a-sec-h"><h2>Request a withdrawal</h2></div>
        <div x-data="{ wallet: @js($default), max: @js(collect($wallets)->map(fn ($w) => round($w['balance'], 2))), amount: @js(old('amount', request('amount'))), note: @js(old('note', '')) }">
          <div class="a-grid" style="gap:12px;margin-top:10px">
            <x-az.field name="wallet" label="From" type="select" required
              :options="collect($wallets)->map(fn ($w) => $w['label'] . ' · KES ' . number_format($w['balance'], 2))->all()" x-model="wallet" />
            <x-az.field name="amount" label="Amount (KES)" type="number" required step="0.01" :min="$min" inputmode="decimal"
              x-model="amount" x-bind:max="max[wallet]" hint="You can request up to the balance of the wallet you choose." />
            <p class="muted" style="margin:0;font-size:14px">Up to <b x-text="'KES ' + Number(max[wallet] || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></b>
              <button type="button" class="btn btn-ghost" style="min-height:32px;padding:0 12px;margin-left:6px" x-on:click="amount = max[wallet]">Use all</button></p>
            <x-az.field name="note" label="Note (optional)" type="textarea" maxlength="500" rows="2" x-model="note" />
            <div class="a-dl"><div><dt>Paid to</dt><dd>{{ $payout['label'] }}</dd></div></div>
            <p class="muted" x-show="Number(amount) > Number(max[wallet])" style="color:var(--rose-d);margin:0;font-size:14px" role="alert">That is more than this wallet holds.</p>
          </div>

          <div style="margin-top:16px" x-bind:style="Number(amount) > Number(max[wallet]) || ! (Number(amount) > 0) ? 'opacity:.5;pointer-events:none' : ''">
            <x-az.confirm id="withdraw-confirm" :action="route('account.partner.withdraw.store')" title="Send this withdrawal request?"
              trigger="Request withdrawal" trigger-class="btn btn-primary btn-block" confirm="Yes, send request">
              <span x-text="'KES ' + Number(amount || 0).toLocaleString('en-KE', { minimumFractionDigits: 2 })"></span> to {{ $payout['label'] }}.
              Our team pays it out and records it on your statement; your balance changes once it is paid.
              <x-slot:fields>
                <input type="hidden" name="wallet" x-bind:value="wallet" />
                <input type="hidden" name="amount" x-bind:value="amount" />
                <input type="hidden" name="note" x-bind:value="note" />
              </x-slot:fields>
            </x-az.confirm>
          </div>
        </div>
      @endif
    </section>

    <x-az.placeholder dependency="withdrawal requests table + maker-checker approval + PartnerLedger posting" title="How withdrawals work today">
      Your request is emailed to our team and logged. A staff member checks your balance, pays you, and records the withdrawal, which then shows on your statement.
      Tracking the request status in your account comes later.
    </x-az.placeholder>
  </div>
@endsection
