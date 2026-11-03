{{-- Collector portal home (owner, 3 Nov 2026): the agency's open placements, forbearance included. --}}
@extends('home.layouts.account', ['title' => 'Accounts', 'tab' => 'home'])

@section('content')
  <div class="acct-head">
    <h1>{{ $agency?->name ?? 'Your accounts' }}</h1>
    <p>{{ $rows->count() }} {{ \Illuminate\Support\Str::plural('account', $rows->count()) }} placed with you · payments go to our paybill only, never to the agency</p>
  </div>

  @if (! $agency || $agency->status !== 'active')
    <section class="a-card"><x-az.empty title="No accounts to work" message="Your agency is not set up or is paused. Ask your contact at Aimzone Ventures." /></section>
  @else
    <div class="a-stats p-three">
      <div class="a-stat"><span>Placed with you</span><b><x-az.money :amount="$rows->sum('owed')" /></b></div>
      <div class="a-stat"><span>Late or defaulted</span><b>{{ $rows->filter(fn ($r) => $r->loan->status !== 'forbearance')->count() }}</b></div>
      <div class="a-stat"><span>In forbearance</span><b>{{ $rows->where('loan.status', 'forbearance')->count() }}</b></div>
    </div>

    <section class="a-card">
      @if ($rows->isEmpty())
        <x-az.empty title="Nothing placed yet" message="Accounts show here as soon as our recovery team places them with you." />
      @else
        <div class="a-list">
          @foreach ($rows as $r)
            <a class="a-row" href="{{ route('collect.show', $r->placement->id) }}" style="text-decoration:none;color:inherit">
              <span class="p-who" aria-hidden="true">{{ collect(explode(' ', (string) $r->loan->user?->name))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') }}</span>
              <span class="t"><b>{{ $r->loan->user?->name }}</b><span>Loan #{{ $r->loan->id }} · {{ $r->loan->loanType?->name }} · {{ $r->days }} days late{{ $r->placement->contactAllowed() ? '' : ' · contact from ' . ($r->placement->contactFrom()?->format('j M') ?? 'when we tell them') }}</span></span>
              <span class="amt"><x-az.money :amount="$r->owed" /><small><x-az.status-badge :status="$r->loan->status" /></small></span>
            </a>
          @endforeach
        </div>
      @endif
    </section>
  @endif
@endsection
