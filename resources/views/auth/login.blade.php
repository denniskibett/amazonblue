<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => 'Sign in — Aimzone Ventures', 'description' => 'Your Aimzone Ventures account'])
  </head>
  <body>
    <a class="skip" href="#main">Skip to content</a>
    <div class="auth center">
      <aside class="auth-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
          ><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures"
        /></a>
        <div class="art-copy art-copy-lg">
          <h2>Welcome back.</h2>
          <p>Check your facility, repay in a tap and talk to us any time.</p>
        </div>
      </aside>
      <main class="auth-main" id="main">
        <div class="auth-box">
          <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
            ><img class="mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures"
          /></a>
          <div class="auth2-head">
            <h1>Sign In</h1>
            <p class="muted">Enter your email and password to sign in!</p>
          </div>
          @if (session('status'))
            <div class="kyc-note ok"><div><b>{{ session('status') }}</b></div></div>
          @endif
          @if ($errors->any())
            <div class="kyc-note warn" role="alert"><div><b>{{ $errors->first() }}</b></div></div>
          @endif
          <form method="POST" action="{{ route('login') }}" data-validate data-server novalidate class="auth2-form">
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
            <div class="field">
              <label for="password">Password<span class="req">*</span></label>
              <div class="pw">
                <input
                  class="input"
                  id="password"
                  name="password"
                  type="password"
                  autocomplete="current-password"
                  placeholder="Enter your password"
                  required
                /><button type="button" aria-label="Show password">
                  <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                  </svg>
                </button>
              </div>
              <span class="error">Enter your password.</span>
            </div>
            <div class="row-between">
              <label class="check"><input type="checkbox" name="remember" /> Keep me logged in</label
              ><a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Sign In</button>
          </form>
          <p class="switch">Don't have an account? <a href="{{ route('register') }}">Sign Up</a></p>
          <p class="switch">Signed up but not verified? <a href="{{ route('kyc.personal') }}">Continue verification</a></p>
        </div>
      </main>
    </div>
    @include('home.partials.toast')
  </body>
</html>
