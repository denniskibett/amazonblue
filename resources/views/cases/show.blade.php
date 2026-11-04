@extends('layouts.app')

@section('content')
@php
    // Plain array with only the fields the page script reads. Never @js() the Eloquent model:
    // its user.borrower accessors run credit scoring per field and exhaust memory, and full user
    // rows carry KYC data.
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('M d, Y') : null;
    $caseUser = $caseData->user;
    $caseLoanRow = $caseData->loan;
    $caseJs = [
        'id' => $caseData->id,
        'case_number' => $caseData->case_number,
        'user_id' => $caseData->user_id,
        'user_name' => $caseUser->name ?? null,
        'user_email' => $caseUser->email ?? null,
        'user_phone' => $caseUser->phone ?? null,
        'user_initials' => $caseUser ? collect(explode(' ', trim($caseUser->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') : '?',
        'client_type' => $caseUser?->borrower?->getRawOriginal('client_type'),
        'loan_id' => $caseData->loan_id,
        'loan_amount' => $caseLoanRow ? (float) $caseLoanRow->amount : null,
        'loan_status' => $caseLoanRow->status ?? null,
        'loan_type' => $caseLoanRow?->loanType?->name,
        'is_non_performing' => (bool) ($caseLoanRow->is_non_performing ?? false),
        'days_overdue' => $caseLoanRow->days_overdue ?? 0,
        'days_in_default' => $caseData->days_in_default,
        'total_debt_amount' => (float) $caseData->total_debt_amount,
        'remaining_balance' => (float) $caseData->remaining_balance,
        'recovery_progress' => $caseData->recovery_progress,
        'total_recovered' => (float) $caseData->actions->whereNotNull('repayment_id')->sum('amount_collected'),
        'default_date_formatted' => $fmtDate($caseData->default_date),
        'last_contact_date_formatted' => $fmtDate($caseData->last_contact_date),
        'next_action_date_formatted' => $fmtDate($caseData->next_action_date),
        'assigned_to_name' => $caseData->assignedTo->name ?? null,
        'recovery_officer' => $caseData->assignedTo->name ?? null,
        'recovery_strategy' => $caseData->recovery_strategy,
        'notes' => $caseData->getAttribute('notes'),
        'actions_count' => $caseData->actions->count(),
        'created_at_diff' => optional($caseData->created_at)->diffForHumans(),
        'updated_at_diff' => optional($caseData->updated_at)->diffForHumans(),
        'payment_plans' => collect($caseData->paymentPlans ?? [])->map(fn ($p) => [
            'id' => data_get($p, 'id'),
            'installment_frequency' => data_get($p, 'installment_frequency'),
            'status' => (string) data_get($p, 'status'),
            'installment_amount' => data_get($p, 'installment_amount'),
            'number_of_installments' => data_get($p, 'number_of_installments'),
            'total_amount' => data_get($p, 'total_amount'),
            'progress_percentage' => data_get($p, 'progress_percentage', 0),
            'remaining_balance' => data_get($p, 'remaining_balance', 0),
        ])->values(),
    ];
@endphp
@php
    // the live position of the loan (engine v2 balance), not the case's opening figure (owner, 4 Nov 2026)
    $bal = $caseData->loan_id ? app(\App\Services\LedgerReports::class)->balance($caseData->loan_id) : null;
    $owedNow = $bal ? (float) ($bal['final_outstanding'] ?? 0) : (float) $caseData->remaining_balance;
    $daysLate = $bal ? (int) ($bal['days_overdue'] ?? 0) : (int) ($caseLoanRow->days_overdue ?? 0);
    $paidSince = $caseData->loan_id ? (float) \App\Services\CustomerWallets::whereCash(\Illuminate\Support\Facades\DB::table('repayments')->whereNull('deleted_at'))
        ->where('loan_id', $caseData->loan_id)->where('repayment_date', '>=', optional($caseData->created_at)->toDateString() ?? '1970-01-01')->sum('amount') : 0.0;
    $opening = (float) $caseData->total_debt_amount;
    $progress = $opening > 0 ? min(100, round($paidSince / $opening * 100)) : 0;
    $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
    $dt = 'text-theme-sm text-gray-500 dark:text-gray-400';
    $dd = 'text-theme-sm font-medium text-gray-800 dark:text-white/90';
    $statusName = $caseData->status->name ?? 'Open';
    $priorityName = $caseData->priority->name ?? null;
    $loanStatus = $caseLoanRow->status ?? null;
    $phone = $caseUser->phone ?? null;
    $wa = $phone ? 'https://wa.me/' . preg_replace('/^0/', '254', preg_replace('/\D/', '', $phone)) : null;
    $caseActions = $caseData->actions->sortByDesc('action_date');
    $forbearanceUntil = $caseLoanRow->forbearance_until ?? null;
@endphp
<div x-data="caseShow({{ \Illuminate\Support\Js::from($caseJs) }})">
    {{-- one header row: case, borrower, status and the breadcrumb (owner, 4 Nov 2026) --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2">
            <h1 class="font-outfit text-xl font-semibold text-gray-800 dark:text-white/90">{{ $caseData->case_number }} · {{ $caseUser->name ?? 'Borrower' }}</h1>
            <span class="text-theme-sm text-gray-500">Opened {{ optional($caseData->created_at)->format('j M Y') }} · {{ $caseData->assignedTo ? 'with ' . $caseData->assignedTo->name : 'not assigned' }}</span>
            <x-staff.pill :tone="str_contains(strtolower($statusName), 'recover') || str_contains(strtolower($statusName), 'clos') ? 'green' : 'blue'">{{ $statusName }}</x-staff.pill>
        @if ($priorityName)<x-staff.pill :tone="in_array(strtolower($priorityName), ['high', 'urgent', 'critical'], true) ? 'red' : 'gray'">{{ $priorityName }} priority</x-staff.pill>@endif
        @if ($loanStatus)<x-staff.pill :tone="$loanStatus === 'forbearance' ? 'amber' : (in_array($loanStatus, ['defaulted', 'recovery', 'written_off'], true) ? 'red' : 'gray')">Loan {{ str_replace('_', ' ', $loanStatus) }}{{ $loanStatus === 'forbearance' && $forbearanceUntil ? (\Illuminate\Support\Carbon::parse($forbearanceUntil)->year >= 2035 ? ', open-ended' : ' until ' . \Illuminate\Support\Carbon::parse($forbearanceUntil)->format('j M Y')) : '' }}</x-staff.pill>@endif
        @if ($caseLoanRow?->is_non_performing)<x-staff.pill tone="red">Non-performing</x-staff.pill>@endif
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Owed now', $kes($owedNow), 'live balance on the loan', $owedNow > 0 ? 'text-error-600' : 'text-success-600'],
            ['Paid since the case opened', $kes($paidSince), $progress . '% of ' . $kes($opening) . ' at opening', null],
            ['Days late', $daysLate, $caseData->default_date ? 'defaulted ' . \Illuminate\Support\Carbon::parse($caseData->default_date)->format('j M Y') : 'not defaulted', $daysLate > 30 ? 'text-error-600' : null],
            ['Next step', $caseData->next_action_date ? \Illuminate\Support\Carbon::parse($caseData->next_action_date)->format('j M Y') : 'Not set', 'last contact ' . ($caseData->last_contact_date ? \Illuminate\Support\Carbon::parse($caseData->last_contact_date)->format('j M Y') : 'never'), $caseData->next_action_date ? null : 'text-orange-600'],
        ] as [$l, $v, $s, $c])
            <div class="{{ $card }}"><p class="{{ $dt }}">{{ $l }}</p><p class="mt-2 whitespace-nowrap font-outfit text-2xl font-bold text-gray-800 dark:text-white/90">{{ $v }}</p><p class="mt-1 text-theme-xs {{ $c ?? 'text-gray-500' }}">{{ $s }}</p></div>
        @endforeach
    </div>

    <div>
        <div>
            {{-- the case's follow-up in one tabline (owner, 4 Nov 2026) --}}
            @php
                $promises = $caseActions->filter(fn ($a) => $a->promised_amount > 0);
                $followUps = $caseActions->filter(fn ($a) => $a->follow_up_date)->sortBy('follow_up_date');
                $plans = collect($caseJs['payment_plans']);
                $hasNotes = $caseData->recovery_strategy || $caseData->getAttribute('notes');
            @endphp
            <x-staff.tabs :tabs="['activity' => ['Activity', $caseActions->count()], 'promises' => ['Promises', $promises->count()], 'followups' => ['Follow-ups', $followUps->count()], 'plan' => ['Payment plan', $plans->count()], 'who' => 'Borrower and loan', 'notes' => 'Notes', 'close' => 'Close']">
                <x-slot:tools>
                    <button type="button" @click="openEditCase()" class="inline-flex h-10 items-center whitespace-nowrap rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Edit case</button>
                    <button type="button" @click="openAddActionModal()" class="inline-flex h-10 items-center whitespace-nowrap rounded-lg bg-brand-500 px-4 text-sm font-medium text-white">+ Log an action</button>
                </x-slot:tools>
                <x-staff.tab name="activity">
                    <div class="p-5 sm:p-6">
                @forelse ($caseActions as $a)
                    <div class="flex gap-4 border-t border-gray-100 py-4 first:border-t-0 first:pt-0 dark:border-gray-800">
                        <span class="mt-1 size-2.5 shrink-0 rounded-full {{ $a->amount_collected > 0 ? 'bg-success-500' : ($a->promised_amount > 0 ? 'bg-orange-400' : 'bg-brand-500') }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="font-medium text-gray-800 dark:text-white/90">{{ $a->actionType->name ?? 'Action' }}@if ($a->outcome) <span class="font-normal text-gray-500">· {{ str_replace('_', ' ', $a->outcome) }}</span>@endif</p>
                                <span class="text-theme-xs text-gray-500">{{ $a->action_date ? \Illuminate\Support\Carbon::parse($a->action_date)->format('j M Y, H:i') : '' }}{{ $a->performedBy ? ' · ' . $a->performedBy->name : '' }}</span>
                            </div>
                            @if ($a->contact_person)<p class="text-theme-sm text-gray-600 dark:text-gray-400">Spoke to {{ $a->contact_person }}{{ $a->contact_relationship ? ' (' . $a->contact_relationship . ')' : '' }}</p>@endif
                            @if ($a->notes)<p class="mt-1 text-theme-sm text-gray-700 dark:text-gray-300">{{ $a->notes }}</p>@endif
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if ($a->amount_collected > 0)<x-staff.pill tone="green">Collected {{ $kes($a->amount_collected) }}</x-staff.pill>@endif
                                @if ($a->promised_amount > 0)<x-staff.pill tone="amber">Promised {{ $kes($a->promised_amount) }}{{ $a->promised_date ? ' by ' . \Illuminate\Support\Carbon::parse($a->promised_date)->format('j M') : '' }}</x-staff.pill>@endif
                                @if ($a->follow_up_date)<x-staff.pill tone="blue">Follow up {{ \Illuminate\Support\Carbon::parse($a->follow_up_date)->format('j M') }}</x-staff.pill>@endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <p class="font-medium text-gray-800 dark:text-white/90">Nothing logged yet</p>
                        <p class="mt-1 text-theme-sm text-gray-500">Call {{ $caseUser->name ?? 'the borrower' }}, then log what they said and any promise to pay.</p>
                        <button type="button" @click="openAddActionModal()" class="mt-4 inline-flex h-10 items-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white">+ Log the first action</button>
                    </div>
                @endforelse
                    </div>
                </x-staff.tab>
                <x-staff.tab name="promises">
                    <div class="p-5 sm:p-6">
                        @forelse ($promises as $a)
                            @php $kept = $a->promised_date && $paidSince > 0 && \Illuminate\Support\Carbon::parse($a->promised_date)->isPast(); $due = $a->promised_date ? \Illuminate\Support\Carbon::parse($a->promised_date) : null; @endphp
                            <div class="flex items-center justify-between border-t border-gray-100 py-3 first:border-t-0 first:pt-0 dark:border-gray-800">
                                <div><p class="font-medium text-gray-800 dark:text-white/90">{{ $kes($a->promised_amount) }}{{ $due ? ' by ' . $due->format('j M Y') : '' }}</p><p class="text-theme-xs text-gray-500">Promised {{ $a->action_date ? \Illuminate\Support\Carbon::parse($a->action_date)->format('j M') : '' }}{{ $a->contact_person ? ' by ' . $a->contact_person : '' }}</p></div>
                                <x-staff.pill :tone="! $due ? 'gray' : ($due->isFuture() ? 'blue' : ($kept ? 'green' : 'red'))">{{ ! $due ? 'No date' : ($due->isFuture() ? 'Due' : ($kept ? 'Paid something' : 'Broken')) }}</x-staff.pill>
                            </div>
                        @empty
                            <p class="py-6 text-center text-theme-sm text-gray-500">No promises to pay yet. Log one with the amount and date when the borrower gives it.</p>
                        @endforelse
                    </div>
                </x-staff.tab>
                <x-staff.tab name="followups">
                    <div class="p-5 sm:p-6">
                        @if ($caseData->next_action_date)<p class="mb-3 rounded-lg bg-brand-50 p-3 text-theme-sm text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">Next step on the case: {{ \Illuminate\Support\Carbon::parse($caseData->next_action_date)->format('j M Y') }}</p>@endif
                        @forelse ($followUps as $a)
                            @php $on = \Illuminate\Support\Carbon::parse($a->follow_up_date); @endphp
                            <div class="flex items-center justify-between border-t border-gray-100 py-3 first:border-t-0 first:pt-0 dark:border-gray-800">
                                <div><p class="font-medium text-gray-800 dark:text-white/90">{{ $on->format('j M Y') }} · {{ $a->actionType->name ?? 'Action' }}</p><p class="text-theme-xs text-gray-500">{{ $a->follow_up_notes ?: ($a->notes ?: '') }}</p></div>
                                <x-staff.pill :tone="$on->isToday() ? 'amber' : ($on->isPast() ? 'red' : 'blue')">{{ $on->isToday() ? 'Today' : ($on->isPast() ? 'Overdue' : 'Coming') }}</x-staff.pill>
                            </div>
                        @empty
                            <p class="py-6 text-center text-theme-sm text-gray-500">No follow-ups set. Add a follow-up date when you log an action.</p>
                        @endforelse
                    </div>
                </x-staff.tab>
                <x-staff.tab name="plan">
                    <div class="p-5 sm:p-6">
                        @forelse ($plans as $p)
                            <div class="border-t border-gray-100 py-3 first:border-t-0 first:pt-0 dark:border-gray-800">
                                <div class="flex justify-between text-theme-sm"><span>{{ $p['number_of_installments'] }} × {{ $kes($p['installment_amount']) }} {{ $p['installment_frequency'] }}</span><x-staff.pill :tone="$p['status'] === 'active' ? 'blue' : 'gray'">{{ ucfirst($p['status']) }}</x-staff.pill></div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5"><div class="h-2 rounded-full bg-brand-500" style="width: {{ (float) $p['progress_percentage'] }}%"></div></div>
                                <p class="mt-1 text-theme-xs text-gray-500">{{ $kes($p['remaining_balance']) }} left of {{ $kes($p['total_amount']) }}</p>
                            </div>
                        @empty
                            <p class="py-6 text-center text-theme-sm text-gray-500">No payment plan. Offer one from the loan when the borrower can't pay in full.</p>
                        @endforelse
                    </div>
                </x-staff.tab>
                <x-staff.tab name="who">
                    <div class="grid grid-cols-1 gap-8 p-5 sm:p-6 lg:grid-cols-2">
            <div>
                <div class="flex items-center gap-3">
                    <span class="flex size-12 items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-600 dark:bg-brand-500/15">{{ $caseJs['user_initials'] }}</span>
                    <div class="min-w-0"><p class="truncate font-semibold text-gray-800 dark:text-white/90">{{ $caseUser->name ?? '—' }}</p><p class="truncate text-theme-sm text-gray-500">{{ $phone ?? 'no phone' }}</p></div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    @if ($phone)<a href="tel:{{ $phone }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 text-sm font-medium dark:border-gray-700">Call</a>@endif
                    @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 text-sm font-medium dark:border-gray-700">WhatsApp</a>@endif
                </div>
                <dl class="mt-4 space-y-2.5">
                    @if ($caseUser?->email)<div class="flex justify-between gap-3"><dt class="{{ $dt }}">Email</dt><dd class="{{ $dd }} truncate">{{ $caseUser->email }}</dd></div>@endif
                    @if ($caseUser?->kin_name)<div class="flex justify-between gap-3"><dt class="{{ $dt }}">Next of kin</dt><dd class="{{ $dd }} text-right">{{ $caseUser->kin_name }}{{ $caseUser->kin_phone ? ' · ' . $caseUser->kin_phone : '' }}</dd></div>@endif
                </dl>
                @if ($caseUser)<a href="{{ route('users.show', $caseUser) }}" class="mt-4 inline-block text-theme-sm font-medium text-brand-500 hover:underline">Full profile →</a>@endif
            </div>

            @if ($caseLoanRow)
                <div>
                    <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Loan #{{ str_pad($caseData->loan_id, 5, '0', STR_PAD_LEFT) }}</h3>
                    <dl class="space-y-2.5">
                        <div class="flex justify-between gap-3"><dt class="{{ $dt }}">Product</dt><dd class="{{ $dd }} text-right">{{ $caseLoanRow->loanType->name ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="{{ $dt }}">Lent</dt><dd class="{{ $dd }}">{{ $kes($caseLoanRow->amount) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="{{ $dt }}">Due</dt><dd class="{{ $dd }}">{{ $caseLoanRow->due_date ? \Illuminate\Support\Carbon::parse($caseLoanRow->due_date)->format('j M Y') : '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="{{ $dt }}">Owed now</dt><dd class="{{ $dd }}">{{ $kes($owedNow) }}</dd></div>
                    </dl>
                    <a href="{{ route('loans.show', $caseData->loan_id) }}" class="mt-4 inline-block text-theme-sm font-medium text-brand-500 hover:underline">Open the loan →</a>
                </div>
            @endif

                    </div>
                </x-staff.tab>
                <x-staff.tab name="close">
                    <div class="max-w-xl p-5 sm:p-6">
                        <p class="text-theme-sm text-gray-600 dark:text-gray-400">Close the case when the borrower has paid in full, or write off what can't be recovered. A write-off needs a reason and is in the audit log.</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="button" @click="markAsRecovered()" class="inline-flex h-10 items-center rounded-lg border border-success-500 px-4 text-sm font-medium text-success-600 hover:bg-success-50">Mark as recovered</button>
                            <button type="button" @click="openWriteOffModal()" class="inline-flex h-10 items-center rounded-lg border border-error-500 px-4 text-sm font-medium text-error-600 hover:bg-error-50">Write off…</button>
                        </div>
                    </div>
                </x-staff.tab>
                <x-staff.tab name="notes">
                    <div class="p-5 sm:p-6 text-theme-sm text-gray-700 dark:text-gray-300">
                        @if ($caseData->recovery_strategy)<p><span class="font-medium">Strategy:</span> {{ $caseData->recovery_strategy }}</p>@endif
                        @if ($caseData->getAttribute('notes'))<p class="mt-1">{{ $caseData->getAttribute('notes') }}</p>@endif
                        @unless ($hasNotes)<p class="py-6 text-center text-gray-500">No notes. Add them with Edit case.</p>@endunless
                    </div>
                </x-staff.tab>
            </x-staff.tabs>
        </div>

    </div>
</div>

{{-- Include the Case Create/Edit Modal --}}
@include('partials.modal.cases-create-modal')

{{-- Include Alert Modal --}}
@include('partials.modal.alert-modal')

{{-- Add action / Write off dialogs (listen for open-add-action / open-write-off) --}}
@include('partials.recovery.case-action-modals')

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('caseShow', (caseData) => ({
        // the case lives in rc: `case` is a reserved word in an Alpine expression
        rc: caseData || {},
        
        init() {
        },
        
        formatCurrency(amount) {
            if (amount === undefined || amount === null) return 'KES 0.00';
            return 'KES ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
        
        openEditCase() {
            window.dispatchEvent(new CustomEvent('edit-case', {
                detail: { case: this.rc }
            }));
        },
        
        openAddActionModal() {
            window.dispatchEvent(new CustomEvent('open-add-action', {
                detail: { caseId: this.rc.id }
            }));
        },
        
        async markAsRecovered() {
            if (await window.uiConfirm('Mark this case as recovered?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/cases/' + this.rc.id + '/recover';
                form.style.display = 'none';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrfInput);
                
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PATCH';
                form.appendChild(methodInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        },
        
        openWriteOffModal() {
            window.dispatchEvent(new CustomEvent('open-write-off', {
                detail: { caseId: this.rc.id }
            }));
        }
    }));
});
</script>
@endpush