@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6" 
     x-data="caseShow()" 
     x-init="init(@json($caseData))">
    
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                    <svg class="w-3 h-3 mr-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                    </svg>
                    Home
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-3 h-3 mx-1 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <a href="{{ route('cases.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">Recovery Cases</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-3 h-3 mx-1 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400" x-text="case.case_number"></span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Case Header -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="case.case_number"></h1>
                    @include('partials.recovery.status-badge', ['status' => $case->status])
                    @include('partials.recovery.priority-badge', ['priority' => $case->priority])
                    @if($case->loan && $case->loan->is_non_performing)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                            NPL Loan
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Created <span x-text="case.created_at_diff"></span> • 
                    Last updated <span x-text="case.updated_at_diff"></span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
                <button @click="openEditCase()" 
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    Edit Case
                </button>
                @endif
                <a href="{{ route('cases.index') }}" 
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Debt</p>
            <p class="text-xl font-bold text-red-600" x-text="formatCurrency(case.total_debt_amount)"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Recovered</p>
            <p class="text-xl font-bold text-green-600" x-text="formatCurrency(case.total_recovered)"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Remaining</p>
            <p class="text-xl font-bold text-orange-600" x-text="formatCurrency(case.remaining_balance)"></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Days in Default</p>
            <p class="text-xl font-bold" :class="case.days_in_default > 90 ? 'text-red-600' : (case.days_in_default > 30 ? 'text-orange-600' : 'text-gray-600')" 
               x-text="case.days_in_default"></p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Case Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Debtor Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Debtor Information</h3>
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-lg font-semibold" 
                         x-text="case.user_initials || 'U'">
                    </div>
                    <div>
                        <p class="text-base font-medium text-gray-900 dark:text-white" x-text="case.user_name"></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="case.user_email"></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="case.user_phone || 'No phone'"></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="case.client_type ? 'Client Type: ' + case.client_type : 'N/A'"></p>
                        <a :href="'/users/' + case.user_id" class="mt-2 inline-block text-sm text-blue-600 hover:underline">View Full Profile →</a>
                    </div>
                </div>
            </div>

            <!-- Loan Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6" x-show="case.loan_id">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Associated Loan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Loan ID</p>
                        <p class="text-base font-medium" x-text="'#' + String(case.loan_id).padStart(5, '0')"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Loan Type</p>
                        <p class="text-base font-medium" x-text="case.loan_type || 'N/A'"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Amount</p>
                        <p class="text-base font-medium" x-text="formatCurrency(case.loan_amount)"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                              :class="{
                                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': case.loan_status === 'disbursed',
                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': case.loan_status === 'repaid',
                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': case.loan_status === 'defaulted',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': case.loan_status === 'overdue',
                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !case.loan_status || !['disbursed','repaid','defaulted','overdue'].includes(case.loan_status)
                              }"
                              x-text="case.loan_status ? case.loan_status.charAt(0).toUpperCase() + case.loan_status.slice(1) : 'N/A'">
                        </span>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg" x-show="case.is_non_performing">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <span class="text-sm font-medium text-red-800 dark:text-red-300">This loan is Non-Performing (NPL)</span>
                    </div>
                    <div class="mt-1 text-sm text-red-700 dark:text-red-400">
                        <span x-text="case.days_overdue || 0"></span> days overdue • Defaulted on <span x-text="case.default_date_formatted || 'N/A'"></span>
                    </div>
                </div>
                <a :href="'/users/' + case.user_id + '/loans/' + case.loan_id" class="mt-4 inline-block text-sm text-blue-600 hover:underline">View Loan Details →</a>
            </div>

            <!-- Recovery Strategy -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6" x-show="case.recovery_strategy">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Recovery Strategy</h3>
                <p class="text-gray-700 dark:text-gray-300" x-text="case.recovery_strategy"></p>
            </div>

            <!-- Notes -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6" x-show="case.notes">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Notes</h3>
                <p class="text-gray-700 dark:text-gray-300" x-text="case.notes"></p>
            </div>

            <!-- Activity Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Activity Timeline</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="case.actions_count + ' actions'"></span>
                </div>
                @include('partials.recovery.action-timeline', ['actions' => $case->actions])
            </div>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Case Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Case Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Assigned To</span>
                        <span class="text-sm font-medium" x-text="case.assigned_to_name || 'Unassigned'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Recovery Officer</span>
                        <span class="text-sm font-medium" x-text="case.recovery_officer || 'N/A'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Default Date</span>
                        <span class="text-sm font-medium" x-text="case.default_date_formatted || 'N/A'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Last Contact</span>
                        <span class="text-sm font-medium" x-text="case.last_contact_date_formatted || 'Never'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Next Action</span>
                        <span class="text-sm font-medium" x-text="case.next_action_date_formatted || 'Not set'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Recovery Progress</span>
                        <span class="text-sm font-medium" x-text="case.recovery_progress + '%'"></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <button @click="openAddActionModal()" 
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Action
                    </button>
                    
                    <button @click="markAsRecovered()" 
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Mark as Recovered
                    </button>
                    
                    @if(auth()->user()->role === 'admin')
                    <button @click="openWriteOffModal()" 
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Write Off Case
                    </button>
                    @endif
                </div>
            </div>
            @endif

            <!-- Payment Plans -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6" x-show="case.payment_plans && case.payment_plans.length > 0">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Payment Plans</h3>
                <template x-for="plan in case.payment_plans" :key="plan.id">
                    <div class="border-b border-gray-100 dark:border-gray-700 last:border-0 py-3 last:pb-0">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium" x-text="plan.installment_frequency + ' Plan'"></span>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': plan.status === 'accepted',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': plan.status === 'proposed',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': plan.status === 'completed',
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !['accepted','proposed','completed'].includes(plan.status)
                                  }"
                                  x-text="plan.status.charAt(0).toUpperCase() + plan.status.slice(1)">
                            </span>
                        </div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="plan.installment_amount + ' x ' + plan.number_of_installments + ' = KES ' + formatCurrency(plan.total_amount)"></div>
                        <div class="mt-2 w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                            <div class="bg-blue-600 h-1.5 rounded-full" :style="'width: ' + plan.progress_percentage + '%'"></div>
                        </div>
                        <div class="mt-1 text-xs text-gray-400" x-text="plan.progress_percentage + '% complete • ' + (plan.remaining_balance > 0 ? 'KES ' + formatCurrency(plan.remaining_balance) + ' remaining' : 'Fully paid')"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- Include the Case Create/Edit Modal --}}
