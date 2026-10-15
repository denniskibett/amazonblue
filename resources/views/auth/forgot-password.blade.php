<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => 'Forgot password — Aimzone Ventures', 'description' => 'Your Aimzone Ventures account', 'noindex' => true])
  </head>
  <body>
    <a class="skip" href="#main">Skip to content</a>
    <div class="auth">
      <aside class="auth-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
          ><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures"
        /></a>
        <div class="art-copy art-copy-lg">
          <h2>It happens.</h2>
          <p>We'll send a one-time code so you can set a new password.</p>
        </div>
      </aside>
      <main class="auth-main" id="main">
        <div class="auth-box">
          <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
            ><img class="mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures"
          /></a>
          <div class="auth2-head">
            <h1>Forgot Password?</h1>
            <p class="muted">Enter the email linked to your account and we'll email you a reset link.</p>
          </div>
          @if (session('status'))
            <div class="kyc-note ok"><div><b>{{ session('status') }}</b></div></div>
          @endif
          @if ($errors->any())
            <div class="kyc-note error" role="alert"><div><b>{{ $errors->first() }}</b></div></div>
          @endif
          <form method="POST" action="{{ route('password.email') }}" data-validate data-server novalidate class="auth2-form">
            @csrf
            <div class="field">
              <label for="email">Email<span class="req">*</span></label
              ><input
                class="input"
                id="email"
                name="email"
                value="{{ old('email') }}"
                type="email"
                autocomplete="email"
                placeholder="info@gmail.com"
                required
              /><span class="error">Enter a valid email address.</span>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Send Reset Link</button>
          </form>
          <p class="switch">Remembered it? <a href="{{ route('login') }}">Sign In</a></p>
        </div>
      </main>
    </div>
    @include('home.partials.toast')
  </body>
</html>
