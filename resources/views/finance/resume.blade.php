@extends('finance.layout', ['title' => 'Get a new link'])

@section('content')
  <div class="kyc-head"><h1>Get a new link</h1><p class="muted">Enter the email you applied with. We will send a fresh private link for each application; older links stop working.</p></div>
  <form class="kyc-form" method="POST" action="{{ route('finance.resume.send') }}">
    @csrf
    <div class="kyc-card">
      <div class="field @error('email') invalid @enderror"><label for="r-email">Email</label><input class="input" id="r-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" /><span class="error">{{ $errors->first('email') }}</span></div>
    </div>
    <div class="kyc-nav"><a href="{{ route('finance.choose') }}">Start a new application</a><button class="btn btn-primary" type="submit">Send me a link</button></div>
  </form>
@endsection
