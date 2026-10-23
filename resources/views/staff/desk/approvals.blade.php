{{-- Loan approval queue (Credit analyst · queue / decision). Approve = existing PUT loans.update (status approved);
     reject = existing PATCH loans.reject. The 4Cs assessment is read-only (loan_risk_assessments). --}}
@extends('layouts.app')

@php
  $tabCls = '-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-theme-sm font-medium';
  $tabOn = 'border-brand-500 text-brand-500 dark:border-brand-400 dark:text-brand-400';
  $tabOff = 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200';
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

  <x-staff.table :title="$status === 'pending' ? 'Waiting for a decision' : 'Approved, not disbursed'" caption="Loans {{ $status }}"
    :headers="['Borrower', 'Loan · product', ['Amount', 'num'], '4Cs assessment', 'Recommendation', 'Waiting', '']">
    <x-slot:tabs>
      @foreach (['pending' => 'Waiting for decision', 'approved' => 'Approved, not disbursed'] as $key => $label)
        <a href="{{ route('staff.approvals', ['status' => $key]) }}" role="tab" aria-selected="{{ $status === $key ? 'true' : 'false' }}"
          class="{{ $tabCls }} {{ $status === $key ? $tabOn : $tabOff }}">
          {{ $label }} <span class="rounded-full px-2 text-theme-xs {{ $status === $key ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-gray-100 dark:bg-gray-800' }}">{{ $counts[$key]->n ?? 0 }}</span>
        </a>
      @endforeach
    </x-slot:tabs>
    @forelse ($loans as $loan)
        @php
          $ra = $loan->getLatestRiskAssessment();
          $over = $ra && $ra->limit_amount !== null && (float) $loan->amount > (float) $ra->limit_amount;
          $reasonOk = mb_strlen(trim((string) $loan->reason)) >= 10;
          $score = $ra?->overall_score;
          $scoreTone = $score === null ? 'gray' : ($score >= 70 ? 'green' : ($score >= 50 ? 'amber' : 'red'));
          $since = $status === 'pending' ? $loan->created_at : $loan->updated_at;
        @endphp
        <tr>
          <td data-sort-value="{{ $loan->user->name ?? '' }}"><x-staff.person :user="$loan->user" :sub="$loan->user->phone ?? $loan->user->email ?? null" /></td>
          <td data-filter-value="{{ $loan->loanType->name ?? '' }}">
            <a class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" href="{{ route('users.loans.show', [$loan->user_id, $loan->id]) }}" title="Open loan">Loan #{{ $loan->id }}</a>
            <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $loan->loanType->name ?? '—' }}</span>
          </td>
          <td class="num" data-sort-value="{{ (float) $loan->amount }}">
            <x-staff.money :amount="$loan->amount" class="text-gray-800 dark:text-white/90" />
            <span class="mt-0.5 flex flex-wrap items-center justify-end gap-1 text-theme-xs text-gray-500 dark:text-gray-400">
              @if ($ra && $ra->limit_amount !== null)
                @if ($over)<x-staff.pill tone="red">Above limit</x-staff.pill>@endif
                limit {{ number_format($ra->limit_amount, 2) }}
              @else no limit set @endif
            </span>
          </td>
          <td data-sort-value="{{ $score ?? -1 }}">
            @if ($ra)
              <div class="flex items-center gap-2">
                <x-staff.pill :tone="$scoreTone" class="font-outfit !text-theme-sm">{{ $score ?? '—' }}</x-staff.pill>
                <div class="text-theme-xs text-gray-500 dark:text-gray-400">
                  <span class="block" title="Character · Capacity · Capital · Conditions">C {{ $ra->character_score ?? '–' }} · Cap {{ $ra->capacity_score ?? '–' }} · K {{ $ra->capital_score ?? '–' }} · Cond {{ $ra->conditions_score ?? '–' }}</span>
                  <span class="block">{{ $ra->risk_category ? (stripos($ra->risk_category, 'risk') === false ? ucfirst($ra->risk_category) . ' risk' : $ra->risk_category) : '' }}{{ $ra->source === 'manual' ? ' · by staff' : ' · by system' }}</span>
                </div>
              </div>
            @else
              <x-staff.pill tone="gray">Not assessed</x-staff.pill>
            @endif
            @php $bp = \App\Services\BorrowerAnalytics::profileFor($loan->user_id); $weekendNow = now()->isoWeekday() >= 5; @endphp
            @if ($bp && $bp->loans)
              {{-- how they have actually repaid (borrower_profiles, owner 23 Oct 2026) --}}
              <span class="mt-1 flex flex-wrap items-center gap-1 text-theme-xs text-gray-500 dark:text-gray-400">
                Repays: {{ $bp->on_time_rate === null ? 'new' : rtrim(rtrim(number_format($bp->on_time_rate, 1), '0'), '.') . '% on time' }} · comfortable to {{ number_format($bp->comfort_amount) }}
                @foreach ($bp->flags as $f)
                  @if (str_contains($f['text'], 'weekend') && $weekendNow)
                    <x-staff.pill tone="red">Weekend loan: this borrower pays late on Fri–Sun loans</x-staff.pill>
                  @elseif ($f['tone'] === 'red')
                    <x-staff.pill tone="red">{{ $f['text'] }}</x-staff.pill>
                  @endif
                @endforeach
              </span>
            @endif
          </td>
          <td class="max-w-[220px] !whitespace-normal text-theme-xs text-gray-600 dark:text-gray-400"><span class="line-clamp-2" title="{{ $ra->recommendation ?? '' }}">{{ $ra->recommendation ?? '—' }}</span></td>
          <td data-sort-value="{{ $since?->timestamp }}">
            <span class="text-theme-sm text-gray-700 dark:text-gray-300">{{ $since?->diffForHumans(short: true) }}</span>
            <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $since?->format('j M, H:i') }}</span>
          </td>
          <td class="text-right" data-actions>
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
                  <a href="{{ route('staff.disbursements.disburse', $loan) }}" data-drawer class="inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600">Disburse</a>
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
    @empty
      <tr data-table-skip><td colspan="7"><x-staff.empty title="Nothing waiting" message="{{ $status === 'pending' ? 'No applications are waiting for a decision.' : 'No approved loans are waiting to be disbursed.' }}" /></td></tr>
    @endforelse
  </x-staff.table>

  <x-staff.placeholder class="mt-6" dependency="approval limits and maker-checker" title="Approval limits">
    Today anyone with “approve loans” can approve any amount, and the person who approves can also disburse. Limits per role and a second approver are Stage 4.
  </x-staff.placeholder>
@endsection
