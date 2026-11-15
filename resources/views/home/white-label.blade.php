{{-- White label & API (owner, 15 Nov 2026): what other lenders can use of our system, and the Android test app (was /app). --}}
@extends('home.layouts.app', [
  'title' => 'White label & API — Aimzone Ventures',
  'description' => 'Run lending on our system under your own name, connect your systems through our API, and try the Aimzone Android app.',
  'noindex' => true,
])

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <span class="chip sand" style="white-space:nowrap">For lenders and partners</span>
      <h1>White label &amp; API</h1>
      <p class="muted" style="max-width:62ch">The loan system we run Aimzone on, for your own lending: your name in front, your own data behind it. Or keep your system and connect it to ours.</p>
    </div>
  </section>

  <section class="section pt-md">
    <div class="wrap grid g2 gap-lg">
      <div class="card">
        <span class="chip sage">White label</span>
        <h2 style="margin-top:10px">Your lending, on our system</h2>
        <ul class="ticks">
          <li>Your brand on the borrower website, the staff desk and the app.</li>
          <li>Your own database: your borrowers and money stay yours.</li>
          <li>Loans, cycles, repayments, recovery and partner money, as we use them every day.</li>
          <li>M-Pesa and Sharet Pay for paying out and collecting.</li>
        </ul>
        <p class="muted text-sm">We are setting up the first lenders now. Tell us about yours and we will show you a test set-up.</p>
        <p><a class="btn btn-primary" href="{{ route('site.page', 'contact') }}">Talk to us</a></p>
      </div>
      <div class="card">
        <span class="chip apricot">API</span>
        <h2 style="margin-top:10px">Connect your systems</h2>
        <ul class="ticks">
          <li>Your own key, issued by us. Each key opens only the part it is for, and can be switched off at any time.</li>
          <li>A system API for back-office tools (accounting, reporting, your own desk).</li>
          <li>A mobile API for apps, with one sign-in per phone.</li>
          <li>Every call needs the key; borrowers still sign in with their own details.</li>
        </ul>
        <p class="muted text-sm">Ask us for a key and the documentation; we give test access first.</p>
        <p><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}">Ask for API access</a></p>
      </div>
    </div>
  </section>

  {{-- the Android test app (owner, 11 and 15 Nov 2026) --}}
  <section class="section section-tint" id="app">
    <div class="wrap">
      <div class="section-head">
        <span class="chip sand">Test app</span>
        <h2>The Aimzone app for Android</h2>
        <p class="muted" style="max-width:62ch">Borrow, repay and see your statement from your phone. A test version for invited testers: not on the Play Store yet, and screens may change between builds. Your account, loans and payments in it are real, the same as on this website.</p>
      </div>
      <div class="grid g2 gap-lg">
        <div class="card">
          <h3>Version {{ $version }}</h3>
          <p class="muted">{{ $notes }}</p>
          @if ($apk)
            <p class="muted text-sm">{{ number_format($apk['size'] / 1048576, 1) }} MB · updated {{ $apk['updated']->format('j M Y') }} · for 64-bit Android phones</p>
            <p><a class="btn btn-primary btn-block" href="{{ route('app.download.file') }}">Download for Android</a></p>
            <p class="muted text-sm">Check the file: SHA-256 <code style="word-break: break-all">{{ $apk['sha256'] }}</code></p>
          @else
            <p class="muted">The test build is not up yet. Please check back soon.</p>
          @endif
          <p class="muted text-sm">Sign in with the phone number or email and password you use on this website.</p>
        </div>
        <div class="card card-soft">
          <h3>How to install</h3>
          <ol class="ticks">
            <li>Open this page on your Android phone and tap <strong>Download for Android</strong>.</li>
            <li>Open the downloaded file. If asked, allow your browser to <strong>install unknown apps</strong>.</li>
            <li>Tap <strong>Install</strong>, then open <strong>Aimzone</strong>.</li>
            <li>For a new version, install it over the old one. You stay signed in.</li>
          </ol>
          <p class="muted text-sm">If your phone says it cannot install it, it may be an older 32-bit phone: <a href="{{ route('site.page', 'contact') }}">tell us</a> and we will send a build for it.</p>
        </div>
      </div>
      <div class="grid g2 gap-lg" style="margin-top:24px">
        <div class="card">
          <h3>Only with your yes</h3>
          <p class="muted text-sm">Your phone model and app version, your approximate area (town or estate) when you apply or pay, and reminders. Each can be switched off in the app under Account.</p>
        </div>
        <div class="card">
          <h3>Never</h3>
          <p class="muted text-sm">Contacts, photos, call logs, SMS or your exact location. The app does not ask for them.</p>
        </div>
      </div>
    </div>
  </section>
@endsection
