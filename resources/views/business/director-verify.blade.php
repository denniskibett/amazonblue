{{-- A director verifies themselves for a business's loans (owner, 23 Oct 2026). Public page: the link is the key. --}}
<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => 'Verify yourself — Aimzone Ventures', 'description' => 'Director verification', 'noindex' => true])
  </head>
  <body>
    <div class="kyc2">
      <aside class="auth-art kyc2-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures" /></a>
        <div class="art-copy">
          <h2>Directors verify once.</h2>
          <p>Required by law before we lend to a business. About three minutes.</p>
        </div>
      </aside>
      <main class="kyc2-main" id="main">
        <div class="kyc2-col">
          <img class="kyc2-mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures" />

          @if ($done)
            <div class="kyc-head"><h1>Thank you, {{ $done['name'] }}</h1>
              <p>You are verified as a director of {{ $done['business'] }}. There is nothing else to do; you can close this page.</p></div>
          @elseif (! $member)
            <div class="kyc-head"><h1>This link has expired</h1>
              <p>It may have been used already or be more than {{ (int) config('lending.business_loans.link_days', 7) }} days old. Ask the business to send you a new one.</p></div>
          @else
            <div class="kyc-head">
              <h1>Verify yourself, {{ \Illuminate\Support\Str::before($member->full_name, ' ') }}</h1>
              <p>{{ $member->business->legal_name }} listed you as {{ strtolower($member->roleLabel()) }}. We check every director before a business loan.</p>
            </div>
            @if ($errors->any())
              <div class="kyc-note error" role="alert"><div><b>Please check the form</b><span>{{ $errors->first() }}</span></div></div>
            @endif
            <form method="POST" action="{{ route('directors.verify.store', $token) }}" enctype="multipart/form-data" class="kyc-card" style="display:grid;gap:14px">
              @csrf
              <x-az.field name="id_number" label="ID or passport number" required maxlength="20" autocomplete="off" />
              <x-az.field name="dob" label="Date of birth" type="date" required :max="now()->subYears(18)->toDateString()" />
              <label class="field" style="display:grid;gap:6px"><span class="label" style="font-weight:600;font-size:14px">Photo of your ID (front) or passport bio page</span>
                <input type="file" name="director_id_front" accept="image/*,application/pdf" capture="environment" required></label>
              <label class="field" style="display:grid;gap:6px"><span class="label" style="font-weight:600;font-size:14px">Photo of the back of your ID (not needed for a passport)</span>
                <input type="file" name="director_id_back" accept="image/*,application/pdf" capture="environment"></label>
              <label class="field" style="display:grid;gap:6px"><span class="label" style="font-weight:600;font-size:14px">A selfie: your face, clear, no hat or glasses</span>
                <input type="file" name="director_selfie" accept="image/*" capture="user" required></label>
              <label class="check"><input type="checkbox" name="consent" value="1" required><span>I am {{ $member->full_name }}, a director of {{ $member->business->legal_name }}, and these are my own documents.</span></label>
              <button class="btn btn-primary btn-block" type="submit">Verify me</button>
              <p class="muted" style="font-size:13px;margin:0">Your ID pictures are seen only by Aimzone's verification team, not by the business.</p>
            </form>
          @endif
        </div>
      </main>
    </div>
  </body>
</html>
