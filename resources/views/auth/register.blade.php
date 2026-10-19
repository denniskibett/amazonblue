<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => 'Create your account — Aimzone Ventures', 'description' => 'Create your Aimzone Ventures account', 'noindex' => true])
  </head>
  <body>
    <a class="skip" href="#main">Skip to content</a>
    <div class="kyc2">
      <aside class="auth-art kyc2-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
          ><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures"
        /></a>
        <div class="art-copy">
          <h2>Capital when it matters.</h2>
          <p>One account for borrowing, trade finance and business credit.</p>
        </div>
      </aside>
      <main class="kyc2-main" id="main">
        <div class="kyc2-col">
          <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
            ><img class="kyc2-mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures"
          /></a>
          <form
            class="kyc-form reg3"
            method="POST"
            action="{{ route('register') }}"
            data-validate
            data-server
            novalidate
          >
            @csrf
            <input class="rt-in" type="radio" name="account_type" id="rt-ind" value="individual" checked /><input
              class="rt-in"
              type="radio"
              name="account_type"
              id="rt-biz"
              value="business"
              aria-disabled="true"
            />
            <nav class="segbar" aria-label="Your journey">
              <span class="segnav back off" aria-hidden="true"
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg
              ></span>
              <div class="bars">
                <div class="bar-ind">
                  <div class="segs">
                    <span class="seg2 now"><span>1</span><em>Register</em></span
                    ><span class="seg2"><span>2</span><em>Personal</em></span
                    ><span class="seg2"><span>3</span><em>Documents</em></span
                    ><span class="seg2"><span>4</span><em>Income</em></span
                    ><span class="seg2"><span>5</span><em>Contacts</em></span
                    ><span class="seg2"><span>6</span><em>Review</em></span>
                  </div>
                </div>
                <div class="bar-biz">
                  <div class="segs">
                    <span class="seg2 now"><span>1</span><em>Register</em></span
                    ><span class="seg2"><span>2</span><em>Business</em></span
                    ><span class="seg2"><span>3</span><em>Directors</em></span
                    ><span class="seg2"><span>4</span><em>Documents</em></span
                    ><span class="seg2"><span>5</span><em>Financials</em></span
                    ><span class="seg2"><span>6</span><em>Review</em></span>
                  </div>
                </div>
              </div>
              <span class="segnav off nextoff" aria-hidden="true"
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg
              ></span>
            </nav>
            <div class="kyc-head">
              <h1>Create your account</h1>
              <p class="muted">Individual accounts borrow in your own name. Have a business? Register yourself first, then add your business from your account.</p>
              @if ($errors->any())
                <div class="kyc-note error" role="alert">
                  <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 16h.01M12 3l10 18H2z" /></svg>
                  <div><b>Please check your details</b><span>{{ $errors->first() }}</span></div>
                </div>
              @endif
            </div>
            <div class="tcards" role="radiogroup" aria-label="Account type">
              <label for="rt-ind" class="tcard tc-ind"
                ><span class="chip"
                  ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c1-4 4-6 8-6s7 2 8 6" /></svg></span
                ><span class="tc-text"
                  ><b>Individual</b
                  ><span>Borrow in your own name: emergency, asset-backed and CRB solutions.</span></span
                ><span class="tc-dot" aria-hidden="true"></span></label
              ><label for="rt-biz" class="tcard tc-biz"
                ><span class="chip sand"
                  ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                      d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c1-4 4-6 7-6s6 2 7 6M16 3a4 4 0 0 1 0 8M18 15c2 1 3.5 3 4 6"
                    /></svg></span
                ><span class="tc-text"
                  ><b>Business<span class="tc-soon">After sign-up</span></b><span>Register yourself first, then add your business from your account.</span></span
                ><span class="tc-dot" aria-hidden="true"></span
              ></label>
            </div>
            <div class="kyc-card">
              <div class="form-row">
                <div class="field @error('name') invalid @enderror">
                  <label for="rg-name">Full name<span class="req">*</span></label
                  ><input
                    class="input"
                    id="rg-name"
                    name="name"
                    value="{{ old('name') }}"
                    type="text"
                    autocomplete="name"
                    placeholder="Enter your full name"
                    required
                  /><span class="error">{{ $errors->first('name') ?: 'Enter your full name.' }}</span>
                </div>
                <div class="field @error('phone') invalid @enderror">
                  <label for="rg-phone">Phone<span class="req">*</span></label
                  ><input
                    class="input"
                    id="rg-phone"
                    name="phone"
                    value="{{ old('phone') }}"
                    type="tel"
                    autocomplete="tel"
                    placeholder="07XX XXX XXX"
                    required
                  /><span class="error">{{ $errors->first('phone') ?: 'Enter your phone number.' }}</span>
                </div>
              </div>
              <div class="form-row">
                <div class="field @error('email') invalid @enderror">
                  <label for="rg-email">Email<span class="req">*</span></label
                  ><input
                    class="input"
                    id="rg-email"
                    name="email"
                    value="{{ old('email') }}"
                    type="email"
                    autocomplete="email"
                    placeholder="you@email.com"
                    required
                  /><span class="error">{{ $errors->first('email') ?: 'Enter a valid email address.' }}</span>
                </div>
                <div class="field @error('password') invalid @enderror">
                  <label for="rg-pass">Password<span class="req">*</span></label>
                  <div class="pw">
                    <input
                      class="input"
                      id="rg-pass"
                      name="password"
                      type="password"
                      autocomplete="new-password"
                      placeholder="Create a password"
                      required
                      data-strength
                    /><button type="button" aria-label="Show password">
                      <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                      </svg>
                    </button>
                  </div>
                  <span class="error">{{ $errors->first('password') ?: 'Enter your password.' }}</span>
                </div>
              </div>
              <label class="check"
                ><input type="checkbox" name="terms" value="1" required @checked(old('terms')) /><span
                  >I agree to the <a href="{{ route('site.page', 'terms') }}">Terms of service</a> and
                  <a href="{{ route('site.page', 'privacy') }}">Privacy policy</a>.</span
                ></label
              >
            </div>
            <div class="kyc-nav">
              <span class="muted text-xs">Have an account? <a href="{{ route('login') }}">Sign in</a></span
              ><button class="btn btn-primary" type="submit">
                <span class="go-ind">Continue to personal details</span
                ><span class="go-biz">Register yourself first</span>
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>
    @include('home.partials.toast')
    <script>
      // A business is added from the account after the person registers and verifies: keep Individual selected and say so
      document.addEventListener('DOMContentLoaded', function () {
        var ind = document.getElementById('rt-ind'),
          biz = document.getElementById('rt-biz'),
          toast = document.querySelector('.toast');
        biz.addEventListener('change', function () {
          biz.checked = false;
          ind.checked = true;
          ind.dispatchEvent(new Event('change', { bubbles: true }));
          toast.querySelector('[data-msg]').textContent =
            'Register yourself first, then add your business from your account.';
          toast.classList.add('show');
          setTimeout(function () {
            toast.classList.remove('show');
          }, 3800);
        });
      });
    </script>
  </body>
</html>
