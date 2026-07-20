{{-- resources/views/partials/modal/cases-create-modal.blade.php --}}
@php
    $borrowers = $borrowers ?? [];
    $nplBorrowers = $nplBorrowers ?? [];
    $statuses = $statuses ?? [];
    $priorities = $priorities ?? [];
    $officers = $officers ?? [];
    $actionTypes = $actionTypes ?? [];
@endphp

<div x-data="caseFormModal()" 
     x-init="initModal()"
     x-show="isOpen" 
     x-cloak
     class="fixed inset-0 z-[99999] overflow-hidden"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/50" @click="close()"></div>
    
    <!-- Slideover Panel -->
    <div class="fixed inset-y-0 right-0 max-w-full flex">
        <div class="w-full max-w-4xl">
            <div class="h-full flex flex-col bg-white dark:bg-gray-900 shadow-xl"
                 x-show="isOpen"
                 x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full">
                
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="modalTitle">Create Recovery Case</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="modalSubtitle">Create a new debt recovery case</p>
                    </div>
                    <button @click="close()" 
                            class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <form @submit.prevent="submitForm" id="caseForm" class="space-y-6">
                        @csrf
                        <input type="hidden" x-model="formData.id">
                        
                        <!-- ============ BASIC INFORMATION ============ -->
                        <div>
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Basic Information</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <!-- Borrower -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Borrower <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="formData.user_id" 
                                            @change="loadBorrowerData()"
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                        <option value="">Select Borrower</option>
                                        @foreach($borrowers as $borrower)
                                            <option value="{{ $borrower->id }}" data-has-npl="{{ $borrower->loans->where('is_non_performing', true)->count() > 0 ? 'true' : 'false' }}">
                                                {{ $borrower->name }} ({{ $borrower->email }})
                                                @if($borrower->loans->where('is_non_performing', true)->count() > 0)
                                                    - ⚠️ NPL
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <template x-if="errors.user_id">
                                        <p class="mt-1 text-sm text-red-500" x-text="errors.user_id[0]"></p>
                                    </template>
                                </div>

                                <!-- Loan -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Associated Loan <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="formData.loan_id" 
                                            @change="populateDebtDetails()"
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                        <option value="">Select Loan</option>
                                        <template x-for="loan in borrowerLoans" :key="loan.id">
                                            <option :value="loan.id" x-text="'#' + loan.id + ' - KES ' + formatNumber(loan.amount) + ' (' + loan.status + ')'"></option>
                                        </template>
                                    </select>
                                    <template x-if="errors.loan_id">
                                        <p class="mt-1 text-sm text-red-500" x-text="errors.loan_id[0]"></p>
                                    </template>
                                </div>
                            </div>

                            <!-- NPL Warning -->
                            <div x-show="showNplWarning" 
                                 x-transition
                                 class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-red-800 dark:text-red-300">⚠️ This borrower has NPL loans</p>
                                        <p class="text-xs text-red-700 dark:text-red-400 mt-1">
                                            The selected borrower has <span x-text="nplLoanCount"></span> non-performing loan(s). 
                                            Recovery case is recommended.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============ DEBT DETAILS ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Debt Details</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Total Debt Amount <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">KES</span>
                                        <input type="number" 
                                               step="0.01" 
                                               min="0.01"
                                               x-model="formData.total_debt_amount" 
                                               class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-14 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                               placeholder="0.00" required>
                                    </div>
                                    <template x-if="errors.total_debt_amount">
                                        <p class="mt-1 text-sm text-red-500" x-text="errors.total_debt_amount[0]"></p>
                                    </template>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Default Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" 
                                           x-model="formData.default_date" 
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                    <template x-if="errors.default_date">
                                        <p class="mt-1 text-sm text-red-500" x-text="errors.default_date[0]"></p>
                                    </template>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Principal Outstanding</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">KES</span>
                                        <input type="number" 
                                               step="0.01" 
                                               min="0"
                                               x-model="formData.principal_outstanding" 
                                               class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-14 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Interest Outstanding</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">KES</span>
                                        <input type="number" 
                                               step="0.01" 
                                               min="0"
                                               x-model="formData.interest_outstanding" 
                                               class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-14 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Penalty Outstanding</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">KES</span>
                                        <input type="number" 
                                               step="0.01" 
                                               min="0"
                                               x-model="formData.penalty_outstanding" 
                                               class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-14 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Fees Outstanding</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">KES</span>
                                        <input type="number" 
                                               step="0.01" 
                                               min="0"
                                               x-model="formData.fees_outstanding" 
                                               class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-14 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                               placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============ CASE SETTINGS ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Case Settings</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Status <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="formData.status_id" 
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                        <option value="">Select Status</option>
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                    <template x-if="errors.status_id">
                                        <p class="mt-1 text-sm text-red-500" x-text="errors.status_id[0]"></p>
                                    </template>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Priority <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="formData.priority_id" 
                                            @change="updateCaseCompilation()"
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                        <option value="">Select Priority</option>
                                        @foreach($priorities as $priority)
                                            <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                                        @endforeach
                                    </select>
                                    <template x-if="errors.priority_id">
                                        <p class="mt-1 text-sm text-red-500" x-text="errors.priority_id[0]"></p>
                                    </template>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Assigned To
                                    </label>
                                    <select x-model="formData.assigned_to" 
                                            @change="updateCaseCompilation()"
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <option value="">Select Officer</option>
                                        @foreach($officers as $officer)
                                            <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ============ RECOVERY STRATEGY ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Recovery Strategy</h4>
                            
                            <!-- Strategy Dropdown -->
                            <div class="mb-4">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Strategy Template
                                </label>
                                <select x-model="selectedStrategy" 
                                        @change="applyStrategyTemplate()"
                                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">Select Strategy Template</option>
                                    <option value="standard">Standard Recovery</option>
                                    <option value="aggressive">Aggressive Recovery</option>
                                    <option value="negotiation">Negotiation Focus</option>
                                    <option value="legal">Legal Action</option>
                                    <option value="custom">Custom Strategy</option>
                                </select>
                            </div>

                            <!-- Strategy Description -->
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Strategy Description</label>
                                <textarea x-model="formData.recovery_strategy" 
                                          @input="updateCaseCompilation()"
                                          rows="3" 
                                          class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                          placeholder="Describe the recovery strategy..."></textarea>
                            </div>
                            
                            <!-- Notes (Point Form) -->
                            <div class="mt-4">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Action Notes (Point Form)</label>
                                <div class="space-y-2">
                                    <template x-for="(note, index) in notesList" :key="index">
                                        <div class="flex items-start gap-2 group">
                                            <span class="text-blue-500 mt-1.5">•</span>
                                            <input type="text" 
                                                   x-model="notesList[index]" 
                                                   @input="updateNotes()"
                                                   class="flex-1 text-sm bg-transparent border-b border-transparent hover:border-gray-300 focus:border-blue-500 focus:outline-none dark:text-gray-300 py-1"
                                                   placeholder="Enter action point...">
                                            <button type="button" 
                                                    @click="removeNote(index)" 
                                                    class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 text-xs transition-opacity">
                                                ×
                                            </button>
                                        </div>
                                    </template>
                                    <button type="button" 
                                            @click="addNote()" 
                                            class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                        + Add Note Point
                                    </button>
                                </div>
                                <input type="hidden" x-model="formData.notes">
                            </div>
                        </div>

                        <!-- ============ INITIAL ACTION ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Initial Action</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Action Type</label>
                                    <select x-model="formData.initial_action_type" 
                                            @change="updateCaseCompilation()"
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <option value="">Select Action</option>
                                        @foreach($actionTypes as $actionType)
                                            <option value="{{ $actionType->id }}">{{ $actionType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Action Notes</label>
                                    <input type="text" 
                                           x-model="formData.initial_action_notes" 
                                           @input="updateCaseCompilation()"
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                           placeholder="Notes about initial action">
                                </div>
                            </div>
                        </div>

                        <!-- ============ CASE COMPILATION SUMMARY ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6" x-show="showCompilation">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Case Compilation Summary</h4>
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Borrower:</span>
                                        <span class="font-medium" x-text="compilation.borrower"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Loan:</span>
                                        <span class="font-medium" x-text="compilation.loan"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Total Debt:</span>
                                        <span class="font-medium text-red-600" x-text="compilation.totalDebt"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Default Date:</span>
                                        <span class="font-medium" x-text="compilation.defaultDate"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Status:</span>
                                        <span class="font-medium" x-text="compilation.status"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Priority:</span>
                                        <span class="font-medium" x-text="compilation.priority"></span>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="text-gray-500 dark:text-gray-400">Assigned To:</span>
                                        <span class="font-medium" x-text="compilation.assignedTo"></span>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="text-gray-500 dark:text-gray-400">Strategy:</span>
                                        <span class="font-medium" x-text="compilation.strategy"></span>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="text-gray-500 dark:text-gray-400">Notes:</span>
                                        <span class="font-medium" x-text="compilation.notes"></span>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="text-gray-500 dark:text-gray-400">Initial Action:</span>
                                        <span class="font-medium" x-text="compilation.initialAction"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0 bg-white dark:bg-gray-900">
                    <div>
                        <span x-show="formData.id" class="text-xs text-gray-500 dark:text-gray-400">
                            Editing case #<span x-text="formData.id"></span>
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="close()" 
                                class="flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            Cancel
                        </button>
                        <button type="submit" form="caseForm"
                                :disabled="isSubmitting"
                                class="flex justify-center px-4 py-2.5 text-sm font-medium text-white rounded-lg bg-blue-600 shadow-theme-xs hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <span x-show="!isSubmitting" x-text="submitButtonText">Create Case</span>
                            <span x-show="isSubmitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="loadingText">Creating...</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('caseFormModal', function() {
        return {
            // Modal state
            isOpen: false,
            isSubmitting: false,
            isEditMode: false,
            errors: {},
            
            // Data
            borrowerLoans: [],
            showNplWarning: false,
            nplLoanCount: 0,
            
            // Strategy & Notes
            selectedStrategy: '',
            notesList: [],
            showCompilation: false,
            
            // Compilation summary
            compilation: {
                borrower: '',
                loan: '',
                totalDebt: '',
                defaultDate: '',
                status: '',
                priority: '',
                assignedTo: '',
                strategy: '',
                notes: '',
                initialAction: ''
            },
            
            formData: {
                id: null,
                user_id: '',
                loan_id: '',
                total_debt_amount: '',
                principal_outstanding: '',
                interest_outstanding: '',
                penalty_outstanding: '',
                fees_outstanding: '',
                default_date: new Date().toISOString().split('T')[0],
                status_id: '',
                priority_id: '',
                assigned_to: '',
                recovery_strategy: '',
                notes: '',
                initial_action_type: '',
                initial_action_notes: ''
            },

            // ============ COMPUTED ============
            get modalTitle() {
                return this.isEditMode ? 'Edit Recovery Case' : 'Create Recovery Case';
            },

            get modalSubtitle() {
                return this.isEditMode ? 'Update case details' : 'Create a new debt recovery case';
            },

            get submitButtonText() {
                return this.isEditMode ? 'Update Case' : 'Create Case';
            },

            get loadingText() {
                return this.isEditMode ? 'Updating...' : 'Creating...';
            },

            get submitUrl() {
                return this.isEditMode
                    ? `/cases/${this.formData.id}`
                    : '{{ route("cases.store") }}';
            },

            get method() {
                return this.isEditMode ? 'PUT' : 'POST';
            },

            // ============ FORMATTING ============
            formatNumber(num) {
                if (!num) return '0';
                return parseFloat(num).toLocaleString();
            },

            // ============ NOTES MANAGEMENT ============
            addNote() {
                this.notesList.push('');
                this.updateNotes();
            },

            removeNote(index) {
                this.notesList.splice(index, 1);
                this.updateNotes();
            },

            updateNotes() {
                this.formData.notes = this.notesList.filter(n => n.trim() !== '').join('\n• ');
                if (this.formData.notes) {
                    this.formData.notes = '• ' + this.formData.notes;
                }
                this.updateCaseCompilation();
            },

            // ============ STRATEGY TEMPLATES ============
            applyStrategyTemplate() {
                const templates = {
                    standard: 'Standard recovery process: 1) Initial contact via phone/email, 2) Follow-up reminders, 3) Field visit, 4) Negotiation if no response, 5) Legal action if necessary.',
                    aggressive: 'Aggressive recovery: 1) Immediate field visit, 2) Contact all references, 3) Skip tracing, 4) Legal notice within 7 days, 5) Court filing within 14 days.',
                    negotiation: 'Negotiation-focused: 1) Flexible payment plans, 2) Settlement offers, 3) Hardship assessment, 4) Reduced interest options.',
                    legal: 'Legal action: 1) Demand letter, 2) Court filing, 3) Judgment, 4) Enforcement proceedings.',
                    custom: 'Custom strategy: Define your own approach below.'
                };

                if (this.selectedStrategy && templates[this.selectedStrategy]) {
                    this.formData.recovery_strategy = templates[this.selectedStrategy];
                } else {
                    this.formData.recovery_strategy = '';
                }
                this.updateCaseCompilation();
            },

            // ============ POPULATE DEBT DETAILS FROM LOAN ============
            populateDebtDetails() {
                if (!this.formData.loan_id) return;

                const selectedLoan = this.borrowerLoans.find(l => l.id === parseInt(this.formData.loan_id));
                if (!selectedLoan) return;

                // Populate debt details from the loan data
                this.formData.total_debt_amount = selectedLoan.outstanding_balance || selectedLoan.amount;
                this.formData.principal_outstanding = selectedLoan.principal_outstanding || selectedLoan.amount;
                this.formData.interest_outstanding = selectedLoan.interest_outstanding || 0;
                this.formData.penalty_outstanding = selectedLoan.penalty_outstanding || 0;
                this.formData.fees_outstanding = 0;

                // Set default date from loan default date or due date
                if (selectedLoan.default_date) {
                    this.formData.default_date = selectedLoan.default_date;
                } else if (selectedLoan.due_date && selectedLoan.is_overdue) {
                    this.formData.default_date = selectedLoan.due_date;
                } else if (selectedLoan.borrow_date) {
                    // If not overdue, use borrow date + 30 days as default
                    const borrowDate = new Date(selectedLoan.borrow_date);
                    borrowDate.setDate(borrowDate.getDate() + 30);
                    this.formData.default_date = borrowDate.toISOString().split('T')[0];
                }

                // Auto-set status based on loan status
                if (selectedLoan.status === 'defaulted' || selectedLoan.is_non_performing) {
                    // Find open status
                    const openStatus = @json($statuses->firstWhere('slug', 'open'));
                    if (openStatus) {
                        this.formData.status_id = openStatus.id;
                    }
                } else if (selectedLoan.is_overdue) {
                    // Find in_progress status for overdue loans
                    const inProgressStatus = @json($statuses->firstWhere('slug', 'in_progress'));
                    if (inProgressStatus) {
                        this.formData.status_id = inProgressStatus.id;
                    }
                }

                // Auto-set priority based on days overdue
                if (selectedLoan.days_overdue && selectedLoan.days_overdue > 0) {
                    if (selectedLoan.days_overdue > 90) {
                        const urgentPriority = @json($priorities->firstWhere('slug', 'urgent'));
                        if (urgentPriority) this.formData.priority_id = urgentPriority.id;
                    } else if (selectedLoan.days_overdue > 60) {
                        const highPriority = @json($priorities->firstWhere('slug', 'high'));
                        if (highPriority) this.formData.priority_id = highPriority.id;
                    } else if (selectedLoan.days_overdue > 30) {
                        const mediumPriority = @json($priorities->firstWhere('slug', 'medium'));
                        if (mediumPriority) this.formData.priority_id = mediumPriority.id;
                    } else {
                        const lowPriority = @json($priorities->firstWhere('slug', 'low'));
                        if (lowPriority) this.formData.priority_id = lowPriority.id;
                    }
                }

                // Auto-set initial action type
                if (selectedLoan.is_overdue || selectedLoan.is_non_performing) {
                    const phoneAction = @json($actionTypes->firstWhere('slug', 'phone_call'));
                    if (phoneAction) {
                        this.formData.initial_action_type = phoneAction.id;
                        this.formData.initial_action_notes = `Initial contact regarding overdue loan #${selectedLoan.id} (${selectedLoan.days_overdue || 0} days overdue)`;
                    }
                }

                // Auto-set recovery strategy based on loan status
                if (selectedLoan.is_non_performing) {
                    this.selectedStrategy = 'aggressive';
                    this.applyStrategyTemplate();
                } else if (selectedLoan.is_overdue && selectedLoan.days_overdue > 30) {
                    this.selectedStrategy = 'standard';
                    this.applyStrategyTemplate();
                }

                this.updateCaseCompilation();
            },

            // ============ LOAD BORROWER DATA ============
            loadBorrowerData() {
                if (!this.formData.user_id) {
                    this.borrowerLoans = [];
                    this.showNplWarning = false;
                    this.nplLoanCount = 0;
                    return;
                }

                console.log('Loading loans for user_id:', this.formData.user_id);

                const url = `/users/${this.formData.user_id}/loans-data`;
                console.log('Fetching from URL:', url);

                fetch(url)
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data);
                        
                        // Handle the loans data
                        this.borrowerLoans = data.loans || [];
                        this.showNplWarning = data.npl_count > 0;
                        this.nplLoanCount = data.npl_count || 0;
                        
                        console.log('Loans loaded:', this.borrowerLoans.length);
                        
                        // Auto-select first loan if available
                        if (this.borrowerLoans.length > 0) {
                            // Prefer NPL loans first, then overdue, then any loan
                            let defaultLoan = this.borrowerLoans.find(l => l.is_non_performing);
                            if (!defaultLoan) {
                                defaultLoan = this.borrowerLoans.find(l => l.is_overdue);
                            }
                            if (!defaultLoan) {
                                defaultLoan = this.borrowerLoans[0];
                            }
                            
                            this.formData.loan_id = defaultLoan.id;
                            this.populateDebtDetails();
                        } else {
                            console.warn('No loans found for user:', this.formData.user_id);
                            // Show a message to the user
                            window.dispatchEvent(new CustomEvent('show-alert', {
                                detail: {
                                    type: 'warning',
                                    title: 'No Loans Found',
                                    message: 'This borrower has no active loans. Please create a loan first or select a different borrower.'
                                }
                            }));
                        }
                    })
                    .catch(error => {
                        console.error('Error loading borrower loans:', error);
                        window.dispatchEvent(new CustomEvent('show-alert', {
                            detail: {
                                type: 'error',
                                title: 'Error Loading Loans',
                                message: 'Could not load loans for this borrower. Please try again.'
                            }
                        }));
                    });
            },

            // ============ UPDATE CASE COMPILATION ============
            updateCaseCompilation() {
                const borrowerName = this.getBorrowerName();
                const loanId = this.formData.loan_id;
                const loan = this.borrowerLoans.find(l => l.id === parseInt(loanId));
                const statusName = this.getStatusName();
                const priorityName = this.getPriorityName();
                const assignedToName = this.getAssignedToName();
                const actionTypeName = this.getActionTypeName();

                this.compilation = {
                    borrower: borrowerName || 'Not selected',
                    loan: loan ? `#${loan.id} - KES ${this.formatNumber(loan.amount)} (${loan.status})` : 'Not selected',
                    totalDebt: this.formData.total_debt_amount ? `KES ${this.formatNumber(this.formData.total_debt_amount)}` : 'Not set',
                    defaultDate: this.formData.default_date || 'Not set',
                    status: statusName || 'Not selected',
                    priority: priorityName || 'Not selected',
                    assignedTo: assignedToName || 'Not assigned',
                    strategy: this.formData.recovery_strategy || 'Not set',
                    notes: this.formData.notes || 'No notes',
                    initialAction: actionTypeName ? `${actionTypeName}${this.formData.initial_action_notes ? ' - ' + this.formData.initial_action_notes : ''}` : 'Not set'
                };

                this.showCompilation = true;
            },

            getBorrowerName() {
                const select = document.querySelector('select[name="user_id"]');
                const option = select?.querySelector(`option[value="${this.formData.user_id}"]`);
                return option?.textContent?.trim() || '';
            },

            getStatusName() {
                const select = document.querySelector('select[name="status_id"]');
                const option = select?.querySelector(`option[value="${this.formData.status_id}"]`);
                return option?.textContent?.trim() || '';
            },

            getPriorityName() {
                const select = document.querySelector('select[name="priority_id"]');
                const option = select?.querySelector(`option[value="${this.formData.priority_id}"]`);
                return option?.textContent?.trim() || '';
            },

            getAssignedToName() {
                const select = document.querySelector('select[name="assigned_to"]');
                const option = select?.querySelector(`option[value="${this.formData.assigned_to}"]`);
                return option?.textContent?.trim() || '';
            },

            getActionTypeName() {
                const select = document.querySelector('select[name="initial_action_type"]');
                const option = select?.querySelector(`option[value="${this.formData.initial_action_type}"]`);
                return option?.textContent?.trim() || '';
            },

            // ============ INIT ============
            initModal() {
                console.log('Case form modal initialized');
                
                window.addEventListener('open-case-create', () => {
                    this.openCreate();
                });

                window.addEventListener('edit-case', (event) => {
                    this.openEdit(event.detail.case);
                });

                // Load borrower data when user_id changes
                this.$watch('formData.user_id', () => {
                    this.loadBorrowerData();
                });
            },

            defaultFormData() {
                return {
                    id: null,
                    user_id: '',
                    loan_id: '',
                    total_debt_amount: '',
                    principal_outstanding: '',
                    interest_outstanding: '',
                    penalty_outstanding: '',
                    fees_outstanding: '',
                    default_date: new Date().toISOString().split('T')[0],
                    status_id: '',
                    priority_id: '',
                    assigned_to: '',
                    recovery_strategy: '',
                    notes: '',
                    initial_action_type: '',
                    initial_action_notes: ''
                };
            },

            openCreate() {
                console.log('Opening create modal');
                this.isEditMode = false;
                this.isOpen = true;
                this.errors = {};
                this.formData = this.defaultFormData();
                this.borrowerLoans = [];
                this.showNplWarning = false;
                this.nplLoanCount = 0;
                this.notesList = [];
                this.showCompilation = false;
                this.selectedStrategy = '';
                document.body.style.overflow = 'hidden';
            },

            openEdit(caseData) {
                console.log('Opening edit modal for:', caseData);
                this.isEditMode = true;
                this.isOpen = true;
                this.errors = {};
                this.notesList = [];
                
                this.formData = {
                    id: caseData.id,
                    user_id: caseData.user_id || '',
                    loan_id: caseData.loan_id || '',
                    total_debt_amount: caseData.total_debt_amount || '',
                    principal_outstanding: caseData.principal_outstanding || '',
                    interest_outstanding: caseData.interest_outstanding || '',
                    penalty_outstanding: caseData.penalty_outstanding || '',
                    fees_outstanding: caseData.fees_outstanding || '',
                    default_date: caseData.default_date || new Date().toISOString().split('T')[0],
                    status_id: caseData.status_id || '',
                    priority_id: caseData.priority_id || '',
                    assigned_to: caseData.assigned_to || '',
                    recovery_strategy: caseData.recovery_strategy || '',
                    notes: caseData.notes || '',
                    initial_action_type: '',
                    initial_action_notes: ''
                };

                // Parse notes into list
                if (this.formData.notes) {
                    const notes = this.formData.notes.replace(/^•\s*/, '').split('•').map(n => n.trim()).filter(n => n);
                    this.notesList = notes.length > 0 ? notes : [''];
                } else {
                    this.notesList = [''];
                }
                
                // Load borrower loans
                this.loadBorrowerData();
                this.updateCaseCompilation();
                document.body.style.overflow = 'hidden';
            },

            close() {
                this.isOpen = false;
                this.isSubmitting = false;
                document.body.style.overflow = '';
            },

            async submitForm() {
                this.isSubmitting = true;
                this.errors = {};

                // Ensure notes are updated
                this.updateNotes();

                try {
                    const response = await fetch(this.submitUrl, {
                        method: this.method,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(this.formData)
                    });

                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned an error. Please check the logs.');
                    }

                    const data = await response.json();

                    if (!response.ok) {
                        if (response.status === 422 && data.errors) {
                            this.errors = data.errors;
                            window.dispatchEvent(new CustomEvent('show-alert', {
                                detail: {
                                    type: 'error',
                                    title: 'Validation Error',
                                    message: Object.values(data.errors)[0][0] || 'Please check the form for errors.'
                                }
                            }));
                        } else {
                            throw new Error(data.message || 'Failed to save case');
                        }
                    } else {
                        window.dispatchEvent(new CustomEvent('show-alert', {
                            detail: {
                                type: 'success',
                                title: 'Success!',
                                message: data.message || 'Case saved successfully.'
                            }
                        }));
                        this.close();
                        window.dispatchEvent(new CustomEvent('refresh-cases'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: {
                            type: 'error',
                            title: 'Error',
                            message: error.message || 'Failed to save case. Please try again.'
                        }
                    }));
                } finally {
                    this.isSubmitting = false;
                }
            }
        };
    });
});
</script>