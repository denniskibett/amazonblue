@extends('home.layouts.account', ['title' => 'Ask for more time', 'tab' => 'loans'])

{{-- Extension (rollover) request. Preview: LoanCalculator::getRolloverPreview, read-only: the same figures a
     rollover booked today would use (owner, 1 Nov 2026: real amounts, not an example).
     Sending = audit row + staff email (Concerns\BorrowerJourneys::requestExtension). --}}
@php
  $loan = $progress['loan'];
  $bal = $progress['balance'];
  $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('j M Y') : '—';
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.loan', $loan) }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>{{ $progress['reference'] }}</a>
    <div class="acct-head">
      <h1>Ask for more time</h1>
      <p>{{ $progress['reference'] }} · due {{ $date($progress['due_date']) }}</p>
    </div>
  </div>

  @if ($asked)
    @php $st = $asked->status ?? 'open'; @endphp
    @if ($st === 'approved')
      <x-az.alert type="ok" title="Extension agreed on {{ \Illuminate\Support\Carbon::parse($asked->decided_at)->format('j M Y') }}">
        Your loan was extended. The new amount and due date are on your loan page.@if (! empty($asked->decision_note)) {{ $asked->decision_note }}@endif
      </x-az.alert>
    @elseif ($st === 'declined')
      <x-az.alert type="warn" title="We couldn't agree this extension">
        {{ $asked->decision_note ?: 'Talk to us about the other options.' }}
      </x-az.alert>
    @else
      <x-az.alert type="info" title="You asked on {{ $asked->created_at->format('j M Y') }}">
        Our team has your request and will call you. You can send another if something has changed.
      </x-az.alert>
    @endif
  @endif

  @unless ($canAsk)
    <x-az.alert type="warn" title="This loan can't be extended from here">
      Extensions are for loans that have been sent and are not in default. <a href="{{ route('account.help', ['loan' => $loan->id]) }}">Talk to us</a> and we'll look at the options.
    </x-az.alert>
  @endunless

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>What an extension costs</h2><span class="badge-s st-active">As of today</span></div>
      @if ($preview)
        @php
          $kes = fn ($n) => 'KES ' . number_format((float) $n, 2);
          $b = $preview['cycle_calculation'] ?? [];
          $rate = rtrim(rtrim(number_format((float) $preview['interest_rate'], 2), '0'), '.');
          $carried = $preview['new_principal'] ?? $preview['final_outstanding'];
        @endphp
        <p class="muted" style="font-size:13.5px">If our team agrees today, what you still owe on this loan moves into a new period and that period's interest is added.</p>
        <dl class="a-break" style="margin-top:12px">
          @if (isset($b['unpaid_principal']))
            <dt>Principal still owed</dt><dd>{{ $kes($b['unpaid_principal']) }}</dd>
            <dt>Interest still owed</dt><dd>{{ $kes($b['unpaid_interest']) }}</dd>
            @if ((float) ($b['unpaid_penalty'] ?? 0) > 0)
              <dt>Late charges</dt><dd>{{ $kes($b['unpaid_penalty']) }}</dd>
            @endif
          @endif
          <dt><b>Carried into the new period</b></dt><dd><b>{{ $kes($carried) }}</b></dd>
          <dt>Interest for the new period ({{ $rate }}%)</dt><dd>{{ $kes($preview['interest_to_capitalize']) }}</dd>
          <dt><b>New amount to repay</b></dt><dd><b>{{ $kes($preview['new_balance']) }}</b></dd>
          <dt>New due date</dt><dd>{{ $preview['new_due_date'] }}</dd>
        </dl>
        <p class="muted" style="font-size:12.5px;margin-top:12px">These are today's figures for this loan. They go down if you pay part of it first, and late charges add up each day it stays unpaid. Nothing changes until our team confirms with you.</p>
      @else
        <x-az.alert type="info" title="We'll tell you the cost when we call">
          Our team works out the amount and the new due date with you.
        </x-az.alert>
      @endif
      <p class="muted" style="font-size:12.5px;margin-top:12px">Extending costs more than paying on time and does not count as an on-time repayment for your limit.</p>
    </section>

    <section class="a-card">
      <div class="a-sec-h"><h2>Send a request</h2></div>
      @if ($canAsk)
        <form method="POST" action="{{ route('account.extension.request', $loan) }}">
          @csrf
          <x-az.field name="reason" label="Why do you need more time?" type="textarea" required maxlength="500" rows="4"
            placeholder="For example: salary is paid on the 28th" hint="Up to 500 characters. Our team reads every request." />
          <label class="a-terms" style="margin-top:12px">
            <input type="checkbox" name="confirm" value="1" required />
            <span>I understand an extension adds interest, and that nothing changes until Aimzone confirms it with me.</span>
          </label>
          @error('confirm')<p class="error">{{ $message }}</p>@enderror
          <button class="btn btn-primary btn-block" type="submit" style="margin-top:14px">Request extension</button>
        </form>
        @if (\App\Services\Requests::enabled())
          <p class="muted" style="font-size:13px;margin-top:14px">Your request shows on this loan with its status. When our team decides, you get a message and the new terms appear here.</p>
        @else
          <p class="muted" style="font-size:13px;margin-top:14px">Your request goes to our team, who call you back to agree the terms.</p>
        @endif
      @else
        <a class="btn btn-primary btn-block" href="{{ route('account.help', ['loan' => $loan->id]) }}">Contact us about this loan</a>
      @endif
    </section>
  </div>
@endsection
