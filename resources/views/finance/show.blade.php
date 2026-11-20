@extends('finance.layout', ['title' => $fa->reference])

@php
  $money = fn ($v) => $fa->currency() . ' ' . number_format((float) $v, 0);
  $steps = config('finance_journey.steps');
  $after = [
    'submitted' => ['We received it', $fa->submitted_at?->format('j M, g:ia')],
    'in_review' => ['Our credit team reviews it', 'We may call you or the directors'],
    'decision' => ['Decision', 'An approval in principle, a request for more, or a no with the reason'],
    'agreement' => ['Final terms and agreement', 'Security, price and a signed agreement'],
    'repay' => ['Repayments', 'Collected through Sharet Pay (M-Pesa or bank)'],
  ];
  $order = ['submitted' => 1, 'in_review' => 2, 'more_info' => 2, 'approved' => 3, 'declined' => 3];
  $at = $order[$fa->status] ?? 0;
@endphp

@section('content')
  <div class="kyc-head">
    <h1>{{ $fa->isEditable() ? ($fa->status === 'more_info' ? 'We need something from you' : 'Your application') : $fa->statusLabel() }}</h1>
    <p class="muted">{{ $fa->productConfig('label') }} for {{ $fa->businessName() }} · started {{ $fa->created_at->format('j M Y') }}</p>
  </div>

  @if ($fa->staff_note && in_array($fa->status, ['more_info', 'declined'], true))
    <div class="kyc-note {{ $fa->status === 'declined' ? 'error' : 'warn' }}" role="status"><div><b>{{ $fa->status === 'declined' ? 'Why' : 'What we need' }}</b><span>{{ $fa->staff_note }}</span></div></div>
  @endif
  @if ($fa->status === 'approved')
    <div class="kyc-note ok" role="status"><div><b>Approved in principle</b><span>This is not yet an offer. The final amount, price and security depend on the checks still to do and a signed agreement. We will call you.</span></div></div>
  @endif

  @if ($fa->isEditable())
    <div class="kyc-card">
      <span class="flabel">Your progress</span>
      <ol class="fj-time">
        @foreach ($steps as $k => $label)
          <li><span class="dot {{ $fa->stepDone($k) ? 'ok' : ($k === $fa->nextStep() ? 'now' : '') }}">{!! $fa->stepDone($k) ? '&#10003;' : $loop->iteration !!}</span>
            <span><a href="{{ route('finance.step', [$token, $k]) }}">{{ $label }}</a><br><span class="fj-small">{{ $fa->stepDone($k) ? 'Saved' : ($k === 'documents' && $missing ? count($missing) . ' required still to add' : 'To do') }}</span></span></li>
        @endforeach
      </ol>
    </div>
    <div class="kyc-nav"><span></span><a class="btn btn-primary" href="{{ route('finance.step', [$token, $fa->nextStep()]) }}">{{ $fa->steps_done ? 'Carry on' : 'Start' }}</a></div>
  @else
    <div class="kyc-card">
      <span class="flabel">What happens now</span>
      <ol class="fj-time">
        @foreach ($after as $k => [$label, $sub])
          @php $n = $loop->iteration; $state = $n < $at || ($n === 1 && $at >= 1) ? 'ok' : ($n === $at + 1 && ! in_array($fa->status, ['declined', 'withdrawn'], true) ? 'now' : ''); @endphp
          <li><span class="dot {{ $state }}">{!! $state === 'ok' ? '&#10003;' : $n !!}</span><span><b>{{ $label }}</b><br><span class="fj-small">{{ $sub }}</span></span></li>
        @endforeach
      </ol>
    </div>
  @endif

  <div class="kyc-card">
    <span class="flabel">What you asked for</span>
    <ul class="fj-rows">
      @if ($fa->product === 'asset' && $fa->details)
        <li><span>Asset</span><span>{{ $fa->details['description'] ?? '' }}</span></li>
        <li><span>Supplier</span><span>{{ $fa->details['supplier'] ?? '' }}</span></li>
      @elseif ($fa->details)
        <li><span>Deal</span><span>{{ $fa->productConfig('kinds')[$fa->details['kind'] ?? ''] ?? '' }} · {{ $fa->details['goods'] ?? '' }}</span></li>
        <li><span>Supplier</span><span>{{ $fa->details['supplier'] ?? '' }}</span></li>
      @endif
      <li><span>Amount</span><span>{{ $fa->amount ? $money($fa->amount) : 'Not yet' }}</span></li>
      <li><span>Term</span><span>{{ $fa->term_months ? $fa->term_months . ' months' : 'Not yet' }}</span></li>
      @if ($fa->estimate)
        <li><span>Estimated {{ $fa->estimate['basis'] === 'monthly' ? 'monthly instalment' : 'repayment at the end' }}</span><span>{{ $money($fa->estimate['instalment']) }}</span></li>
      @endif
      <li><span>Documents</span><span>{{ $fa->documents->count() }} uploaded</span></li>
    </ul>
    @if ($fa->estimate)<p class="fj-small">Estimates are indicative, not an offer.</p>@endif
  </div>

  @if ($canPay)
    <form method="POST" action="{{ route('finance.pay', $token) }}" class="kyc-card" style="display:grid;gap:8px">
      @csrf
      <span class="flabel">Repay</span>
      <p class="fj-small" style="margin:0">Pay what is due through Sharet Pay, by M-Pesa or Sharet wallet.</p>
      <button class="btn btn-primary" type="submit">Pay with Sharet Pay</button>
    </form>
  @endif

  @if ($fa->history)
    <details class="kyc-card"><summary class="flabel" style="cursor:pointer">History</summary>
      <ul class="fj-rows" style="margin-top:8px">
        @foreach (array_reverse($fa->history) as $h)
          <li><span>{{ \Illuminate\Support\Carbon::parse($h['at'])->format('j M, g:ia') }}</span><span>{{ config('finance_journey.statuses.' . $h['status'], $h['status']) }}{{ ! empty($h['note']) && ! in_array($h['status'], ['more_info', 'declined'], true) ? ' · ' . $h['note'] : '' }}</span></li>
        @endforeach
      </ul>
    </details>
  @endif
@endsection
