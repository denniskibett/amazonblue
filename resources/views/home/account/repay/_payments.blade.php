{{-- Recorded repayments of one loan (real data), each linking to its receipt. Non-cash rows (rollover, discount,
     write-off: CustomerWallets::kind) are book entries, not money received. Needs $repayments, $loan. --}}
@php
  $kindLabel = ['rollover' => 'Rollover (moved to a new period)', 'discount' => 'Discount', 'bad_debt' => 'Written off', 'non_cash' => 'Book entry (not cash)'];
@endphp
@forelse ($repayments as $payment)
  @php $kind = \App\Services\CustomerWallets::kindOf($payment); @endphp
  <a class="a-row" href="{{ route('account.receipt', [$loan, $payment]) }}">
    <span class="chip {{ $kind === 'cash' ? 'sage' : 'cloud' }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $kind === 'cash' ? 'M12 19V5M5 12l7-7 7 7' : 'M4 12h16M14 6l6 6-6 6' }}" /></svg></span>
    <span class="t"><b>KES {{ number_format((float) $payment->amount, 2) }}</b><span>{{ $kind === 'cash' ? ($payment->mode ? (strtolower($payment->mode) === 'mpesa' ? 'M-Pesa' : ucfirst($payment->mode)) : 'Payment') . ($payment->transaction ? ' · ' . $payment->transaction : '') : $kindLabel[$kind] }} · {{ \Illuminate\Support\Carbon::parse($payment->repayment_date)->format('j M Y') }}</span></span>
    @if ($kind === 'cash')
      <x-az.status-badge type="payment" status="received" />
    @else
      <span class="badge-s st-rejected">Book entry</span>
    @endif
  </a>
@empty
  <p class="a-empty">No payments recorded yet. Once our team records a payment, its receipt shows here.</p>
@endforelse
