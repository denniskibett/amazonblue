{{-- The next of kin confirms they know the client, with their ID (owner, 21 Nov 2026). Public page: the texted link is
     the key. App\Http\Controllers\Guarantors\KinConfirmController, App\Services\Kyc\KinConfirmations. --}}
@php
  $client = $row?->user?->name;
  $first = $client ? strtok($client, ' ') : null;
  $upIcon = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M6 10l6-6 6 6M4 20h16" /></svg>';
  $answer = old('answer', 'yes');
@endphp
<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => 'Confirm you know them — Aimzone Ventures', 'description' => 'Next of kin confirmation', 'noindex' => true])
  </head>
  <body>
    <div class="kyc2">
      <aside class="auth-art kyc2-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures" /></a>
        <div class="art-copy">
          <h2>Two minutes to confirm.</h2>
          <p>You are not borrowing or guaranteeing anything.</p>
        </div>
      </aside>
      <main class="kyc2-main" id="main">
        <div class="kyc2-col">
          <img class="kyc2-mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures" />

          @if ($done)
            <div class="kyc-head"><h1>Thank you, {{ $done['name'] }}</h1>
              <p>{{ $done['yes'] ? 'You have confirmed. There is nothing else to do; you can close this page.' : 'Thank you for telling us. Our team will look into it; you can close this page.' }}</p></div>
          @elseif (! $row)
            <div class="kyc-head"><h1>This link no longer works</h1>
              <p>It may have been used already or be more than {{ (int) config('lending.guarantors.link_days', 14) }} days old. Ask the person who gave your number to send a new one.</p></div>
          @else
            <div class="kyc-head">
              <h1>Do you know {{ $client }}?</h1>
              <p>{{ $first }} gave you, {{ $row->name }}, as their next of kin{{ $row->relation ? ' (' . strtolower($row->relation) . ')' : '' }} when signing up with Aimzone Ventures. Please confirm it's really you.</p>
            </div>

            @if ($errors->any())
              <div class="kyc-note error" role="alert"><div><b>Please check the form</b><span>{{ $errors->first() }}</span></div></div>
            @endif

            <form class="kyc-form kyc-card" method="POST" action="{{ route('kin.confirm.store', $token) }}" enctype="multipart/form-data" style="display:grid;gap:14px" data-kin>
              @csrf
              <fieldset class="kin-answer">
                <legend class="guar-h">Do you know {{ $first }}?</legend>
                @foreach (\App\Services\Kyc\KinConfirmations::ANSWERS as $value => $label)
                  <label class="check"><input type="radio" name="answer" value="{{ $value }}" @checked($answer === $value)><span>{{ $label }}</span></label>
                @endforeach
              </fieldset>

              <div class="kin-yes" style="display:grid;gap:14px" @if ($answer !== 'yes') hidden @endif>
                <x-az.field name="relation" label="How do you know {{ $first }}?" type="select" :options="array_combine($relations, $relations)" :value="old('relation', $row->relation)" />
                <x-az.field name="id_number" label="Your ID or passport number" maxlength="20" autocomplete="off" />
                <div class="uploads">
                  <div class="upload">
                    <span class="chip">{!! $upIcon !!}</span>
                    <div class="u-text"><b>Your ID front<span class="req">*</span></b><span class="u-hint">Or passport photo page</span></div>
                    <label class="btn btn-ghost u-btn" for="k-front">Add</label
                    ><input class="u-input" type="file" id="k-front" name="id_front" accept="image/*,application/pdf" capture="environment" />
                  </div>
                  <div class="upload">
                    <span class="chip">{!! $upIcon !!}</span>
                    <div class="u-text"><b>Your ID back</b><span class="u-hint">Skip for a passport</span></div>
                    <label class="btn btn-ghost u-btn" for="k-back">Add</label
                    ><input class="u-input" type="file" id="k-back" name="id_back" accept="image/*,application/pdf" capture="environment" />
                  </div>
                </div>
                <label class="check"><input type="checkbox" name="agree" value="1" @checked(old('agree'))><span>I am {{ $row->name }}, I know {{ $first }}, and this is my own ID.</span></label>
              </div>

              <button class="btn btn-primary btn-block" type="submit">Send</button>
              <p class="muted" style="font-size:13px;margin:0">Your ID is seen only by Aimzone's team, not by {{ $first }}. Being next of kin does not make you responsible for any loan.</p>
            </form>
            <script>
              (function () {
                var f = document.querySelector('[data-kin]'), yes = f.querySelector('.kin-yes');
                f.querySelectorAll('input[name=answer]').forEach(function (r) {
                  r.addEventListener('change', function () { yes.hidden = f.answer.value !== 'yes'; });
                });
              })();
            </script>
          @endif
        </div>
      </main>
    </div>
    @include('home.partials.toast')
  </body>
</html>
