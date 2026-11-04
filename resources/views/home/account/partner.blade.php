{{-- Partner home: the portfolio in investor terms (owner, 4 Nov 2026). Rendered by HomeController@account
     (partner-only users, /account) and Concerns\PartnerSelfService@partnerHome (/account/partner, also for
     borrower + partner users). Figures: App\Services\Partners\PartnerReturns. --}}
@extends('home.layouts.account', ['title' => 'Portfolio', 'tab' => 'home', 'area' => 'partner'])

@php
  $first = strtok($user->name, ' ');
  $hour = (int) now()->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
  if ($portfolio && ! isset($home)) {
      $home = app(\App\Services\PartnerAccount::class)->home($portfolio['partner']);
  }
  if ($portfolio && ! isset($returns)) {
      $svc = app(\App\Services\Partners\PartnerReturns::class);
      $returns = $svc->summary($portfolio['partner']);
      $navChart = $svc->navByMonth($portfolio['partner']);
      $tranches = $svc->tranches($portfolio['partner']);
      $treasury = $returns['corporate'] ? $svc->treasury($portfolio['partner'], app(\App\Services\PartnerAccount::class)) : null;
  }
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 0);
  $tile = fn ($d) => '<span class="chip"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $d . '" /></svg></span>';
  // NAV chart points (0..100 across, 0..100 down)
  $pts = function ($key) use ($navChart) {
      $max = max(1, max(array_column($navChart, 'nav')), max(array_column($navChart, 'capital')));
      $n = max(1, count($navChart) - 1);

      return collect($navChart)->values()->map(fn ($r, $i) => round($i / $n * 100, 2) . ',' . round(100 - $r[$key] / $max * 92, 2))->implode(' ');
  };
@endphp

