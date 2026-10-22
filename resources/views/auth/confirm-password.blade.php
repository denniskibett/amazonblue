<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => 'Confirm your password — Aimzone Ventures', 'description' => 'Your Aimzone Ventures account', 'noindex' => true])
  </head>
  <body>
    {{-- Branded like the other sign-in pages (22 Oct 2026); the old starter layout crashed in production. --}}
    <a class="skip" href="#main">Skip to content</a>
    <div class="auth">
      <aside class="auth-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
          ><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures"
        /></a>
        <div class="art-copy art-copy-lg">
          <h2>Just checking it's you.</h2>
          <p>This part of your account needs your password again.</p>
        </div>
      </aside>
      <main class="auth-main" id="main">
        <div class="auth-box">
          <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
            ><img class="mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures"
          /></a>
          <div class="auth2-head">
            <h1>Confirm your password</h1>
            <p class="muted">Enter your password to continue.</p>
          </div>
          @if ($errors->any())
            <div class="kyc-note error" role="alert"><div><b>{{ $errors->first() }}</b></div></div>
          @endif
          <form method="POST" action="{{ route('password.confirm') }}" data-validate data-server novalidate class="auth2-form">
            @csrf
            <div class="field">
              <label for="password">Password<span class="req">*</span></label
              ><input class="input" id="password" name="password" type="password" autocomplete="current-password" required
              /><span class="error">Enter your password.</span>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Confirm</button>
          </form>
        </div>
      </main>
    </div>
    @include('home.partials.toast')
  </body>
</html>
