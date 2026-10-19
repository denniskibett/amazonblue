{{-- Partner home. Rendered by HomeController@account (partner-only users, /account) and
     Concerns\PartnerSelfService@partnerHome (/account/partner, also for borrower + partner users). --}}
@extends('home.layouts.account', ['title' => 'My account', 'tab' => 'home', 'area' => 'partner'])

@php
  $first = strtok($user->name, ' ');
  $hour = (int) now()->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
  if ($portfolio && ! isset($home)) {
      $home = app(\App\Services\PartnerAccount::class)->home($portfolio['partner']);
  }
  $tile = fn ($d) => '<span class="chip"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $d . '" /></svg></span>';
@endphp

@section('content')
  <div class="acct-head">
    <h1>{{ $greeting }}, {{ $first }}</h1>
    <p>{{ $portfolio ? $portfolio['partner']->name . ($portfolio['partner']->handle ? ' · @' . $portfolio['partner']->handle : '') . ' · partner account' : 'Partner account' }}</p>
  </div>

  @if (! $portfolio)
    <section class="a-card">
      <x-az.empty title="Your partner account is being set up"
        message="Our team links your login to your partner record. Once that is done, your balances, investments, funded loans and statement show here.">
        <a class="btn btn-primary" href="https://wa.me/254701607959" target="_blank" rel="noopener">Message us on WhatsApp</a>
      </x-az.empty>
    </section>
  @else
    <section class="a-hero">
      <p class="label">Available balance</p>
      <p class="big"><x-az.money :amount="$portfolio['available']" /></p>
      <p class="sub"><x-az.money :amount="$portfolio['earned_this_month']" /> earned this month · <x-az.money :amount="$portfolio['earned']" /> all time</p>
      <div class="row">
        <a class="btn btn-light" href="{{ route('account.partner.funds') }}">Add funds</a>
        <a class="btn btn-glass" href="{{ route('account.partner.withdraw') }}">Withdraw</a>
      </div>
    </section>

    <div class="a-stats p-three">
      <a class="a-stat" href="{{ route('account.statement', ['wallet' => 'principal']) }}" style="text-decoration:none"><span>Capital (principal)</span><b><x-az.money :amount="$portfolio['wallets']['principal']['balance']" /></b></a>
      <a class="a-stat" href="{{ route('account.partner.earnings') }}" style="text-decoration:none"><span>Interest earned</span><b><x-az.money :amount="$home['interest_earned']" /></b></a>
      <a class="a-stat" href="{{ route('account.partner.earnings') }}" style="text-decoration:none"><span>Broker fees earned</span><b><x-az.money :amount="$home['broker_fees']" /></b></a>
    </div>

    <nav class="a-actions" aria-label="Partner shortcuts">
      <a class="a-action" href="{{ route('account.partner.investments') }}">{!! $tile('M4 20V10M10 20V4M16 20v-7M22 20H2') !!}Investments</a>
      <a class="a-action" href="{{ route('account.partner.loans') }}">{!! $tile('M12 8c4 0 7-1 7-2.5S16 3 12 3 5 4 5 5.5 8 8 12 8zM5 5.5v6C5 13 8 14 12 14s7-1 7-2.5v-6') !!}Funded loans</a>
      <a class="a-action" href="{{ route('account.partner.earnings') }}">{!! $tile('M4 19h16M7 15l4-4 3 3 5-6') !!}Earnings</a>
      <a class="a-action" href="{{ route('account.statement') }}">{!! $tile('M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6') !!}Statement</a>
    </nav>

    <div class="a-grid two">
      <section class="a-card">
        <div class="a-sec-h"><h2>Latest entries</h2><a href="{{ route('account.statement') }}">Full statement</a></div>
        @if ($portfolio['recent']->isEmpty())
          <x-az.empty title="No entries yet" message="Capital you add, earnings and withdrawals appear here once our team records them." />
        @else
          <div class="a-list">
            @foreach ($portfolio['recent'] as $entry)
              @php $out = \App\Services\PartnerAccount::entryIsOut($entry); @endphp
              <div class="a-row">
                <span class="chip {{ $out ? 'apricot' : 'sage' }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $out ? 'M12 5v14M5 12l7 7 7-7' : 'M12 19V5M5 12l7-7 7 7' }}" /></svg></span>
                <span class="t"><b>{{ \App\Services\PartnerPortfolio::label($entry) }}</b><span>{{ \App\Services\PartnerPortfolio::WALLETS[$entry->wallet_slug] ?? ucfirst(str_replace('_', ' ', $entry->wallet_slug)) }}{{ $entry->loan_id ? ' · loan #' . str_pad($entry->loan_id, 5, '0', STR_PAD_LEFT) : '' }}</span></span>
                <span class="amt"><x-az.money :amount="$out ? -abs($entry->amount) : $entry->amount" sign tone="auto" /><small>{{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('j M Y') }}</small></span>
              </div>
            @endforeach
          </div>
        @endif
      </section>

      <div class="a-grid">
        <section class="a-card">
          <div class="a-sec-h"><h2>How you earn</h2><a href="{{ route('account.partner.earnings') }}">By month</a></div>
          @forelse ($portfolio['breakdown'] as $row)
            @php $share = $portfolio['earned'] > 0 ? round($row['total'] / $portfolio['earned'] * 100) : 0; @endphp
            <div style="margin-top:12px">
              <div style="display:flex;justify-content:space-between;font-size:14px"><span>{{ $row['label'] }} <span class="muted">· {{ $row['entries'] }}</span></span><b><x-az.money :amount="$row['total']" /></b></div>
              <div class="a-progress" style="margin-top:6px"><i style="width: {{ $share }}%"></i></div>
            </div>
          @empty
            <p class="a-empty">Profit share, interest, penalty share and broker fees appear here once they are paid to you.</p>
          @endforelse
        </section>

        <section class="a-card">
          <div class="a-sec-h"><h2>Your funding</h2><a href="{{ route('account.partner.investments') }}">Investments</a></div>
          <dl class="a-dl">
            <div><dt>Capital added</dt><dd><x-az.money :amount="$home['principal_in']" /></dd></div>
            <div><dt>Committed to investments</dt><dd><x-az.money :amount="$portfolio['committed']" /></dd></div>
            <div><dt>Lent to borrowers</dt><dd><x-az.money :amount="$portfolio['disbursed']" /></dd></div>
            <div><dt>Returned</dt><dd><x-az.money :amount="$portfolio['returned']" /></dd></div>
            <div><dt>Loans open now</dt><dd>{{ $home['open_loans'] }} of {{ $home['loans'] }}</dd></div>
          </dl>
        </section>
      </div>
    </div>
  @endif
@endsection
