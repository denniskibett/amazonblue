@extends('finance.layout', ['title' => 'Business finance', 'art' => 'Finance for businesses.'])

@section('content')
  <div class="kyc-head">
    <h1>What does your business need?</h1>
    <p class="muted">For registered businesses. About 15 minutes; you can stop and come back.</p>
  </div>
  <div class="fj-cards">
    @foreach (config('finance_journey.products') as $key => $p)
      <a class="fj-card" href="{{ route('finance.start', array_filter(['product' => $key, 'p' => $track])) }}">
        <b style="font-size:18px">{{ $p['label'] }}</b>
        <span>{{ $p['blurb'] }}</span>
        <span class="fj-small">
          @if ($key === 'asset') {{ implode(' · ', array_slice($p['categories'], 0, 4)) }} @else {{ implode(' · ', $p['kinds']) }} @endif
        </span>
      </a>
    @endforeach
  </div>
  <div class="kyc-card" style="margin-top:16px">
    <span class="flabel">What you will need</span>
    <ul class="fj-small" style="margin:0;padding-left:18px">
      <li>Business KRA PIN certificate and CR12</li>
      <li>6 months of bank or M-Pesa statements</li>
      <li>Directors' IDs</li>
      <li>A supplier quote (asset finance) or the LPO, invoice or proforma (trade finance)</li>
    </ul>
  </div>
@endsection
