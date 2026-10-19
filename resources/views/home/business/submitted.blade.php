@extends('home.business.layout', ['step' => 6, 'title' => 'Submitted'])

@section('content')
  <div class="kyc-card done">
    <span class="chip sage big"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg></span>
    <h1>Thank you, {{ $firstName }}. {{ $business->legal_name }} is with our team.</h1>
    <p class="muted">
      We check the documents against the Business Registration Service and KRA, and may call you to confirm the directors.
      We'll email you when it's done, usually within two working days.
    </p>
    <div class="kyc-ref">
      <div><span>Reference</span><b class="num">{{ $business->reference() }}</b></div>
      <div><span>Submitted</span><b class="num">{{ $business->submitted_at->isToday() ? 'Today' : $business->submitted_at->format('j M Y') }}</b></div>
      <div><span>Status</span><b class="num">{{ ['submitted' => 'In review', 'approved' => 'Verified', 'rejected' => 'Needs attention', 'draft' => 'Draft'][$business->status] ?? $business->status }}</b></div>
    </div>
    <div class="actions">
      <a class="btn btn-primary" href="{{ route('account.business.show', $business) }}">View my business</a
      ><a class="btn btn-ghost" href="{{ route('account.home') }}">Go to my account</a>
    </div>
  </div>
@endsection
