@extends('home.layouts.account', ['title' => 'Statement', 'tab' => 'statement'])

@php
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
  $outgoing = ['withdrawal'];
@endphp

@section('content')
  <div class="acct-head">
    <h1>Statement</h1>
    <p>{{ $partner->name }} · every entry on your account</p>
  </div>

  <nav class="a-filter" aria-label="Filter by wallet">
    <a href="{{ route('account.statement') }}" @if (! $wallet) aria-current="page" @endif>All</a>
    @foreach (\App\Services\PartnerPortfolio::WALLETS as $slug => $label)
      <a href="{{ route('account.statement', ['wallet' => $slug]) }}" @if ($wallet === $slug) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
  </nav>

  <section class="a-card">
    @if ($entries->isEmpty())
      <p class="a-empty">No entries{{ $wallet ? ' in this wallet' : '' }} yet.</p>
    @else
      <div class="a-list">
        @foreach ($entries as $entry)
          @php $out = in_array($entry->type, $outgoing, true); @endphp
          <div class="a-row">
            <span class="chip {{ $out ? 'apricot' : 'sage' }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $out ? 'M12 5v14M5 12l7 7 7-7' : 'M12 19V5M5 12l7-7 7 7' }}" /></svg></span>
            <span class="t">
              <b>{{ \App\Services\PartnerPortfolio::label($entry) }}</b>
              <span>{{ \App\Services\PartnerPortfolio::WALLETS[$entry->wallet_slug] ?? ucfirst(str_replace('_', ' ', $entry->wallet_slug)) }}{{ $entry->loan_id ? ' · loan #' . str_pad($entry->loan_id, 5, '0', STR_PAD_LEFT) : '' }}{{ $entry->reference ? ' · ' . $entry->reference : '' }}</span>
            </span>
            <span class="amt {{ $out ? '' : 'in' }}">{{ $out ? '−' : '+' }}{{ $kes($entry->amount) }}<small>{{ \Illuminate\Support\Carbon::parse($entry->transaction_date)->format('j M Y') }}</small></span>
          </div>
        @endforeach
      </div>
      @if ($entries->hasPages())
        <div class="a-pager">
          @if ($entries->previousPageUrl())<a class="btn btn-ghost" href="{{ $entries->previousPageUrl() }}">Newer</a>@else<span></span>@endif
          <span class="muted" style="align-self:center;font-size:13px">Page {{ $entries->currentPage() }} of {{ $entries->lastPage() }}</span>
          @if ($entries->nextPageUrl())<a class="btn btn-ghost" href="{{ $entries->nextPageUrl() }}">Older</a>@else<span></span>@endif
        </div>
      @endif
    @endif
  </section>
@endsection
