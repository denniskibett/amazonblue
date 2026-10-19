{{-- Teller · disbursement queue: approved loans waiting for money to go out. --}}
@extends('layouts.app')

@php $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]'; @endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Disbursement queue', 'sub' => 'Approved loans waiting for money to go out, oldest approval first.'])

  <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="{{ $card }}">
      <p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Waiting</p>
      <p class="mt-1 font-outfit text-2xl font-semibold text-az-ink-blue dark:text-white/90">{{ $loans->total() }}</p>
      <p class="text-theme-sm text-gray-500"><x-staff.money :amount="$waitingTotal" tone="muted" /></p>
    </div>
    <div class="{{ $card }}">
      <p class="text-theme-xs font-semibold uppercase tracking-wide text-gray-500">Disbursed today</p>
      <p class="mt-1 font-outfit text-2xl font-semibold text-az-ink-blue dark:text-white/90">{{ $todayCount }}</p>
      <p class="text-theme-sm text-gray-500"><x-staff.money :amount="$todayTotal" tone="muted" /></p>
    </div>
    <div class="{{ $card }} flex flex-col justify-center gap-2">
      @can('create disbursements')
        <a href="{{ route('staff.end-of-day') }}" class="text-theme-sm font-medium text-brand-500 hover:underline">My end of day →</a>
      @endcan
      <a href="{{ route('disbursements.index') }}" class="text-theme-sm font-medium text-brand-500 hover:underline">All disbursements →</a>
    </div>
  </div>

  @cannot('create disbursements')
    <x-staff.alert type="info" class="mb-6" title="Read-only">You can see the queue but not disburse (needs the “create disbursements” permission).</x-staff.alert>
  @endcannot

  @if ($loans->isEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <x-staff.empty title="Queue is empty" message="No approved loans are waiting. Loans appear here once a manager approves them.">
        @can('view loans')<a href="{{ route('staff.approvals') }}" class="text-theme-sm font-medium text-brand-500 hover:underline">Go to approvals</a>@endcan
      </x-staff.empty>
    </div>
  @else
    <x-staff.table caption="Approved loans" :headers="['Loan', 'Borrower', 'Product', ['Principal', 'num'], ['Loan amount', 'num'], 'Limit', 'Approved', '']">
      @foreach ($loans as $loan)
        @php $ra = $loan->getLatestRiskAssessment(); @endphp
        <tr>
          <td><a class="font-medium text-brand-500 hover:underline" href="{{ route('users.loans.show', [$loan->user_id, $loan->id]) }}">#{{ $loan->id }}</a></td>
          <td><span class="font-medium text-gray-800 dark:text-white/90">{{ $loan->user->name ?? '—' }}</span><span class="block text-theme-xs text-gray-500">{{ $loan->user->phone ?? '' }}</span></td>
          <td>{{ $loan->loanType->name ?? '—' }}</td>
          <td class="num">@if ($loan->original_amount)<x-staff.money :amount="$loan->original_amount" />@else — @endif</td>
          <td class="num"><x-staff.money :amount="$loan->amount" /></td>
          <td>@if ($ra && $ra->limit_amount !== null)<x-staff.money :amount="$ra->limit_amount" tone="muted" />@else — @endif</td>
          <td class="text-theme-xs text-gray-500">{{ $loan->updated_at?->diffForHumans(short: true) }}</td>
          <td class="text-right">
            @can('create disbursements')
              <a href="{{ route('staff.disbursements.disburse', $loan) }}" class="inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600">Disburse</a>
            @endcan
          </td>
        </tr>
      @endforeach
    </x-staff.table>
    <div class="mt-4">{{ $loans->links() }}</div>
  @endif
@endsection
