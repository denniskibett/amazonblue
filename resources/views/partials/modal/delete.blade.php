{{-- resources/views/partials/modal/delete-modal.blade.php --}}
<div x-data="deleteModal()" 
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
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white" x-text="title">Delete Investment</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" x-text="message">Are you sure you want to delete this investment? This action cannot be undone.</p>
            </div>
            
            <div class="mt-6 flex justify-center gap-3">
                <button @click="close()" 
                        class="flex justify-center px-6 py-2.5 text-sm font-medium text-gray-700 bg-white rounded-lg border border-gray-300 shadow-theme-xs hover:bg-gray-50 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                    Cancel
                </button>
                <button @click="confirmDelete()" 
                        :disabled="isDeleting"
                        class="flex justify-center px-6 py-2.5 text-sm font-medium text-white rounded-lg bg-red-600 shadow-theme-xs hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span x-show="!isDeleting">Delete</span>
                    <span x-show="isDeleting" class="flex items-center">
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
document.addEventListener('alpine:init', function() {
    Alpine.data('deleteModal', function() {
        return {
            isOpen: false,
            isDeleting: false,
            title: 'Delete Investment',
            message: 'Are you sure you want to delete this investment? This action cannot be undone.',
            itemId: null,
            itemName: '',

            init() {
                // Ensure modal starts closed
                this.isOpen = false;
                
                window.addEventListener('delete-investment', (event) => {
                    this.open(
                        event.detail.id,
                        event.detail.name || 'this investment'
                    );
                });
            },

            open(id, name) {
                this.itemId = id;
                this.itemName = name;
                this.title = `Delete ${name}`;
                this.message = `Are you sure you want to delete "${name}"? This action cannot be undone.`;
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
            },

            close() {
                this.isOpen = false;
                this.isDeleting = false;
                document.body.style.overflow = '';
            },

            async confirmDelete() {
                if (!this.itemId) return;
                
                this.isDeleting = true;

                try {
                    const response = await fetch(`/investments/destroy/${this.itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to delete investment');
                    }

                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: {
                            type: 'success',
                            title: 'Deleted!',
                            message: data.message || 'Investment deleted successfully.'
                        }
                    }));

                    this.close();
                    window.dispatchEvent(new CustomEvent('refresh-investments'));

                } catch (error) {
                    console.error('Error:', error);
                    window.dispatchEvent(new CustomEvent('show-alert', {
                        detail: {
                            type: 'error',
                            title: 'Error',
                            message: error.message || 'Failed to delete investment.'
                        }
                    }));
                } finally {
                    this.isDeleting = false;
                }
            }
        };
    });
});
</script>
