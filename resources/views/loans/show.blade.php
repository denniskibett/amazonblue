@extends('layouts.app')

@section('content')
<div class="flex h-full flex-col gap-6 sm:gap-5 xl:flex-row">
    <!-- Loan Details Sidebar -->
    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] xl:w-1/5">
        <div class="mb-5">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Loan Details</h2>
                <div class="loan-status-badge px-3 py-1 rounded-full text-xs
                    @if($loan->status === 'approved') bg-green-100 text-green-800
                    @elseif($loan->status === 'disbursed') bg-blue-100 text-blue-800
                    @elseif($loan->status === 'rejected') bg-red-100 text-red-800
                    @elseif($loan->status === 'repaid') bg-purple-100 text-purple-800
                    @elseif($loan->status === 'defaulted') bg-red-100 text-red-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ ucfirst($loan->status) }}
                </div>
            </div>

            <!-- ============ NPL STATUS SECTION ============ -->
            <div class="mt-3 p-3 rounded-lg border
                @if($loan->is_non_performing) 
                    border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20
                @elseif($loan->isOverdue())
                    border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20
                @else
                    border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20
                @endif
            ">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        @if($loan->is_non_performing)
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <span class="font-semibold text-red-800 dark:text-red-300">🚨 Non-Performing Loan (NPL)</span>
                        @elseif($loan->isOverdue())
                            <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-semibold text-yellow-800 dark:text-yellow-300">⚠️ Overdue</span>
                        @else
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-semibold text-green-800 dark:text-green-300">✅ Performing</span>
                        @endif
                    </div>
                    @if($loan->is_non_performing)
                        <span class="text-xs text-red-600 dark:text-red-400 font-medium">
                            {{ $loan->days_overdue ?? 0 }} days overdue
                        </span>
                    @endif
                </div>
                
                @if($loan->is_non_performing)
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-300">
                        🔴 NPL Status: Defaulted
                    </span>
                    @if($loan->default_date)
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                        📅 Default Date: {{ \Carbon\Carbon::parse($loan->default_date)->format('M d, Y') }}
                    </span>
                    @endif
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium 
                        @if(($loan->days_overdue ?? 0) > 90) bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-300
                        @elseif(($loan->days_overdue ?? 0) > 60) bg-orange-100 text-orange-800 dark:bg-orange-800/30 dark:text-orange-300
                        @elseif(($loan->days_overdue ?? 0) > 30) bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-300
                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                        📊 {{ $loan->days_overdue ?? 0 }} days in default
                    </span>
                    @if(isset($recoveryCase))
                    <a href="{{ route('cases.show', $recoveryCase) }}" class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-300 hover:bg-blue-200 transition-colors">
                        📋 View Recovery Case
                    </a>
                    @endif
                </div>
                @elseif($loan->isOverdue())
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-300">
                        🟡 Overdue: {{ $loan->days_overdue ?? 0 }} days
                    </span>
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                        ⏳ Threshold: {{ $loan->npl_trigger_threshold ?? 0 }} days until NPL
                    </span>
                </div>
                @else
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-300">
                        ✅ Loan is performing
                    </span>
                    @if($loan->calculated_due_date)
                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                        📅 Due: {{ \Carbon\Carbon::parse($loan->calculated_due_date)->format('M d, Y') }}
                    </span>
                    @endif
                </div>
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
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-600">Loan Type</p>
                    <p class="font-medium">{{ $loan->loanType->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">{{ $interest_rate }}% for {{ $period }} {{ $period_unit }}</p>
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

                <!-- Risk Assessment -->
                @if(isset($loan->riskAssessments) && $loan->riskAssessments->isNotEmpty())
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <h3 class="text-md font-semibold mb-3">Risk Assessment</h3>
                    @php $latestAssessment = $loan->getLatestRiskAssessment(); @endphp
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm">Overall Score</span>
                            <span class="text-sm font-medium">{{ $latestAssessment->overall_score }}/100</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm">Risk Category</span>
                            <span class="text-sm font-medium 
                                @if($latestAssessment->getRiskCategory() === 'Low Risk') text-green-600
                                @elseif($latestAssessment->getRiskCategory() === 'Medium Risk') text-yellow-600
                                @elseif($latestAssessment->getRiskCategory() === 'High Risk') text-orange-600
                                @else text-red-600 @endif">
                                {{ $latestAssessment->getRiskCategory() }}
                            </span>
                        </div>
                    </div>
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
                        <p class="text-sm text-green-600">✓ Agreed on {{ $loan->consent_date->format('M d, Y') }}</p>
                    </div>
                    @endif
                    
                    <!-- Signature Display -->
                    @if($hasSignature && $loan->user && $loan->user->signature)
                    <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                        <h3 class="text-lg font-medium mb-4">Digital Signature</h3>
                        
                        <div class="flex items-center space-x-6 p-5 border border-gray-300 rounded-2xl bg-white shadow-sm dark:bg-gray-900">
                            <div class="flex-shrink-0 p-2 bg-white border border-gray-200 rounded-xl overflow-hidden">
                                <div class="flex items-center justify-center h-24 w-40 bg-transparent">
                                    <img src="{{ asset('storage/' . $loan->user->signature) }}" 
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
        
        <!-- Financial Summary -->
        <div class="border-t border-gray-200 pt-4">
            <h3 class="text-md font-semibold mb-3">Financial Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Principal</span>
                    <span class="text-sm font-medium">KES {{ number_format($principal, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Interest</span>
                    <span class="text-sm font-medium">KES {{ number_format($interest, 2) }}</span>
                </div>
                @if($penalty_amount > 0)
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Penalties</span>
                    <span class="text-sm font-medium">KES {{ number_format($penalty_amount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between pt-2 border-t">
                    <span class="text-sm font-medium">Total Due</span>
                    <span class="text-sm font-bold">KES {{ number_format($total_due, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium">Repayments</span>
                    <span class="text-sm font-medium text-green-600">-KES {{ number_format($total_repayments, 2) }}</span>
                </div>
                <div class="flex justify-between border-t pt-2">
                    <span class="text-sm font-medium">Outstanding</span>
                    <span class="text-sm font-bold @if($outstanding_balance > 0) text-red-600 @else text-green-600 @endif">
                        KES {{ number_format($outstanding_balance, 2) }}
                    </span>
                </div>

                @if($is_brokered)
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Broker Fees</span>
                    <span class="text-sm font-medium">KES {{ number_format($total_broker_fees, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between border-t pt-2">
                    <span class="text-sm font-medium">Net Earnings</span>
                    <span class="text-sm font-bold @if($net_earnings >= 0) text-green-600 @else text-red-600 @endif">
                        KES {{ number_format($net_earnings, 2) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-medium">Profit/Loss</span>
                    <span class="text-sm font-bold @if($pl >= 0) text-green-600 @else text-red-600 @endif">
                        KES {{ number_format($pl, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

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
                        {{ \Carbon\Carbon::parse($due_date)->format('D, M d, Y') }}
                        @if($days_late > 0)
                            <span class="text-red-500 ml-2">({{ round($days_late) }} days late)</span>
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
                                <p class="text-right text-sm text-gray-500 dark:text-gray-400">{{ number_format($principal, 2) }}</p>
                            </div>
                        </div>

                        <!-- Interest -->
                        <div class="grid grid-cols-11 border-t border-gray-100 px-5 py-3.5 dark:border-gray-800">
                            <div class="col-span-1 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">2</p>
                            </div>
                            <div class="col-span-5 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Interest ({{ number_format($interest_rate, 0) }}%)</p>
                            </div>
                            <div class="col-span-2 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                            </div>
                            <div class="col-span-3 flex items-center justify-end">
                                <p class="text-right text-sm text-gray-500 dark:text-gray-400">{{ number_format($interest, 2) }}</p>
                            </div>
                        </div>

                        <!-- Penalties -->
                        @if($penalty_amount > 0)
                        <div class="grid grid-cols-11 border-t border-gray-100 px-5 py-3.5 dark:border-gray-800">
                            <div class="col-span-1 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">3</p>
                            </div>
                            <div class="col-span-5 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Penalties ({{ number_format($base_penalty_rate, 0) }}% daily)</p>
                            </div>
                            <div class="col-span-2 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    KES {{ number_format($base_penalty_rate/100*$outstanding_at_due, 2) }} * {{ round($days_late) }} days
                                </p>
                            </div>
                            <div class="col-span-3 flex items-center justify-end">
                                <p class="text-right text-sm text-red-500 dark:text-red-400">{{ number_format($penalty_amount, 2) }}</p>
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

                        <!-- Total Repayments -->
                        <div class="grid grid-cols-11 border-t border-gray-100 px-5 py-3.5 dark:border-gray-800">
                            <div class="col-span-1 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">5</p>
                            </div>
                            <div class="col-span-5 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Total Repayments</p>
                            </div>
                            <div class="col-span-2 flex items-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                            </div>
                            <div class="col-span-3 flex items-center justify-end">
                                <p class="text-right text-sm text-green-500 dark:text-green-400">-{{ number_format($total_repayments, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="my-6 border-b border-gray-100 pb-6 dark:border-gray-800">
                <div class="flex justify-between mb-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Principal + Interest + Penalties:</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($principal_plus_interest + $penalty_amount, 2) }}</p>
                </div>
                
                <div class="flex justify-between mb-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Repayments:</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">-KES {{ number_format($total_repayments, 2) }}</p>
                </div>

                <div class="flex justify-between pt-4 border-t">
                    <p class="text-lg font-semibold text-gray-800 dark:text-white/90">Balance Due:</p>
                    <p class="text-lg font-bold @if($outstanding_balance > 0) text-red-600 @else text-green-600 @endif">
                        KES {{ number_format($outstanding_balance, 2) }}
                    </p>
                </div>
            </div>

            <!-- Disbursements and Repayments -->
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

                <!-- Repayments -->
                <div class="bg-white p-4 rounded-lg shadow border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-semibold">Repayments</h3>
                        @if(auth()->user()->role !== 'borrower')
                            <button onclick="openRepaymentModal({{ $loan->id }})" 
                                class="text-xs bg-green-500 hover:bg-green-700 text-white py-1 px-2 rounded">
                                + Add
                            </button>
                        @endif
                    </div>
                    
                    @if($loan->repayments->isEmpty())
                        <p class="text-gray-500 text-sm">No repayments found.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($loan->repayments as $repayment)
                                <div class="flex justify-between items-center border-b pb-2 dark:border-gray-700">
                                    <div>
                                        <p class="text-sm font-medium">KES {{ number_format($repayment->amount, 2) }}</p>
                                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($repayment->repayment_date)->format('M d, Y H:i') }}</p>
                                        @if($repayment->transaction_ref)
                                            <p class="text-xs text-gray-400">Ref: {{ $repayment->transaction_ref }}</p>
                                        @endif
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
                                <span class="text-sm">KES {{ number_format($total_repayments, 2) }}</span>
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

{{-- Include Modals --}}
@include('partials.modal.disbursement-create-modal')
@include('partials.modal.repayment-create-modal')
@include('partials.modal.alert-modal')
@include('partials.modal.cases-create-modal')

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