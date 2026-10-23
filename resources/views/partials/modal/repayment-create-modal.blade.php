{{-- resources/views/partials/modal/repayment-create-modal.blade.php --}}
<div 
    x-data="repaymentModal()" 
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
                <input type="hidden" name="loan_id" x-model="loanId">
                <input type="hidden" name="loan_cycle_id" x-model="selectedCycleId">

                <!-- Transaction Message Input - Only show on create -->
                <div x-show="!editId" class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Paste Transaction Message
                    </label>
                    <div class="relative">
                        <textarea x-model="message" @input.debounce.500ms="readMessage()" rows="3" 
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"
                            placeholder="Paste transaction message here... It will auto-parse!"></textarea>
                    </div>
                    <p class="mt-1 text-xs" :class="readOk ? 'text-green-700 dark:text-green-400' : (readMsg ? 'text-amber-700 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400')"
                       x-text="readMsg || 'M-Pesa, bank to M-Pesa (both references), Pesalink and bank payment messages are read.'"></p>
                </div>

                <div x-show="!editId" class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

                <!-- Loan Cycle Selection -->
                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Apply to Loan Cycle <span class="text-red-500">*</span>
                    </label>
                    <select x-model="selectedCycleId" 
                        class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"
                        required>
                        <option value="">-- Select Cycle --</option>
                        <template x-for="cycle in cycles" :key="cycle.id">
                            <option :value="cycle.id" x-text="'Cycle #' + cycle.cycle_number + ' - Balance: KES ' + Number(cycle.new_balance).toFixed(2) + ' (' + cycle.status + ')'"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span x-show="selectedCycleId" x-text="'Selected cycle #' + getSelectedCycleNumber()"></span>
                        <span x-show="!selectedCycleId">Please select the cycle this repayment applies to</span>
                    </p>
                </div>

                <!-- Form Fields -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Amount (KES) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.amount" step="0.01" min="0"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"
                            required>
                        <p class="mt-1 text-xs text-gray-400" x-show="selectedCycleId">
                            Cycle balance: KES <span x-text="Number(getSelectedCycleBalance()).toFixed(2)"></span>
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Repayment Date <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.repayment_date" x-ref="datepicker"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"
                            placeholder="Select date" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">How the money came</label>
                        <select x-model="form.mode" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
                            <option value="">Select mode...</option>
                            @foreach (\App\Support\PaymentModes::MODES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div x-show="!['bank_to_mpesa', 'mpesa_to_bank'].includes(form.mode)">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Transaction Reference <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.transaction" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white" placeholder="e.g., UFAAL79NHH or 4540EMLQ4038">
                    </div>
                    <div x-show="['bank_to_mpesa', 'mpesa_to_bank'].includes(form.mode)">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">M-Pesa code <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.mpesa_ref" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white" placeholder="e.g., UJH6OA6DRB" style="text-transform:uppercase">
                    </div>
                    <div x-show="['bank_to_mpesa', 'mpesa_to_bank'].includes(form.mode)">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Bank Transaction Ref ID <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.bank_ref" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white" placeholder="e.g., 5690NMQS2509">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Both are kept, so the M-Pesa and bank statements each match.</p>
                    </div>
                </div>

                <!-- Parsed Data Preview -->
                <div x-show="Object.keys(parsedData).length > 0 && !editId" class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                    <h4 class="text-sm font-semibold text-green-800 dark:text-green-300"><x-ui.icon name="check" class="inline-block size-4 align-[-3px]" /> Parsed from message</h4>
                    <div class="mt-1 text-xs text-green-700 dark:text-green-400">
                        <ul class="list-disc pl-4 space-y-1">
                            <template x-for="(value, key) in parsedData" :key="key">
                                <li x-show="value">
                                    <strong x-text="String(key).replace(/_/g, ' ').toUpperCase() + ':'"></strong>
                                    <span x-text="value"></span>
                                </li>
                            </template>
                        </ul>
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
                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:ring-4 focus:ring-green-300 dark:focus:ring-green-800">
                        <span x-text="saving ? 'Saving…' : submitText"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function repaymentModal() {
    return {
        open: false,
        title: 'Add Repayment',
        submitText: 'Create Repayment',
        method: 'POST',
        editId: null,
        loanId: null,
        selectedCycleId: null,
        cycles: [],
        message: '',
        parsedData: {},
        form: {
            amount: '',
            repayment_date: '',
            transaction: '',
            bank_ref: '',
            mpesa_ref: '',
            mode: ''
        },
        readMsg: '',
        readOk: false,
        errors: [],
        saving: false,

        init() {
            this.$watch('open', (value) => {
                if (value) {
                    this.$nextTick(() => {
                        this.initDatepicker();
                        // Load cycles from the page data instead of AJAX
                        this.loadCyclesFromPage();
                    });
                }
            });

            window.openRepaymentModal = (loanId, data) => this.openModal(loanId, data);
            window.closeRepaymentModal = () => this.close();
            
            // Listen for cycle data from the parent page
            window.azOn('set-cycles-data', (event) => {
                if (event.detail && event.detail.cycles) {
                    this.cycles = event.detail.cycles;
                    // Auto-select the active cycle
                    const activeCycle = this.cycles.find(c => c.status === 'active');
                    if (activeCycle) {
                        this.selectedCycleId = activeCycle.id;
                    } else if (this.cycles.length > 0) {
                        this.selectedCycleId = this.cycles[this.cycles.length - 1].id;
                    }
                }
            });
        },

        initDatepicker() {
            const input = this.$refs.datepicker;
            if (input && typeof flatpickr !== 'undefined') {
                if (input._flatpickr) {
                    input._flatpickr.destroy();
                }
                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    altInput: true, altFormat: 'j M Y',   // shows 23 Oct 2026, sends 2026-10-23
                    locale: { firstDayOfWeek: 1 },
                    onChange: (selectedDates) => {
                        if (selectedDates.length > 0) {
                            this.form.repayment_date = selectedDates[0];
                        }
                    }
                });
            }
        },

        // ============ FIX: Load cycles from page data ============
        loadCyclesFromPage() {
            // Try to get cycles from the page's loan data
            if (window.loanData && window.loanData.cycles) {
                this.cycles = window.loanData.cycles;
                // Auto-select the active cycle
                const activeCycle = this.cycles.find(c => c.status === 'active');
                if (activeCycle) {
                    this.selectedCycleId = activeCycle.id;
                } else if (this.cycles.length > 0) {
                    this.selectedCycleId = this.cycles[this.cycles.length - 1].id;
                }
                return;
            }

            // If not found, try to get from a data attribute on the page
            const cyclesDataEl = document.querySelector('[data-cycles]');
            if (cyclesDataEl) {
                try {
                    const cyclesData = JSON.parse(cyclesDataEl.dataset.cycles);
                    this.cycles = cyclesData;
                    const activeCycle = this.cycles.find(c => c.status === 'active');
                    if (activeCycle) {
                        this.selectedCycleId = activeCycle.id;
                    } else if (this.cycles.length > 0) {
                        this.selectedCycleId = this.cycles[this.cycles.length - 1].id;
                    }
                    return;
                } catch (e) {
                    console.warn('Failed to parse cycles data:', e);
                }
            }

            // Fallback: try AJAX if page data is not available
            this.loadCycles();
        },

        // ============ FIX: Load cycles via AJAX (fallback) ============
        async loadCycles() {
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
                    // Auto-select the active cycle
                    const activeCycle = this.cycles.find(c => c.status === 'active');
                    if (activeCycle) {
                        this.selectedCycleId = activeCycle.id;
                    } else if (this.cycles.length > 0) {
                        this.selectedCycleId = this.cycles[this.cycles.length - 1].id;
                    }
                }
            } catch (error) {
                console.error('Error loading cycles:', error);
            }
        },

        getSelectedCycleNumber() {
            const cycle = this.cycles.find(c => c.id === this.selectedCycleId);
            return cycle ? cycle.cycle_number : '';
        },

        getSelectedCycleBalance() {
            const cycle = this.cycles.find(c => c.id === this.selectedCycleId);
            return cycle ? cycle.new_balance : 0;
        },

        openModal(loanId = null, data = null) {
            this.resetForm();
            this.open = true;
            document.body.style.overflow = 'hidden';
            this.loanId = loanId || (data ? data.loan_id : null);

            if (data) {
                this.title = 'Edit Repayment';
                this.submitText = 'Update Repayment';
                this.method = 'PUT';
                this.editId = data.id;
                this.loanId = data.loan_id;
                this.selectedCycleId = data.loan_cycle_id || null;
                this.form.amount = data.amount;
                this.form.transaction = data.transaction || '';
                this.form.bank_ref = data.bank_ref || '';
                this.form.mpesa_ref = data.mpesa_ref || '';
                this.form.mode = data.mode || '';
                
                if (data.repayment_date) {
                    this.form.repayment_date = data.repayment_date;
                    this.$nextTick(() => {
                        const input = this.$refs.datepicker;
                        if (input && input._flatpickr) {
                            input._flatpickr.setDate(data.repayment_date);
                        }
                    });
                }
            } else {
                this.title = 'Add Repayment';
                this.submitText = 'Create Repayment';
                this.method = 'POST';
                this.editId = null;
                
                // Load cycles when modal opens
                this.loadCyclesFromPage();
                
                this.$nextTick(() => {
                    const input = this.$refs.datepicker;
                    if (input && input._flatpickr) {
                        const now = new Date();
                        input._flatpickr.setDate(now);
                        this.form.repayment_date = now;
                    } else if (!this.form.repayment_date) {
                        this.form.repayment_date = new Date().toLocaleDateString('en-CA');   // today, when no date picker is loaded
                    }
                });
            }
        },

        close() {
            this.open = false;
            document.body.style.overflow = '';
            this.resetForm();
        },

        resetForm() {
            this.form = {
                amount: '',
                repayment_date: '',
                transaction: '',
                bank_ref: '',
                mpesa_ref: '',
                mode: ''
            };
            this.errors = []; this.readMsg = ''; this.readOk = false;
            this.message = '';
            this.parsedData = {};
            this.editId = null;
            // Don't reset cycles and selectedCycleId on close
        },

        autoParseMessage() {
            const message = this.message.trim();
            if (!message) {
                this.parsedData = {};
                return;
            }

            const patterns = [
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
                    regex: /Bank to M-PESA transfer of KES ([\d,]+\.?\d*)\s*to\s*\d+\s*-\s*[^-]+?\s*(?:successfully processed\. Transaction Ref ID:\s*([A-Z0-9]+)\.\s*M-PESA Ref ID:\s*([A-Z0-9]+))?/i,
                    extract: (match) => ({
                        amount: parseFloat(match[1].replace(/,/g, '')),
                        transaction: match[2] || match[3] || '',
                        mode: 'mpesa',
                        date: new Date()
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
                    extract: (match) => ({
                        transaction: match[1]
                    })
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
                this.form.repayment_date = parsed.date;
                this.$nextTick(() => {
                    const input = this.$refs.datepicker;
                    if (input && input._flatpickr) {
                        input._flatpickr.setDate(parsed.date);
                    }
                });
            }

            if (parsed.date) {
                const d = new Date(parsed.date);
                parsed.date_display = d.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
            }

            this.parsedData = parsed;
        },

        // read on the server: M-Pesa, bank to M-Pesa (both references), Pesalink (owner, 23 Oct 2026)
        async readMessage() {
            const text = this.message.trim();
            if (!text) { this.readMsg = ''; return; }
            let body = { ok: false };
            try {
                const res = await fetch('/staff/mpesa/read-sms', { method: 'POST', credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ sms: text }) });
                if (res.ok) body = await res.json();
            } catch (e) {}
            if (!body.ok) { this.autoParseMessage(); this.readOk = !!this.parsedData.amount; this.readMsg = this.readOk ? '' : 'Could not read that message. Type the details in.'; return; }
            const m = body.sms;
            this.parsedData = {};
            this.form.amount = m.amount;
            this.form.mode = m.mode || this.form.mode;
            this.form.mpesa_ref = m.mpesa_ref || '';
            this.form.bank_ref = m.bank_ref || '';
            this.form.transaction = m.mpesa_ref || m.bank_ref || m.code;
            if (m.date) { this.form.repayment_date = m.date; this.$nextTick(() => this.$refs.datepicker?._flatpickr?.setDate(m.date)); }
            this.readOk = !(body.seen || []).length;
            this.readMsg = (body.seen || []).length ? 'Already recorded: ' + body.seen.join(', ') + '. Check you are not entering it twice.'
                : 'Read: ' + body.mode_label + ' · KES ' + Number(m.amount).toLocaleString('en-KE', { minimumFractionDigits: 2 })
                  + (m.mpesa_ref ? ' · M-Pesa ' + m.mpesa_ref : '') + (m.bank_ref ? ' · bank ' + m.bank_ref : '') + (m.party ? ' · ' + m.party : '');
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
            if (!this.selectedCycleId) {
                this.errors = ['Please select a loan cycle.'];
                return;
            }
            if (this.saving) return;
            this.errors = [];
            this.saving = true;
            try {
                const formData = new FormData();
                const action = this.editId ? `/repayments/${this.editId}` : '/repayments';
                const both = ['bank_to_mpesa', 'mpesa_to_bank'].includes(this.form.mode);

                formData.append('_method', this.method);
                formData.append('loan_id', this.loanId);
                formData.append('loan_cycle_id', this.selectedCycleId);
                formData.append('amount', this.form.amount);
                formData.append('repayment_date', this.formatDate(this.form.repayment_date));
                formData.append('transaction', both ? (this.form.mpesa_ref || this.form.bank_ref || '') : (this.form.transaction || ''));
                formData.append('bank_ref', this.form.bank_ref || '');
                formData.append('mpesa_ref', this.form.mpesa_ref || '');
                formData.append('mode', this.form.mode || '');

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
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: this.editId ? 'Payment updated.' : 'Payment recorded.' } }));
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