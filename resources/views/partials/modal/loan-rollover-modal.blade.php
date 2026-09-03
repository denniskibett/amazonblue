{{-- resources/views/partials/modal/loan-rollover-modal.blade.php --}}
<div 
    x-data="rolloverModal()" 
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
        class="fixed right-0 top-0 h-full w-full max-w-5xl bg-white dark:bg-gray-900 shadow-2xl z-[99999] overflow-y-auto"
        @click.away="close()"
    >
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 p-4 sticky top-0 bg-white dark:bg-gray-900 z-10">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Rollover Loan</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Renew the loan and capitalize the interest</p>
                </div>
                <button @click="close()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="submitRollover()" class="flex-1 overflow-y-auto p-6">
                @csrf

                <!-- Loan Information -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Loan ID</span>
                        <p class="font-semibold text-gray-900 dark:text-white" x-text="loanId ? '#' + String(loanId).padStart(5, '0') : 'N/A'"></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Interest Rate</span>
                        <p class="font-semibold text-gray-900 dark:text-white" x-text="interestRate + '%'"></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Current Cycle</span>
                        <p class="font-semibold text-purple-600 dark:text-purple-400" x-text="currentCycle"></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Original Principal</span>
                        <p class="font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(originalPrincipal)"></p>
                    </div>
                </div>

                <!-- Potential Cycles Warning -->
                <div x-show="missedCycles > 0" class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300 mb-2">⚠️ Overdue Analysis</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Days Overdue</span>
                            <p class="font-semibold text-red-600 dark:text-red-400" x-text="daysOverdue"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Period</span>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="periodDisplay"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Missed Rollovers</span>
                            <p class="font-semibold text-orange-600 dark:text-orange-400" x-text="missedCycles"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Potential Cycles</span>
                            <p class="font-semibold text-purple-600 dark:text-purple-400" x-text="potentialCycles"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Current Due Date</span>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="currentDueDate"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Next Due Date</span>
                            <p class="font-semibold text-green-600 dark:text-green-400" x-text="nextDueDateIfRolled"></p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-yellow-700 dark:text-yellow-400">
                        This loan has been overdue for <span x-text="daysOverdue"></span> days. 
                        <span x-text="missedCycles"></span> rollover(s) have been missed.
                    </p>
                </div>

                <!-- Interest Type Selection - MUTUALLY EXCLUSIVE -->
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3">Interest Type</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Simple Interest Radio -->
                        <label class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 cursor-pointer transition-all duration-200 hover:shadow-md"
                               :class="interestType === 'simple' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 shadow-md' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'"
                               @click="interestType = 'simple'">
                            <input type="radio" x-model="interestType" value="simple" class="mt-1 w-4 h-4 text-blue-600 focus:ring-blue-500">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-gray-900 dark:text-white">Simple Interest</p>
                                    <span x-show="interestType === 'simple'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                        Selected
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Interest calculated on original principal only<br>
                                    <span class="text-blue-600 dark:text-blue-400 font-medium"><span x-text="formatCurrency(simpleInterest)"></span> per cycle</span>
                                </p>
                            </div>
                        </label>

                        <!-- Compound Interest Radio -->
                        <label class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 cursor-pointer transition-all duration-200 hover:shadow-md"
                               :class="interestType === 'compound' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30 shadow-md' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'"
                               @click="interestType = 'compound'">
                            <input type="radio" x-model="interestType" value="compound" class="mt-1 w-4 h-4 text-purple-600 focus:ring-purple-500">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-gray-900 dark:text-white">Compound Interest</p>
                                    <span x-show="interestType === 'compound'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300">
                                        Selected
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Interest calculated on current balance<br>
                                    <span class="text-purple-600 dark:text-purple-400 font-medium"><span x-text="formatCurrency(compoundInterest)"></span> this cycle</span>
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Info Text -->
                    <div class="mt-4 p-3 rounded-lg bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-xs text-gray-600 dark:text-gray-300">
                                <template x-if="interestType === 'simple'">
                                    <div>
                                        <p class="font-medium text-blue-700 dark:text-blue-300">💡 Simple Interest</p>
                                        <p>Interest is calculated on the original principal only. The interest amount stays the same each cycle.</p>
                                        <p class="mt-1 text-blue-600 dark:text-blue-400">Example: 20% of KES 10,000 = KES 2,000 each cycle</p>
                                    </div>
                                </template>
                                <template x-if="interestType === 'compound'">
                                    <div>
                                        <p class="font-medium text-purple-700 dark:text-purple-300">💡 Compound Interest</p>
                                        <p>Interest is calculated on the current balance (principal + accumulated interest). The interest grows each cycle.</p>
                                        <p class="mt-1 text-purple-600 dark:text-purple-400">Example: Cycle 1: 20% of 10,000 = 2,000 | Cycle 2: 20% of 12,000 = 2,400</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>


