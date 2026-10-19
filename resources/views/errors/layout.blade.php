{{--
  Branded error page in the website design. Each errors/{code}.blade.php passes $code, $heading, $text.
  Kept self-contained and defensive: it must still render when the database or session is unavailable,
  and it never shows anything internal (no exception message, no trace).
--}}
@php
  // a way back that suits who is looking: guests -> website, customers -> account, staff -> dashboard
  $back = ['url' => url('/'), 'label' => 'Go to the home page'];
  try {
      $viewer = auth()->user();
      if ($viewer) {
          $back = $viewer->isCustomer()
              ? ['url' => route('account.home'), 'label' => 'Back to my account']
              : ['url' => route('dashboard'), 'label' => 'Back to the dashboard'];
      }
  } catch (\Throwable $e) {
      // keep the guest link
  }
@endphp
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#1B2B6B" />
    <title>{{ $heading }} — Aimzone Ventures</title>
    <link rel="icon" href="{{ asset('assets/images/logo/favicon.ico') }}" sizes="any" />
    <link rel="icon" href="{{ asset('assets/images/logo/favicon.svg') }}" type="image/svg+xml" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}" />
    <style>
      .err-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--cloud); }
      .err-top { padding: 22px 16px; }
      .err-top .wrap { display: flex; align-items: center; }
      .err-main { flex: 1; display: flex; align-items: center; padding: 24px 16px 64px; }
      .err-card { max-width: 560px; margin: 0 auto; background: var(--paper); border: 1px solid var(--line); border-radius: 28px; padding: 40px 32px; text-align: center; }
      .err-code { display: inline-block; font: 600 13px/1 var(--body); letter-spacing: .12em; color: var(--royal); background: var(--royal-tint); border-radius: 999px; padding: 8px 14px; margin-bottom: 18px; }
      .err-card h1 { font-family: var(--display); font-size: clamp(26px, 5vw, 34px); line-height: 1.2; margin: 0 0 12px; color: var(--ink-blue); }
      .err-card p { color: var(--slate); margin: 0 0 26px; }
      .err-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
      @media (max-width: 480px) { .err-card { padding: 32px 20px; } .err-actions .btn { width: 100%; } }
    </style>
  </head>
  <body>
    <div class="err-page">
      <header class="err-top">
        <div class="wrap">
          <a href="{{ url('/') }}" aria-label="Aimzone Ventures home"><img src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures" height="22" /></a>
        </div>
      </header>
      <main class="err-main" id="main">
        <div class="err-card">
          <span class="err-code">ERROR {{ $code }}</span>
          <h1>{{ $heading }}</h1>
          <p>{{ $text }}</p>
          <div class="err-actions">
            @if ($retry ?? false)
              <a class="btn btn-primary" href="{{ url()->current() }}">Try again</a>
              <a class="btn btn-ghost" href="{{ $back['url'] }}">{{ $back['label'] }}</a>
            @else
              <a class="btn btn-primary" href="{{ $back['url'] }}">{{ $back['label'] }}</a>
              <a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}">Contact us</a>
            @endif
          </div>
        </div>
      </main>
    </div>
  </body>
</html>
