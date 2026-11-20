@extends('finance.layout', ['title' => 'Start · ' . $p['label'], 'art' => $p['label'] . '.'])

@section('content')
  <div class="kyc-head">
    <h1>Start your {{ strtolower($p['label']) }} application</h1>
    <p class="muted">{{ $p['blurb'] }} We save every step and email you a private link to come back.</p>
  </div>
  <form class="kyc-form" method="POST" action="{{ route('finance.store', $product) }}" novalidate>
    @csrf
    @if ($track)<input type="hidden" name="p" value="{{ $track }}" />@endif
    <div class="kyc-card">
      @foreach ([['legal_name', 'Registered business name', 'text', 'organization'], ['contact_name', 'Your name', 'text', 'name'], ['email', 'Work email', 'email', 'email'], ['phone', 'Mobile number', 'tel', 'tel']] as [$f, $l, $t, $ac])
        <div class="field @error($f) invalid @enderror">
          <label for="s-{{ $f }}">{{ $l }}<span class="req">*</span></label>
          <input class="input" id="s-{{ $f }}" name="{{ $f }}" type="{{ $t }}" value="{{ old($f) }}" autocomplete="{{ $ac }}" required @error($f) aria-invalid="true" @enderror />
          <span class="error">{{ $errors->first($f) }}</span>
        </div>
      @endforeach
      <label class="check"><input type="checkbox" name="contact_ok" value="1" @checked(old('contact_ok')) required /><span>Aimzone Ventures may contact me by email, phone or WhatsApp about this application.</span></label>
      @error('contact_ok')<span class="biz-err">{{ $message }}</span>@enderror
    </div>
    <div class="kyc-nav">
      <a href="{{ route('finance.choose') }}">Back</a>
      <button class="btn btn-primary" type="submit">Start</button>
    </div>
  </form>
@endsection
