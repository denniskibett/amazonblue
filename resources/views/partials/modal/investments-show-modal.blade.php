{{-- resources/views/partials/modal/investments-show-modal.blade.php --}}
<div x-data="investmentShowModal()" 
     x-init="initModal()"
     x-show="isOpen" 
     x-cloak
     class="fixed inset-0 z-[99999] overflow-y-auto"
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
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="investment?.name || 'Investment Details'"></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="investment?.type || ''"></p>
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
                    <template x-if="investment">
                        <div class="space-y-6">
                            <!-- Summary Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</p>
                                    <p class="text-xl font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(investment.initial_amount)"></p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Value</p>
                                    <p class="text-xl font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(investment.current_value)"></p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Return</p>
                                    <p class="text-xl font-semibold" 
                                       :class="(investment.return_percentage || 0) >= 15 ? 'text-green-600 dark:text-green-400' : (investment.return_percentage >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400')"
                                       x-text="(investment.return_percentage || 0).toFixed(1) + '%'"></p>
                                </div>
                            </div>

                            <!-- Details Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</label>
                                    <p class="text-gray-900 dark:text-white" x-text="investment.type || 'N/A'"></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Sector</label>
                                    <p class="text-gray-900 dark:text-white" x-text="investment.sector || 'N/A'"></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Country</label>
                                    <p class="text-gray-900 dark:text-white" x-text="investment.country || 'N/A'"></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': investment.status === 'pipeline' || investment.status === 'due_diligence',
                                              'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': investment.status === 'active',
                                              'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': investment.status === 'matured',
                                              'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': investment.status === 'liquidated',
                                              'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': investment.status === 'write_off'
                                          }">
                                        <span x-text="investment.status ? investment.status.replace('_', ' ').toUpperCase() : 'N/A'"></span>
                                    </span>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Purchase Date</label>
                                    <p class="text-gray-900 dark:text-white" x-text="investment.purchase_date || 'N/A'"></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Maturity Date</label>
                                    <p class="text-gray-900 dark:text-white" x-text="investment.maturity_date || 'N/A'"></p>
                                </div>
                            </div>

                            <!-- Company Details -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Company Details</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Company Name</label>
                                        <p class="text-gray-900 dark:text-white" x-text="investment.company_name || 'N/A'"></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Registration Number</label>
                                        <p class="text-gray-900 dark:text-white" x-text="investment.registration_number || 'N/A'"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Pre-Investment Financials -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Pre-Investment Financials</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">EBITDA</label>
                                        <p class="text-gray-900 dark:text-white" x-text="formatCurrency(investment.ebitda_pre_investment)"></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenue</label>
                                        <p class="text-gray-900 dark:text-white" x-text="formatCurrency(investment.revenue_pre_investment)"></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Profit</label>
                                        <p class="text-gray-900 dark:text-white" x-text="formatCurrency(investment.net_profit_pre_investment)"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Funding Partners -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Funding Partners</h4>
                                <div x-show="investment.funding_partners && investment.funding_partners.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="(partner, idx) in investment.funding_partners" :key="idx">
                                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="partner.partner_name || 'Unknown'"></span>
                                                <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="formatCurrency(partner.amount)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <p x-show="!investment.funding_partners || investment.funding_partners.length === 0" 
                                   class="text-sm text-gray-500 dark:text-gray-400">No partners funded this investment</p>
                            </div>

                            <!-- Notes -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Notes</h4>
                                <div x-show="investment.notes && investment.notes.length > 0">
                                    <div class="space-y-3">
                                        <template x-for="(note, idx) in investment.notes" :key="idx">
                                            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400" x-text="note.author || 'System'"></span>
                                                    <span class="text-xs text-gray-400 dark:text-gray-500" x-text="note.date"></span>
                                                </div>
                                                <p class="text-sm text-gray-700 dark:text-gray-300" x-text="note.content"></p>
                                                <span class="text-xs text-gray-400 dark:text-gray-500" x-text="note.category ? note.category.toUpperCase() : ''"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <p x-show="!investment.notes || investment.notes.length === 0" 
                                   class="text-sm text-gray-500 dark:text-gray-400">No notes available</p>
                            </div>
                        </div>
                    </template>
                    
                    <!-- Loading state -->
                    <div x-show="!investment" class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <svg class="animate-spin mx-auto h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-4 text-gray-600 dark:text-gray-400">Loading investment details...</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0 bg-white dark:bg-gray-900">
                    <button @click="close()" 
                            class="flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                        Close
                    </button>
                    <button @click="editInvestment()" 
                            class="flex justify-center px-4 py-2.5 text-sm font-medium text-white rounded-lg bg-blue-600 shadow-theme-xs hover:bg-blue-700 transition-colors">
                        Edit Investment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('investmentShowModal', function() {
        return {
            isOpen: false,
            investment: null,
            editCallback: null,

            initModal() {
                // Ensure modal starts closed
                this.isOpen = false;
                
                window.addEventListener('show-investment', (event) => {
                    this.open(event.detail.investment);
                });
            },

            open(investment) {
                this.investment = investment;
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
            },

            close() {
                this.isOpen = false;
                this.investment = null;
                document.body.style.overflow = '';
            },

            editInvestment() {
                if (this.investment) {
                    this.close();
                    window.dispatchEvent(new CustomEvent('edit-investment', {
                        detail: { investment: this.investment }
                    }));
                }
            },

            formatCurrency(value) {
                if (!value && value !== 0) return 'KES 0.00';
                return 'KES ' + parseFloat(value).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        };
    });
});
</script>