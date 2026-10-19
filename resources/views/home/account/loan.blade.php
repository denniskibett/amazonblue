@extends('home.layouts.account', ['title' => $progress['reference'], 'tab' => 'loans'])

@php
  $loan = $progress['loan'];
  $bal = $progress['balance'];
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
  $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('j M Y') : null;
  $sent = in_array($loan->status, ['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery', 'repaid', 'written_off']);
  $tick = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>';
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.loans') }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>My loans</a>
    <div class="acct-head">
      <h1>{{ $kes($loan->amount) }}</h1>
      <p>{{ $progress['reference'] }} · {{ $loan->loanType->name ?? '' }} · <x-az.status-badge :status="$progress['is_late'] && in_array($loan->status, ['disbursed', 'active']) ? 'overdue' : $loan->status" :label="$loan->status === 'forbearance' ? 'Payment plan' : null" /></p>
    </div>
  </div>

  {{-- what this state means and what to do next (borrower journeys, App\Support\BorrowerLoanState) --}}
  @include('home.account.partials.loan-state', ['loan' => $loan, 'progress' => $progress])

  <div class="a-grid two">
    <div class="a-grid">
      @if ($sent && $bal)
        <section class="a-hero {{ $progress['is_late'] ? 'red' : '' }}">
          <div class="hero-grid">
            <div>
              <p class="label">{{ $loan->status === 'repaid' ? 'Fully repaid' : 'Amount due' }}</p>
              <p class="big">{{ $kes($loan->status === 'repaid' ? $bal['paid'] : $bal['due']) }}</p>
              <p class="sub">{{ $loan->status === 'repaid' ? 'Thank you!' : ($progress['due_date'] ? ($progress['is_late'] ? 'Overdue since ' : 'Due ') . $date($progress['due_date']) : '') }}</p>
            </div>
            <div class="ring" style="--p: {{ $loan->status === 'repaid' ? 100 : $progress['progress'] }}"><span>{{ $loan->status === 'repaid' ? 100 : $progress['progress'] }}%<small>repaid</small></span></div>
          </div>
          {{-- repay journey (Concerns\BorrowerRepayment) --}}
          @if (in_array($loan->status, ['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery']))
            <div class="row"><a class="btn btn-light" href="{{ route('account.repay', $loan) }}">Repay</a></div>
          @elseif ($loan->status === 'repaid')
            <div class="row"><a class="btn btn-glass" href="{{ route('account.closed', $loan) }}">Loan closed · summary</a></div>
          @endif
        </section>
        <div class="a-stats">
          <div class="a-stat"><span>Total to repay</span><b>{{ $kes($bal['total']) }}</b></div>
          <div class="a-stat"><span>Paid so far</span><b>{{ $kes($bal['paid']) }}</b></div>
          @if ($bal['penalty'] > 0)<div class="a-stat"><span>Late penalty</span><b class="red">{{ $kes($bal['penalty']) }}</b></div>@endif
          <div class="a-stat"><span>Due date</span><b>{{ $date($progress['due_date']) ?? '—' }}</b></div>
        </div>
      @endif

      <section class="a-card">
        <div class="a-sec-h"><h2>Progress</h2></div>
        <x-az.timeline :items="$progress['timeline']" style="margin-top:10px" />
      </section>
    </div>

    <div class="a-grid">
      <section class="a-card">
        <div class="a-sec-h"><h2>Payments</h2></div>
        @include('home.account.repay._payments', ['repayments' => $progress['repayments'], 'loan' => $loan])
      </section>

      @include('home.account.repay._claims', ['loan' => $loan])

      @if ($progress['cycles']->count() > 1)
        <section class="a-card">
          <div class="a-sec-h"><h2>Repayment periods</h2></div>
          <dl class="a-dl">
            @foreach ($progress['cycles'] as $cycle)
              <div><dt>Period {{ $cycle->cycle_number }}</dt><dd>{{ $kes($cycle->new_balance) }} · due {{ $date($cycle->due_date) }}</dd></div>
            @endforeach
          </dl>
        </section>
      @endif

      @include('home.account.partials.agreement-summary', ['loan' => $loan])

      @if ($sent)
        <a class="btn btn-ghost btn-block" href="{{ route('account.loan.statement', $loan) }}">Statement for this loan</a>
      @endif
    </div>
  </div>
@endsection
