{{-- One subscription (owner, 23 Oct 2026): where to send the money, "I've sent it", the signed copy and, once confirmed, the line. --}}
@extends('home.layouts.account', ['title' => 'Subscription', 'tab' => 'home', 'area' => 'partner'])

@php
  $p = $r->payload;
  $waiting = $r->status === 'pending' && ! $r->reference;
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('partner.subscribe') }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>Subscriptions</a>
    <div class="acct-head">
      <h1>KES {{ number_format((float) $r->amount, 2) }} · {{ $p['term'] === 'long_term' ? $p['months'] . '-month line' : 'short-term line' }}</h1>
      <p>Signed {{ \Illuminate\Support\Carbon::parse($p['signed_at'])->format('j M Y, H:i') }}. <a href="{{ route('partner.subscriptions.file', $r->id) }}" target="_blank" rel="noopener">Open the signed copy</a></p>
    </div>
  </div>

  @if ($sent = session('partner_sent'))<x-az.alert type="ok" :title="$sent['title']">{{ $sent['body'] }}</x-az.alert>@endif

  @if ($r->status === 'done')
    <x-az.alert type="ok" title="Your line is open">Our team confirmed the money{{ $line ? ' and opened line #' . $line->id : '' }}. It shows on your <a href="{{ route('account.partner.investments') }}">investments</a> and statement.</x-az.alert>
  @elseif ($r->status === 'declined')
    <x-az.alert type="warn" title="This subscription is closed">{{ $r->decision_note ?: 'Nothing was added to your account.' }}</x-az.alert>
  @elseif (! $waiting)
    <x-az.alert type="info" title="We are checking the money">You told us code {{ $r->reference }}. The line opens once our team confirms it on the statement.</x-az.alert>
  @endif

  @if ($waiting)
    <div class="a-grid two">
      <section class="a-card">
        <div class="a-sec-h"><h2>1. Send the money</h2></div>
        <dl class="a-dl">
          @if (filled($cfg['bank_account_number'] ?? null))
            <div><dt>Bank</dt><dd>{{ $cfg['bank_name'] }} · {{ $cfg['bank_account_name'] }}</dd></div>
            <div><dt>Account number</dt><dd class="p-copy">{{ $cfg['bank_account_number'] }}</dd></div>
          @endif
          @if (filled($cfg['mpesa_paybill'] ?? null))
            <div><dt>M-Pesa paybill</dt><dd class="p-copy">{{ $cfg['mpesa_paybill'] }}</dd></div>
            <div><dt>Account no.</dt><dd class="p-copy">{{ $cfg['mpesa_account'] ?? $reference }}</dd></div>
          @endif
          <div><dt>Amount</dt><dd>KES {{ number_format((float) $r->amount, 2) }}</dd></div>
          <div><dt>Your reference</dt><dd class="p-copy">{{ $reference }}</dd></div>
        </dl>
        <x-az.alert type="warn" title="From your own account" style="margin-top:12px">Money from an account that is not in your name is returned.</x-az.alert>
      </section>

      <section class="a-card">
        <div class="a-sec-h"><h2>2. Tell us the code</h2></div>
        <form method="POST" action="{{ route('partner.subscriptions.sent', $r->id) }}" class="a-grid" style="gap:12px">
          @csrf
          <x-az.field name="method" label="How you sent it" type="select" required :options="['mpesa' => 'M-Pesa', 'bank' => 'Bank transfer']" placeholder="Choose one" />
          <x-az.field name="transaction_code" label="Transaction code" required maxlength="60" hint="The M-Pesa code or the bank's transaction reference" />
          <x-az.field name="sent_on" label="Date sent" type="date" required :value="now()->toDateString()" :max="now()->toDateString()" />
          <button class="btn btn-primary btn-block" type="submit">I've sent it</button>
        </form>
        <form method="POST" action="{{ route('partner.subscriptions.cancel', $r->id) }}" style="margin-top:10px" onsubmit="return confirm('Cancel this subscription?')">
          @csrf<button class="btn btn-ghost btn-block" type="submit">Cancel this subscription</button>
        </form>
      </section>
    </div>
  @endif
@endsection
