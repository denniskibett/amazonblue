{{-- One recorded repayment of the borrower's own loan, printable (print CSS, no PDF).
     Receipt number = repayment id. Balance after is not shown: LoanProgress does not provide a per-payment balance.
     Non-cash rows (CustomerWallets::kind != cash) are book entries, not receipts. --}}
@php
  $isCash = $kind === 'cash';
  $number = ($isCash ? 'RCPT-' : 'ENTRY-') . str_pad((string) $repayment->id, 6, '0', STR_PAD_LEFT);
  $ref = 'Loan #' . str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT);
  $kindLabel = ['rollover' => 'Rollover (moved to a new period)', 'discount' => 'Discount', 'bad_debt' => 'Written off', 'non_cash' => 'Book entry (not cash)'][$kind] ?? 'Book entry';
  $method = $repayment->mode ? (strtolower($repayment->mode) === 'mpesa' ? 'M-Pesa' : ucfirst($repayment->mode)) : 'Payment';
@endphp
@extends('home.layouts.account', ['title' => ($isCash ? 'Receipt ' : 'Book entry ') . $number, 'tab' => 'loans'])

@section('content')
  <div class="rp-noprint">
    <a class="acct-back" href="{{ route('account.loan', $loan) }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>{{ $ref }}</a>
  </div>

  <article class="rp-receipt" aria-label="{{ $isCash ? 'Payment receipt' : 'Book entry' }} {{ $number }}">
    <header class="rp-receipt__head">
      <img src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures" />
      <div>
        <b>{{ $isCash ? 'PAYMENT RECEIPT' : 'BOOK ENTRY' }}</b>
        <span>No. {{ $number }}</span>
        <span>{{ \Illuminate\Support\Carbon::parse($repayment->repayment_date)->format('j M Y') }}</span>
      </div>
    </header>

    <div class="rp-receipt__amount {{ $isCash ? '' : 'book' }}">
      <div>
        <span>{{ $isCash ? 'Amount received' : $kindLabel }}</span>
        <b>KES {{ number_format((float) $repayment->amount, 2) }}</b>
      </div>
      @if ($isCash)<x-az.status-badge type="payment" status="received" />@else<span class="badge-s st-rejected">Not a receipt</span>@endif
    </div>

    @unless ($isCash)
      <x-az.alert type="info" title="This is a book entry, not a payment">
        No money was received. It is an adjustment recorded on your loan ({{ strtolower($kindLabel) }}), so it is not a receipt.
      </x-az.alert>
    @endunless

    <div class="rp-receipt__cols">
      <div><b>{{ $isCash ? 'Received from' : 'Borrower' }}</b><span>{{ $user->name }}</span>@if ($user->phone)<span>{{ $user->phone }}</span>@endif</div>
      <div><b>Applied to</b><span>{{ $loan->loanType->name ?? 'Loan' }}</span><span>{{ $ref }}</span></div>
    </div>

    <dl class="rp-receipt__table">
      <div><dt>{{ $isCash ? 'Receipt number' : 'Entry number' }}</dt><dd>{{ $number }}</dd></div>
      <div><dt>{{ $isCash ? 'Payment date' : 'Date' }}</dt><dd>{{ \Illuminate\Support\Carbon::parse($repayment->repayment_date)->format('j M Y') }}</dd></div>
      @if ($isCash)<div><dt>Method</dt><dd>{{ $method }}</dd></div>@endif
      <div><dt>{{ $isCash ? 'Transaction ID' : 'Reference' }}</dt><dd>{{ $repayment->transaction ?: '—' }}</dd></div>
      <div><dt>Amount</dt><dd>KES {{ number_format((float) $repayment->amount, 2) }}</dd></div>
      <div><dt>Recorded</dt><dd>{{ optional($repayment->created_at)->format('j M Y, H:i') ?? '—' }}</dd></div>
    </dl>

    <footer class="rp-receipt__foot">
      <span>{{ $isCash ? 'This receipt is system-generated and valid without a signature.' : 'System-generated record of a book entry.' }}</span>
      <span>Aimzone Ventures · Nairobi, Kenya</span>
    </footer>
  </article>

  <div class="rp-actions rp-noprint">
    <button class="btn btn-primary" type="button" onclick="window.print()">{{ $isCash ? 'Print or save receipt' : 'Print' }}</button>
    <a class="btn btn-ghost" href="{{ route('account.loan', $loan) }}">Back to loan</a>
  </div>
@endsection
