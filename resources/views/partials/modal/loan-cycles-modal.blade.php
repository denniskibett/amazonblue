{{-- resources/views/partials/modal/loan-cycles-modal.blade.php --}}
<div 
    x-data="loanCyclesModal()" 
    x-init="init()"
    x-cloak
>
    <!-- Backdrop -->
    <div 
        x-show="isOpen" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 z-[99999]"
        @click="closeModal()"
    ></div>

    <!-- Modal Slideover -->
    <div 
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="transform translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform translate-x-full"
        class="fixed right-0 top-0 h-full w-full max-w-5xl bg-white dark:bg-gray-900 shadow-2xl z-[99999] overflow-y-auto"
        @click.away="closeModal()"
    >
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 p-4 sticky top-0 bg-white dark:bg-gray-900 z-10">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Loan Cycles</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Complete history of all loan cycles</p>
                </div>
                <button @click="closeModal()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Loading State -->
                <div x-show="loading" class="flex items-center justify-center py-12">
                    <svg class="animate-spin h-8 w-8 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-3 text-gray-500 dark:text-gray-400">Loading cycles...</span>
                </div>

                <!-- Content -->
                <div x-show="!loading">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="totalCycles"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Cycles</p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="formatCurrency(totalCapitalized)"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Capitalized</p>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="currentCycleNumber"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Current Cycle</p>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400" x-text="activeCycles"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Active Cycles</p>
                        </div>
                    </div>

                    <!-- Cycles Table -->
                    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Start Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Previous Balance</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Interest Capitalized</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">New Balance</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Due Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Days</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <template x-for="cycle in cycles" :key="cycle.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white" x-text="cycle.cycle_number"></td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="formatDate(cycle.start_date)"></td>
                                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400" x-text="formatCurrency(cycle.previous_balance)"></td>
                                        <td class="px-4 py-3 text-right text-purple-600 dark:text-purple-400" x-text="formatCurrency(cycle.interest_capitalized)"></td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white" x-text="formatCurrency(cycle.new_balance)"></td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="formatDate(cycle.due_date)"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                                                  :class="getStatusClass(cycle.status)">
                                                <span x-text="getStatusLabel(cycle.status)"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="cycle.days_in_cycle || '-'"></td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-[150px] truncate" x-text="cycle.notes || '-'" :title="cycle.notes || ''"></td>
                                    </tr>
                                </template>
                                <tr x-show="cycles.length === 0">
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No cycles found for this loan
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot x-show="cycles.length > 0" class="bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Totals</td>
                                    <td class="px-4 py-3 text-right font-bold text-purple-700 dark:text-purple-400" x-text="formatCurrency(totalCapitalized)"></td>
                                    <td class="px-4 py-3 text-right font-bold text-green-700 dark:text-green-400" x-text="formatCurrency(totalBalance)"></td>
                                    <td colspan="4" class="px-4 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- No Data Message -->
                    <div x-show="cycles.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No cycles found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This loan hasn't been rolled over yet.</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-900">
                    <button @click="closeModal()" 
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('loanCyclesModal', function() {
        return {
            isOpen: false,
            cycles: [],
            loanId: null,
            totalCycles: 0,
            totalCapitalized: 0,
            totalBalance: 0,
            currentCycleNumber: 0,
            activeCycles: 0,
            loading: false,

            init() {
                window.addEventListener('open-cycles-modal', (event) => {
                    this.openModal(event.detail);
                });
            },

            openModal(data) {
                this.loanId = data.loanId;
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
                this.loadCycles();
            },

            closeModal() {
                this.isOpen = false;
                document.body.style.overflow = '';
            },

            async loadCycles() {
                this.loading = true;
                try {
                    const response = await fetch(`/loans/${this.loanId}/cycles`);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.cycles = data.cycles;
                        this.totalCycles = data.total_cycles;
                        this.totalCapitalized = data.total_capitalized;
                        this.totalBalance = data.total_balance;
                        this.currentCycleNumber = data.current_cycle;
                        this.activeCycles = data.active_cycles;
                    }
                } catch (error) {
                    console.error('Error loading cycles:', error);
                } finally {
                    this.loading = false;
                }
            },

            formatCurrency(amount) {
                if (amount === undefined || amount === null) return 'KES 0.00';
                return 'KES ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            },

            formatDate(date) {
                if (!date) return '-';
                try {
                    return new Date(date).toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                } catch {
                    return date;
                }
            },

            getStatusLabel(status) {
                const labels = {
                    'active': 'Active',
                    'completed': 'Completed',
                    'defaulted': 'Defaulted',
                    'repaid': 'Repaid'
                };
                return labels[status] || status || 'Unknown';
            },

            getStatusClass(status) {
                const classes = {
                    'active': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                    'completed': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                    'defaulted': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                    'repaid': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                };
                return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
            }
        };
    });
});
</script>