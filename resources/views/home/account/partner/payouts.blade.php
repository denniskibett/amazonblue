{{-- Partner payouts (owner, 4 Nov 2026): every distribution paid with its reference, what is being paid, and how
     the latest one was built from a borrower's repayment. Figures: App\Services\Partners\PartnerReturns. --}}
@extends('home.layouts.account', ['title' => 'Payouts', 'tab' => 'payouts', 'area' => 'partner'])

@php $kes = fn ($v) => 'KES ' . number_format((float) $v, 2); @endphp

@section('content')
  <div class="acct-head">
    <h1>Payouts</h1>
    <p>Every shilling paid to you, how it was worked out, and what is coming next.</p>
  </div>

  <div class="a-stats">
    <div class="a-stat"><span>Paid to you, all time</span><b>{{ $kes($d['paid_total']) }}</b></div>
    <div class="a-stat"><span>Distributions</span><b>{{ $d['count'] }}</b></div>
    <div class="a-stat"><span>Being paid now</span><b>{{ $kes($d['next']) }}</b></div>
    <div class="a-stat"><span>Accrued, not asked for yet</span><b>{{ $kes($returns['accrued']) }}</b></div>
  </div>

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>Distributions</h2><a href="{{ route('account.statement') }}">Statement and CSV</a></div>
      @if ($d['requests']->isNotEmpty())
        <div class="a-list">
          @foreach ($d['requests'] as $r)
            <div class="a-row"><span class="t"><b>{{ \App\Services\PartnerPortfolio::WALLETS[$r->wallet_slug] ?? 'Payout' }}</b><span>{{ $r->status === 'prepared' ? 'Prepared, waiting for a second person' : 'Asked ' . \Illuminate\Support\Carbon::parse($r->created_at)->format('j M Y') }}</span></span>
              <span class="amt">{{ $kes($r->amount) }}<small><span class="chip sand" style="font-size:11px">Being paid</span></small></span></div>
          @endforeach
        </div>
      @endif
      @forelse ($d['paid'] as $p)
        <div class="a-row"><span class="t"><b>{{ \App\Services\PartnerPortfolio::WALLETS[$p->wallet_slug] ?? ucfirst($p->wallet_slug) }}</b><span>{{ $p->bank_reference ? 'Ref. ' . $p->bank_reference : ($p->reference ?: 'no reference') }}</span></span>
          <span class="amt">{{ $kes(abs($p->amount)) }}<small>{{ \Illuminate\Support\Carbon::parse($p->transaction_date)->format('j M Y') }}</small></span></div>
      @empty
        <p class="a-empty">Nothing has been paid out to you yet. When we pay you, it shows here with the date and the bank or M-Pesa reference.</p>
      @endforelse
    </section>

    <section class="a-card">
      <div class="a-sec-h"><h2>How a distribution is built</h2></div>
      @if ($d['built'])
        @php $b = $d['built']; @endphp
        <p class="muted" style="font-size:14px;margin:0 0 8px">Loan #{{ str_pad($b['loan_id'], 5, '0', STR_PAD_LEFT) }} · repayment of {{ \Illuminate\Support\Carbon::parse($b['date'])->format('j M Y') }}</p>
        <dl class="a-dl" style="--dd-nowrap:1">
          <div><dt>Borrower paid (interest and penalty)</dt><dd style="white-space:nowrap">{{ $kes($b['collected']) }}</dd></div>
          <div><dt>Our share (we find, serve and collect)</dt><dd style="white-space:nowrap">− {{ $kes($b['house']) }}</dd></div>
          @if ($b['others'] > 0)<div><dt>Other funders of this loan</dt><dd style="white-space:nowrap">− {{ $kes($b['others']) }}</dd></div>@endif
          <div><dt>Your share of the interest</dt><dd>{{ $kes($b['interest']) }}</dd></div>
          @if ($b['penalty'] > 0)<div><dt>Your share of the penalty</dt><dd>{{ $kes($b['penalty']) }}</dd></div>@endif
          @if ($b['fee'] > 0)<div><dt>Introducer fee</dt><dd>{{ $kes($b['fee']) }}</dd></div>@endif
          <div><dt><b>To you</b></dt><dd><b>{{ $kes($b['interest'] + $b['penalty'] + $b['fee']) }}</b></dd></div>
        </dl>
        <p class="muted" style="font-size:12.5px;margin:10px 0 0">The principal goes back to your tranche and is lent again or paid out when the tranche closes.</p>
      @else
        <p class="a-empty">Once a loan you fund is repaid, this shows how the interest was shared between you and us.</p>
      @endif
      <div style="display:flex;gap:8px;margin-top:14px">
        <a class="btn btn-primary" href="{{ route('account.partner.withdraw') }}">Ask for a payout</a>
        <a class="btn btn-outline" href="{{ route('account.partner.home') }}">Portfolio</a>
      </div>
    </section>
  </div>
@endsection
