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
        class="fixed inset-0 z-[99999] az-backdrop"
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
        class="az-sheet fixed right-0 top-0 h-full w-full max-w-2xl bg-white dark:bg-gray-900 shadow-theme-xl z-[99999] overflow-y-auto"
        @click.away="close()"
    >
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 px-6 py-5 sticky top-0 bg-white/95 backdrop-blur dark:bg-gray-900/95 z-10">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="title"></h3>
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

                <!-- Transaction Message Input - Only show on create -->
                <div x-show="!editId" class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Paste Transaction Message
                    </label>
                    <div class="relative">
                        <textarea x-model="message" @input.debounce.500ms="readMessage()" rows="3" 
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"
                            placeholder="Paste the bank or M-Pesa message: amount, references and date are read from it"></textarea>
                    </div>
                    <p class="mt-1 text-xs" :class="readOk ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400'" x-show="readMsg" x-text="readMsg"></p>
                </div>

                <!-- ============ LOAN MODE: CYCLE SELECTION ============ -->
                <div x-show="mode === 'loan'" class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Apply to Loan Cycle <span class="text-red-500">*</span>
                    </label>
                    <select x-model="selectedCycleId" 
                        class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
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

                <!-- ============ WHO IS BEHIND THIS LOAN (owner, 23 Oct 2026) ============ -->
                <div x-show="mode === 'loan'" class="mb-4 rounded-2xl border border-gray-100 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                    <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">Who is behind this loan</p>
                    <p x-show="editId && opts.has_repayments" class="mb-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                        Payments on this loan are already split, so a change here would move shares already paid.
                        <a :href="opts.links_url" data-drawer class="font-medium underline" @click="close()">Change it with a preview</a>.
                    </p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Brought in by (broker)</label>
                            <select x-model.number="broker" :disabled="editId && opts.has_repayments" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
                                <option :value="0">Nobody: our own client</option>
                                <template x-for="p in opts.partners" :key="p.id"><option :value="p.id" x-text="p.name" :selected="Number(p.id) === Number(broker)"></option></template>
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="opts.own && Number(broker) === Number(opts.own.id) ? 'Their own loan: full rate, their broker share of each repayment comes back to them.' : 'The broker earns their share of the interest and penalty on every repayment.'"></p>
                        </div>
                        <div>
                            <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Funded by</span>
                            <div class="flex flex-wrap gap-2 text-sm">
                                <template x-for="[k, l] in (editId ? [['house', 'Our money'], ['line', 'A partner line']] : [['house', 'Our money'], ['line', 'A partner line'], ['new', 'New partner money']])" :key="k">
                                    <button type="button" @click="fund = k" :disabled="(k === 'line' && opts.lines.length === 0) || (editId && opts.has_repayments)"
                                        :class="fund === k ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300' : 'border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-300'"
                                        class="rounded-lg border px-3 py-2 font-medium disabled:opacity-40" x-text="l"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div x-show="fund === 'line'" class="mt-4">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Partner line</label>
                        <select x-model="lineId" :disabled="editId && opts.has_repayments" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
                            <template x-for="l in opts.lines" :key="l.id"><option :value="l.id" x-text="l.label"></option></template>
                        </select>
                    </div>
                    <div x-show="fund === 'new'" class="mt-4 space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">The partner's M-Pesa or bank message</label>
                            <textarea x-model="np.sms" @input.debounce.500ms="readPartnerSms()" rows="2" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white" placeholder="Paste the message for the money the partner sent"></textarea>
                            <p class="mt-1 text-xs" :class="np.ok ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400'" x-show="np.msg" x-text="np.msg"></p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div><label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Partner who sent it</label>
                                <select x-model="np.partner" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"><option value="">Choose…</option>
                                    <template x-for="p in opts.partners" :key="p.id"><option :value="p.id" x-text="p.name"></option></template></select></div>
                            <div><label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Amount received (KES)</label><input type="number" step="0.01" min="1" x-model="np.amount" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"></div>
                            <div><label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Reference (M-Pesa or bank)</label><input type="text" x-model="np.code" maxlength="40" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white" style="text-transform:uppercase"></div>
                            <div><label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Date received</label><input type="date" x-model="np.date" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"></div>
                            <div><span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">How long</span>
                                <div class="flex gap-2 text-sm">
                                    <button type="button" @click="np.term = 'short_term'; np.maturity = opts.maturity" :class="np.term === 'short_term' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Short term</button>
                                    <button type="button" @click="np.term = 'long_term'; np.maturity = ''" :class="np.term === 'long_term' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Long term</button>
                                </div></div>
                            <div><label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Pay back by</label><input type="date" x-model="np.maturity" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="np.term === 'short_term' ? 'Closes and pays out when its loans are repaid.' : 'Lends again what comes back until this date.'"></p></div>
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
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"
                            required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Disbursement Date <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.disburse_date" x-ref="datepicker"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"
                            placeholder="Select date" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">How the money was sent</label>
                        <select x-model="form.mode" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
                            <option value="">Select mode...</option>
                            @foreach (\App\Support\PaymentModes::MODES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                        </select>
                    </div>

                    <div x-show="!['bank_to_mpesa', 'mpesa_to_bank'].includes(form.mode)">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Transaction Reference <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.transaction" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white" placeholder="e.g., 4540EMLQ4038">
                    </div>
                    <div x-show="['bank_to_mpesa', 'mpesa_to_bank'].includes(form.mode)">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Bank Transaction Ref ID <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.bank_ref" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white" placeholder="e.g., 5690NMQS2509">
                    </div>
                    <div x-show="['bank_to_mpesa', 'mpesa_to_bank'].includes(form.mode)">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">M-Pesa code <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.mpesa_ref" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white" placeholder="e.g., UJH6OA6DRB" style="text-transform:uppercase">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Both are kept, so the bank statement and the M-Pesa statement each match.</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Payment Date (Optional)
                        </label>
                        <input type="text" x-model="form.payment_date" x-ref="paymentDatepicker"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"
                            placeholder="Select date">
                    </div>

                </div>

                <div x-show="errors.length" class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                    <template x-for="e in errors" :key="e"><p x-text="e"></p></template>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-900">
                    <button type="button" @click="close()" 
                        class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:ring-4 focus:ring-brand-500/20">
                        <span x-text="saving ? 'Saving…' : submitText"></span>
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
            bank_ref: '',
            mpesa_ref: '',
            mode: '',
            payment_date: '',
            funding_source: 'internal'
        },
        // who is behind the loan (owner, 23 Oct 2026)
        opts: { partners: [], lines: [], own: null, house: null, maturity: '', has_repayments: false, links_url: '#' },
        broker: 0,
        fund: 'house',
        lineId: '',
        np: { sms: '', msg: '', ok: false, partner: '', amount: '', code: '', date: '', term: 'short_term', maturity: '' },
        readMsg: '',
        readOk: false,
        errors: [],
        saving: false,

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

            window.azOn('set-disbursement-cycles', (event) => {
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
                    altInput: true, altFormat: 'j M Y',   // shows 23 Oct 2026, sends 2026-10-23
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
                    altInput: true, altFormat: 'j M Y',   // shows 23 Oct 2026, sends 2026-10-23
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
                        return;
                    }
                } catch (e) {
                    console.warn('Disbursement: failed to parse data-investments', e);
                }
            }

            // AJAX
            try {
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
                this.form.bank_ref = data.bank_ref || '';
                this.form.mpesa_ref = data.mpesa_ref || '';
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
                this.loadPartnerOptions();

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
                this.loadPartnerOptions();

                this.$nextTick(() => {
                    const input = this.$refs.datepicker;
                    if (input && input._flatpickr) {
                        const now = new Date();
                        input._flatpickr.setDate(now);
                        this.form.disburse_date = now;
                    } else if (!this.form.disburse_date) {
                        this.form.disburse_date = new Date().toLocaleDateString('en-CA');   // today, when no date picker is loaded
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
                bank_ref: '',
                mpesa_ref: '',
                mode: '',
                payment_date: '',
                funding_source: 'internal'
            };
            this.broker = 0; this.fund = 'house'; this.lineId = ''; this.errors = []; this.readMsg = ''; this.readOk = false;
            this.np = { sms: '', msg: '', ok: false, partner: '', amount: '', code: '', date: '', term: 'short_term', maturity: '' };
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

        async loadPartnerOptions() {
            if (!this.loanId) return;
            try {
                const res = await fetch(`/staff/loans/${this.loanId}/partner-options`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                this.opts = await res.json();
                this.np.maturity = this.opts.maturity || '';
                if (this.editId) {
                    // editing: start from what the loan has now
                    this.broker = Number(this.opts.current_broker || 0);
                    this.fund = this.opts.current_line && Number(this.opts.current_line) !== Number(this.opts.house) ? 'line' : 'house';
                    this.lineId = this.opts.current_line || (this.opts.lines.length ? this.opts.lines[0].id : '');
                } else {
                    this.broker = Number(this.opts.broker || 0);
                    this.lineId = this.opts.lines.length ? this.opts.lines[0].id : '';
                }
            } catch (e) { /* the modal still records the money as before */ }
        },

        async readSms(text) {
            const res = await fetch('/staff/mpesa/read-sms', { method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ sms: text }) });
            return res.ok ? res.json() : { ok: false };
        },

        // the payout message: bank and M-Pesa both read on the server, with both references when the message has them
        async readMessage() {
            const text = this.message.trim();
            if (!text) { this.readMsg = ''; return; }
            let body = { ok: false };
            try { body = await this.readSms(text); } catch (e) {}
            if (!body.ok) { this.autoParseMessage(); this.readOk = !!this.parsedData.amount; this.readMsg = this.readOk ? 'Read from the message. Check the amount and reference.' : 'Could not read that message. Type the details in.'; return; }
            const m = body.sms;
            this.form.amount = m.amount;
            this.form.mode = m.mode || this.form.mode;
            this.form.bank_ref = m.bank_ref || '';
            this.form.mpesa_ref = m.mpesa_ref || '';
            this.form.transaction = m.bank_ref || m.mpesa_ref || m.code;
            if (m.date) {
                this.form.disburse_date = m.date; this.form.payment_date = m.date;
                this.$nextTick(() => { this.$refs.datepicker?._flatpickr?.setDate(m.date); this.$refs.paymentDatepicker?._flatpickr?.setDate(m.date); });
            }
            this.readOk = !(body.seen || []).length;
            this.readMsg = (body.seen || []).length ? 'Already recorded: ' + body.seen.join(', ') + '. Check you are not entering it twice.'
                : 'Read: ' + body.mode_label + ' · KES ' + Number(m.amount).toLocaleString('en-KE', { minimumFractionDigits: 2 })
                  + (m.bank_ref ? ' · bank ' + m.bank_ref : '') + (m.mpesa_ref ? ' · M-Pesa ' + m.mpesa_ref : '') + (m.party ? ' · ' + m.party : '');
        },

        async readPartnerSms() {
            const text = this.np.sms.trim();
            if (!text) { this.np.msg = ''; return; }
            let body = { ok: false };
            try { body = await this.readSms(text); } catch (e) {}
            if (!body.ok) { this.np.ok = false; this.np.msg = body.message || 'Could not read it. Type the reference and amount in.'; return; }
            const m = body.sms;
            this.np.amount = m.amount; this.np.code = m.code; if (m.date) this.np.date = m.date;
            this.np.ok = !body.used;
            this.np.msg = body.used ? m.code + ' has already been recorded as partner money.' : 'Read: ' + m.code + ' · KES ' + Number(m.amount).toLocaleString('en-KE', { minimumFractionDigits: 2 }) + (m.party ? ' · ' + m.party : '') + '. Choose the partner.';
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
            if (this.saving) return;
            this.errors = [];
            this.saving = true;
            try {
                const formData = new FormData();
                const action = this.editId ? `/disbursements/${this.editId}` : '/disbursements';
                const both = ['bank_to_mpesa', 'mpesa_to_bank'].includes(this.form.mode);

                formData.append('_method', this.method);
                formData.append('amount', this.form.amount);
                formData.append('disburse_date', this.formatDate(this.form.disburse_date));
                formData.append('transaction', both ? (this.form.bank_ref || this.form.mpesa_ref || '') : (this.form.transaction || ''));
                formData.append('bank_ref', this.form.bank_ref || '');
                formData.append('mpesa_ref', this.form.mpesa_ref || '');
                formData.append('mode', this.form.mode || '');
                formData.append('payment_date', this.formatDate(this.form.payment_date) || '');
                formData.append('loan_id', this.loanId);
                formData.append('loan_cycle_id', this.selectedCycleId);

                if (this.editId) {
                    // broker and funding: changed straight away before any payment, refused (with a link to the preview) after
                    if (!this.opts.has_repayments && this.opts.partners.length) {
                        formData.append('broker_partner_id', Number(this.broker) || 0);
                        formData.append('funding_mode', this.fund);
                        formData.append('investment_id', this.fund === 'line' ? (this.lineId || '') : (this.opts.house || ''));
                    } else {
                        formData.append('funding_source', this.form.funding_source || 'internal');
                        if (this.selectedInvestmentId) formData.append('investment_id', this.selectedInvestmentId);
                    }
                } else {
                    // who is behind the loan (owner, 23 Oct 2026)
                    formData.append('broker_partner_id', Number(this.broker) || 0);
                    formData.append('funding_mode', this.fund);
                    if (this.fund === 'house' && this.opts.house) formData.append('investment_id', this.opts.house);
                    if (this.fund === 'line') formData.append('investment_id', this.lineId || '');
                    if (this.fund === 'new') {
                        formData.append('new_partner_id', this.np.partner || '');
                        formData.append('new_amount', this.np.amount || '');
                        formData.append('new_code', this.np.code || '');
                        formData.append('new_date', this.np.date || '');
                        formData.append('new_term', this.np.term);
                        formData.append('new_maturity', this.np.maturity || '');
                    }
                }

                const response = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json().catch(() => ({}));

                if (response.ok) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: this.editId ? 'Disbursement updated.' : 'Disbursement recorded.' } }));
                    this.close();
                    // the loan's cards, cycles and tables refresh in the background (owner, 23 Oct 2026)
                    setTimeout(() => (window.azRefresh ? window.azRefresh() : location.reload()), 300);
                } else {
                    this.errors = data.errors ? Object.values(data.errors).flat() : [data.message || 'Something went wrong.'];
                }
            } catch (error) {
                this.errors = ['Network error. Nothing was saved. Please try again.'];
            } finally {
                this.saving = false;
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