@include('partials.modal.cases-create-modal')

{{-- Include Alert Modal --}}
@include('partials.modal.alert-modal')

@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('caseShow', () => ({
        case: {},
        
        init(caseData) {
            this.case = caseData;
        },
        
        formatCurrency(amount) {
            if (amount === undefined || amount === null) return 'KES 0.00';
            return 'KES ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
        
        openEditCase() {
            window.dispatchEvent(new CustomEvent('edit-case', {
                detail: { case: this.case }
            }));
        },
        
        openAddActionModal() {
            window.dispatchEvent(new CustomEvent('open-add-action', {
                detail: { caseId: this.case.id }
            }));
        },
        
        markAsRecovered() {
            if (confirm('Mark this case as recovered?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/cases/' + this.case.id + '/recover';
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
                detail: { caseId: this.case.id }
            }));
        }
    }));
});

// Listen for edit events
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('edit-case', function(event) {
        console.log('Edit case event received:', event.detail);
        const caseData = event.detail.case;
        if (document.getElementById('editCaseModal')) {
            document.getElementById('editCaseModal').classList.remove('hidden');
        }
    });
    
    document.addEventListener('open-add-action', function(event) {
        console.log('Add action event received:', event.detail);
        // Implement add action modal logic
        alert('Add action functionality - implement modal here');
    });
    
    document.addEventListener('open-write-off', function(event) {
        console.log('Write off event received:', event.detail);
        // Implement write off modal logic
        alert('Write off functionality - implement modal here');
    });
});
</script>
@endpush