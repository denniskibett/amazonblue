@php
  // Business finance journey shell (20 Nov 2026): the KYB look (kyc2 + business.css). $title, $fa (or null), $token,
  // $current (a step key, or null on the status page).
  $fa = $fa ?? null; $token = $token ?? null; $current = $current ?? null;
  $steps = config('finance_journey.steps');
  $keys = array_keys($steps);
  $tick = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>';
  $art = $art ?? ($fa ? $fa->productConfig('label') . '.' : 'Business finance.');
@endphp
<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => $title . ' — Aimzone Ventures', 'description' => 'Apply for asset or trade finance for your business', 'noindex' => true])
    <meta name="referrer" content="no-referrer" />
    <link rel="stylesheet" href="{{ asset('assets/css/business.css') }}?v={{ @filemtime(public_path('assets/css/business.css')) }}" />
    <style>
      .fj-est{display:grid;gap:6px;border:1px solid #DDE3EF;border-radius:14px;padding:16px;background:#F6F8FD}
      .fj-est b.num{font:600 24px/1.2 Outfit,sans-serif;color:#1B2B6B}
      .fj-rows{display:grid;gap:8px;margin:0;padding:0;list-style:none}
      .fj-rows li{display:flex;justify-content:space-between;gap:12px;font-size:14px;border-bottom:1px solid #EEF1F7;padding-bottom:8px}
      .fj-rows li span:first-child{color:#5A6178}
      .fj-time{display:grid;gap:0;margin:0;padding:0;list-style:none}
      .fj-time li{display:grid;grid-template-columns:28px 1fr;gap:10px;padding:10px 0;border-bottom:1px solid #EEF1F7}
      .fj-time .dot{width:24px;height:24px;border-radius:50%;display:grid;place-items:center;font-size:12px;font-weight:600;background:#E6ECFC;color:#2B4FD8}
      .fj-time .dot.ok{background:#DCEBE2;color:#2F6B4F}
      .fj-time .dot.now{background:#2B4FD8;color:#fff}
      .fj-cards{display:grid;gap:14px}
      .fj-card{display:grid;gap:6px;border:1px solid #DDE3EF;border-radius:16px;padding:18px;text-decoration:none;color:inherit;background:#fff}
      .fj-card:hover,.fj-card:focus-visible{border-color:#2B4FD8}
      .fj-small{font-size:13px;color:#5A6178}
      .fj-file{display:flex;gap:8px;align-items:center;font-size:13px}
      .fj-file form{margin:0}
      .fj-file button{background:none;border:0;color:#9B2C3B;cursor:pointer;font:inherit;text-decoration:underline;padding:0}
    </style>
  </head>
  <body>
    <a class="skip" href="#main">Skip to content</a>
    <div class="kyc2 biz" data-server>
      <aside class="auth-art kyc2-art">
        <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"><img src="{{ asset('assets/images/logo/aimzone-primary-white.svg') }}" alt="Aimzone Ventures" /></a>
        <div class="art-copy">
          <h2>{{ $art }}</h2>
          <p>Every step is saved as you go. Leave at any time and come back with your private link.</p>
        </div>
      </aside>
      <main class="kyc2-main" id="main">
        <div class="kyc2-col">
          <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"><img class="kyc2-mobile-logo" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures" /></a>

          @if ($fa && $current)
            <nav class="segbar" aria-label="Application steps">
              <div class="segs">
                @foreach ($steps as $k => $label)
                  @php $n = $loop->iteration; @endphp
                  @if ($k === $current)<span class="seg2 now" aria-current="step"><span>{{ $n }}</span><em>{{ $label }}</em></span
                  >@elseif ($fa->stepDone($k))<a class="seg2 done" href="{{ route('finance.step', [$token, $k]) }}"><span>{!! $tick !!}</span><em>{{ $label }}</em></a
                  >@else<a class="seg2" href="{{ route('finance.step', [$token, $k]) }}"><span>{{ $n }}</span><em>{{ $label }}</em></a
                  >@endif
                @endforeach
              </div>
            </nav>
          @endif

          @if ($fa)
            <div class="biz-who">
              <span class="biz-av">{{ mb_strtoupper(mb_substr($fa->businessName(), 0, 2)) }}</span>
              <div><b>{{ $fa->businessName() }}</b>{{ $fa->productConfig('label') }} · {{ $fa->statusLabel() }}</div>
              <span class="biz-ref num">{{ $fa->reference }}</span>
            </div>
          @endif

          @if (session('status'))
            <div class="kyc-note ok" role="status">{!! $tick !!}<div><b>{{ session('status') }}</b></div></div>
          @endif
          @if ($errors->any())
            <div class="kyc-note error" role="alert">
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 16h.01M12 3l10 18H2z" /></svg>
              <div><b>{{ $errors->has('form') ? 'Not yet' : 'Please check the highlighted fields' }}</b><span>{{ $errors->first('form') ?: $errors->first() }}</span></div>
            </div>
          @endif

          @yield('content')

          <p class="fj-small" style="margin-top:24px">Aimzone Ventures offers credit subject to its assessment, terms and a signed agreement. Starting an application does not commit you or us. Questions? +254 701 607 959 · <a href="{{ route('finance.resume') }}">Lost your link?</a></p>
        </div>
      </main>
    </div>
    @stack('scripts')
  </body>
</html>
