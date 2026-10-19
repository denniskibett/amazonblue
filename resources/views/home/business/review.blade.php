@extends('home.business.layout', ['step' => 5, 'title' => 'Review & submit'])

@php
  $locked = ! $business->isEditable();
  $warn = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 16h.01M12 3l10 18H2z" /></svg>';
@endphp

@section('content')
  <div class="kyc-head">
    <h1>Review &amp; submit</h1>
    <p class="muted">{{ $business->legal_name }} · check everything is right before you send it.</p>
  </div>
  @if ($business->status === 'rejected' && $business->review_notes)
    <div class="kyc-note warn">
      {!! $warn !!}
      <div><b>What our team asked for</b><span>{{ $business->review_notes }}</span></div>
    </div>
  @endif
  <form class="kyc-form" method="POST" action="{{ route('account.business.submit', $business) }}" data-validate data-server novalidate>
    @csrf
    @foreach ($sections as $section)
      <div class="rev">
        @if ($section['complete'])
          <span class="chip sage"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg></span>
        @else
          <span class="chip sand">{!! $warn !!}</span>
        @endif
        <div><b>{{ $section['title'] }}</b><span>{{ $section['complete'] ? $section['summary'] : (($section['problem'] ?? null) ?: $section['summary']) }}</span></div>
        <a href="{{ $section['href'] }}">{{ $locked ? 'View' : ($section['complete'] ? 'Edit' : 'Finish') }}</a>
      </div>
    @endforeach
    @unless ($locked)
      <div class="kyc-card">
        <label class="check"><input type="checkbox" name="confirm_true" value="1" required /><span>The information and documents I've given are true, complete and current.</span></label
        ><label class="check"><input type="checkbox" name="confirm_authorised" value="1" required /><span>I am authorised to act for {{ $business->legal_name }}, and the people listed agreed to share their details.</span></label
        ><label class="check"><input type="checkbox" name="confirm_checks" value="1" required /><span>I consent to Aimzone checking the business with the Business Registration Service, KRA and credit reference bureaus.</span></label>
      </div>
    @endunless
    <div class="kyc-nav">
      <a href="{{ route('account.business.financials', $business) }}">Back</a>
      @if ($locked)
        <a class="btn btn-primary" href="{{ route('account.business.show', $business) }}">Done</a>
      @else
        <button class="btn btn-primary" type="submit">{{ $business->status === 'rejected' ? 'Send again for review' : 'Submit for review' }}</button>
      @endif
    </div>
  </form>
@endsection
