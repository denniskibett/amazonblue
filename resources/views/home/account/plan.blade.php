@extends('home.layouts.account', ['title' => 'Your payment plan', 'tab' => 'loans'])

{{-- Payment plan (forbearance) shown from the loan's current cycle; "I accept" = audit row + staff email
     (Concerns\BorrowerJourneys::acceptPlan). Stage 4: proper consent storage. --}}
@php
  $loan = $progress['loan'];
  $bal = $progress['balance'];
  $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('j M Y') : '—';
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.loan', $loan) }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>{{ $progress['reference'] }}</a>
    <div class="acct-head">
      <h1>Your payment plan</h1>
      <p>{{ $progress['reference'] }} · <x-az.status-badge :status="$loan->status" :label="$loan->status === 'forbearance' ? 'Payment plan' : null" /></p>
    </div>
  </div>

  @if (! $plan)
    <x-az.empty title="No payment plan on this loan" message="Payment plans are agreed with our team when you can't pay on time.">
      <a class="btn btn-primary" href="{{ route('account.help', ['loan' => $loan->id]) }}">Talk to us</a>
    </x-az.empty>
  @else
    <div class="a-grid two">
      <section class="a-card">
        <div class="a-sec-h"><h2>The plan</h2></div>
        <dl class="a-break">
          <dt>Plan period</dt><dd>Period {{ $plan->cycle_number }}</dd>
          <dt>Starts</dt><dd>{{ $date($plan->start_date) }}</dd>
          <dt>Pay by</dt><dd>{{ $date($plan->due_date) }}</dd>
          <dt>Interest for the plan</dt><dd>{{ rtrim(rtrim(number_format((float) $plan->interest_rate, 2), '0'), '.') }}%</dd>
          @if ($bal)<dt>Paid so far</dt><dd>{{ $kes($bal['paid']) }}</dd>@endif
          <dt class="total">Amount to pay under the plan</dt><dd class="total">{{ $kes($bal['due'] ?? $plan->new_balance) }}</dd>
        </dl>
        @if ($loan->forbearance_until)
          <p class="muted" style="font-size:13px;margin-top:12px">Late penalties are paused until {{ $date($loan->forbearance_until) }} while you keep to the plan.</p>
        @endif
      </section>

      <section class="a-card">
        <div class="a-sec-h"><h2>Your agreement</h2></div>
        @if ($accepted)
          <x-az.alert type="ok" title="You accepted this plan on {{ $accepted->created_at->format('j M Y, H:i') }}">
            Thank you. Pay by {{ $date($plan->due_date) }} to keep the plan.
          </x-az.alert>
        @else
          <p>Please read the plan. By accepting you agree to pay {{ $kes($bal['due'] ?? $plan->new_balance) }} by {{ $date($plan->due_date) }}.</p>
          <ul class="bj-next">
            <li>If you miss the plan, the loan goes back to overdue and penalties apply again.</li>
            <li>You can pay early or in parts by M-Pesa at any time.</li>
          </ul>
          <div class="bj-actions">
            <x-az.confirm id="accept-plan" :action="route('account.plan.accept', $loan)" trigger="I accept this plan" trigger-class="btn btn-primary"
              title="Accept this payment plan?" confirm="Yes, I accept" cancel="Not yet">
              You agree to pay {{ $kes($bal['due'] ?? $plan->new_balance) }} by {{ $date($plan->due_date) }}. We record the date and time you accepted.
            </x-az.confirm>
            <a class="btn btn-ghost" href="{{ route('account.help', ['loan' => $loan->id]) }}">I have a question</a>
          </div>
        @endif
        <x-az.placeholder dependency="signed plan consent stored on the loan" style="margin-top:14px">
          Today your acceptance is recorded in our audit log and sent to our team.
        </x-az.placeholder>
      </section>
    </div>
  @endif
@endsection
