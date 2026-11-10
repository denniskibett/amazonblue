@php
  // Bike loan requirements (SEO, 10 Nov 2026). Mirrors what the sign-up and verification steps actually ask for
  // (RegisteredUserController, HomeController KYC) and the documents listed on /asset-finance.
  $faqs = [
    ['How old do I need to be to apply?', 'At least 18. Your date of birth is checked against your ID when you verify your account.'],
    ['Do I need a KRA PIN?', 'Yes. Verification asks for your KRA PIN along with your ID or passport number.'],
    ['Do I need a guarantor?', 'Yes. Verification asks for a guarantor and a next of kin, each with a Kenyan mobile number that is not your own.'],
    ['Can I apply if I am self-employed as a boda boda rider?', 'Yes. Choose self-employed, tell us what you do (boda boda counts), where you work, how long you have done it and your monthly income.'],
    ['Which phone number receives the money?', 'The M-Pesa number you give when you verify. The loan is paid to that number once it is approved.'],
    ['What extra do I need for a logbook loan?', 'The bike\'s logbook or other proof of ownership, and comprehensive insurance. An independent valuer then inspects the bike, usually within 48 hours.'],
  ];
  $url = route('site.page', 'bike-loan-requirements');
@endphp
@extends('home.layouts.app', [
  'title' => 'Bike Loan Requirements & How to Apply in Kenya | Aimzone Ventures',
  'description' => 'What you need for a boda boda, motorbike or e-bike loan in Kenya: ID or passport, KRA PIN, M-Pesa number, guarantor, and the logbook and insurance for a logbook loan.',
  'active' => 'bikes',
])

@push('jsonld')
  {!! \App\Support\Seo::script(\App\Support\Seo::breadcrumbs(['Home' => route('home'), 'Boda boda loans' => route('site.page', 'boda-boda-loans'), 'Requirements and how to apply' => $url])) !!}
  {!! \App\Support\Seo::script([
    '@type' => 'HowTo',
    'name' => 'How to apply for a bike loan with Aimzone Ventures',
    'step' => [
      ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Create an account', 'text' => 'Register with your name, email and Kenyan mobile number, and confirm your email.'],
      ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Personal details', 'text' => 'Your name, ID or passport number, date of birth, KRA PIN, county and address.'],
      ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Income', 'text' => 'How you earn, your monthly income and the M-Pesa number that receives the loan.'],
      ['@type' => 'HowToStep', 'position' => 4, 'name' => 'Contacts', 'text' => 'A next of kin and a guarantor, each with their own Kenyan mobile number.'],
      ['@type' => 'HowToStep', 'position' => 5, 'name' => 'Documents', 'text' => 'Photos of your ID or passport, a passport photo, three quick selfies and your signature.'],
      ['@type' => 'HowToStep', 'position' => 6, 'name' => 'Review, submit and apply', 'text' => 'Check your details and submit. Once verified, choose an amount, read your offer and sign.'],
    ],
  ]) !!}
@endpush

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <h1>Bike loan requirements and how to apply</h1>
      <p class="lead">
        Everything you need for a boda boda, motorbike or e-bike loan, in the order the application asks for it. Have it
        ready and verification takes minutes.
      </p>
      <div class="actions">
        <a class="btn btn-primary" href="{{ route('register') }}" data-sa-click="bike_apply_requirements">Start your application</a
        ><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}?topic=bike#message" data-sa-click="bike_enquiry_requirements">Ask a question</a>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="wrap grid g2 gap-lg">
      <div class="card card-soft">
        <h2 class="h-card">For every loan</h2>
        <ul class="ticks docs">
          <li>@include('home.partials.doc-icon')You are 18 or older</li>
          <li>@include('home.partials.doc-icon')National ID (front and back) or passport</li>
          <li>@include('home.partials.doc-icon')KRA PIN</li>
          <li>@include('home.partials.doc-icon')A Kenyan mobile number and an email address you can confirm</li>
          <li>@include('home.partials.doc-icon')Your M-Pesa number for the payout</li>
          <li>@include('home.partials.doc-icon')What you do and your monthly income</li>
          <li>@include('home.partials.doc-icon')A next of kin and a guarantor, with their phone numbers</li>
          <li>@include('home.partials.doc-icon')A passport photo, three selfies and your signature (done on your phone)</li>
        </ul>
      </div>
      <div class="card card-soft">
        <h2 class="h-card">Also for a logbook loan</h2>
        <ul class="ticks docs">
          <li>@include('home.partials.doc-icon')The motorbike's or e-motorcycle's logbook, or other proof of ownership</li>
          <li>@include('home.partials.doc-icon')Comprehensive insurance on the bike</li>
          <li>@include('home.partials.doc-icon')The bike available for an independent valuation, usually within 48 hours</li>
        </ul>
        <p class="muted text-sm">
          You can borrow up to 70% of the bike's forced sale value for up to 3 months, and keep riding while you repay.
          <a href="{{ route('site.page', 'asset-finance') }}">More on asset finance</a>
        </p>
      </div>
    </div>
  </section>

  <section class="section section-tint">
    <div class="wrap">
      <div class="section-head reveal"><h2>How to apply, step by step</h2></div>
      <div class="grid g3" aria-label="How to apply">
        <div class="step">
          <span class="n">01</span>
          <h3>Create an account</h3>
          <p class="muted text-sm">Your name, email and Kenyan mobile number, then confirm your email.</p>
        </div>
        <div class="step">
          <span class="n">02</span>
          <h3>Personal details</h3>
          <p class="muted text-sm">ID or passport number, date of birth, KRA PIN, county and address.</p>
        </div>
        <div class="step">
          <span class="n">03</span>
          <h3>Income</h3>
          <p class="muted text-sm">How you earn, your monthly income and the M-Pesa number for the payout.</p>
        </div>
        <div class="step">
          <span class="n">04</span>
          <h3>Contacts</h3>
          <p class="muted text-sm">A next of kin and a guarantor, each with their own number.</p>
        </div>
        <div class="step">
          <span class="n">05</span>
          <h3>Documents</h3>
          <p class="muted text-sm">Photos of your ID, a passport photo, three quick selfies and your signature.</p>
        </div>
        <div class="step">
          <span class="n">06</span>
          <h3>Submit and apply</h3>
          <p class="muted text-sm">Once you are verified, choose an amount, read your offer and sign.</p>
        </div>
      </div>
    </div>
  </section>

  @include('home.partials.faq', ['faqs' => $faqs, 'heading' => 'Questions about applying'])
  @include('home.partials.bike-links', ['current' => 'bike-loan-requirements'])
@endsection
