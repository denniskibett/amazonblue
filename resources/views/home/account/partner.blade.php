@extends('home.layouts.account', ['title' => 'My account', 'tab' => 'home'])

@php
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
  $first = strtok($user->name, ' ');
  $hour = (int) now()->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
  $outgoing = ['withdrawal'];
@endphp

@section('content')
  <div class="acct-head">
    <h1>{{ $greeting }}, {{ $first }}</h1>
    <p>{{ $portfolio ? $portfolio['partner']->name . ' · partner account' : 'Partner account' }}</p>
  </div>

  @if (! $portfolio)
    <div class="a-note info"><div><b>Your partner account is being set up</b>Our team will link your account shortly. Questions? Message us on WhatsApp.</div></div>
  @else
    <section class="a-hero">
      <p class="label">Balance across your wallets</p>
      <p class="big">{{ $kes($portfolio['available']) }}</p>
      <p class="sub">{{ $kes($portfolio['earned_this_month']) }} earned this month · {{ $kes($portfolio['earned']) }} all time</p>
      <div class="row">
        <a class="btn btn-light" href="{{ route('account.statement') }}">View statement</a>
        <a class="btn btn-glass" href="https://wa.me/254701607959" target="_blank" rel="noopener">Request a withdrawal</a>
      </div>
    </section>

    <div class="a-stats four">
      @foreach ($portfolio['wallets'] as $slug => $wallet)
        <a class="a-stat" href="{{ route('account.statement', ['wallet' => $slug]) }}" style="text-decoration:none"><span>{{ $wallet['label'] }}</span><b>{{ $kes($wallet['balance']) }}</b></a>
      @endforeach
      <div class="a-stat"><span>Profit share</span><b>{{ rtrim(rtrim(number_format((float) $portfolio['partner']->profit_share_rate, 2), '0'), '.') }}%</b></div>
    </div>

    <div class="a-grid two">
      <section class="a-card">
        <div class="a-sec-h"><h2>Latest entries</h2><a href="{{ route('account.statement') }}">Full statement</a></div>
        @if ($portfolio['recent']->isEmpty())
          <p class="a-empty">No entries yet.</p>
        @else
          <div class="a-list">
            @foreach ($portfolio['recent'] as $entry)
              @php $out = in_array($entry->type, $outgoing, true); @endphp
              <div class="a-row">
                <span class="chip {{ $out ? 'apricot' : 'sage' }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $out ? 'M12 5v14M5 12l7 7 7-7' : 'M12 19V5M5 12l7-7 7 7' }}" /></svg></span>
                <span class="t"><b>{{ \App\Services\PartnerPortfolio::label($entry) }}</b><span>{{ \App\Services\PartnerPortfolio::WALLETS[$entry->wallet_slug] ?? ucfirst(str_replace('_', ' ', $entry->wallet_slug)) }}{{ $entry->loan_id ? ' · loan #' . str_pad($entry->loan_id, 5, '0', STR_PAD_LEFT) : '' }}</span></span>
                <span class="amt {{ $out ? '' : 'in' }}">{{ $out ? '−' : '+' }}{{ $kes($entry->amount) }}<small>{{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('j M Y') }}</small></span>
              </div>
            @endforeach
          </div>
        @endif
      </section>

      <div class="a-grid">
        <section class="a-card">
          <div class="a-sec-h"><h2>How you earn</h2></div>
          @forelse ($portfolio['breakdown'] as $row)
            @php $share = $portfolio['earned'] > 0 ? round($row['total'] / $portfolio['earned'] * 100) : 0; @endphp
            <div style="margin-top:12px">
              <div style="display:flex;justify-content:space-between;font-size:14px"><span>{{ $row['label'] }} <span class="muted">· {{ $row['entries'] }}</span></span><b style="font-family:var(--num)">{{ $kes($row['total']) }}</b></div>
              <div class="a-progress" style="margin-top:6px"><i style="width: {{ $share }}%"></i></div>
            </div>
          @empty
            <p class="a-empty">Your earnings will appear here.</p>
          @endforelse
        </section>

        @if ($portfolio['committed'] > 0)
          <section class="a-card">
            <div class="a-sec-h"><h2>Funding</h2></div>
            <dl class="a-dl">
              <div><dt>Committed</dt><dd>{{ $kes($portfolio['committed']) }}</dd></div>
              <div><dt>Sent to borrowers</dt><dd>{{ $kes($portfolio['disbursed']) }}</dd></div>
              <div><dt>Returned</dt><dd>{{ $kes($portfolio['returned']) }}</dd></div>
            </dl>
          </section>
        @endif
      </div>
    </div>
  @endif
@endsection
