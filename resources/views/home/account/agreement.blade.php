@extends('home.layouts.account', ['title' => 'Loan agreement', 'tab' => 'loans'])

@php
  $number = str_pad($loan->id, 5, '0', STR_PAD_LEFT);
  $file = route('account.agreement.file', $loan);
@endphp

@section('content')
  <div class="acct-head">
    <a class="a-back" href="{{ route('account.loan', $loan) }}">← Loan #{{ $number }}</a>
    <h1>Loan agreement</h1>
    <p>Signed with your verified signature on {{ \Illuminate\Support\Carbon::parse($loan->consent_date ?? $loan->borrow_date)->format('j M Y') }}.</p>
  </div>

  <section class="a-card agreement-card">
    <object class="agreement-pdf" data="{{ $file }}#view=FitH" type="application/pdf" aria-label="Loan agreement #{{ $number }}">
      {{-- phones that can't show a PDF inline get the buttons below instead --}}
      <p class="a-empty">Your browser can't show the agreement here. Open or download it below.</p>
    </object>
    <div class="row agreement-actions">
      <a class="btn btn-primary" href="{{ $file }}?download=1">Download PDF</a>
      <a class="btn btn-ghost" href="{{ $file }}" target="_blank" rel="noopener">Open full screen</a>
    </div>
    @if ($loan->agreement_sha256)
      {{-- the PDF was frozen when you signed; this fingerprint changes if even one byte of it does --}}
      <p class="agreement-proof">
        Signed {{ \Illuminate\Support\Carbon::parse($loan->agreement_signed_at)->format('j M Y, H:i') }}
        · template {{ $loan->agreement_version }}<br />
        SHA-256: <code>{{ $loan->agreement_sha256 }}</code>
      </p>
    @endif
  </section>
@endsection
