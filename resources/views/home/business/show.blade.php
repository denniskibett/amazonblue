@extends('home.layouts.account', ['title' => $business->legal_name, 'tab' => 'profile'])

@php
  $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.') . '%';
  $done = collect($sections)->where('complete', true)->count();
  $next = collect($sections)->firstWhere('complete', false);
@endphp

@section('content')
  <link rel="stylesheet" href="{{ asset('assets/css/business.css') }}?v={{ @filemtime(public_path('assets/css/business.css')) }}" />
  <div class="acct-head biz-head">
    <div>
      <h1>{{ $business->legal_name }}</h1>
      <p>{{ $business->reference() }}{{ $business->handle ? ' · @' . $business->handle : '' }} · {{ \App\Models\Business::REGISTRATION_TYPES[$business->registration_type] ?? 'Business' }}</p>
    </div>
    @include('home.business.partials.status')
  </div>

  @switch ($business->status)
    @case('draft')
      <div class="a-note info">
        <div style="flex:1">
          <b>Finish adding your business</b>
          {{ $done }} of {{ count($sections) }} steps done. Your progress is saved.
          <div class="a-steps" aria-label="{{ $done }} of {{ count($sections) }} steps done">@foreach ($sections as $s)<i class="{{ $s['complete'] ? 'on' : '' }}"></i>@endforeach</div>
          <div><a class="btn btn-primary" href="{{ $next['href'] ?? route('account.business.review', $business) }}">{{ $next ? 'Continue' : 'Review & submit' }}</a></div>
        </div>
      </div>
      @break
    @case('submitted')
      <div class="a-note warn"><div><b>We're checking your business</b>Submitted {{ $business->submitted_at->format('j M Y') }}. We'll email you when it's done, usually within two working days.</div></div>
      @break
    @case('rejected')
      <div class="a-note err">
        <div>
          <b>Your business needs attention</b>
          {{ $business->review_notes ?: 'Please check the details and send them again.' }}
          <div><a class="btn btn-primary" href="{{ route('account.business.review', $business) }}">Update and resubmit</a></div>
        </div>
      </div>
      @break
    @case('approved')
      <div class="a-note ok"><div><b>Verified</b>Approved {{ optional($business->reviewed_at)->format('j M Y') }}. Business products open here in Stage 4.</div></div>
      @break
  @endswitch

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>Details</h2>@if ($business->isEditable())<a href="{{ route('account.business.details', $business) }}">Edit</a>@endif</div>
      <dl class="biz-dl">
        <div><dt>Registration no.</dt><dd class="num">{{ $business->registration_number ?: '—' }}</dd></div>
        <div><dt>KRA PIN</dt><dd class="num">{{ $business->kra_pin ?: '—' }}</dd></div>
        <div><dt>Registered</dt><dd>{{ optional($business->date_registered)->format('j M Y') ?: '—' }}</dd></div>
        <div><dt>Industry</dt><dd>{{ $business->industry ?: '—' }}</dd></div>
        <div><dt>Address</dt><dd>{{ collect([$business->address_line_1, $business->city, $business->county])->filter()->unique()->implode(', ') ?: '—' }}</dd></div>
        <div><dt>Monthly revenue</dt><dd class="num">{{ $business->monthly_revenue !== null ? 'KES ' . number_format((float) $business->monthly_revenue, 2) : '—' }}</dd></div>
      </dl>
    </section>
    <section class="a-card">
      <div class="a-sec-h"><h2>People</h2>@if ($business->isEditable())<a href="{{ route('account.business.members', $business) }}">Edit</a>@endif</div>
      <div class="a-list">
        @forelse ($business->members as $m)
          <div class="a-row">
            <span class="biz-av">{{ $m->initials() }}</span>
            <span class="t"><b>{{ $m->full_name }}</b><span>{{ $m->roleLabel() }}{{ $m->is_signatory ? ' · signs' : '' }}</span></span>
            <span class="amt num">{{ $pct($m->ownership_pct) }}</span>
          </div>
        @empty
          <p class="a-empty">No one added yet.</p>
        @endforelse
      </div>
    </section>
  </div>

  <section class="a-card">
    <div class="a-sec-h"><h2>Business products</h2></div>
    <div class="biz-ph">
      <x-az.placeholder dependency="business loan products and limits" title="Business loans">Working-capital loans priced for {{ $business->trading_name ?: $business->legal_name }}, once business products and limits are set up.</x-az.placeholder>
      <x-az.placeholder dependency="trade finance (LC, FX, supplier payments)" title="Trade finance">Pay suppliers, letters of credit and FX for verified businesses.</x-az.placeholder>
      <x-az.placeholder dependency="asset-backed lending and valuation" title="Asset-backed loans">Borrow against business assets after a valuation.</x-az.placeholder>
    </div>
  </section>
@endsection
