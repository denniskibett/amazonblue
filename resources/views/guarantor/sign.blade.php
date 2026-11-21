{{-- A guarantor reads and signs the guarantee and release for one loan (owner, 21 Nov 2026). Public page: the texted link
     is the key. App\Http\Controllers\Guarantors\GuarantorSignController, App\Services\Loans\Guarantees. --}}
@php
  $loan = $row?->loan;
  $borrower = $loan?->user?->name;
  $borrowerFirst = $borrower ? strtok($borrower, ' ') : null;
  $upIcon = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M6 10l6-6 6 6M4 20h16" /></svg>';
  $lock = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>';
@endphp
<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => 'Sign as guarantor — Aimzone Ventures', 'description' => 'Guarantor sign-off', 'noindex' => true])
  </head>
  <body>
    <div class="kyc2">
      <aside class="auth-art kyc2-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures" /></a>
        <div class="art-copy">
          <h2>One signature, then the money goes.</h2>
          <p>About three minutes on your phone. Have your ID with you.</p>
        </div>
      </aside>
      <main class="kyc2-main" id="main">
        <div class="kyc2-col">
          <img class="kyc2-mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures" />

          @if ($done)
            <div class="kyc-head"><h1>Thank you, {{ $done['name'] }}</h1>
              <p>Your guarantee is signed. We'll now send {{ $done['borrower'] ?: 'the borrower' }}'s loan. There is nothing else to do; you can close this page.</p></div>
          @elseif (! $row)
            <div class="kyc-head"><h1>This link no longer works</h1>
              <p>It may have been used already, be more than {{ (int) config('lending.guarantors.link_days', 14) }} days old, or the loan may no longer be waiting. Ask the borrower to send you a new one from their account.</p></div>
          @else
            <div class="kyc-head">
              <h1>Sign as {{ $borrowerFirst }}'s guarantor</h1>
              <p>{{ $borrower }} named you, {{ $row->name }}, as guarantor for a loan. We pay it out once you sign.</p>
            </div>

            @if ($errors->any())
              <div class="kyc-note error" role="alert"><div><b>Please check the form</b><span>{{ $errors->first() }}</span></div></div>
            @endif

            <section class="kyc-card guar-doc" aria-labelledby="guar-terms">
              <div class="guar-loan">
                <div><span>Loan</span><b>#{{ str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT) }}</b></div>
                <div><span>Amount</span><b>KES {{ number_format((float) $loan->amount, 2) }}</b></div>
                <div><span>Borrower</span><b>{{ $borrower }}</b></div>
              </div>
              <h2 id="guar-terms">Guarantee and release</h2>
              <ol class="guar-terms">@foreach ($terms as $line)<li>{{ $line }}</li>@endforeach</ol>
            </section>

            <form class="kyc-form kyc-card" method="POST" action="{{ route('guarantor.sign.store', $token) }}" enctype="multipart/form-data" style="display:grid;gap:14px">
              @csrf
              <h2 class="guar-h">Your details</h2>
              <x-az.field name="id_number" label="ID or passport number" required maxlength="20" autocomplete="off" :value="old('id_number', $previous?->id_number)" />
              <x-az.field name="dob" label="Date of birth" type="date" required :max="now()->subYears(18)->toDateString()" :value="old('dob', $previous?->dob?->toDateString())" />
              <x-az.field name="residence" label="Where you live (town and area)" required maxlength="120" placeholder="e.g. Nakuru, Milimani" :value="old('residence', $previous?->residence)" />
              <x-az.field name="occupation" label="What you do (job or business)" required maxlength="120" placeholder="e.g. Teacher at Moi Primary" :value="old('occupation', $previous?->occupation)" />
              <x-az.field name="email" label="Email (optional)" type="email" maxlength="255" :value="old('email', $previous?->email ?? $row->email)" />

              <h2 class="guar-h">Your ID</h2>
              @if ($previous)
                <div class="kyc-note ok"><div><b>Your ID is on file</b><span>You gave it when you last signed for {{ $borrowerFirst }}. Upload again only if it changed.</span></div></div>
              @endif
              <div class="uploads">
                <div class="upload {{ $previous ? 'done' : '' }}">
                  <span class="chip">{!! $upIcon !!}</span>
                  <div class="u-text"><b>ID front{!! $previous ? "" : "<span class=\"req\">*</span>" !!}</b><span class="u-hint">{{ $previous ? 'On file' : 'Or passport photo page' }}</span></div>
                  <label class="btn btn-ghost u-btn" for="g-front">{{ $previous ? 'Replace' : 'Add' }}</label
                  ><input class="u-input" type="file" id="g-front" name="id_front" accept="image/*,application/pdf" capture="environment" />
                </div>
                <div class="upload {{ $previous?->id_back_path ? 'done' : '' }}">
                  <span class="chip">{!! $upIcon !!}</span>
                  <div class="u-text"><b>ID back</b><span class="u-hint">{{ $previous?->id_back_path ? 'On file' : 'Skip for a passport' }}</span></div>
                  <label class="btn btn-ghost u-btn" for="g-back">{{ $previous?->id_back_path ? 'Replace' : 'Add' }}</label
                  ><input class="u-input" type="file" id="g-back" name="id_back" accept="image/*,application/pdf" capture="environment" />
                </div>

                <h2 class="guar-h">Sign here</h2>
                <div class="upload sign">
                  <div class="sig-head">
                    <span class="chip"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg></span>
                    <div class="u-text"><b>Your signature<span class="req">*</span></b><span class="u-hint">Use your finger, or a mouse on a computer.</span></div>
                  </div>
                  <div class="sig-pad">
                    <canvas aria-label="Signature pad: draw your signature" data-sign></canvas
                    ><span class="sig-line"></span><button type="button" class="sig-clear" data-sign-clear>Clear</button>
                  </div>
                  <input type="hidden" name="signature_data" data-sign-out />
                </div>
              </div>

              <label class="check"><input type="checkbox" name="agree" value="1" required @checked(old('agree'))><span>I have read the guarantee above and I agree to it. I am {{ $row->name }}.</span></label>
              <button class="btn btn-primary btn-block" type="submit">Sign and release the loan</button>
              <p class="muted" style="font-size:13px;margin:0">Your ID is seen only by Aimzone's team, not by {{ $borrowerFirst }}.</p>
            </form>
          @endif
        </div>
      </main>
    </div>
    @include('home.partials.toast')
  </body>
</html>
