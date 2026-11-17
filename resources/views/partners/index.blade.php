{{-- resources/views/partners/index.blade.php --}}
{{-- Partners (owner, 16 Nov 2026; Figma "Partners redesign"): the cards, then one card with tablines (All, Funding lines,
     Introducers, Suspended) over the same table in the Loan approvals look. Rows come from PartnerController::partnerPayload. --}}
@extends('layouts.app')

@section('content')
@php
  $partners = collect($partners ?? []);
  $lineIds = \Illuminate\Support\Facades\DB::table('investment_fundings as f')->join('investments as i', 'i.id', '=', 'f.investment_id')
    ->whereIn('i.return_type', ['loan_share', 'fixed_return'])->distinct()->pluck('f.partner_id')->map(fn ($id) => (int) $id)->all();
  $introIds = \Illuminate\Support\Facades\DB::table('partners as p')->join('brokers as b', 'b.user_id', '=', 'p.user_id')->distinct()->pluck('p.id')->map(fn ($id) => (int) $id)->all();
  $rows = $partners->map(fn ($p) => $p + ['long' => in_array((int) $p['id'], $lineIds, true), 'short' => in_array((int) $p['id'], $introIds, true)]);
  $sets = [
    'all' => ['All', $rows],
    'lines' => ['Funding lines', $rows->where('long', true)],
    'introducers' => ['Introducers', $rows->where('short', true)],
    'suspended' => ['Suspended', $rows->whereIn('status', ['suspended', 'inactive'])],
  ];
@endphp
<div x-data @refresh-partners.window="location.reload()">
  <x-ui.page-header title="Partners" sub="Long-term partners (funding lines) and short-term partners (introduce borrowers), their capital and what they have earned.">
    @can('view partners')
      <a href="{{ route('staff.partners.numbers') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"><x-ui.icon name="chart" class="size-4" />Partner numbers</a>
    @endcan
    @can('create partners')
      <button type="button" @click="window.dispatchEvent(new CustomEvent('open-partner-create'))" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600"><x-ui.icon name="plus" class="size-4" />Add partner</button>
    @endcan
  </x-ui.page-header>
  <x-ui.page-metrics page="partners" />

  <x-staff.tabs class="mt-6" :tabs="collect($sets)->map(fn ($s) => [$s[0], $s[1]->count()])->all()">
    @foreach ($sets as $key => [$label, $set])
      <x-staff.tab :name="$key">
        <x-staff.table :caption="$label . ' partners'" :headers="['Partner', 'Type', ['Put in now', 'num'], ['Lent now', 'num'], ['Free', 'num'], ['Earned', 'num'], ['Available', 'num'], 'Status', '']">
          @forelse ($set as $p)
            {{-- put in now = the principal wallet (lending does not move it); lent now = capital out on loans (Partner::lentOut; the
                 investment_tracker wallet it read was never filled); free = the rest (owner, 17 Nov 2026) --}}
            @php $in = (float) $p['principal_balance']; $free = max(0, round($in - (float) $p['lent_out'], 2)); @endphp
            <tr data-partner="{{ $p['id'] }}">
              <td data-sort-value="{{ $p['name'] }}"><a href="{{ route('partners.show', $p['id']) }}" class="block hover:opacity-80"><x-staff.person :name="$p['name']" :sub="$p['phone'] ?: $p['email']" /></a></td>
              <td data-filter-value="{{ $p['long'] && $p['short'] ? 'Both' : ($p['long'] ? 'Long-term' : ($p['short'] ? 'Short-term' : ucfirst($p['type'] ?? ''))) }}">
                @if ($p['is_house'])<x-staff.pill tone="gray">House</x-staff.pill>
                @elseif ($p['long'] && $p['short'])<x-staff.pill tone="purple">Both</x-staff.pill>
                @elseif ($p['long'])<x-staff.pill tone="brand">Long-term</x-staff.pill>
                @elseif ($p['short'])<x-staff.pill tone="blue">Short-term</x-staff.pill>
                @else<x-staff.pill tone="gray">{{ ucfirst($p['type'] ?? '—') }}</x-staff.pill>@endif
              </td>
              <td class="num" data-sort-value="{{ $in }}">{{ $in ? number_format($in, 2) : '—' }}</td>
              <td class="num" data-sort-value="{{ $p['lent_out'] }}">{{ number_format((float) $p['lent_out'], 2) }}</td>
              <td class="num" data-sort-value="{{ $free }}">{{ number_format($free, 2) }}</td>
              <td class="num" data-sort-value="{{ $p['earned'] }}">{{ number_format((float) $p['earned'], 2) }}</td>
              <td class="num font-medium text-gray-800 dark:text-white/90" data-sort-value="{{ $p['available'] }}">{{ number_format((float) $p['available'], 2) }}</td>
              <td data-filter-value="{{ ucfirst($p['status'] ?? '') }}"><x-staff.pill :tone="['active' => 'green', 'suspended' => 'red'][$p['status']] ?? 'gray'" dot>{{ ucfirst($p['status'] ?? '—') }}</x-staff.pill></td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1">
                  <a href="{{ route('partners.show', $p['id']) }}" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-500 dark:hover:bg-white/5" title="View" aria-label="View {{ $p['name'] }}"><x-ui.icon name="eye" class="size-4" /></a>
                  @can('edit partners')<button type="button" @click="window.dispatchEvent(new CustomEvent('edit-partner', { detail: { partner: {{ \Illuminate\Support\Js::from($p) }} } }))" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand-500 dark:hover:bg-white/5" title="Edit" aria-label="Edit {{ $p['name'] }}"><x-ui.icon name="edit" class="size-4" /></button>@endcan
                  @can('delete partners')<button type="button" @click="window.dispatchEvent(new CustomEvent('delete-partner', { detail: { id: {{ (int) $p['id'] }}, name: {{ \Illuminate\Support\Js::from($p['name']) }} } }))" class="rounded-lg p-2 text-gray-500 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10" title="Delete" aria-label="Delete {{ $p['name'] }}"><x-ui.icon name="trash" class="size-4" /></button>@endcan
                </div>
              </td>
            </tr>
          @empty
            <tr data-table-skip><td colspan="9"><x-staff.empty title="No partners here" message="Partners show here once they are added." /></td></tr>
          @endforelse
        </x-staff.table>
      </x-staff.tab>
    @endforeach
  </x-staff.tabs>

  <p class="mt-3 text-theme-xs text-gray-500">Put in now = the capital they sent, less what was paid back to them; Lent now = the part out on loans; Free = the rest. Available = the partner's wallets (capital, interest, introducer fees) minus capital still lent out; partners can ask for up to this. Payouts they ask for, and line payouts, wait on <a class="underline" href="{{ route('staff.requests', ['tab' => 'partners']) }}">Requests</a>: one person prepares{{ config('lending.approvals.enabled') ? ', a second approves on Second sign-offs' : '' }}, and payouts already made post on the day they were paid.</p>

  @include('partials.modal.partners-create-modal', ['users' => $users ?? []])
  @include('partials.modal.delete')
  @include('partials.modal.alert-modal')
</div>
@endsection
