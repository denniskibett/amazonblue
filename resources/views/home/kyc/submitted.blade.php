@extends('home.layouts.kyc', ['step' => 6, 'title' => 'Submitted'])

@section('content')
  <div class="kyc-card done">
    <span class="chip sage big"
      ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg
    ></span>
    <h1>Thank you, {{ $firstName }}. You're almost verified.</h1>
    <p class="muted">
      Our team is reviewing your documents. Most reviews finish within 30 minutes, and we'll text you the moment
      it's done.
    </p>
    <div class="kyc-ref">
      <div><span>Reference</span><b class="num">{{ $reference }}</b></div>
      <div><span>Submitted</span><b class="num">{{ $user->kyc_submitted_at->isToday() ? 'Today' : $user->kyc_submitted_at->format('j M Y') }}</b></div>
      <div><span>Status</span><b class="num">{{ $verified ? 'Verified' : 'In review' }}</b></div>
    </div>
    <div class="actions">
      <a class="btn btn-primary" href="{{ route('dashboard') }}">Go to dashboard</a
      ><a class="btn btn-ghost" href="{{ url('/') }}">Back to website</a>
    </div>
  </div>
@endsection
