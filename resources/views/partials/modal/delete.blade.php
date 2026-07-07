{{-- resources/views/reports/partials/modal/delete.blade.php --}}
<div x-data="deleteModal()" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity" @click="close()"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6 shadow-xl transform transition-all">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white" x-text="title">Delete Record</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="message">Are you sure you want to delete this record? This action cannot be undone.</p>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <button @click="close()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
                <button @click="confirmDelete()" 
                        :disabled="isSubmitting"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span x-show="!isSubmitting">Delete</span>
                    <span x-show="isSubmitting" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteModal() {
    return {
        isOpen: false,
        title: 'Delete Record',
        message: 'Are you sure you want to delete this record? This action cannot be undone.',
        recordId: null,
        isSubmitting: false,
        
        open(title = 'Delete Record', message = 'Are you sure you want to delete this record? This action cannot be undone.', recordId = null) {
            this.title = title;
            this.message = message;
            this.recordId = recordId;
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
        },
        
        close() {
            this.isOpen = false;
            this.isSubmitting = false;
            document.body.style.overflow = '';
        },
        
        async confirmDelete() {
            this.isSubmitting = true;
            try {
                // Dispatch event for parent to handle
                const event = new CustomEvent('delete-confirmed', {
                    detail: { id: this.recordId }
                });
                window.dispatchEvent(event);
                
                // Show success
                this.$dispatch('show-alert', {
                    type: 'success',
                    title: 'Deleted!',
                    message: 'Record deleted successfully.'
                });
                
                this.close();
            } catch (error) {
                console.error('Delete failed:', error);
                this.$dispatch('show-alert', {
                    type: 'error',
                    title: 'Error',
                    message: 'Failed to delete record: ' + error.message
                });
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>