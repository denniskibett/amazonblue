{{-- Loan approval queue (Credit analyst · queue / decision). Approve = existing PUT loans.update (status approved);
     reject = existing PATCH loans.reject. The 4Cs assessment is read-only (loan_risk_assessments). --}}
@extends('layouts.app')

@php
  $tab = 'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-theme-sm font-medium';
  $on = 'bg-white text-gray-900 shadow-theme-xs dark:bg-gray-800 dark:text-white';
  $off = 'text-gray-500 hover:text-gray-700 dark:text-gray-400';
  $canApprove = auth()->user()->can('approve loans');
  $canReject = auth()->user()->can('edit loans') && $canApprove;
  $back = route('staff.approvals', ['status' => $status]);
@endphp

@section('content')
  @include('staff.desk._header', [
    'title' => 'Loan approvals',
    'sub' => 'Applications waiting for a decision, with the 4Cs score and limit from the credit assessment.',
  ])
  @include('staff.desk._ajax')

  @unless ($canApprove)
    <x-staff.alert type="info" class="mb-6" title="Read-only">You can see the queue but not decide on loans (needs the “approve loans” permission).</x-staff.alert>
  @endunless

  <div class="mb-4 inline-flex gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-900" role="tablist">
    @foreach (['pending' => 'Waiting for decision', 'approved' => 'Approved, not disbursed'] as $key => $label)
      <a href="{{ route('staff.approvals', ['status' => $key]) }}" role="tab" aria-selected="{{ $status === $key ? 'true' : 'false' }}" class="{{ $tab }} {{ $status === $key ? $on : $off }}">
        {{ $label }} <span class="rounded-full bg-gray-200 px-2 text-theme-xs dark:bg-gray-700">{{ $counts[$key]->n ?? 0 }}</span>
      </a>
    @endforeach
  </div>

  @if ($loans->isEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <x-staff.empty title="Nothing waiting" message="{{ $status === 'pending' ? 'No applications are waiting for a decision.' : 'No approved loans are waiting to be disbursed.' }}" />
    </div>
  @else
    <x-staff.table caption="Loans {{ $status }}" :headers="['Loan', 'Borrower', ['Amount · product', 'num'], '4Cs score', ['Limit', 'num'], 'Recommendation', '']">
      @foreach ($loans as $loan)
        @php
          $ra = $loan->getLatestRiskAssessment();
          $over = $ra && $ra->limit_amount !== null && (float) $loan->amount > (float) $ra->limit_amount;
          $reasonOk = mb_strlen(trim((string) $loan->reason)) >= 10;
        @endphp
        <tr>
          <td><a class="font-medium text-brand-500 hover:underline" href="{{ route('users.loans.show', [$loan->user_id, $loan->id]) }}" title="Open loan">#{{ $loan->id }}</a>
            <span class="block text-theme-xs text-gray-500">{{ ($status === 'pending' ? $loan->created_at : $loan->updated_at)?->diffForHumans(short: true) }}</span></td>
          <td>
            <span class="font-medium text-gray-800 dark:text-white/90">{{ $loan->user->name ?? '—' }}</span>
            <span class="block text-theme-xs text-gray-500">{{ $loan->user->phone ?? $loan->user->email ?? '' }}</span>
          </td>
          <td class="num"><x-staff.money :amount="$loan->amount" />
            <span class="block font-figtree text-theme-xs text-gray-500">{{ $loan->loanType->name ?? '—' }}</span></td>
          <td>
            @if ($ra)
              <span class="font-outfit text-base font-semibold text-gray-800 dark:text-white/90">{{ $ra->overall_score ?? '—' }}</span>
              <span class="ml-1 text-theme-xs text-gray-500" title="Character · Capacity · Capital · Conditions">{{ $ra->character_score ?? '–' }} · {{ $ra->capacity_score ?? '–' }} · {{ $ra->capital_score ?? '–' }} · {{ $ra->conditions_score ?? '–' }}</span>
              <span class="block text-theme-xs text-gray-500">{{ $ra->risk_category ?? '' }}{{ $ra->source === 'manual' ? ' · staff' : ' · system' }}</span>
            @else
              <span class="text-theme-xs text-gray-500">Not assessed</span>
            @endif
          </td>
          <td class="num">
            @if ($ra && $ra->limit_amount !== null)
              <x-staff.money :amount="$ra->limit_amount" />
              @if ($over)<span class="block text-theme-xs font-medium text-az-rose-d">Above limit</span>@endif
            @else — @endif
          </td>
          <td class="max-w-[200px] !whitespace-normal text-theme-xs"><span class="line-clamp-2" title="{{ $ra->recommendation ?? '' }}">{{ $ra->recommendation ?? '—' }}</span></td>
          <td class="text-right">
            <div class="flex justify-end gap-2">
              @if ($status === 'pending' && $canApprove)
                <x-desk.dialog id="approve-{{ $loan->id }}" :action="route('loans.update', $loan)" method="PUT" title="Approve loan #{{ $loan->id }}?"
                  trigger="Approve" confirm="Approve loan" :done="route('staff.approvals', ['done' => 'approved'])"
                  trigger-class="inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600"
                  intro="{{ $loan->user->name ?? 'Borrower' }} · KES {{ number_format($loan->amount, 2) }}. This uses the existing loan update (status → approved); nothing else on the loan changes.">
                  <input type="hidden" name="status" value="approved">
                  <input type="hidden" name="loan_type_id" value="{{ $loan->loan_type_id }}">
                  <input type="hidden" name="amount" value="{{ $loan->amount }}">
                  <input type="hidden" name="borrow_date" value="{{ optional($loan->borrow_date ? \Illuminate\Support\Carbon::parse($loan->borrow_date) : $loan->created_at)->toDateString() }}">
                  <input type="hidden" name="broker_status" value="{{ (int) $loan->broker_status }}">
                  @if ($loan->consent)<input type="hidden" name="consent" value="1">@endif
                  @if ($over)<x-staff.alert type="warn">The amount is above the assessed limit of KES {{ number_format($ra->limit_amount, 2) }}.</x-staff.alert>@endif
                  @if ($reasonOk)
                    <input type="hidden" name="reason" value="{{ $loan->reason }}">
                  @else
                    <x-staff.field name="reason" id="reason-{{ $loan->id }}" label="Loan purpose" type="textarea" :value="$loan->reason" required
                      hint="The loan has no purpose of 10+ characters yet; the existing loan update needs one." />
                  @endif
                </x-desk.dialog>
              @endif
              @if ($status === 'approved')
                @can('create disbursements')
                  <a href="{{ route('staff.disbursements.disburse', $loan) }}" class="inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600">Disburse</a>
                @endcan
              @endif
              @if ($canReject)
                <x-desk.dialog id="reject-{{ $loan->id }}" :action="route('loans.reject', $loan)" method="PATCH" title="Reject loan #{{ $loan->id }}?"
                  trigger="Reject" confirm="Reject loan" tone="danger" :done="route('staff.approvals', ['status' => $status, 'done' => 'rejected'])"
                  intro="The reason is saved on the loan. The borrower sees the application as rejected.">
                  <x-staff.field name="rejection_reason" id="rr-{{ $loan->id }}" label="Reason" type="textarea" required minlength="5" maxlength="1000" />
                </x-desk.dialog>
              @endif
            </div>
          </td>
        </tr>
      @endforeach
    </x-staff.table>
    <div class="mt-4">{{ $loans->links() }}</div>
  @endif

  <x-staff.placeholder class="mt-6" dependency="approval limits and maker-checker" title="Approval limits">
    Today anyone with “approve loans” can approve any amount, and the person who approves can also disburse. Limits per role and a second approver are Stage 4.
  </x-staff.placeholder>
@endsection
