{{-- What KYC asks for, before the first step (owner, 21 Nov 2026: "stipulate their kyc demands and make it known").
     Lists each section with done/needed, and sends them on to the first section still missing. --}}
@extends('home.layouts.kyc', ['step' => 1, 'title' => 'Verify your account'])

@php
  $state = $status['state'];
  $done = collect($status['sections'])->keyBy('title');
  $tick = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>';
  $started = $status['done'] > 0 || in_array($state, ['rejected', 'missing'], true);
@endphp

@section('content')
  <div class="kyc-head">
    <h1>{{ $started ? 'Pick up where you left off' : 'Here\'s what you\'ll need' }}</h1>
    <p class="muted">
      {{ $partnerOnly
          ? 'Before you can add funds or invest, we confirm who you are. It takes about 3 minutes.'
          : 'Before you can borrow, we confirm who you are and how to reach you. It takes about 5 minutes.' }}
      Your progress is saved after every step.
    </p>
  </div>

  @if ($state === 'rejected')
    <div class="kyc-note error" role="alert">{!! $tick !!}<div><b>Our team asked for changes</b><span>{{ $status['notes'] ?: 'Check your details and submit again.' }}</span></div></div>
  @endif

  <div class="kyc-card">
  <ol class="hold-list">
    @foreach ($requirements as $title => $items)
      @php $section = $done[$title] ?? null; $ok = $section['complete'] ?? false; @endphp
      <li class="{{ $ok ? 'ok' : '' }}">
        <span class="chip {{ $ok ? 'sage' : 'sand' }}">{!! $ok ? $tick : $loop->iteration !!}</span>
        <div>
          <b>{{ $title }} <em>{{ $ok ? 'Done' : 'Needed' }}</em></b>
          <ul>@foreach ($items as $item)<li>{{ $item }}</li>@endforeach</ul>
        </div>
        @if ($section)<a href="{{ $section['href'] }}">{{ $ok ? 'Edit' : 'Add' }}</a>@endif
      </li>
    @endforeach
    @if ($state !== 'missing')
      <li class="{{ $status['emailConfirmed'] ? 'ok' : '' }}">
        <span class="chip {{ $status['emailConfirmed'] ? 'sage' : 'sand' }}">{!! $status['emailConfirmed'] ? $tick : count($requirements) + 1 !!}</span>
        <div>
          <b>Submit <em>Last step</em></b>
          <ul>@foreach ($finalStep as $item)<li>{{ $item }}</li>@endforeach</ul>
        </div>
      </li>
    @endif
  </ol>
  </div>

  <p class="hold-meta">Tip: have your ID card with you and use your phone, so you can photograph it and take the selfies straight away.</p>

  <div class="kyc-nav">
    <a href="{{ route('account.home') }}">Later</a>
    <a class="btn btn-primary" href="{{ $state === 'ready' ? route('kyc.review') : $status['next'] }}">{{ $state === 'ready' ? 'Review and submit' : ($started ? 'Continue' : 'Start') }}</a>
  </div>
@endsection
