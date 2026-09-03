@extends('layouts.app')

@section('content')
<div class="flex h-full flex-col gap-6 sm:gap-5 xl:flex-row"
     x-data="loanShow()"
     x-init="init()">

    @include('partials.grid.loan-sidebar-grid')

    <!-- Loan Details Main Content -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:w-4/5">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h3 class="text-xl font-medium text-gray-800 dark:text-white/90">
                Loan Statement
            </h3>
            <h4 class="text-base font-medium text-gray-700 dark:text-gray-400">
                ID : #{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}
            </h4>
        </div>
        
        <!-- Default Warning Banner -->
        @if($is_defaulted || $loan->status === 'defaulted')
        <div class="mt-3 p-4 rounded-lg border-2 border-red-500 bg-red-50 dark:bg-red-900/20 animate-pulse">
            <div class="flex items-center gap-3">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                    <h4 class="font-bold text-red-800 dark:text-red-300">⚠️ LOAN DEFAULTED</h4>
                    <p class="text-sm text-red-700 dark:text-red-400">
                        This loan has been defaulted due to {{ $metrics['days_overdue'] ?? 0 }} days of non-payment.
                        Penalties have been capped at KES {{ number_format($metrics['penalty_amount'] ?? 0, 2) }}.
                    </p>
                    <p class="text-xs text-red-600 dark:text-red-500 mt-1">
                        Default threshold: {{ $metrics['default_threshold_days'] ?? 30 }} days
                    </p>
                </div>
            </div>
        </div>
        @endif

        <div class="p-5 xl:p-8">
            
            <div class="mb-9 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        From
                    </span>
                    <h5 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">
                        AmazonBlue Capital
                    </h5>
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                        G.P.O 50054 - 00100,<br>
                        Nairobi, Kenya
                    </p>
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Issued On:
                    </span>
                    <span class="block text-sm text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($loan->borrow_date)->format('D, M d, Y') }}
                    </span>
                </div>

                <div class="h-px w-full bg-gray-200 dark:bg-gray-800 sm:h-[158px] sm:w-px"></div>

                <div class="sm:text-right">
                    <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        To
                    </span>
                    <h5 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">
                        {{ $loan->user->name ?? 'N/A' }}
                    </h5>
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $loan->user->phone ?? 'Phone not available' }}<br>
                        {{ $loan->user->email ?? '' }}<br>
                        {{ $loan->user->country ?? '' }}
                    </p>
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Due On:
                    </span>
                    <span class="block text-sm text-gray-500 dark:text-gray-400">
                        {{ $metrics['due_date'] ? $metrics['due_date']->format('D, M d, Y') : 'N/A' }}
                        @if(($metrics['days_late'] ?? 0) > 0)
                            <span class="text-red-500 ml-2">({{ round($metrics['days_late']) }} days late)</span>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Loan Charges Table -->
            <div class="mb-6 overflow-hidden rounded-2xl border border-gray-100 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="max-w-full overflow-x-auto">
                    <div class="min-w-[1026px]">
                        <!-- table header -->
                        <div class="grid grid-cols-11 px-5 py-3 bg-gray-50">
                            <div class="col-span-1 flex items-center">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-400">#</p>
                            </div>
                            <div class="col-span-5 flex items-center">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Charge Description</p>
                            </div>
                            <div class="col-span-2 flex items-center">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Rate</p>
                            </div>
                            <div class="col-span-3 flex items-center">
                                <p class="w-full text-right text-sm font-medium text-gray-700 dark:text-gray-400">Amount (KES)</p>
                            </div>
                        </div>

                        <!-- Principal -->
                        <div class="grid grid-cols-11 border-t border-gray-100 px-5 py-3.5 dark:border-gray-800">
                            <div class="col-span-1 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">1</p>
                            </div>
                            <div class="col-span-5 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Principal Amount</p>
                            </div>
                            <div class="col-span-2 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                            </div>
                            <div class="col-span-3 flex items-center justify-end">
                                <p class="text-right text-sm text-gray-500 dark:text-gray-400">{{ number_format($metrics['principal'] ?? $loan->amount, 2) }}</p>
                            </div>
                        </div>

                        <!-- Interest -->
                        <div class="grid grid-cols-11 border-t border-gray-100 px-5 py-3.5 dark:border-gray-800">
                            <div class="col-span-1 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">2</p>
                            </div>
                            <div class="col-span-5 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Interest ({{ number_format($metrics['interest_rate'] ?? 0, 0) }}%)</p>
                            </div>
                            <div class="col-span-2 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                            </div>
                            <div class="col-span-3 flex items-center justify-end">
                                <p class="text-right text-sm text-gray-500 dark:text-gray-400">{{ number_format($metrics['interest'] ?? 0, 2) }}</p>
                            </div>
                        </div>

                        <!-- Penalties - Use service calculated values -->

                                                
                        <!-- Penalties -->
                        @if($penaltyAmount > 0)
                        <div class="grid grid-cols-11 border-t border-gray-100 px-5 py-3.5 dark:border-gray-800">
                            <div class="col-span-1 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">3</p>
                            </div>
                            <div class="col-span-5 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Penalties ({{ number_format($penaltyRate, 0) }}% daily)</p>
                            </div>
                            <div class="col-span-2 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    KES {{ number_format( $outstandingAtDue * ($penaltyRate / 100), 2) }} × {{ round($daysSubjectToPenalty) }} days
                                </p>
                            </div>
                            <div class="col-span-3 flex items-center justify-end">
                                <p class="text-right text-sm text-red-500 dark:text-red-400">
                                    {{ number_format($penaltyAmount, 2) }}
                                </p>
                            </div>
                        </div>
                        @endif

                        <!-- Broker Fees -->
                        @if($is_brokered)
                        <div class="grid grid-cols-11 border-t border-gray-100 px-5 py-3.5 dark:border-gray-800">
                            <div class="col-span-1 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">4</p>
                            </div>
                            <div class="col-span-5 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Broker Fees (Interest {{ number_format($brokerRate, 0) }}% + Penalties {{ number_format($penalty_rate, 0) }}%)</p>
                            </div>
                            <div class="col-span-2 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">KES {{ number_format($total_broker_fees, 2) }}</p>
                            </div>
                            <div class="col-span-3 flex items-center justify-end">
                                <p class="text-right text-sm text-gray-500 dark:text-gray-400">-</p>
                            </div>
                        </div>
                        @endif

                        <!-- Total Repayments (Current Cycle Only) -->
                        <div class="grid grid-cols-11 border-t border-gray-100 px-5 py-3.5 dark:border-gray-800">
                            <div class="col-span-1 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">5</p>
                            </div>
                            <div class="col-span-5 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Total Repayments (Cycle #{{ $cycleNumber ?? 1 }})</p>
                            </div>
                            <div class="col-span-2 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                            </div>
                            <div class="col-span-3 flex items-center justify-end">
                                <p class="text-right text-sm text-green-500 dark:text-green-400">-{{ number_format($cycleRepayments, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary - Cycle Specific -->
            <div class="my-6 border-b border-gray-100 pb-6 dark:border-gray-800">
                @php
                    // ALL calculations come from the service
                    $cycleNumber = $cycleCalculation['cycle_number'] ?? $activeCycle->cycle_number ?? 1;
                    $newCycleBalance = $cycleCalculation['new_balance'] ?? 0;
                    $cycleBalanceFull = $cycleCalculation['new_balance'] ?? $metrics['principal_plus_interest'] ?? 0;
                    $cyclePenalty = $cycleCalculation['penalty'] ?? 0;
                    $cycleRepaymentsTotal = $cycleCalculation['total_repayments'] ?? 0;
                    $cycleOutstanding = $cycleCalculation['final_outstanding'] ?? 0;
                    $cycleDaysOverdue = $cycleCalculation['days_overdue'] ?? 0;
                    $outstandingAfterRepayments = $cycleCalculation['outstanding_after_repayments'] ?? 0;
                    $isFullyPaid = $cycleOutstanding <= 0;
                @endphp
                
                <div class="flex justify-between mb-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Cycle #{{ $cycleNumber }} Balance (Principal + Interest):</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($cycleBalanceFull, 2) }}</p>
                </div>
                
                @if($cyclePenalty > 0)
                <div class="flex justify-between mb-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Penalties (This Cycle):</p>
                    <p class="text-sm text-red-600 dark:text-red-400">+KES {{ number_format($cyclePenalty, 2) }}</p>
                </div>
                @endif
                
                <div class="flex justify-between mb-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Repayments (This Cycle):</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">-KES {{ number_format($cycleRepaymentsTotal, 2) }}</p>
                </div>

                <div class="flex justify-between pt-4 border-t">
                    <p class="text-lg font-semibold text-gray-800 dark:text-white/90">Balance Due (Cycle #{{ $cycleNumber }}):</p>
                    <p class="text-lg font-bold @if($cycleOutstanding > 0) text-red-600 @else text-green-600 @endif">
                        KES {{ number_format($cycleOutstanding, 2) }}
                    </p>
                </div>
                
                @if($isFullyPaid)
                <div class="mt-2 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <p class="text-sm text-green-600 dark:text-green-400 font-medium text-center">
                        ✅ This cycle is fully paid
                    </p>
                </div>
                @endif
                
                <!-- Grace Period Info -->
                <div class="flex justify-between pt-2 text-xs text-gray-500 dark:text-gray-400 border-t mt-2">
                    <span>Grace Days Remaining</span>
                    <span>{{ $loan->getRemainingGraceDays() }}</span>
                </div>
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Grace Days Balance</span>
                    <span>{{ $loan->grace_days_balance ?? 0 }}</span>
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
                <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500 border-t mt-2 pt-2">
                    <span>Total Capitalized Interest (All Cycles)</span>
                    <span>KES {{ number_format($loan->cycles->sum('interest_capitalized'), 2) }}</span>
                </div>
                @endif
            </div>

            <!-- Rollover Statement Section -->
            @if($loan->cycles && $loan->cycles->count() > 1)
            <div class="mb-8 border border-purple-200 rounded-lg overflow-hidden dark:border-purple-800">
                <div class="bg-purple-50 px-4 py-3 border-b border-purple-200 dark:bg-purple-900/20 dark:border-purple-800">
                    <h4 class="font-semibold text-purple-800 dark:text-purple-300 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Rollover Statement
                        <span class="ml-auto text-xs font-normal text-purple-600 dark:text-purple-400">
                            {{ $loan->cycles->count() }} cycles total
                        </span>
                    </h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Cycle</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Previous Balance</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Interest Capitalized</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">New Balance</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Due Date</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @php
                                $sortedCycles = $loan->cycles->sortBy('cycle_number');
                            @endphp
                            @foreach($sortedCycles as $cycle)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">#{{ $cycle->cycle_number }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $cycle->start_date->format('M d, Y') }}</td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">KES {{ number_format($cycle->previous_balance, 2) }}</td>
                                <td class="px-4 py-2 text-right text-purple-600 dark:text-purple-400">KES {{ number_format($cycle->interest_capitalized, 2) }}</td>
                                <td class="px-4 py-2 text-right font-medium text-gray-900 dark:text-white">KES {{ number_format($cycle->new_balance, 2) }}</td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $cycle->due_date->format('M d, Y') }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                        @if($cycle->status === 'active') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @elseif($cycle->status === 'completed') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                        @elseif($cycle->status === 'defaulted') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        {{ $cycle->status_label }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                            <tr>
                                <td colspan="2" class="px-4 py-2 font-medium text-gray-700 dark:text-gray-300">Total Capitalized Interest</td>
                                <td colspan="5" class="px-4 py-2 text-right font-bold text-purple-700 dark:text-purple-400">
                                    KES {{ number_format($loan->cycles->sum('interest_capitalized'), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            <!-- Disbursements and Repayments - Current Cycle Only -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Disbursements -->
                <div class="bg-white p-4 rounded-lg shadow border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-semibold">Disbursements</h3>
                        @if(auth()->user()->role !== 'borrower')
                            <button onclick="openDisbursementModal({{ $loan->id }})" 
                                class="text-xs bg-blue-500 hover:bg-blue-700 text-white py-1 px-2 rounded">
                                + Add
                            </button>
                        @endif
                    </div>
                    
                    @if($loan->disbursements->isEmpty())
                        <p class="text-gray-500 text-sm">No disbursements found.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($loan->disbursements as $disbursement)
                                <div class="flex justify-between items-center border-b pb-2 dark:border-gray-700">
                                    <div>
                                        <p class="text-sm font-medium">KES {{ number_format($disbursement->amount, 2) }}</p>
                                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($disbursement->disburse_date)->format('M d, Y H:i') }}</p>
                                        @if($disbursement->transaction_ref)
                                            <p class="text-xs text-gray-400">Ref: {{ $disbursement->transaction_ref }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">{{ $disbursement->transaction ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-400">{{ $disbursement->mode ?? '' }}</p>
                                        @if(auth()->user()->role !== 'borrower')
                                        <div class="flex space-x-1 mt-1 justify-end">
                                            <button onclick="openDisbursementModal(null, {{ json_encode($disbursement) }})" 
                                                class="text-blue-600 text-xs hover:underline">Edit</button>
                                            <form action="{{ route('disbursements.destroy', $disbursement->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 text-xs hover:underline ml-1" onclick="return confirm('Are you sure?');">Delete</button>
                                            </form>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <div class="flex justify-between font-semibold pt-2">
                                <span class="text-sm">Total:</span>
                                <span class="text-sm">KES {{ number_format($loan->disbursements->sum('amount'), 2) }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Repayments - Current Cycle -->
                <div class="bg-white p-4 rounded-lg shadow border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-semibold">
                            Repayments 
                            <span class="text-xs font-normal text-gray-500">(Cycle #{{ $cycleNumber ?? 1 }})</span>
                        </h3>
                        @if(auth()->user()->role !== 'borrower')
                            <button onclick="openRepaymentModal({{ $loan->id }})" 
                                class="text-xs bg-green-500 hover:bg-green-700 text-white py-1 px-2 rounded">
                                + Add
                            </button>
                        @endif
                    </div>
                    
                    @php
                        $cycleRepaymentsList = $activeCycle ? $loan->repayments()->where('loan_cycle_id', $activeCycle->id)->get() : collect();
                    @endphp
                    
                    @if($cycleRepaymentsList->isEmpty())
                        <p class="text-gray-500 text-sm">No repayments for current cycle.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($cycleRepaymentsList as $repayment)
                                <div class="flex justify-between items-center border-b pb-2 dark:border-gray-700">
                                    <div>
                                        <p class="text-sm font-medium">KES {{ number_format($repayment->amount, 2) }}</p>
                                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($repayment->repayment_date)->format('M d, Y H:i') }}</p>
                                        @if($repayment->transaction_ref)
                                            <p class="text-xs text-gray-400">Ref: {{ $repayment->transaction_ref }}</p>
                                        @endif
                                        <p class="text-xs text-green-600">Cycle #{{ $activeCycle->cycle_number ?? 1 }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">{{ $repayment->transaction ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-400">{{ $repayment->mode ?? '' }}</p>
                                        @if(auth()->user()->role !== 'borrower')
                                        <div class="flex space-x-1 mt-1 justify-end">
                                            <button onclick="openRepaymentModal(null, {{ json_encode($repayment) }})" 
                                                class="text-blue-600 text-xs hover:underline">Edit</button>
                                            <form action="{{ route('repayments.destroy', $repayment->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 text-xs hover:underline ml-1" onclick="return confirm('Are you sure?');">Delete</button>
                                            </form>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <div class="flex justify-between font-semibold pt-2">
                                <span class="text-sm">Total:</span>
                                <span class="text-sm">KES {{ number_format($cycleRepaymentsList->sum('amount'), 2) }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Print Button -->
            <div class="flex items-center justify-end gap-3">
                <button onclick="window.print()"
                    class="flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.99578 4.08398C6.58156 4.08398 6.24578 4.41977 6.24578 4.83398V6.36733H13.7542V5.62451C13.7542 5.42154 13.672 5.22724 13.5262 5.08598L12.7107 4.29545C12.5707 4.15983 12.3835 4.08398 12.1887 4.08398H6.99578ZM15.2542 6.36902V5.62451C15.2542 5.01561 15.0074 4.43271 14.5702 4.00891L13.7547 3.21839C13.3349 2.81151 12.7733 2.58398 12.1887 2.58398H6.99578C5.75314 2.58398 4.74578 3.59134 4.74578 4.83398V6.36902C3.54391 6.41522 2.58374 7.40415 2.58374 8.61733V11.3827C2.58374 12.5959 3.54382 13.5848 4.74561 13.631V15.1665C4.74561 16.4091 5.75297 17.4165 6.99561 17.4165H13.0041C14.2467 17.4165 15.2541 16.4091 15.2541 15.1665V13.6311C16.456 13.585 17.4163 12.596 17.4163 11.3827V8.61733C17.4163 7.40414 16.4561 6.41521 15.2542 6.36902ZM4.74561 11.6217V12.1276C4.37292 12.084 4.08374 11.7671 4.08374 11.3827V8.61733C4.08374 8.20312 4.41953 7.86733 4.83374 7.86733H15.1663C15.5805 7.86733 15.9163 8.20312 15.9163 8.61733V11.3827C15.9163 11.7673 15.6269 12.0842 15.2541 12.1277V11.6217C15.2541 11.2075 14.9183 10.8717 14.5041 10.8717H5.49561C5.08139 10.8717 4.74561 11.2075 4.74561 11.6217ZM6.24561 12.3717V15.1665C6.24561 15.5807 6.58139 15.9165 6.99561 15.9165H13.0041C13.4183 15.9165 13.7541 15.5807 13.7541 15.1665V12.3717H6.24561Z" fill=""/>
                    </svg>
                    Print Statement
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Include all modals --}}
@include('partials.modal.loan-rollover-modal')
@include('partials.modal.loan-payment-plan-modal') 
@include('partials.modal.loan-cycles-modal')
@include('partials.modal.cases-create-modal')
@include('partials.modal.disbursement-create-modal')
@include('partials.modal.repayment-create-modal')
@include('partials.modal.alert-modal')

@endsection

@push('styles')
<style>
    .loan-status-badge {
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    @media print {
        .no-print {
            display: none;
        }
        body {
            background: white;
            padding: 0;
        }
        .container {
            max-width: 100%;
            padding: 0;
        }
    }
</style>
@endpush

<script>
// ============ GLOBAL FUNCTIONS ============
function openCaseModal(userId, loanId) {
    window.dispatchEvent(new CustomEvent('open-case-create', {
        detail: { 
            user_id: userId, 
            loan_id: loanId 
        }
    }));
}

function openRolloverModal(loanId) {
    fetch(`/loans/${loanId}/rollover-preview`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const preview = data.data;
                window.dispatchEvent(new CustomEvent('open-rollover-modal', {
                    detail: {
                        loanId: loanId,
                        currentBalance: preview.current_balance,
                        interestToCapitalize: preview.interest_to_capitalize,
                        newBalance: preview.new_balance,
                        currentCycle: preview.current_cycle,
                        newCycle: preview.new_cycle,
                        newDueDate: preview.new_due_date,
                        previousDueDate: preview.previous_due_date,
                        interestRate: preview.interest_rate,
                        graceDaysBalance: preview.grace_days_balance,
                        previousBalance: preview.previous_balance,
                        missedCycles: preview.missed_cycles,
                        potentialCycles: preview.potential_cycles,
                        daysOverdue: preview.days_overdue,
                        periodDisplay: preview.period_display,
                        nextDueDateIfRolled: preview.next_due_date_if_rolled,
                        currentDueDate: preview.current_due_date,
                    }
                }));
            }
        })
        .catch(error => {
            console.error('Error fetching rollover preview:', error);
            const loanData = window.loanData || {};
            const currentBalance = loanData.amount || 0;
            const interestRate = loanData.interest_rate || 0;
            const period = loanData.period || 30;
            const unit = loanData.unit || 'days';
            const previousDueDate = loanData.due_date ? new Date(loanData.due_date) : new Date(loanData.borrow_date || new Date());
            
            const interestToCapitalize = (interestRate / 100) * currentBalance;
            const newBalance = currentBalance + interestToCapitalize;
            
            const newDueDate = new Date(previousDueDate);
            if (unit === 'days') {
                newDueDate.setDate(newDueDate.getDate() + period);
            } else if (unit === 'weeks') {
                newDueDate.setDate(newDueDate.getDate() + (period * 7));
            } else if (unit === 'months') {
                newDueDate.setMonth(newDueDate.getMonth() + period);
            } else if (unit === 'years') {
                newDueDate.setFullYear(newDueDate.getFullYear() + period);
            }
            
            window.dispatchEvent(new CustomEvent('open-rollover-modal', {
                detail: {
                    loanId: loanId,
                    currentBalance: currentBalance,
                    interestToCapitalize: interestToCapitalize,
                    newBalance: newBalance,
                    currentCycle: loanData.cycle || 1,
                    newCycle: (loanData.cycle || 1) + 1,
                    newDueDate: newDueDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    }),
                    previousDueDate: previousDueDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    }),
                    interestRate: interestRate,
                    graceDaysBalance: loanData.grace_days_balance || 0,
                    previousBalance: currentBalance,
                    period: period,
                    unit: unit,
                    missedCycles: 0,
                    potentialCycles: 0,
                    daysOverdue: 0,
                    periodDisplay: period + ' ' + unit,
                    nextDueDateIfRolled: newDueDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    }),
                    currentDueDate: previousDueDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    }),
                }
            }));
        });
}

