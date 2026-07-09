{{-- resources/views/partials/modal/investments-create-modal.blade.php --}}
@php
    $partners = $partners ?? [];
@endphp

<div x-data="investmentFormModal()" 
     x-init="initModal()"
     x-show="isOpen" 
     x-cloak
     class="fixed inset-0 z-[99999] overflow-hidden"
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
        <div class="w-full max-w-5xl">
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
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="modalTitle">Create New Investment</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="modalSubtitle">Add a new investment opportunity to your portfolio</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span x-show="hasDraft" 
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 mr-1 animate-pulse"></span>
                            Draft
                        </span>
                        <button @click="close()" 
                                class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Auto-save Indicator -->
                <div x-show="showAutoSave" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="px-6 py-2 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800 flex items-center justify-between">
                    <span class="text-sm text-blue-700 dark:text-blue-300 flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving draft...
                    </span>
                    <span class="text-xs text-blue-500 dark:text-blue-400" x-text="autoSaveTime"></span>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <form @submit.prevent="submitForm" id="investmentForm" class="space-y-6">
                        @csrf
                        <input type="hidden" x-model="formData.id">
                        
                        <!-- ============ BASIC INFORMATION ============ -->
                        <div>
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Basic Information</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Investment Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           x-model="formData.name" 
                                           @input="autoSave()"
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                           placeholder="e.g., Tech Startup Fund" required>
                                    <template x-if="errors.name">
                                        <p class="mt-1 text-sm text-red-500" x-text="errors.name[0]"></p>
                                    </template>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Investment Type <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="formData.type" 
                                            @change="autoSave()"
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                        <option value="">Select Type</option>
                                        <option value="commodity">Commodity</option>
                                        <option value="equity">Equity</option>
                                        <option value="bond">Bond</option>
                                        <option value="real_estate">Real Estate</option>
                                        <option value="startup">Startup</option>
                                        <option value="infrastructure">Infrastructure</option>
                                        <option value="technology">Technology</option>
                                        <option value="agriculture">Agriculture</option>
                                        <option value="energy">Energy</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <template x-if="errors.type">
                                        <p class="mt-1 text-sm text-red-500" x-text="errors.type[0]"></p>
                                    </template>
                                </div>
                                
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Sector</label>
                                    <input type="text" 
                                           x-model="formData.sector" 
                                           @input="autoSave()"
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                           placeholder="e.g., Technology">
                                </div>
                                
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Sub-Sector</label>
                                    <input type="text" 
                                           x-model="formData.sub_sector" 
                                           @input="autoSave()"
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                           placeholder="e.g., SaaS">
                                </div>
                            </div>
                        </div>

                        <!-- ============ LOCATION ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Location</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Country <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           x-model="formData.country" 
                                           @input="autoSave()"
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                           placeholder="e.g., Kenya" required>
                                    <template x-if="errors.country">
                                        <p class="mt-1 text-sm text-red-500" x-text="errors.country[0]"></p>
                                    </template>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Region</label>
                                    <input type="text" 
                                           x-model="formData.region" 
                                           @input="autoSave()"
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                           placeholder="e.g., East Africa">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">City</label>
                                    <input type="text" 
                                           x-model="formData.city" 
                                           @input="autoSave()"
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                           placeholder="e.g., Nairobi">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Full Address</label>
                                <textarea x-model="formData.address" 
                                          @input="autoSave()"
                                          rows="2" 
                                          class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                          placeholder="P.O. Box 12345, Nairobi, Kenya"></textarea>
                            </div>
                        </div>

                        <!-- ============ COMPANY DETAILS ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Company Details</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Company Name</label>
                                    <input type="text" 
                                           x-model="formData.company_name" 
                                           @input="autoSave()"
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                           placeholder="e.g., ABC Technologies Ltd">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Registration Number</label>
                                    <input type="text" 
                                           x-model="formData.registration_number" 
                                           @input="autoSave()"
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                           placeholder="e.g., PVT-2024-001">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Incorporation Date</label>
                                    <input type="date" 
                                           x-model="formData.incorporation_date" 
                                           @change="autoSave()"
                                           class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Legal Structure</label>
                                    <select x-model="formData.legal_structure" 
                                            @change="autoSave()"
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <option value="">Select Legal Structure</option>
                                        <option value="sole_proprietorship">Sole Proprietorship</option>
                                        <option value="partnership">Partnership</option>
                                        <option value="llc">LLC</option>
                                        <option value="corporation">Corporation</option>
                                        <option value="non_profit">Non-Profit</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ============ PRE-INVESTMENT FINANCIALS ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Pre-Investment Financials</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <!-- ... same as before ... -->
                            </div>
                        </div>

                        <!-- ============ INVESTMENT DETAILS ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Investment Details</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <!-- ... same as before ... -->
                            </div>
                        </div>

                        <!-- ============ INVESTMENT METRICS ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Investment Metrics</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <!-- ... same as before ... -->
                            </div>
                        </div>

                        <!-- ============ DATES ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Dates</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <!-- ... same as before ... -->
                            </div>
                        </div>

                        <!-- ============ RISK ASSESSMENT ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Risk Assessment</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Risk Rating</label>
                                    <select x-model="formData.risk_rating" 
                                            @change="autoSave()"
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <option value="">Select Rating</option>
                                        <option value="AAA">AAA - Lowest Risk</option>
                                        <option value="AA">AA - Very Low Risk</option>
                                        <option value="A">A - Low Risk</option>
                                        <option value="BBB">BBB - Moderate Risk</option>
                                        <option value="BB">BB - High Risk</option>
                                        <option value="B">B - Very High Risk</option>
                                        <option value="C">C - Highest Risk</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Stage</label>
                                    <select x-model="formData.stage" 
                                            @change="autoSave()"
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <option value="">Select Stage</option>
                                        <option value="ideation">Ideation</option>
                                        <option value="seed">Seed</option>
                                        <option value="startup">Startup</option>
                                        <option value="growth">Growth</option>
                                        <option value="expansion">Expansion</option>
                                        <option value="mature">Mature</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ============ RISK FACTORS ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-white/90">Risk Factors</h4>
                                <button type="button" 
                                        @click="addRiskFactor()" 
                                        class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Risk Factor
                                </button>
                            </div>
                            
                            <div class="space-y-3">
                                <template x-for="(risk, index) in riskFactors" :key="index">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-4 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Factor</label>
                                            <input type="text" 
                                                   x-model="risk.factor" 
                                                   @input="autoSave(); updateRiskFactorsJSON()"
                                                   class="w-full rounded-lg border border-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-800 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                                   placeholder="e.g., Regulatory Changes">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Severity</label>
                                            <select x-model="risk.severity" 
                                                    @change="autoSave(); updateRiskFactorsJSON()"
                                                    class="w-full rounded-lg border border-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">Select</option>
                                                <option value="Low">Low</option>
                                                <option value="Medium">Medium</option>
                                                <option value="High">High</option>
                                                <option value="Critical">Critical</option>
                                            </select>
                                        </div>
                                        <div class="flex items-end gap-2">
                                            <div class="flex-1">
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Mitigation</label>
                                                <input type="text" 
                                                       x-model="risk.mitigation" 
                                                       @input="autoSave(); updateRiskFactorsJSON()"
                                                       class="w-full rounded-lg border border-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-800 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                                       placeholder="e.g., Engage regulators">
                                            </div>
                                            <button type="button" 
                                                    @click="removeRiskFactor(index)" 
                                                    class="p-1.5 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="riskFactors.length === 0" class="text-sm text-gray-500 dark:text-gray-400 italic">No risk factors added. Click "Add Risk Factor" to start.</p>
                            </div>
                            <input type="hidden" x-model="formData.risk_factors">
                        </div>

                        <!-- ============ STAKEHOLDERS ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-white/90">Stakeholders</h4>
                                <button type="button" 
                                        @click="addStakeholder()" 
                                        class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Stakeholder
                                </button>
                            </div>
                            
                            <div class="space-y-3">
                                <template x-for="(stakeholder, index) in stakeholders" :key="index">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-4 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                            <select x-model="stakeholder.type" 
                                                    @change="autoSave(); updateStakeholdersJSON()"
                                                    class="w-full rounded-lg border border-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                                                <option value="director">Director</option>
                                                <option value="board">Board Member</option>
                                                <option value="advisor">Advisor</option>
                                                <option value="partner">Partner</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                            <input type="text" 
                                                   x-model="stakeholder.name" 
                                                   @input="autoSave(); updateStakeholdersJSON()"
                                                   class="w-full rounded-lg border border-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-800 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                                   placeholder="Full name">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Title / Role</label>
                                            <input type="text" 
                                                   x-model="stakeholder.title" 
                                                   @input="autoSave(); updateStakeholdersJSON()"
                                                   class="w-full rounded-lg border border-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-800 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                                   placeholder="e.g., CEO">
                                        </div>
                                        <div class="flex items-end gap-2">
                                            <div class="flex-1">
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Shareholding (%)</label>
                                                <input type="number" 
                                                       step="0.01" 
                                                       x-model="stakeholder.shareholding" 
                                                       @input="autoSave(); updateStakeholdersJSON()"
                                                       class="w-full rounded-lg border border-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-800 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                                       placeholder="0.00">
                                            </div>
                                            <button type="button" 
                                                    @click="removeStakeholder(index)" 
                                                    class="p-1.5 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="stakeholders.length === 0" class="text-sm text-gray-500 dark:text-gray-400 italic">No stakeholders added. Click "Add Stakeholder" to start.</p>
                            </div>
                            <input type="hidden" x-model="formData.stakeholders">
                        </div>

                        <!-- ============ SWOT ANALYSIS (Enhanced with Points) ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-white/90">SWOT Analysis</h4>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        <span x-text="swotTotalPoints"></span> points total
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <!-- Strengths -->
                                <div class="border border-green-200 dark:border-green-800 rounded-lg overflow-hidden">
                                    <div class="bg-green-50 dark:bg-green-900/20 px-3 py-2 border-b border-green-200 dark:border-green-800 flex items-center justify-between">
                                        <h5 class="text-sm font-medium text-green-700 dark:text-green-300">Strengths</h5>
                                        <button type="button" 
                                                @click="addSwotPoint('strengths')" 
                                                class="text-xs text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                                            + Add
                                        </button>
                                    </div>
                                    <div class="p-2 space-y-2">
                                        <template x-for="(point, index) in swotData.strengths" :key="'strength-'+index">
                                            <div class="flex items-start gap-2 group">
                                                <span class="text-green-500 mt-1">●</span>
                                                <input type="text" 
                                                       x-model="point.text" 
                                                       @input="autoSave(); updateSwotJSON()"
                                                       class="flex-1 text-sm bg-transparent border-b border-transparent hover:border-gray-300 focus:border-blue-500 focus:outline-none dark:text-gray-300"
                                                       placeholder="Enter strength...">
                                                <button type="button" 
                                                        @click="removeSwotPoint('strengths', index)" 
                                                        class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 text-xs transition-opacity">
                                                    ×
                                                </button>
                                            </div>
                                        </template>
                                        <p x-show="swotData.strengths.length === 0" class="text-xs text-gray-400 dark:text-gray-500 italic px-2">No strengths added</p>
                                    </div>
                                </div>

                                <!-- Weaknesses -->
                                <div class="border border-red-200 dark:border-red-800 rounded-lg overflow-hidden">
                                    <div class="bg-red-50 dark:bg-red-900/20 px-3 py-2 border-b border-red-200 dark:border-red-800 flex items-center justify-between">
                                        <h5 class="text-sm font-medium text-red-700 dark:text-red-300">Weaknesses</h5>
                                        <button type="button" 
                                                @click="addSwotPoint('weaknesses')" 
                                                class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                            + Add
                                        </button>
                                    </div>
                                    <div class="p-2 space-y-2">
                                        <template x-for="(point, index) in swotData.weaknesses" :key="'weakness-'+index">
                                            <div class="flex items-start gap-2 group">
                                                <span class="text-red-500 mt-1">●</span>
                                                <input type="text" 
                                                       x-model="point.text" 
                                                       @input="autoSave(); updateSwotJSON()"
                                                       class="flex-1 text-sm bg-transparent border-b border-transparent hover:border-gray-300 focus:border-blue-500 focus:outline-none dark:text-gray-300"
                                                       placeholder="Enter weakness...">
                                                <button type="button" 
                                                        @click="removeSwotPoint('weaknesses', index)" 
                                                        class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 text-xs transition-opacity">
                                                    ×
                                                </button>
                                            </div>
                                        </template>
                                        <p x-show="swotData.weaknesses.length === 0" class="text-xs text-gray-400 dark:text-gray-500 italic px-2">No weaknesses added</p>
                                    </div>
                                </div>

                                <!-- Opportunities -->
                                <div class="border border-blue-200 dark:border-blue-800 rounded-lg overflow-hidden">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 px-3 py-2 border-b border-blue-200 dark:border-blue-800 flex items-center justify-between">
                                        <h5 class="text-sm font-medium text-blue-700 dark:text-blue-300">Opportunities</h5>
                                        <button type="button" 
                                                @click="addSwotPoint('opportunities')" 
                                                class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            + Add
                                        </button>
                                    </div>
                                    <div class="p-2 space-y-2">
                                        <template x-for="(point, index) in swotData.opportunities" :key="'opportunity-'+index">
                                            <div class="flex items-start gap-2 group">
                                                <span class="text-blue-500 mt-1">●</span>
                                                <input type="text" 
                                                       x-model="point.text" 
                                                       @input="autoSave(); updateSwotJSON()"
                                                       class="flex-1 text-sm bg-transparent border-b border-transparent hover:border-gray-300 focus:border-blue-500 focus:outline-none dark:text-gray-300"
                                                       placeholder="Enter opportunity...">
                                                <button type="button" 
                                                        @click="removeSwotPoint('opportunities', index)" 
                                                        class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 text-xs transition-opacity">
                                                    ×
                                                </button>
                                            </div>
                                        </template>
                                        <p x-show="swotData.opportunities.length === 0" class="text-xs text-gray-400 dark:text-gray-500 italic px-2">No opportunities added</p>
                                    </div>
                                </div>

                                <!-- Threats -->
                                <div class="border border-yellow-200 dark:border-yellow-800 rounded-lg overflow-hidden">
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 px-3 py-2 border-b border-yellow-200 dark:border-yellow-800 flex items-center justify-between">
                                        <h5 class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Threats</h5>
                                        <button type="button" 
                                                @click="addSwotPoint('threats')" 
                                                class="text-xs text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300">
                                            + Add
                                        </button>
                                    </div>
                                    <div class="p-2 space-y-2">
                                        <template x-for="(point, index) in swotData.threats" :key="'threat-'+index">
                                            <div class="flex items-start gap-2 group">
                                                <span class="text-yellow-500 mt-1">●</span>
                                                <input type="text" 
                                                       x-model="point.text" 
                                                       @input="autoSave(); updateSwotJSON()"
                                                       class="flex-1 text-sm bg-transparent border-b border-transparent hover:border-gray-300 focus:border-blue-500 focus:outline-none dark:text-gray-300"
                                                       placeholder="Enter threat...">
                                                <button type="button" 
                                                        @click="removeSwotPoint('threats', index)" 
                                                        class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 text-xs transition-opacity">
                                                    ×
                                                </button>
                                            </div>
                                        </template>
                                        <p x-show="swotData.threats.length === 0" class="text-xs text-gray-400 dark:text-gray-500 italic px-2">No threats added</p>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" x-model="formData.swot_analysis">
                        </div>

                        <!-- ============ RESEARCH & ANALYSIS ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Research & Analysis</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Market Research</label>
                                    <textarea x-model="formData.market_research" 
                                              @input="autoSave()"
                                              rows="2" 
                                              class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                              placeholder="Market research findings..."></textarea>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Competitive Landscape</label>
                                    <textarea x-model="formData.competitive_landscape" 
                                              @input="autoSave()"
                                              rows="2" 
                                              class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                              placeholder="Competitive landscape analysis..."></textarea>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Key Assumptions</label>
                                    <textarea x-model="formData.key_assumptions" 
                                              @input="autoSave()"
                                              rows="2" 
                                              class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                              placeholder="Key assumptions..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- ============ MILESTONES ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-white/90">Milestones</h4>
                                <button type="button" 
                                        @click="addMilestone()" 
                                        class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Milestone
                                </button>
                            </div>
                            
                            <div class="space-y-3">
                                <template x-for="(milestone, index) in milestones" :key="index">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-4 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                                            <input type="date" 
                                                   x-model="milestone.date" 
                                                   @change="autoSave(); updateMilestonesJSON()"
                                                   class="w-full rounded-lg border border-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                            <input type="text" 
                                                   x-model="milestone.description" 
                                                   @input="autoSave(); updateMilestonesJSON()"
                                                   class="w-full rounded-lg border border-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-800 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                                   placeholder="e.g., Complete pilot project">
                                        </div>
                                        <div class="flex items-end gap-2">
                                            <div class="flex-1">
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                                <select x-model="milestone.status" 
                                                        @change="autoSave(); updateMilestonesJSON()"
                                                        class="w-full rounded-lg border border-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-800 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                                                    <option value="pending">Pending</option>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="completed">Completed</option>
                                                    <option value="cancelled">Cancelled</option>
                                                </select>
                                            </div>
                                            <button type="button" 
                                                    @click="removeMilestone(index)" 
                                                    class="p-1.5 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="milestones.length === 0" class="text-sm text-gray-500 dark:text-gray-400 italic">No milestones added. Click "Add Milestone" to start.</p>
                            </div>
                            <input type="hidden" x-model="formData.milestones">
                        </div>

                        <!-- ============ PARTNER FUNDING ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Partner Funding</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Partner</label>
                                    <select x-model="formData.funding_partner_id" 
                                            @change="autoSave()"
                                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <option value="">Select Partner (Optional)</option>
                                        @foreach($partners as $partner)
                                            <option value="{{ $partner->id }}">{{ $partner->name }} ({{ $partner->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Funding Amount</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">KES</span>
                                        <input type="number" 
                                               step="0.01" 
                                               x-model="formData.funding_amount" 
                                               @input="autoSave()"
                                               class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-14 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                               placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============ NOTES ============ -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white/90 mb-4">Notes</h4>
                            <textarea x-model="formData.notes" 
                                      @input="autoSave()"
                                      rows="3" 
                                      class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                      placeholder="Any notes about this investment..."></textarea>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0 bg-white dark:bg-gray-900">
                    <div class="flex items-center gap-3">
                        <button type="button" 
                                @click="restoreDraft()" 
                                x-show="hasDraft"
                                class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Restore Draft
                        </button>
                        <button type="button" 
                                @click="clearDraft()" 
                                x-show="hasDraft"
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                            Clear Draft
                        </button>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="close()" 
                                class="flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            Cancel
                        </button>
                        <button type="submit" form="investmentForm"
                                :disabled="isSubmitting"
                                class="flex justify-center px-4 py-2.5 text-sm font-medium text-white rounded-lg bg-blue-600 shadow-theme-xs hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <span x-show="!isSubmitting" x-text="submitButtonText">Create Investment</span>
                            <span x-show="isSubmitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="loadingText">Creating...</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('investmentFormModal', function() {
        return {
            isOpen: false,
            isSubmitting: false,
            isEditMode: false,
            errors: {},
            
            // Auto-save
            showAutoSave: false,
            autoSaveTimeout: null,
            autoSaveTime: '',
            hasDraft: false,
            draftKey: '',
            
            // JSON data arrays
            riskFactors: [],
            stakeholders: [],
            milestones: [],
            swotData: {
                strengths: [],
                weaknesses: [],
                opportunities: [],
                threats: []
            },
            
            formData: {
                id: null,
                name: '',
                type: '',
                sector: '',
                sub_sector: '',
                country: '',
                region: '',
                city: '',
                address: '',
                company_name: '',
                registration_number: '',
                incorporation_date: '',
                legal_structure: '',
                ebitda_pre_investment: '',
                revenue_pre_investment: '',
                net_profit_pre_investment: '',
                total_assets_pre_investment: '',
                total_liabilities_pre_investment: '',
                current_value: '',
                expected_return: '',
                actual_return: '',
                revenue_current: '',
                profit_current: '',
                valuation_current: '',
                initial_amount: '',
                irr: '',
                payback_period_months: '',
                break_even_point: '',
                purchase_date: new Date().toISOString().split('T')[0],
                maturity_date: '',
                exit_date: '',
                risk_rating: '',
                risk_factors: '',
                stakeholders: '',
                market_research: '',
                competitive_landscape: '',
                swot_analysis: '',
                key_assumptions: '',
                status: 'pipeline',
                stage: '',
                milestones: '',
                notes: '',
                funding_partner_id: '',
                funding_amount: ''
            },

            get swotTotalPoints() {
                return this.swotData.strengths.length + 
                       this.swotData.weaknesses.length + 
                       this.swotData.opportunities.length + 
                       this.swotData.threats.length;
            },

            get modalTitle() {
                return this.isEditMode ? 'Edit Investment' : 'Create New Investment';
            },

            get modalSubtitle() {
                return this.isEditMode ? 'Update investment details' : 'Add a new investment opportunity to your portfolio';
            },

            get submitButtonText() {
                return this.isEditMode ? 'Update Investment' : 'Create Investment';
            },

            get loadingText() {
                return this.isEditMode ? 'Updating...' : 'Creating...';
            },

            get submitUrl() {
                return this.isEditMode
                    ? `/investments/update/${this.formData.id}`
                    : '{{ route("investments.store") }}';
            },

            get method() {
                return this.isEditMode ? 'PUT' : 'POST';
            },

            // ============ AUTO-SAVE METHODS ============
            autoSave() {
                if (this.isEditMode) return; // Don't auto-save edits
                
                clearTimeout(this.autoSaveTimeout);
                this.showAutoSave = true;
                
                this.autoSaveTimeout = setTimeout(() => {
                    this.saveDraft();
                }, 2000);
            },

            saveDraft() {
                try {
                    const draftData = {
                        formData: this.formData,
                        riskFactors: this.riskFactors,
                        stakeholders: this.stakeholders,
                        milestones: this.milestones,
                        swotData: this.swotData,
                        timestamp: new Date().toISOString()
                    };
                    
                    localStorage.setItem(this.draftKey, JSON.stringify(draftData));
                    this.hasDraft = true;
                    this.autoSaveTime = 'Last saved: ' + new Date().toLocaleTimeString();
                    
                    setTimeout(() => {
                        this.showAutoSave = false;
                    }, 1000);
                } catch (e) {
                    console.error('Auto-save failed:', e);
                }
            },

            restoreDraft() {
                try {
                    const draft = localStorage.getItem(this.draftKey);
                    if (draft) {
                        const data = JSON.parse(draft);
                        this.formData = { ...this.formData, ...data.formData };
                        this.riskFactors = data.riskFactors || [];
                        this.stakeholders = data.stakeholders || [];
                        this.milestones = data.milestones || [];
                        this.swotData = data.swotData || { strengths: [], weaknesses: [], opportunities: [], threats: [] };
                        
                        // Update JSON fields
                        this.updateRiskFactorsJSON();
                        this.updateStakeholdersJSON();
                        this.updateMilestonesJSON();
                        this.updateSwotJSON();
                        
                        this.hasDraft = true;
                    }
                } catch (e) {
                    console.error('Restore draft failed:', e);
                }
            },

            clearDraft() {
                localStorage.removeItem(this.draftKey);
                this.hasDraft = false;
            },

            // ============ RISK FACTORS METHODS ============
            addRiskFactor() {
                this.riskFactors.push({
                    factor: '',
                    severity: 'Medium',
                    mitigation: ''
                });
                this.updateRiskFactorsJSON();
                this.autoSave();
            },

            removeRiskFactor(index) {
                this.riskFactors.splice(index, 1);
                this.updateRiskFactorsJSON();
                this.autoSave();
            },

            updateRiskFactorsJSON() {
                this.formData.risk_factors = JSON.stringify(this.riskFactors);
            },

            // ============ STAKEHOLDER METHODS ============
            addStakeholder() {
                this.stakeholders.push({
                    type: 'director',
                    name: '',
                    title: '',
                    shareholding: ''
                });
                this.updateStakeholdersJSON();
                this.autoSave();
            },

            removeStakeholder(index) {
                this.stakeholders.splice(index, 1);
                this.updateStakeholdersJSON();
                this.autoSave();
            },

            updateStakeholdersJSON() {
                const grouped = {
                    directors: [],
                    board: [],
                    advisors: [],
                    partners: []
                };
                
                this.stakeholders.forEach(s => {
                    const typeMap = {
                        'director': 'directors',
                        'board': 'board',
                        'advisor': 'advisors',
                        'partner': 'partners'
                    };
                    const key = typeMap[s.type] || 'directors';
                    grouped[key].push({
                        name: s.name,
                        title: s.title,
                        shareholding: s.shareholding ? parseFloat(s.shareholding) : undefined
                    });
                });
                
                Object.keys(grouped).forEach(key => {
                    if (grouped[key].length === 0) delete grouped[key];
                });
                
                this.formData.stakeholders = JSON.stringify(grouped);
            },

            // ============ SWOT METHODS ============
            addSwotPoint(category) {
                if (this.swotData[category]) {
                    this.swotData[category].push({ text: '' });
                    this.updateSwotJSON();
                    this.autoSave();
                }
            },

            removeSwotPoint(category, index) {
                if (this.swotData[category]) {
                    this.swotData[category].splice(index, 1);
                    this.updateSwotJSON();
                    this.autoSave();
                }
            },

            updateSwotJSON() {
                const swot = {};
                Object.keys(this.swotData).forEach(key => {
                    swot[key] = this.swotData[key].map(p => p.text).filter(t => t.trim() !== '');
                });
                this.formData.swot_analysis = JSON.stringify(swot);
            },

            // ============ MILESTONE METHODS ============
            addMilestone() {
                this.milestones.push({
                    date: new Date().toISOString().split('T')[0],
                    description: '',
                    status: 'pending'
                });
                this.updateMilestonesJSON();
                this.autoSave();
            },

            removeMilestone(index) {
                this.milestones.splice(index, 1);
                this.updateMilestonesJSON();
                this.autoSave();
            },

            updateMilestonesJSON() {
                this.formData.milestones = JSON.stringify(this.milestones);
            },

            // ============ INIT ============
            initModal() {
                console.log('Investment form modal initialized');
                
                this.draftKey = 'investment_draft_' + (this.isEditMode ? 'edit_' + (this.formData.id || '') : 'new');
                
                // Check for existing draft
                const draft = localStorage.getItem(this.draftKey);
                this.hasDraft = !!draft;
                
                window.addEventListener('open-investment-create', () => {
                    console.log('Received open-investment-create event');
                    this.openCreate();
                });

                window.addEventListener('edit-investment', (event) => {
                    console.log('Received edit-investment event');
                    this.openEdit(event.detail.investment);
                });
            },

            defaultFormData() {
                return {
                    id: null,
                    name: '',
                    type: '',
                    sector: '',
                    sub_sector: '',
                    country: '',
                    region: '',
                    city: '',
                    address: '',
                    company_name: '',
                    registration_number: '',
                    incorporation_date: '',
                    legal_structure: '',
                    ebitda_pre_investment: '',
                    revenue_pre_investment: '',
                    net_profit_pre_investment: '',
                    total_assets_pre_investment: '',
                    total_liabilities_pre_investment: '',
                    current_value: '',
                    expected_return: '',
                    actual_return: '',
                    revenue_current: '',
                    profit_current: '',
                    valuation_current: '',
                    initial_amount: '',
                    irr: '',
                    payback_period_months: '',
                    break_even_point: '',
                    purchase_date: new Date().toISOString().split('T')[0],
                    maturity_date: '',
                    exit_date: '',
                    risk_rating: '',
                    risk_factors: '',
                    stakeholders: '',
                    market_research: '',
                    competitive_landscape: '',
                    swot_analysis: '',
                    key_assumptions: '',
                    status: 'pipeline',
                    stage: '',
                    milestones: '',
                    notes: '',
                    funding_partner_id: '',
                    funding_amount: ''
                };
            },

            openCreate() {
                console.log('Opening create modal');
                this.isEditMode = false;
                this.isOpen = true;
                this.errors = {};
                this.riskFactors = [];
                this.stakeholders = [];
                this.milestones = [];
                this.swotData = { strengths: [], weaknesses: [], opportunities: [], threats: [] };
                this.formData = this.defaultFormData();
                this.draftKey = 'investment_draft_new';
                
                // Check for existing draft
                const draft = localStorage.getItem(this.draftKey);
                if (draft) {
                    this.hasDraft = true;
                    this.restoreDraft();
                } else {
                    this.hasDraft = false;
                }
                
                document.body.style.overflow = 'hidden';
            },

            openEdit(investment) {
                console.log('Opening edit modal for:', investment);
                this.isEditMode = true;
                this.isOpen = true;
                this.errors = {};
                this.draftKey = 'investment_draft_edit_' + investment.id;
                
                // Parse JSON data
                const parseJSON = (value) => {
                    if (typeof value === 'string') {
                        try {
                            return JSON.parse(value);
                        } catch (e) {
                            return value;
                        }
                    }
                    return value;
                };

                // Parse risk factors
                const riskFactors = parseJSON(investment.risk_factors);
                this.riskFactors = Array.isArray(riskFactors) ? riskFactors : [];
                
                // Parse stakeholders
                const stakeholders = parseJSON(investment.stakeholders);
                if (stakeholders && typeof stakeholders === 'object') {
                    const flat = [];
                    Object.keys(stakeholders).forEach(key => {
                        const typeMap = {
                            'directors': 'director',
                            'board': 'board',
                            'advisors': 'advisor',
                            'partners': 'partner'
                        };
                        const type = typeMap[key] || 'director';
                        stakeholders[key].forEach(item => {
                            flat.push({
                                type: type,
                                name: item.name || '',
                                title: item.title || '',
                                shareholding: item.shareholding || ''
                            });
                        });
                    });
                    this.stakeholders = flat;
                } else {
                    this.stakeholders = [];
                }
                
                // Parse milestones
                const milestones = parseJSON(investment.milestones);
                this.milestones = Array.isArray(milestones) ? milestones : [];
                
                // Parse SWOT
                const swot = parseJSON(investment.swot_analysis);
                this.swotData = {
                    strengths: (swot && swot.strengths) ? swot.strengths.map(t => ({ text: t })) : [],
                    weaknesses: (swot && swot.weaknesses) ? swot.weaknesses.map(t => ({ text: t })) : [],
                    opportunities: (swot && swot.opportunities) ? swot.opportunities.map(t => ({ text: t })) : [],
                    threats: (swot && swot.threats) ? swot.threats.map(t => ({ text: t })) : []
                };
                
                this.formData = {
                    id: investment.id,
                    name: investment.name || '',
                    type: investment.type || '',
                    sector: investment.sector || '',
                    sub_sector: investment.sub_sector || '',
                    country: investment.country || '',
                    region: investment.region || '',
                    city: investment.city || '',
                    address: investment.address || '',
                    company_name: investment.company_name || '',
                    registration_number: investment.registration_number || '',
                    incorporation_date: investment.incorporation_date || '',
                    legal_structure: investment.legal_structure || '',
                    ebitda_pre_investment: investment.ebitda_pre_investment || '',
                    revenue_pre_investment: investment.revenue_pre_investment || '',
                    net_profit_pre_investment: investment.net_profit_pre_investment || '',
                    total_assets_pre_investment: investment.total_assets_pre_investment || '',
                    total_liabilities_pre_investment: investment.total_liabilities_pre_investment || '',
                    current_value: investment.current_value || '',
                    expected_return: investment.expected_return || '',
                    actual_return: investment.actual_return || '',
                    revenue_current: investment.revenue_current || '',
                    profit_current: investment.profit_current || '',
                    valuation_current: investment.valuation_current || '',
                    initial_amount: investment.initial_amount || '',
                    irr: investment.irr || '',
                    payback_period_months: investment.payback_period_months || '',
                    break_even_point: investment.break_even_point || '',
                    purchase_date: investment.purchase_date || '',
                    maturity_date: investment.maturity_date || '',
                    exit_date: investment.exit_date || '',
                    risk_rating: investment.risk_rating || '',
                    risk_factors: investment.risk_factors || '',
                    stakeholders: investment.stakeholders || '',
                    market_research: investment.market_research || '',
                    competitive_landscape: investment.competitive_landscape || '',
                    swot_analysis: investment.swot_analysis || '',
                    key_assumptions: investment.key_assumptions || '',
                    status: investment.status || 'pipeline',
                    stage: investment.stage || '',
                    milestones: investment.milestones || '',
                    notes: typeof investment.notes === 'object' ? JSON.stringify(investment.notes) : (investment.notes || ''),
                    funding_partner_id: '',
                    funding_amount: ''
                };
                
                // Update JSON fields
                this.updateRiskFactorsJSON();
                this.updateStakeholdersJSON();
                this.updateMilestonesJSON();
                this.updateSwotJSON();
                
                this.hasDraft = false;
                localStorage.removeItem(this.draftKey);
                
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
                    // Ensure all JSON fields are updated
                    this.updateRiskFactorsJSON();
                    this.updateStakeholdersJSON();
                    this.updateMilestonesJSON();
                    this.updateSwotJSON();
                    
                    // Clear draft on successful submit
                    localStorage.removeItem(this.draftKey);
                    this.hasDraft = false;
                    
                    console.log('Submitting form to:', this.submitUrl);
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
                            window.dispatchEvent(new CustomEvent('show-alert', {
                                detail: {
                                    type: 'error',
                                    title: 'Validation Error',
                                    message: Object.values(data.errors)[0][0] || 'Please check the form for errors.'
                                }
                            }));
                        } else {
                            throw new Error(data.message || 'Failed to save investment');
                        }
                    } else {
                        window.dispatchEvent(new CustomEvent('show-alert', {
                            detail: {
                                type: 'success',
                                title: 'Success!',
                                message: data.message || 'Investment saved successfully.'
                            }
                        }));
                        this.close();
                        window.dispatchEvent(new CustomEvent('refresh-investments'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: {
                            type: 'error',
                            title: 'Error',
                            message: error.message || 'Failed to save investment.'
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