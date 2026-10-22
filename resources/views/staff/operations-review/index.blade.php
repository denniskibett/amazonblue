@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'Operations review', 'sub' => 'Review recent application errors and loan receivables that differ from their latest cycle. No changes are made unless you submit a forbearance action.'])

  @if (session('success'))<x-staff.alert type="ok" class="mb-5">{{ session('success') }}</x-staff.alert>@endif
  @if ($errors->any())<x-staff.alert type="err" class="mb-5">{{ $errors->first() }}</x-staff.alert>@endif

  <div class="mb-5 flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-800">
    @if ($canViewLedger)
      <a href="{{ route('staff.operations-review', ['section' => 'receivables']) }}" class="border-b-2 px-3 py-2 text-sm font-medium {{ $section === 'receivables' ? 'border-brand-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500' }}">Loan discrepancies <span class="ml-1 text-xs">{{ $issues->total() }}</span></a>
    @endif
    @if ($canViewLedger)
      <a href="{{ route('staff.operations-review', ['section' => 'grace']) }}" class="border-b-2 px-3 py-2 text-sm font-medium {{ $section === 'grace' ? 'border-brand-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500' }}">Late payers &amp; forbearance</a>
    @endif
    @if ($canViewLedger)
      <a href="{{ route('staff.operations-review', ['section' => 'rollovers']) }}" class="border-b-2 px-3 py-2 text-sm font-medium {{ $section === 'rollovers' ? 'border-brand-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500' }}">Rollover chains</a>
    @endif
    @if ($canViewLedger)
      <a href="{{ route('staff.operations-review', ['section' => 'repairs']) }}" class="border-b-2 px-3 py-2 text-sm font-medium {{ $section === 'repairs' ? 'border-brand-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500' }}">Per-loan repairs</a>
    @endif
    @if ($canViewLogs)
      <a href="{{ route('staff.operations-review', ['section' => 'errors']) }}" class="border-b-2 px-3 py-2 text-sm font-medium {{ $section === 'errors' ? 'border-brand-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500' }}">Application errors <span class="ml-1 text-xs">{{ $logs->total() }}</span></a>
    @endif
  </div>

  @if ($section === 'errors' && $canViewLogs)
    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h2 class="font-semibold text-gray-900 dark:text-white">Recent error entries</h2>
        <p class="mt-1 text-sm text-gray-500">ERROR and higher, from the latest seven daily log files. Personal contact details and common secret values are redacted; stack traces are not shown.</p>
      </div>
      @if ($logs->isEmpty())
        <p class="p-5 text-sm text-gray-500">No error-level entries found in the available daily logs.</p>
      @else
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/[0.02]"><tr><th class="px-5 py-3">Time</th><th class="px-5 py-3">Level</th><th class="px-5 py-3">Message</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @foreach ($logs as $entry)
                <tr><td class="whitespace-nowrap px-5 py-3 text-gray-600 dark:text-gray-300">{{ $entry['time'] }}</td>
                  <td class="px-5 py-3"><span class="font-medium {{ $entry['level'] === 'ERROR' ? 'text-red-700 dark:text-red-300' : 'text-orange-700 dark:text-orange-300' }}">{{ $entry['level'] }}</span></td>
                  <td class="max-w-3xl break-words px-5 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $entry['message'] }}</td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-3 dark:border-gray-800">{{ $logs->withQueryString()->links() }}</div>
      @endif
    </section>
  @elseif ($section === 'receivables' && $canViewLedger)
    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h2 class="font-semibold text-gray-900 dark:text-white">Ledger receivable vs latest cycle</h2>
        <p class="mt-1 text-sm text-gray-500">A difference is a review finding, not an instruction to adjust the balance. Check the cycle and repayment history before changing loan status.</p>
      </div>
      <form method="GET" action="{{ route('staff.operations-review') }}" class="grid gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-800 sm:grid-cols-2 xl:grid-cols-6">
        <input type="hidden" name="section" value="receivables">
        <label class="text-xs font-medium text-gray-600 dark:text-gray-300 xl:col-span-2">Borrower or loan number
          <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name or loan #" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
        </label>
        <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Loan status
          <select name="status" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
            <option value="">All statuses</option>
            @foreach (['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery', 'repaid', 'written_off'] as $status)
              <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
            @endforeach
          </select>
        </label>
        <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Due from
          <input type="date" name="due_from" value="{{ $filters['due_from'] ?? '' }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
        </label>
        <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Due to
          <input type="date" name="due_to" value="{{ $filters['due_to'] ?? '' }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
        </label>
        <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Legacy rollover
          <select name="rollover" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
            <option value="">All</option>
            <option value="yes" @selected(($filters['rollover'] ?? '') === 'yes')>Has rollover rows</option>
            <option value="no" @selected(($filters['rollover'] ?? '') === 'no')>No rollover rows</option>
          </select>
        </label>
        <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-6">
          <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Filter loans</button>
          <a href="{{ route('staff.operations-review', ['section' => 'receivables']) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Clear</a>
          <span class="ml-auto self-center text-sm text-gray-500">{{ $issues->total() }} matching discrepancy{{ $issues->total() === 1 ? '' : 'ies' }}</span>
        </div>
      </form>
      @if ($issues->isEmpty())
        <p class="p-5 text-sm text-gray-500">No receivable differences match these filters.</p>
      @else
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/[0.02]"><tr><th class="px-4 py-3">Borrower / loan</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Latest cycle due</th><th class="px-4 py-3">Previous cycle due</th><th class="px-4 py-3 text-right">Cycle owes</th><th class="px-4 py-3 text-right">Ledger</th><th class="px-4 py-3 text-right">Difference</th><th class="px-4 py-3">Review finding</th><th class="px-4 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @foreach ($issues as $issue)
                <tr class="align-top">
                  <td class="px-4 py-3"><a class="font-medium text-brand-600 hover:underline" href="{{ route('loans.show', $issue['loan']) }}">{{ $issue['borrower_name'] }}</a><span class="mt-1 block text-xs text-gray-500">Loan #{{ $issue['loan'] }}</span>
                    @if ($plan = config('repairs.loans.' . $issue['loan']))<span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold {{ ['A' => 'bg-emerald-100 text-emerald-800', 'B' => 'bg-amber-100 text-amber-800', 'C' => 'bg-blue-100 text-blue-800'][$plan['group']] ?? '' }}">Plan {{ $plan['group'] }}</span>@endif
                    <button type="button" x-data @click="$dispatch('open-loan', {id: {{ (int) $issue['loan'] }}})" class="mt-2 block rounded-md bg-brand-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-brand-700">Details &amp; fix</button></td>
                  <td class="px-4 py-3">{{ str_replace('_', ' ', ucfirst($issue['status'])) }}</td>
                  <td class="min-w-44 px-4 py-3">
                    @if (isset($issue['cycles'][0]))
                      <span class="font-medium">Cycle {{ $issue['cycles'][0]['number'] }}</span>
                      <span class="mt-1 block text-xs text-gray-500">{{ $issue['cycles'][0]['start_date'] ?? '—' }} to {{ $issue['cycles'][0]['due_date'] ?? '—' }}</span>
                      <span class="mt-2 block text-xs text-gray-700 dark:text-gray-300">Stored outstanding: KES {{ number_format($issue['cycles'][0]['stored_due_amount'], 2) }}</span>
                      <span class="block text-xs text-gray-700 dark:text-gray-300">Calculated outstanding: KES {{ number_format($issue['cycles'][0]['calculated_due_amount'] ?? 0, 2) }}</span>
                      <span class="block text-xs {{ abs($issue['cycles'][0]['due_amount_difference'] ?? 0) >= 0.01 ? 'font-semibold text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300' }}">Amount difference: KES {{ number_format($issue['cycles'][0]['due_amount_difference'] ?? 0, 2) }}</span>
                      <span class="mt-1 block text-xs {{ $issue['cycles'][0]['date_matches_product'] ? 'text-green-700 dark:text-green-300' : 'font-semibold text-orange-700 dark:text-orange-300' }}">Product due: {{ $issue['cycles'][0]['expected_due_date'] ?? 'unknown' }}{{ $issue['cycles'][0]['date_matches_product'] ? ' · matches' : ' · differs' }}</span>
                    @else — @endif
                  </td>
                  <td class="min-w-44 px-4 py-3">
                    @if (isset($issue['cycles'][1]))
                      <span class="font-medium">Cycle {{ $issue['cycles'][1]['number'] }}</span>
                      <span class="mt-1 block text-xs text-gray-500">{{ $issue['cycles'][1]['start_date'] ?? '—' }} to {{ $issue['cycles'][1]['due_date'] ?? '—' }}</span>
                      <span class="mt-2 block text-xs text-gray-700 dark:text-gray-300">Stored outstanding: KES {{ number_format($issue['cycles'][1]['stored_due_amount'], 2) }}</span>
                      <span class="block text-xs text-gray-700 dark:text-gray-300">Calculated outstanding: KES {{ number_format($issue['cycles'][1]['calculated_due_amount'] ?? 0, 2) }}</span>
                      <span class="block text-xs {{ abs($issue['cycles'][1]['due_amount_difference'] ?? 0) >= 0.01 ? 'font-semibold text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300' }}">Amount difference: KES {{ number_format($issue['cycles'][1]['due_amount_difference'] ?? 0, 2) }}</span>
                      <span class="mt-1 block text-xs {{ $issue['cycles'][1]['date_matches_product'] ? 'text-green-700 dark:text-green-300' : 'font-semibold text-orange-700 dark:text-orange-300' }}">Product due: {{ $issue['cycles'][1]['expected_due_date'] ?? 'unknown' }}{{ $issue['cycles'][1]['date_matches_product'] ? ' · matches' : ' · differs' }}</span>
                    @else <span class="text-gray-400">No previous cycle</span> @endif
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 text-right">KES {{ number_format($issue['owed'], 2) }}</td>
                  <td class="whitespace-nowrap px-4 py-3 text-right">KES {{ number_format($issue['ledger'], 2) }}</td>
                  <td class="whitespace-nowrap px-4 py-3 text-right {{ abs($issue['difference']) >= 0.01 ? 'text-red-700 dark:text-red-300' : '' }}">KES {{ number_format($issue['difference'], 2) }}</td>
                  <td class="min-w-64 px-4 py-3"><p class="text-xs text-gray-700 dark:text-gray-300">{{ $issue['review_hint'] }}</p><p class="mt-1 text-xs text-gray-500">Rollover repayments: {{ $issue['rollover_repayments'] }} · rollover disbursements: {{ $issue['rollover_disbursements'] }}</p></td>
                  <td class="min-w-64 px-4 py-3">
                    @if ($issue['forbearance_until'])<p class="mb-2 text-xs text-gray-500">Forbearance to {{ $issue['forbearance_until'] }}</p>@endif
                    @if ($issue['cycle_date_mismatch'])
                      <p class="mb-2 text-xs font-medium text-orange-700 dark:text-orange-300">Cycle due date is outside the product period.</p>
                    @endif
                    @if ($issue['can_grant'] && auth()->user()->can('grant forbearance'))
                      <details>
                        <summary class="cursor-pointer font-medium text-brand-600">Review for forbearance</summary>
                        <form method="POST" action="{{ route('staff.operations-review.forbearance', $issue['loan']) }}" class="mt-3 space-y-2">
                          @csrf
                          <label class="block text-xs text-gray-600 dark:text-gray-300">Penalty waiver until
                            <input required type="date" name="until" min="{{ now()->addDay()->toDateString() }}" value="{{ old('until', now()->addDays(30)->toDateString()) }}" class="mt-1 block w-full rounded border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                          </label>
                          <label class="block text-xs text-gray-600 dark:text-gray-300">Reason
                            <textarea required minlength="5" maxlength="500" name="reason" rows="2" class="mt-1 block w-full rounded border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">{{ old('reason') }}</textarea>
                          </label>
                          <button type="submit" class="rounded bg-brand-600 px-3 py-2 text-xs font-semibold text-white" onclick="return confirm('Record forbearance for loan #{{ $issue['loan'] }} through the selected date?')">Record forbearance</button>
                        </form>
                      </details>
                    @endif
                    @if ($issue['can_grant'] && auth()->user()->canAny(['edit loans', 'manage loan cycles']))
                      <details class="mt-2">
                        <summary class="cursor-pointer font-medium text-gray-700 dark:text-gray-300">Correct due date</summary>
                        <form method="POST" action="{{ route('staff.operations-review.due-date', $issue['loan']) }}" class="mt-3 space-y-2">
                          @csrf
                          <p class="text-xs text-gray-600 dark:text-gray-300">Align to product date: <strong>{{ $issue['latest_cycle']['expected_due_date'] ?? 'unavailable' }}</strong></p>
                          <label class="block text-xs text-gray-600 dark:text-gray-300">Reason
                            <textarea required minlength="5" maxlength="500" name="reason" rows="2" class="mt-1 block w-full rounded border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">{{ old('reason') }}</textarea>
                          </label>
                          <button type="submit" class="rounded border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-200" onclick="return confirm('Align the loan and active cycle due dates to the loan product period? Existing repayment amounts and dates will not be changed.')">Align due date</button>
                        </form>
                      </details>
                    @endif
                    @if (! $issue['cycle_date_mismatch'])
                      <span class="text-xs text-gray-500">Cycle due date matches product period</span>
                    @elseif (! $issue['can_grant'] || (! auth()->user()->can('grant forbearance') && ! auth()->user()->canAny(['edit loans', 'manage loan cycles'])))
                      <span class="text-xs text-gray-500">Review loan and cycle history manually</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-3 dark:border-gray-800">{{ $issues->withQueryString()->links() }}</div>
      @endif
    </section>
  @elseif ($section === 'grace' && $canViewLedger)
    @php
      $badge = ['grace' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300', 'forbearance' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300', 'short_paid' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'];
      $label = ['grace' => 'Grace days', 'forbearance' => 'Forbearance', 'short_paid' => 'Owner decides'];
      $all = $grace;
      $canFix = auth()->user()->canAny(['edit loans', 'manage loan cycles']);
      $kes = fn ($v) => $v === null ? '—' : number_format((float) $v, 2);
    @endphp
    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h2 class="font-semibold text-gray-900 dark:text-white">Paid in full but late, and forbearance to restore</h2>
        <p class="mt-1 text-sm text-gray-500">Old-model loans whose borrower paid principal + interest a few days late. Grace days for exactly the days late remove penalty nobody collected, and the "kept repaid" write-off goes. The due date is not changed. Forbearance rows are the loans that were in forbearance before Phase 2. "Owner decides" rows are never changed here.</p>
      </div>
      <div class="flex flex-wrap gap-2 border-b border-gray-200 px-5 py-3 text-sm dark:border-gray-800">
        @foreach (['' => 'All', 'grace' => 'Grace days', 'forbearance' => 'Forbearance', 'short_paid' => 'Owner decides'] as $k => $v)
          <a href="{{ route('staff.operations-review', array_filter(['section' => 'grace', 'action' => $k])) }}" class="rounded-full px-3 py-1 {{ ($graceAction ?? '') === $k ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300' }}">{{ $v }}</a>
        @endforeach
        <span class="ml-auto self-center text-gray-500">{{ $grace->count() }} loan(s) · penalty removed KES {{ number_format($grace->whereIn('action', ['grace', 'forbearance'])->sum(fn ($r) => $r['penalty_now'] - ($r['penalty_after'] ?? 0)), 2) }} · write-offs removed KES {{ number_format($grace->where('action', 'grace')->sum('write_off'), 2) }}</span>
      </div>
      @if ($grace->isEmpty())
        <p class="p-5 text-sm text-gray-500">Nothing left to fix.</p>
      @else
        <form method="POST" action="{{ route('staff.operations-review.grace-batch') }}" id="grace-batch">@csrf</form>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/[0.02]">
              <tr>
                <th class="px-3 py-3"><input type="checkbox" onclick="document.querySelectorAll('.grace-pick').forEach(c => c.checked = this.checked)" aria-label="Tick all"></th>
                <th class="px-3 py-3">Loan</th><th class="px-3 py-3">Proposal</th><th class="px-3 py-3">Cycle</th>
                <th class="px-3 py-3 text-right">Contract</th><th class="px-3 py-3 text-right">Paid</th><th class="px-3 py-3">Paid in full</th>
                <th class="px-3 py-3 text-right">Penalty now → after</th><th class="px-3 py-3 text-right">Owed now → after</th><th class="px-3 py-3 text-right">Write-off</th>
                <th class="px-3 py-3">Status</th><th class="px-3 py-3"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @foreach ($grace as $r)
                <tr class="align-top">
                  <td class="px-3 py-3">@if ($r['action'] !== 'short_paid' && $canFix)<input type="checkbox" form="grace-batch" name="loan_ids[]" value="{{ $r['loan_id'] }}" class="grace-pick">@endif</td>
                  <td class="px-3 py-3"><a href="{{ route('loans.show', $r['loan_id']) }}" class="font-medium text-brand-600 hover:underline">#{{ $r['loan_id'] }}</a><div class="text-xs text-gray-500">{{ $r['borrower'] }}</div></td>
                  <td class="max-w-xs px-3 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badge[$r['action']] }}">{{ $label[$r['action']] }}{{ $r['action'] === 'grace' ? ': ' . $r['grace_after'] . ' day(s)' : '' }}</span><div class="mt-1 text-xs text-gray-500">{{ $r['reason'] }}</div></td>
                  <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-600 dark:text-gray-300">#{{ $r['cycle_number'] }} · {{ $r['start'] }} → {{ $r['due'] }}</td>
                  <td class="px-3 py-3 text-right tabular-nums">{{ $kes($r['contract']) }}</td>
                  <td class="px-3 py-3 text-right tabular-nums">{{ $kes($r['paid']) }}</td>
                  <td class="whitespace-nowrap px-3 py-3 text-xs">{{ $r['paid_in_full_on'] ?? '—' }}@if ($r['days_late'])<div class="text-amber-600">{{ $r['days_late'] }} day(s) late</div>@endif</td>
                  <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">{{ $kes($r['penalty_now']) }} → {{ $kes($r['penalty_after']) }}</td>
                  <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">{{ $kes($r['owed_now']) }} → {{ $kes($r['owed_after']) }}</td>
                  <td class="px-3 py-3 text-right tabular-nums">{{ $r['write_off'] ? $kes($r['write_off']) : '—' }}</td>
                  <td class="whitespace-nowrap px-3 py-3 text-xs">{{ $r['status_now'] }}@if ($r['status_after']) → <span class="font-medium">{{ $r['status_after'] }}</span>@endif</td>
                  <td class="px-3 py-3">
                    @if ($r['action'] === 'grace' && $canFix)
                      <form method="POST" action="{{ route('staff.operations-review.grace', $r['loan_id']) }}">@csrf<button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">Apply</button></form>
                    @elseif ($r['action'] === 'forbearance' && auth()->user()->canAny(['grant forbearance', 'edit loans', 'manage loan cycles']))
                      <form method="POST" action="{{ route('staff.operations-review.grace', $r['loan_id']) }}" class="flex items-center gap-1">@csrf
                        <input type="date" name="until" value="{{ ($r['waived_until_after'] ?? '') !== \App\Services\Loans\GraceFix::OPEN_ENDED ? ($r['waived_until_after'] ?? '') : '' }}" title="Leave empty for open-ended" class="rounded-lg border-gray-300 py-1 text-xs dark:border-gray-700 dark:bg-gray-900">
                        <button class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">Restore</button></form>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @if ($canFix)
          <div class="flex items-center justify-between border-t border-gray-200 px-5 py-3 dark:border-gray-800">
            <span class="text-xs text-gray-500">Each ticked loan is re-checked before it is saved; forbearance rows ticked here use the date shown in their row (empty = open-ended).</span>
            <button form="grace-batch" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Apply ticked</button>
          </div>
        @endif
      @endif
    </section>
  @elseif ($section === 'rollovers' && $canViewLedger && $rollover)
    @php $canFix = auth()->user()->canAny(['edit loans', 'manage loan cycles']); $kes = fn ($v) => number_format((float) $v, 2); @endphp
    <section class="mb-5 overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h2 class="font-semibold text-gray-900 dark:text-white">ROLL OVER chains to merge</h2>
        <p class="mt-1 text-sm text-gray-500">A later loan of the same borrower that opened with a ROLL OVER within {{ \App\Services\Loans\RolloverCleanup::WINDOW }} days of the earlier loan's due date becomes its next cycle(s); the ROLL OVER rows then go, because the cycles hold the carry. A carry difference is what the old rollover charged above (+) or below (−) what was owed that day; the ledger books it as a rollover adjustment.</p>
      </div>
      @if ($rollover['chains']->isEmpty())
        <p class="p-5 text-sm text-gray-500">No chains left to merge.</p>
      @else
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/[0.02]"><tr><th class="px-3 py-3">Main loan</th><th class="px-3 py-3">Links (old due → new start)</th><th class="px-3 py-3 text-right">Owed at rollover → carried</th><th class="px-3 py-3 text-right">Carry difference</th><th class="px-3 py-3"></th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @foreach ($rollover['chains'] as $ch)
                <tr class="align-top">
                  <td class="px-3 py-3"><a href="{{ route('loans.show', $ch['root']) }}" class="font-medium text-brand-600 hover:underline">#{{ $ch['root'] }}</a><div class="text-xs text-gray-500">{{ $ch['borrower'] }}</div></td>
                  <td class="px-3 py-3 text-xs">
                    @foreach ($ch['links'] as $l)
                      <div>#{{ $l['from'] }} c{{ $l['from_cycle'] }} due {{ $l['from_due'] }} → <a href="{{ route('loans.show', $l['to']) }}" class="text-brand-600 hover:underline">#{{ $l['to'] }}</a> from {{ $l['to_start'] }}@if ($l['days_late'])<span class="text-amber-600"> ({{ $l['days_late'] > 0 ? $l['days_late'] . ' day(s) late' : abs($l['days_late']) . ' day(s) early' }})</span>@endif <span class="rounded bg-gray-100 px-1 text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $l['matched_on'] }}</span>@if ($l['overlap'])<span class="text-amber-600"> · same start day</span>@endif</div>
                    @endforeach
                  </td>
                  <td class="whitespace-nowrap px-3 py-3 text-right text-xs tabular-nums">@foreach ($ch['links'] as $l)<div>{{ $kes($l['owed_at_rollover']) }} → {{ $kes($l['continuation_principal']) }}</div>@endforeach</td>
                  <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums {{ abs($ch['carry_difference']) >= 0.01 ? 'text-amber-600' : 'text-gray-500' }}">{{ $kes($ch['carry_difference']) }}</td>
                  <td class="px-3 py-3">@if ($canFix)<form method="POST" action="{{ route('staff.operations-review.chain', $ch['root']) }}">@csrf<button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">Merge</button></form>@endif</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </section>
    <section class="mb-5 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <h2 class="font-semibold text-gray-900 dark:text-white">ROLL OVER rows the cycles already hold, and loans never sent money</h2>
      <p class="mt-1 text-sm text-gray-500">{{ $rollover['stray']->count() }} ROLL OVER row(s) ({{ $rollover['stray']->where('table', 'repayments')->count() }} repayments, {{ $rollover['stray']->where('table', 'disbursements')->count() }} disbursements) on loans whose cycles already show the carry. They were never money, so removing them changes no balance. {{ $rollover['never']->count() }} loan(s) never sent money still carry interest/penalty charges: #{{ $rollover['never']->pluck('loan_id')->implode(', #') }}.</p>
      @php $pending = $rollover['transfers']->where('posted', false); @endphp
      @if ($rollover['transfers']->isNotEmpty())
        <p class="mt-3 text-sm text-gray-500">Rollovers into a loan that already existed: the receivable moves in the ledger from the old loan to the loan that took it; both loans' rows stay. {{ $pending->count() }} to post, {{ $rollover['transfers']->count() - $pending->count() }} posted.</p>
        <div class="mt-2 overflow-x-auto"><table class="min-w-full text-left text-xs"><thead class="text-gray-500"><tr><th class="py-1 pr-3">From</th><th class="py-1 pr-3">To</th><th class="py-1 pr-3">Borrower</th><th class="py-1 pr-3">Closed → opened</th><th class="py-1 pr-3 text-right">KES</th><th class="py-1 pr-3">Match</th><th class="py-1"></th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @foreach ($rollover['transfers'] as $t)<tr><td class="py-1 pr-3"><a href="{{ route('loans.show', $t['from']) }}" class="text-brand-600 hover:underline">#{{ $t['from'] }}</a></td><td class="py-1 pr-3"><a href="{{ route('loans.show', $t['to']) }}" class="text-brand-600 hover:underline">#{{ $t['to'] }}</a></td><td class="py-1 pr-3">{{ $t['borrower'] }}</td><td class="py-1 pr-3">{{ $t['closed'] }} → {{ $t['opened'] }}</td><td class="py-1 pr-3 text-right tabular-nums">{{ number_format($t['amount'], 2) }}</td><td class="py-1 pr-3">{{ $t['how'] }} · cycle {{ $t['into_cycle'] }}{{ $t['target'] === 'adjustment' ? ' (already booked there)' : '' }}</td><td class="py-1">{{ $t['posted'] ? 'posted' : '' }}</td></tr>@endforeach
        </tbody></table></div>
      @endif
      @if ($canFix && ($rollover['stray']->isNotEmpty() || $rollover['never']->isNotEmpty() || $pending->isNotEmpty()))
        <form method="POST" action="{{ route('staff.operations-review.rollovers-clean') }}" class="mt-3">@csrf<button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Remove the rows, clear the charges, post the transfers</button></form>
      @endif
    </section>
    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="font-semibold text-gray-900 dark:text-white">Not linked ({{ $rollover['unmatched']->count() }})</h2><p class="mt-1 text-sm text-gray-500">Left as separate loans. Their differences go to the per-loan decisions (Feature 2b).</p></div>
      <div class="max-h-96 overflow-y-auto"><table class="min-w-full text-left text-xs"><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @foreach ($rollover['unmatched'] as $u)<tr><td class="px-5 py-2"><a href="{{ route('loans.show', $u['loan']) }}" class="text-brand-600 hover:underline">#{{ $u['loan'] }}</a></td><td class="px-3 py-2 text-gray-500">{{ $u['date'] }}</td><td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $u['why'] }}</td></tr>@endforeach
      </tbody></table></div>
    </section>
  @elseif ($section === 'repairs' && $canViewLedger)
    @include('staff.operations-review._repairs')
  @else
    <x-staff.alert type="warn" title="Review section unavailable">You do not have permission to view this section.</x-staff.alert>
  @endif
  @if ($canViewLedger && in_array($section, ['receivables', 'repairs'], true))
    @include('staff.operations-review._loan-modal')
  @endif
@endsection
