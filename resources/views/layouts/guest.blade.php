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

    <!-- Scripts -->
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/bootstrap.js',
        'resources/js/index.js'
    ])

    <!-- Auto-load JS components -->
    @php
        $components = array_diff(scandir(resource_path('js/components')), array('..', '.'));
    @endphp
    @foreach ($components as $component)
        @vite(['resources/js/components/' . $component])
    @endforeach
</head>
<body class="font-sans antialiased">
    @yield('content')
</body>
</html>