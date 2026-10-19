@extends('home.layouts.account', ['title' => 'Earnings', 'tab' => 'home', 'area' => 'partner'])

@php
  $tone = ['profit_distribution' => 'p-t1', 'interest' => 'p-t2', 'penalty_share' => 'p-t3', 'broker_fee_earned' => 'p-t4'];
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.partner.home') }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>Home</a>
    <div class="acct-head">
      <h1>Earnings</h1>
      <p>{{ $partner->name }} · what has been paid to you, from your statement</p>
    </div>
  </div>

  @if ($years->count() > 1 || $year)
    <nav class="a-filter" aria-label="Filter by year">
      <a href="{{ route('account.partner.earnings') }}" @if (! $year) aria-current="page" @endif>All time</a>
      @foreach ($years as $y)
        <a href="{{ route('account.partner.earnings', ['year' => $y]) }}" @if ($year === $y) aria-current="page" @endif>{{ $y }}</a>
      @endforeach
    </nav>
  @endif

  @if ($total == 0)
    <section class="a-card">
      <x-az.empty title="No earnings yet{{ $year ? ' in ' . $year : '' }}"
        message="Profit share, interest, penalty share and broker fees show here, month by month, once our team records them on your account." />
    </section>
  @else
    <section class="a-hero">
      <p class="label">Earned {{ $year ? 'in ' . $year : 'all time' }}</p>
      <p class="big"><x-az.money :amount="$total" /></p>
      <p class="sub">{{ $by_month->count() }} {{ \Illuminate\Support\Str::plural('month', $by_month->count()) }} with earnings</p>
      <p class="sub">Earnings up to 7 Oct 2026; newer earnings are being calculated and will be added.</p>{{-- S-P2 interim until Phase 3 --}}
    </section>

    <div class="a-stats four">
      @foreach ($by_type as $row)
        <div class="a-stat"><span class="{{ $tone[$row['type']] }}">{{ $row['label'] }}</span><b><x-az.money :amount="$row['total']" /></b></div>
      @endforeach
    </div>

    <section class="a-card">
      <div class="a-sec-h"><h2>By month</h2><a href="{{ route('account.statement') }}">Statement</a></div>
      <div class="p-legend">
        @foreach ($by_type as $row)<span class="{{ $tone[$row['type']] }}">{{ $row['label'] }}</span>@endforeach
      </div>
      @foreach ($by_month as $m)
        <div class="p-month">
          <header><b>{{ $m['month']->format('F Y') }}</b><x-az.money :amount="$m['total']" /></header>
          <div class="p-split" role="img" aria-label="@foreach ($by_type as $row){{ $row['label'] }} KES {{ number_format($m['types'][$row['type']], 2) }}{{ $loop->last ? '' : ', ' }}@endforeach">
            @foreach ($m['types'] as $type => $amount)
              @if ($amount > 0 && $m['total'] > 0)<i class="{{ $tone[$type] }}" style="width: {{ round($amount / $m['total'] * 100, 2) }}%"></i>@endif
            @endforeach
          </div>
        </div>
      @endforeach
    </section>
  @endif
@endsection
