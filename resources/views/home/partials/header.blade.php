{{-- Site header. $variant: '', 'on-photo' or 'on-dark'. $active: 'services', 'company', 'contact' or null. --}}
@php
  $links = ['services' => 'Services', 'company' => 'Company', 'contact' => 'Contact'];
@endphp
<header class="site-header {{ $variant ?? '' }}">
  <div class="wrap header-row">
    <a class="brand" href="{{ url('/') }}" aria-label="Aimzone Ventures home"
      ><img class="word" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures" height="22"
    /></a>
    <button class="menu-btn" type="button" aria-label="Menu" aria-expanded="false" aria-controls="site-nav">
      <span></span>
    </button>
    <nav class="nav" id="site-nav" aria-label="Main">
      @foreach ($links as $page => $label)
        <a class="nav-link" href="{{ route('site.page', $page) }}" @if (($active ?? null) === $page) aria-current="page" @endif>{{ $label }}</a>
      @endforeach
      @auth
        <a class="btn btn-primary" href="{{ route(auth()->user()->homeRoute()) }}">{{ auth()->user()->isCustomer() ? 'My account' : 'Dashboard' }}</a>
      @else
        <a class="login" href="{{ route('login') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c1-4 4-6 8-6s7 2 8 6" /></svg
          >Log in</a>
        <a class="btn btn-primary" href="{{ route('register') }}">Get started</a>
      @endauth
    </nav>
  </div>
</header>
