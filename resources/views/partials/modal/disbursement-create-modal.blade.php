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
                <input type="hidden" name="loan_id" x-model="loanId">

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
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Supports: Bank to M-PESA, Pesalink, and payment confirmations
                    </p>
                </div>

                <div x-show="!editId" class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

                <!-- Form Fields -->
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

                    <!-- Transaction Reference - Always shown and editable -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Transaction Reference <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.transaction" 
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            placeholder="e.g., 4540EMLQ4038 or UFAAL79NHH" required>
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
                </div>

                <!-- Parsed Data Preview -->
                <div x-show="Object.keys(parsedData).length > 0 && !editId" class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                    <h4 class="text-sm font-semibold text-green-800 dark:text-green-300">✓ Parsed from message</h4>
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
        loanId: null,
        message: '',
        parsedData: {},
        form: {
            amount: '',
            disburse_date: '',
            transaction: '',
            mode: '',
            payment_date: ''
        },

        init() {
            this.$watch('open', (value) => {
                if (value) {
                    this.$nextTick(() => {
                        this.initDatepickers();
                    });
                }
            });

            window.openDisbursementModal = (loanId, data) => this.openModal(loanId, data);
            window.closeDisbursementModal = () => this.close();
        },

        initDatepickers() {
            const input = this.$refs.datepicker;
            if (input && typeof flatpickr !== 'undefined') {
                if (input._flatpickr) {
                    input._flatpickr.destroy();
                }
                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    locale: { firstDayOfWeek: 1 },
                    onChange: (selectedDates) => {
                        if (selectedDates.length > 0) {
                            this.form.disburse_date = selectedDates[0];
                        }
                    }
                });
            }

            const paymentInput = this.$refs.paymentDatepicker;
            if (paymentInput && typeof flatpickr !== 'undefined') {
                if (paymentInput._flatpickr) {
                    paymentInput._flatpickr.destroy();
                }
                flatpickr(paymentInput, {
                    dateFormat: 'Y-m-d',
                    locale: { firstDayOfWeek: 1 },
                    onChange: (selectedDates) => {
                        if (selectedDates.length > 0) {
                            this.form.payment_date = selectedDates[0];
                        }
                    }
                });
            }
        },

        openModal(loanId = null, data = null) {
            this.resetForm();
            this.open = true;
            document.body.style.overflow = 'hidden';

            if (data) {
                this.title = 'Edit Disbursement';
                this.submitText = 'Update Disbursement';
                this.method = 'PUT';
                this.editId = data.id;
                this.loanId = data.loan_id;
                this.form.amount = data.amount;
                this.form.transaction = data.transaction || '';
                this.form.mode = data.mode || '';
                
                if (data.disburse_date) {
                    this.form.disburse_date = data.disburse_date;
                    this.$nextTick(() => {
                        const input = this.$refs.datepicker;
                        if (input && input._flatpickr) {
                            input._flatpickr.setDate(data.disburse_date);
                        }
                    });
                }
                
                if (data.payment_date) {
                    this.form.payment_date = data.payment_date;
                    this.$nextTick(() => {
                        const input = this.$refs.paymentDatepicker;
                        if (input && input._flatpickr) {
                            input._flatpickr.setDate(data.payment_date);
                        }
                    });
                }
            } else {
                this.title = 'Add Disbursement';
                this.submitText = 'Create Disbursement';
                this.method = 'POST';
                this.editId = null;
                this.loanId = loanId;
                
                this.$nextTick(() => {
                    const input = this.$refs.datepicker;
                    if (input && input._flatpickr) {
                        const now = new Date();
                        input._flatpickr.setDate(now);
                        this.form.disburse_date = now;
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
                disburse_date: '',
                transaction: '',
                mode: '',
                payment_date: ''
            };
            this.message = '';
            this.parsedData = {};
            this.editId = null;
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
                this.form.disburse_date = parsed.date;
                this.form.payment_date = parsed.date;
                this.$nextTick(() => {
                    const input = this.$refs.datepicker;
                    if (input && input._flatpickr) {
                        input._flatpickr.setDate(parsed.date);
                    }
                    const paymentInput = this.$refs.paymentDatepicker;
                    if (paymentInput && paymentInput._flatpickr) {
                        paymentInput._flatpickr.setDate(parsed.date);
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
            try {
                const formData = new FormData();
                const action = this.editId ? `/disbursements/${this.editId}` : '/disbursements';
                
                formData.append('_method', this.method);
                formData.append('loan_id', this.loanId);
                formData.append('amount', this.form.amount);
                formData.append('disburse_date', this.formatDate(this.form.disburse_date));
                formData.append('transaction', this.form.transaction || '');
                formData.append('mode', this.form.mode || '');
                formData.append('payment_date', this.formatDate(this.form.payment_date) || '');

                const response = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (response.ok) {
                    this.showAlert('success', this.editId ? 'Disbursement updated successfully!' : 'Disbursement created successfully!');
                    this.close();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    const error = await response.json();
                    this.showAlert('error', error.message || 'Something went wrong.');
                }
            } catch (error) {
                this.showAlert('error', 'Network error. Please try again.');
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
        },

        showAlert(type, message) {
            if (typeof window.showAlert === 'function') {
                window.showAlert(type, message);
            } else {
                alert(message);
            }
        }
    }
}
</script>