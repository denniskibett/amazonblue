<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <style>[x-cloak]{display:none !important}</style>{{-- hidden until Alpine starts: no pop-up flashes on load (23 Oct 2026) --}}
    @include('partials.ui.img-fallback')
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    {{-- Favicons: the brand icon first; an icon uploaded in System settings is used only when its file is really there --}}
    <link rel="icon" href="{{ asset('assets/images/logo/favicon.svg') }}?v=2" type="image/svg+xml">
    <link rel="icon" href="{{ \App\Helpers\SystemHelper::faviconUrl() }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('assets/images/logo/site.webmanifest') }}">
    <title>@yield('title', 'AmazonBlue Capital | We Sell Convenience')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@800&family=Inter:wght@400;600;700;800&display=swap"
        rel="stylesheet" />
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- AOS Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    <!-- Swiper JS CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/css/style.css') }}">
    
    @stack('styles')
</head>

<body>
    <!-- SVG Icons Sprite -->
    @include('partials.svg-icons')
    
    <!-- Fullscreen Menu Overlay -->
    @include('partials.fullscreen-menu')

    <!-- Main Content -->
    @yield('content')

    <!-- Footer Section -->
    @include('partials.footer')

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JQuery js -->
    <script src="{{ asset('frontend/js/jquery-1.11.0.min.js') }}"></script>
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- Main Script -->
    <script src="{{ asset('frontend/js/script.js') }}"></script>
    
    @include('partials.modal.dialog')
@include('partials.modal.toasts')
@stack('scripts')
</body>

</html>