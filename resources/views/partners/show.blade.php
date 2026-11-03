{{-- resources/views/partners/show.blade.php --}}
@extends('layouts.app')

@section('content')
@php
  // the partner page (owner, 3 Nov 2026): who they are, their four wallets, then one tabbed card for money and loans,
  // the journal, details and banking, and the portal login
  $short = \Illuminate\Support\Facades\DB::table('brokers')->where('user_id', $partner->user_id)->exists() && $partner->user_id;
  $long = \App\Models\Investment::whereHas('fundings', fn ($q) => $q->where('partner_id', $partner->id))->exists();
  $words = collect(preg_split('/\s+/', trim($partner->name)))->filter();
  $initials = mb_strtoupper($words->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
  $tx = collect($data['transactions'] ?? []);
  $typeTone = fn ($t) => match ($t) { 'contribution', 'broker_fee_earned' => 'green', 'withdrawal', 'payout', 'broker_fee_paid' => 'red', 'profit_distribution' => 'purple', 'penalty_share', 'bonus' => 'amber', default => 'blue' };
  $ghost = 'inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
  $primary = 'inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600';
  $actions = '<a href="' . route('partners.index') . '" class="' . $ghost . '">Back to partners</a>';
@endphp
  @include('staff.desk._header', ['title' => $partner->name, 'sub' => 'Partner #' . $partner->id . ' · since ' . ($partner->created_at?->format('j M Y') ?? '—'), 'metrics' => false, 'actions' => $actions])

  {{-- profile --}}
  <section class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:p-6">
      <span class="flex size-16 shrink-0 items-center justify-center rounded-full bg-brand-50 font-outfit text-xl font-semibold text-brand-500 dark:bg-brand-500/15">{{ $initials }}</span>
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $partner->name }}</h2>
          <x-staff.pill :tone="['active' => 'green', 'suspended' => 'red'][$partner->status] ?? 'gray'" dot>{{ ucfirst($partner->status) }}</x-staff.pill>
          <x-staff.pill tone="gray">{{ ucfirst($partner->type) }}</x-staff.pill>
          @if ($long)<x-staff.pill tone="brand">Long-term · funds lines</x-staff.pill>@endif
          @if ($short)<x-staff.pill tone="purple">Short-term · introduces borrowers</x-staff.pill>@endif
        </div>
        <p class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-theme-sm text-gray-500 dark:text-gray-400">
          <span>{{ $partner->email }}</span>@if ($partner->phone)<span>{{ $partner->phone }}</span>@endif
          @if ($partner->user)<span class="text-success-600">Portal login: {{ $partner->user->email }}</span>@else<span class="text-warning-600">No portal login</span>@endif
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        @can('edit partners')<a href="?tab=money#record-capital" class="{{ $ghost }}">Record money</a>@endcan
        <button type="button" @click="window.dispatchEvent(new CustomEvent('edit-partner', { detail: { partner: @js($data) } }))" class="{{ $primary }}">Edit partner</button>
        <button type="button" @click="window.dispatchEvent(new CustomEvent('delete-partner', { detail: { id: {{ $partner->id }}, name: @js($partner->name) } }))"
          class="inline-flex items-center rounded-lg border border-error-300 px-4 py-2.5 text-theme-sm font-medium text-error-600 hover:bg-error-50 dark:border-error-500/40 dark:hover:bg-error-500/10">Delete</button>
      </div>
    </div>
    <dl class="grid grid-cols-2 border-t border-gray-100 dark:border-gray-800 sm:grid-cols-5">
      @foreach ([['Introducer rate', ($data['broker_rate'] ?? 40) . '%'], ['Profit share', ($partner->profit_share_rate ?? 0) . '%'], ['Max loan to value', ($partner->max_loan_to_value ?? 75) . '%'], ['Risk tolerance', ucfirst($partner->risk_tolerance ?? 'moderate')], ['Bank', $partner->bank_name ?: '—']] as $i => [$k, $v])
        <div class="px-5 py-4 sm:px-6 {{ $i ? 'border-l border-gray-100 dark:border-gray-800' : '' }}">
          <dt class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $k }}</dt>
          <dd class="mt-1 font-outfit text-theme-sm font-semibold text-gray-800 dark:text-white/90">{{ $v }}</dd>
        </div>
      @endforeach
    </dl>
  </section>

  {{-- the four wallets --}}
  <x-ui.metrics :items="[
    ['label' => 'Principal wallet', 'value' => 'KES ' . number_format($data['principal_balance'] ?? 0, 2), 'icon' => 'wallet', 'hint' => 'capital put in, not lent'],
    ['label' => 'Interest wallet', 'value' => 'KES ' . number_format($data['interest_balance'] ?? 0, 2), 'icon' => 'percent', 'hint' => 'earned on lines'],
    ['label' => 'Introducer fee wallet', 'value' => 'KES ' . number_format($data['broker_fee_balance'] ?? 0, 2), 'icon' => 'coins', 'hint' => 'from clients they brought'],
    ['label' => 'Investment tracker', 'value' => 'KES ' . number_format($data['tracker_balance'] ?? 0, 2), 'icon' => 'layers', 'hint' => 'lent out on loans'],
  ]" />

  <x-staff.tabs :tabs="['money' => 'Money, clients and loans', 'journal' => ['Journal', $tx->count()], 'details' => 'Details and banking', 'login' => ['Portal login', $partner->user ? 'on' : 'none']]">
    <x-staff.tab name="money">
      @include('partners._lines')
    </x-staff.tab>

    <x-staff.tab name="journal">
      <x-staff.table title="Every wallet posting, newest first" caption="Partner journal" :headers="['Date', 'Wallet', 'Type', ['Amount', 'num'], ['Balance after', 'num'], 'Reference']">
        @forelse ($tx as $t)
          <tr>
            <td data-sort-value="{{ $t['transaction_date'] ?? $t['created_at'] }}">{{ \Illuminate\Support\Carbon::parse($t['transaction_date'] ?? $t['created_at'])->format('j M Y') }}</td>
            <td><x-staff.pill tone="gray">{{ ['broker_fee' => 'introducer fee', 'investment_tracker' => 'tracker'][$t['wallet_slug']] ?? str_replace('_', ' ', $t['wallet_slug'] ?: 'principal') }}</x-staff.pill></td>
            <td data-filter-value="{{ ucfirst(str_replace('_', ' ', $t['type'])) }}"><x-staff.pill :tone="$typeTone($t['type'])">{{ \App\Services\PartnerPortfolio::TYPES[$t['type']] ?? ucfirst(str_replace('_', ' ', $t['type'])) }}</x-staff.pill></td>
            <td class="num" data-sort-value="{{ $t['amount'] }}"><x-staff.money :amount="$t['amount']" tone="auto" /></td>
            <td class="num"><x-staff.money :amount="$t['balance_after']" /></td>
            <td class="max-w-[260px] !whitespace-normal text-theme-xs text-gray-500 dark:text-gray-400">{{ $t['reference'] ?: '—' }}@if ($t['loan_id']) · loan #{{ $t['loan_id'] }}@endif</td>
          </tr>
        @empty
          <tr data-table-skip><td colspan="6"><x-staff.empty title="No postings yet" message="Money in, payouts and earnings show here as they are booked." /></td></tr>
        @endforelse
      </x-staff.table>
    </x-staff.tab>

    <x-staff.tab name="details">
      <div class="grid grid-cols-1 gap-x-8 gap-y-0 p-5 sm:p-6 lg:grid-cols-2">
        @foreach ([
          'Who' => [['Full name', $partner->name], ['Email', $partner->email], ['Phone', $partner->phone], ['Company', $partner->company_name], ['Registration number', $partner->registration_number], ['Tax ID', $partner->tax_id], ['Linked user', $partner->user?->name]],
          'Banking' => [['Account name', $partner->bank_account_name], ['Account number', $partner->bank_account_number], ['Bank', $partner->bank_name], ['SWIFT code', $partner->swift_code]],
        ] as $heading => $rows)
          <div>
            <h3 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">{{ $heading }}</h3>
            <dl class="divide-y divide-gray-100 dark:divide-gray-800">
              @foreach ($rows as [$k, $v])
                <div class="flex justify-between gap-4 py-2.5 text-theme-sm"><dt class="text-gray-500 dark:text-gray-400">{{ $k }}</dt><dd class="text-right font-medium text-gray-800 dark:text-white/90">{{ $v ?: '—' }}</dd></div>
              @endforeach
            </dl>
          </div>
        @endforeach
        @if ($partner->notes)
          <div class="lg:col-span-2"><h3 class="mb-1 mt-4 text-base font-semibold text-gray-800 dark:text-white/90">Notes</h3><p class="text-theme-sm text-gray-600 dark:text-gray-400">{{ $partner->notes }}</p></div>
        @endif
      </div>
    </x-staff.tab>

    <x-staff.tab name="login">
      @include('partners._login')
    </x-staff.tab>
  </x-staff.tabs>

<!-- Edit Modal -->
@include('partials.modal.partners-create-modal', ['users' => $users ?? []])

<!-- Delete Modal -->
@include('partials.modal.delete')

<!-- Alert Modal -->
@include('partials.modal.alert-modal')



<style>
[x-cloak] { display: none !important; }
</style>
@endsection