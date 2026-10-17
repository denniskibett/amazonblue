@extends('home.layouts.kyc', ['step' => 7, 'title' => 'Submitted'])

@section('content')
  <div class="kyc-card done">
    <span class="chip sage big"
      ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg
    ></span>
    <h1>Thank you, {{ $firstName }}. You're almost verified.</h1>
    <p class="muted">
      Our team is checking your documents and will call your next of kin and guarantor to confirm your details.
      Once you're approved, you can borrow straight from your dashboard.
    </p>
    <div class="kyc-ref">
      <div><span>Reference</span><b class="num">{{ $reference }}</b></div>
      <div><span>Submitted</span><b class="num">{{ $user->kyc_submitted_at->isToday() ? 'Today' : $user->kyc_submitted_at->format('j M Y') }}</b></div>
      <div><span>Status</span><b class="num">{{ $verified ? 'Verified' : ($user->kyc_status === 'rejected' ? 'Needs attention' : 'In review') }}</b></div>
    </div>
    <div class="actions">
      <a class="btn btn-primary" href="{{ route('account.home') }}">Go to my account</a
      ><a class="btn btn-ghost" href="{{ url('/') }}">Back to website</a>
    </div>
  </div>
@endsection
