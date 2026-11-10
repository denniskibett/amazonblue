@php
  // Bike loan landing page (SEO, 10 Nov 2026): electric motorbikes and e-bikes. Terms as published on /asset-finance.
  $faqs = [
    ['Do you give loans for electric motorbikes in Kenya?', 'Yes. A registered electric motorbike with a logbook in your name is a vehicle like any other: you can borrow against it through our asset finance, up to 70% of its forced sale value, for up to 3 months, and keep riding while you repay.'],
    ['My e-bike has no logbook. Can I still borrow?', 'Not against the bike itself, because we need proof of ownership to register our security. You can still apply online for emergency cash to M-Pesa once you have verified your account.'],
    ['Can you finance a new electric motorbike or e-bike?', 'Financing to buy a new electric motorbike or e-bike is not open for online applications yet. Send us an enquiry with the model you want and how you plan to repay, and our team will tell you what we can do.'],
    ['Is insurance required for an e-motorcycle logbook loan?', 'Yes. Like any vehicle we lend against, the bike needs comprehensive insurance while the loan is running.'],
    ['How long does the valuation take?', 'An independent valuer usually inspects the bike within 48 hours. You then see your limit and sign before the money is released.'],
    ['Can I use the loan for batteries, charging or repairs?', 'Yes. What you spend the money on is up to you; tell us what it is for when you apply so we can match the right product.'],
  ];
  $url = route('site.page', 'electric-bike-loans');
@endphp
@extends('home.layouts.app', [
  'title' => 'Electric Bike & E-Motorcycle Loans in Kenya | Aimzone Ventures',
  'description' => 'Loans for electric motorbike and e-bike riders in Nairobi and across Kenya: borrow against your e-motorcycle logbook and keep riding, or get emergency cash to M-Pesa.',
  'active' => 'bikes',
])

@push('jsonld')
  {!! \App\Support\Seo::script(\App\Support\Seo::logbookLoan('Electric motorbike logbook loan', $url, 'Borrow against a registered electric motorbike you own, up to 70% of its forced sale value, for up to 3 months, and keep riding while you repay.')) !!}
  {!! \App\Support\Seo::script(\App\Support\Seo::breadcrumbs(['Home' => route('home'), 'Boda boda loans' => route('site.page', 'boda-boda-loans'), 'Electric bike loans' => $url])) !!}
@endpush

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <div class="stack stack-lg">
        <span class="tag sage">@include('home.partials.bike-icon')Electric bikes</span>
        <h1>Electric bike and e-motorcycle loans in Kenya</h1>
        <p class="lead">
          Riding electric in Nairobi or anywhere in Kenya? Borrow against your registered e-motorcycle and keep riding,
          or apply online for emergency cash to M-Pesa for batteries, repairs or a slow week.
        </p>
        <div class="facts">
          <div class="fact"><b>Up to 70%</b><span>Of the bike's forced sale value</span></div>
          <div class="fact"><b>48 hours</b><span>Usual valuation time</span></div>
          <div class="fact"><b>3 months</b><span>Longest logbook loan</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}" data-sa-click="bike_apply_ebike">Apply now</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}?topic=bike#message" data-sa-click="bike_enquiry_ebike">Ask about e-bike finance</a>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="wrap grid g2 gap-lg">
      <div class="card card-soft">
        <h2 class="h-card">E-motorcycle with a logbook</h2>
        <p class="muted">
          Electric motorbikes registered in your name can secure a loan just like a petrol bike. We value the bike, lend
          up to 70% of its forced sale value and register our interest as security. You repay monthly or at the end of
          the term, up to 3 months, and the bike keeps working for you.
        </p>
      </div>
      <div class="card card-soft">
        <h2 class="h-card">E-bike without a logbook</h2>
        <p class="muted">
          No logbook means the bike cannot be security, but you can still borrow. Verify your account once, then apply
          for emergency cash to your M-Pesa. Your offer shows the total to repay and the due date before you sign.
        </p>
      </div>
    </div>
  </section>

  <section class="section section-tint">
    <div class="wrap grid g2 gap-lg">
      <div>
        <h2>What you'll need</h2>
        <ul class="ticks docs">
          <li>@include('home.partials.doc-icon')National ID or passport</li>
          <li>@include('home.partials.doc-icon')Passport-size photo and your signature</li>
          <li>@include('home.partials.doc-icon')For a logbook loan: the e-motorcycle's logbook or other proof of ownership</li>
          <li>@include('home.partials.doc-icon')For a logbook loan: comprehensive insurance</li>
        </ul>
      </div>
      <div>
        <h2>Buying a new e-bike?</h2>
        <p class="muted">
          Financing to buy a new electric motorbike or e-bike is not open for online applications yet. Tell us the model
          and how you plan to repay, and our team will come back to you.
        </p>
        <p><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}?topic=bike#message" data-sa-click="bike_enquiry_ebike_buy">Send an enquiry</a></p>
      </div>
    </div>
  </section>

  @include('home.partials.faq', ['faqs' => $faqs, 'heading' => 'Electric bike loan questions'])
  @include('home.partials.bike-links', ['current' => 'electric-bike-loans'])
@endsection
