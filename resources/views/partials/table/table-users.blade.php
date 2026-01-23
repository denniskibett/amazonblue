@php
    // Define $users at the very top based on user role
    if (auth()->user()->role === 'admin') {
        $users = $users ?? [];
    } elseif (auth()->user()->role === 'broker') {
        // Brokers only see their clients
        $users = $brokerClients ?? [];
    } elseif (auth()->user()->role === 'teller') {
        $users = $activeUsers ?? [];
    } else {
        // Regular users shouldn't see this page, but just in case
        $users = [];
    }
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6"
     x-data="userTable()"
     x-init="init()">
  
  <!-- CREATE USER MODAL -->
  <div x-show="isCreateModalOpen" 
       x-cloak
       class="fixed inset-0 flex items-center justify-center p-5 overflow-y-auto modal z-[99999]"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    
    <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]" 
         @click="closeCreateModal()"></div>
    
    <div class="relative w-full max-w-2xl rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10"
         @click.outside="closeCreateModal()">
      
      <!-- Close button -->
      <button @click="closeCreateModal()"
              class="group absolute right-3 top-3 z-99999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
        <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200"
             width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" 
                d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" />
        </svg>
      </button>

      <!-- Modal Form -->
      <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">
        Create New User
      </h4>
      
      <!-- Success/Error Messages -->
      <div x-show="message" 
           x-text="message"
           :class="messageType === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
           class="p-3 mb-4 rounded-lg"
           x-transition></div>

      <form @submit.prevent="submitForm" x-ref="createUserForm">
        @csrf
        <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
          
          <!-- Basic Information -->
          <div class="col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              Full Name *
            </label>
            <input type="text"
                   x-model="formData.name"
                   placeholder="John Doe"
                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                   required />
          </div>

          <div class="col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              Email Address *
            </label>
            <input type="email"
                   x-model="formData.email"
                   placeholder="user@example.com"
                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                   required />
          </div>

          <div class="col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              Phone Number *
            </label>
            <input type="tel"
                   x-model="formData.phone"
                   placeholder="+254 712 345 678"
                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                   required />
          </div>

          <div class="col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              Role *
            </label>
            <select x-model="formData.role"
                    @change="toggleRoleFields()"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    required>
              <option value="">Select Role</option>
              <option value="admin">Admin</option>
              <option value="broker">Broker</option>
              <option value="borrower">Borrower</option>
              <option value="teller">Teller</option>
            </select>
          </div>

          <div class="col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              Password *
            </label>
            <input type="password"
                   x-model="formData.password"
                   placeholder="Minimum 6 characters"
                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                   minlength="6"
                   required />
          </div>

          <!-- Broker Fields -->
          <template x-if="formData.role === 'broker'">
            <div class="col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Certificate Number *
                </label>
                <input type="text"
                       x-model="formData.cert_no"
                       placeholder="e.g., CERT12345"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>

              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Client Interest Rate (%) *
                </label>
                <input type="number"
                       x-model="formData.interest_client"
                       placeholder="e.g., 5.5"
                       step="0.01"
                       min="0"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>

              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Broker Interest Rate (%) *
                </label>
                <input type="number"
                       x-model="formData.interest_broker"
                       placeholder="e.g., 2.5"
                       step="0.01"
                       min="0"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>

              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Client Penalty Rate (%) *
                </label>
                <input type="number"
                       x-model="formData.penalty_client"
                       placeholder="e.g., 1.5"
                       step="0.01"
                       min="0"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>

              <div class="col-span-1 sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Broker Penalty Rate (%) *
                </label>
                <input type="number"
                       x-model="formData.penalty_broker"
                       placeholder="e.g., 0.5"
                       step="0.01"
                       min="0"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>
            </div>
          </template>

          <!-- Borrower Fields -->
          <template x-if="formData.role === 'borrower'">
            <div class="col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  National ID *
                </label>
                <input type="text"
                       x-model="formData.national_id"
                       placeholder="e.g., 12345678"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>

              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Client Type *
                </label>
                <select x-model="formData.client_type"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                  <option value="0">Our Client</option>
                  <option value="1">Broker Client</option>
                </select>
              </div>

              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Status *
                </label>
                <select x-model="formData.status"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
            </div>
          </template>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end w-full gap-3 mt-8">
          <button type="button"
                  @click="closeCreateModal()"
                  class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 sm:w-auto"
                  :disabled="isSubmitting">
            Cancel
          </button>
          <button type="submit"
                  class="flex justify-center w-full px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600 sm:w-auto"
                  :disabled="isSubmitting"
                  :class="{'opacity-50 cursor-not-allowed': isSubmitting}">
            <span x-show="!isSubmitting">Create User</span>
            <span x-show="isSubmitting" class="flex items-center gap-2">
              <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Creating...
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT USER MODAL -->
  <div x-show="isEditModalOpen" 
       x-cloak
       class="fixed inset-0 flex items-center justify-center p-5 overflow-y-auto modal z-[99999]"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    
    <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]" 
         @click="closeEditModal()"></div>
    
    <div class="relative w-full max-w-2xl rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10"
         @click.outside="closeEditModal()">
      
      <!-- Close button -->
      <button @click="closeEditModal()"
              class="group absolute right-3 top-3 z-999999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
        <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200"
             width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" 
                d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" />
        </svg>
      </button>

      <!-- Modal Form -->
      <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">
        Edit User
      </h4>
      
      <!-- Success/Error Messages -->
      <div x-show="editMessage" 
           x-text="editMessage"
           :class="editMessageType === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
           class="p-3 mb-4 rounded-lg"
           x-transition></div>

      <form @submit.prevent="submitEditForm" x-ref="editUserForm">
        @csrf
        @method('PUT')
        <input type="hidden" x-model="editFormData.id">
        
        <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
          
          <!-- Basic Information -->
          <div class="col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              Full Name *
            </label>
            <input type="text"
                   x-model="editFormData.name"
                   placeholder="John Doe"
                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                   required />
          </div>

          <div class="col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              Email Address *
            </label>
            <input type="email"
                   x-model="editFormData.email"
                   placeholder="user@example.com"
                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                   required />
          </div>

          <div class="col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              Phone Number *
            </label>
            <input type="tel"
                   x-model="editFormData.phone"
                   placeholder="+254 712 345 678"
                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                   required />
          </div>

          <div class="col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              Role *
            </label>
            <select x-model="editFormData.role"
                    @change="toggleEditRoleFields()"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    required>
              <option value="">Select Role</option>
              <option value="admin">Admin</option>
              <option value="broker">Broker</option>
              <option value="borrower">Borrower</option>
              <option value="teller">Teller</option>
            </select>
          </div>

          <div class="col-span-1">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
              Password (leave blank to keep current)
            </label>
            <input type="password"
                   x-model="editFormData.password"
                   placeholder="Minimum 6 characters"
                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                   minlength="6" />
          </div>

          <!-- Broker Fields -->
          <template x-if="editFormData.role === 'broker'">
            <div class="col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Certificate Number *
                </label>
                <input type="text"
                       x-model="editFormData.cert_no"
                       placeholder="e.g., CERT12345"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>

              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Client Interest Rate (%) *
                </label>
                <input type="number"
                       x-model="editFormData.interest_client"
                       placeholder="e.g., 5.5"
                       step="0.01"
                       min="0"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>

              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Broker Interest Rate (%) *
                </label>
                <input type="number"
                       x-model="editFormData.interest_broker"
                       placeholder="e.g., 2.5"
                       step="0.01"
                       min="0"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>

              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Client Penalty Rate (%) *
                </label>
                <input type="number"
                       x-model="editFormData.penalty_client"
                       placeholder="e.g., 1.5"
                       step="0.01"
                       min="0"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>

              <div class="col-span-1 sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Broker Penalty Rate (%) *
                </label>
                <input type="number"
                       x-model="editFormData.penalty_broker"
                       placeholder="e.g., 0.5"
                       step="0.01"
                       min="0"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>
            </div>
          </template>

          <!-- Borrower Fields -->
          <template x-if="editFormData.role === 'borrower'">
            <div class="col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  National ID *
                </label>
                <input type="text"
                       x-model="editFormData.national_id"
                       placeholder="e.g., 12345678"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                       required />
              </div>

              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Client Type *
                </label>
                <select x-model="editFormData.client_type"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                  <option value="0">Our Client</option>
                  <option value="1">Broker Client</option>
                </select>
              </div>

              <div class="col-span-1">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                  Status *
                </label>
                <select x-model="editFormData.status"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
            </div>
          </template>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end w-full gap-3 mt-8">
          <button type="button"
                  @click="closeEditModal()"
                  class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 sm:w-auto"
                  :disabled="isEditSubmitting">
            Cancel
          </button>
          <button type="submit"
                  class="flex justify-center w-full px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600 sm:w-auto"
                  :disabled="isEditSubmitting"
                  :class="{'opacity-50 cursor-not-allowed': isEditSubmitting}">
            <span x-show="!isEditSubmitting">Update User</span>
            <span x-show="isEditSubmitting" class="flex items-center gap-2">
              <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Updating...
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <div x-show="isDeleteModalOpen" 
       x-cloak
       class="fixed inset-0 flex items-center justify-center p-5 overflow-y-auto modal z-[99999]"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    
    <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]" 
         @click="closeDeleteModal()"></div>
    
    <div class="relative w-full max-w-md rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10"
         @click.outside="closeDeleteModal()">
      
      <!-- Close button -->
      <button @click="closeDeleteModal()"
              class="group absolute right-3 top-3 z-99999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
        <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200"
             width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" 
                d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" />
        </svg>
      </button>

      <!-- Delete Confirmation -->
      <div class="text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
          <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        
        <h4 class="mb-2 text-lg font-medium text-gray-800 dark:text-white/90">
          Confirm Delete
        </h4>
        
        <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
          Are you sure you want to delete <span x-text="deleteUserName" class="font-semibold"></span>? 
          This action cannot be undone.
        </p>
        
        <div class="flex items-center justify-center gap-3">
          <button type="button"
                  @click="closeDeleteModal()"
                  class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 sm:w-auto"
                  :disabled="isDeleteSubmitting">
            Cancel
          </button>
          <button type="button"
                  @click="confirmDelete()"
                  class="flex justify-center w-full px-4 py-3 text-sm font-medium text-white rounded-lg bg-red-500 shadow-theme-xs hover:bg-red-600 sm:w-auto"
                  :disabled="isDeleteSubmitting"
                  :class="{'opacity-50 cursor-not-allowed': isDeleteSubmitting}">
            <span x-show="!isDeleteSubmitting">Delete User</span>
            <span x-show="isDeleteSubmitting" class="flex items-center gap-2">
              <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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

  <!-- Table Header -->
  <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Users Overview</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Showing <span id="showingStart">1</span> to <span id="showingEnd">10</span> of <span id="totalCount">{{ count($users) }}</span> entries
      </p>
    </div>
    
    <div class="flex flex-wrap items-center gap-3">
      <div class="flex items-center">
        <label for="entriesPerPage" class="text-sm text-gray-500 dark:text-gray-400 mr-2 hidden sm:inline">Show:</label>
        <div class="relative">
          <select id="entriesPerPage" class="appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 pr-8">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <div class="absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none text-gray-400 dark:text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
      </div>
      
      <div class="relative flex-1 min-w-[150px]">
        <input type="text" id="userSearch" placeholder="Search users..." class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 pl-10">
        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>
      
      @if(auth()->user()->role === 'admin')
        <button @click="openCreateModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Create New User
        </button>
      @endif
    </div>
  </div>

  <!-- Table -->
  <div class="w-full overflow-x-auto">
    <table class="min-w-full" id="usersTable">
      <!-- Desktop table header -->
      <thead class="hidden sm:table-header-group">
        <tr class="border-gray-100 border-y dark:border-gray-800">
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortTable(0)">
            <div class="flex items-center justify-between">
              <span>User</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortTable(1)">
            <div class="flex items-center justify-between">
              <span>Contact</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortTable(2)">
            <div class="flex items-center justify-between">
              <span>Role</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortTable(3)">
            <div class="flex items-center justify-between">
              <span>Profile Completion</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortTable(4)">
            <div class="flex items-center justify-between">
              <span>Loan Stats</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortTable(5)">
            <div class="flex items-center justify-center">
              <span>Status</span>
              <span class="sort-icon text-gray-400 ml-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
            Actions
          </th>
        </tr>
      </thead>
      
      <!-- Mobile table header -->
      <thead class="sm:hidden">
        <tr class="border-gray-100 border-y dark:border-gray-800">
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            User
          </th>
          <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
            Status
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Actions
          </th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="usersTableBody">
        @forelse($users as $user)
        @php
            $completionPercentage = $user->getBiodataCompletionPercentage();
            $totalLoans = $user->loans->sum('amount');
            $totalRepaid = $user->repayments->sum('amount');
            $activeLoans = $user->loans->where('status', 'active')->count();
            
            // Clean phone number
            $phoneNumber = $user->phone ?? 'N/A';
            if ($phoneNumber !== 'N/A') {
                $phoneNumber = \App\Helpers\PhoneHelper::cleanPhoneNumber($phoneNumber);
            }
        @endphp
        <tr class="user-row hover:bg-gray-50 transition duration-150" data-user-id="{{ $user->id }}" data-user-data="{{ json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'national_id' => $user->borrower->national_id ?? '',
            'client_type' => $user->borrower->client_type ?? '0',
            'status' => $user->status ?? '1',
            'cert_no' => $user->broker->cert_no ?? '',
            'interest_client' => $user->broker->interest_client ?? '',
            'interest_broker' => $user->broker->interest_broker ?? '',
            'penalty_client' => $user->broker->penalty_client ?? '',
            'penalty_broker' => $user->broker->penalty_broker ?? '',
        ]) }}">
          <!-- Desktop cells -->
          <td class="py-3 hidden sm:table-cell">
            <div class="flex items-center gap-3">
              <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                <a href="{{ route('users.show', $user->id) }}">
                  @if($user->profile_photo_path)
                    <img src="{{ asset('storage/' . $user->profile_photo_path) }}" 
                         alt="{{ $user->name }}" 
                         class="h-10 w-10 rounded-full object-cover">
                  @else
                    <span class="text-indigo-600 font-medium">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                  @endif
                </a>    
              </div>
              <div>
                <a href="{{ route('users.show', $user->id) }}" class="user-name">
                  <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                    {{ $user->name }}
                  </p>
                </a>
                <span class="text-gray-500 text-theme-xs dark:text-gray-400 user-email">
                  {{ $user->email }}
                </span>
              </div>
            </div>
          </td>

          <td class="py-3 hidden sm:table-cell">
            <div>
              <p class="text-gray-800 text-theme-sm dark:text-white/90 user-phone">
                {{ $phoneNumber }}
              </p>
              @if($user->id_number)
              <span class="text-gray-500 text-theme-xs dark:text-gray-400 user-id">
                ID: {{ $user->id_number }}
              </span>
              @endif
            </div>
          </td>
          
          <td class="py-3 hidden sm:table-cell">
            <span class="user-role inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                @if($user->role === 'admin') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                @elseif($user->role === 'broker') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                @elseif($user->role === 'borrower') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                @elseif($user->role === 'teller') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif" 
                data-sort-value="{{ $user->role }}">
              {{ ucfirst($user->role) }}
            </span>
          </td>

          <!-- Profile Completion -->
          <td class="py-3 hidden sm:table-cell">
            <div class="w-32">
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $completionPercentage }}%</span>
                @if($user->role === 'borrower')
                  <span class="text-xs {{ $completionPercentage >= 80 ? 'text-green-600' : 'text-yellow-600' }}">
                    {{ $completionPercentage >= 80 ? 'Eligible' : 'Incomplete' }}
                  </span>
                @endif
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                <div class="h-2 rounded-full {{ $completionPercentage >= 80 ? 'bg-green-500' : ($completionPercentage >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                     style="width: {{ $completionPercentage }}%"></div>
              </div>
            </div>
          </td>

          <!-- Loan Stats -->
          <td class="py-3 hidden sm:table-cell">
            @if($user->role === 'borrower')
            <div class="space-y-1">
              <div class="flex justify-between text-xs">
                <span class="text-gray-500">Borrowed:</span>
                <span class="font-medium">KES {{ number_format($totalLoans, 2) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-gray-500">Active Loans:</span>
                <span class="font-medium {{ $activeLoans > 0 ? 'text-orange-600' : 'text-gray-600' }}">
                  {{ $activeLoans }}
                </span>
              </div>
            </div>
            @else
            <span class="text-gray-400 text-sm">N/A</span>
            @endif
          </td>
          
          <!-- Status Column - Centered -->
          <td class="py-3 hidden sm:table-cell">
            <div class="flex justify-center">
              <span class="user-status rounded-full px-2.5 py-0.5 text-xs font-medium 
                  @if($user->status == 0) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                  @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif" 
                  data-sort-value="{{ $user->status }}">
                {{ $user->status == 0 ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </td>
          
          <td class="py-3 text-right">
            <div class="flex justify-end space-x-3">
              <a href="{{ route('users.show', $user->id) }}" class="text-blue-600 hover:text-blue-900" title="View">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              </a>
              
              @if(auth()->user()->role === 'admin')
              <button @click="openEditModal({{ $user->id }})" class="text-green-600 hover:text-green-900" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
              </button>
              
              <button @click="openDeleteModal({{ $user->id }}, '{{ addslashes($user->name) }}')" class="text-red-600 hover:text-red-900" title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
              </button>
              @endif
            </div>
          </td>
          
          <!-- Mobile cells (simplified view) -->
          <td class="py-3 sm:hidden">
            <div class="flex items-center gap-3">
              <div class="h-[40px] w-[40px] overflow-hidden rounded-md bg-indigo-100 flex items-center justify-center">
                @if($user->profile_photo_path)
                  <img src="{{ asset('storage/' . $user->profile_photo_path) }}" 
                       alt="{{ $user->name }}" 
                       class="h-10 w-10 rounded-full object-cover">
                @else
                  <span class="text-indigo-600 font-medium">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @endif
              </div>
              <div>
                <a href="{{ route('users.show', $user->id) }}" class="user-name">
                  <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                    {{ $user->name }}
                  </p>
                </a>
                <span class="text-gray-500 text-theme-xs dark:text-gray-400 user-email">
                  {{ $user->email }}
                </span>
                @if($user->role === 'borrower')
                <div class="flex items-center mt-1">
                  <div class="w-16 bg-gray-200 rounded-full h-1.5 mr-2">
                    <div class="h-1.5 rounded-full {{ $completionPercentage >= 80 ? 'bg-green-500' : ($completionPercentage >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                         style="width: {{ $completionPercentage }}%"></div>
                  </div>
                  <span class="text-xs text-gray-500">{{ $completionPercentage }}%</span>
                </div>
                @endif
              </div>
            </div>
          </td>
          
          <!-- Mobile Status Column - Centered -->
          <td class="py-3 sm:hidden">
            <div class="flex flex-col items-center space-y-1">
              <span class="user-status rounded-full px-2 py-0.5 text-xs font-medium 
                  @if($user->status == 0) bg-green-100 text-green-800
                  @else bg-red-100 text-red-800 @endif">
                {{ $user->status == 0 ? 'Active' : 'Inactive' }}
              </span>
              @if($user->role === 'borrower')
              <div class="text-xs text-gray-500 text-center">
                {{ $activeLoans }} active loans
              </div>
              @endif
            </div>
          </td>
          
          <td class="py-3 sm:hidden text-right">
            <div class="flex justify-end space-x-2">
              <a href="{{ route('users.show', $user->id) }}" class="text-blue-600 hover:text-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              </a>
              @if(auth()->user()->role === 'admin')
              <button @click="openEditModal({{ $user->id }})" class="text-green-600 hover:text-green-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
              </button>
              <button @click="openDeleteModal({{ $user->id }}, '{{ addslashes($user->name) }}')" class="text-red-600 hover:text-red-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
              </button>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="py-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No users found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria</p>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
    
    <!-- Pagination -->
    <div class="flex flex-col items-center justify-between px-2 py-4 sm:flex-row sm:px-0">
      <div class="hidden sm:flex">
        <p class="text-sm text-gray-700 dark:text-gray-400">
          Showing <span id="paginationStart">1</span> to <span id="paginationEnd">10</span> of <span id="paginationTotal">{{ count($users) }}</span> results
        </p>
      </div>
      <div class="flex-1 flex justify-between sm:justify-end">
        <button id="prevPage" class="relative inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
          Previous
        </button>
        <div id="paginationNumbers" class="hidden sm:flex">
          <!-- Page numbers will be inserted here -->
        </div>
        <button id="nextPage" class="relative ml-3 inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
          Next
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function userTable() {
    return {
        // Modal states
        isCreateModalOpen: false,
        isEditModalOpen: false,
        isDeleteModalOpen: false,
        
        // Form states
        isSubmitting: false,
        isEditSubmitting: false,
        isDeleteSubmitting: false,
        
        // Messages
        message: '',
        messageType: '',
        editMessage: '',
        editMessageType: '',
        
        // Form data
        formData: {
            name: '',
            email: '',
            phone: '',
            role: '',
            password: '',
            // Broker fields
            cert_no: '',
            interest_client: '',
            interest_broker: '',
            penalty_client: '',
            penalty_broker: '',
            // Borrower fields (broker_id REMOVED)
            national_id: '',
            client_type: '0',
            status: '1',
        },
        
        // Edit form data
        editFormData: {
            id: '',
            name: '',
            email: '',
            phone: '',
            role: '',
            password: '',
            // Broker fields
            cert_no: '',
            interest_client: '',
            interest_broker: '',
            penalty_client: '',
            penalty_broker: '',
            // Borrower fields (broker_id REMOVED)
            national_id: '',
            client_type: '0',
            status: '1',
        },
        
        // Delete data
        deleteUserId: null,
        deleteUserName: '',
        
        // Table data and state
        currentPage: 1,
        entriesPerPage: 10,
        currentSortColumn: null,
        sortDirection: 1,
        allUsers: [],
        filteredUsers: [],
        
        // Initialize function
        init() {
            this.$nextTick(() => {
                // Initialize table data
                this.allUsers = Array.from(document.querySelectorAll('.user-row'));
                this.filteredUsers = [...this.allUsers];
                this.entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
                
                // Initialize table
                this.initializeTable();
                this.attachEventListeners();
            });
        },
        
        // ============= CREATE MODAL FUNCTIONS =============
        openCreateModal() {
            this.resetForm();
            this.isCreateModalOpen = true;
            setTimeout(() => {
                const modal = document.querySelector('[x-show="isCreateModalOpen"]');
                if (modal) modal.scrollTop = 0;
            }, 100);
        },
        
        closeCreateModal() {
            this.isCreateModalOpen = false;
            this.resetForm();
        },
        
        resetForm() {
            this.formData = {
                name: '',
                email: '',
                phone: '',
                role: '',
                password: '',
                cert_no: '',
                interest_client: '',
                interest_broker: '',
                penalty_client: '',
                penalty_broker: '',
                national_id: '',
                client_type: '0',
                status: '1',
            };
            this.message = '';
            this.messageType = '';
            this.isSubmitting = false;
        },
        
        toggleRoleFields() {
            // This function is triggered when role changes
            // Alpine's conditional rendering handles the UI
        },
        
        async submitForm() {
            this.isSubmitting = true;
            this.message = '';
            this.messageType = '';
            
            try {
                // Basic validation
                if (!this.validateForm()) {
                    throw new Error('Please fill in all required fields correctly.');
                }
                
                // Prepare form data
                const formData = new FormData();
                for (const key in this.formData) {
                    if (this.formData[key] !== null && this.formData[key] !== undefined) {
                        formData.append(key, this.formData[key]);
                    }
                }
                
                // Add CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (csrfToken) {
                    formData.append('_token', csrfToken);
                }
                
                // Send request
                const response = await fetch('{{ route("users.store") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    // Handle validation errors
                    if (data.errors) {
                        const errorMessages = Object.values(data.errors).flat();
                        throw new Error(errorMessages.join(', '));
                    }
                    throw new Error(data.message || 'Something went wrong');
                }
                
                // Success
                this.message = data.message || 'User created successfully!';
                this.messageType = 'success';
                
                // Reload page after success to show new user
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                
            } catch (error) {
                this.message = error.message;
                this.messageType = 'error';
                // Scroll to error message
                this.$nextTick(() => {
                    const modal = document.querySelector('[x-show="isCreateModalOpen"]');
                    if (modal) modal.scrollTop = 0;
                });
            } finally {
                this.isSubmitting = false;
            }
        },
        
        // ============= EDIT MODAL FUNCTIONS =============
        openEditModal(userId) {
            const row = document.querySelector(`.user-row[data-user-id="${userId}"]`);
            if (row) {
                const userData = JSON.parse(row.getAttribute('data-user-data'));
                this.editFormData = {
                    id: userData.id,
                    name: userData.name,
                    email: userData.email,
                    phone: userData.phone,
                    role: userData.role,
                    password: '',
                    // Broker fields
                    cert_no: userData.cert_no || '',
                    interest_client: userData.interest_client || '',
                    interest_broker: userData.interest_broker || '',
                    penalty_client: userData.penalty_client || '',
                    penalty_broker: userData.penalty_broker || '',
                    // Borrower fields (broker_id REMOVED)
                    national_id: userData.national_id || '',
                    client_type: userData.client_type || '0',
                    status: userData.status || '1',
                };
                this.isEditModalOpen = true;
                this.editMessage = '';
                this.editMessageType = '';
            }
        },
        
        closeEditModal() {
            this.isEditModalOpen = false;
            this.editFormData = {
                id: '',
                name: '',
                email: '',
                phone: '',
                role: '',
                password: '',
                cert_no: '',
                interest_client: '',
                interest_broker: '',
                penalty_client: '',
                penalty_broker: '',
                national_id: '',
                client_type: '0',
                status: '1',
            };
        },
        
        toggleEditRoleFields() {
            // This function is triggered when role changes in edit modal
        },
        
        async submitEditForm() {
            this.isEditSubmitting = true;
            this.editMessage = '';
            this.editMessageType = '';
            
            try {
                // Prepare form data
                const formData = new FormData();
                for (const key in this.editFormData) {
                    if (this.editFormData[key] !== null && this.editFormData[key] !== undefined) {
                        formData.append(key, this.editFormData[key]);
                    }
                }
                
                // Add CSRF token and method spoofing
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (csrfToken) {
                    formData.append('_token', csrfToken);
                    formData.append('_method', 'PUT');
                }
                
                // Send request
                const response = await fetch(`/users/${this.editFormData.id}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    // Handle validation errors
                    if (data.errors) {
                        const errorMessages = Object.values(data.errors).flat();
                        throw new Error(errorMessages.join(', '));
                    }
                    throw new Error(data.message || 'Something went wrong');
                }
                
                // Success
                this.editMessage = data.message || 'User updated successfully!';
                this.editMessageType = 'success';
                
                // Reload page after success
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                
            } catch (error) {
                this.editMessage = error.message;
                this.editMessageType = 'error';
                // Scroll to error message
                this.$nextTick(() => {
                    const modal = document.querySelector('[x-show="isEditModalOpen"]');
                    if (modal) modal.scrollTop = 0;
                });
            } finally {
                this.isEditSubmitting = false;
            }
        },
        
        // ============= DELETE MODAL FUNCTIONS =============
        openDeleteModal(userId, userName) {
            this.deleteUserId = userId;
            this.deleteUserName = userName;
            this.isDeleteModalOpen = true;
        },
        
        closeDeleteModal() {
            this.isDeleteModalOpen = false;
            this.deleteUserId = null;
            this.deleteUserName = '';
            this.isDeleteSubmitting = false;
        },
        
        async confirmDelete() {
            this.isDeleteSubmitting = true;
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                
                // Send request
                const response = await fetch(`/users/${this.deleteUserId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        _token: csrfToken,
                        _method: 'DELETE'
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Something went wrong');
                }
                
                // Success - reload page
                window.location.reload();
                
            } catch (error) {
                alert('Error deleting user: ' + error.message);
                this.closeDeleteModal();
            } finally {
                this.isDeleteSubmitting = false;
            }
        },
        
        // ============= FORM VALIDATION =============
        validateForm() {
            // Basic required fields validation
            const requiredFields = ['name', 'email', 'phone', 'role', 'password'];
            for (const field of requiredFields) {
                if (!this.formData[field]?.trim()) {
                    this.message = `Please fill in the ${field.replace('_', ' ')} field`;
                    this.messageType = 'error';
                    return false;
                }
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.formData.email)) {
                this.message = 'Please enter a valid email address';
                this.messageType = 'error';
                return false;
            }
            
            // Password validation
            if (this.formData.password.length < 6) {
                this.message = 'Password must be at least 6 characters long';
                this.messageType = 'error';
                return false;
            }
            
            // Role-specific validation
            if (this.formData.role === 'broker') {
                const brokerFields = ['cert_no', 'interest_client', 'interest_broker', 'penalty_client', 'penalty_broker'];
                for (const field of brokerFields) {
                    if (!this.formData[field] && this.formData[field] !== 0) {
                        this.message = `Please fill in the ${field.replace('_', ' ')} field for broker`;
                        this.messageType = 'error';
                        return false;
                    }
                }
            }
            
            if (this.formData.role === 'borrower') {
                if (!this.formData.national_id?.trim()) {
                    this.message = 'Please enter National ID for borrower';
                    this.messageType = 'error';
                    return false;
                }
            }
            
            return true;
        },
        
        // ============= TABLE FUNCTIONS =============
        initializeTable() {
            this.updateTable();
            this.updatePaginationNumbers();
        },
        
        attachEventListeners() {
            const searchInput = document.getElementById('userSearch');
            const entriesPerPageSelect = document.getElementById('entriesPerPage');
            const prevPageBtn = document.getElementById('prevPage');
            const nextPageBtn = document.getElementById('nextPage');
            
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    this.currentPage = 1;
                    this.filterUsers();
                    this.updateTable();
                });
            }
            
            if (entriesPerPageSelect) {
                entriesPerPageSelect.addEventListener('change', () => {
                    this.entriesPerPage = parseInt(entriesPerPageSelect.value);
                    this.currentPage = 1;
                    this.updateTable();
                });
            }
            
            if (prevPageBtn) {
                prevPageBtn.addEventListener('click', () => {
                    if (this.currentPage > 1) {
                        this.currentPage--;
                        this.updateTable();
                    }
                });
            }
            
            if (nextPageBtn) {
                nextPageBtn.addEventListener('click', () => {
                    const totalPages = Math.ceil(this.filteredUsers.length / this.entriesPerPage);
                    if (this.currentPage < totalPages) {
                        this.currentPage++;
                        this.updateTable();
                    }
                });
            }
        },
        
        filterUsers() {
            const searchInput = document.getElementById('userSearch');
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            
            if (searchTerm === '') {
                this.filteredUsers = [...this.allUsers];
            } else {
                this.filteredUsers = this.allUsers.filter(row => {
                    const name = row.querySelector('.user-name')?.textContent.toLowerCase() || '';
                    const email = row.querySelector('.user-email')?.textContent.toLowerCase() || '';
                    const phone = row.querySelector('.user-phone')?.textContent.toLowerCase() || '';
                    const id = row.querySelector('.user-id')?.textContent.toLowerCase() || '';
                    const role = row.querySelector('.user-role')?.textContent.toLowerCase() || '';
                    const status = row.querySelector('.user-status')?.textContent.toLowerCase() || '';
                    
                    return name.includes(searchTerm) || 
                           email.includes(searchTerm) || 
                           phone.includes(searchTerm) || 
                           id.includes(searchTerm) || 
                           role.includes(searchTerm) || 
                           status.includes(searchTerm);
                });
            }
            
            // Update total count display
            const totalCount = document.getElementById('totalCount');
            const paginationTotal = document.getElementById('paginationTotal');
            if (totalCount) totalCount.textContent = this.filteredUsers.length;
            if (paginationTotal) paginationTotal.textContent = this.filteredUsers.length;
            
            // Apply sorting if any column is sorted
            if (this.currentSortColumn !== null) {
                this.sortTable(this.currentSortColumn, true);
            }
        },
        
        updateTable() {
            const startIndex = (this.currentPage - 1) * this.entriesPerPage;
            const endIndex = startIndex + this.entriesPerPage;
            const paginatedUsers = this.filteredUsers.slice(startIndex, endIndex);
            
            // Hide all rows first
            this.allUsers.forEach(row => row.style.display = 'none');
            
            // Show only paginated rows
            paginatedUsers.forEach(row => row.style.display = '');
            
            // Update counters
            const total = this.filteredUsers.length;
            const showing = paginatedUsers.length;
            
            const showingStart = document.getElementById('showingStart');
            const showingEnd = document.getElementById('showingEnd');
            const paginationStart = document.getElementById('paginationStart');
            const paginationEnd = document.getElementById('paginationEnd');
            
            if (showingStart) showingStart.textContent = startIndex + 1;
            if (showingEnd) showingEnd.textContent = Math.min(endIndex, total);
            if (paginationStart) paginationStart.textContent = startIndex + 1;
            if (paginationEnd) paginationEnd.textContent = Math.min(endIndex, total);
            
            // Update pagination buttons
            const prevPageBtn = document.getElementById('prevPage');
            const nextPageBtn = document.getElementById('nextPage');
            
            if (prevPageBtn) prevPageBtn.disabled = this.currentPage === 1;
            if (nextPageBtn) nextPageBtn.disabled = this.currentPage === Math.ceil(total / this.entriesPerPage);
            
            // Update pagination numbers
            this.updatePaginationNumbers();
        },
        
        updatePaginationNumbers() {
            const paginationNumbers = document.getElementById('paginationNumbers');
            if (!paginationNumbers) return;
            
            const totalPages = Math.ceil(this.filteredUsers.length / this.entriesPerPage);
            paginationNumbers.innerHTML = '';
            
            if (totalPages <= 1) return;
            
            // Always show first page
            this.addPageNumber(1);
            
            // Show ellipsis if needed
            if (this.currentPage > 3) {
                this.addEllipsis();
            }
            
            // Show current page and neighbors
            const startPage = Math.max(2, this.currentPage - 1);
            const endPage = Math.min(totalPages - 1, this.currentPage + 1);
            
            for (let i = startPage; i <= endPage; i++) {
                this.addPageNumber(i);
            }
            
            // Show ellipsis if needed
            if (this.currentPage < totalPages - 2) {
                this.addEllipsis();
            }
            
            // Always show last page if there's more than one page
            if (totalPages > 1) {
                this.addPageNumber(totalPages);
            }
        },
        
        addPageNumber(page) {
            const paginationNumbers = document.getElementById('paginationNumbers');
            const pageBtn = document.createElement('button');
            pageBtn.className = `relative inline-flex items-center px-4 py-2 text-sm font-medium ${
                this.currentPage === page 
                    ? 'bg-brand-500 text-white' 
                    : 'text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700'
            }`;
            pageBtn.textContent = page;
            pageBtn.addEventListener('click', () => {
                this.currentPage = page;
                this.updateTable();
            });
            paginationNumbers.appendChild(pageBtn);
        },
        
        addEllipsis() {
            const paginationNumbers = document.getElementById('paginationNumbers');
            const ellipsis = document.createElement('span');
            ellipsis.className = 'relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-400';
            ellipsis.textContent = '...';
            paginationNumbers.appendChild(ellipsis);
        },
        
        sortTable(columnIndex, preserveFilter = false) {
            // Update sort direction if clicking the same column
            if (this.currentSortColumn === columnIndex) {
                this.sortDirection *= -1;
            } else {
                this.currentSortColumn = columnIndex;
                this.sortDirection = 1;
            }
            
            // Update sort icons
            document.querySelectorAll('.sort-icon').forEach(icon => {
                icon.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                `;
            });
            
            const currentIcon = document.querySelectorAll('th')[columnIndex]?.querySelector('.sort-icon');
            if (currentIcon) {
                currentIcon.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M${this.sortDirection === 1 ? '19 9l-7 7-7-7' : '19 15l-7-7-7 7'}" />
                    </svg>
                `;
            }
            
            // Sort the filtered users
            this.filteredUsers.sort((a, b) => {
                const cellA = a.querySelectorAll('td')[columnIndex];
                const cellB = b.querySelectorAll('td')[columnIndex];
                
                let valueA, valueB;
                
                // Get sort values from data attributes if available
                if (columnIndex === 5) { // Status column
                    valueA = cellA.querySelector('.user-status')?.getAttribute('data-sort-value') || '';
                    valueB = cellB.querySelector('.user-status')?.getAttribute('data-sort-value') || '';
                } else if (columnIndex === 2) { // Role column
                    valueA = cellA.querySelector('.user-role')?.getAttribute('data-sort-value') || '';
                    valueB = cellB.querySelector('.user-role')?.getAttribute('data-sort-value') || '';
                } else {
                    // For text columns
                    valueA = cellA?.textContent.trim().toLowerCase() || '';
                    valueB = cellB?.textContent.trim().toLowerCase() || '';
                }
                
                if (valueA < valueB) return -1 * this.sortDirection;
                if (valueA > valueB) return 1 * this.sortDirection;
                return 0;
            });
            
            // Update the table
            if (!preserveFilter) {
                this.currentPage = 1;
            }
            this.updateTable();
        }
    };
}

// Fix double arrow issue in select
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('entriesPerPage');
    if (select) {
        select.classList.add('appearance-none');
        select.style.backgroundImage = 'none';
    }
});
</script>

<style>
[x-cloak] { display: none !important; }

/* Modal backdrop blur fix */
.modal-close-btn {
    backdrop-filter: blur(32px);
    -webkit-backdrop-filter: blur(32px);
}
</style>