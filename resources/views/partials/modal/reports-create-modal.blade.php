blade.php{{-- resources/views/reports/partials/modal/slideover.blade.php --}}
<div x-data="slideoverModal()" x-show="isOpen" class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity" @click="close()"></div>
    
    <!-- Slideover Panel -->
    <div class="fixed inset-y-0 right-0 max-w-full flex">
        <div class="w-screen max-w-2xl">
            <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl transform transition-all"
                 x-show="isOpen"
                 x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full">
                
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="title">Create/Edit Record</h3>
                    <button @click="close()" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <form @submit.prevent="submitForm" class="space-y-6">
                        <!-- Dynamic form fields would be rendered here -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                <input type="text" 
                                       x-model="formData.name"
                                       class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea x-model="formData.description" 
                                          rows="3"
                                          class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" @click="close()" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" 
                                    :disabled="isSubmitting"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <span x-show="!isSubmitting" x-text="submitButtonText">Save</span>
                                <span x-show="isSubmitting" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function slideoverModal() {
    return {
        isOpen: false,
        title: 'Create Record',
        submitButtonText: 'Save',
        formData: {},
        mode: 'create', // 'create' or 'edit'
        recordId: null,
        isSubmitting: false,
        
        open(mode = 'create', data = null) {
            this.mode = mode;
            this.title = mode === 'create' ? 'Create Record' : 'Edit Record';
            this.submitButtonText = mode === 'create' ? 'Create' : 'Update';
            
            if (data) {
                this.formData = {...data};
                this.recordId = data.id;
            } else {
                this.formData = {};
                this.recordId = null;
            }
            
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
        },
        
        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
        },
        
        async submitForm() {
            this.isSubmitting = true;
            try {
                // Dispatch event for parent to handle
                const event = new CustomEvent('form-submitted', {
                    detail: {
                        mode: this.mode,
                        data: this.formData,
                        id: this.recordId
                    }
                });
                window.dispatchEvent(event);
                
                this.close();
            } catch (error) {
                console.error('Form submission failed:', error);
                this.$dispatch('show-alert', {
                    type: 'error',
                    title: 'Error',
                    message: 'Failed to save record: ' + error.message
                });
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>