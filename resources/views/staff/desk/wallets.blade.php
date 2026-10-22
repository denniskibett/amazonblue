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

  @php $diffPill = fn ($v) => abs((float) $v) < 0.005 ? 'green' : 'red'; @endphp
  <div class="grid grid-cols-1 gap-6">
    {{-- borrowers: one card per check, Loans Due look (owner, 22 Oct 2026) --}}
    <div>
      <x-staff.table title="Borrower cash wallets vs loan tables" caption="Borrower differences" :headers="['Borrower', ['Expected', 'num'], ['Wallet', 'num'], ['Difference', 'num']]">
        @forelse ($borrowers['issues'] as $i)
          @php $diff = ($i['wallet'] ?? 0) - $i['expected']; @endphp
          <tr>
            <td data-sort-value="{{ $i['name'] ?? '' }}"><x-staff.person :name="$i['name'] ?? '—'" :id="$i['user']" :sub="'User #' . $i['user']" /></td>
            <td class="num" data-sort-value="{{ $i['expected'] }}"><x-staff.money :amount="$i['expected']" /></td>
            <td class="num" data-sort-value="{{ $i['wallet'] ?? 0 }}">@if ($i['wallet'] === null)<x-staff.pill tone="red">Missing</x-staff.pill>@else<x-staff.money :amount="$i['wallet']" />@endif</td>
            <td class="num" data-sort-value="{{ $diff }}"><x-staff.pill :tone="$diffPill($diff)"><x-staff.money :amount="$diff" /></x-staff.pill></td>
          </tr>
        @empty
          <tr data-table-skip><td colspan="4"><x-staff.empty title="No differences" message="{{ $borrowers['checked'] }} borrower wallet(s) match the loan tables." /></td></tr>
        @endforelse
      </x-staff.table>
      <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">{{ $borrowers['checked'] }} borrower(s) checked. Expected = cash repaid − cash disbursed (book entries excluded).</p>
    </div>

    @if (! empty($borrowers['integrity']))
      <x-staff.table title="Borrower wallets that differ from their own transactions" caption="Borrower wallet integrity" :headers="['Wallet', 'Holder', ['Balance', 'num'], ['Transactions add up to', 'num']]">
        @foreach ($borrowers['integrity'] as $i)
          <tr>
            <td><span class="font-medium text-gray-800 dark:text-white/90">Wallet #{{ $i['wallet_id'] }}</span><span class="block font-mono text-theme-xs text-gray-500 dark:text-gray-400">{{ $i['slug'] }}</span></td>
            <td><span class="cursor-pointer hover:text-brand-500" data-user-peek="{{ $i['holder'] }}">User #{{ $i['holder'] }}</span></td>
            <td class="num"><x-staff.money :amount="$i['balance']" /></td>
            <td class="num"><x-staff.money :amount="$i['log']" /></td>
          </tr>
        @endforeach
      </x-staff.table>
    @endif

    {{-- partners --}}
    @if (! empty($partners['slug_mismatches']))
      <div>
        <x-staff.alert type="err" class="mb-4" title="Journal rows point at the wrong wallet">Fix the journal first; wallets must not be aligned to it until then.</x-staff.alert>
        <x-staff.table title="Journal rows posted to the wrong wallet" caption="Journal slug mismatches" :headers="['Partner', 'Journal says', 'Posted to', ['Rows', 'num'], ['Amount', 'num']]">
          @foreach ($partners['slug_mismatches'] as $m)
            <tr>
              <td><x-staff.person :name="$partnerNames[$m['partner']] ?? '#' . $m['partner']" :sub="'Partner #' . $m['partner']" /></td>
              <td><x-staff.pill tone="blue">{{ $m['journal_says'] }}</x-staff.pill></td>
              <td><x-staff.pill tone="red">{{ $m['posted_to'] }}</x-staff.pill></td>
              <td class="num">{{ $m['rows'] }}</td>
              <td class="num"><x-staff.money :amount="$m['amount']" /></td>
            </tr>
          @endforeach
        </x-staff.table>
      </div>
    @endif

    <div>
      <x-staff.table title="Partner wallets vs partner journal" caption="Partner differences" :headers="['Partner', 'Wallet', ['Journal', 'num'], ['Wallet balance', 'num'], ['Difference', 'num']]">
        @forelse ($partners['issues'] as $i)
          @php $diff = ($i['balance'] ?? 0) - $i['journal']; @endphp
          <tr>
            <td data-sort-value="{{ $partnerNames[$i['partner']] ?? '' }}"><x-staff.person :name="$partnerNames[$i['partner']] ?? '#' . $i['partner']" :sub="'Partner #' . $i['partner']" /></td>
            <td data-filter-value="{{ $i['wallet'] }}"><x-staff.pill tone="brand">{{ $i['wallet'] }}</x-staff.pill></td>
            <td class="num"><x-staff.money :amount="$i['journal']" /></td>
            <td class="num">@if ($i['balance'] === null)<x-staff.pill tone="red">Missing</x-staff.pill>@else<x-staff.money :amount="$i['balance']" />@endif</td>
            <td class="num" data-sort-value="{{ $diff }}"><x-staff.pill :tone="$diffPill($diff)"><x-staff.money :amount="$diff" /></x-staff.pill></td>
          </tr>
        @empty
          <tr data-table-skip><td colspan="5"><x-staff.empty title="No differences" message="{{ $partners['checked'] }} partner wallet(s) match the journal." /></td></tr>
        @endforelse
      </x-staff.table>
      <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">{{ $partners['checked'] }} partner wallet(s) checked (every wallet and every journal group).</p>
    </div>

    @if (! empty($partners['integrity']))
      <x-staff.table title="Partner wallets that differ from their own transactions" caption="Partner wallet integrity" :headers="['Wallet', 'Partner', ['Balance', 'num'], ['Transactions add up to', 'num']]">
        @foreach ($partners['integrity'] as $i)
          <tr>
            <td><span class="font-medium text-gray-800 dark:text-white/90">Wallet #{{ $i['wallet_id'] }}</span><span class="block font-mono text-theme-xs text-gray-500 dark:text-gray-400">{{ $i['slug'] }}</span></td>
            <td>{{ $partnerNames[$i['holder']] ?? '#' . $i['holder'] }}</td>
            <td class="num"><x-staff.money :amount="$i['balance']" /></td>
            <td class="num"><x-staff.money :amount="$i['log']" /></td>
          </tr>
        @endforeach
      </x-staff.table>
    @endif

    <x-staff.placeholder dependency="general ledger and bank reconciliation" title="Ledger and bank statements">
      Matching M-Pesa and bank statements line by line, and a double-entry ledger with the six accounts, are Stage 4.
    </x-staff.placeholder>
  </div>
@endsection
