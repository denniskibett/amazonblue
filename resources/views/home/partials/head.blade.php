{{-- Shared <head> for every Aimzone page. Pass $title and $description; $noindex = true keeps a page out of search results. --}}
<meta charset="utf-8" />
<style>[x-cloak]{display:none !important}</style>{{-- hidden until Alpine starts: no pop-up flashes on load (23 Oct 2026) --}}
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}" />
<meta name="theme-color" content="#1B2B6B" />
@if ($noindex ?? false)
  <meta name="robots" content="noindex, nofollow" />
@else
  <link rel="canonical" href="{{ url()->current() }}" />
@endif
<link rel="icon" href="{{ asset('assets/images/logo/favicon.ico') }}?v=2" sizes="32x32" />
<link rel="icon" href="{{ asset('assets/images/logo/favicon.svg') }}?v=2" type="image/svg+xml" />
<link rel="apple-touch-icon" href="{{ asset('assets/images/logo/apple-touch-icon.png') }}" />
<link rel="manifest" href="{{ asset('assets/images/logo/site.webmanifest') }}" />
<meta property="og:title" content="{{ $title }}" />
<meta property="og:description" content="{{ $description }}" />
<meta property="og:type" content="website" />
<meta property="og:site_name" content="Aimzone Ventures" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ asset('assets/images/og-aimzone.jpg') }}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:alt" content="Aimzone Ventures: emergency funding and trade finance in Kenya" />
<meta property="og:locale" content="en_KE" />
<meta name="twitter:card" content="summary_large_image" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=Sora:wght@300;500;600;700&family=Figtree:wght@400;500;600;700&family=Outfit:wght@500;600&display=swap"
  rel="stylesheet"
/>
<link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}" />
{{-- date fields become flatpickr pickers; flatpickr itself is fetched only on pages with a date field (owner, 23 Oct 2026) --}}
<script defer src="{{ asset('assets/js/dates.js') }}?v={{ filemtime(public_path('assets/js/dates.js')) }}"></script>
