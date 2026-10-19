{{-- One loan the partner funded (or brokered). Borrower by initials / @handle only: no ID number, phone or name. --}}
@extends('home.layouts.account', ['title' => $progress['reference'], 'tab' => 'funded', 'area' => 'partner'])

@php
  $bal = $progress['balance'];
  $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('j M Y') : null;
  $sent = in_array($loan->status, ['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery', 'repaid', 'written_off'], true);
  $pct = $loan->status === 'repaid' ? 100 : $progress['progress'];
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.partner.loans') }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>Funded loans</a>
    <div class="acct-head">
      <h1><x-az.money :amount="$loan->amount" /></h1>
      <p>{{ $progress['reference'] }} · {{ $loan->loanType->name ?? 'Loan' }} · <x-az.status-badge :status="$loan->status" /></p>
    </div>
  </div>

  <div class="a-grid two">
    <div class="a-grid">
      @if ($sent && $bal)
        <section class="a-hero {{ $progress['is_late'] ? 'red' : '' }}">
          <div class="hero-grid">
            <div>
              <p class="label">{{ $loan->status === 'repaid' ? 'Fully repaid' : 'Still to be repaid' }}</p>
              <p class="big"><x-az.money :amount="$loan->status === 'repaid' ? $bal['paid'] : $bal['due']" /></p>
              <p class="sub">{{ $loan->status === 'repaid' ? 'Closed' : ($progress['due_date'] ? ($progress['is_late'] ? 'Overdue since ' : 'Due ') . $date($progress['due_date']) : '') }}</p>
            </div>
            <div class="ring" style="--p: {{ $pct }}"><span>{{ $pct }}%<small>repaid</small></span></div>
          </div>
        </section>
        <div class="a-stats">
          <div class="a-stat"><span>Total to repay</span><b><x-az.money :amount="$bal['total']" /></b></div>
          <div class="a-stat"><span>Repaid so far</span><b><x-az.money :amount="$bal['paid']" /></b></div>
        </div>
      @else
        <x-az.alert type="info" title="Not paid out">This loan has not been sent to the borrower, so there is nothing to repay yet.</x-az.alert>
      @endif

      <section class="a-card">
        <div class="a-sec-h"><h2>About this loan</h2></div>
        <dl class="a-dl">
          <div><dt>Borrower</dt><dd><span class="p-who" style="width:28px;height:28px;font-size:11px;vertical-align:middle">{{ $borrower['initials'] }}</span> {{ $borrower['handle'] ?? '' }}</dd></div>
          <div><dt>Applied</dt><dd>{{ $date($loan->created_at) }}</dd></div>
          @if ($disbursement)<div><dt>Paid out</dt><dd>{{ $date($disbursement->disburse_date) }}</dd></div>@endif
          <div><dt>Due</dt><dd>{{ $date($progress['due_date']) ?? '—' }}</dd></div>
          <div><dt>Repayment periods</dt><dd>{{ $progress['cycles']->count() }}</dd></div>
          <div><dt>How you are linked</dt><dd>{{ $investment ? 'Your investment' : '' }}{{ $investment && $brokered ? ' · ' : '' }}{{ $brokered ? 'You brought this loan in' : '' }}</dd></div>
        </dl>
        @if ($investment)
          <a class="btn btn-ghost btn-block" style="margin-top:14px" href="{{ route('account.partner.investment', $investment) }}">Open the investment</a>
        @endif
      </section>
    </div>

    <div class="a-grid">
      <section class="a-card">
        <div class="a-sec-h"><h2>Repayments</h2><span class="muted" style="font-size:13px">{{ $progress['repayments']->count() }}</span></div>
        @forelse ($progress['repayments'] as $payment)
          @php $kind = \App\Services\CustomerWallets::kind($payment->transaction); @endphp
          <div class="a-row">
            <span class="t"><b><x-az.money :amount="$payment->amount" /></b><span>{{ ['cash' => 'Payment', 'rollover' => 'Rolled over', 'discount' => 'Discount', 'bad_debt' => 'Written off', 'non_cash' => 'Book entry'][$kind] ?? 'Payment' }}</span></span>
            <span class="amt"><small>{{ $date($payment->repayment_date) }}</small></span>
          </div>
        @empty
          <p class="a-empty">No repayments recorded yet.</p>
        @endforelse
      </section>

      <section class="a-card">
        <div class="a-sec-h"><h2>What you earned on it</h2></div>
        @forelse ($entries as $entry)
          <div class="a-row">
            <span class="t"><b>{{ \App\Services\PartnerPortfolio::label($entry) }}</b><span>{{ \App\Services\PartnerPortfolio::WALLETS[$entry->wallet_slug] ?? $entry->wallet_slug }}</span></span>
            <span class="amt"><x-az.money :amount="$entry->amount" sign tone="auto" /><small>{{ $date($entry->transaction_date) }}</small></span>
          </div>
        @empty
          <p class="a-empty">Nothing booked to you from this loan yet. Shares of repayments are recorded by our team.</p>
        @endforelse
      </section>
    </div>
  </div>
@endsection
