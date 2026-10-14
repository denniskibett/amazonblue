{{--
  Website layout. Pages extend it with:
    @extends('home.layouts.app', ['title' => ..., 'description' => ..., 'variant' => 'on-photo|on-dark', 'active' => 'services|company|contact'])
--}}
<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', [
      'title' => $title ?? 'Aimzone Ventures — Emergency funding and trade finance in Kenya',
      'description' => $description ?? 'Emergency bridge finance, asset-backed lending, CRB resolution and trade finance for businesses across East Africa. Fast, discreet, from Nairobi.',
    ])
  </head>
  <body>
    <a class="skip" href="#main">Skip to content</a>
    @include('home.partials.header', ['variant' => $variant ?? '', 'active' => $active ?? null])
    <main id="main">
      @yield('content')
    </main>
    @include('home.partials.footer')
    @include('home.partials.toast')
    @stack('scripts')
  </body>
</html>
