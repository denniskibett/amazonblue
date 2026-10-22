<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => 'Confirm your email — Aimzone Ventures', 'description' => 'Your Aimzone Ventures account', 'noindex' => true])
  </head>
  <body>
    {{-- Branded like the other sign-in pages (22 Oct 2026). The old starter layout crashed in production, so this page
         never loaded and borrowers could not resend the link. Email is confirmed with a link, not a code. --}}
    <a class="skip" href="#main">Skip to content</a>
    <div class="auth">
      <aside class="auth-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
          ><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures"
        /></a>
        <div class="art-copy art-copy-lg">
          <h2>One last check.</h2>
          <p>Confirm your email so we can reach you about your application.</p>
        </div>
      </aside>
      <main class="auth-main" id="main">
        <div class="auth-box">
          <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
            ><img class="mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures"
          /></a>
          <div class="auth2-head">
            <h1>Confirm your email</h1>
            <p class="muted">We sent a link to <b>{{ auth()->user()->email }}</b>. Open it on this device and you're done; your verification details are kept.</p>
          </div>
          @if (session('status') === 'verification-link-sent')
            <div class="kyc-note ok"><div><b>A new link is on its way to {{ auth()->user()->email }}.</b> It can take a minute. Check your spam or promotions folder too.</div></div>
          @endif
          @if ($errors->any())
            <div class="kyc-note error" role="alert"><div><b>{{ $errors->first() }}</b></div></div>
          @endif
          <form method="POST" action="{{ route('verification.send') }}" class="auth2-form">
            @csrf
            <button class="btn btn-primary btn-block" type="submit">Send me a new link</button>
          </form>
          <p class="muted" style="margin-top:16px">The link works for 60 minutes. Wrong email address? Sign out and contact us so we can correct it.</p>
          <p class="switch">
            @if (auth()->user()->isCustomer() && \Illuminate\Support\Facades\Route::has('kyc.review'))
              <a href="{{ route('kyc.review') }}">Back to my verification</a> ·
            @endif
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('ve-logout').submit()">Sign out</a>
          </p>
          <form id="ve-logout" method="POST" action="{{ route('logout') }}" hidden>@csrf</form>
        </div>
      </main>
    </div>
    @include('home.partials.toast')
  </body>
</html>
