{{-- resources/views/partials/modal/loans-create-modal.blade.php --}}
<div 
    x-data="loansCreateModal()" 
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
                <input type="hidden" name="user_id" x-model="selectedUserId">
                <input type="hidden" name="broker_status" x-model="brokerStatus">
                <input type="hidden" name="status" x-model="form.status">
                <input type="hidden" name="signature_data" x-model="signatureData">
                <input type="hidden" name="use_existing_signature" x-model="useExistingSignature ? '1' : '0'">

                <!-- User Selection Section -->
                <div class="mb-6">
                    <!-- For Edit Mode - Show user as locked/read-only -->
                    <div x-show="editId && borrowerName" class="mb-6">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Borrower <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="w-full rounded-lg border border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 cursor-not-allowed">
                                <span x-text="borrowerName"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">(locked - cannot change)</span>
                            </div>
                            <input type="hidden" name="user_id" x-model="selectedUserId">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <svg class="inline h-4 w-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Borrower cannot be changed while editing an existing loan
                        </p>
                    </div>

                    <!-- For Create Mode - Show select dropdown -->
                    <template x-if="!editId && showUserSelection">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Select Borrower <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select x-model="selectedUserId" @change="onUserChange()"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                    required>
                                    <option value="">-- Select User --</option>
                                    @if(isset($users) && $users->count())
                                        @foreach($users as $userOption)
                                            <option value="{{ $userOption->id }}" data-name="{{ $userOption->name }}" data-email="{{ $userOption->email }}">
                                                {{ $userOption->name }} ({{ $userOption->email }} - {{ ucfirst($userOption->role) }})
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <p x-show="selectedUserName" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Selected: <strong x-text="selectedUserName"></strong>
                            </p>
                        </div>
                    </template>

                    <!-- For Create Mode - Show borrower name (when user is pre-selected) -->
                    <template x-if="!editId && !showUserSelection && borrowerName">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Borrower <span class="text-red-500">*</span>
                            </label>
                            <p class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-800 dark:text-white">
                                <span x-text="borrowerName"></span>
                            </p>
                            <input type="hidden" name="user_id" x-model="selectedUserId">
                        </div>
                    </template>
                </div>

                <!-- Borrower Info Display (for non-admin users) -->
                <div x-show="!showUserSelection && borrowerName" class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Borrower
                    </label>
                    <p class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-800 dark:text-white">
                        <span x-text="borrowerName"></span>
                    </p>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

                <!-- Loan Details Section -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Loan Amount (KES) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" x-model="form.amount" step="0.01" min="1"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Borrow Date <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.borrow_date" x-ref="datepicker"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            placeholder="Select date" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Loan Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.loan_type_id" @change="calculateDueDate()"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            required>
                            <option value="">-- Select Loan Type --</option>
                            @if(isset($loanTypes) && $loanTypes->count())
                                @foreach($loanTypes as $loanType)
                                    <option value="{{ $loanType->id }}" data-period="{{ $loanType->period }}" data-unit="{{ $loanType->unit }}" data-interest="{{ $loanType->interest_rate }}">
                                        {{ $loanType->name }} ({{ $loanType->interest_rate }}% interest)
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Due Date
                        </label>
                        <p class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-800 dark:text-white" 
                           x-text="dueDateDisplay || 'Select loan type to calculate'"></p>
                        <input type="hidden" name="due_date" x-model="form.due_date">
                    </div>

                    <!-- Status (Admin/Teller only) -->
                    <div x-show="isAdminOrTeller">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Loan Status
                        </label>
                        <select x-model="form.status"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="disbursed">Disbursed</option>
                            <option value="repaid">Repaid</option>
                            <option value="active">Active</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>

                    <!-- Transaction Type (Admin/Teller only) -->
                    <div x-show="isAdminOrTeller">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Transaction Type
                        </label>
                        <select x-model="brokerStatus"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="0">Direct Transaction</option>
                            <option value="1">Broker Transaction</option>
                        </select>
                    </div>
                </div>

                <!-- Reason -->
                <div class="mt-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Reason for Loan <span class="text-red-500">*</span>
                    </label>
                    <textarea x-model="form.reason" rows="3"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        placeholder="Describe the purpose of this loan..." required></textarea>
                </div>

                <!-- Guarantor Section -->
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Guarantor
                        </label>
                        <select x-model="form.guarantor_id"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="">-- Select Guarantor (Optional) --</option>
                            @if(isset($guarantors) && $guarantors->count())
                                @foreach($guarantors as $guarantor)
                                    <option value="{{ $guarantor->id }}">{{ $guarantor->name }} ({{ $guarantor->email }})</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Relationship to Guarantor
                        </label>
                        <input type="text" x-model="form.guarantor_relationship"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            placeholder="e.g., Friend, Relative, Colleague">
                    </div>
                </div>

                <!-- Loan Officer (Admin/Teller only) -->
                <div x-show="isAdminOrTeller" class="mt-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Loan Officer
                    </label>
                    <select x-model="form.loan_officer_id"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">-- Select Loan Officer (Optional) --</option>
                        @if(isset($loanOfficers) && $loanOfficers->count())
                            @foreach($loanOfficers as $officer)
                                <option value="{{ $officer->id }}">{{ $officer->name }} ({{ ucfirst($officer->role) }})</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Digital Signature Section -->
                <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h4 class="text-lg font-medium mb-4 text-gray-700 dark:text-white/90">Digital Signature</h4>
                    
                    <!-- Existing Signature -->
                    <div x-show="hasExistingSignature" class="mb-4">
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                <div class="flex-shrink-0">
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-green-200 dark:border-green-700 shadow-sm">
                                        <div class="w-24 h-24 flex items-center justify-center bg-transparent">
                                            <img :src="existingSignatureUrl" 
                                                alt="Existing signature"
                                                class="max-w-full max-h-full object-contain">
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-green-800 dark:text-green-300" x-text="selectedUserName || borrowerName || 'User'"></h4>
                                    <p class="text-green-700 dark:text-green-400 text-sm mb-2">✅ Existing Signature Found</p>
                                    <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Signature Verified
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div>
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Use existing signature?</p>
                                <p class="text-xs text-blue-600 dark:text-blue-400">The existing signature will be used for the loan agreement unless you create a new one.</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" id="use-existing-signature-modal" x-model="useExistingSignature" @change="onUseExistingSignatureChange()" class="rounded border-blue-300 text-blue-600 focus:ring-blue-500 dark:border-blue-600 dark:bg-blue-900">
                                <label for="use-existing-signature-modal" class="text-sm text-blue-800 dark:text-blue-300">Use Existing</label>
                            </div>
                        </div>
                    </div>

                    <!-- Signature Pad -->
                    <div class="mt-4" id="signature-section">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            <span x-text="hasExistingSignature ? 'Draw New Signature (Will Replace Existing)' : 'Draw Your Signature'"></span>
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 bg-white dark:bg-gray-900">
                            <div class="flex justify-center">
                                <div class="signature-pad relative">
                                    <canvas id="signature-canvas-modal" class="border border-gray-300 rounded-lg bg-white" 
                                            style="touch-action: none; width: 400px; height: 200px; max-width: 100%;"></canvas>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex flex-col sm:flex-row gap-2 justify-center items-center">
                                <button type="button" @click="clearSignature()" class="px-4 py-2 text-sm bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">Clear Signature</button>
                                <button type="button" @click="saveSignature()" class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                    <span x-text="hasExistingSignature ? 'Save New Signature' : 'Save Signature'"></span>
                                </button>
                            </div>
                        </div>
                        <div id="signature-status-modal" class="mt-2 text-sm text-center" x-text="signatureStatus"></div>
                        
                        <!-- Signature Preview -->
                        <div x-show="showSignaturePreview" class="mt-4">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-center">Signature Preview</h4>
                                <div class="flex flex-col items-center gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="bg-white dark:bg-gray-900 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
                                            <div class="w-64 h-32 flex items-center justify-center bg-transparent">
                                                <img :src="signatureData" x-show="signatureData" class="max-w-full max-h-full object-contain" alt="Signature Preview">
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400"><strong>File name:</strong> <span x-text="signatureFilename"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consent -->
                <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="flex items-start space-x-3">
                        <input type="checkbox" id="consent-modal" value="1" x-model="form.consent" class="mt-1 rounded border-gray-300 text-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800">
                        <div>
                            <label for="consent-modal" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                I agree to the terms and conditions of the loan agreement <span class="text-red-500">*</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">By checking this box, you acknowledge that you have read, understood, and agree to be bound by all terms and conditions of the loan agreement.</p>
                        </div>
                    </div>
                    <p x-show="!form.consent && showConsentError" class="mt-1 text-sm text-red-500">You must agree to the terms to proceed.</p>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 sticky bottom-0 bg-white dark:bg-gray-900">
                    <button type="button" @click="close()" 
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isSubmitting" x-text="submitText"></span>
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

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<script>
function loansCreateModal() {
    return {
        // Modal state
        open: false,
        title: 'Create New Loan',
        submitText: 'Create Loan',
        method: 'POST',
        editId: null,
        isSubmitting: false,
        
        // User selection
        selectedUserId: '',
        selectedUserName: '',
        showUserSelection: true,
        borrowerName: '',
        
        // Loan data
        loanId: null,
        brokerStatus: '0',
        dueDateDisplay: '',
        showConsentError: false,
        
        // Signature
        signaturePad: null,
        signatureData: '',
        signatureStatus: '',
        showSignaturePreview: false,
        signatureFilename: '',
        useExistingSignature: true,
        hasExistingSignature: false,
        existingSignatureUrl: '',
        canvasSize: 200,
        
        // Form data
        form: {
            amount: '',
            borrow_date: '',
            loan_type_id: '',
            status: 'pending',
            reason: '',
            guarantor_id: '',
            guarantor_relationship: '',
            loan_officer_id: '',
            consent: false,
            due_date: '',
            signature_data: ''
        },
        
        // Computed
        get isAdminOrTeller() {
            const role = '{{ auth()->user()->role }}';
            return role === 'admin' || role === 'teller';
        },

        init() {
            this.$watch('open', (value) => {
                if (value) {
                    this.$nextTick(() => {
                        this.initDatepickers();
                        this.initSignaturePad();
                        // Check for existing signature after a slight delay to ensure user data is loaded
                        setTimeout(() => {
                            this.checkExistingSignature();
                        }, 300);
                    });
                }
            });

            // Initialize the modal globally
            window.openLoansCreateModal = (data) => this.openModal(data);
            window.closeLoansCreateModal = () => this.close();
        },

        initDatepickers() {
            const input = this.$refs.datepicker;
            if (input && typeof flatpickr !== 'undefined') {
                if (input._flatpickr) {
                    input._flatpickr.destroy();
                }
                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    maxDate: 'today',
                    locale: { firstDayOfWeek: 1 },
                    onChange: (selectedDates) => {
                        if (selectedDates.length > 0) {
                            this.form.borrow_date = selectedDates[0];
                            this.calculateDueDate();
                        }
                    }
                });
            }
        },

        initSignaturePad() {
            const canvas = document.querySelector('#signature-canvas-modal');
            if (!canvas) return;
            
            if (this.signaturePad) {
                this.signaturePad.clear();
                return;
            }

            canvas.width = this.canvasSize * 2;
            canvas.height = this.canvasSize;
            canvas.style.width = this.canvasSize * 2 + 'px';
            canvas.style.height = this.canvasSize + 'px';
            
            this.signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(255, 255, 255, 0)',
                penColor: 'rgb(0, 0, 0)',
                minWidth: 2,
                maxWidth: 4,
                throttle: 16,
                velocityFilterWeight: 0.7
            });
            
            canvas.addEventListener('mouseup', () => {
                if (this.signaturePad && !this.signaturePad.isEmpty()) {
                    this.signatureData = this.getFullSignature();
                    this.form.signature_data = this.signatureData;
                    this.useExistingSignature = false;
                }
            });
        },

        checkExistingSignature() {
            const userId = this.selectedUserId || '{{ isset($user) ? $user->id : '' }}';
            
            console.log('checkExistingSignature called with userId:', userId);
            
            if (!userId) {
                console.log('No userId found, setting hasExistingSignature to false');
                this.hasExistingSignature = false;
                this.existingSignatureUrl = '';
                this.useExistingSignature = false;
                return;
            }

            // Check if the user has an existing signature
            fetch(`/users/${userId}/signature-check`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Signature check response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Signature check data:', data);
                this.hasExistingSignature = data.hasSignature || false;
                this.existingSignatureUrl = data.signatureUrl || '';
                // IMPORTANT: Set useExistingSignature based on whether signature exists
                this.useExistingSignature = data.hasSignature || false;
                
                if (this.hasExistingSignature) {
                    console.log('Existing signature found:', this.existingSignatureUrl);
                } else {
                    console.log('No existing signature found');
                }
            })
            .catch((error) => {
                console.error('Error checking signature:', error);
                this.hasExistingSignature = false;
                this.existingSignatureUrl = '';
                this.useExistingSignature = false;
            });
        },

        onUserChange() {
            const select = document.querySelector('select[x-model="selectedUserId"]');
            if (select && select.selectedIndex > 0) {
                const option = select.options[select.selectedIndex];
                this.selectedUserName = option.dataset.name || '';
            } else {
                this.selectedUserName = '';
            }
            // Check for existing signature when user changes
            this.checkExistingSignature();
        },

        openModal(data = null) {
            this.resetForm();
            this.open = true;
            document.body.style.overflow = 'hidden';

            const userRole = '{{ auth()->user()->role }}';
            this.showUserSelection = userRole === 'admin' || userRole === 'teller' || userRole === 'broker';
            
            const isEdit = data && data.edit;
            
            if (isEdit) {
                console.log('EDIT MODE - Received data:', data);
                
                this.title = 'Edit Loan';
                this.submitText = 'Update Loan';
                this.method = 'PUT';
                this.editId = data.edit.id;
                this.loanId = data.edit.id;
                
                // Set user info - this will show the locked user field
                if (data.userId) {
                    this.selectedUserId = data.userId;
                    this.borrowerName = data.userName || '';
                } else if (data.edit.user_id) {
                    this.selectedUserId = data.edit.user_id;
                    this.borrowerName = data.edit.user_name || '';
                } else if (data.edit.user_name) {
                    this.borrowerName = data.edit.user_name;
                }
                
                // IMPORTANT: Make sure selectedUserId is set for the hidden input
                if (!this.selectedUserId && data.edit.user_id) {
                    this.selectedUserId = data.edit.user_id;
                }
                
                // Populate ALL form fields
                this.form.amount = data.edit.amount || '';
                this.form.borrow_date = data.edit.borrow_date || '';
                this.form.loan_type_id = data.edit.loan_type_id || '';
                this.form.status = data.edit.status || 'pending';
                this.form.reason = data.edit.reason || '';
                this.form.guarantor_id = data.edit.guarantor_id || '';
                this.form.guarantor_relationship = data.edit.guarantor_relationship || '';
                this.form.loan_officer_id = data.edit.loan_officer_id || '';
                this.form.consent = data.edit.consent !== undefined ? data.edit.consent : true;
                this.form.due_date = data.edit.due_date || '';
                this.brokerStatus = data.edit.broker_status || '0';
                
                // Check if the loan already has a signature (from the edit data)
                if (data.edit.has_signature !== undefined) {
                    console.log('Signature data from edit:', data.edit.has_signature, data.edit.signature_url);
                    this.hasExistingSignature = data.edit.has_signature;
                    this.existingSignatureUrl = data.edit.signature_url || '';
                    this.useExistingSignature = this.hasExistingSignature;
                }
                
                // Set due date display
                if (this.form.due_date) {
                    this.dueDateDisplay = this.form.due_date;
                } else {
                    this.calculateDueDate();
                }
                
                // Set the datepicker value
                if (this.form.borrow_date) {
                    this.$nextTick(() => {
                        const input = this.$refs.datepicker;
                        if (input && input._flatpickr) {
                            input._flatpickr.setDate(this.form.borrow_date);
                        }
                    });
                }
                
                // Check for existing signature (will set hasExistingSignature)
                this.$nextTick(() => {
                    this.checkExistingSignature();
                });
                
                // Set loan type in select
                if (data.edit.loan_type_id) {
                    this.$nextTick(() => {
                        const select = document.querySelector('select[x-model="form.loan_type_id"]');
                        if (select) {
                            select.value = data.edit.loan_type_id;
                            const event = new Event('change');
                            select.dispatchEvent(event);
                        }
                    });
                }
                
                // Set guarantor in select
                if (data.edit.guarantor_id) {
                    this.$nextTick(() => {
                        const select = document.querySelector('select[x-model="form.guarantor_id"]');
                        if (select) {
                            select.value = data.edit.guarantor_id;
                        }
                    });
                }
                
                // Set loan officer in select
                if (data.edit.loan_officer_id) {
                    this.$nextTick(() => {
                        const select = document.querySelector('select[x-model="form.loan_officer_id"]');
                        if (select) {
                            select.value = data.edit.loan_officer_id;
                        }
                    });
                }
                
                console.log('Edit mode - Form data set:', this.form);
                
            } else {
                // === CREATE MODE ===
                this.title = 'Create New Loan';
                this.submitText = 'Create Loan';
                this.method = 'POST';
                this.editId = null;
                this.loanId = null;
                
                // Set user from data or role
                if (data && data.userId) {
                    this.selectedUserId = data.userId;
                    this.borrowerName = data.userName || '';
                    this.showUserSelection = false;
                } else if (data && data.user) {
                    this.selectedUserId = data.user.id;
                    this.borrowerName = data.user.name;
                    this.showUserSelection = false;
                } else if (this.showUserSelection) {
                    this.selectedUserId = '';
                    this.borrowerName = '';
                } else {
                    this.selectedUserId = '{{ auth()->id() }}';
                    this.borrowerName = '{{ auth()->user()->name }}';
                }

                // Set default borrow date to today
                const now = new Date();
                this.form.borrow_date = now;
                this.$nextTick(() => {
                    const input = this.$refs.datepicker;
                    if (input && input._flatpickr) {
                        input._flatpickr.setDate(now);
                    }
                });

                // Set broker status based on user role
                if (userRole === 'broker') {
                    this.brokerStatus = '1';
                }
                
                // Check for existing signature
                this.$nextTick(() => {
                    this.checkExistingSignature();
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
                borrow_date: '',
                loan_type_id: '',
                status: 'pending',
                reason: '',
                guarantor_id: '',
                guarantor_relationship: '',
                loan_officer_id: '',
                consent: false,
                due_date: '',
                signature_data: ''
            };
            this.dueDateDisplay = '';
            this.signatureData = '';
            this.signatureStatus = '';
            this.showSignaturePreview = false;
            this.editId = null;
            this.loanId = null;
            this.isSubmitting = false;
            this.showConsentError = false;
            
            if (this.signaturePad) {
                this.signaturePad.clear();
            }
        },

        calculateDueDate() {
            const borrowDate = new Date(this.form.borrow_date);
            if (isNaN(borrowDate.getTime())) {
                this.dueDateDisplay = '';
                this.form.due_date = '';
                return;
            }
            
            let period = 10;
            let unit = 'days';
            
            if (this.form.loan_type_id) {
                const select = document.querySelector('select[x-model="form.loan_type_id"]');
                if (select && select.selectedIndex > 0) {
                    const selectedOption = select.options[select.selectedIndex];
                    period = parseInt(selectedOption.dataset.period) || 10;
                    unit = selectedOption.dataset.unit || 'days';
                }
            }
            
            const dueDate = new Date(borrowDate);
            if (unit === 'days') dueDate.setDate(dueDate.getDate() + period);
            else if (unit === 'weeks') dueDate.setDate(dueDate.getDate() + (period * 7));
            else if (unit === 'months') dueDate.setMonth(dueDate.getMonth() + period);
            else if (unit === 'years') dueDate.setFullYear(dueDate.getFullYear() + period);
            
            this.dueDateDisplay = dueDate.toISOString().split('T')[0];
            this.form.due_date = this.dueDateDisplay;
        },

        getFullSignature() {
            return this.signaturePad.toDataURL('image/png');
        },

        clearSignature() {
            if (this.signaturePad) {
                this.signaturePad.clear();
                this.signatureData = '';
                this.form.signature_data = '';
                this.signatureStatus = 'Signature cleared';
                this.showSignaturePreview = false;
                
                if (this.hasExistingSignature) {
                    this.useExistingSignature = true;
                }
            }
        },

        saveSignature() {
            if (!this.signaturePad || this.signaturePad.isEmpty()) {
                this.signatureStatus = '⚠️ Please provide a signature first';
                return;
            }
            
            this.signatureData = this.getFullSignature();
            this.form.signature_data = this.signatureData;
            this.useExistingSignature = false;
            
            this.signatureStatus = '✅ Signature captured successfully!';
            this.showSignaturePreview = true;
            this.updateSignatureFilename();
        },

        onUseExistingSignatureChange() {
            if (this.useExistingSignature) {
                this.signatureData = '';
                this.form.signature_data = '';
                this.showSignaturePreview = false;
                if (this.signaturePad) {
                    this.signaturePad.clear();
                }
            }
        },

        updateSignatureFilename() {
            const name = this.selectedUserName || this.borrowerName || 'user';
            this.signatureFilename = `signature_${name.replace(/[^a-zA-Z0-9]/g, '_')}.png`;
        },

        async submitForm() {
            // Validate consent
            if (!this.form.consent) {
                this.showConsentError = true;
                return;
            }
            this.showConsentError = false;

            // SIGNATURE VALIDATION - FIXED
            // Check if we need a signature:
            // 1. If in edit mode AND has existing signature AND useExistingSignature is true -> OK
            // 2. If in create mode AND has existing signature AND useExistingSignature is true -> OK
            // 3. If in create mode AND no existing signature -> need to draw one
            // 4. If in create mode AND useExistingSignature is false -> need to draw one
            
            const hasValidSignature = this.hasExistingSignature && this.useExistingSignature;
            const hasNewSignature = this.signatureData && !this.useExistingSignature;
            
            // For create mode: must have either an existing signature (checked) or a new signature
            // For edit mode: can use existing signature or provide a new one
            const needsSignature = !this.editId && !hasValidSignature && !hasNewSignature;
            
            if (needsSignature) {
                this.signatureStatus = '⚠️ Please provide a signature';
                return;
            }

            this.isSubmitting = true;

            try {
                const formData = new FormData();
                const action = this.editId ? `/loans/${this.editId}` : '{{ route("loans.store") }}';
                
                formData.append('_method', this.method);
                formData.append('user_id', this.selectedUserId || '{{ auth()->id() }}');
                formData.append('loan_type_id', this.form.loan_type_id);
                formData.append('amount', this.form.amount);
                formData.append('borrow_date', this.formatDate(this.form.borrow_date));
                formData.append('status', this.form.status);
                formData.append('broker_status', this.brokerStatus);
                formData.append('reason', this.form.reason);
                formData.append('guarantor_id', this.form.guarantor_id || '');
                formData.append('guarantor_relationship', this.form.guarantor_relationship || '');
                formData.append('loan_officer_id', this.form.loan_officer_id || '');
                formData.append('consent', this.form.consent ? '1' : '0');
                formData.append('due_date', this.form.due_date || '');
                
                // Add signature if using new one (not using existing)
                if (!this.useExistingSignature && this.signatureData) {
                    formData.append('signature_data', this.signatureData);
                }

                const response = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', 'Success!', data.message || (this.editId ? 'Loan updated successfully!' : 'Loan created successfully!'));
                    } else {
                        alert(data.message || (this.editId ? 'Loan updated successfully!' : 'Loan created successfully!'));
                    }
                    this.close();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    // Handle validation errors
                    if (response.status === 422 && data.errors) {
                        let errorMsg = Object.values(data.errors).flat().join('\n');
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', 'Validation Error', errorMsg);
                        } else {
                            alert('Validation Error: ' + errorMsg);
                        }
                    } else if (data.duplicate && data.active_loans) {
                        // Handle duplicate loans
                        let loanList = data.active_loans.map(l => 
                            `• ${l.borrower_name}: KES ${l.amount.toLocaleString()} (${l.status_display})`
                        ).join('\n');
                        
                        if (confirm(`This borrower has ${data.active_loans.length} active loan(s):\n\n${loanList}\n\nDo you want to create another loan anyway?`)) {
                            // Retry with force create
                            formData.append('X-Force-Create', 'true');
                            const retryResponse = await fetch(action, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-Force-Create': 'true'
                                },
                                body: formData
                            });
                            
                            const retryData = await retryResponse.json();
                            if (retryResponse.ok && retryData.success) {
                                if (typeof window.showAlert === 'function') {
                                    window.showAlert('success', 'Success!', 'Loan created successfully!');
                                } else {
                                    alert('Loan created successfully!');
                                }
                                this.close();
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                throw new Error(retryData.message || 'Failed to create loan');
                            }
                        }
                    } else {
                        throw new Error(data.message || 'Something went wrong');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof window.showAlert === 'function') {
                    window.showAlert('error', 'Error!', error.message || 'Network error. Please try again.');
                } else {
                    alert('Error: ' + error.message);
                }
            } finally {
                this.isSubmitting = false;
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