function openCyclesModal(loanId) {
    window.dispatchEvent(new CustomEvent('open-cycles-modal', {
        detail: { loanId: loanId }
    }));
}

function openForbearanceModal() {
    const event = new CustomEvent('open-forbearance-modal');
    window.dispatchEvent(event);
}

document.addEventListener('alpine:init', function() {
    Alpine.data('loanShow', function() {
        return {
            isRolloverModalOpen: false,
            isForbearanceModalOpen: false,
            
            init() {
                window.loanData = {
                    id: {{ $loan->id }},
                    amount: {{ $loan->amount }},
                    cycle: {{ $loan->cycle }},
                    grace_days_balance: {{ $loan->grace_days_balance }},
                    interest_rate: {{ $loan->loanType->interest_rate ?? 0 }},
                    period: {{ $loan->loanType->period ?? 30 }},
                    unit: '{{ $loan->loanType->unit ?? 'days' }}',
                    borrow_date: '{{ $loan->borrow_date ? $loan->borrow_date : '' }}',
                    due_date: '{{ $loan->due_date ? $loan->due_date : '' }}',
                    status: '{{ $loan->status }}',
                };
                
                window.addEventListener('open-forbearance-modal', () => {
                    this.isForbearanceModalOpen = true;
                    document.body.style.overflow = 'hidden';
                });
            },
            
            closeForbearanceModal() {
                this.isForbearanceModalOpen = false;
                document.body.style.overflow = '';
            },
            
            openRolloverModal(loanId) {
                const loanData = window.loanData || {};
                const currentBalance = loanData.amount || 0;
                const interestRate = loanData.interest_rate || 0;
                const period = loanData.period || 30;
                const interestToCapitalize = (interestRate / 100) * currentBalance * (period / 30);
                const newBalance = currentBalance + interestToCapitalize;
                const newDueDate = new Date();
                newDueDate.setDate(newDueDate.getDate() + period);
                
                window.dispatchEvent(new CustomEvent('open-rollover-modal', {
                    detail: {
                        loanId: loanId,
                        currentBalance: currentBalance,
                        interestToCapitalize: interestToCapitalize,
                        newBalance: newBalance,
                        currentCycle: loanData.cycle || 1,
                        newDueDate: newDueDate.toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'short', 
                            day: 'numeric' 
                        }),
                        interestRate: interestRate,
                        graceDaysBalance: loanData.grace_days_balance || 0,
                    }
                }));
            },
            
            openCyclesModal(loanId) {
                window.dispatchEvent(new CustomEvent('open-cycles-modal', {
                    detail: { loanId: loanId }
                }));
            }
        };
    });
});
</script>