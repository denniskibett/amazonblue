{{-- Account on hold until KYC is finished (owner, 21 Nov 2026). Rendered by HomeController@account for any borrower or
     partner App\Services\KycHold says is not clear. States what KYC asks for, ticks what is done, and keeps paying open. --}}
@extends('home.layouts.account', ['title' => 'Account on hold', 'tab' => 'home', 'area' => $partnerOnly ? 'partner' : null])

@php
  $first = strtok($user->name, ' ');
  $state = $status['state'];
  $done = collect($status['sections'])->keyBy('title');
  $tick = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>';
  $warn = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 16h.01M12 3l10 18H2z" /></svg>';
  $lock = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" /></svg>';
  $clock = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>';
  $what = $partnerOnly ? 'add funds, invest or withdraw' : 'borrow';
  [$tone, $heading, $lead, $cta] = match ($state) {
      'submitted' => ['warn', 'We\'re checking your details', 'Nothing more is needed from you. Our team checks your documents and calls the people you gave us; we\'ll text and email you once you\'re verified.', null],
      'rejected' => ['err', 'Your verification needs changes', $status['notes'] ?: 'Some details could not be confirmed. Correct them and submit again.', 'Fix my details'],
      'missing' => ['info', 'A few details are missing', 'You\'re verified, but some details below are missing. Add them and your account opens straight away; no new review is needed.', 'Add the missing details'],
      'ready' => ['info', 'Everything is filled in: submit it', 'Check your details once and send them to our team.', 'Review and submit'],
      'in_progress' => ['info', 'Finish verifying your account', 'Your progress is saved. Pick up where you left off.', 'Continue where I left off'],
      default => ['info', 'Verify your account to get started', 'Kenyan law requires us to know who our clients are (KYC). It takes about 5 minutes on your phone, and your progress is saved as you go.', 'Start verification'],
  };
@endphp

@section('content')
  <div class="acct-head">
    <h1>Hi {{ $first }}, your account is on hold</h1>
    <p>You can {{ $what }} as soon as your account is verified.</p>
  </div>

  <div class="a-grid">
    <div class="a-note {{ $tone }}" role="status">
      {!! $state === 'submitted' ? $clock : ($state === 'rejected' ? $warn : $lock) !!}
      <div style="flex:1">
        <b>{{ $heading }}</b>
        {{ $lead }}
        @if ($state !== 'submitted')
          <div class="a-steps" aria-label="{{ $status['done'] }} of {{ $status['total'] }} sections done">
            @foreach ($status['sections'] as $s)<i class="{{ $s['complete'] ? 'on' : '' }}"></i>@endforeach
          </div>
          <div class="hold-meta">{{ $status['done'] }} of {{ $status['total'] }} sections done</div>
        @endif
        @if ($cta)
          <div><a class="btn btn-primary" href="{{ $state === 'ready' ? route('kyc.review') : $status['next'] }}">{{ $cta }}</a></div>
        @endif
      </div>
    </div>

    <section class="a-card" aria-labelledby="kyc-needs">
      <div class="a-sec-h"><h2 id="kyc-needs">What we need from you</h2><a href="{{ route('kyc.start') }}">Checklist</a></div>
      <ol class="hold-list">
        @foreach ($requirements as $title => $items)
          @php $section = $done[$title] ?? null; $ok = $section['complete'] ?? false; @endphp
          <li class="{{ $ok ? 'ok' : '' }}">
            <span class="chip {{ $ok ? 'sage' : 'sand' }}">{!! $ok ? $tick : $loop->iteration !!}</span>
            <div>
              <b>{{ $title }} <em>{{ $ok ? 'Done' : 'Needed' }}</em></b>
              <ul>@foreach ($items as $item)<li>{{ $item }}</li>@endforeach</ul>
            </div>
            @if ($section && $state !== 'submitted')
              <a href="{{ $section['href'] }}">{{ $ok ? 'Edit' : 'Add' }}</a>
            @endif
          </li>
        @endforeach
        @if (! in_array($state, ['submitted', 'missing'], true))
          <li class="{{ $status['emailConfirmed'] ? 'ok' : '' }}">
            <span class="chip {{ $status['emailConfirmed'] ? 'sage' : 'sand' }}">{!! $status['emailConfirmed'] ? $tick : count($requirements) + 1 !!}</span>
            <div>
              <b>Submit <em>Last step</em></b>
              <ul>@foreach ($finalStep as $item)<li>{{ $item }}</li>@endforeach</ul>
            </div>
          </li>
        @endif
      </ol>
      <p class="hold-meta">Have your ID and phone camera ready. Your details are kept private and used only to verify you.</p>
    </section>

    <section class="a-card" aria-labelledby="still-open">
      <div class="a-sec-h"><h2 id="still-open">While you wait, you can still</h2></div>
      <ul class="hold-open">@foreach ($stillOpen as $item)<li>{!! $tick !!}{{ $item }}</li>@endforeach</ul>
      <div class="hold-actions">
        @if ($openLoan)
          <a class="btn btn-primary" href="{{ route('account.repay', $openLoan) }}">Pay my loan</a>
          <a class="btn btn-ghost" href="{{ route('account.loan', $openLoan) }}">See my loan</a>
        @elseif ($partnerOnly)
          <a class="btn btn-ghost" href="{{ route('account.statement') }}">My statement</a>
        @else
          <a class="btn btn-ghost" href="{{ route('account.loans') }}">My loans</a>
        @endif
        @unless ($partnerOnly)
          <a class="btn btn-ghost" href="{{ route('account.help') }}">Get help</a>
        @endunless
      </div>
    </section>
  </div>
@endsection
