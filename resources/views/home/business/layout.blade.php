@php
  // Business KYC shell (same look as individual KYC). $title: page name. $step: 0 start, 1 details, 2 directors,
  // 3 documents, 4 financials, 5 review, 6 submitted. $business: the business (null/unsaved before step 1 is saved).
  $business = $business ?? null;
  $saved = $business && $business->exists;
  $steps = [
    1 => ['Business', $saved ? route('account.business.details', $business) : route('account.business.create')],
    2 => ['Directors', $saved ? route('account.business.members', $business) : null],
    3 => ['Documents', $saved ? route('account.business.documents', $business) : null],
    4 => ['Financials', $saved ? route('account.business.financials', $business) : null],
    5 => ['Review', $saved ? route('account.business.review', $business) : null],
  ];
  $last = array_key_last($steps);
  $tick = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>';
  $arrow = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>';
  $prev = $step === 1 ? route('account.business.start') : ($steps[$step - 1][1] ?? null);
  $next = $step >= 1 && $step < $last ? $steps[$step + 1][1] : null;
  $me = auth()->user();
  $initials = collect(explode(' ', trim($me->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', [
      'title' => $title . ' — Aimzone Ventures',
      'description' => 'Add your business to your Aimzone Ventures account',
      'noindex' => true,
    ])
    <link rel="stylesheet" href="{{ asset('assets/css/business.css') }}?v={{ @filemtime(public_path('assets/css/business.css')) }}" />
  </head>
  <body>
    <a class="skip" href="#main">Skip to content</a>
    <div class="kyc2 biz" data-server>
      <aside class="auth-art kyc2-art">
        <a href="{{ route('account.home') }}" aria-label="My account"
          ><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures"
        /></a>
        <div class="art-copy">
          <h2>Add your business.</h2>
          <p>Business credit and trade finance start here. About 10 minutes, and your progress is saved.</p>
        </div>
      </aside>
      <main class="kyc2-main" id="main">
        <div class="kyc2-col">
          <a href="{{ route('account.home') }}" aria-label="My account"
            ><img class="kyc2-mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures"
          /></a>
          @if ($step >= 1 && $step <= $last)
          <nav class="segbar" aria-label="Business verification steps">
            @if ($prev)
              <a class="segnav back" href="{{ $prev }}" aria-label="Previous step">{!! $arrow !!}</a>
            @else
              <span class="segnav back off" aria-hidden="true">{!! $arrow !!}</span>
            @endif
            <div class="segs">
              @foreach ($steps as $n => [$label, $href])@if ($n < $step && $href)<a class="seg2 done" href="{{ $href }}"><span>{!! $tick !!}</span><em>{{ $label }}</em></a
              >@elseif ($n === $step)<span class="seg2 now" aria-current="step"><span>{{ $n }}</span><em>{{ $label }}</em></span
              >@elseif ($href)<a class="seg2" href="{{ $href }}"><span>{{ $n }}</span><em>{{ $label }}</em></a
              >@else<span class="seg2"><span>{{ $n }}</span><em>{{ $label }}</em></span
              >@endif @endforeach
            </div>
            @if ($next)
              <a class="segnav" href="{{ $next }}" aria-label="Next step">{!! $arrow !!}</a>
            @else
              <span class="segnav off" aria-hidden="true">{!! $arrow !!}</span>
            @endif
          </nav>
          @endif

          <div class="biz-who">
            <span class="biz-av">{{ $initials }}</span>
            <div><b>{{ $me->name }}</b>{{ $me->kyc_status === 'approved' ? 'Individual account · verified' : 'Individual account · not verified yet' }}</div>
            @if ($saved)<span class="biz-ref num">{{ $business->reference() }}</span>@endif
          </div>

          @if (session('status'))
            <div class="kyc-note ok" role="status">
              {!! $tick !!}
              <div><b>{{ session('status') }}</b></div>
            </div>
          @endif
          @if ($errors->any())
            <div class="kyc-note error" role="alert">
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 16h.01M12 3l10 18H2z" /></svg>
              <div>
                <b>{{ $errors->has('business') || $errors->has('members') || $errors->has('documents') ? 'Not yet' : 'Please check the highlighted fields' }}</b>
                <span>{{ $errors->first('business') ?: ($errors->first('members') ?: ($errors->first('documents') ?: $errors->first())) }}</span>
              </div>
            </div>
          @endif
          @if ($saved && ! $business->isEditable() && $step >= 1 && $step <= $last)
            <div class="kyc-note info" role="status">
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>
              <div>
                <b>{{ $business->status === 'approved' ? 'Verified: details are locked' : 'With our team for review' }}</b>
                <span>{{ $business->status === 'approved' ? 'To change anything, message us on WhatsApp.' : 'You can look, but changes wait until the review is done.' }}</span>
              </div>
            </div>
          @endif

          @yield('content')
        </div>
      </main>
    </div>
    @include('home.kyc.partials.capture')
    @include('home.partials.toast')
    @stack('scripts')
  </body>
</html>
