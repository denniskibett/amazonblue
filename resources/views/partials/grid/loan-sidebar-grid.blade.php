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
                        <span class="font-semibold text-red-800 dark:text-red-300">🚨 Defaulted / NPL</span>
                    @elseif($loan->isOverdue() || $loan->status === 'overdue')
                        <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-semibold text-yellow-800 dark:text-yellow-300">⚠️ Overdue</span>
                    @elseif($loan->status === 'forbearance')
                        <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        <span class="font-semibold text-gray-800 dark:text-gray-300">⏸️ Forbearance</span>
                    @elseif($loan->status === 'recovery')
                        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="font-semibold text-purple-800 dark:text-purple-300">🔄 In Recovery</span>
                    @else
                        <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-semibold text-green-800 dark:text-green-300">✅ Performing</span>
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
                    🔴 Defaulted
                </span>
                @if($loan->default_date)
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    📅 Default Date: {{ \Carbon\Carbon::parse($loan->default_date)->format('M d, Y') }}
                </span>
                @endif
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium 
                    @if(($metrics['days_overdue'] ?? 0) > 90) bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-300
                    @elseif(($metrics['days_overdue'] ?? 0) > 60) bg-orange-100 text-orange-800 dark:bg-orange-800/30 dark:text-orange-300
                    @elseif(($metrics['days_overdue'] ?? 0) > 30) bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-300
                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                    📊 {{ $metrics['days_overdue'] ?? 0 }} days in default
                </span>
                @if(isset($recoveryCase))
                <a href="{{ route('cases.show', $recoveryCase) }}" class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-300 hover:bg-blue-200 transition-colors">
                    📋 View Recovery Case
                </a>
                @endif
            </div>
            @elseif($loan->isOverdue() || $loan->status === 'overdue')
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-300">
                    🟡 Overdue: {{ $metrics['days_overdue'] ?? 0 }} days
                </span>
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    ⏳ Threshold: {{ $metrics['default_threshold_days'] ?? 30 }} days until default
                </span>
            </div>
            @elseif($loan->status === 'forbearance')
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    ⏸️ Forbearance until: {{ $loan->forbearance_until ? \Carbon\Carbon::parse($loan->forbearance_until)->format('M d, Y') : 'N/A' }}
                </span>
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    📅 Days remaining: {{ $loan->forbearance_until ? \Carbon\Carbon::now()->diffInDays($loan->forbearance_until, false) : 0 }}
                </span>
            </div>
            @elseif($loan->status === 'recovery')
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800/30 dark:text-purple-300">
                    🔄 Recovery started: {{ $loan->recovery_started_at ? \Carbon\Carbon::parse($loan->recovery_started_at)->format('M d, Y') : 'N/A' }}
                </span>
                @if($loan->recovery_notes)
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    📝 Notes available
                </span>
                @endif
            </div>
            @else
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-300">
                    ✅ Loan is performing
                </span>
                @if($metrics['due_date'] ?? false)
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    📅 Due: {{ $metrics['due_date']->format('M d, Y') }}
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
                    <span class="text-green-600 dark:text-green-400">🟢</span>
                    <span class="font-medium text-green-800 dark:text-green-300">In Grace Period</span>
                    <span class="ml-auto text-xs text-green-600 dark:text-green-400">{{ $loan->getRemainingGraceDays() }} days remaining</span>
                @elseif($loan->grace_days_balance > 0)
                    <span class="text-blue-600 dark:text-blue-400">📋</span>
                    <span class="font-medium text-blue-800 dark:text-blue-300">{{ $loan->grace_days_balance }} Grace Days</span>
                    <span class="ml-auto text-xs text-blue-600 dark:text-blue-400">Earned: {{ $loan->grace_days_earned }} | Used: {{ $loan->grace_days_used }}</span>
                @else
                    <span class="text-gray-500 dark:text-gray-400">📅</span>
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
                <span class="text-purple-600 dark:text-purple-400">🔄</span>
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

            <!-- End Forbearance -->
            @if($loan->isInForbearance())
            <button onclick="if(confirm('End forbearance for this loan?')) { document.getElementById('end-forbearance-form').submit(); }" 
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
            <button onclick="if(confirm('Start recovery process for this loan?')) { document.getElementById('start-recovery-form').submit(); }" 
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
                    ✅ This cycle is fully paid
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

function openRolloverModal(loanId) {
    window.dispatchEvent(new CustomEvent('open-rollover-modal', {
        detail: { loanId: loanId }
    }));
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

function openCaseModal(userId, loanId) {
    window.dispatchEvent(new CustomEvent('open-case-create', {
        detail: { 
            user_id: userId, 
            loan_id: loanId 
        }
    }));
}
</script>