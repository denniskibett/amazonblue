{{-- Finance · portfolio on the ledger (Stage 4 Phase 7): what is owed, how late, income booked and collected, partners. --}}
@extends('layouts.app')

@php $card = 'rounded-2xl border border-gray-200 bg-white px-4 py-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6';
  $tile = fn ($label, $value, $sub = null) => ['label' => $label, 'value' => $value, 'sub' => $sub]; @endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Portfolio', 'sub' => 'From the loan cycles and the ledger, as of today. Not from loan amounts.',
    'actions' => '<a class="rounded-lg border border-gray-300 px-3 py-2 text-theme-sm font-medium dark:border-gray-700" href="' . route('staff.portfolio.csv') . '">Download CSV</a>'])

  <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <section class="{{ $card }}">
      <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Income booked (ledger)</h2>
      <dl class="divide-y divide-gray-100 text-theme-sm dark:divide-gray-800">
        @foreach (['interest_income' => 'Interest charged', 'penalty_income' => 'Penalty charged', 'fee_income' => 'Fees', 'rollover_adjustments' => 'Rollover adjustments (+ extra carried, − forgiven)',
          'discounts' => 'Discounts and waivers (cost)', 'bad_debts' => 'Bad debts written off (cost)', 'partner_shares' => 'Partner and broker shares (cost)'] as $k => $l)
          <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500 dark:text-gray-400">{{ $l }}</dt><dd><x-staff.money :amount="$p[$k]" /></dd></div>
        @endforeach
      </dl>
      <p class="mt-2 text-theme-xs text-gray-500">Charged is what loans were billed; collected is what came in (partner earnings, right). Trial balance {{ number_format($p['trial_balance'], 4) }} (must be 0).</p>
    </section>
    <section class="{{ $card }}">
      <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Earned from interest and penalty collected</h2>
      <dl class="divide-y divide-gray-100 text-theme-sm dark:divide-gray-800">
        @foreach ($p['partners'] as $row)
          <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500 dark:text-gray-400">{{ $row['name'] }}</dt><dd><x-staff.money :amount="$row['earned']" /></dd></div>
        @endforeach
        <div class="flex justify-between gap-4 py-2 font-semibold text-gray-800 dark:text-white/90"><dt>Total collected and shared</dt><dd><x-staff.money :amount="collect($p['partners'])->sum('earned')" /></dd></div>
      </dl>
    </section>
  </div>

  @php
    $openRows = $p['rows']->sortByDesc('owed')->values();
    (new \Illuminate\Database\Eloquent\Collection($openRows->pluck('loan')->all()))->load(['user:id,name,phone', 'loanType:id,name']);
  @endphp
  <x-staff.table title="Open loans by balance" caption="Open loans" :headers="['Borrower', 'Loan · product', 'Status', ['Unpaid principal', 'num'], ['Owed today', 'num'], ['Days late', 'num'], 'NPL']">
    @foreach ($openRows as $r)
      @php $d = $r['days']; @endphp
      <tr>
        <td data-sort-value="{{ $r['loan']->user->name ?? '' }}"><x-staff.person :user="$r['loan']->user" :sub="$r['loan']->user->phone ?? null" /></td>
        <td><a class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" href="{{ route('loans.show', $r['loan']->id) }}">Loan #{{ $r['loan']->id }}</a>
          <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $r['loan']->loanType->name ?? '—' }}</span></td>
        <td data-filter-value="{{ ucfirst($r['loan']->status) }}"><x-staff.status-badge :status="$r['loan']->status" /></td>
        <td class="num" data-sort-value="{{ $r['principal'] }}"><x-staff.money :amount="$r['principal']" tone="muted" /></td>
        <td class="num" data-sort-value="{{ $r['owed'] }}"><x-staff.money :amount="$r['owed']" class="text-gray-800 dark:text-white/90" /></td>
        <td class="num" data-sort-value="{{ $d }}"><x-staff.pill :tone="$d >= 30 ? 'red' : ($d > 0 ? 'amber' : 'green')">{{ $d }} {{ $d === 1 ? 'day' : 'days' }}</x-staff.pill></td>
        <td data-filter-value="{{ $r['loan']->is_non_performing ? 'NPL' : 'Performing' }}">@if ($r['loan']->is_non_performing)<x-staff.pill tone="red" dot>NPL</x-staff.pill>@else<span class="text-theme-xs text-gray-400">—</span>@endif</td>
      </tr>
    @endforeach
  </x-staff.table>
  <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">Every open loan with a balance, largest first. The CSV has the same list.</p>
@endsection
