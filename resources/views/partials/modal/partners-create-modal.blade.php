{{-- resources/views/partials/modal/partners-create-modal.blade.php --}}
<div x-data="partnerFormModal()" 
     x-init="initPartnerModal()"
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
                <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90" x-text="modalTitle">Create New Partner</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6" x-text="modalSubtitle">Add a new partner to your network</p>

                <form @submit.prevent="submitForm" class="space-y-6">
                    @csrf
                    <input type="hidden" x-model="formData.id">
                    
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Partner Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   x-model="formData.name" 
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                   placeholder="e.g., John Doe" required>
                            <template x-if="errors.name">
                                <p class="mt-1 text-sm text-red-500" x-text="errors.name[0]"></p>
                            </template>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" 
                                   x-model="formData.email" 
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                   placeholder="john@example.com" required>
                            <template x-if="errors.email">
                                <p class="mt-1 text-sm text-red-500" x-text="errors.email[0]"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Contact & Company -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Phone</label>
                            <input type="text" 
                                   x-model="formData.phone" 
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                   placeholder="+254 700 000 000">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Company Name</label>
                            <input type="text" 
                                   x-model="formData.company_name" 
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                   placeholder="e.g., ABC Investments Ltd">
                        </div>
                    </div>

                    <!-- Type & Status -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Partner Type <span class="text-red-500">*</span>
                            </label>
                            <div class="relative z-20 bg-transparent">
                                <select x-model="formData.type" 
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                                    <option value="individual">Individual</option>
                                    <option value="corporate">Corporate</option>
                                    <option value="institutional">Institutional</option>
                                </select>
                            </div>
                            <template x-if="errors.type">
                                <p class="mt-1 text-sm text-red-500" x-text="errors.type[0]"></p>
                            </template>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <div class="relative z-20 bg-transparent">
                                <select x-model="formData.status" 
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                            <template x-if="errors.status">
                                <p class="mt-1 text-sm text-red-500" x-text="errors.status[0]"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Financial Settings -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h5 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">Financial Settings</h5>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Profit Share Rate (%)</label>
                                <input type="number" 
                                       step="0.01" 
                                       x-model="formData.profit_share_rate" 
                                       class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                       placeholder="10.00">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Max LTV (%)</label>
                                <input type="number" 
                                       step="0.01" 
                                       x-model="formData.max_loan_to_value" 
                                       class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                       placeholder="75.00">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Risk Tolerance <span class="text-red-500">*</span>
                                </label>
                                <div class="relative z-20 bg-transparent">
                                    <select x-model="formData.risk_tolerance" 
                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                                        <option value="conservative">Conservative</option>
                                        <option value="moderate">Moderate</option>
                                        <option value="aggressive">Aggressive</option>
                                    </select>
                                </div>
                                <template x-if="errors.risk_tolerance">
                                    <p class="mt-1 text-sm text-red-500" x-text="errors.risk_tolerance[0]"></p>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Banking Details -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h5 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">Banking Details</h5>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bank Account Name</label>
                                <input type="text" 
                                       x-model="formData.bank_account_name" 
                                       class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                       placeholder="John Doe">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bank Account Number</label>
                                <input type="text" 
                                       x-model="formData.bank_account_number" 
                                       class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                       placeholder="1234567890">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bank Name</label>
                                <input type="text" 
                                       x-model="formData.bank_name" 
                                       class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                       placeholder="e.g., KCB Bank">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">SWIFT Code</label>
                                <input type="text" 
                                       x-model="formData.swift_code" 
                                       class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                       placeholder="e.g., KCBLKENX">
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Notes</label>
                        <textarea x-model="formData.notes" 
                                  rows="3" 
                                  class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                  placeholder="Any notes about this partner..."></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end w-full gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" @click="close()" 
                                class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 sm:w-auto">
                            Cancel
                        </button>
                        <button type="submit" 
                                :disabled="isSubmitting"
                                class="flex justify-center w-full px-4 py-3 text-sm font-medium text-white rounded-lg bg-blue-600 shadow-theme-xs hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto">
                            <span x-show="!isSubmitting" x-text="submitButtonText">Create Partner</span>
                            <span x-show="isSubmitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="loadingText">Creating...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function partnerFormModal() {
    return {
        isOpen: false,
        isSubmitting: false,
        isEditMode: false,
        errors: {},
        formData: {
            id: null,
            name: '',
            email: '',
            phone: '',
            company_name: '',
            registration_number: '',
            type: 'individual',
            status: 'active',
            profit_share_rate: '',
            max_loan_to_value: '75',
            risk_tolerance: 'moderate',
            bank_account_name: '',
            bank_account_number: '',
            bank_name: '',
            swift_code: '',
            tax_id: '',
            notes: ''
        },
        
        get modalTitle() {
            return this.isEditMode ? 'Edit Partner' : 'Create New Partner';
        },
        
        get modalSubtitle() {
            return this.isEditMode ? 'Update partner details' : 'Add a new partner to your network';
        },
        
        get submitButtonText() {
            return this.isEditMode ? 'Update Partner' : 'Create Partner';
        },
        
        get loadingText() {
            return this.isEditMode ? 'Updating...' : 'Creating...';
        },
        
        get submitUrl() {
            return this.isEditMode 
                ? `/partners/update/${this.formData.id}`
                : '{{ route("partners.store") }}';
        },
        
        get method() {
            return this.isEditMode ? 'PUT' : 'POST';
        },
        
        initPartnerModal() {
            // Listen for create event
            window.addEventListener('open-partner-create', () => {
                this.openCreate();
            });
            
            // Listen for edit event
            window.addEventListener('edit-partner', (event) => {
                this.openEdit(event.detail.partner);
            });
        },
        
        openCreate() {
            this.isEditMode = false;
            this.isOpen = true;
            this.errors = {};
            this.formData = {
                id: null,
                name: '',
                email: '',
                phone: '',
                company_name: '',
                registration_number: '',
                type: 'individual',
                status: 'active',
                profit_share_rate: '',
                max_loan_to_value: '75',
                risk_tolerance: 'moderate',
                bank_account_name: '',
                bank_account_number: '',
                bank_name: '',
                swift_code: '',
                tax_id: '',
                notes: ''
            };
            document.body.style.overflow = 'hidden';
        },
        
        openEdit(partner) {
            this.isEditMode = true;
            this.isOpen = true;
            this.errors = {};
            this.formData = {
                id: partner.id,
                name: partner.name || '',
                email: partner.email || '',
                phone: partner.phone || '',
                company_name: partner.company_name || '',
                registration_number: partner.registration_number || '',
                type: partner.type || 'individual',
                status: partner.status || 'active',
                profit_share_rate: partner.profit_share_rate || '',
                max_loan_to_value: partner.max_loan_to_value || '75',
                risk_tolerance: partner.risk_tolerance || 'moderate',
                bank_account_name: partner.bank_account_name || '',
                bank_account_number: partner.bank_account_number || '',
                bank_name: partner.bank_name || '',
                swift_code: partner.swift_code || '',
                tax_id: partner.tax_id || '',
                notes: partner.notes || ''
            };
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
                
                const data = await response.json();
                
                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        this.errors = data.errors;
                        window.showAlert('error', 'Please check the form for errors.', 'Validation Error');
                    } else {
                        throw new Error(data.message || 'Failed to save partner');
                    }
                } else {
                    window.showAlert('success', data.message || 'Partner saved successfully.', 'Success!');
                    this.close();
                    setTimeout(() => location.reload(), 500);
                }
            } catch (error) {
                console.error('Error:', error);
                window.showAlert('error', error.message || 'Failed to save partner.', 'Error');
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>