<!-- ============ PAYMENT PLAN TOGGLE ============ -->
<div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
    <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" x-model="waivePenalty" class="mt-1 w-4 h-4 text-yellow-600 focus:ring-yellow-500">
        <div>
            <div class="flex items-center gap-2">
                <p class="font-medium text-gray-900 dark:text-white">📋 Payment Plan (Waive Penalties)</p>
                <span x-show="waivePenalty" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">
                    Selected
                </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Create a payment plan with waived penalties. New cycle will be in forbearance.
            </p>
            <div x-show="waivePenalty" class="mt-2 p-2 bg-green-100 dark:bg-green-800/30 rounded-lg">
                <p class="text-xs text-green-700 dark:text-green-300 font-medium">✅ Penalties will be WAIVED for this cycle</p>
            </div>
        </div>
    </label>
</div>

                <!-- Rollover Preview -->
                <div class="mb-6 p-4 bg-white dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Current Rollover Preview</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Current Balance</span>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(currentBalance)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Interest to Capitalize</span>
                            <p class="font-semibold" 
                               :class="interestType === 'simple' ? 'text-blue-600 dark:text-blue-400' : 'text-purple-600 dark:text-purple-400'"
                               x-text="formatCurrency(interestType === 'simple' ? simpleInterest : compoundInterest)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">New Balance</span>
                            <p class="font-semibold text-green-600 dark:text-green-400" 
                               x-text="formatCurrency(interestType === 'simple' ? simpleNewBalance : compoundNewBalance)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Current Cycle</span>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="currentCycle"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">New Cycle</span>
                            <p class="font-semibold text-purple-600 dark:text-purple-400" x-text="newCycleNumber"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">New Due Date</span>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="newDueDate"></p>
                        </div>
                    </div>
                </div>

                <!-- Future Rollover Projections -->
                <div x-show="projections && projections.length > 0" class="mb-6 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">📈 Future Rollover Projections</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Cycle</th>
                                    <th class="px-2 py-1 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Previous Balance</th>
                                    <th class="px-2 py-1 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Interest</th>
                                    <th class="px-2 py-1 text-right text-xs font-medium text-gray-500 dark:text-gray-400">New Balance</th>
                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Due Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <template x-for="proj in projections" :key="proj.cycle_number">
                                    <tr>
                                        <td class="px-2 py-1 font-medium text-gray-900 dark:text-white" x-text="'#' + proj.cycle_number"></td>
                                        <td class="px-2 py-1 text-right text-gray-600 dark:text-gray-400" x-text="formatCurrency(interestType === 'simple' ? proj.simple_previous_balance : proj.compound_previous_balance)"></td>
                                        <td class="px-2 py-1 text-right"
                                           :class="interestType === 'simple' ? 'text-blue-600 dark:text-blue-400' : 'text-purple-600 dark:text-purple-400'"
                                           x-text="formatCurrency(interestType === 'simple' ? proj.simple_interest : proj.compound_interest)"></td>
                                        <td class="px-2 py-1 text-right font-medium text-green-600 dark:text-green-400" 
                                            x-text="formatCurrency(interestType === 'simple' ? proj.simple_new_balance : proj.compound_new_balance)"></td>
                                        <td class="px-2 py-1 text-gray-600 dark:text-gray-400" x-text="proj.due_date"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Showing next 5 cycles with <span x-text="interestType"></span> interest at <span x-text="interestRate"></span>%
                    </p>
                </div>

                <!-- Previous Due Date -->
                <div class="mb-6 p-3 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400">📅</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">Previous Due Date</span>
                        <span class="ml-auto text-sm text-gray-600 dark:text-gray-400" x-text="previousDueDate"></span>
                    </div>
                </div>

                <!-- Grace Days -->
                <div class="mb-6 p-3 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
                    <div class="flex items-center gap-2">
                        <span x-show="graceDaysBalance > 0" class="text-blue-600 dark:text-blue-400">📋</span>
                        <span x-show="graceDaysBalance <= 0" class="text-gray-500 dark:text-gray-400">📅</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300" x-text="graceDaysBalance > 0 ? graceDaysBalance + ' Grace Days Available' : 'No Grace Days'"></span>
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Grace days will carry over to the new cycle
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Notes (Optional)</label>
                    <textarea x-model="notes" rows="3" 
                              class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                              placeholder="Reason for rollover..."></textarea>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-900">
                    <button type="button" @click="close()" 
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting"
                        class="rounded-lg bg-purple-600 px-6 py-2 text-sm font-medium text-white hover:bg-purple-700 focus:ring-4 focus:ring-purple-300 dark:focus:ring-purple-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isSubmitting">Confirm Rollover</span>
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
    Alpine.data('rolloverModal', function() {
        return {
            open: false,
            isSubmitting: false,
            loanId: null,
            currentBalance: 0,
            interestType: 'simple', // CHANGED: Default is now 'simple'
            currentCycle: 1,
            newCycleNumber: 2,
            newDueDate: '',
            previousDueDate: '',
            interestRate: 0,
            graceDaysBalance: 0,
            originalPrincipal: 0,
            // Simple Interest
            simpleInterest: 0,
            simpleNewBalance: 0,
            // Compound Interest
            compoundInterest: 0,
            compoundNewBalance: 0,
            // Cycle info
            missedCycles: 0,
            potentialCycles: 0,
            daysOverdue: 0,
            periodDisplay: '',
            nextDueDateIfRolled: '',
            currentDueDate: '',
            projections: [],
            notes: '',
            errors: {},

            init() {
                window.addEventListener('open-rollover-modal', (event) => {
                    this.openModal(event.detail);
                });
            },

            openModal(data) {
                this.loanId = data.loanId;
                this.currentBalance = data.currentBalance || 0;
                this.currentCycle = data.currentCycle || 1;
                this.newCycleNumber = (data.currentCycle || 1) + 1;
                this.newDueDate = data.newDueDate || '';
                this.previousDueDate = data.previousDueDate || '';
                this.interestRate = data.interestRate || 0;
                this.graceDaysBalance = data.graceDaysBalance || 0;
                this.originalPrincipal = data.originalPrincipal || data.currentBalance || 0;
                // Simple Interest
                this.simpleInterest = data.simple_interest || 0;
                this.simpleNewBalance = data.simple_new_balance || 0;
                // Compound Interest
                this.compoundInterest = data.compound_interest || 0;
                this.compoundNewBalance = data.compound_new_balance || 0;
                // Cycle info
                this.missedCycles = data.missedCycles || 0;
                this.potentialCycles = data.potentialCycles || 0;
                this.daysOverdue = data.daysOverdue || 0;
                this.periodDisplay = data.periodDisplay || '';
                this.nextDueDateIfRolled = data.nextDueDateIfRolled || '';
                this.currentDueDate = data.currentDueDate || '';
                this.projections = data.projections || [];
                // CHANGED: Set default to 'simple'
                this.interestType = 'simple';
                this.notes = '';
                this.errors = {};
                this.open = true;
                document.body.style.overflow = 'hidden';
            },

            close() {
                this.open = false;
                this.isSubmitting = false;
                document.body.style.overflow = '';
            },

            formatCurrency(amount) {
                if (amount === undefined || amount === null) return 'KES 0.00';
                return 'KES ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            },

            async submitRollover() {
                this.isSubmitting = true;
                this.errors = {};

                try {
                    const response = await fetch(`/loans/${this.loanId}/rollover`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            notes: this.notes,
                            interest_type: this.interestType,
                            waive_penalty: this.waivePenalty,  // Add this
                            interest_rate: this.customInterestRate,  // Add custom rate
                            period_days: this.customPeriodDays  // Add custom period
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        if (response.status === 422 && data.errors) {
                            this.errors = data.errors;
                            throw new Error(Object.values(data.errors).flat().join('\n'));
                        }
                        throw new Error(data.message || 'Failed to rollover loan');
                    }

                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: {
                            type: 'success',
                            title: 'Success!',
                            message: data.message || 'Loan rolled over successfully.'
                        }
                    }));

                    this.close();
                    setTimeout(() => window.location.reload(), 1000);

                } catch (error) {
                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: {
                            type: 'error',
                            title: 'Error',
                            message: error.message || 'Failed to rollover loan'
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