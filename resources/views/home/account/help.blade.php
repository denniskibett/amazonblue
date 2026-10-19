@extends('home.layouts.account', ['title' => 'Help', 'tab' => 'home'])

{{-- Support: contact channels (same numbers as the website footer), FAQ, and a message form that emails staff
     and writes an audit row (Concerns\BorrowerJourneys::sendHelp). --}}
@php
  $c = \App\Support\BorrowerLoanState::CONTACT;
  $statusWord = fn ($s) => (config('az.customer_loan_statuses') + config('az.loan_statuses'))[$s] ?? ucfirst($s);
  $loanOptions = $loans->mapWithKeys(fn ($l) => [$l->id => 'Loan #' . str_pad($l->id, 5, '0', STR_PAD_LEFT) . ' · KES ' . number_format((float) $l->amount, 2) . ' · ' . $statusWord($l->status)])->all();
  $faq = [
    ['How do I repay?', 'Pay by M-Pesa from your account (Repay on the loan page). Your balance updates once our team records the payment, usually the same working day.'],
    ['When is my loan sent?', 'After our team approves it, during working hours. You get an M-Pesa message when it is sent.'],
    ['Can I get more time to pay?', 'Yes, ask for an extension from the loan page before the due date. It adds interest for the new period, and our team confirms the cost with you first.'],
    ['How do I raise my limit?', 'Repay in full, by M-Pesa, on or before the due date. Rollovers, discounts and late payments keep your limit lower.'],
    ['How do I change my details?', 'Send us a message below. Verified details are locked to protect you, so our team makes the change after checking it is you.'],
    ['Where is my loan agreement?', 'On each loan page, under "Your loan terms". The copy you open is the exact one you accepted.'],
  ];
@endphp

@section('content')
  <div class="acct-head">
    <h1>Help</h1>
    <p>We're here 24/7 on WhatsApp and phone</p>
  </div>

  <div class="bj-contact">
    <a class="a-card bj-contact__item" href="{{ $c['whatsapp'] }}" target="_blank" rel="noopener">
      <span class="chip sage"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.8 7L4 20l1.1-4.9A8 8 0 1 1 21 12z" /></svg></span>
      <span><b>WhatsApp</b><small>{{ $c['phone'] }}</small></span>
    </a>
    <a class="a-card bj-contact__item" href="tel:{{ $c['tel'] }}">
      <span class="chip cloud"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2" /></svg></span>
      <span><b>Call us</b><small>{{ $c['phone'] }}</small></span>
    </a>
    <a class="a-card bj-contact__item" href="mailto:{{ $c['email'] }}">
      <span class="chip sand"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18v12H3zM3 7l9 6 9-6" /></svg></span>
      <span><b>Email</b><small>{{ $c['email'] }}</small></span>
    </a>
  </div>

  <div class="a-grid two">
    <section class="a-card" id="message">
      <div class="a-sec-h"><h2>Send us a message</h2></div>
      <form method="POST" action="{{ route('account.help.send') }}">
        @csrf
        <x-az.field name="topic" label="What is it about?" type="select" :options="$topics" :value="$selected ? 'loan' : null" required />
        @if ($loans->isNotEmpty())
          <x-az.field name="loan_id" label="Which loan?" type="select" :options="$loanOptions" :value="$selected ?: null" placeholder="Not about a loan" />
        @endif
        <x-az.field name="message" label="Your message" type="textarea" required rows="5" maxlength="1000" placeholder="Tell us what you need and the best time to call you" />
        <button class="btn btn-primary btn-block" type="submit" style="margin-top:14px">Send message</button>
        <p class="muted" style="font-size:12.5px;margin-top:10px">Our team replies by phone or email, usually the same working day.</p>
      </form>
    </section>

    <section class="a-card">
      <div class="a-sec-h"><h2>Common questions</h2></div>
      <div class="bj-faq">
        @foreach ($faq as [$q, $a])
          <details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
        @endforeach
      </div>
      <p style="margin-top:14px"><a href="{{ route('account.notifications') }}">Notifications and emails we send</a> · <a href="{{ route('account.soa') }}">Statement of account</a></p>
    </section>
  </div>
@endsection
