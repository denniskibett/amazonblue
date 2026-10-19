{{-- Collections officer · queue. Read-only worklist; the work happens on the existing case pages (cases.*). --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]';
  $tab = 'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-theme-sm font-medium';
  $on = 'bg-white text-gray-900 shadow-theme-xs dark:bg-gray-800 dark:text-white';
  $off = 'text-gray-500 hover:text-gray-700 dark:text-gray-400';
  $tones = ['text-az-apricot-d', 'text-az-rose-d', 'text-az-rose-d', 'text-az-ink-blue dark:text-white/90'];
  $outcomes = ['promise_to_pay' => 'Promise to pay', 'no_answer' => 'No answer', 'wrong_number' => 'Wrong number'];
@endphp

@section('content')
  @include('staff.desk._header', [
    'title' => 'Collections',
    'sub' => 'Overdue, defaulted and recovery loans, and every open recovery case. Most days late first.',
    'actions' => auth()->user()->can('view recovery cases') ? '<a href="' . route('cases.index') . '" class="text-theme-sm font-medium text-brand-500 hover:underline">All recovery cases →</a>' : null,
  ])

  <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ($buckets as $label => $set)
      <div class="{{ $card }}">
        <p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
        <p class="mt-1 font-outfit text-2xl font-semibold {{ $tones[$loop->index] }}">{{ $set->count() }}</p>
        <p class="text-theme-sm text-gray-500"><x-staff.money :amount="$set->sum(fn ($r) => (float) $r->loan->amount)" tone="muted" /></p>
      </div>
    @endforeach
  </div>

  <div class="mb-4 inline-flex gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-900" role="tablist">
    @foreach (['all' => 'All', 'mine' => 'Assigned to me', 'unassigned' => 'Unassigned'] as $key => $label)
      <a href="{{ route('staff.collections', ['view' => $key]) }}" role="tab" aria-selected="{{ $view === $key ? 'true' : 'false' }}" class="{{ $tab }} {{ $view === $key ? $on : $off }}">{{ $label }}</a>
    @endforeach
  </div>

  @if ($rows->isEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <x-staff.empty title="Nothing to work" message="{{ $view === 'mine' ? 'No open recovery cases are assigned to you.' : 'No loans or cases in this list.' }}" />
    </div>
  @else
    <x-staff.table caption="Collections worklist" :headers="['Borrower', 'Loan', ['Amount · repaid', 'num'], ['Days late', 'num'], 'Case', 'Last action', '']">
      @foreach ($rows as $r)
        <tr>
          <td><span class="font-medium text-gray-800 dark:text-white/90">{{ $r->loan->user->name ?? '—' }}</span><span class="block text-theme-xs text-gray-500">{{ $r->loan->user->phone ?? '' }}</span></td>
          <td><a class="text-brand-500 hover:underline" href="{{ route('users.loans.show', [$r->loan->user_id, $r->loan->id]) }}">#{{ $r->loan->id }}</a> <x-staff.status-badge :status="$r->loan->status" class="ml-1" /><span class="block text-theme-xs text-gray-500">{{ \Illuminate\Support\Str::limit($r->loan->loanType->name ?? '', 22) }}</span></td>
          <td class="num"><x-staff.money :amount="$r->loan->amount" /><span class="block text-theme-xs text-gray-500">repaid <x-staff.money :amount="$r->repaid" :currency="false" tone="muted" /></span></td>
          <td class="num"><span @class(['font-semibold', 'text-az-rose-d' => $r->days > 30, 'text-az-apricot-d' => $r->days > 0 && $r->days <= 30])>{{ $r->days }}</span></td>
          <td>
            @if ($r->case)
              <span class="font-medium">{{ $r->case->case_number }}</span>
              <span class="block text-theme-xs text-gray-500">{{ $r->case->status->name ?? '' }} · {{ $r->case->assignedTo->name ?? 'unassigned' }}</span>
            @else
              <span class="text-theme-xs text-gray-500">No case</span>
            @endif
          </td>
          <td class="text-theme-xs">
            @if ($r->last)
              {{ \Illuminate\Support\Carbon::parse($r->last->action_date)->format('j M') }} · {{ $outcomes[$r->last->outcome] ?? ucfirst(str_replace('_', ' ', (string) $r->last->outcome)) }}
              @if ($r->last->promised_date)<span class="block text-gray-500">Promised {{ \Illuminate\Support\Carbon::parse($r->last->promised_date)->format('j M') }}</span>@endif
            @elseif ($r->case && $r->case->last_contact_date)
              Contact {{ \Illuminate\Support\Carbon::parse($r->case->last_contact_date)->format('j M') }}
            @else
              <span class="text-gray-500">—</span>
            @endif
          </td>
          <td class="text-right">
            @if ($r->case)
              <a href="{{ route('cases.show', $r->case) }}" class="inline-flex rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600">{{ auth()->user()->can('edit recovery cases') ? 'Work case' : 'View case' }}</a>
            @else
              @can('create recovery cases')
                <a href="{{ route('cases.create', ['loan_id' => $r->loan->id]) }}" class="inline-flex rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Open case</a>
              @endcan
            @endif
          </td>
        </tr>
      @endforeach
    </x-staff.table>
  @endif

  <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <x-staff.placeholder dependency="restructure flow" title="Restructure">Restructuring needs the Stage 4 loan engine (new schedule, approvals). Use the loan page's payment plan or forbearance for now.</x-staff.placeholder>
    <x-staff.placeholder dependency="collector performance" title="Performance">Collected per officer per month needs collections to post through one ledger (Stage 4).</x-staff.placeholder>
  </div>
@endsection
