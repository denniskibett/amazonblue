@php
  // $title: page name. $step: 2 personal, 3 income, 4 contacts, 5 documents (last, camera work), 6 review, 7 submitted (all done)
  $steps = [
    2 => ['Personal', route('kyc.personal')],
    3 => ['Income', route('kyc.income')],
    4 => ['Contacts', route('kyc.contacts')],
    5 => ['Documents', route('kyc.documents')],
    6 => ['Review', route('kyc.review')],
  ];
  $last = array_key_last($steps);
  $tick = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>';
  $arrow = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>';
  $prev = $steps[$step - 1][1] ?? null;
  $next = $step < $last ? $steps[$step + 1][1] : null;
@endphp
<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', [
      'title' => $title . ' — Aimzone Ventures',
      'description' => 'Verify your Aimzone Ventures account',
      'noindex' => true,
    ])
  </head>
  <body>
    <a class="skip" href="#main">Skip to content</a>
    <div class="kyc2" data-server>
      <aside class="auth-art kyc2-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
          ><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures"
        /></a>
        <div class="art-copy">
          <h2>Verify once, borrow any time.</h2>
          <p>Required by law (KYC). About 5 minutes, and your progress is saved.</p>
        </div>
      </aside>
      <main class="kyc2-main" id="main">
        <div class="kyc2-col">
          <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"
            ><img class="kyc2-mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures"
          /></a>
          <nav class="segbar" aria-label="Verification steps">
            @if ($prev && $step <= $last)
              <a class="segnav back" href="{{ $prev }}" aria-label="Previous step">{!! $arrow !!}</a>
            @else
              <span class="segnav back off" aria-hidden="true">{!! $arrow !!}</span>
            @endif
            <div class="segs">
              <span class="seg2 done"><span>{!! $tick !!}</span><em>Register</em></span
              >@foreach ($steps as $n => [$label, $href])@if ($n < $step)<a class="seg2 done" href="{{ $href }}"><span>{!! $tick !!}</span><em>{{ $label }}</em></a
              >@elseif ($n === $step)<a class="seg2 now" href="{{ $href }}" aria-current="step"><span>{{ $n }}</span><em>{{ $label }}</em></a
              >@else<a class="seg2" href="{{ $href }}"><span>{{ $n }}</span><em>{{ $label }}</em></a
              >@endif @endforeach
            </div>
            @if ($next)
              <a class="segnav" href="{{ $next }}" aria-label="Next step">{!! $arrow !!}</a>
            @else
              <span class="segnav off" aria-hidden="true">{!! $arrow !!}</span>
            @endif
          </nav>

          @if (session('status'))
            <div class="kyc-note ok">
              {!! $tick !!}
              <div><b>{{ session('status') }}</b></div>
            </div>
          @endif
          @if ($errors->any())
            <div class="kyc-note error" role="alert">
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 16h.01M12 3l10 18H2z" /></svg>
              <div>
                <b>Please check the highlighted fields</b>
                <span>{{ $errors->first() }}</span>
              </div>
            </div>
          @endif

          @yield('content')
        </div>
      </main>
    </div>
    @include('home.kyc.partials.capture')
    @include('home.partials.toast')
  </body>
</html>
