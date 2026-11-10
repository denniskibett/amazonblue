@php
  // Bike loan landing page (SEO, 10 Nov 2026): "motorbike loans Kenya", logbook and "buy a motorbike on loan" intent.
  // Hire purchase of a new bike is not offered online yet: the page says so and takes an enquiry.
  $faqs = [
    ['How do motorbike logbook loans work?', 'You bring the bike\'s logbook or other proof of ownership. An independent valuer inspects the bike, usually within 48 hours, and we lend up to 70% of its forced sale value. Our security is registered, the money is released, and you keep using the bike while you repay over up to 3 months.'],
    ['Can I buy a motorbike on loan or hire purchase?', 'Financing to buy a new motorbike is not open for online applications yet. Send us an enquiry with the bike, the dealer and how you plan to repay, and our team will tell you what we can do.'],
    ['I own several bikes. Can I borrow against my fleet?', 'Yes. Fleet expansion is one of the things asset finance is for. Each bike is valued and the limit depends on their combined value; talk to us first so we can plan the valuations.'],
    ['Where in Kenya do you lend?', 'We are based on Lenana Road, Nairobi, and borrowers apply online from anywhere in Kenya. Money is sent to M-Pesa or a bank account.'],
    ['Will a CRB listing stop me from borrowing?', 'It can make lending harder. Our CRB Solutions team can review your report and help you settle or restructure the debt behind a listing so you can borrow again.'],
    ['How do I repay a motorbike loan?', 'Logbook loans are repaid monthly or at the end of the term. Payments go through M-Pesa or the bank, and your account shows every payment and what is left.'],
  ];
  $url = route('site.page', 'motorbike-loans-kenya');
@endphp
@extends('home.layouts.app', [
  'title' => 'Motorbike Loans in Kenya — Logbook Loans for Bikes | Aimzone Ventures',
  'description' => 'Motorbike loans in Kenya from a Nairobi lender: borrow up to 70% of your bike\'s forced sale value for up to 3 months and keep riding. Ask us about buying a bike on loan.',
  'active' => 'bikes',
])

@push('jsonld')
  {!! \App\Support\Seo::script(\App\Support\Seo::logbookLoan('Motorbike logbook loan', $url, 'Asset finance against a motorbike you own in Kenya: up to 70% of its forced sale value, for up to 3 months, repaid monthly or at the end of the term.')) !!}
  {!! \App\Support\Seo::script(\App\Support\Seo::breadcrumbs(['Home' => route('home'), 'Boda boda loans' => route('site.page', 'boda-boda-loans'), 'Motorbike loans in Kenya' => $url])) !!}
@endpush

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <div class="stack stack-lg">
        <span class="tag sand">@include('home.partials.bike-icon')Motorbike finance</span>
        <h1>Motorbike loans in Kenya</h1>
        <p class="lead">
          Turn the motorbike you own into working capital without parking it. We value the bike quickly, lend against
          its logbook, and you keep riding while you repay.
        </p>
        <div class="facts">
          <div class="fact"><b>Up to 70%</b><span>Of forced sale value</span></div>
          <div class="fact"><b>48 hours</b><span>Usual valuation time</span></div>
          <div class="fact"><b>Monthly</b><span>Or at the end of the term</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}" data-sa-click="bike_apply_motorbike">Apply now</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}?topic=bike#message" data-sa-click="bike_enquiry_motorbike">Talk to us</a>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="wrap">
      <div class="section-head reveal"><h2>Logbook loans for motorbikes</h2></div>
      <div class="grid g4" data-carousel="step" aria-label="How a logbook loan works">
        <div class="step">
          <span class="n">01</span>
          <h3>Tell us about the bike</h3>
          <p class="muted text-sm">Make, model and year, with the logbook or other proof of ownership.</p>
        </div>
        <div class="step">
          <span class="n">02</span>
          <h3>Independent valuation</h3>
          <p class="muted text-sm">A valuer inspects the bike, usually within 48 hours.</p>
        </div>
        <div class="step">
          <span class="n">03</span>
          <h3>See your limit</h3>
          <p class="muted text-sm">Borrow up to 70% of the forced sale value, for up to 3 months.</p>
        </div>
        <div class="step">
          <span class="n">04</span>
          <h3>Sign and receive</h3>
          <p class="muted text-sm">Our security is registered and the money is released. You keep riding.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="section section-tint">
    <div class="wrap grid g2 gap-lg">
      <div class="card card-soft">
        <h2 class="h-card">Who it suits</h2>
        <ul class="ticks">
          <li>@include('home.partials.tick-icon')Boda boda riders who own their bike</li>
          <li>@include('home.partials.tick-icon')Owners who rent bikes out to riders</li>
          <li>@include('home.partials.tick-icon')Delivery and courier businesses growing a fleet</li>
          <li>@include('home.partials.tick-icon')Anyone who needs cash and owns a motorbike outright</li>
        </ul>
      </div>
      <div class="card card-soft">
        <h2 class="h-card">Want to buy a motorbike on loan?</h2>
        <p class="muted">
          Financing to buy a new motorbike or e-bike is not open for online applications yet. Send us an enquiry with
          the bike you want and how you plan to repay, and our team will tell you what we can do.
        </p>
        <p><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}?topic=bike#message" data-sa-click="bike_enquiry_motorbike_buy">Send an enquiry</a></p>
      </div>
    </div>
  </section>

  @include('home.partials.faq', ['faqs' => $faqs, 'heading' => 'Motorbike loan questions'])
  @include('home.partials.bike-links', ['current' => 'motorbike-loans-kenya'])
@endsection
