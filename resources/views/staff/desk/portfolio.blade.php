{{-- Finance · portfolio on the ledger (Stage 4 Phase 7): what is owed, how late, income booked and collected, partners. --}}
@extends('layouts.app')

@php $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]';
  $tile = fn ($label, $value, $sub = null) => ['label' => $label, 'value' => $value, 'sub' => $sub]; @endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Portfolio', 'sub' => 'From the loan cycles and the ledger, as of today. Not from loan amounts.',
    'actions' => '<a class="rounded-lg border border-gray-300 px-3 py-2 text-theme-sm font-medium dark:border-gray-700" href="' . route('staff.portfolio.csv') . '">Download CSV</a>'])

  <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="{{ $card }}"><p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Owed to us</p><p class="mt-1 text-xl"><x-staff.money :amount="$p['owed']" /></p><p class="text-theme-xs text-gray-500">{{ $p['open_loans'] }} open loans · principal <x-staff.money :amount="$p['principal']" :currency="false" /></p></div>
    <div class="{{ $card }}"><p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Portfolio at risk</p><p class="mt-1 text-xl">{{ $p['par30'] }}% <span class="text-theme-sm text-gray-500">PAR30</span></p><p class="text-theme-xs text-gray-500">PAR1 {{ $p['par1'] }}% · PAR90 {{ $p['par90'] }}%</p></div>
    <div class="{{ $card }}"><p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Non-performing</p><p class="mt-1 text-xl"><x-staff.money :amount="$p['npl_owed']" /></p><p class="text-theme-xs text-gray-500">{{ $p['npl_loans'] }} loans past their default threshold</p></div>
    <div class="{{ $card }}"><p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Cash</p><p class="mt-1 text-theme-sm">Lent <x-staff.money :amount="$p['cash_lent']" /></p><p class="text-theme-sm">Repaid <x-staff.money :amount="$p['cash_repaid']" /></p></div>
  </div>

  <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <section class="{{ $card }}">
      <h2 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">Income booked (ledger)</h2>
      <dl class="space-y-1 text-theme-sm">
        @foreach (['interest_income' => 'Interest charged', 'penalty_income' => 'Penalty charged', 'fee_income' => 'Fees', 'rollover_adjustments' => 'Rollover adjustments (+ extra carried, − forgiven)',
          'discounts' => 'Discounts and waivers (cost)', 'bad_debts' => 'Bad debts written off (cost)', 'partner_shares' => 'Partner and broker shares (cost)'] as $k => $l)
          <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ $l }}</dt><dd><x-staff.money :amount="$p[$k]" /></dd></div>
        @endforeach
      </dl>
      <p class="mt-2 text-theme-xs text-gray-500">Charged is what loans were billed; collected is what came in (partner earnings, right). Trial balance {{ number_format($p['trial_balance'], 4) }} (must be 0).</p>
    </section>
    <section class="{{ $card }}">
      <h2 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">Earned from interest and penalty collected</h2>
      <dl class="space-y-1 text-theme-sm">
        @foreach ($p['partners'] as $row)
          <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ $row['name'] }}</dt><dd><x-staff.money :amount="$row['earned']" /></dd></div>
        @endforeach
        <div class="flex justify-between gap-4 border-t border-gray-200 pt-1 font-medium dark:border-gray-800"><dt>Total collected and shared</dt><dd><x-staff.money :amount="collect($p['partners'])->sum('earned')" /></dd></div>
      </dl>
    </section>
  </div>

  <x-staff.table caption="Open loans" :headers="['Loan', 'Status', ['Unpaid principal', 'num'], ['Owed today', 'num'], ['Days late', 'num'], 'NPL']">
    @foreach ($p['rows']->sortByDesc('owed')->take(50) as $r)
      <tr><td><a class="text-brand-500 hover:underline" href="{{ route('loans.show', $r['loan']->id) }}">#{{ $r['loan']->id }}</a></td><td>{{ $r['loan']->status }}</td>
        <td class="num"><x-staff.money :amount="$r['principal']" :currency="false" /></td><td class="num"><x-staff.money :amount="$r['owed']" :currency="false" /></td>
        <td class="num">{{ $r['days'] }}</td><td>{{ $r['loan']->is_non_performing ? 'yes' : '' }}</td></tr>
    @endforeach
  </x-staff.table>
  <p class="mt-2 text-theme-xs text-gray-500">The 50 largest balances; the CSV has every open loan.</p>
@endsection
