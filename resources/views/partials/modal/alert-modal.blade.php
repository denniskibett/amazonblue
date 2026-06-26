<div x-data="alertModal()"
     x-init="initAlertModal()"
     class="fixed inset-0 z-50"
     x-show="open"
     x-cloak>
    
    <!-- Backdrop -->
    <div class="fixed inset-0 modal-backdrop" @click="close()"></div>
    
    <!-- Modal Content -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all dark:bg-gray-900 modal-content">
            <!-- Icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full" 
                 :class="config.iconBg">
                <svg class="h-8 w-8" :class="config.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath"/>
                </svg>
            </div>

            <!-- Content -->
            <div class="mt-4 text-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="title"></h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="message"></p>
            </div>

            <!-- Buttons -->
            <div class="mt-6 flex justify-center gap-3">
                <button @click="confirm()" 
                    class="rounded-lg px-6 py-2.5 text-sm font-medium text-white hover:opacity-90 focus:ring-4 focus:ring-opacity-50"
                    :class="config.btnBg">
                    <span x-text="confirmText"></span>
                </button>
                <button x-show="showCancel" @click="cancel()" 
                    class="rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    <span x-text="cancelText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function alertModal() {
    return {
        open: false,
        title: 'Alert',
        message: '',
        confirmText: 'Okay',
        cancelText: 'Cancel',
        showCancel: false,
        config: {
            iconBg: 'bg-blue-100 dark:bg-blue-900/30',
            iconColor: 'text-blue-600 dark:text-blue-400',
            btnBg: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-300 dark:focus:ring-blue-800'
        },
        iconPath: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        resolve: null,
        
        initAlertModal() {
            window.addEventListener('show-alert', (event) => {
                const { type, message, title, confirmText, showCancel } = event.detail;
                this.show(type, message, title, confirmText, showCancel);
            });
            
            window.showAlert = (type, message, title, confirmText, showCancel) => {
                this.show(type, message, title, confirmText, showCancel);
            };
            
            window.showConfirm = (message, title) => {
                return new Promise((resolve) => {
                    this.resolve = resolve;
                    this.show('warning', message, title || 'Confirm', 'Confirm', true);
                });
            };
        },
        
        show(type, message, title = null, confirmText = 'Okay', showCancel = false) {
            const types = {
                success: {
                    icon: 'check-circle',
                    iconBg: 'bg-green-100 dark:bg-green-900/30',
                    iconColor: 'text-green-600 dark:text-green-400',
                    btnBg: 'bg-green-600 hover:bg-green-700 focus:ring-green-300 dark:focus:ring-green-800'
                },
                error: {
                    icon: 'x-circle',
                    iconBg: 'bg-red-100 dark:bg-red-900/30',
                    iconColor: 'text-red-600 dark:text-red-400',
                    btnBg: 'bg-red-600 hover:bg-red-700 focus:ring-red-300 dark:focus:ring-red-800'
                },
                warning: {
                    icon: 'exclamation-triangle',
                    iconBg: 'bg-yellow-100 dark:bg-yellow-900/30',
                    iconColor: 'text-yellow-600 dark:text-yellow-400',
                    btnBg: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-300 dark:focus:ring-yellow-800'
                },
                info: {
                    icon: 'information-circle',
                    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
                    iconColor: 'text-blue-600 dark:text-blue-400',
                    btnBg: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-300 dark:focus:ring-blue-800'
                }
            };
            
            const icons = {
                'check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'x-circle': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                'exclamation-triangle': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z',
                'information-circle': 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
            };
            
            const config = types[type] || types.info;
            this.config = config;
            this.iconPath = icons[config.icon] || icons['information-circle'];
            this.title = title || type.charAt(0).toUpperCase() + type.slice(1);
            this.message = message;
            this.confirmText = confirmText || 'Okay';
            this.showCancel = showCancel || false;
            this.open = true;
            document.body.style.overflow = 'hidden';
        },
        
        confirm() {
            this.close();
            if (this.resolve) {
                this.resolve(true);
                this.resolve = null;
            }
        },
        
        cancel() {
            this.close();
            if (this.resolve) {
                this.resolve(false);
                this.resolve = null;
            }
        },
        
        close() {
            this.open = false;
            document.body.style.overflow = '';
        }
    };
}
</script>