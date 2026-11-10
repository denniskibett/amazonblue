@php
  // Bike loan landing page (SEO, 10 Nov 2026). Terms are the ones already published on /asset-finance and in the app;
  // no rate is quoted here that is not on the site.
  $faqs = [
    ['Can a boda boda rider get a loan from Aimzone Ventures?', 'Yes. Riders apply online like any other borrower: create an account, tell us what you do and how you earn, add your contacts and documents, and send the application. Once approved, the money goes to your M-Pesa.'],
    ['Can I get a loan using my motorbike logbook?', 'Yes. A motorbike is a vehicle, so you can borrow against it through our asset finance: we value the bike and lend up to 70% of its forced sale value, for up to 3 months. You keep riding it while you repay; we register our interest as security.'],
    ['Do I need to stop riding my bike while I repay?', 'No. With a logbook loan the bike stays with you and keeps earning. We only hold a registered interest in it until the loan is repaid.'],
    ['What documents do I need for a boda boda loan?', 'Your national ID or passport, a passport-size photo and your signature. For a loan against the bike you also need its logbook (or other proof that you own it) and comprehensive insurance.'],
    ['Can you finance a new boda boda for me?', 'Financing to buy a new motorbike is not open for online applications yet. Send us an enquiry with the bike you want and how you plan to repay, and our team will tell you what we can do.'],
    ['How do I repay?', 'Through M-Pesa. Your offer shows the amount, the full total to repay and the due date before you sign, and your account shows every payment you make.'],
  ];
  $url = route('site.page', 'boda-boda-loans');
@endphp
@extends('home.layouts.app', [
  'title' => 'Boda Boda Loans in Kenya — Logbook & Emergency Loans | Aimzone Ventures',
  'description' => 'Loans for boda boda riders and owners in Kenya: borrow against your motorbike logbook and keep riding, or apply online for emergency cash to M-Pesa.',
  'active' => 'bikes',
])

@push('jsonld')
  {!! \App\Support\Seo::script(\App\Support\Seo::logbookLoan('Boda boda logbook loan', $url, 'Borrow against a motorbike you own, up to 70% of its forced sale value, for up to 3 months, and keep riding while you repay.')) !!}
  {!! \App\Support\Seo::script(\App\Support\Seo::breadcrumbs(['Home' => route('home'), 'Boda boda loans' => $url])) !!}
@endpush

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <div class="stack stack-lg">
        <span class="tag sand">@include('home.partials.bike-icon')Bike loans</span>
        <h1>Boda boda loans in Kenya</h1>
        <p class="lead">
          Your bike is your business. Borrow against its logbook and keep riding while you repay, or apply online for
          emergency cash straight to your M-Pesa.
        </p>
        <div class="facts">
          <div class="fact"><b>Up to 70%</b><span>Of the bike's forced sale value</span></div>
          <div class="fact"><b>3 months</b><span>Longest logbook loan</span></div>
          <div class="fact"><b>M-Pesa</b><span>Paid and repaid</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}" data-sa-click="bike_apply_boda">Apply now</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}?topic=bike#message" data-sa-click="bike_enquiry_boda">Talk to us</a>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="wrap">
      <div class="section-head reveal"><h2>Two ways we can help a rider</h2></div>
      <div class="grid g2 gap-lg">
        <div class="card card-soft">
          <h3 class="h-card">Logbook loan against your motorbike</h3>
          <p class="muted">
            Already own the bike? Use it as security. We value it, usually within 48 hours, and lend up to 70% of its
            forced sale value for up to 3 months. Repay monthly or at the end of the term. The bike stays with you.
          </p>
          <p><a href="{{ route('site.page', 'asset-finance') }}">How asset finance works</a></p>
        </div>
        <div class="card card-soft">
          <h3 class="h-card">Emergency cash to M-Pesa</h3>
          <p class="muted">
            A breakdown, a spare part, a licence or a slow week. Verify once in the app, then apply for emergency cash
            without putting up the bike as security. Your offer shows the total to repay and the due date before you sign. Repay on time and
            you can borrow again.
          </p>
          <p><a href="{{ route('site.page', 'emergency-solutions') }}">About emergency finance</a></p>
        </div>
      </div>
    </div>
  </section>

  <section class="section section-tint">
    <div class="wrap">
      <div class="section-head reveal"><h2>How to apply</h2></div>
      <div class="grid g4" data-carousel="step" aria-label="How to apply">
        <div class="step">
          <span class="n">01</span>
          <h3>Create an account</h3>
          <p class="muted text-sm">With your Kenyan mobile number and email.</p>
        </div>
        <div class="step">
          <span class="n">02</span>
          <h3>Verify yourself</h3>
          <p class="muted text-sm">Your details, how you earn (boda boda counts), contacts, ID, photo and signature.</p>
        </div>
        <div class="step">
          <span class="n">03</span>
          <h3>See your offer</h3>
          <p class="muted text-sm">Amount, total to repay and due date, in plain language, before you sign.</p>
        </div>
        <div class="step">
          <span class="n">04</span>
          <h3>Get paid to M-Pesa</h3>
          <p class="muted text-sm">Once approved, the money is sent to your verified M-Pesa number.</p>
        </div>
      </div>
      <p class="section-foot"><a href="{{ route('site.page', 'bike-loan-requirements') }}">Full list of requirements</a></p>
    </div>
  </section>

  @include('home.partials.faq', ['faqs' => $faqs, 'heading' => 'Boda boda loan questions'])
  @include('home.partials.bike-links', ['current' => 'boda-boda-loans'])
@endsection
