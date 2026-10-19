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
    <x-staff.table caption="Partner balances" :headers="$headers">
      @foreach ($rows as $s)
        <tr>
          <td class="font-medium text-gray-800 dark:text-white/90">{{ $s['partner']->name }}@if ($s['partner']->is_house_account) <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-theme-xs text-gray-500 dark:bg-gray-800">house</span>@endif</td>
          @foreach (array_keys($wallets) as $slug)
            <td class="num"><x-staff.money :amount="$s['wallets'][$slug]['balance'] ?? 0" :currency="false" /></td>
          @endforeach
          <td class="num font-semibold"><x-staff.money :amount="$s['available']" /></td>
          <td class="num"><x-staff.money :amount="$s['earned']" tone="muted" /></td>
          <td class="num"><x-staff.money :amount="$s['committed']" tone="muted" /></td>
          <td class="text-right">@can('view partners')<a class="text-theme-xs font-medium text-brand-500 hover:underline" href="{{ route('partners.show', $s['partner']) }}">Open</a>@endcan</td>
        </tr>
      @endforeach
    </x-staff.table>
    <p class="mt-2 text-theme-xs text-gray-500">Available = the partner's wallets (capital, interest, broker fees) minus capital still lent out to borrowers. Partners can request up to this.</p>
  @endif
@endsection
