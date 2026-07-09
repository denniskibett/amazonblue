{{-- resources/views/partials/modal/alert-modal.blade.php --}}
<div x-data="alertModal()" 
     x-init="init()"
     x-show="isOpen" 
     x-cloak
     class="fixed inset-0 z-[999999] overflow-y-auto"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">
    
    <div class="flex items-center justify-center min-h-screen p-5">
        <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]" @click="close()"></div>
        
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10 z-50">
            <button @click="close()" 
                    class="group absolute right-3 top-3 z-999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
                <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""/>
                </svg>
            </button>

            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full"
                     :class="type === 'success' ? 'bg-green-100 dark:bg-green-900/30' : 
                             (type === 'error' ? 'bg-red-100 dark:bg-red-900/30' : 
                             (type === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-blue-100 dark:bg-blue-900/30'))">
                    <svg x-show="type === 'success'" class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg x-show="type === 'error'" class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <svg x-show="type === 'warning'" class="h-8 w-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <svg x-show="type === 'info'" class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white" x-text="title"></h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" x-text="message"></p>
            </div>
            
            <div class="mt-6 flex justify-center">
                <button @click="close()" 
                        class="flex justify-center px-6 py-2.5 text-sm font-medium text-white rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600 transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('alertModal', function() {
        return {
            isOpen: false,
            type: 'success',
            title: '',
            message: '',
            timeoutId: null,

            init() {
                // Ensure modal starts closed
                this.isOpen = false;
                
                window.addEventListener('show-alert', (event) => {
                    this.show(
                        event.detail.type || 'success',
                        event.detail.title || 'Success',
                        event.detail.message || 'Operation completed successfully.'
                    );
                });
            },

            show(type = 'success', title = 'Success', message = 'Operation completed successfully.') {
                this.type = type;
                this.title = title;
                this.message = message;
                this.isOpen = true;
                document.body.style.overflow = 'hidden';

                if (type === 'success') {
                    clearTimeout(this.timeoutId);
                    this.timeoutId = setTimeout(() => {
                        this.close();
                    }, 5000);
                }
            },

            close() {
                this.isOpen = false;
                document.body.style.overflow = '';
                clearTimeout(this.timeoutId);
            }
        };
    });
});
</script>