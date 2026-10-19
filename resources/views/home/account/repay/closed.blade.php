{{-- Facility closed: the loan's status is repaid (Concerns\BorrowerRepayment@accountLoanClosed). Figures from
     LoanProgress; "on time" from the 4Cs history (CreditAssessment); limit from BorrowingEligibility. --}}
@extends('home.layouts.account', ['title' => 'Repaid ' . $progress['reference'], 'tab' => 'loans'])

@php
  $loan = $progress['loan'];
  $bal = $progress['balance'];
  $repaidStep = collect($progress['timeline'])->firstWhere('label', 'Repaid');
  $first = strtok($user->name, ' ');
@endphp

@section('content')
  <a class="acct-back" href="{{ route('account.loan', $loan) }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>{{ $progress['reference'] }}</a>

  <section class="a-card rp-closed">
    <span class="rp-closed__ic" aria-hidden="true"><svg class="ic" viewBox="0 0 24 24"><path d="M5 12l4 4 10-10" /></svg></span>
    <h1>All paid. Thank you, {{ $first }}.</h1>
    <p class="muted">{{ $progress['reference'] }}{{ $loan->loanType ? ' (' . $loan->loanType->name . ')' : '' }} is fully repaid and closed.</p>

    @if ($limit && ! $hasOpenLoan)
      <x-az.alert type="ok" title="You can borrow up to KES {{ number_format($limit, 2) }}">
        {{ ($outcome['status'] ?? null) === 'on_time' ? 'Because you repaid on time. Keep it up to keep your limit.' : 'Repay your next loan in full and on time to raise your limit.' }}
      </x-az.alert>
    @endif

    <dl class="a-dl">
      @if ($bal)<div><dt>Total repaid</dt><dd class="num">KES {{ number_format($bal['paid'], 2) }}</dd></div>@endif
      @if ($repaidStep['date'] ?? null)<div><dt>Closed</dt><dd>{{ \Illuminate\Support\Carbon::parse($repaidStep['date'])->format('j M Y') }}</dd></div>@endif
      @if ($outcome)<div><dt>How it ended</dt><dd>{{ $outcome['label'] }}</dd></div>@endif
      <div><dt>Payments</dt><dd>{{ $progress['repayments']->count() }}</dd></div>
    </dl>

    <div class="rp-actions">
      @if (! $hasOpenLoan)<a class="btn btn-primary" href="{{ route('account.borrow') }}">Borrow again</a>@endif
      <a class="btn btn-ghost" href="{{ route('account.loan', $loan) }}">Payments and receipts</a>
    </div>
    <x-az.placeholder dependency="clearance letter" title="Clearance letter" style="margin-top:18px">
      A downloadable letter confirming the loan is closed will be added to your documents.
    </x-az.placeholder>
  </section>
@endsection
