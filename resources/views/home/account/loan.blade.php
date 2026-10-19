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
      <p>{{ $progress['reference'] }} · {{ $loan->loanType->name ?? '' }}</p>
    </div>
  </div>

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
        <ol class="a-timeline" style="margin-top:10px">
          @foreach ($progress['timeline'] as $step)
            @php $red = ($step['tone'] ?? null) === 'red'; @endphp
            <li class="{{ $red ? 'red' : ($step['done'] ? 'done' : (($step['current'] ?? false) ? 'current' : '')) }}">
              <span class="dot">{!! $step['done'] && ! $red ? $tick : $loop->iteration !!}</span>
              <div>
                <b>{{ $step['label'] }}@if ($step['date']) <small>· {{ $date($step['date']) }}</small>@endif</b>
                <span>{{ $step['detail'] }}</span>
              </div>
            </li>
          @endforeach
        </ol>
      </section>
    </div>

    <div class="a-grid">
      <section class="a-card">
        <div class="a-sec-h"><h2>Payments</h2></div>
        @forelse ($progress['repayments'] as $payment)
          @php $kind = \App\Services\CustomerWallets::kind($payment->transaction); @endphp
          <div class="a-row">
            <span class="chip {{ $kind === 'cash' ? 'sage' : 'cloud' }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $kind === 'cash' ? 'M12 19V5M5 12l7-7 7 7' : 'M4 12h16M14 6l6 6-6 6' }}" /></svg></span>
            <span class="t"><b>{{ $kes($payment->amount) }}</b><span>{{ ['cash' => ($payment->mode ? ucfirst($payment->mode) : 'Payment'), 'rollover' => 'Rollover (moved to a new period)', 'discount' => 'Discount', 'bad_debt' => 'Written off'][$kind] }}{{ $kind === 'cash' && $payment->transaction ? ' · ' . $payment->transaction : '' }}</span></span>
            <span class="amt"><small>{{ $date($payment->repayment_date) }}</small></span>
          </div>
        @empty
          <p class="a-empty">No payments recorded yet. Pay by M-Pesa and our team records it here.</p>
        @endforelse
      </section>

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

      @if ($loan->consent)
        <a class="btn btn-ghost btn-block" href="{{ route('account.agreement', $loan) }}">View signed loan agreement</a>
      @endif
    </div>
  </div>
@endsection
