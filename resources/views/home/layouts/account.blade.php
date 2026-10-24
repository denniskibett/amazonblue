{{--
  Customer account shell (borrowers & partners). Pages extend it with:
    @extends('home.layouts.account', ['title' => ..., 'tab' => 'home|borrow|loans|statement|profile'])
  Partner pages also pass 'area' => 'partner' (tabs home|investments|funded|statement|profile), so a user who is
  both borrower and partner gets the partner tabs there; the switcher links the two homes.
--}}
@php
  $me = auth()->user();
  $isBorrower = $me->hasRole('borrower');
  $isPartner = $me->hasRole('partner');
  $partnerArea = $isPartner && (! $isBorrower || ($area ?? null) === 'partner');
  // blue tick: borrowers whose verification staff have approved
  $verifiedTick = $isBorrower && $me->kyc_status === 'approved';
  $icons = [
    'home' => 'M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
    'borrow' => 'M12 5v14M5 12h14',
    'loans' => 'M4 6h16M4 12h16M4 18h10',
    'statement' => 'M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6',
    'profile' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c1-4 4-6 8-6s7 2 8 6',
    'investments' => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
    'funded' => 'M12 8c4 0 7-1 7-2.5S16 3 12 3 5 4 5 5.5 8 8 12 8zM5 5.5v6C5 13 8 14 12 14s7-1 7-2.5v-6M5 11.5v6C5 19 8 20 12 20s7-1 7-2.5v-6',
    'signout' => 'M15 12H4M8 8l-4 4 4 4M13 4h6a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-6',
  ];
  $nav = $partnerArea
    ? ['home' => ['Home', route($isBorrower ? 'account.partner.home' : 'account.home')], 'investments' => ['Investments', route('account.partner.investments')], 'funded' => ['Loans', route('account.partner.loans')], 'statement' => ['Statement', route('account.statement')], 'profile' => ['Profile', route('account.profile')]]
    : ($isBorrower
    ? ['home' => ['Home', route('account.home')], 'borrow' => ['Borrow', route('account.borrow')], 'loans' => ['Loans', route('account.loans')], 'profile' => ['Profile', route('account.profile')]]
    : ['home' => ['Home', route('account.home')], 'profile' => ['Profile', route('account.profile')]]);
  // borrower + partner: switch between the two homes
  $switch = $isBorrower && $isPartner
    ? '<div class="acct-switch" role="group" aria-label="Account type"><a href="' . route('account.home') . '"' . ($partnerArea ? '' : ' aria-current="true"') . '>Borrowing</a><a href="' . route('account.partner.home') . '"' . ($partnerArea ? ' aria-current="true"' : '') . '>Partner</a></div>'
    : '';
  $icon = fn ($key) => '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $icons[$key] . '" /></svg>';
  $initials = collect(explode(' ', trim($me->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
  $avatar = $me->avatar ? $me->documentUrl('photo') : null;
  $whatsapp = 'https://wa.me/254701607959';
@endphp
<!doctype html>
<html lang="en">
  <head>
    @include('home.partials.head', ['title' => $title . ' — Aimzone Ventures', 'description' => 'Your Aimzone Ventures account', 'noindex' => true])
    {{-- live figures on the account pages (amount picker, totals) --}}
    {{-- served by the app itself, not a CDN (owner, 24 Oct 2026) --}}
    <script defer src="{{ asset('assets/vendor/alpine-3.15.4.min.js') }}"></script>
  </head>
  <body>
    @include('partials.ui.preview-bar')
    <a class="skip" href="#main">Skip to content</a>
    <div class="acct">
      {{-- desktop sidebar --}}
      <aside class="acct-side" aria-label="Account">
        <a class="brand" href="{{ url('/') }}" aria-label="Aimzone Ventures website"
          ><img src="{{ asset('assets/images/logo/aimzone-horizontal-white.svg') }}" alt="Aimzone Ventures"
        /></a>
        {!! $switch !!}
        <nav>
          @foreach ($nav as $key => [$label, $href])
            <a href="{{ $href }}" @if (($tab ?? null) === $key) aria-current="page" @endif>{!! $icon($key) !!}{{ $label }}</a>
          @endforeach
        </nav>
        <div class="side-foot">
          <div class="acct-help">
            Need help? Our team is on WhatsApp 24/7.<br /><a href="{{ $whatsapp }}" target="_blank" rel="noopener">Message us</a>@if ($isBorrower && ! $partnerArea) · <a href="{{ route('account.help') }}">Help centre</a>@endif
          </div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="signout" type="submit">{!! $icon('signout') !!}Sign out</button>
          </form>
        </div>
      </aside>

      <div>
        {{-- phone top bar --}}
        <header class="acct-top">
          <a class="brand" href="{{ route('account.home') }}" aria-label="Account home"
            ><img src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures"
          /></a>
          {!! $switch !!}
          <a class="acct-avatar" href="{{ route('account.profile') }}" aria-label="Your profile{{ $verifiedTick ? ', verified' : '' }}">
            @if ($avatar)<img src="{{ $avatar }}" alt="" />@else{{ $initials }}@endif
            @if ($verifiedTick)@include('home.partials.verified-tick', ['class' => 'on-avatar'])@endif
          </a>
        </header>

        <main class="acct-main" id="main">
          @yield('content')
        </main>
      </div>

      {{-- phone bottom tabs --}}
      <nav class="acct-tabs" aria-label="Account">
        @foreach ($nav as $key => [$label, $href])
          <a href="{{ $href }}" @if (($tab ?? null) === $key) aria-current="page" @endif>{!! $icon($key) !!}{{ $label }}</a>
        @endforeach
      </nav>
    </div>

    @include('home.partials.alert-dialog')
    @include('home.partials.toast')
    @stack('scripts')
  </body>
</html>
