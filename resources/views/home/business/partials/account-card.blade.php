{{-- Borrower home: the businesses they added, or the way in ("Add a business"). Needs $user. --}}
@php
  $myBusinesses = $user->businesses()->latest('id')->get();
  $canAdd = $user->kyc_status === 'approved';
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/business.css') }}?v={{ @filemtime(public_path('assets/css/business.css')) }}" />
<section class="a-card biz-card" style="margin-top:16px">
  <div class="a-sec-h"><h2>{{ $myBusinesses->count() > 1 ? 'Your businesses' : 'Your business' }}</h2>@if ($myBusinesses->isNotEmpty() && $canAdd)<a href="{{ route('account.business.create') }}">Add a business</a>@endif</div>
  @if ($myBusinesses->isEmpty())
    <a class="a-row" href="{{ route('account.business.start') }}">
      <span class="chip cloud"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4h10v17M15 9h4v12M8 8h4M8 12h4M8 16h4" /></svg></span>
      <span class="t"><b>Add a business</b><span>{{ $canAdd ? 'Verify your company for business credit and trade finance.' : 'Available once your own verification is approved.' }}</span></span>
      <span class="amt" aria-hidden="true">›</span>
    </a>
  @else
    <div class="a-list">
      @foreach ($myBusinesses as $b)
        <a class="a-row" href="{{ route('account.business.show', $b) }}">
          <span class="chip cloud"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4h10v17M15 9h4v12M8 8h4M8 12h4M8 16h4" /></svg></span>
          <span class="t"><b>{{ $b->legal_name }}</b><span>{{ $b->reference() }}</span></span>
          @include('home.business.partials.status', ['business' => $b])
        </a>
      @endforeach
    </div>
  @endif
</section>
