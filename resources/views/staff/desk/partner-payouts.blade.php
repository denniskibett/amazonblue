{{-- Accountant · partner distributions: partner wallet balances (read-only). Payout requests are Stage 4. --}}
@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'Partner payouts', 'sub' => 'What each partner holds today, by wallet. Read-only.'])

  <x-staff.placeholder class="mb-6" dependency="withdrawal requests and maker-checker" title="Paying a partner out">
    Partners will request a withdrawal, one person prepares the payout and a second approves it, and the ledger and wallet move together.
    That needs a withdrawal requests table and the maker-checker step (Stage 4). Until then payouts are recorded on the partner page.
  </x-staff.placeholder>

  @if ($rows->isEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><x-staff.empty title="No partners" /></div>
  @else
    @php $headers = array_merge(['Partner'], array_map(fn ($l) => [$l, 'num'], array_values($wallets)), [['Available', 'num'], ['Earned to date', 'num'], ['Committed', 'num'], '']); @endphp
    <x-staff.table title="Partner balances" caption="Partner balances" :headers="$headers">
      @foreach ($rows as $s)
        <tr>
          <td data-sort-value="{{ $s['partner']->name }}"><x-staff.person :name="$s['partner']->name" :sub="$s['partner']->is_house_account ? 'House account' : 'Partner #' . $s['partner']->id" /></td>
          @foreach (array_keys($wallets) as $slug)
            <td class="num"><x-staff.money :amount="$s['wallets'][$slug]['balance'] ?? 0" :currency="false" /></td>
          @endforeach
          <td class="num" data-sort-value="{{ $s['available'] }}"><x-staff.money :amount="$s['available']" class="font-semibold text-gray-800 dark:text-white/90" /></td>
          <td class="num"><x-staff.money :amount="$s['earned']" tone="muted" /></td>
          <td class="num"><x-staff.money :amount="$s['committed']" tone="muted" /></td>
          <td class="text-right" data-actions>@can('view partners')<a class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" href="{{ route('partners.show', $s['partner']) }}">Open partner</a>@endcan</td>
        </tr>
      @endforeach
    </x-staff.table>
    <p class="mt-2 text-theme-xs text-gray-500">Available = the partner's wallets (capital, interest, broker fees) minus capital still lent out to borrowers. Partners can request up to this.</p>
  @endif
@endsection
