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
          <div class="ml-auto mt-1 flex w-full max-w-36 items-center gap-2">
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

  {{-- collected per officer per month (owner, 23 Oct 2026), read-only --}}
  @php
    $perfMonth = rescue(fn () => \Illuminate\Support\Carbon::createFromFormat('Y-m', (string) request('month', now()->format('Y-m')))->startOfMonth(), now()->startOfMonth(), false);
    $perf = \App\Support\CollectorPerformance::forMonth($perfMonth);
    $months = collect(range(0, 11))->mapWithKeys(fn ($i) => [now()->startOfMonth()->subMonthsNoOverflow($i)->format('Y-m') => now()->startOfMonth()->subMonthsNoOverflow($i)->format('F Y')]);
  @endphp
  <div class="mt-6">
    <div id="perf-tools" class="hidden">
      <form method="GET" action="{{ route('staff.collections') }}" class="flex items-center gap-2">
        <input type="hidden" name="view" value="{{ $view }}">
        <label for="perf-month" class="sr-only">Month</label>
        <select id="perf-month" name="month" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()"
          class="h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
          @foreach ($months as $k => $label)<option value="{{ $k }}" @selected($k === $perfMonth->format('Y-m'))>{{ $label }}</option>@endforeach
        </select>
      </form>
    </div>
    <x-staff.table :title="'Collected per officer · ' . $perfMonth->format('F Y')" caption="Collected per officer" data-table-buttons="#perf-tools > *"
      :headers="['Officer', ['Open cases', 'num'], ['Contacts · actions', 'num'], ['Promises', 'num'], 'Promises kept', ['Collected', 'num']]">
      @forelse ($perf as $o)
        <tr>
          <td data-sort-value="{{ $o->name }}">
            @if ($o->id)
              <x-staff.person :name="$o->name" :id="$o->id" :sub="$o->payments . ' ' . \Illuminate\Support\Str::plural('payment', $o->payments) . ' in the month'" />
            @else
              <x-staff.person name="Not assigned" sub="cases with no officer" />
            @endif
          </td>
          <td class="num">{{ $o->open_cases }}</td>
          <td class="num" data-sort-value="{{ $o->actions }}">{{ $o->contacts }} <span class="text-gray-400">·</span> {{ $o->actions }}</td>
          <td class="num" data-sort-value="{{ $o->promised }}">{{ $o->promises }}<span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ number_format($o->promised, 2) }}</span></td>
          <td data-sort-value="{{ $o->kept_rate ?? -1 }}">
            @if ($o->kept_rate === null)
              <span class="text-theme-xs text-gray-400">none due yet</span>
            @else
              <div class="flex items-center gap-2">
                <div class="h-1.5 w-20 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-full rounded-full {{ $o->kept_rate >= 70 ? 'bg-success-500' : ($o->kept_rate >= 40 ? 'bg-amber-500' : 'bg-error-500') }}" style="width: {{ $o->kept_rate }}%"></div></div>
                <span class="text-theme-xs text-gray-600 dark:text-gray-400">{{ $o->kept }} of {{ $o->due_promises }} · {{ $o->kept_rate }}%</span>
              </div>
            @endif
          </td>
          <td class="num" data-sort-value="{{ $o->collected }}"><x-staff.money :amount="$o->collected" class="font-semibold text-gray-800 dark:text-white/90" /></td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="6"><x-staff.empty title="No collections work in {{ $perfMonth->format('F Y') }}" message="Assign cases to officers and log contacts on the case pages; their figures show here." /></td></tr>
      @endforelse
    </x-staff.table>
    <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">Collected = cash repayments in the month on loans with a recovery case, credited to the officer the case is assigned to now. Rollovers, discounts and write-offs are not counted. A promise is kept when the cash paid by the promised date covers it.</p>
  </div>

  <p class="mt-4 text-theme-xs text-gray-500 dark:text-gray-400">To restructure a loan, use Payment plan / forbearance or Extend on the loan page.</p>
@endsection
