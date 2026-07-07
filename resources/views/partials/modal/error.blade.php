{{-- resources/views/reports/partials/modal/error.blade.php --}}
<div x-data="errorModal()" x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75 transition-opacity" @click="close()"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6 shadow-xl transform transition-all">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full" 
                     :class="type === 'error' ? 'bg-red-100 dark:bg-red-900/30' : (type === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-blue-100 dark:bg-blue-900/30')">
                    <svg x-show="type === 'error'" class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <svg x-show="type === 'warning'" class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <svg x-show="type === 'info'" class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white" x-text="title"></h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="message"></p>
            </div>
            
            <div class="mt-6 flex justify-center">
                <button @click="close()" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function errorModal() {
    return {
        isOpen: false,
        type: 'error',
        title: 'Error',
        message: 'An error occurred.',
        
        show(type = 'error', title = 'Error', message = 'An error occurred.') {
            this.type = type;
            this.title = title;
            this.message = message;
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
        },
        
        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
        }
    }
}
</script>