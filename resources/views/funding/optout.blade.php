<!doctype html>
<html lang="en">
  <head>@include('home.partials.head', ['title' => 'Email preferences — Aimzone Ventures', 'description' => 'Stop emails from Aimzone Ventures', 'noindex' => true])</head>
  <body>
    <main class="kyc2-main" id="main" style="min-height:100vh;display:grid;place-items:center;padding:24px">
      <div class="kyc2-col" style="max-width:480px">
        <img src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures" style="height:36px;margin-bottom:24px" />
        @if ($done)
          <div class="kyc-head"><h1>You won't hear from us again</h1><p class="muted">{{ $email?->to }} is off our list. We keep the address only so we never email it again (Data Protection Act, 2019).</p></div>
        @elseif (! $email)
          <div class="kyc-head"><h1>Link not recognised</h1><p class="muted">Reply to the email with "unsubscribe" and we will remove you.</p></div>
        @else
          <div class="kyc-head"><h1>Stop these emails?</h1><p class="muted">We will not email {{ $email->to }} about business finance again.</p></div>
          <form method="POST" action="{{ route('funding.optout.store', $track) }}">@csrf<button class="btn btn-primary btn-block" type="submit">Yes, stop emailing me</button></form>
        @endif
      </div>
    </main>
  </body>
</html>
