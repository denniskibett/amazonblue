{{-- resources/views/partials/modal/partners-show-modal.blade.php --}}
<div x-data="partnerShowModal()" 
     x-show="isOpen" 
     class="fixed inset-0 z-99999 overflow-y-auto" 
     style="display: none;"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <div class="flex items-center justify-center min-h-screen p-5">
        <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]" @click="close()"></div>
        
        <div class="relative w-full max-w-4xl rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10 z-50 max-h-[90vh] overflow-y-auto">
            <!-- Close Button -->
            <button @click="close()" 
                    class="group absolute right-3 top-3 z-999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
                <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""/>
                </svg>
            </button>

            <div class="pr-4">
                <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90" x-text="partner.name || 'Partner Details'">Partner Details</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Complete partner information and financial summary</p>

                <!-- Partner Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Basic Info -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Basic Information</h5>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Name</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.name || 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Email</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.email || 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Phone</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.phone || 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Type</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.type ? partner.type.charAt(0).toUpperCase() + partner.type.slice(1) : 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Status</span>
                                    <span class="text-sm font-medium" 
                                          :class="partner.status === 'active' ? 'text-green-600 dark:text-green-400' : (partner.status === 'inactive' ? 'text-gray-600 dark:text-gray-400' : 'text-red-600 dark:text-red-400')"
                                          x-text="partner.status ? partner.status.charAt(0).toUpperCase() + partner.status.slice(1) : 'N/A'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Company Info -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Company Information</h5>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Company Name</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.company_name || 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Registration Number</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.registration_number || 'N/A'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Summary -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Financial Summary</h5>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Total Contribution</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="formatCurrency(partner.total_contribution || 0)"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Current Balance</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="formatCurrency(partner.current_balance || 0)"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Total Invested</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="formatCurrency(partner.total_invested || 0)"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Total Returned</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="formatCurrency(partner.total_returned || 0)"></span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Net Position</span>
                                    <span class="text-sm font-bold" 
                                          :class="(partner.total_invested - partner.total_returned) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                          x-text="formatCurrency((partner.total_invested || 0) - (partner.total_returned || 0))"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Banking Details -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Banking Details</h5>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Account Name</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.bank_account_name || 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Account Number</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.bank_account_number || 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Bank Name</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.bank_name || 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">SWIFT Code</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.swift_code || 'N/A'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Risk Settings -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Risk Settings</h5>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Profit Share Rate</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.profit_share_rate ? partner.profit_share_rate + '%' : 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Max LTV</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.max_loan_to_value ? partner.max_loan_to_value + '%' : 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Risk Tolerance</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.risk_tolerance ? partner.risk_tolerance.charAt(0).toUpperCase() + partner.risk_tolerance.slice(1) : 'N/A'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Notes</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400" x-text="partner.notes || 'No notes available'"></p>
                        </div>

                        <!-- Meta -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Meta Information</h5>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Created</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.created_at || 'N/A'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Updated</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="partner.updated_at || 'N/A'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                    <button @click="close()" 
                            class="flex justify-center px-6 py-2.5 text-sm font-medium text-gray-700 bg-white rounded-lg border border-gray-300 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                        Close
                    </button>
                    <button @click="editPartner()" 
                            class="flex justify-center px-6 py-2.5 text-sm font-medium text-white rounded-lg bg-blue-600 shadow-theme-xs hover:bg-blue-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Partner
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function partnerShowModal() {
    return {
        isOpen: false,
        partner: {},
        
        open(partner) {
            this.partner = partner;
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
        },
        
        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
        },
        
        editPartner() {
            this.close();
            window.dispatchEvent(new CustomEvent('edit-partner', {
                detail: { partner: this.partner }
            }));
        },
        
        formatCurrency(value) {
            if (!value && value !== 0) return 'KES 0.00';
            return 'KES ' + parseFloat(value).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
}

// Event listener for showing partner
document.addEventListener('alpine:init', () => {
    window.addEventListener('show-partner', (event) => {
        const modal = document.querySelector('[x-data="partnerShowModal()"]')?.__x?.$data;
        if (modal) modal.open(event.detail.partner);
    });
});
</script>
@endpush