@extends('home.layouts.app', [
  'title' => 'Aimzone app (test) — Aimzone Ventures',
  'description' => 'Test build of the Aimzone mobile app for Android.',
  'noindex' => true,
])

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <span class="chip sand">Test build</span>
      <h1>The Aimzone app</h1>
      <p class="muted">Borrow, repay and see your statement from your phone. Android only for now; iPhone later.</p>
      {{-- test mode (owner, 15 Nov 2026): say plainly what testers are installing --}}
      <p class="muted text-sm" style="margin-top:12px;padding:10px 14px;border:1px dashed currentColor;border-radius:10px;max-width:60ch">
        Test version for invited testers. It is not on the Play Store yet, the app shows a TEST ribbon, and screens may change between builds.
        Your account, loans and payments in it are real: the same as on this website.</p>
    </div>
  </section>

  <section class="section pt-md">
    <div class="wrap grid g2 gap-lg">
      <div class="card">
        <h2>Version {{ $version }}</h2>
        <p class="muted">{{ $notes }}</p>
        @if ($apk)
          <p class="muted text-sm">{{ number_format($apk['size'] / 1048576, 1) }} MB · updated {{ $apk['updated']->format('j M Y') }}</p>
          <p><a class="btn btn-primary btn-block" href="{{ route('app.download.file') }}">Download for Android</a></p>
          <p class="muted text-sm">Check the file: SHA-256 <code style="word-break: break-all">{{ $apk['sha256'] }}</code></p>
        @else
          <p class="muted">The test build is not up yet. Please check back soon.</p>
        @endif
        <p class="muted text-sm">Sign in with the phone number or email and password you use on this website.</p>
      </div>

      <div class="card card-soft">
        <h2>How to install</h2>
        <ol class="ticks">
          <li>Open this page on your Android phone and tap <strong>Download for Android</strong>.</li>
          <li>Open the downloaded file. If asked, allow your browser to <strong>install unknown apps</strong> (Settings › Apps › Special access).</li>
          <li>Tap <strong>Install</strong>, then open <strong>Aimzone</strong>.</li>
          <li>For a new version, download it again and install over the old one. You stay signed in.</li>
        </ol>
      </div>
    </div>
  </section>

  <section class="section section-tint">
    <div class="wrap">
      <div class="section-head"><h2>What the app uses</h2></div>
      <div class="grid g2 gap-lg">
        <div class="card">
          <h3>Only with your yes</h3>
          <p class="muted text-sm">Your phone model and app version, your approximate area (town or estate) when you apply or pay, and reminders. Each one can be switched off in the app under Account.</p>
        </div>
        <div class="card">
          <h3>Never</h3>
          <p class="muted text-sm">Contacts, photos, call logs, SMS or your exact location. The app does not ask for them.</p>
        </div>
      </div>
    </div>
  </section>
@endsection
