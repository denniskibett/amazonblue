<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => 'Reset password — Aimzone Ventures', 'description' => 'Your Aimzone Ventures account'])
  </head>
  <body>
    <a class="skip" href="#main">Skip to content</a>
    <div class="auth">
      <aside class="auth-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
          ><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures"
        /></a>
        <div class="art-copy art-copy-lg">
          <h2>Almost there.</h2>
          <p>Choose a new password you haven't used before.</p>
        </div>
      </aside>
      <main class="auth-main" id="main">
        <div class="auth-box">
          <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
            ><img class="mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures"
          /></a>
          <div class="auth2-head">
            <h1>Reset Password</h1>
            <p class="muted">Choose a new password you haven't used before.</p>
          </div>
          @if (session('status'))
            <div class="kyc-note ok"><div><b>{{ session('status') }}</b></div></div>
          @endif
          @if ($errors->any())
            <div class="kyc-note warn" role="alert"><div><b>{{ $errors->first() }}</b></div></div>
          @endif
          <form method="POST" action="{{ route('password.store') }}" data-validate data-server novalidate class="auth2-form">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}" />
            <input type="hidden" name="email" value="{{ old('email', $request->email) }}" />
            <div class="field">
              <label for="new-password">New Password<span class="req">*</span></label>
              <div class="pw">
                <input
                  class="input"
                  id="new-password"
                  name="password"
                  type="password"
                  autocomplete="new-password"
                  placeholder="Enter your new password"
                  required
                  data-strength
                /><button type="button" aria-label="Show password">
                  <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                  </svg>
                </button>
              </div>
              <span class="error">Enter your password.</span>
            </div>
            <div class="strength" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
            <ul class="rules">
              <li>
                <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>At least 8
                characters
              </li>
              <li>
                <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>One number
              </li>
              <li>
                <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>One uppercase
                letter
              </li>
              <li>
                <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>One symbol
              </li>
            </ul>
            <div class="field">
              <label for="confirm">Confirm Password<span class="req">*</span></label>
              <div class="pw">
                <input
                  class="input"
                  id="confirm"
                  name="password_confirmation"
                  type="password"
                  autocomplete="new-password"
                  placeholder="Confirm your password"
                  required
                  data-match="new-password"
                /><button type="button" aria-label="Show password">
                  <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                  </svg>
                </button>
              </div>
              <span class="error">Enter your password.</span>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Update Password</button>
          </form>
          <p class="switch">Back to <a href="{{ route('login') }}">Sign In</a></p>
        </div>
      </main>
    </div>
    @include('home.partials.toast')
  </body>
</html>
