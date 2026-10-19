@extends('home.business.layout', ['step' => 0, 'title' => 'Add your business', 'business' => null])

@php
  $doc = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg>';
  $ready = [
    ['Certificate of registration or incorporation', 'From the Business Registration Service (BRS)'],
    ['CR12 (limited companies)', 'Directors and shareholders, dated within the last 6 months'],
    ['Business KRA PIN certificate', 'From iTax'],
    ['ID numbers of directors and owners', 'Everyone who owns 10% or more, and every director'],
    ['Business bank account', 'Bank name and account number; statements help'],
  ];
@endphp

@section('content')
  <div class="kyc-head">
    <h1>Add your business</h1>
    <p class="muted">A business is always linked to a verified individual: you, applying on the business's behalf.</p>
  </div>

  @if ($eligible)
    <div class="kyc-note ok">
      <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>
      <div><b>You're verified</b><span>{{ $user->name }} · individual KYC approved{{ $user->kyc_reviewed_at ? ' ' . $user->kyc_reviewed_at->format('j M') : '' }}. You can add a business.</span></div>
    </div>
  @else
    <div class="kyc-note warn" role="alert">
      <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 16h.01M12 3l10 18H2z" /></svg>
      <div>
        <b>Verify yourself first</b>
        <span>
          @if ($user->kyc_status === 'submitted')
            Your individual verification is with our team. You can add a business as soon as it's approved.
          @elseif ($user->kyc_status === 'rejected')
            Your individual verification needs attention. Fix it and send it again, then add your business.
          @else
            We verify the person behind every business. Complete your individual verification first; it takes about 5 minutes.
          @endif
        </span>
      </div>
    </div>
  @endif

  <div class="kyc-card">
    <span class="flabel">Have these ready</span>
    <ul class="biz-ready">
      @foreach ($ready as [$name, $hint])
        <li><span class="chip cloud">{!! $doc !!}</span><div><b>{{ $name }}</b><span>{{ $hint }}</span></div></li>
      @endforeach
    </ul>
  </div>

  <div class="kyc-nav">
    <a href="{{ route('account.home') }}">Back to my account</a>
    @if (! $eligible)
      <a class="btn btn-primary" href="{{ $user->kyc_status === 'submitted' ? route('account.home') : route('kyc.personal') }}">{{ $user->kyc_status === 'submitted' ? 'Go to my account' : ($user->kyc_status === 'rejected' ? 'Fix my verification' : 'Verify myself') }}</a>
    @elseif ($draft)
      <a class="btn btn-primary" href="{{ route('account.business.review', $draft) }}">Continue {{ \Illuminate\Support\Str::limit($draft->legal_name, 24) }}</a>
    @else
      <a class="btn btn-primary" href="{{ route('account.business.create') }}">Start business verification</a>
    @endif
  </div>
  @if ($eligible && $draft)
    <p class="muted biz-small">Adding a different business? <a href="{{ route('account.business.create') }}">Start a new one</a>.</p>
  @endif
@endsection
