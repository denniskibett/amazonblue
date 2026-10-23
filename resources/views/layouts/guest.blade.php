<!-- resources/views/layouts/guest.blade.php -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{ darkMode: false }"
    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode'));
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>
<head>
    <meta charset="utf-8">
    <style>[x-cloak]{display:none !important}</style>{{-- hidden until Alpine starts: no pop-up flashes on load (23 Oct 2026) --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Favicons: the brand icon first; an icon uploaded in System settings is used only when its file is really there --}}
    <link rel="icon" href="{{ asset('assets/images/logo/favicon.svg') }}?v=2" type="image/svg+xml">
    <link rel="icon" href="{{ \App\Helpers\SystemHelper::faviconUrl() }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('assets/images/logo/site.webmanifest') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts: one stylesheet and one script (owner, 23 Oct 2026) -->
    @vite(['resources/css/app.css', 'resources/js/index.js'])
    <script defer src="{{ asset('assets/js/dates.js') }}?v={{ filemtime(public_path('assets/js/dates.js')) }}"></script>
</head>
<body class="font-sans antialiased">
    @yield('content')
</body>
</html>