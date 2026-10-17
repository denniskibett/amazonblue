{{--
  Right-side drawer: a borrower's view of one loan's progress.
  Pass $progress from App\Services\LoanProgress::for($loan). Open it with:
    $dispatch('open-loan-progress')   or   window.dispatchEvent(new CustomEvent('open-loan-progress'))
--}}
@php
    $loan = $progress['loan'];
    $kes = fn ($v) => 'KES ' . number_format((float) $v);
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('j M Y') : null;
    $balance = $progress['balance'];
@endphp
<div x-data="{ open: false }"
     x-on:open-loan-progress.window="open = true; document.body.style.overflow = 'hidden'"
     x-on:keydown.escape.window="if (open) { open = false; document.body.style.overflow = '' }"
     x-effect="if (!open) document.body.style.overflow = ''"
     class="relative z-[99999]" aria-labelledby="loan-progress-title" role="dialog" aria-modal="true">

    {{-- backdrop --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms @click="open = false"
         class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"></div>

    {{-- panel --}}
    <div x-show="open" x-cloak
         x-transition:enter="transform transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-xl dark:bg-gray-900">

        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5 dark:border-gray-800">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $progress['reference'] }} · {{ $loan->loanType->name ?? '' }}</p>
                <h2 id="loan-progress-title" class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90">{{ $kes($loan->amount) }}</h2>
            </div>
            <button type="button" @click="open = false" aria-label="Close"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
            {{-- timeline --}}
            <section>
                <h3 class="mb-4 text-sm font-medium text-gray-500 dark:text-gray-400">Progress</h3>
                <ol class="relative">
                    @foreach ($progress['timeline'] as $step)
                        @php
                            $red = ($step['tone'] ?? null) === 'red';
                            $dot = $red ? 'bg-red-500 text-white'
                                : ($step['done'] ? 'bg-green-500 text-white'
                                : (($step['current'] ?? false) ? 'bg-brand-500 text-white ring-4 ring-brand-500/20' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400'));
                        @endphp
                        <li class="relative flex gap-4 pb-6 last:pb-0">
                            @unless ($loop->last)
                                <span class="absolute left-[13px] top-7 h-[calc(100%-1.75rem)] w-0.5 {{ $step['done'] ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}" aria-hidden="true"></span>
                            @endunless
                            <span class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $dot }}">
                                @if ($step['done'] && ! $red)
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>
                            <div class="min-w-0 pt-0.5">
                                <p class="text-sm font-medium {{ $red ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-white/90' }}">
                                    {{ $step['label'] }}
                                    @if ($step['date'])
                                        <span class="ml-1 font-normal text-gray-500 dark:text-gray-400">· {{ $date($step['date']) }}</span>
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs {{ $red ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $step['detail'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- money --}}
            @if ($balance)
                <section class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/60">
                    <dl class="grid grid-cols-2 gap-4">
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Total to repay</dt><dd class="mt-1 font-semibold text-gray-800 dark:text-white/90">{{ $kes($balance['total']) }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Paid so far</dt><dd class="mt-1 font-semibold text-gray-800 dark:text-white/90">{{ $kes($balance['paid']) }}</dd></div>
                        @if ($balance['penalty'] > 0)
                            <div><dt class="text-xs text-gray-500 dark:text-gray-400">Late penalty</dt><dd class="mt-1 font-semibold text-red-600 dark:text-red-400">{{ $kes($balance['penalty']) }}</dd></div>
                        @endif
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Amount due</dt><dd class="mt-1 font-semibold {{ $progress['is_late'] ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-white/90' }}">{{ $kes($balance['due']) }}</dd></div>
                        <div><dt class="text-xs text-gray-500 dark:text-gray-400">Due date</dt><dd class="mt-1 font-semibold text-gray-800 dark:text-white/90">{{ $date($progress['due_date']) ?? '—' }}</dd></div>
                    </dl>
                    <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ $progress['progress'] }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $progress['progress'] }}% repaid</p>
                </section>
            @endif

            {{-- repayments --}}
            <section>
                <h3 class="mb-3 text-sm font-medium text-gray-500 dark:text-gray-400">Payments</h3>
                @forelse ($progress['repayments'] as $payment)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2.5 text-sm last:border-0 dark:border-gray-800">
                        <div>
                            <p class="font-medium text-gray-800 dark:text-white/90">{{ $kes($payment->amount) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $payment->mode ?: 'Payment' }}{{ $payment->transaction ? ' · ' . $payment->transaction : '' }}</p>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $date($payment->repayment_date) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No payments recorded yet. Pay by M-Pesa and our team records it here.</p>
                @endforelse
            </section>

            {{-- cycles (only when the loan has been rolled over) --}}
            @if ($progress['cycles']->count() > 1)
                <section>
                    <h3 class="mb-3 text-sm font-medium text-gray-500 dark:text-gray-400">Repayment periods</h3>
                    @foreach ($progress['cycles'] as $cycle)
                        <div class="flex items-center justify-between border-b border-gray-100 py-2.5 text-sm last:border-0 dark:border-gray-800">
                            <span class="text-gray-800 dark:text-white/90">Period {{ $cycle->cycle_number }}</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $kes($cycle->new_balance) }} · due {{ $date($cycle->due_date) }}</span>
                        </div>
                    @endforeach
                </section>
            @endif
        </div>

        @if ($progress['has_agreement'])
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                <a href="{{ route('loans.agreement.show', $loan->id) }}" target="_blank" rel="noopener"
                   class="flex h-11 w-full items-center justify-center rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    View signed loan agreement
                </a>
            </div>
        @endif
    </div>
</div>
