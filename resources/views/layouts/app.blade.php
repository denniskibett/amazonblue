<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ SystemHelper::appName() }} | {{ ucfirst(request()->segment(1) ?? 'Dashboard') }}</title>

    {{-- Dynamic Meta --}}
    @if(SystemHelper::metaDescription())
        <meta name="description" content="{{ SystemHelper::metaDescription() }}">
    @endif

    @if(SystemHelper::metaKeywords())
        <meta name="keywords" content="{{ SystemHelper::metaKeywords() }}">
    @endif

    {{-- Dynamic Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ SystemHelper::faviconUrl() }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ SystemHelper::faviconUrl() }}">
    <link rel="apple-touch-icon" href="{{ SystemHelper::faviconUrl() }}">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/bootstrap.js',
        'resources/js/index.js'
    ])

    <!-- Auto-load all JS components -->
    @php
        // Get all JS component files
        $components = collect(File::allFiles(resource_path('js/components')))
            ->filter(fn($file) => $file->getExtension() === 'js')
            ->map(fn($file) => 'resources/js/components/'.$file->getRelativePathname())
            ->values()
            ->all();
    @endphp

    @vite($components)
</head>

<body class="font-outfit antialiased bg-gray-100"
      x-data="{
          page: '{{ request()->segment(1) ?? 'dashboard' }}',
          loaded: true,
          darkMode: JSON.parse(localStorage.getItem('darkMode')) || false,
          stickyMenu: false,
          sidebarToggle: false,
          scrollTop: false,
          selected: $persist('{{ ucfirst(request()->segment(1) ?? 'Dashboard') }}')
      }"
      x-init="$watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
      :class="{'dark bg-gray-900': darkMode}">

@include('partials.preloader')

<div class="flex h-screen overflow-hidden">
    @include('partials.sidebar')

    <div class="relative flex flex-col flex-1 overflow-x-hidden overflow-y-auto">

        {{-- Header --}}
        @include('partials.header')

        

        {{-- Page Content --}}
        <main class="flex-1 p-6">
            @yield('content')
        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
