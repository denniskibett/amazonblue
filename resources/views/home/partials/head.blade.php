{{-- Shared <head> for every Aimzone page. Pass $title and $description. --}}
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}" />
<meta name="theme-color" content="#1B2B6B" />
<link rel="icon" href="{{ asset('assets/images/logo/favicon.ico') }}" sizes="any" />
<link rel="icon" href="{{ asset('assets/images/logo/favicon.svg') }}" type="image/svg+xml" />
<link rel="apple-touch-icon" href="{{ asset('assets/images/logo/apple-touch-icon.png') }}" />
<link rel="manifest" href="{{ asset('assets/images/logo/site.webmanifest') }}" />
<meta property="og:title" content="{{ $title }}" />
<meta property="og:description" content="{{ $description }}" />
<meta property="og:type" content="website" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=Sora:wght@300;500;600;700&family=Figtree:wght@400;500;600;700&family=Outfit:wght@500;600&display=swap"
  rel="stylesheet"
/>
<link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}" />
