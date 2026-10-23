@extends('home.layouts.account', ['title' => 'Statement of account', 'tab' => 'loans'])

{{-- Statement of account: every disbursement and repayment on the borrower's loans (or one loan), with cash
     vs book entries (CustomerWallets::kind). Read-only; prints cleanly (.bj-print rules in styles.css). --}}
@php
  $kes = fn ($v) => number_format((float) $v, 2);
  $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('j M Y') : '—';
  $kindLabel = ['cash' => 'Cash', 'rollover' => 'Rollover', 'discount' => 'Discount', 'bad_debt' => 'Write-off', 'non_cash' => 'Book entry'];
  $ref = fn ($id) => 'Loan #' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
  $base = $loan ? route('account.loan.statement', $loan) : route('account.soa');
  $bal = $progress['balance'] ?? null;
@endphp

@section('content')
  <div class="bj-noprint">
    @if ($loan)
      <a class="acct-back" href="{{ route('account.loan', $loan) }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>{{ $ref($loan->id) }}</a>
    @endif
  </div>

  <div class="bj-print-head">
    <img src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures" />
    <div><b>STATEMENT OF ACCOUNT</b><br />Printed {{ now()->format('j M Y, H:i') }}</div>
  </div>

  <div class="acct-head bj-soa-head">
    <div>
      <h1>Statement of account</h1>
      <p>{{ $user->name }} · {{ $loan ? $ref($loan->id) . ' · ' . ($loan->loanType->name ?? '') : 'All loans' }} · {{ $from || $to ? ($from ? $date($from) : 'Start') . ' to ' . ($to ? $date($to) : 'today') : 'All dates' }}</p>
    </div>
    <button type="button" class="btn btn-ghost bj-noprint" onclick="window.print()">
      <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v7H7z" /></svg>Print or save as PDF
    </button>
  </div>

  <form method="GET" action="{{ $base }}" class="a-card bj-filter bj-noprint" aria-label="Filter by date">
    @if (! $loan && $loans->count() > 1)
      <div class="field az-field">
        <label for="soa-loan">Loan</label>
        <select id="soa-loan" class="input" onchange="if (this.value) window.location = this.value">
          <option value="">All loans</option>
          @foreach ($loans->sortByDesc('id') as $l)
            <option value="{{ route('account.loan.statement', $l) }}">{{ $ref($l->id) }} · KES {{ $kes($l->amount) }}</option>
          @endforeach
        </select>
      </div>
    @endif
    <x-az.field name="from" label="Dates" type="date" data-range-end="to" :value="$from?->toDateString()" />
    <x-az.field name="to" label="To" type="date" :value="$to?->toDateString()" />
    <div class="bj-filter__go">
      <button class="btn btn-primary" type="submit">Show</button>
      @if ($from || $to)<a class="btn btn-ghost" href="{{ $base }}">Clear</a>@endif
    </div>
  </form>

  <div class="bj-tiles">
    <div class="bj-tile cloud"><span>Sent to you (cash)</span><b>KES {{ $kes($totals['sent']) }}</b></div>
    <div class="bj-tile sage"><span>Paid by you (cash)</span><b>KES {{ $kes($totals['paid']) }}</b></div>
    @if ($loan && $bal && ! in_array($loan->status, ['pending', 'approved', 'rejected', 'written_off']))
      <div class="bj-tile apricot"><span>{{ $loan->status === 'repaid' ? 'Balance' : 'Owed today' }}</span><b>KES {{ $kes($loan->status === 'repaid' ? 0 : $bal['due']) }}</b></div>
    @else
      <div class="bj-tile sand"><span>Book entries (not cash)</span><b>KES {{ $kes($totals['book']) }}</b></div>
    @endif
  </div>

  <section class="a-card">
    @if ($entries->isEmpty())
      <x-az.empty title="Nothing in this period" message="Money sent to you and payments you make will show here." />
    @else
      <x-az.table caption="Statement entries" :headers="array_values(array_filter(['Date', 'Description', $loan ? null : 'Loan', ['Sent to you', 'num'], ['Paid or credited', 'num']]))">
        @foreach ($entries as $e)
          <tr @class(['bj-book' => $e['kind'] !== 'cash'])>
            <td>{{ $date($e['date']) }}</td>
            <td>
              {{ $e['type'] === 'sent' ? 'Loan sent' . ($e['mode'] ? ' by ' . (strtolower($e['mode']) === 'mpesa' ? 'M-Pesa' : ucfirst($e['mode'])) : '') : ($e['kind'] === 'cash' ? 'Payment' . ($e['mode'] ? ' · ' . (strtolower($e['mode']) === 'mpesa' ? 'M-Pesa' : ucfirst($e['mode'])) : '') : ['rollover' => 'Moved to a new period (rollover)', 'discount' => 'Discount', 'bad_debt' => 'Written off', 'non_cash' => 'Book entry'][$e['kind']]) }}
              <span class="bj-kind {{ $e['kind'] }}">{{ $kindLabel[$e['kind']] }}</span>
              @if ($e['ref'] && $e['kind'] === 'cash')<small class="muted bj-ref">{{ $e['ref'] }}</small>@endif
            </td>
            @unless ($loan)<td><a href="{{ route('account.loan', $e['loan_id']) }}">#{{ str_pad($e['loan_id'], 5, '0', STR_PAD_LEFT) }}</a></td>@endunless
            <td class="num">{{ $e['sent'] > 0 ? $kes($e['sent']) : '—' }}</td>
            <td class="num">{{ $e['paid'] > 0 ? $kes($e['paid']) : '—' }}</td>
          </tr>
        @endforeach
      </x-az.table>
      <p class="muted" style="font-size:12.5px;margin-top:12px">
        <b>Cash</b> is money that actually moved by M-Pesa or bank. <b>Rollover, discount, write-off and book entries</b> are adjustments our team recorded on the loan; no money changed hands. Amounts in KES.
      </p>
    @endif
  </section>

  <x-az.placeholder class="bj-noprint" dependency="ledger of charges (interest, penalties) with a running balance">
    Interest and late penalties are worked out on the loan page today; a full line-by-line balance comes with the new ledger.
  </x-az.placeholder>

  <p class="muted bj-print-foot">Questions about this statement? Contact {{ \App\Support\BorrowerLoanState::CONTACT['email'] }} or {{ \App\Support\BorrowerLoanState::CONTACT['phone'] }}, quoting your loan number. Aimzone Ventures · Nairobi, Kenya.</p>
@endsection
