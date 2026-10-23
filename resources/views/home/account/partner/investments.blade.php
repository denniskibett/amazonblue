@extends('home.layouts.account', ['title' => 'Investments', 'tab' => 'investments', 'area' => 'partner'])

@section('content')
  <div class="acct-head">
    <h1>Investments</h1>
    <p>{{ $partner->name }} · the lending lines your capital funds</p>
  </div>

  @if ($fundings->isEmpty())
    <section class="a-card">
      <x-az.empty title="No investments yet"
        message="When our team places your capital in a lending line, it shows here with what was committed, lent out and returned.">
        <a class="btn btn-primary" href="{{ route('account.partner.funds') }}">Add funds</a>
      </x-az.empty>
    </section>
  @else
    <div class="a-grid two">
      @foreach ($fundings as $funding)
        @php $inv = $funding->investment; $used = (float) $funding->amount_committed > 0 ? min(100, round($funding->amount_disbursed / $funding->amount_committed * 100)) : null; @endphp
        <a class="a-card" href="{{ route('account.partner.investment', $inv) }}" style="text-decoration:none;color:inherit;display:block">
          <div class="a-sec-h"><h2>{{ $inv->name }}</h2><x-az.status-badge :status="$funding->status === 'settled' ? 'repaid' : 'disbursed'" :label="ucfirst($funding->status ?: 'active')" /></div>
          <p class="muted" style="margin:4px 0 0;font-size:14px">{{ $inv->formatted_status }}{{ $inv->facility_type ? ' · ' . $inv->facility_type_label : '' }}{{ $inv->interest_rate ? ' · ' . rtrim(rtrim(number_format((float) $inv->interest_rate, 2), '0'), '.') . '%' : '' }}</p>
          <dl class="a-dl">
            <div><dt>Committed</dt><dd><x-az.money :amount="$funding->amount_committed" /></dd></div>
            <div><dt>Lent to borrowers</dt><dd><x-az.money :amount="$funding->amount_disbursed" /></dd></div>
            <div><dt>Paid back to the line</dt><dd><x-az.money :amount="$funding->amount_returned" /></dd></div>
            <div><dt>Loans</dt><dd>{{ $loanCounts[$inv->id] ?? 0 }}</dd></div>
            {{-- partner lines (owner, 23 Oct 2026): what it earned and when it comes back --}}
            @if ($inv->return_type === 'loan_share')
              @php $st = app(\App\Services\Partners\PartnerLines::class)->status($inv); @endphp
              <div><dt>Earned so far</dt><dd><x-az.money :amount="$st['earned']" /></dd></div>
              <div><dt>{{ $inv->status === 'active' ? 'Comes back' : 'Closed' }}</dt><dd>{{ $inv->status === 'active' ? ($inv->maturity_date ? 'by ' . $inv->maturity_date->format('j M Y') : 'when its loans are repaid') : ($inv->exit_date?->format('j M Y') ?? 'yes') }}</dd></div>
            @endif
          </dl>
          @if ($used !== null)
            <div class="a-progress" style="margin-top:12px" aria-label="{{ $used }}% of the commitment lent out"><i style="width: {{ $used }}%"></i></div>
            <p class="muted" style="margin:6px 0 0;font-size:13px">{{ $used }}% of your commitment lent out</p>
          @endif
        </a>
      @endforeach
    </div>
  @endif

  <x-az.placeholder dependency="online subscriptions (partner KYC, subscription agreement, funding requests table)" title="Invest online">
    Choosing a fund, amount and tenor and signing the subscription agreement in the app comes later. For now our team sets up each investment with you.
  </x-az.placeholder>
@endsection
