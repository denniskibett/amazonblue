{{-- Teller · disbursement queue: approved loans waiting for money to go out. --}}
@extends('layouts.app')

@php $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]'; @endphp

@section('content')
  @php
    $btn = 'inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
    $links = (auth()->user()->can('create disbursements') ? '<a href="' . route('staff.end-of-day') . '" class="' . $btn . '">My end of day</a>' : '')
      . '<a href="' . route('disbursements.index') . '" class="' . $btn . '">Disbursements register</a>';
  @endphp
  @include('staff.desk._header', ['title' => 'Disbursement queue', 'sub' => 'Approved loans waiting for money to go out, oldest approval first.', 'actions' => $links])

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
    <x-staff.table title="Approved, waiting to be sent" caption="Approved loans" :headers="['Loan', 'Borrower', 'Product', ['Principal', 'num'], ['Loan amount', 'num'], 'Limit', 'Approved', '']">
      @foreach ($loans as $loan)
        @php $ra = $loan->getLatestRiskAssessment(); @endphp
        <tr>
          <td><a class="font-medium text-brand-500 hover:underline" href="{{ route('users.loans.show', [$loan->user_id, $loan->id]) }}">#{{ $loan->id }}</a></td>
          <td><span class="font-medium text-gray-800 dark:text-white/90 cursor-pointer hover:text-brand-500" data-user-peek="{{ $loan->user_id }}">{{ $loan->user->name ?? '—' }}</span><span class="block text-theme-xs text-gray-500">{{ $loan->user->phone ?? '' }}</span></td>
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
  @endif
@endsection
