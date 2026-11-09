<!-- Loan Details Sidebar -->
<div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] xl:w-1/5">
    <div class="mb-5">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Loan Details</h2>
            <div class="loan-status-badge px-3 py-1 rounded-full text-xs
                @if($loan->status === 'approved') bg-green-100 text-green-800
                @elseif($loan->status === 'disbursed') bg-blue-100 text-blue-800
                @elseif($loan->status === 'active') bg-indigo-100 text-indigo-800
                @elseif($loan->status === 'overdue') bg-yellow-100 text-yellow-800
                @elseif($loan->status === 'rejected') bg-red-100 text-red-800
                @elseif($loan->status === 'repaid') bg-purple-100 text-purple-800
                @elseif($loan->status === 'defaulted') bg-red-100 text-red-800
                @elseif($loan->status === 'recovery') bg-purple-100 text-purple-800
                @elseif($loan->status === 'forbearance') bg-gray-100 text-gray-800
                @elseif($loan->status === 'written_off') bg-slate-100 text-slate-800
                @else bg-gray-100 text-gray-800 @endif">
                {{ $loan->status_label }}
            </div>
        </div>

        <!-- ============ NPL / OVERDUE STATUS SECTION ============ -->
        <div class="mt-3 p-3 rounded-lg border
            @if($loan->is_non_performing || $loan->status === 'defaulted') 
                border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20
            @elseif($loan->isOverdue() || $loan->status === 'overdue')
                border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20
            @elseif($loan->status === 'forbearance')
                border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50
            @elseif($loan->status === 'recovery')
                border-purple-200 bg-purple-50 dark:border-purple-800 dark:bg-purple-900/20
            @else
                border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20
            @endif
        ">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    @if($loan->is_non_performing || $loan->status === 'defaulted')
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <span class="font-semibold text-red-800 dark:text-red-300"><x-ui.icon name="alert" class="inline-block size-4 align-[-3px]" /> Defaulted / NPL</span>
                    @elseif($loan->isOverdue() || $loan->status === 'overdue')
                        <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-semibold text-yellow-800 dark:text-yellow-300"><x-ui.icon name="alert" class="inline-block size-4 align-[-3px]" /> Overdue</span>
                    @elseif($loan->status === 'forbearance')
                        <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        <span class="font-semibold text-gray-800 dark:text-gray-300"><x-ui.icon name="clock" class="inline-block size-4 align-[-3px]" /> Forbearance</span>
                    @elseif($loan->status === 'recovery')
                        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="font-semibold text-purple-800 dark:text-purple-300"><x-ui.icon name="refresh" class="inline-block size-4 align-[-3px]" /> In Recovery</span>
                    @else
                        <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-semibold text-green-800 dark:text-green-300"><x-ui.icon name="check-circle" class="inline-block size-4 align-[-3px]" /> Performing</span>
                    @endif
                </div>
                @if($loan->is_non_performing || $loan->status === 'defaulted')
                    <span class="text-xs text-red-600 dark:text-red-400 font-medium">
                        {{ $metrics['days_overdue'] ?? 0 }} days overdue
                    </span>
                @endif
            </div>
            
            @if($loan->is_non_performing || $loan->status === 'defaulted')
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-300">
                    <span class="mr-1 inline-block size-2 rounded-full bg-error-500 align-middle"></span> Defaulted
                </span>
                @if($loan->default_date)
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    <x-ui.icon name="calendar" class="inline-block size-4 align-[-3px]" /> Default Date: {{ \Carbon\Carbon::parse($loan->default_date)->format('M d, Y') }}
                </span>
                @endif
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium 
                    @if(($metrics['days_overdue'] ?? 0) > 90) bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-300
                    @elseif(($metrics['days_overdue'] ?? 0) > 60) bg-orange-100 text-orange-800 dark:bg-orange-800/30 dark:text-orange-300
                    @elseif(($metrics['days_overdue'] ?? 0) > 30) bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-300
                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                    <x-ui.icon name="chart" class="inline-block size-4 align-[-3px]" /> {{ $metrics['days_overdue'] ?? 0 }} days in default
                </span>
                @if(isset($recoveryCase))
                <a href="{{ route('cases.show', $recoveryCase) }}" class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-300 hover:bg-blue-200 transition-colors">
                    <x-ui.icon name="list" class="inline-block size-4 align-[-3px]" /> View Recovery Case
                </a>
                @endif
            </div>
            @elseif($loan->isOverdue() || $loan->status === 'overdue')
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-300">
                    <span class="mr-1 inline-block size-2 rounded-full bg-amber-400 align-middle"></span> Overdue: {{ $metrics['days_overdue'] ?? 0 }} days
                </span>
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    <x-ui.icon name="clock" class="inline-block size-4 align-[-3px]" /> Threshold: {{ $metrics['default_threshold_days'] ?? 30 }} days until default
                </span>
            </div>
            @elseif($loan->status === 'forbearance')
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    <x-ui.icon name="clock" class="inline-block size-4 align-[-3px]" /> Forbearance until: {{ $loan->forbearance_until ? \Carbon\Carbon::parse($loan->forbearance_until)->format('M d, Y') : 'N/A' }}
                </span>
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    <x-ui.icon name="calendar" class="inline-block size-4 align-[-3px]" /> Days remaining: {{ $loan->forbearance_until ? \Carbon\Carbon::now()->diffInDays($loan->forbearance_until, false) : 0 }}
                </span>
            </div>
            @elseif($loan->status === 'recovery')
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800/30 dark:text-purple-300">
                    <x-ui.icon name="refresh" class="inline-block size-4 align-[-3px]" /> Recovery started: {{ $loan->recovery_started_at ? \Carbon\Carbon::parse($loan->recovery_started_at)->format('M d, Y') : 'N/A' }}
                </span>
                @if($loan->recovery_notes)
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    <x-ui.icon name="file" class="inline-block size-4 align-[-3px]" /> Notes available
                </span>
                @endif
            </div>
            @else
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-300">
                    <x-ui.icon name="check-circle" class="inline-block size-4 align-[-3px]" /> Loan is performing
                </span>
                @if($metrics['due_date'] ?? false)
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    <x-ui.icon name="calendar" class="inline-block size-4 align-[-3px]" /> Due: {{ $metrics['due_date']->format('M d, Y') }}
                </span>
                @endif
            </div>
            @endif
        </div>

        <!-- ============ GRACE PERIOD STATUS ============ -->
        <div class="mt-3 p-3 rounded-lg border 
            @if($loan->isWithinGracePeriod()) border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20
            @elseif($loan->grace_days_balance > 0) border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20
            @else border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 @endif">
            <div class="flex items-center gap-2">
                @if($loan->isWithinGracePeriod())
                    <span class="text-green-600 dark:text-green-400"><span class="mr-1 inline-block size-2 rounded-full bg-success-500 align-middle"></span></span>
                    <span class="font-medium text-green-800 dark:text-green-300">In Grace Period</span>
                    <span class="ml-auto text-xs text-green-600 dark:text-green-400">{{ $loan->getRemainingGraceDays() }} days remaining</span>
                @elseif($loan->grace_days_balance > 0)
                    <span class="text-blue-600 dark:text-blue-400"><x-ui.icon name="list" class="inline-block size-4 align-[-3px]" /></span>
                    <span class="font-medium text-blue-800 dark:text-blue-300">{{ $loan->grace_days_balance }} Grace Days</span>
                    <span class="ml-auto text-xs text-blue-600 dark:text-blue-400">Earned: {{ $loan->grace_days_earned }} | Used: {{ $loan->grace_days_used }}</span>
                @else
                    <span class="text-gray-500 dark:text-gray-400"><x-ui.icon name="calendar" class="inline-block size-4 align-[-3px]" /></span>
                    <span class="font-medium text-gray-600 dark:text-gray-300">No Grace Days</span>
                    @if(($metrics['days_overdue'] ?? 0) > 0)
                        <span class="ml-auto text-xs text-red-600 dark:text-red-400">{{ $metrics['days_overdue'] }} days overdue</span>
                    @endif
                @endif
            </div>
        </div>

        <!-- ============ CYCLE / ROLLOVER STATUS ============ -->
        <div class="mt-3 p-3 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
            <div class="flex items-center gap-2">
                <span class="text-purple-600 dark:text-purple-400"><x-ui.icon name="refresh" class="inline-block size-4 align-[-3px]" /></span>
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $loan->cycle_display }}</span>
                @if($loan->cycle > 1)
                    <span class="ml-auto text-xs text-purple-600 dark:text-purple-400">
                        +{{ number_format($loan->cycles->sum('interest_capitalized'), 2) }} capitalized
                    </span>
                @endif
            </div>
            @if($loan->cycle > 1)
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Original: KES {{ number_format($loan->original_amount ?? $loan->amount, 2) }}
            </div>
            @endif
            <button onclick="openCyclesModal({{ $loan->id }})" 
                    class="mt-2 w-full text-xs text-indigo-600 dark:text-indigo-400 hover:underline text-center">
                View All Cycles →
            </button>
        </div>

        <!-- ============ QUICK ACTIONS ============ -->
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
            
            {{-- edit the loan in the loan modal (owner, 23 Oct 2026) --}}
            @can('edit loans')
            <button type="button" onclick="window.openLoanEdit && window.openLoanEdit({{ $loan->id }})"
               class="w-full flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5 transition-colors">
                <x-ui.icon name="edit" class="size-4" />
                Edit loan
            </button>
            @endcan
            {{-- who brought it in and whose money funded it, with a dry run (owner, 23 Oct 2026) --}}
            @can('edit partners')
            <a href="{{ route('staff.loans.partners', $loan) }}" data-drawer
               class="w-full flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5 transition-colors">
                <x-ui.icon name="users" class="size-4" />
                Partners: broker and funding{{ $loan->broker_status ? ' · brokered' : '' }}
            </a>
            @endcan
            {{-- send an approved loan's money to the borrower's phone; a second person approves (owner, 24 Oct 2026) --}}
            @if ($loan->status === 'approved' && \App\Services\Mpesa\B2cPayouts::enabled())
            @can('disburse loans')
            @php $b2cPhone = optional($loan->user?->paymentMethods()->where('method_type_id', \Illuminate\Support\Facades\DB::table('payment_method_types')->where('slug', 'mobile_money')->value('id'))->latest('id')->first())->mobile_number ?? $loan->user?->phone; @endphp
            <details class="rounded-lg border border-gray-300 dark:border-gray-700">
                <summary class="flex cursor-pointer items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300">Send by M-Pesa</summary>
                <form method="POST" action="{{ route('mpesa.payouts.store', $loan) }}" class="space-y-2 p-3">
                    @csrf
                    <x-staff.field name="phone" label="M-Pesa number" :value="$b2cPhone" required />
                    <x-staff.field name="amount" type="number" label="Amount (KES)" :value="(int) $loan->amount" min="10" step="1" required />
                    <button class="w-full rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Ask for the payout</button>
                    <p class="text-xs text-gray-500">A second person approves it on Second sign-offs; then it is sent.</p>
                </form>
            </details>
            @endcan
            @endif
            {{-- Sharet Pay, the second rail (9 Nov 2026): send an approved loan's money through Sharet at the next run --}}
            @if ($loan->status === 'approved' && \App\Services\Sharet\SharetPayouts::enabled())
            @can('disburse loans')
            <details class="rounded-lg border border-gray-300 dark:border-gray-700">
                <summary class="flex cursor-pointer items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300">Send through Sharet</summary>
                <form method="POST" action="{{ route('sharet.disburse', $loan) }}" class="space-y-2 p-3">
                    @csrf
                    <x-staff.field name="phone" id="sharet-phone" label="M-Pesa number" :value="$loan->user?->phone" required />
                    <x-staff.field name="amount" id="sharet-amount" type="number" label="Amount (KES)" :value="(int) $loan->amount" min="10" step="1" required />
                    <button class="w-full rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Send through Sharet</button>
                    <p class="text-xs text-gray-500">Goes at the next run (08:00, 12:00, 16:00){{ \App\Services\Approvals::enabled() ? ', after a second person approves it on Second sign-offs' : '' }}.</p>
                </form>
            </details>
            @endcan
            @endif
            {{-- a Sharet pay link for what is due, texted to the borrower --}}
            @if (in_array($loan->status, \App\Services\Mpesa\PaymentRecorder::PAYABLE, true) && \App\Services\Sharet\SharetCollections::available())
            @can('create repayments')
            <form method="POST" action="{{ route('sharet.pay-link', $loan) }}">
                @csrf
                <button class="w-full flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5 transition-colors"
                        data-confirm="Text the borrower a Sharet pay link for what is due now?">Send Sharet pay link</button>
            </form>
            @endcan
            @endif
            <!-- Rollover Loan -->
            @if(in_array($loan->status, ['active', 'overdue', 'disbursed']) && !$loan->isDefaulted() && !$loan->isInForbearance())
            <button @click="window.openRolloverModal({{ $loan->id }})"
                    class="w-full flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-purple-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Rollover Loan
            </button>
            @endif

            <!-- ============ PAYMENT PLAN / FORBEARANCE BUTTON ============ -->
            @if(in_array($loan->status, ['active', 'overdue', 'disbursed']) && !$loan->isDefaulted() && !$loan->isInForbearance())
            <button onclick="openPaymentPlanModal({{ $loan->id }})"
                    class="w-full flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Payment Plan / Forbearance
            </button>
            @endif

            <!-- Edit current cycle: another product, or a due date and rate set by staff; open cycle only (owner, 1 Nov 2026) -->
            @can('edit loans')
            @php $editCycle = \App\Services\LoanCalculator::v2() && in_array($loan->status, ['active', 'overdue', 'disbursed', 'forbearance']) ? \App\Services\Loans\ProductChange::cycleFor($loan) : null; @endphp
            @if($editCycle)
            @php
                $cycleTypes = \App\Models\LoanType::orderBy('name')->get();
                $cycleTypesJs = $cycleTypes->map(fn ($lt) => ['id' => (string) $lt->id, 'name' => $lt->name, 'n' => (int) $lt->period, 'unit' => (string) $lt->unit,
                    'label' => $lt->period . ' ' . $lt->unit . ' · ' . (float) $lt->interest_rate . '%'])->values();
            @endphp
            <details class="rounded-lg border border-gray-300 dark:border-gray-700"
                     x-data="{ t: '{{ $loan->loan_type_id }}', due: '', rate: '', p: null, err: '', busy: false, timer: null, pickDue: false,
                               types: @js($cycleTypesJs), cur: '{{ $loan->loan_type_id }}',
                               start: '{{ \Illuminate\Support\Carbon::parse($editCycle->start_date)->toDateString() }}',
                               curDue: '{{ \Illuminate\Support\Carbon::parse($editCycle->due_date)->toDateString() }}',
                               today: new Date().toLocaleDateString('en-CA'),
                               day(d) { return d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) : ''; },
                               {{-- quick picks (owner, 1 Nov 2026): one pill per period and rate, and the due date counted from the start, the due date or today --}}
                               pills() { const seen = {}; this.types.forEach(x => { if (!seen[x.label] || x.id === this.cur) seen[x.label] = x; }); return Object.values(seen); },
                               choose(x) { this.t = x.id; this.due = ''; this.rate = ''; this.pickDue = false; this.go(); },
                               dueChoices() {
                                 const x = this.types.find(x => x.id === String(this.t)), add = d => window.azAddPeriod(d, x.n, x.unit);
                                 return [['start', 'From cycle start', add(this.start)], ['due', 'From due date', add(this.curDue)], ['today', 'From today', add(this.today)]]
                                   .map(([key, label, date]) => ({ key, label: label + ' · ' + this.day(date), date, off: date <= this.start })).concat([{ key: 'pick', label: 'Pick a date', date: null }]);
                               },
                               dueMode() { if (this.pickDue) return 'pick'; const c = this.dueChoices().find(c => c.date && c.date === this.due); return c ? c.key : (this.due ? 'pick' : ''); },
                               setDue(c) {
                                 this.pickDue = c.key === 'pick';
                                 if (c.date) { this.due = c.date; this.go(); return; }
                                 this.$nextTick(() => { const fp = this.$refs.due && this.$refs.due._azPicker; fp ? fp.open() : this.$refs.due.focus(); });
                               },
                               kes(n) { return 'KES ' + Number(n || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                               go() { clearTimeout(this.timer); this.timer = setTimeout(() => this.load(), 300); },
                               async load() {
                                 const q = new URLSearchParams();
                                 if (this.t !== '{{ $loan->loan_type_id }}') q.set('loan_type_id', this.t);
                                 if (this.due) q.set('due_date', this.due);
                                 if (this.rate !== '') q.set('interest_rate', this.rate);
                                 this.err = ''; if (![...q.keys()].length) { this.p = null; return; }
                                 this.busy = true;
                                 try {
                                   const r = await fetch('{{ route('loans.product-change.preview', $loan) }}?' + q, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                                   const b = await r.json().catch(() => ({}));
                                   this.p = r.ok ? b.data : null; this.err = r.ok ? '' : (b.message || (b.errors ? Object.values(b.errors).flat().join(' ') : 'Could not work it out.'));
                                 } catch (e) { this.err = 'Could not reach the server.'; } finally { this.busy = false; }
                               } }">
                <summary class="flex cursor-pointer items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300">Edit current cycle</summary>
                <form method="POST" action="{{ route('loans.cycle.edit', $loan) }}" class="space-y-2 p-3">
                    @csrf
                    <div class="flex flex-wrap gap-1.5" role="group" aria-label="Product">
                        <template x-for="x in pills()" :key="x.id">
                            <button type="button" @click="choose(x)" :title="x.name" x-text="x.label"
                                    :class="String(t) === x.id ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300'"
                                    class="rounded-full border px-2.5 py-1 text-xs font-medium"></button>
                        </template>
                    </div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Product
                        <select name="loan_type_id" x-model="t" @change="due = ''; rate = ''; pickDue = false; go()" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                            @foreach($cycleTypes as $lt)
                                <option value="{{ $lt->id }}">{{ $lt->name }} · {{ $lt->period }} {{ $lt->unit }} · {{ (float) $lt->interest_rate }}%{{ $lt->id === $loan->loan_type_id ? ' (current)' : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex flex-wrap gap-1.5" role="group" aria-label="Due date">
                        <template x-for="c in dueChoices()" :key="c.key">
                            <button type="button" @click="setDue(c)" :disabled="!!c.off" x-text="c.label"
                                    :class="dueMode() === c.key ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300'"
                                    class="rounded-full border px-2.5 py-1 text-xs font-medium disabled:cursor-not-allowed disabled:opacity-40"></button>
                        </template>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Due date
                            <input type="date" name="due_date" x-ref="due" x-model="due" @change="go()" min="{{ \Illuminate\Support\Carbon::parse($editCycle->start_date)->addDay()->toDateString() }}" placeholder="{{ \Illuminate\Support\Carbon::parse($editCycle->due_date)->format('j M Y') }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                        </label>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Interest %
                            <input type="number" name="interest_rate" x-model="rate" @input="go()" step="0.01" min="0" max="100" placeholder="{{ (float) ($editCycle->interest_rate ?? $loan->loanType?->interest_rate) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                        </label>
                    </div>
                    <p class="text-xs text-gray-500">Cycle {{ $editCycle->cycle_number }} started {{ \Illuminate\Support\Carbon::parse($editCycle->start_date)->format('j M Y') }}, due {{ \Illuminate\Support\Carbon::parse($editCycle->due_date)->format('j M Y') }}. Leave a field empty to keep it (or take the product's).</p>
                    <p x-show="err" x-text="err" class="rounded bg-red-50 px-2 py-1 text-xs text-red-700"></p>
                    <template x-if="p">
                        <table class="w-full text-left text-xs">
                            <thead class="text-gray-500"><tr><th></th><th>Now</th><th>After</th></tr></thead>
                            <tbody class="text-gray-800 dark:text-gray-200">
                                <tr><td class="text-gray-500">Due</td><td x-text="p.before.due_date"></td><td class="font-semibold" x-text="p.after.due_date"></td></tr>
                                <tr><td class="text-gray-500">Rate</td><td x-text="p.before.interest_rate + '%'"></td><td class="font-semibold" x-text="p.after.interest_rate + '%'"></td></tr>
                                <tr><td class="text-gray-500">Interest</td><td x-text="kes(p.before.interest)"></td><td class="font-semibold" x-text="kes(p.after.interest)"></td></tr>
                                <tr><td class="text-gray-500">Penalty</td><td x-text="kes(p.before.penalty)"></td><td class="font-semibold" x-text="kes(p.after.penalty)"></td></tr>
                                <tr><td class="text-gray-500">Owed today</td><td x-text="kes(p.before.owed_today)"></td><td class="font-semibold" x-text="kes(p.after.owed_today)"></td></tr>
                            </tbody>
                        </table>
                    </template>
                    <x-staff.field name="reason" label="Reason" :value="old('reason')" placeholder="Agreed 5 days instead of 10" required />
                    <button :disabled="busy || !!err || !p" class="w-full rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">Save cycle changes</button>
                </form>
            </details>
            @endif
            @endcan

            <!-- Short payment accepted as the cycle's full amount: the cycle is fitted to the cash, no repayment row (owner, 1 Nov 2026) -->
            @can('edit loans')
            @php $short = \App\Services\Loans\ShortfallFit::check($loan); @endphp
            @if($short && $short['ok'])
            <details class="rounded-lg border border-amber-300 dark:border-amber-500/40">
                <summary class="flex cursor-pointer items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-amber-800 dark:text-amber-300">
                    Short by KES {{ number_format($short['gap'], 2) }} · close at amount paid
                </summary>
                <form method="POST" action="{{ route('loans.shortfall.accept', $loan) }}" class="space-y-2 p-3">
                    @csrf
                    <dl class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs">
                        <dt class="text-gray-500">Cash paid</dt><dd class="text-right font-medium">KES {{ number_format($short['cash'], 2) }}</dd>
                        <dt class="text-gray-500">Interest now</dt><dd class="text-right">KES {{ number_format($short['old_interest'], 2) }}</dd>
                        <dt class="text-gray-500">Interest after</dt><dd class="text-right font-semibold text-amber-700 dark:text-amber-300">KES {{ number_format($short['new_interest'], 2) }}</dd>
                        @if($short['grace_days'] > (int) $short['cycle']->grace_days)
                            <dt class="text-gray-500">Grace days</dt><dd class="text-right">{{ $short['grace_days'] }} (paid late; no penalty)</dd>
                        @endif
                    </dl>
                    <x-staff.field name="reason" label="Reason" :value="old('reason')" placeholder="Paid 25,000 of 25,200; agreed" required />
                    <button class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">Close cycle {{ $short['cycle']->cycle_number }} at KES {{ number_format($short['cash'], 2) }}</button>
                    <p class="text-xs text-gray-500">Worked backwards from the cash received: the cycle's interest is lowered by the shortfall. No repayment or discount is recorded; only real cash stays on the loan. Limit KES {{ number_format(\App\Services\Loans\ShortfallFit::limit()) }}.</p>
                </form>
            </details>
            @endif
            @endcan

            <!-- Grace days on the current cycle: no penalty for a short delay (owner, 1 Nov 2026) -->
            @can('grant forbearance')
            @if(in_array($loan->status, ['active', 'overdue', 'disbursed', 'forbearance', 'defaulted', 'repaid']))
            @php
                $graceCycle = $loan->getCurrentCycle() ?? $loan->cycles()->orderByDesc('cycle_number')->first();
                $graceLate = $graceCycle && $graceCycle->due_date ? max(0, (int) \Illuminate\Support\Carbon::parse($graceCycle->due_date)->startOfDay()->diffInDays(now()->startOfDay(), false)) : 0;
            @endphp
            @if($graceCycle)
            <details class="rounded-lg border border-gray-300 dark:border-gray-700">
                <summary class="flex cursor-pointer items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Grace days{{ $graceCycle->grace_days ? ' · ' . $graceCycle->grace_days . ' now' : '' }}
                </summary>
                <form method="POST" action="{{ route('loans.grace', $loan) }}" class="space-y-2 p-3">
                    @csrf
                    <x-staff.field name="grace_days" type="number" label="Days with no penalty after the due date" :value="old('grace_days', $graceCycle->grace_days ?: $graceLate)" min="0" max="60" step="1" required />
                    <x-staff.field name="reason" label="Reason" :value="old('reason')" placeholder="Paid a day late (M-Pesa delay)" required />
                    <button class="w-full rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Save grace days</button>
                    <p class="text-xs text-gray-500">Cycle {{ $graceCycle->cycle_number }}, due {{ \Illuminate\Support\Carbon::parse($graceCycle->due_date)->format('j M Y') }}{{ $graceLate ? ', ' . $graceLate . ' day(s) late today' : '' }}. Penalty starts only after these days; the due date stays the same.</p>
                </form>
            </details>
            @endif
            @endif
            @endcan

            <!-- End Forbearance -->
            @if($loan->isInForbearance())
            <button type="button" data-confirm="End forbearance for this loan?" data-confirm-submit="end-forbearance-form" 
                    class="w-full flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                End Forbearance
            </button>
            <form id="end-forbearance-form" action="{{ route('loans.forbearance.end', $loan) }}" method="POST" style="display:none;">
                @csrf
                @method('PATCH')
            </form>
            @endif


            <!-- Start Recovery -->
            @if($loan->isDefaulted() && !$loan->isInRecovery())
            <button type="button" data-confirm="Start the recovery process for this loan?" data-confirm-submit="start-recovery-form" 
                    class="w-full flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-purple-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Start Recovery
            </button>
            <form id="start-recovery-form" action="{{ route('loans.recovery.start', $loan) }}" method="POST" style="display:none;">
                @csrf
                @method('PATCH')
            </form>
            @endif

            <!-- Create Recovery Case -->
            @if($loan->is_non_performing || $loan->isOverdue())
            <button onclick="openCaseModal({{ $loan->user_id }}, {{ $loan->id }})" 
                    class="w-full flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                Create Recovery Case
            </button>
            @endif
        </div>

        <!-- Rest of the existing sidebar content -->
        <div class="space-y-4 mt-4">
            <div>
                <p class="text-sm font-medium text-gray-600">Loan ID</p>
                <p class="text-lg font-bold">#{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}</p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-600">Borrower</p>
                <p class="font-medium">{{ $loan->user->name ?? 'N/A' }}</p>
                <p class="text-xs text-gray-500">ID: {{ $loan->user_id }}</p>
                {{-- a business loan (owner, 24 Oct 2026): the owner signs, the business borrows --}}
                @if ($loan->business_id && ($biz = $loan->business))
                    <p class="mt-1 text-xs text-gray-600">For <a class="font-medium text-brand-500 hover:underline" href="{{ route('business-reviews.show', $biz) }}">{{ $biz->legal_name }}</a></p>
                @endif
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-600">Loan Type</p>
                <p class="font-medium">{{ $loan->loanType->name ?? 'N/A' }}</p>
                <p class="text-xs text-gray-500">{{ $metrics['interest_rate'] ?? 0 }}% for {{ $metrics['period'] ?? 0 }} {{ $metrics['period_unit'] ?? 'days' }}</p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-600">Client Type</p>
                <p class="font-medium">
                    {{ ($client_type == 0) ? 'Our Client' : 'Broker Client' }} 
                    <span class="text-xs ml-1 px-2 py-0.5 rounded 
                        {{ $client_type == 0 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ $client_type == 0 ? 'Direct' : 'Brokered' }}
                    </span>
                </p>
            </div>
            
            <div class="pt-4">
                <a href="{{ route('loans.generatePdf', ['loan' => $loan->id, 'loanId' => $loan->id]) }}" 
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Download PDF
                </a>
            </div>

            <!-- Agreement Buttons -->
            <div class="pt-4 space-y-2">
                <a href="{{ route('loans.agreement.download', $loan->id) }}" 
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download Agreement
                </a>
                
                <a href="{{ route('loans.agreement.show', $loan->id) }}" 
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-green-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    View Agreement
                </a>
            </div>

            <!-- Risk Assessment (4Cs: staff assessment if any, otherwise the system score as at the borrow date) -->
            @php $latestAssessment = $loan->getLatestRiskAssessment(); @endphp
            @if($latestAssessment)
            @php
                $riskTone = match (true) {
                    $latestAssessment->overall_score >= 80 => 'text-green-600 bg-green-50',
                    $latestAssessment->overall_score >= 65 => 'text-yellow-700 bg-yellow-50',
                    $latestAssessment->overall_score >= 50 => 'text-orange-600 bg-orange-50',
                    default => 'text-red-600 bg-red-50',
                };
                $f = $latestAssessment->factors ?? [];
            @endphp
            <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-md font-semibold">Risk Assessment (4Cs)</h3>
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $riskTone }}">{{ $latestAssessment->getRiskCategory() }} · {{ $latestAssessment->overall_score }}/100</span>
                </div>
                <div class="space-y-2">
                    @foreach (['character' => 'Character', 'capacity' => 'Capacity', 'capital' => 'Capital', 'conditions' => 'Conditions'] as $c => $label)
                        @php $v = (int) $latestAssessment->{$c . '_score'}; @endphp
                        <div>
                            <div class="flex justify-between text-xs"><span class="text-gray-500">{{ $label }}</span><span class="font-medium">{{ $v }}</span></div>
                            <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700"><div class="h-1.5 rounded-full {{ $v >= 65 ? 'bg-green-500' : ($v >= 50 ? 'bg-orange-400' : 'bg-red-500') }}" style="width: {{ max(3, $v) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
                @if ($latestAssessment->recommendation)
                    <p class="mt-3 text-sm">{{ $latestAssessment->recommendation }}</p>
                @endif
                @if ($latestAssessment->limit_amount)
                    <p class="mt-1 text-xs text-gray-500">Limit at the time: KES {{ number_format($latestAssessment->limit_amount, 2) }}</p>
                @endif
                @if ($latestAssessment->assessment_notes)
                    <p class="mt-1 text-xs text-gray-500">{{ $latestAssessment->assessment_notes }}</p>
                @endif
                @if (! empty($f['outcome']['label']) && $f['outcome']['status'] !== 'not_taken')
                    <p class="mt-1 text-xs text-gray-500">This loan: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $f['outcome']['label'] }}</span></p>
                @endif
                <p class="mt-1 text-xs text-gray-400">{{ $latestAssessment->source === 'auto' ? 'System score as at ' . optional($latestAssessment->assessed_at)->format('j M Y') . ', from repayment history' : 'Assessed by ' . ($latestAssessment->assessor?->name ?? 'staff') }}</p>
            </div>
            @endif

            <!-- Additional Information -->
            @if($loan->guarantor_id || $loan->loan_officer_id || $loan->consent || $hasSignature)
            <div class="border-t border-gray-200 pt-4 mt-4">
                <h3 class="text-md font-semibold mb-3">Additional Information</h3>
                
                @if($loan->guarantor_id && $loan->guarantor)
                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-600">Guarantor</p>
                    <p class="text-sm">{{ $loan->guarantor->name }}</p>
                    @if($loan->guarantor_relationship)
                        <p class="text-xs text-gray-500">Relationship: {{ $loan->guarantor_relationship }}</p>
                    @endif
                </div>
                @endif
                
                @if($loan->loan_officer_id && $loan->loanOfficer)
                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-600">Loan Officer</p>
                    <p class="text-sm">{{ $loan->loanOfficer->name }}</p>
                    <p class="text-xs text-gray-500">{{ ucfirst($loan->loanOfficer->role) }}</p>
                </div>
                @endif
                
                @if($loan->consent)
                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-600">Consent Given</p>
                    <p class="text-sm text-green-600"><x-ui.icon name="check" class="inline-block size-4 align-[-3px]" /> Agreed on {{ $loan->consent_date->format('M d, Y') }}</p>
                </div>
                @endif
                
                <!-- Signature Display -->
                @if($hasSignature && $loan->user && $loan->user->signature)
                <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                    <h3 class="text-lg font-medium mb-4">Digital Signature</h3>
                    
                    <div class="flex items-center space-x-6 p-5 border border-gray-300 rounded-2xl bg-white shadow-sm dark:bg-gray-900">
                        <div class="flex-shrink-0 p-2 bg-white border border-gray-200 rounded-xl overflow-hidden">
                            <div class="flex items-center justify-center h-24 w-40 bg-transparent">
                                <img src="{{ $loan->user->documentUrl('signature') }}" 
                                    alt="Signature of {{ $loan->user->name }}"
                                    class="max-h-20 object-contain object-center">
                            </div>
                        </div>

                        <div class="flex flex-col justify-center">
                            <p class="font-semibold text-gray-800 dark:text-white">{{ $loan->user->name }}’s Signature</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Signed on: {{ $loan->consent_date ? $loan->consent_date->format('M j, Y') : 'N/A' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                File: {{ $loan->user->signature }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
    
    <!-- ============ FINANCIAL SUMMARY - CYCLE SPECIFIC ============ -->
    <div class="border-t border-gray-200 pt-4">
        <h3 class="text-md font-semibold mb-3">
            Financial Summary 
            <span class="text-xs font-normal text-gray-500">(Cycle #{{ $cycleCalculation['cycle_number'] ?? $cycleNumber ?? 1 }})</span>
        </h3>
    
        
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Cycle Balance</span>
                <span class="text-sm font-medium">KES {{ number_format($cycleBalanceFull, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Interest Capitalized</span>
                <span class="text-sm font-medium">KES {{ number_format($cycleInterest, 2) }}</span>
            </div>
            @if($cyclePenalty > 0)
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Penalties (This Cycle)</span>
                <span class="text-sm font-medium text-red-600">+KES {{ number_format($cyclePenalty, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between">
                <span class="text-sm text-gray-600">Repayments (This Cycle)</span>
                <span class="text-sm font-medium text-green-600">-KES {{ number_format($cycleRepayments, 2) }}</span>
            </div>
            <div class="flex justify-between border-t pt-2">
                <span class="text-sm font-medium">Outstanding (This Cycle)</span>
                <span class="text-sm font-bold @if($cycleOutstanding > 0) text-red-600 @else text-green-600 @endif">
                    KES {{ number_format($cycleOutstanding, 2) }}
                </span>
            </div>
            
            @if($isFullyPaid)
            <div class="mt-1 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-xs text-green-600 dark:text-green-400 font-medium text-center">
                    <x-ui.icon name="check-circle" class="inline-block size-4 align-[-3px]" /> This cycle is fully paid
                </p>
            </div>
            @endif
            
            <!-- Grace Period Info -->
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 border-t pt-2 mt-2">
                <span>Grace Days Remaining</span>
                <span>{{ $cycleGraceDaysRemaining }}</span>
            </div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Grace Days Balance</span>
                <span>{{ $cycleGraceDaysBalance }}</span>
            </div>
            @if($cycleDaysOverdue > 0 && $outstandingAfterRepayments > 0)
            <div class="flex justify-between text-xs text-red-500 dark:text-red-400">
                <span>Days Overdue</span>
                <span>{{ $cycleDaysOverdue }} days</span>
            </div>
            @elseif($cycleDaysOverdue > 0 && $outstandingAfterRepayments <= 0)
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Days Overdue</span>
                <span>{{ $cycleDaysOverdue }} days <span class="text-green-500">(Paid off)</span></span>
            </div>
            @endif
            
            @if($loan->cycle > 1)
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 border-t pt-2 mt-2">
                <span>Total Capitalized Interest (All Cycles)</span>
                <span>KES {{ number_format($loan->cycles->sum('interest_capitalized'), 2) }}</span>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- ============ JAVASCRIPT FOR MODAL TRIGGERS ============ -->
<script>
function openPaymentPlanModal(loanId) {
    window.dispatchEvent(new CustomEvent('open-payment-plan-modal', {
        detail: { loanId: loanId }
    }));
}

// openRolloverModal comes from partials/modal/loan-rollover-modal (owner, 23 Oct 2026)

function openCyclesModal(loanId) {
    window.dispatchEvent(new CustomEvent('open-cycles-modal', {
        detail: { loanId: loanId }
    }));
}

function openForbearanceModal() {
    const event = new CustomEvent('open-forbearance-modal');
    window.dispatchEvent(event);
}

function openCaseModal(userId, loanId) {
    window.dispatchEvent(new CustomEvent('open-case-create', {
        detail: { 
            user_id: userId, 
            loan_id: loanId 
        }
    }));
}
</script>