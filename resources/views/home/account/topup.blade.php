@extends('home.layouts.account', ['title' => 'Top up this loan', 'tab' => 'loans'])

{{-- Top-up (owner, 23 Oct 2026): more money on a loan repaid on time, as a new period on the same loan.
     The figures come from Loans\TopUp::plan, the same calculation staff book when they send the money. --}}
@php
  $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('j M Y') : '—';
  $kes = fn ($n) => 'KES ' . number_format((float) $n, 2);
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.loan', $loan) }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>{{ $progress['reference'] }}</a>
    <div class="acct-head">
      <h1>Top up this loan</h1>
      <p>{{ $progress['reference'] }} · due {{ $date($progress['due_date']) }}</p>
    </div>
  </div>

  @if ($asked)
    @if ($asked->status === 'approved')
      <x-az.alert type="ok" title="Top-up sent on {{ $date($asked->decided_at) }}">The new amount and due date are on your loan page. Repay on time to keep top-ups open to you.</x-az.alert>
    @elseif ($asked->status === 'declined')
      <x-az.alert type="warn" title="We couldn't agree your last top-up">{{ $asked->decision_note ?: 'Talk to us about the options.' }}</x-az.alert>
    @elseif ($asked->status === 'open')
      <x-az.alert type="info" title="You asked on {{ $date($asked->created_at) }}">Our team has your request. The money goes to your M-Pesa once it is agreed.</x-az.alert>
    @endif
  @endif

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>Can I top up?</h2>@if ($check['ok'])<span class="badge-s st-active">Yes</span>@else<span class="badge-s st-pending">Not yet</span>@endif</div>
      <dl class="a-break">
        <dt>Still owed today</dt><dd>{{ $kes($check['owed']) }}</dd>
        <dt>Paid on this period</dt><dd>{{ $kes($check['paid']) }} of {{ $kes($check['cycle_total']) }}</dd>
        <dt>Your limit</dt><dd>{{ $kes($check['limit']) }}</dd>
        <dt>Most you can add</dt><dd><b>{{ $check['ok'] ? $kes($check['max']) : '—' }}</b></dd>
      </dl>
      @unless ($check['ok'])
        <x-az.alert type="warn" title="Not available right now" style="margin-top:14px">
          @foreach ($check['why'] as $why)<span style="display:block">{{ $why }}</span>@endforeach
        </x-az.alert>
      @endunless
      <p class="muted" style="font-size:12.5px;margin-top:12px">Top-ups are for loans you are repaying on time: at least half of this period paid, not past the due date, and nothing unpaid in your history.</p>
    </section>

    <section class="a-card">
      <div class="a-sec-h"><h2>How much do you need?</h2></div>
      @if ($check['ok'])
        <form method="GET" action="{{ route('account.topup', $loan) }}">
          <x-az.field name="amount" label="Top-up amount (KES)" type="number" :value="$amount ?: null" min="{{ $check['min'] }}" max="{{ $check['max'] }}" step="500" required
            hint="Between {{ $kes($check['min']) }} and {{ $kes($check['max']) }}." />
          <button class="btn btn-ghost btn-block" type="submit" style="margin-top:10px">See the new terms</button>
        </form>
        @if ($error)<x-az.alert type="warn" style="margin-top:14px">{{ $error }}</x-az.alert>@endif
        @if ($preview)
          <dl class="a-break" style="margin-top:16px">
            <dt>Still owed</dt><dd>{{ $kes($preview['owed']) }}</dd>
            <dt>Top-up sent to your M-Pesa</dt><dd>{{ $kes($preview['amount']) }}</dd>
            <dt>New loan amount</dt><dd>{{ $kes($preview['principal']) }}</dd>
            <dt>Interest ({{ rtrim(rtrim(number_format($preview['interest_rate'], 2), '0'), '.') }}%)</dt><dd>{{ $kes($preview['interest']) }}</dd>
            <dt>You repay</dt><dd><b>{{ $kes($preview['new_balance']) }}</b></dd>
            <dt>By</dt><dd><b>{{ $date($preview['due_date']) }}</b></dd>
          </dl>
          <x-az.alert type="warn" title="Repay on time" style="margin-top:14px">
            The whole amount is due on {{ $date($preview['due_date']) }}. Paying late adds a penalty every day and closes top-ups to you. We'll remind you before the due date.
          </x-az.alert>
          <form method="POST" action="{{ route('account.topup.request', $loan) }}" style="margin-top:14px">
            @csrf
            <input type="hidden" name="amount" value="{{ $preview['amount'] }}">
            <label class="a-terms">
              <input type="checkbox" name="confirm" value="1" required />
              <span>I will repay {{ $kes($preview['new_balance']) }} by {{ $date($preview['due_date']) }}, and nothing changes until Aimzone sends the money.</span>
            </label>
            @error('confirm')<p class="error">{{ $message }}</p>@enderror
            @error('amount')<p class="error">{{ $message }}</p>@enderror
            <button class="btn btn-primary btn-block" type="submit" style="margin-top:14px">Ask for {{ $kes($preview['amount']) }}</button>
          </form>
        @endif
      @else
        <a class="btn btn-primary btn-block" href="{{ route('account.repay', $loan) }}">Repay this loan</a>
        <a class="btn btn-ghost btn-block" style="margin-top:10px" href="{{ route('account.help', ['loan' => $loan->id]) }}">Talk to us</a>
      @endif
    </section>
  </div>
@endsection
