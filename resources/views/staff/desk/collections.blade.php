{{-- Collections officer · queue. Read-only worklist; the work happens on the existing case pages (cases.*). --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]';
  $tabCls = '-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-theme-sm font-medium';
  $tabOn = 'border-brand-500 text-brand-500 dark:border-brand-400 dark:text-brand-400';
  $tabOff = 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200';
  $tones = ['text-az-apricot-d', 'text-az-rose-d', 'text-az-rose-d', 'text-az-ink-blue dark:text-white/90'];
  $outcomes = ['promise_to_pay' => 'Promise to pay', 'no_answer' => 'No answer', 'wrong_number' => 'Wrong number'];
@endphp

@section('content')
  @include('staff.desk._header', [
    'title' => 'Collections',
    'sub' => 'Overdue, defaulted and recovery loans, and every open recovery case. Most days late first.',
    'actions' => auth()->user()->can('view recovery cases') ? '<a href="' . route('cases.index') . '" class="text-theme-sm font-medium text-brand-500 hover:underline">All recovery cases →</a>' : null,
  ])

  <x-staff.table :title="['all' => 'All loans to collect', 'mine' => 'Assigned to me', 'unassigned' => 'Not assigned yet'][$view]" caption="Collections worklist"
    :headers="['Borrower', 'Loan', ['Amount · repaid', 'num'], ['Days late', 'num'], 'Case', 'Last action', '']">
    <x-slot:tabs>
      @foreach (['all' => 'All', 'mine' => 'Assigned to me', 'unassigned' => 'Unassigned'] as $key => $label)
        <a href="{{ route('staff.collections', ['view' => $key]) }}" role="tab" aria-selected="{{ $view === $key ? 'true' : 'false' }}" class="{{ $tabCls }} {{ $view === $key ? $tabOn : $tabOff }}">{{ $label }}</a>
      @endforeach
    </x-slot:tabs>
    @forelse ($rows as $r)
      @php
        $pct = (float) $r->loan->amount > 0 ? min(100, round($r->repaid / (float) $r->loan->amount * 100)) : 0;
        $lateTone = $r->days > 90 ? 'red' : ($r->days > 30 ? 'red' : ($r->days > 0 ? 'amber' : 'green'));
      @endphp
      <tr>
        <td data-sort-value="{{ $r->loan->user->name ?? '' }}"><x-staff.person :user="$r->loan->user" :sub="$r->loan->user->phone ?? null" /></td>
        <td data-filter-value="{{ ucfirst($r->loan->status) }}">
          <div class="flex items-center gap-2">
            <a class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" href="{{ route('users.loans.show', [$r->loan->user_id, $r->loan->id]) }}">Loan #{{ $r->loan->id }}</a>
            <x-staff.status-badge :status="$r->loan->status" />
          </div>
          <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($r->loan->loanType->name ?? '', 26) }}</span>
        </td>
        <td class="num" data-sort-value="{{ (float) $r->loan->amount }}">
          <x-staff.money :amount="$r->loan->amount" class="text-gray-800 dark:text-white/90" />
          <div class="ml-auto mt-1 flex w-36 items-center gap-2">
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-full rounded-full bg-success-500" style="width: {{ $pct }}%"></div></div>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $pct }}%</span>
          </div>
          <span class="block text-theme-xs text-gray-500 dark:text-gray-400">repaid {{ number_format($r->repaid, 2) }}</span>
        </td>
        <td class="num" data-sort-value="{{ $r->days }}"><x-staff.pill :tone="$lateTone">{{ $r->days }} {{ $r->days === 1 ? 'day' : 'days' }}</x-staff.pill></td>
        <td data-filter-value="{{ $r->case ? ($r->case->status->name ?? 'Open') : 'No case' }}">
          @if ($r->case)
            <span class="font-medium text-gray-800 dark:text-white/90">{{ $r->case->case_number }}</span>
            <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $r->case->status->name ?? '' }} · {{ $r->case->assignedTo->name ?? 'unassigned' }}</span>
          @else
            <x-staff.pill tone="gray">No case</x-staff.pill>
          @endif
        </td>
        <td class="text-theme-xs text-gray-600 dark:text-gray-400">
          @if ($r->last)
            <span class="text-theme-sm text-gray-700 dark:text-gray-300">{{ $outcomes[$r->last->outcome] ?? ucfirst(str_replace('_', ' ', (string) $r->last->outcome)) }}</span>
            <span class="block">{{ \Illuminate\Support\Carbon::parse($r->last->action_date)->format('j M') }}@if ($r->last->promised_date) · promised {{ \Illuminate\Support\Carbon::parse($r->last->promised_date)->format('j M') }}@endif</span>
          @elseif ($r->case && $r->case->last_contact_date)
            Contact {{ \Illuminate\Support\Carbon::parse($r->case->last_contact_date)->format('j M') }}
          @else
            —
          @endif
        </td>
        <td class="text-right" data-actions>
          @if ($r->case)
            <a href="{{ route('cases.show', $r->case) }}" class="inline-flex rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600">{{ auth()->user()->can('edit recovery cases') ? 'Work case' : 'View case' }}</a>
          @else
            @can('create recovery cases')
              <a href="{{ route('cases.create', ['loan_id' => $r->loan->id]) }}" class="inline-flex rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Open case</a>
            @endcan
          @endif
        </td>
      </tr>
    @empty
      <tr data-table-skip><td colspan="7"><x-staff.empty title="Nothing to work" message="{{ $view === 'mine' ? 'No open recovery cases are assigned to you.' : 'No loans or cases in this list.' }}" /></td></tr>
    @endforelse
  </x-staff.table>

  <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <x-staff.placeholder dependency="restructure flow" title="Restructure">Restructuring needs the Stage 4 loan engine (new schedule, approvals). Use the loan page's payment plan or forbearance for now.</x-staff.placeholder>
    <x-staff.placeholder dependency="collector performance" title="Performance">Collected per officer per month needs collections to post through one ledger (Stage 4).</x-staff.placeholder>
  </div>
@endsection
