@extends('home.layouts.account', ['title' => 'Ask for more time', 'tab' => 'loans'])

{{-- Extension (rollover) request. Preview: LoanCalculator::getRolloverPreview, read-only and indicative.
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
    <x-az.alert type="info" title="You asked on {{ $asked->created_at->format('j M Y') }}">
      Our team has your request and will call you. You can send another if something has changed.
    </x-az.alert>
  @endif

  @unless ($canAsk)
    <x-az.alert type="warn" title="This loan can't be extended from here">
      Extensions are for loans that have been sent and are not in default. <a href="{{ route('account.help', ['loan' => $loan->id]) }}">Talk to us</a> and we'll look at the options.
    </x-az.alert>
  @endunless

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>What an extension costs</h2><span class="badge-s st-pending">Indicative</span></div>
      @if ($preview)
        <p class="muted" style="font-size:13.5px">If our team agrees, the amount still owed moves into a new repayment period and interest for that period is added.</p>
        <dl class="a-break" style="margin-top:12px">
          <dt>Owed today</dt><dd>KES {{ number_format((float) $preview['final_outstanding'], 2) }}</dd>
          <dt>Interest for the new period ({{ rtrim(rtrim(number_format((float) $preview['interest_rate'], 2), '0'), '.') }}%)</dt><dd>KES {{ number_format((float) $preview['interest_to_capitalize'], 2) }}</dd>
          <dt>New due date</dt><dd>{{ $date($preview['new_due_date_raw']) }}</dd>
          <dt class="total">New amount to repay</dt><dd class="total">KES {{ number_format((float) $preview['new_balance'], 2) }}</dd>
        </dl>
        <x-az.alert type="warn" title="This is an estimate" style="margin-top:14px">
          Extension pricing is being reworked. Our team confirms the exact cost and date with you before anything changes on your loan.
          @if (\Illuminate\Support\Carbon::parse($preview['new_due_date_raw'])->lte(today()))
            The date above counts from your original due date, so it is already close; the team will agree a workable date with you.
          @endif
        </x-az.alert>
      @else
        <x-az.placeholder dependency="rollover pricing rework" title="Cost preview not available for this loan">
          Our team will tell you the cost and new due date when they call.
        </x-az.placeholder>
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
        <x-az.placeholder dependency="requests table + approval in your account" style="margin-top:14px">
          For now, requests go to our team by email and they call you back. You'll be able to follow approval here later.
        </x-az.placeholder>
      @else
        <a class="btn btn-primary btn-block" href="{{ route('account.help', ['loan' => $loan->id]) }}">Contact us about this loan</a>
      @endif
    </section>
  </div>
@endsection