@section('content')
  <div class="acct-head">
    <h1>{{ $greeting }}, {{ $first }}</h1>
    <p>{{ $portfolio ? $portfolio['partner']->name . ' · ' . ($returns['corporate'] ?? false ? 'institutional partner' : 'partner') . ($returns['since'] ?? null ? ' since ' . \Illuminate\Support\Carbon::parse($returns['since'])->format('M Y') : '') : 'Partner account' }}</p>
  </div>

  @if (! $portfolio)
    <section class="a-card">
      <x-az.empty title="Your partner account is being set up"
        message="Our team links your login to your partner record. Once that is done, your portfolio, tranches, payouts and statement show here.">
        <a class="btn btn-primary" href="https://wa.me/254701607959" target="_blank" rel="noopener">Message us on WhatsApp</a>
      </x-az.empty>
    </section>
  @else
    <section class="a-hero">
      <p class="label">Net asset value</p>
      <p class="big"><x-az.money :amount="$returns['nav']" /></p>
      <p class="sub">{{ $returns['gain'] >= 0 ? '▲' : '▼' }} {{ $kes(abs($returns['gain'])) }} since your first deposit @if ($returns['moic']) · {{ number_format($returns['moic'], 2) }}× MOIC @endif</p>
      <div class="a-stats p-three" style="margin-top:14px">
        <div class="a-stat" style="background:rgba(255,255,255,.08);color:#fff"><span style="color:rgba(255,255,255,.75)">{{ $returns['net_yield'] !== null ? 'Net yield (a year)' : 'Return to date' }}</span><b style="color:#fff">{{ $returns['net_yield'] ?? $returns['return_to_date'] ?? 0 }}%</b></div>
        <div class="a-stat" style="background:rgba(255,255,255,.08);color:#fff"><span style="color:rgba(255,255,255,.75)">Run-rate a month</span><b style="color:#fff">{{ $kes($returns['run_rate']) }}</b></div>
        <div class="a-stat" style="background:rgba(255,255,255,.08);color:#fff"><span style="color:rgba(255,255,255,.75)">Utilisation</span><b style="color:#fff">{{ $returns['utilisation'] !== null ? $returns['utilisation'] . '%' : '—' }}</b></div>
      </div>
      @if (count($navChart) > 1 && max(array_column($navChart, 'nav')) > 0)
        <svg viewBox="0 0 100 100" preserveAspectRatio="none" style="width:100%;height:110px;margin-top:14px" aria-label="Net asset value and capital put in, by month">
          <polyline points="{{ $pts('capital') }}" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="1" stroke-dasharray="2 2" vector-effect="non-scaling-stroke" />
          <polyline points="{{ $pts('nav') }}" fill="none" stroke="#fff" stroke-width="2.5" vector-effect="non-scaling-stroke" />
        </svg>
        <div style="display:flex;justify-content:space-between;font-size:12px;color:rgba(255,255,255,.7)">@foreach ($navChart as $r)<span>{{ $r['m'] }}</span>@endforeach</div>
        <p class="sub" style="margin-top:6px">Solid line: net asset value · dashed: capital you put in</p>
      @endif
      <div class="row">
        @if (\App\Services\Partners\Subscription::enabled())
          <a class="btn btn-light" href="{{ route('partner.subscribe') }}">Deploy more capital</a>
        @else
          <a class="btn btn-light" href="{{ route('account.partner.funds') }}">Deploy more capital</a>
        @endif
        <a class="btn btn-glass" href="{{ route('account.partner.withdraw') }}">Withdraw</a>
      </div>
    </section>

    <div class="a-stats">
      <a class="a-stat" href="{{ route('account.partner.investments') }}" style="text-decoration:none"><span>Deployed capital</span><b>{{ $kes($returns['deployed']) }}</b></a>
      <a class="a-stat" href="{{ route('account.partner.investments') }}" style="text-decoration:none"><span>Dry powder (not lent yet)</span><b>{{ $kes($returns['dry_powder']) }}</b></a>
      <a class="a-stat" href="{{ route('account.partner.payouts') }}" style="text-decoration:none"><span>Realised (paid to you)</span><b>{{ $kes($returns['paid_out']) }}</b></a>
      <a class="a-stat" href="{{ route('account.partner.payouts') }}" style="text-decoration:none"><span>Accrued, not paid yet</span><b>{{ $kes($returns['accrued']) }}</b></a>
    </div>

    {{-- owner, 1 Nov 2026: partners asked what is to be paid out and what was paid out --}}
    <div class="a-grid two">
      <section class="a-card" id="to-be-paid">
        <div class="a-sec-h"><h2>To be paid out</h2><a href="{{ route('account.partner.withdraw') }}">Withdraw</a></div>
        <dl class="a-dl">
          @foreach ($home['withdrawable'] as $slug => $w)
            <div><dt>{{ $w['label'] }}</dt><dd><x-az.money :amount="$w['balance']" /></dd></div>
          @endforeach
          <div><dt><b>Total you can withdraw now</b></dt><dd><b><x-az.money :amount="collect($home['withdrawable'])->sum('balance')" /></b></dd></div>
        </dl>
        @if ($home['withdrawable']['principal']['lent_out'] > 0)
          <p class="muted" style="font-size:12.5px;margin:8px 0 0">Not counting <x-az.money :amount="$home['withdrawable']['principal']['lent_out']" /> of your capital lent out to borrowers; it comes back as they repay.</p>
        @endif
        @if ($home['requested']->isNotEmpty())
          <p style="font-size:14px;margin:12px 0 4px"><b>Requested, being paid</b></p>
          <div class="a-list">
            @foreach ($home['requested'] as $r)
              <div class="a-row"><span class="t"><b>{{ \App\Services\PartnerPortfolio::WALLETS[$r->wallet_slug] ?? 'Withdrawal' }}</b><span>Asked {{ \Illuminate\Support\Carbon::parse($r->created_at)->format('j M Y') }}</span></span><span class="amt"><x-az.money :amount="$r->amount" /></span></div>
            @endforeach
          </div>
        @endif
      </section>

      <section class="a-card" id="paid-out">
        <div class="a-sec-h"><h2>Paid out</h2><a href="{{ route('account.partner.payouts') }}">All payouts</a></div>
        <p class="muted" style="font-size:14px;margin:0 0 8px">Total paid to you: <b><x-az.money :amount="$home['paid_out_total']" /></b></p>
        @if ($home['paid_out']->isEmpty())
          <p class="a-empty">Nothing has been paid out to you yet. When we pay you, it shows here with the date and amount.</p>
        @else
          <div class="a-list">
            @foreach ($home['paid_out'] as $entry)
              <div class="a-row">
                <span class="t"><b>{{ \App\Services\PartnerPortfolio::label($entry) }}</b><span>{{ \App\Services\PartnerPortfolio::WALLETS[$entry->wallet_slug] ?? ucfirst(str_replace('_', ' ', $entry->wallet_slug)) }}{{ $entry->reference ? ' · ' . $entry->reference : '' }}</span></span>
                <span class="amt"><x-az.money :amount="abs((float) $entry->amount)" /><small>{{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('j M Y') }}</small></span>
              </div>
            @endforeach
          </div>
        @endif
      </section>
    </div>

    <div class="a-grid two">
      <section class="a-card">
        <div class="a-sec-h"><h2>Where your money is</h2><a href="{{ route('account.partner.investments') }}">All tranches</a></div>
        @forelse ($tranches as $t)
          <div class="a-row">
            <span class="t"><b>{{ \Illuminate\Support\Str::after($t['name'], ' · ') ?: $t['name'] }}</b><span>{{ $t['book'] }} · {{ $t['yield'] }}{{ $t['matures'] ? ' · matures ' . \Illuminate\Support\Carbon::parse($t['matures'])->format('j M Y') : '' }}</span></span>
            <span class="amt">{{ $kes($t['committed']) }}<small>{{ $t['open'] ? $t['open'] . ' loan' . ($t['open'] > 1 ? 's' : '') . ' out · ' . $kes($t['out']) : ($t['status'] === 'matured' ? 'closed · earned ' . $kes($t['earned']) : 'earned ' . $kes($t['earned'])) }}</small></span>
          </div>
        @empty
          <p class="a-empty">Your capital hasn't been recorded as a tranche yet. Each deposit becomes a tranche that funds loans and pays you its share.</p>
        @endforelse
      </section>

      <section class="a-card">
        <div class="a-sec-h"><h2>Compound it</h2><a href="{{ route('account.partner.payouts') }}">Payouts</a></div>
        @php
          $next = $returns['accrued'];
          $y = ($returns['net_yield'] ?? 0) / 100;
        @endphp
        <p class="muted" style="font-size:14px;margin:0 0 8px">Leave your next distribution in instead of cashing it out, and it starts earning too.</p>
        <dl class="a-dl">
          <div><dt>Next distribution</dt><dd><x-az.money :amount="$next" /></dd></div>
          @if ($y > 0 && $next > 0)
            <div><dt>Cash out: worth in 12 months</dt><dd>{{ $kes($next) }}</dd></div>
            <div><dt>Leave it in: worth in 12 months*</dt><dd>{{ $kes($next * (1 + $y)) }}</dd></div>
          @endif
        </dl>
        <p class="muted" style="font-size:12px;margin:8px 0 0">*At your net yield so far; illustrative, not a promise. Loans can pay late or not at all.</p>
        <div class="row" style="display:flex;gap:8px;margin-top:12px">
          <a class="btn btn-primary" href="https://wa.me/254701607959?text={{ rawurlencode('Please keep my next distribution invested.') }}" target="_blank" rel="noopener">Keep it invested</a>
          <a class="btn btn-outline" href="{{ route('account.partner.withdraw') }}">Cash out</a>
        </div>
      </section>
    </div>

    @if ($treasury)
      {{-- institutional partners (owner, 4 Nov 2026): the book behind their money and its credit risk --}}
      <section class="a-card">
        <div class="a-sec-h"><h2>Treasury view</h2><a href="{{ route('account.statement') }}">Statements</a></div>
        <div class="a-stats">
          <div class="a-stat"><span>Loans funded</span><b>{{ $treasury['loans'] }}</b></div>
          <div class="a-stat"><span>Open now</span><b>{{ $treasury['open'] }}</b></div>
          <div class="a-stat"><span>PAR 30 (open loans)</span><b>{{ $treasury['par30'] }}%</b></div>
          <div class="a-stat"><span>Written off</span><b>{{ $treasury['written_off'] }}</b></div>
        </div>
      </section>
    @endif

    <nav class="a-actions" aria-label="Partner shortcuts">
      <a class="a-action" href="{{ route('account.partner.investments') }}">{!! $tile('M4 20V10M10 20V4M16 20v-7M22 20H2') !!}Tranches</a>
      <a class="a-action" href="{{ route('account.partner.loans') }}">{!! $tile('M12 8c4 0 7-1 7-2.5S16 3 12 3 5 4 5 5.5 8 8 12 8zM5 5.5v6C5 13 8 14 12 14s7-1 7-2.5v-6') !!}Loans funded</a>
      <a class="a-action" href="{{ route('account.partner.payouts') }}">{!! $tile('M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6') !!}Payouts</a>
      <a class="a-action" href="{{ route('account.partner.earnings') }}">{!! $tile('M4 19h16M7 15l4-4 3 3 5-6') !!}Earnings</a>
    </nav>

    <div class="a-grid two">
      <section class="a-card">
        <div class="a-sec-h"><h2>Latest entries</h2><a href="{{ route('account.statement') }}">Full statement</a></div>
        @if ($portfolio['recent']->isEmpty())
          <x-az.empty title="No entries yet" message="Capital you add, earnings and payouts appear here once our team records them." />
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

      <section class="a-card">
        <div class="a-sec-h"><h2>How you earn</h2><a href="{{ route('account.partner.earnings') }}">By month</a></div>
        @forelse ($portfolio['breakdown'] as $row)
          @php $share = $portfolio['earned'] > 0 ? round($row['total'] / $portfolio['earned'] * 100) : 0; @endphp
          <div style="margin-top:12px">
            <div style="display:flex;justify-content:space-between;font-size:14px"><span>{{ $row['label'] }} <span class="muted">· {{ $row['entries'] }}</span></span><b><x-az.money :amount="$row['total']" /></b></div>
            <div class="a-progress" style="margin-top:6px"><i style="width: {{ $share }}%"></i></div>
          </div>
        @empty
          <p class="a-empty">Your share of interest, penalty share and introducer fees appear here as borrowers repay.</p>
        @endforelse
        <dl class="a-dl" style="margin-top:14px">
          <div><dt>Capital put in</dt><dd>{{ $kes($returns['capital_in']) }}</dd></div>
          <div><dt>Earned, all time</dt><dd>{{ $kes($returns['earned']) }}</dd></div>
          <div><dt>Funded loans open now</dt><dd>{{ $home['open_loans'] }} of {{ $home['loans'] }}</dd></div>
          @if ($home['brokered_loans'] > 0)<div><dt>Clients you introduced</dt><dd><a href="{{ route('account.partner.loans', ['list' => 'brokered']) }}">{{ $home['brokered_loans'] }}</a></dd></div>@endif
          <div><dt>Pending loans (not sent yet)</dt><dd><a href="{{ route('account.partner.loans', ['list' => 'pending']) }}">{{ $home['pending_loans'] }}</a></dd></div>
        </dl>
        @if ($home['investments'] === 0)
          <p class="muted" style="font-size:12.5px;margin:8px 0 0">Your capital hasn't been recorded as an investment yet — <a href="https://wa.me/254701607959" target="_blank" rel="noopener">contact us</a>.</p>
        @endif
      </section>
    </div>
  @endif
@endsection
