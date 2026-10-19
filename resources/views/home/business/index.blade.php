@extends('home.layouts.account', ['title' => 'My businesses', 'tab' => 'profile'])

@section('content')
  <link rel="stylesheet" href="{{ asset('assets/css/business.css') }}?v={{ @filemtime(public_path('assets/css/business.css')) }}" />
  <div class="acct-head">
    <h1>My businesses</h1>
    <p>Businesses you added to your account.</p>
  </div>
  <section class="a-card">
    <div class="a-list">
      @foreach ($businesses as $b)
        <a class="a-row" href="{{ route('account.business.show', $b) }}">
          <span class="chip cloud"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4h10v17M15 9h4v12M8 8h4M8 12h4M8 16h4" /></svg></span>
          <span class="t"><b>{{ $b->legal_name }}</b><span>{{ $b->reference() }} · {{ \App\Models\Business::REGISTRATION_TYPES[$b->registration_type] ?? 'Business' }}</span></span>
          @include('home.business.partials.status', ['business' => $b])
        </a>
      @endforeach
    </div>
  </section>
  @if ($eligible)
    <div class="biz-actions"><a class="btn btn-primary" href="{{ route('account.business.create') }}">Add another business</a></div>
  @endif
@endsection
