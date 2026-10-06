{{-- resources/views/partials/modal/disbursement-create-modal.blade.php --}}
<div 
    x-data="disbursementModal()" 
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
        class="fixed right-0 top-0 h-full w-full max-w-2xl bg-white dark:bg-gray-900 shadow-2xl z-[99999] overflow-y-auto"
        @click.away="close()"
    >
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 p-4 sticky top-0 bg-white dark:bg-gray-900 z-10">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white" x-text="title"></h3>
                <button @click="close()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm()" class="flex-1 overflow-y-auto p-6">
                @csrf
                <input type="hidden" name="_method" x-model="method">
                <input type="hidden" name="id" x-model="editId">

                <!-- ============ MODE TOGGLE (only on create) ============ -->
                <div x-show="!editId" class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Disbursement Type <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 p-1 bg-gray-100 dark:bg-gray-800 rounded-lg">
                        <button type="button" @click="setMode('loan')"
                            :class="mode === 'loan' ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-600 dark:text-gray-400'"
                            class="py-2 px-4 text-sm rounded-md transition-all">
                            Loan Disbursement
                        </button>
                        <button type="button" @click="setMode('investment')"
                            :class="mode === 'investment' ? 'bg-white dark:bg-gray-700 shadow-sm text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-600 dark:text-gray-400'"
                            class="py-2 px-4 text-sm rounded-md transition-all">
                            Investment Disbursement
                        </button>
                    </div>
                </div>

                <!-- Transaction Message Input - Only show on create -->
                <div x-show="!editId" class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Paste Transaction Message
                    </label>
                    <div class="relative">
                        <textarea x-model="message" @input="autoParseMessage()" rows="3" 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            placeholder="Paste transaction message here... It will auto-parse!"></textarea>
                    </div>
                </div>

                <!-- ============ LOAN MODE: CYCLE SELECTION ============ -->
                <div x-show="mode === 'loan'" class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Apply to Loan Cycle <span class="text-red-500">*</span>
                    </label>
                    <select x-model="selectedCycleId" 
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">-- Select Cycle --</option>
                        <template x-for="cycle in cycles" :key="cycle.id">
                            <option :value="cycle.id" x-text="'Cycle #' + cycle.cycle_number + ' - Balance: KES ' + Number(cycle.new_balance).toFixed(2) + ' (' + cycle.status + ')'"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span x-show="selectedCycleId" x-text="'Selected cycle #' + getSelectedCycleNumber()"></span>
                        <span x-show="!selectedCycleId && cycles.length === 0">Loading cycles...</span>
                        <span x-show="!selectedCycleId && cycles.length > 0">Please select the cycle this disbursement applies to</span>
                    </p>
                </div>

                <!-- ============ INVESTMENT SELECTION (shown for investment mode OR when funding_source is partner/mixed) ============ -->
                <div x-show="mode === 'investment' || form.funding_source === 'partner' || form.funding_source === 'mixed'"
                     class="mb-4 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Investment
                            <span class="text-red-500" x-show="mode === 'investment'">*</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2" x-show="mode === 'loan'">
                                (Optional — link this disbursement to an investment)
                            </span>
                        </label>
                        <select x-model="selectedInvestmentId" @change="onInvestmentChange()"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="">-- Select Investment --</option>
                            <template x-for="inv in investments" :key="inv.id">
                                <option :value="Number(inv.id)" x-text="inv.name + ' (' + inv.facility_type_label + ')'"></option>
                            </template>
                        </select>
                        <p x-show="investments.length === 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Loading investments...
                        </p>
                    </div>

                    <!-- Auto-populated partner info -->
                    <div x-show="selectedInvestmentId && currentInvestmentPartners.length > 0"
                         class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                        <p class="text-xs font-medium text-blue-800 dark:text-blue-300 mb-1">Funding Partners</p>
                        <div class="flex flex-wrap gap-1">
                            <template x-for="p in currentInvestmentPartners" :key="p.id">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                    <span x-text="p.name"></span>
                                    <span x-show="p.amount_committed" class="ml-1 text-blue-600 dark:text-blue-300"
                                          x-text="'(KES ' + Number(p.amount_committed).toLocaleString() + ')'"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Remaining amount preview -->
                    <div x-show="selectedInvestmentId && currentInvestment"
                         class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Committed</p>
                                <p class="font-semibold text-gray-800 dark:text-white"
                                   x-text="'KES ' + Number(currentInvestment?.committed_amount || 0).toLocaleString()"></p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Disbursed</p>
                                <p class="font-semibold text-gray-800 dark:text-white"
                                   x-text="'KES ' + Number(currentInvestment?.disbursed_amount || 0).toLocaleString()"></p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Remaining</p>
                                <p class="font-semibold text-green-700 dark:text-green-400"
                                   x-text="'KES ' + Number(currentInvestment?.remaining_amount || 0).toLocaleString()"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ FORM FIELDS ============ -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Amount (KES) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.amount" step="0.01" min="0"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Disbursement Date <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.disburse_date" x-ref="datepicker"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            placeholder="Select date" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Transaction Reference <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.transaction" 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            placeholder="e.g., 4540EMLQ4038" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Payment Mode
                        </label>
                        <select x-model="form.mode" 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="">Select mode...</option>
                            <option value="mpesa">M-PESA</option>
                            <option value="pesalink">Pesalink</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Payment Date (Optional)
                        </label>
                        <input type="text" x-model="form.payment_date" x-ref="paymentDatepicker"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            placeholder="Select date">
                    </div>

                    <!-- Funding source (auto-detected but user can override) -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Funding Source
                        </label>
                        <select x-model="form.funding_source" 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="internal">Internal Funds</option>
                            <option value="partner">Partner Funds</option>
                            <option value="mixed">Mixed Funds</option>
                        </select>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-900">
                    <button type="button" @click="close()" 
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800">
                        <span x-text="submitText"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function disbursementModal() {
    return {
        open: false,
        title: 'Add Disbursement',
        submitText: 'Create Disbursement',
        method: 'POST',
        editId: null,

        // Mode: 'loan' | 'investment'
        mode: 'loan',

        // Loan fields
        loanId: null,
        selectedCycleId: null,
        cycles: [],
        cyclesLoaded: false,

        // Investment fields
        selectedInvestmentId: null,
        investments: [],
        currentInvestmentPartners: [],
        investmentsLoaded: false,

        message: '',
        parsedData: {},
        form: {
            amount: '',
            disburse_date: '',
            transaction: '',
            mode: '',
            payment_date: '',
            funding_source: 'internal'
        },

        get currentInvestment() {
            return this.investments.find(i => Number(i.id) === Number(this.selectedInvestmentId)) || null;
        },

        init() {
            this.$watch('open', (value) => {
                if (value) {
                    this.$nextTick(() => {
                        this.initDatepickers();
                        this.loadInvestments();
                        if (this.mode === 'loan') {
                            this.loadCyclesWithFallback();
                        }
                    });
                }
            });

            window.openDisbursementModal = (loanId, data) => this.openModal(loanId, data);
            window.openInvestmentDisbursementModal = (data) => this.openInvestmentModal(data);
            window.closeDisbursementModal = () => this.close();

            window.addEventListener('set-disbursement-cycles', (event) => {
                if (event.detail && event.detail.cycles) {
                    this.cycles = event.detail.cycles;
                    this.cyclesLoaded = true;
                    this.autoSelectCycle();
                }
            });
        },

        initDatepickers() {
            const input = this.$refs.datepicker;
            if (input && typeof flatpickr !== 'undefined') {
                if (input._flatpickr) input._flatpickr.destroy();
                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    locale: { firstDayOfWeek: 1 },
                    onChange: (selectedDates) => {
                        if (selectedDates.length > 0) this.form.disburse_date = selectedDates[0];
                    }
                });
            }

            const paymentInput = this.$refs.paymentDatepicker;
            if (paymentInput && typeof flatpickr !== 'undefined') {
                if (paymentInput._flatpickr) paymentInput._flatpickr.destroy();
                flatpickr(paymentInput, {
                    dateFormat: 'Y-m-d',
                    locale: { firstDayOfWeek: 1 },
                    onChange: (selectedDates) => {
                        if (selectedDates.length > 0) this.form.payment_date = selectedDates[0];
                    }
                });
            }
        },

        setMode(newMode) {
            this.mode = newMode;
            this.loadInvestments();
            if (newMode === 'loan') {
                this.loadCyclesWithFallback();
            }
        },

        loadCyclesWithFallback() {
            if (window.loanData && window.loanData.cycles && window.loanData.cycles.length > 0) {
                this.cycles = window.loanData.cycles;
                this.cyclesLoaded = true;
                this.autoSelectCycle();
                return;
            }

            const cyclesDataEl = document.querySelector('[data-cycles]');
            if (cyclesDataEl) {
                try {
                    const cyclesData = JSON.parse(cyclesDataEl.dataset.cycles);
                    if (Array.isArray(cyclesData) && cyclesData.length > 0) {
                        this.cycles = cyclesData;
                        this.cyclesLoaded = true;
                        this.autoSelectCycle();
                        return;
                    }
                } catch (e) {
                    console.warn('failed to parse data-cycles', e);
                }
            }

            this.loadCyclesAjax();
        },

        async loadCyclesAjax() {
            if (!this.loanId) return;

            try {
                const response = await fetch(`/loans/${this.loanId}/cycles`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.cycles = data.cycles;
                    this.cyclesLoaded = true;
                    this.autoSelectCycle();
                }
            } catch (error) {
                console.error('Error loading cycles:', error);
            }
        },

        autoSelectCycle() {
            if (!this.cycles || this.cycles.length === 0) return;
            const activeCycle = this.cycles.find(c => c.status === 'active');
            if (activeCycle) {
                this.selectedCycleId = activeCycle.id;
            } else {
                const sorted = [...this.cycles].sort((a, b) => b.cycle_number - a.cycle_number);
                this.selectedCycleId = sorted[0].id;
            }
        },

        /**
         * Load investments.
         * @param {boolean} force - When true, bypass cache (used for edit).
         */
        async loadInvestments(force = false) {
            if (!force && this.investmentsLoaded && this.investments.length > 0) {
                console.log('Disbursement: investments already cached', this.investments.length);
                return;
            }

            // Page data
            if (window.investmentsData && Array.isArray(window.investmentsData)) {
                this.investments = window.investmentsData.map(inv => ({
                    ...inv,
                    id: Number(inv.id),
                    facility_type_label: inv.facility_type === 'short_term' ? 'Short-Term' : 'Long-Term',
                }));
                this.investmentsLoaded = true;
                console.log('Disbursement: investments loaded from window.investmentsData', this.investments.length);
                return;
            }

            // data-investments attribute
            const invDataEl = document.querySelector('[data-investments]');
            if (invDataEl) {
                try {
                    const invData = JSON.parse(invDataEl.dataset.investments);
                    if (Array.isArray(invData) && invData.length > 0) {
                        this.investments = invData.map(inv => ({
                            ...inv,
                            id: Number(inv.id),
                            facility_type_label: inv.facility_type === 'short_term' ? 'Short-Term' : 'Long-Term',
                        }));
                        this.investmentsLoaded = true;
                        console.log('Disbursement: investments loaded from data-investments', this.investments.length);
                        return;
                    }
                } catch (e) {
                    console.warn('Disbursement: failed to parse data-investments', e);
                }
            }

            // AJAX
            try {
                console.log('Disbursement: fetching investments via AJAX');
                const response = await fetch('/investments/data', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) {
                    console.error('Disbursement: investments fetch failed', response.status);
                    return;
                }

                const data = await response.json();
                const list = Array.isArray(data) ? data : (data.data || []);
                if (!Array.isArray(list)) {
                    console.error('Disbursement: investments response is not an array');
                    return;
                }

                this.investments = list.map(inv => ({
                    ...inv,
                    id: Number(inv.id),
                    facility_type_label: inv.facility_type === 'short_term' ? 'Short-Term' : 'Long-Term',
                }));
                this.investmentsLoaded = true;
                console.log('Disbursement: investments loaded via AJAX', this.investments.length);

            } catch (error) {
                console.error('Disbursement: error loading investments', error);
            }
        },

        onInvestmentChange() {
            const inv = this.currentInvestment;
            if (!inv) {
                this.currentInvestmentPartners = [];
                return;
            }

            this.currentInvestmentPartners = (inv.funding_partner_models || []);

            if (this.form.funding_source !== 'mixed') {
                this.form.funding_source = this.currentInvestmentPartners.length > 0
                    ? 'partner'
                    : 'internal';
            }
        },

        getSelectedCycleNumber() {
            const cycle = this.cycles.find(c => c.id === this.selectedCycleId);
            return cycle ? cycle.cycle_number : '';
        },

        getSelectedCycleBalance() {
            const cycle = this.cycles.find(c => c.id === this.selectedCycleId);
            return cycle ? Number(cycle.new_balance) : 0;
        },

        async openModal(loanId = null, data = null) {
            this.resetForm();
            this.open = true;
            this.mode = 'loan';
            document.body.style.overflow = 'hidden';

            if (loanId) {
                this.loanId = loanId;
            } else if (data && data.loan_id) {
                this.loanId = data.loan_id;
            } else if (window.loanData && window.loanData.id) {
                this.loanId = window.loanData.id;
            }

            if (data) {
                this.title = 'Edit Disbursement';
                this.submitText = 'Update Disbursement';
                this.method = 'PUT';
                this.editId = data.id;
                this.loanId = data.loan_id || this.loanId;
                this.selectedCycleId = data.loan_cycle_id || null;
                this.selectedInvestmentId = data.investment_id ? Number(data.investment_id) : null;
                this.form.amount = data.amount;
                this.form.transaction = data.transaction || '';
                this.form.mode = data.mode || '';
                this.form.funding_source = data.funding_source || 'internal';

                if (data.disburse_date) {
                    this.form.disburse_date = data.disburse_date;
                    this.$nextTick(() => {
                        const input = this.$refs.datepicker;
                        if (input && input._flatpickr) input._flatpickr.setDate(data.disburse_date);
                    });
                }

                if (data.payment_date) {
                    this.form.payment_date = data.payment_date;
                    this.$nextTick(() => {
                        const input = this.$refs.paymentDatepicker;
                        if (input && input._flatpickr) input._flatpickr.setDate(data.payment_date);
                    });
                }

                if (data.investment_id && !data.loan_id) {
                    this.mode = 'investment';
                }

                // ============ FIX: FORCE refresh investments on edit ============
                await this.loadInvestments(true);

                // ============ FIX: Re-apply selection after options rendered ============
                this.$nextTick(() => {
                    this.$nextTick(() => {
                        if (this.selectedInvestmentId) {
                            // Re-assert numeric form
                            this.selectedInvestmentId = Number(this.selectedInvestmentId);
                            // Trigger partner preview if applicable
                            this.onInvestmentChange();
                        }
                    });
                });
            } else {
                this.title = 'Add Disbursement';
                this.submitText = 'Create Disbursement';
                this.method = 'POST';
                this.editId = null;

                this.$nextTick(() => {
                    const input = this.$refs.datepicker;
                    if (input && input._flatpickr) {
                        const now = new Date();
                        input._flatpickr.setDate(now);
                        this.form.disburse_date = now;
                    }
                });
            }

            this.$nextTick(() => {
                this.loadCyclesWithFallback();
            });
        },

        async openInvestmentModal(data = null) {
            this.resetForm();
            this.open = true;
            this.mode = 'investment';
            document.body.style.overflow = 'hidden';
            this.title = 'Add Investment Disbursement';
            this.submitText = 'Create Disbursement';
            this.method = 'POST';
            this.editId = null;

            if (data) {
                this.selectedInvestmentId = data.investment_id ? Number(data.investment_id) : null;
                this.form.amount = data.amount || '';
            }

            this.$nextTick(async () => {
                const input = this.$refs.datepicker;
                if (input && input._flatpickr) {
                    const now = new Date();
                    input._flatpickr.setDate(now);
                    this.form.disburse_date = now;
                }
                await this.loadInvestments();
                if (this.selectedInvestmentId) {
                    this.onInvestmentChange();
                }
            });
        },

        close() {
            this.open = false;
            document.body.style.overflow = '';
            this.resetForm();
        },

        resetForm() {
            this.form = {
                amount: '',
                disburse_date: '',
                transaction: '',
                mode: '',
                payment_date: '',
                funding_source: 'internal'
            };
            this.message = '';
            this.parsedData = {};
            this.editId = null;
            this.selectedCycleId = null;
            this.selectedInvestmentId = null;
            this.cycles = [];
            this.cyclesLoaded = false;
            this.currentInvestmentPartners = [];
            this.mode = 'loan';
        },

        autoParseMessage() {
            const message = this.message.trim();
            if (!message) {
                this.parsedData = {};
                return;
            }

            const patterns = [
                {
                    regex: /Bank to M-PESA transfer of KES ([\d,]+\.?\d*)\s*to\s*(\d+)\s*-\s*[^-]+?\s*(?:successfully processed\. Transaction Ref ID:\s*([A-Z0-9]+)\.\s*M-PESA Ref ID:\s*([A-Z0-9]+))?/i,
                    extract: (match) => ({
                        amount: parseFloat(match[1].replace(/,/g, '')),
                        transaction: match[3] || match[4] || '',
                        mode: 'mpesa',
                        date: new Date()
                    })
                },
                {
                    regex: /([A-Z0-9]+)\s+Confirmed\.\s*KES\s*([\d,]+\.?\d*)\s+received from\s+[^t]+?\s+tel\s+\d+\s+for account\s+\d+\s+on\s+(\d{2}\/\d{2}\/\d{2})\s+at\s+(\d{2}:\d{2}\s*(?:AM|PM))/i,
                    extract: (match) => ({
                        transaction: match[1],
                        amount: parseFloat(match[2].replace(/,/g, '')),
                        date: this.parseDate(match[3] + ' ' + match[4]),
                        mode: 'mpesa'
                    })
                },
                {
                    regex: /Pesalink transfer of KES ([\d,]+\.?\d*)\s+to\s+[A-Z\s]+\s+A\/c\s+[\d-]+\s+on\s+(\d{2}\/\d{2}\/\d{4})\s+([\d:]+)\s+processed successfully\.\s+Transaction Ref ID:\s+([A-Z0-9]+)/i,
                    extract: (match) => ({
                        amount: parseFloat(match[1].replace(/,/g, '')),
                        date: this.parseDate(match[2] + ' ' + match[3]),
                        transaction: match[4],
                        mode: 'pesalink'
                    })
                },
                {
                    regex: /Payment of KES ([\d,]+\.?\d*)\s+to\s+\d+\s+on\s+(\d{2}\/\d{2}\/\d{4})\s+([\d:]+)\s+processed successfully\.\s+Transaction Ref ID:\s+([A-Z0-9]+)/i,
                    extract: (match) => ({
                        amount: parseFloat(match[1].replace(/,/g, '')),
                        date: this.parseDate(match[2] + ' ' + match[3]),
                        transaction: match[4],
                        mode: 'mpesa'
                    })
                },
                {
                    regex: /(?:Ref|REF|Transaction|TRANSACTION|ID|No|NO)[:\s]+([A-Z0-9]{6,})/i,
                    extract: (match) => ({ transaction: match[1] })
                }
            ];

            let parsed = {};
            for (const pattern of patterns) {
                const match = message.match(pattern.regex);
                if (match) {
                    const result = pattern.extract(match);
                    parsed = { ...parsed, ...result };
                    if (result.amount && result.transaction) break;
                }
            }

            if (!parsed.amount && !parsed.transaction) {
                this.parsedData = {};
                return;
            }

            if (parsed.amount) this.form.amount = parsed.amount;
            if (parsed.transaction) this.form.transaction = parsed.transaction;
            if (parsed.mode) this.form.mode = parsed.mode;

            if (parsed.date) {
                this.form.disburse_date = parsed.date;
                this.form.payment_date = parsed.date;
                this.$nextTick(() => {
                    const input = this.$refs.datepicker;
                    if (input && input._flatpickr) input._flatpickr.setDate(parsed.date);
                    const paymentInput = this.$refs.paymentDatepicker;
                    if (paymentInput && paymentInput._flatpickr) paymentInput._flatpickr.setDate(parsed.date);
                });
            }

            if (parsed.date) {
                const d = new Date(parsed.date);
                parsed.date_display = d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            }

            this.parsedData = parsed;
        },

        parseDate(dateStr) {
            try {
                const cleanStr = dateStr.replace(/\s+/g, ' ');
                let date = new Date(cleanStr);
                if (isNaN(date.getTime())) {
                    const parts = cleanStr.match(/(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})/);
                    if (parts) {
                        const [_, day, month, year, hour, min] = parts;
                        date = new Date(year, month - 1, day, hour, min);
                    }
                }
                return date;
            } catch (e) {
                return new Date();
            }
        },

        async submitForm() {
            if (this.mode === 'loan' && !this.selectedCycleId) {
                window.showAlert('error', 'Error!', 'Please select a loan cycle.');
                return;
            }
            if (this.mode === 'investment' && !this.selectedInvestmentId) {
                window.showAlert('error', 'Error!', 'Please select an investment.');
                return;
            }

            try {
                const formData = new FormData();
                const action = this.editId ? `/disbursements/${this.editId}` : '/disbursements';

                formData.append('_method', this.method);
                formData.append('amount', this.form.amount);
                formData.append('disburse_date', this.formatDate(this.form.disburse_date));
                formData.append('transaction', this.form.transaction || '');
                formData.append('mode', this.form.mode || '');
                formData.append('payment_date', this.formatDate(this.form.payment_date) || '');
                formData.append('funding_source', this.form.funding_source || 'internal');

                if (this.mode === 'loan') {
                    formData.append('loan_id', this.loanId);
                    formData.append('loan_cycle_id', this.selectedCycleId);
                    if (this.selectedInvestmentId) {
                        formData.append('investment_id', this.selectedInvestmentId);
                    }
                } else {
                    formData.append('investment_id', this.selectedInvestmentId);
                }

                const response = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    window.showAlert('success', 'Success!', this.editId ? 'Disbursement updated successfully!' : 'Disbursement created successfully!');
                    this.close();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    window.showAlert('error', 'Error!', data.message || 'Something went wrong.');
                }
            } catch (error) {
                window.showAlert('error', 'Error!', 'Network error. Please try again.');
            }
        },

        formatDate(date) {
            if (!date) return '';
            const d = new Date(date);
            if (isNaN(d.getTime())) return '';
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    }
}
</script>