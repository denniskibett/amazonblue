@extends('home.layouts.account', ['title' => 'Investments', 'tab' => 'investments', 'area' => 'partner'])

@section('content')
  <div class="acct-head">
    <h1>Investments</h1>
    <p>{{ $partner->name }} · the lending lines your capital funds</p>
  </div>

  @if ($fundings->isEmpty())
    <section class="a-card">
      {{-- owner, 1 Nov 2026: say plainly why it's empty (capital not recorded as a line yet) --}}
      <x-az.empty title="No investments yet"
        message="Your capital hasn't been recorded as an investment yet — contact us and we'll set it up. Once it is, each line shows what was committed, lent out, returned and what is free.">
        <a class="btn btn-primary" href="https://wa.me/254701607959" target="_blank" rel="noopener">Contact us</a>
        <a class="btn btn-ghost" href="{{ route('account.partner.loans', ['list' => 'brokered']) }}">Loans you brought in</a>
      </x-az.empty>
    </section>
  @else
    <div class="a-grid two">
      @foreach ($fundings as $funding)
        @php $inv = $funding->investment; $used = (float) $funding->amount_committed > 0 ? min(100, round($funding->amount_disbursed / $funding->amount_committed * 100)) : null; @endphp
        <a class="a-card" href="{{ route('account.partner.investment', $inv) }}" style="text-decoration:none;color:inherit;display:block">
          <div class="a-sec-h"><h2>{{ $inv->name }}</h2><x-az.status-badge :status="$funding->status === 'settled' ? 'repaid' : 'disbursed'" :label="ucfirst($funding->status ?: 'active')" /></div>
          <p class="muted" style="margin:4px 0 0;font-size:14px">{{ $inv->formatted_status }}{{ $inv->facility_type ? ' · ' . $inv->facility_type_label : '' }}{{ $inv->interest_rate ? ' · ' . rtrim(rtrim(number_format((float) $inv->interest_rate, 2), '0'), '.') . '%' : '' }}</p>
          @if ($inv->return_type === 'fixed_return')
            {{-- a fixed return (owner, 15 Nov 2026): what they put in, what they are owed and when; how we used the
                 money is ours, not shown --}}
            @php [$fxIn, $fxRet, $fxDue] = app(\App\Services\Partners\PartnerLines::class)->fixedDue($inv); @endphp
            <dl class="a-dl">
              <div><dt>Terms</dt><dd>{{ \App\Services\Partners\PartnerLines::fixedLabel((float) $inv->interest_rate, (float) $inv->partner_share_rate, (int) $inv->duration_days) }}</dd></div>
              <div><dt>You put in</dt><dd><x-az.money :amount="$fxIn" /></dd></div>
              <div><dt>Your return</dt><dd><x-az.money :amount="$fxRet" /></dd></div>
              <div><dt>{{ $inv->status === 'active' ? 'Due to you' : 'Paid to you' }}</dt><dd><x-az.money :amount="$inv->status === 'active' ? $fxDue : app(\App\Services\Partners\PartnerLines::class)->paidOut($inv)" /></dd></div>
              <div><dt>{{ $inv->status === 'active' ? 'Due on' : 'Paid on' }}</dt><dd>{{ $inv->status === 'active' ? ($inv->maturity_date?->format('j M Y') ?? '—') : ($inv->exit_date?->format('j M Y') ?? '—') }}</dd></div>
            </dl>
          @else
          <dl class="a-dl">
            <div><dt>Committed</dt><dd><x-az.money :amount="$funding->amount_committed" /></dd></div>
            <div><dt>Lent to borrowers</dt><dd><x-az.money :amount="$funding->amount_disbursed" /></dd></div>
            <div><dt>Paid back to the line</dt><dd><x-az.money :amount="$funding->amount_returned" /></dd></div>
            <div><dt>Available (not lent out)</dt><dd><x-az.money :amount="max(0, (float) $funding->amount_committed - (float) $funding->amount_disbursed + (float) $funding->amount_returned)" /></dd></div>
            <div><dt>Loans</dt><dd>{{ $loanCounts[$inv->id] ?? 0 }}</dd></div>
            {{-- partner lines (owner, 23 Oct 2026): what it earned and when it comes back --}}
            @if ($inv->return_type === 'loan_share')
              @php $st = app(\App\Services\Partners\PartnerLines::class)->status($inv); @endphp
              <div><dt>Earned so far</dt><dd><x-az.money :amount="$st['earned']" /></dd></div>
              <div><dt>{{ $inv->status === 'active' ? 'Comes back' : 'Closed' }}</dt><dd>{{ $inv->status === 'active' ? ($inv->maturity_date ? 'by ' . $inv->maturity_date->format('j M Y') : 'when its loans are repaid') : ($inv->exit_date?->format('j M Y') ?? 'yes') }}</dd></div>
            @endif
          </dl>
          @endif
          @if ($used !== null && $inv->return_type !== 'fixed_return')
            <div class="a-progress" style="margin-top:12px" aria-label="{{ $used }}% of the commitment lent out"><i style="width: {{ $used }}%"></i></div>
            <p class="muted" style="margin:6px 0 0;font-size:13px">{{ $used }}% of your commitment lent out</p>
          @endif
        </a>
      @endforeach
    </div>
  @endif

  @if (\App\Services\Partners\Subscription::enabled())
    <section class="a-card">
      <div class="a-sec-h"><h2>Invest online</h2></div>
      <p class="muted" style="font-size:14px;margin:0 0 12px">Choose a short- or long-term line and an amount, sign the subscription and send the money.</p>
      <a class="btn btn-primary" href="{{ route('partner.subscribe') }}">Fund loans</a>
    </section>
  @endif
@endsection
