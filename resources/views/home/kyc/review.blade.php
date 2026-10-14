@extends('home.layouts.kyc', ['step' => 5, 'title' => 'Review & submit'])

@section('content')
  <div class="kyc-head">
    <h1>Review &amp; submit</h1>
    <p class="muted">Check everything is right before you send it.</p>
  </div>
  <form class="kyc-form" method="POST" action="{{ route('kyc.submit') }}" data-validate data-server novalidate>
    @csrf
    @foreach ($sections as $section)
      <div class="rev">
        @if ($section['complete'])
          <span class="chip sage"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg
          ></span>
        @else
          <span class="chip sand"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5M12 16h.01M12 3l10 18H2z" /></svg
          ></span>
        @endif
        <div><b>{{ $section['title'] }}</b><span>{{ $section['summary'] }}</span></div>
        <a href="{{ $section['href'] }}">{{ $section['complete'] ? 'Edit' : 'Finish' }}</a>
      </div>
    @endforeach
    <div class="kyc-card">
      <label class="check"
        ><input type="checkbox" name="confirm_true" value="1" required /><span>The information I've given is true and complete.</span></label
      ><label class="check"
        ><input type="checkbox" name="confirm_not_pep" value="1" required /><span
          >I am not a politically exposed person (PEP), nor related to one.</span
        ></label
      ><label class="check"
        ><input type="checkbox" name="confirm_crb" value="1" required /><span
          >I consent to Aimzone checking my details with credit reference bureaus.</span
        ></label
      >
    </div>
    <div class="kyc-nav">
      <a href="{{ route('kyc.income') }}">Back</a><button class="btn btn-primary" type="submit">Submit for review</button>
    </div>
  </form>
@endsection
