@extends('finance.layout', ['title' => 'Link not working'])

@section('content')
  <div class="kyc-head"><h1>This link is not working</h1><p class="muted">It may be mistyped, replaced by a newer link, or unused for more than {{ (int) config('finance_journey.link_days', 30) }} days. Your application is still saved.</p></div>
  <div class="kyc-nav"><a href="{{ route('finance.choose') }}">Start a new application</a><a class="btn btn-primary" href="{{ route('finance.resume') }}">Email me a new link</a></div>
@endsection
