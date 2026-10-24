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
      <div class="a-note ok"><div><b>Verified</b>Approved {{ optional($business->reviewed_at)->format('j M Y') }}.@if (\App\Services\Business\BusinessLending::enabled() && $business->credit_limit) Business limit KES {{ number_format((float) $business->credit_limit, 2) }}.@endif
        @if (\App\Services\Business\BusinessLending::enabled())<div><a class="btn btn-primary" href="{{ route('account.business.borrow', $business) }}">Borrow for the business</a></div>@endif</div></div>
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
        @php $dv = app(\App\Services\Business\DirectorVerification::class); $mustVerify = $dv->required($business)->pluck('id')->all(); @endphp
        @error('link')<x-az.alert type="err" title="Link not sent">{{ $message }}</x-az.alert>@enderror
        @forelse ($business->members as $m)
          <div class="a-row">
            <span class="biz-av">{{ $m->initials() }}</span>
            <span class="t"><b>{{ $m->full_name }}</b><span>{{ $m->roleLabel() }}{{ $m->is_signatory ? ' · signs' : '' }}
              {{-- directors verify themselves from a link (owner, 24 Oct 2026) --}}
              @if (\App\Services\Business\BusinessLending::enabled() && in_array($m->id, $mustVerify, true))
                · {{ $dv->isVerified($m) ? 'verified' : ($m->verify_sent_at ? 'link sent ' . $m->verify_sent_at->diffForHumans() : 'not verified') }}
              @endif</span></span>
            <span class="amt num">{{ $pct($m->ownership_pct) }}</span>
            @if (\App\Services\Business\BusinessLending::enabled() && in_array($m->id, $mustVerify, true) && ! $dv->isVerified($m))
              <form method="POST" action="{{ route('account.business.members.link', [$business, $m]) }}">@csrf<button class="btn btn-ghost" type="submit">{{ $m->verify_sent_at ? 'Send again' : 'Send link' }}</button></form>
            @endif
          </div>
        @empty
          <p class="a-empty">No one added yet.</p>
        @endforelse
      </div>
    </section>
  </div>

  @if ($business->status === 'approved' && \App\Services\Requests::enabled())
    {{-- an approved business is locked; changes go through a request (owner, 24 Oct 2026) --}}
    <section class="a-card">
      <div class="a-sec-h"><h2>Something changed?</h2></div>
      <p class="muted" style="font-size:14px;margin:0 0 10px">New director, new address or new bank? Tell us; we reopen the business for you to update it, then check it again.</p>
      <form method="POST" action="{{ route('account.business.change', $business) }}" class="a-grid" style="gap:10px">
        @csrf
        <x-az.field name="message" label="What changed" type="textarea" required maxlength="1000" rows="2" />
        <button class="btn btn-ghost" type="submit">Ask for a change</button>
      </form>
    </section>
  @endif

  @if (\App\Services\Business\BusinessLending::enabled())
    <section class="a-card">
      <div class="a-sec-h"><h2>Business loans</h2></div>
      @if ($business->status === 'approved')
        <p class="muted" style="font-size:14px;margin:0 0 12px">Borrow against {{ $business->trading_name ?: $business->legal_name }}'s own limit. You apply and sign as the owner.</p>
        <a class="btn btn-primary" href="{{ route('account.business.borrow', $business) }}">Borrow for the business</a>
      @else
        <p class="muted" style="font-size:14px;margin:0">Once we verify the business and every director has verified themselves, you can borrow against its own limit here.</p>
      @endif
    </section>
  @endif
@endsection
