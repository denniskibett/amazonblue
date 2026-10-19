{{-- Accountant · reconciliation: the same checks as `php artisan wallets:check` (CustomerWallets), read-only, no repair. --}}
@extends('layouts.app')

@php $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6'; @endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Wallets & reconciliation', 'sub' => 'Customer wallets checked against the loan tables and the partner journal, run now. Same checks as php artisan wallets:check.'])

  @if ($problems === 0)
    <x-staff.alert type="ok" class="mb-6" title="All customer wallets reconcile">{{ $borrowers['checked'] }} borrower wallet(s) and {{ $partners['checked'] }} partner wallet(s) checked. Wallets match the journal; the journal is not yet complete (repayments since 7 Oct 2026 are not split to partners yet), so this does not prove partner balances are right.</x-staff.alert>{{-- S-S5 interim until Phase 3 --}}
  @else
    <x-staff.alert type="warn" class="mb-6" title="{{ $problems }} item(s) need attention">Repairs are run by a developer from the command line after review (wallets:check --repair-partners); there is no repair button here.</x-staff.alert>
  @endif

  <div class="grid grid-cols-1 gap-6">
    <section class="{{ $card }} min-w-0">
      <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Borrower cash wallets vs loan tables</h2>
      <p class="mb-4 text-theme-sm text-gray-500">{{ $borrowers['checked'] }} borrower(s) checked. Expected = cash repaid − cash disbursed (book entries excluded).</p>
      @if (empty($borrowers['issues']))
        <p class="text-theme-sm text-az-sage-d">No differences.</p>
      @else
        <x-staff.table caption="Borrower differences" :headers="['User', 'Name', ['Expected', 'num'], ['Wallet', 'num'], ['Difference', 'num']]">
          @foreach ($borrowers['issues'] as $i)
            <tr><td>#{{ $i['user'] }}</td><td>{{ $i['name'] ?? '—' }}</td><td class="num"><x-staff.money :amount="$i['expected']" /></td>
              <td class="num">@if ($i['wallet'] === null) missing @else <x-staff.money :amount="$i['wallet']" /> @endif</td>
              <td class="num"><x-staff.money :amount="($i['wallet'] ?? 0) - $i['expected']" tone="auto" /></td></tr>
          @endforeach
        </x-staff.table>
      @endif
      @if (! empty($borrowers['integrity']))
        <h3 class="mb-2 mt-5 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Wallets whose balance differs from their own transactions</h3>
        <x-staff.table :headers="['Wallet', 'User', 'Slug', ['Balance', 'num'], ['Transactions add up to', 'num']]">
          @foreach ($borrowers['integrity'] as $i)
            <tr><td>#{{ $i['wallet_id'] }}</td><td>#{{ $i['holder'] }}</td><td>{{ $i['slug'] }}</td><td class="num"><x-staff.money :amount="$i['balance']" /></td><td class="num"><x-staff.money :amount="$i['log']" /></td></tr>
          @endforeach
        </x-staff.table>
      @endif
    </section>

    <section class="{{ $card }} min-w-0">
      <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Partner wallets vs partner journal</h2>
      <p class="mb-4 text-theme-sm text-gray-500">{{ $partners['checked'] }} partner wallet(s) checked (every wallet and every journal group).</p>
      @if (! empty($partners['slug_mismatches']))
        <x-staff.alert type="err" class="mb-4" title="Journal rows point at the wrong wallet">Fix the journal first; wallets must not be aligned to it until then.</x-staff.alert>
        <div class="mb-4"><x-staff.table :headers="['Partner', 'Journal says', 'Posted to', ['Rows', 'num'], ['Amount', 'num']]">
          @foreach ($partners['slug_mismatches'] as $m)
            <tr><td>{{ $partnerNames[$m['partner']] ?? '#' . $m['partner'] }}</td><td>{{ $m['journal_says'] }}</td><td>{{ $m['posted_to'] }}</td><td class="num">{{ $m['rows'] }}</td><td class="num"><x-staff.money :amount="$m['amount']" /></td></tr>
          @endforeach
        </x-staff.table></div>
      @endif
      @if (empty($partners['issues']))
        <p class="text-theme-sm text-az-sage-d">No differences.</p>
      @else
        <x-staff.table caption="Partner differences" :headers="['Partner', 'Wallet', ['Journal', 'num'], ['Wallet balance', 'num']]">
          @foreach ($partners['issues'] as $i)
            <tr><td>{{ $partnerNames[$i['partner']] ?? '#' . $i['partner'] }}</td><td>{{ $i['wallet'] }}</td><td class="num"><x-staff.money :amount="$i['journal']" /></td>
              <td class="num">@if ($i['balance'] === null) missing @else <x-staff.money :amount="$i['balance']" /> @endif</td></tr>
          @endforeach
        </x-staff.table>
      @endif
      @if (! empty($partners['integrity']))
        <h3 class="mb-2 mt-5 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Wallets whose balance differs from their own transactions</h3>
        <x-staff.table :headers="['Wallet', 'Partner', 'Slug', ['Balance', 'num'], ['Transactions add up to', 'num']]">
          @foreach ($partners['integrity'] as $i)
            <tr><td>#{{ $i['wallet_id'] }}</td><td>{{ $partnerNames[$i['holder']] ?? '#' . $i['holder'] }}</td><td>{{ $i['slug'] }}</td><td class="num"><x-staff.money :amount="$i['balance']" /></td><td class="num"><x-staff.money :amount="$i['log']" /></td></tr>
          @endforeach
        </x-staff.table>
      @endif
    </section>

    <x-staff.placeholder dependency="general ledger and bank reconciliation" title="Ledger and bank statements">
      Matching M-Pesa and bank statements line by line, and a double-entry ledger with the six accounts, are Stage 4.
    </x-staff.placeholder>
  </div>
@endsection
