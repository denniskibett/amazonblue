{{-- Add funds: where to send money (App\Support\PartnerFunding: /system, else config lending.partner_funding) and an "I've sent funds" form that only
     emails staff + writes an audit row. Staff record the contribution after checking the bank / M-Pesa statement. --}}
@extends('home.layouts.account', ['title' => 'Add funds', 'tab' => 'home', 'area' => 'partner'])

@php
  $hasBank = filled($cfg['bank_account_number'] ?? null);
  $hasMpesa = filled($cfg['mpesa_paybill'] ?? null);
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.partner.home') }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>Home</a>
    <div class="acct-head">
      <h1>Add funds</h1>
      <p>Send from an account in your own name and use your reference exactly as shown.</p>
    </div>
  </div>

  @if ($sent = session('partner_sent'))
    <x-az.alert type="ok" :title="$sent['title']">{{ $sent['body'] }}</x-az.alert>
  @endif

  <div class="a-grid two">
    <div class="a-grid">
      <section class="a-card">
        <div class="a-sec-h"><h2>Bank transfer</h2></div>
        @if ($hasBank)
          <dl class="a-dl">
            <div><dt>Bank</dt><dd>{{ $cfg['bank_name'] }}</dd></div>
            <div><dt>Account name</dt><dd>{{ $cfg['bank_account_name'] }}</dd></div>
            <div><dt>Account number</dt><dd class="p-copy">{{ $cfg['bank_account_number'] }}</dd></div>
            @if (filled($cfg['bank_branch']) || filled($cfg['bank_swift']))<div><dt>Branch / SWIFT</dt><dd>{{ collect([$cfg['bank_branch'], $cfg['bank_swift']])->filter()->implode(' · ') }}</dd></div>@endif
            <div><dt>Reference</dt><dd class="p-copy">{{ $reference }}</dd></div>
          </dl>
        @else
          <x-az.alert type="info" title="Bank details not set yet">Our team will share the account to pay into. Your reference is {{ $reference }}.</x-az.alert>
        @endif
      </section>

      <section class="a-card">
        <div class="a-sec-h"><h2>M-Pesa</h2></div>
        @if ($hasMpesa)
          <dl class="a-dl">
            <div><dt>Paybill</dt><dd class="p-copy">{{ $cfg['mpesa_paybill'] }}</dd></div>
            <div><dt>Account no.</dt><dd class="p-copy">{{ $cfg['mpesa_account'] ?? $reference }}</dd></div>
          </dl>
          @if (filled($cfg['mpesa_account'] ?? null))
            <p class="muted" style="font-size:13px;margin-top:8px">M-Pesa has no space for your reference, so send us the M-Pesa message with your reference <b>{{ $reference }}</b> using the form below.</p>
          @endif
        @else
          <x-az.alert type="info" title="Paybill not set yet">Use bank transfer, or message us for the M-Pesa details. Your account no. is {{ $reference }}.</x-az.alert>
        @endif
      </section>

      <x-az.alert type="warn" title="Third-party payments are returned">Money from an account that is not in your name can't be accepted.</x-az.alert>
    </div>

    <section class="a-card">
      <div class="a-sec-h"><h2>I've sent funds</h2></div>
      <p class="muted" style="margin:4px 0 14px;font-size:14px">Tell us what you sent so we can match it. Your capital goes up once our team confirms the money has arrived.</p>
      <form method="POST" action="{{ route('account.partner.funds.store') }}" class="a-grid" style="gap:12px">
        @csrf
        <x-az.field name="amount" label="Amount (KES)" type="number" required step="0.01" :min="\App\Support\PartnerFunding::minAmount()" inputmode="decimal" />
        <x-az.field name="method" label="How you sent it" type="select" required :options="['bank' => 'Bank transfer', 'mpesa' => 'M-Pesa']" placeholder="Choose one" />
        <x-az.field name="transaction_code" label="Transaction code" required hint="The bank or M-Pesa confirmation code" maxlength="60" />
        <x-az.field name="sent_on" label="Date sent" type="date" required :value="now()->toDateString()" :max="now()->toDateString()" />
        <x-az.field name="note" label="Note (optional)" type="textarea" maxlength="500" rows="2" />
        <button class="btn btn-primary btn-block" type="submit">Let our team know</button>
      </form>
      <x-az.placeholder dependency="funding requests table + staff confirmation" style="margin-top:14px">
        Today this sends an email to our team. Tracking each request and its confirmation in your account comes later.
      </x-az.placeholder>
    </section>
  </div>
@endsection
