{{-- resources/views/partials/modal/loan-payment-plan-modal.blade.php --}}
<div 
    x-data="paymentPlanModal()" 
    x-init="init()"
    x-cloak
>
    <!-- Backdrop -->
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 z-[99999]"
        @click="close()"
    ></div>

    <!-- Modal Slideover -->
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="transform translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform translate-x-full"
        class="fixed right-0 top-0 h-full w-full max-w-3xl bg-white dark:bg-gray-900 shadow-2xl z-[99999] overflow-y-auto"
        @click.away="close()"
    >
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 p-4 sticky top-0 bg-white dark:bg-gray-900 z-10">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        <span x-show="!editMode">📋 Payment Plan / Forbearance</span>
                        <span x-show="editMode">✏️ Edit Payment Plan</span>
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <span x-show="!editMode">Create a payment plan with waived penalties</span>
                        <span x-show="editMode">Modify existing payment plan terms</span>
                    </p>
                </div>
                <button @click="close()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="submitPaymentPlan()" class="flex-1 overflow-y-auto p-6">
                @csrf

                <!-- ============ SECTION 1: CURRENT CYCLE FIGURES (READ-ONLY) ============ -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                        <span class="text-gray-500">📊</span> Current Cycle #<span x-text="currentCycle"></span> Figures
                        <span class="ml-auto text-xs text-gray-400">(Read-Only)</span>
                    </h4>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Principal</span>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(currentPrincipal)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Interest Rate</span>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="currentInterestRate + '%'"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Interest</span>
                            <p class="font-semibold text-purple-600 dark:text-purple-400" x-text="formatCurrency(currentInterest)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Balance</span>
                            <p class="font-semibold text-blue-600 dark:text-blue-400" x-text="formatCurrency(currentBalance)"></p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Repayments (This Cycle)</span>
                            <p class="font-semibold text-green-600 dark:text-green-400" x-text="formatCurrency(currentRepayments)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Penalties</span>
                            <p class="font-semibold text-red-600 dark:text-red-400" x-text="formatCurrency(currentPenalty)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Outstanding</span>
                            <p class="font-semibold text-orange-600 dark:text-orange-400" x-text="formatCurrency(outstanding)"></p>
                        </div>
                    </div>
                    
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Due Date: <span x-text="currentDueDate"></span> | 
                        Days Overdue: <span x-text="daysOverdue"></span> days
                    </div>
                </div>

                <!-- ============ SECTION 2: CURRENT PENALTIES WARNING ============ -->
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <div class="text-sm">
                            <p class="font-semibold text-red-800 dark:text-red-300">⚠️ Current Penalties: <span x-text="formatCurrency(currentPenalty)"></span></p>
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                                These penalties will be <strong>WAIVED</strong> when you create this payment plan.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ============ SECTION 3: PAYMENT PLAN TYPE ============ -->
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3">📋 Payment Plan Type</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 cursor-pointer transition-all"
                               :class="planType === 'standard' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-gray-200 dark:border-gray-700'"
                               @click="planType = 'standard'">
                            <input type="radio" x-model="planType" value="standard" class="w-4 h-4 text-blue-600">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Standard</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Use calculated outstanding balance</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center gap-2 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 cursor-pointer transition-all"
                               :class="planType === 'manual' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30' : 'border-gray-200 dark:border-gray-700'"
                               @click="planType = 'manual'">
                            <input type="radio" x-model="planType" value="manual" class="w-4 h-4 text-purple-600">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Manual Adjustment</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Set custom balance (e.g., 180k → 50k)</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- ============ SECTION 4: STANDARD PAYMENT PLAN PREVIEW ============ -->
                <div x-show="planType === 'standard'" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <h4 class="text-sm font-semibold text-green-800 dark:text-green-300 mb-3">✅ Standard Payment Plan Preview</h4>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Current Outstanding</span>
                            <p class="font-semibold text-orange-600 dark:text-orange-400" x-text="formatCurrency(outstanding)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">New Principal</span>
                            <p class="font-semibold text-blue-600 dark:text-blue-400" x-text="formatCurrency(outstanding)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">New Interest (<span x-text="interestRate"></span>%)</span>
                            <p class="font-semibold text-purple-600 dark:text-purple-400" x-text="formatCurrency(newInterest)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">New Balance</span>
                            <p class="font-semibold text-green-600 dark:text-green-400" x-text="formatCurrency(newBalance)"></p>
                        </div>
                    </div>
                </div>

                <!-- ============ SECTION 5: MANUAL ADJUSTMENT ============ -->
                <div x-show="planType === 'manual'" class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <h4 class="text-sm font-semibold text-purple-800 dark:text-purple-300 mb-3">✏️ Manual Balance Adjustment</h4>
                    
                    <div class="space-y-4">
                        <!-- Current vs New Comparison -->
                        <div class="grid grid-cols-2 gap-4 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Current Outstanding</span>
                                <p class="font-semibold text-red-600 dark:text-red-400" x-text="formatCurrency(outstanding)"></p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Reduction</span>
                                <p class="font-semibold text-green-600 dark:text-green-400" x-text="formatCurrency(outstanding - (manualNewBalance || 0))"></p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                New Principal Amount (KES) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" x-model="manualNewBalance" step="0.01" min="0"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                @input="calculateManualPreview()"
                                x-bind:required="planType === 'manual'">>  
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Enter the new principal amount. Interest will be calculated on this amount.
                            </p>
                        </div>
                        
                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-3 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">New Principal</span>
                                    <p class="font-semibold text-purple-600 dark:text-purple-400" x-text="formatCurrency(manualNewBalance || 0)"></p>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Interest (<span x-text="interestRate"></span>%)</span>
                                    <p class="font-semibold text-purple-600 dark:text-purple-400" x-text="formatCurrency(manualNewInterest)"></p>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">New Balance</span>
                                    <p class="font-semibold text-green-600 dark:text-green-400" x-text="formatCurrency(manualNewTotal)"></p>
                                </div>
                            </div>
                            <div class="mt-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <p class="text-xs text-yellow-700 dark:text-yellow-300">
                                    ⚠️ This will reduce the balance from <span x-text="formatCurrency(outstanding)"></span> to <span x-text="formatCurrency(manualNewBalance || 0)"></span>
                                    <span class="block text-green-600 dark:text-green-400 mt-1" x-show="outstanding - (manualNewBalance || 0) > 0">
                                        ✅ Reduction: <span x-text="formatCurrency(outstanding - (manualNewBalance || 0))"></span>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ SECTION 6: COMMON SETTINGS ============ -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">⚙️ Payment Plan Settings</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Interest Rate (%) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" x-model="interestRate" step="0.01" min="0" max="100"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                @input="calculatePreview()"
                                required>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Default: {{ $loan->loanType->interest_rate ?? 20 }}%</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Period (Days) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" x-model="periodDays" min="1"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                @input="calculatePreview()"
                                required>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Default: {{ $loan->loanType->period ?? 30 }} days</p>
                        </div>
                    </div>
                </div>

                <!-- ============ SECTION 7: FINAL PREVIEW ============ -->
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <h4 class="text-sm font-semibold text-green-800 dark:text-green-300 mb-3">✅ Final Payment Plan Summary</h4>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Cycle</span>
                            <p class="font-semibold text-purple-600 dark:text-purple-400">
                                #<span x-text="newCycleNumber"></span>
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Principal</span>
                            <p class="font-semibold text-gray-900 dark:text-white" 
                               x-text="formatCurrency(planType === 'manual' ? (manualNewBalance || 0) : outstanding)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Interest</span>
                            <p class="font-semibold text-purple-600 dark:text-purple-400" 
                               x-text="formatCurrency(planType === 'manual' ? manualNewInterest : newInterest)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">New Balance</span>
                            <p class="font-semibold text-green-600 dark:text-green-400 text-lg" 
                               x-text="formatCurrency(planType === 'manual' ? manualNewTotal : newBalance)"></p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm mt-3 pt-3 border-t border-green-200 dark:border-green-700">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Interest Rate</span>
                            <p class="font-semibold text-purple-600 dark:text-purple-400" x-text="interestRate + '%'"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Period</span>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="periodDays + ' days'"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Due Date</span>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="newDueDate"></p>
                        </div>
                    </div>
                    
                    <div class="mt-3 p-2 bg-yellow-100 dark:bg-yellow-800/30 rounded-lg">
                        <p class="text-xs text-yellow-700 dark:text-yellow-300 font-medium">
                            🟢 Penalties will be <strong>WAIVED</strong> | 
                            <span x-show="planType === 'standard'">Standard calculation</span>
                            <span x-show="planType === 'manual'">Manual adjustment applied</span>
                        </p>
                    </div>
                </div>

                <!-- ============ SECTION 8: NOTES ============ -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Notes (Optional)</label>
                    <textarea x-model="notes" rows="3" 
                              class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                              placeholder="Reason for payment plan or manual adjustment..."></textarea>
                </div>

                <!-- ============ SECTION 9: FOOTER ============ -->
                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-900">
                    <button type="button" @click="close()" 
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting || (planType === 'manual' && (!manualNewBalance || manualNewBalance < 0))"
                        class="rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isSubmitting">
                            <span x-show="planType === 'manual'">Apply Manual Adjustment</span>
                            <span x-show="planType === 'standard'">Create Payment Plan</span>
                        </span>
                        <span x-show="isSubmitting" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('paymentPlanModal', function() {
        return {
            open: false,
            isSubmitting: false,
            editMode: false,
            editId: null,
            
            // Loan info
            loanId: null,
            currentCycle: 1,
            newCycleNumber: 2,
            
            // Current cycle figures
            currentPrincipal: 0,
            currentInterestRate: 0,
            currentInterest: 0,
            currentBalance: 0,
            currentRepayments: 0,
            currentPenalty: 0,
            currentTotal: 0,
            daysOverdue: 0,
            currentDueDate: '',
            outstanding: 0,
            
            // Payment plan settings
            interestRate: 20,
            periodDays: 30,
            planType: 'standard',
            manualNewBalance: null,
            manualNewInterest: 0,
            manualNewTotal: 0,
            newInterest: 0,
            newBalance: 0,
            newDueDate: '',
            notes: '',

            init() {
                window.addEventListener('open-payment-plan-modal', (event) => {
                    this.openModal(event.detail);
                });
            },

            async openModal(data) {
                this.loanId = data.loanId;
                this.editMode = data.editMode || false;
                this.editId = data.editId || null;
                this.interestRate = 20;
                this.periodDays = 30;
                this.notes = '';
                this.planType = 'standard';
                this.manualNewBalance = null;
                
                // Fetch current loan data for preview
                try {
                    const url = this.editMode 
                        ? `/payment-plans/${this.editId}/data` 
                        : `/loans/${this.loanId}/payment-plan-preview`;
                    
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const result = await response.json();
                    if (result.success) {
                        // Current cycle figures
                        this.currentPrincipal = result.data.current_principal || 0;
                        this.currentInterestRate = result.data.current_interest_rate || 0;
                        this.currentInterest = result.data.current_interest || 0;
                        this.currentBalance = result.data.current_balance || 0;
                        this.currentRepayments = result.data.current_repayments || 0;
                        this.currentPenalty = result.data.current_penalty || 0;
                        this.currentTotal = result.data.current_total || 0;
                        this.daysOverdue = result.data.days_overdue || 0;
                        this.currentCycle = result.data.current_cycle || 1;
                        this.newCycleNumber = (result.data.current_cycle || 1) + 1;
                        this.currentDueDate = result.data.current_due_date || '';
                        this.outstanding = result.data.outstanding || this.currentBalance;
                        
                        // If editing, populate fields
                        if (this.editMode && result.data.payment_plan) {
                            this.interestRate = result.data.payment_plan.interest_rate || 20;
                            this.periodDays = result.data.payment_plan.period_days || 30;
                            this.notes = result.data.payment_plan.notes || '';
                            this.planType = result.data.payment_plan.plan_type || 'standard';
                            if (this.planType === 'manual') {
                                this.manualNewBalance = result.data.payment_plan.manual_balance || null;
                            }
                        } else {
                            this.interestRate = result.data.interest_rate || 20;
                            this.periodDays = result.data.period_days || 30;
                        }
                        
                        this.calculatePreview();
                    }
                } catch (error) {
                    console.error('Error fetching preview:', error);
                    this.outstanding = this.currentBalance;
                    this.calculatePreview();
                }
                
                this.open = true;
                document.body.style.overflow = 'hidden';
            },

            calculatePreview() {
                // Standard calculation
                this.newInterest = this.outstanding * (this.interestRate / 100);
                this.newBalance = this.outstanding + this.newInterest;
                
                // Manual calculation
                if (this.manualNewBalance && this.manualNewBalance > 0) {
                    this.calculateManualPreview();
                }
                
                const dueDate = new Date();
                dueDate.setDate(dueDate.getDate() + this.periodDays);
                this.newDueDate = dueDate.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
            },

            calculateManualPreview() {
                const principal = parseFloat(this.manualNewBalance) || 0;
                this.manualNewInterest = principal * (this.interestRate / 100);
                this.manualNewTotal = principal + this.manualNewInterest;
            },

            formatCurrency(amount) {
                if (amount === undefined || amount === null) return 'KES 0.00';
                return 'KES ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            },

            close() {
                this.open = false;
                this.isSubmitting = false;
                this.editMode = false;
                document.body.style.overflow = '';
            },

            async submitPaymentPlan() {
                this.isSubmitting = true;
                
                try {
                    const payload = {
                        interest_rate: this.interestRate,
                        period_days: this.periodDays,
                        notes: this.notes,
                        plan_type: this.planType,
                    };
                    
                    // Add manual balance if manual adjustment is selected
                    if (this.planType === 'manual' && this.manualNewBalance !== null) {
                        payload.manual_new_balance = this.manualNewBalance;
                    }
                    
                    const url = this.editMode 
                        ? `/payment-plans/${this.editId}` 
                        : `/loans/${this.loanId}/payment-plan`;
                    
                    const method = this.editMode ? 'PUT' : 'POST';
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ ...payload, _method: method })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to create payment plan');
                    }

                    const message = this.editMode
                        ? 'Payment plan updated successfully!'
                        : (this.planType === 'manual' 
                            ? 'Manual adjustment applied successfully! Balance updated.' 
                            : 'Payment plan created successfully! Penalties have been waived.');

                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: {
                            type: 'success',
                            title: 'Success!',
                            message: message
                        }
                    }));

                    this.close();
                    setTimeout(() => window.location.reload(), 1500);

                } catch (error) {
                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: {
                            type: 'error',
                            title: 'Error',
                            message: error.message || 'Failed to create payment plan'
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