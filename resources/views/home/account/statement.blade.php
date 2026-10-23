{{-- Partner statement: journal rows (partner_transactions), filter by wallet and dates, CSV of the same rows. --}}
@extends('home.layouts.account', ['title' => 'Statement', 'tab' => 'statement', 'area' => 'partner'])

@php
  $date = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('j M Y');
  $keep = array_filter(['from' => $from, 'to' => $to]);
@endphp

@section('content')
  <div class="acct-head">
    <h1>Statement</h1>
    <p>{{ $partner->name }} · every entry on your account</p>
  </div>

  <nav class="a-filter" aria-label="Filter by wallet">
    <a href="{{ route('account.statement', $keep) }}" @if (! $wallet) aria-current="page" @endif>All</a>
    @foreach (\App\Services\PartnerPortfolio::WALLETS as $slug => $label)
      <a href="{{ route('account.statement', $keep + ['wallet' => $slug]) }}" @if ($wallet === $slug) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
  </nav>

  <section class="a-card">
    <form class="p-dates" method="GET" action="{{ route('account.statement') }}">
      @if ($wallet)<input type="hidden" name="wallet" value="{{ $wallet }}" />@endif
      <x-az.field name="from" label="Dates" type="date" data-range-end="to" :value="$from" />
      <x-az.field name="to" label="To" type="date" :value="$to" />
      <button class="btn btn-primary" type="submit">Show</button>
      @if ($from || $to)<a class="btn btn-ghost" href="{{ route('account.statement', array_filter(['wallet' => $wallet])) }}">Clear</a>@endif
      <a class="btn btn-ghost" href="{{ route('account.partner.statement.csv', array_filter(['wallet' => $wallet, 'from' => $from, 'to' => $to])) }}" download>Download CSV</a>
    </form>
  </section>

  <section class="a-card">
    @if ($entries->isEmpty())
      <x-az.empty title="No entries" :message="($wallet || $from || $to) ? 'Nothing matches these filters. Try a wider date range or another wallet.' : 'Capital you add, earnings and withdrawals appear here once our team records them.'" />
    @else
      <x-az.table caption="Statement entries" class="p-statement">
        <x-slot:head><tr><th class="p-hide-sm">Date</th><th>Entry</th><th class="p-hide-sm">Wallet</th><th class="p-hide-sm">Reference</th><th class="num">Amount</th></tr></x-slot:head>
        @foreach ($entries as $entry)
          @php $out = \App\Services\PartnerAccount::entryIsOut($entry); @endphp
          <tr>
            <td class="p-hide-sm" style="white-space:nowrap">{{ $date($entry->transaction_date) }}</td>
            <td><b>{{ \App\Services\PartnerPortfolio::label($entry) }}</b><br /><span class="muted" style="font-size:13px"><span class="p-show-sm">{{ $date($entry->transaction_date) }}{{ $entry->loan_id ? ' · ' : '' }}</span>{{ $entry->loan_id ? 'Loan #' . str_pad($entry->loan_id, 5, '0', STR_PAD_LEFT) : '' }}</span></td>
            <td class="p-hide-sm">{{ \App\Services\PartnerPortfolio::WALLETS[$entry->wallet_slug] ?? ucfirst(str_replace('_', ' ', $entry->wallet_slug)) }}</td>
            <td class="muted p-hide-sm" style="font-size:13px">{{ $entry->reference }}</td>
            <td class="num"><x-az.money :amount="$out ? -abs($entry->amount) : $entry->amount" sign tone="auto" /></td>
          </tr>
        @endforeach
      </x-az.table>
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
