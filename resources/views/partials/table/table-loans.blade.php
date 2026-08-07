@php
    // Set default values if not provided
    $showUserColumn = $showUserColumn ?? true;
    $showCreateButton = $showCreateButton ?? true;
    $loans = $loans ?? [];
    $context = $context ?? 'user-profile';
    
    // Ensure variables exist with defaults - query if not provided
    $users = $users ?? collect();
    $loanTypes = $loanTypes ?? App\Models\LoanType::all();
    $guarantors = $guarantors ?? App\Models\User::where('role', 'borrower')->get();
    $loanOfficers = $loanOfficers ?? App\Models\User::whereIn('role', ['admin', 'teller'])->get();
    
    // Signature-related variables with defaults
    $signatureUser = $signatureUser ?? null;
    $hasExistingSignature = $hasExistingSignature ?? false;
    $existingSignatureUrl = $existingSignatureUrl ?? null;
    
    // Determine if we should show signature section
    $showSignatureSection = false;
    if (isset($user) || auth()->user()->role === 'borrower') {
        $showSignatureSection = true;
        $signatureUser = $signatureUser ?? $user ?? auth()->user();
        $hasExistingSignature = $signatureUser ? ($signatureUser->signature ?? false) : false;
        $existingSignatureUrl = $hasExistingSignature ? asset('storage/' . $signatureUser->signature) : null;
    }
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6"
     x-data="loanTable()"
     x-init="init()">
  <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        {{ $context === 'user-profile' ? 'Loan History' : 'Loans Overview' }}
      </h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Showing <span id="showingStart">1</span> to <span id="showingEnd">10</span> of <span id="totalCount">{{ count($loans) }}</span> entries
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
        <input type="text" id="loanSearch" placeholder="Search loans..." class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 pl-10">
        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      @if(isset($user) && (auth()->user()->role === 'admin' || auth()->user()->role === 'teller' || (auth()->user()->role === 'broker' && auth()->user()->broker && $user->borrower->broker_id === auth()->user()->broker->id)))
      <button 
          @click="openCreateModal({ userId: {{ $user->id }}, userName: '{{ addslashes($user->name) }}' })"
          class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-theme-sm font-medium text-gray-500 shadow-theme-xs ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Create Loan
      </button>
      @elseif(!isset($user) && (auth()->user()->role === 'admin' || auth()->user()->role === 'teller' || auth()->user()->role === 'broker'))
      <button 
          @click="openCreateModal()"
          class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-theme-sm font-medium text-gray-500 shadow-theme-xs ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Create Loan
      </button>
      @endif
    </div>
  </div>

  <div class="w-full overflow-x-auto">
    <table class="min-w-full" id="loansTable">
      <thead class="hidden sm:table-header-group">
        <tr class="border-gray-100 border-y dark:border-gray-800">
          @if($showUserColumn && in_array(auth()->user()->role, ['admin', 'broker', 'teller']))
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="window.loanTableInstance?.sortTable(0)">
            <div class="flex items-center justify-between">
              <span>@if(auth()->user()->role === 'broker') Client @else Borrower @endif</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          @endif
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="window.loanTableInstance?.sortTable({{ $showUserColumn && in_array(auth()->user()->role, ['admin', 'broker', 'teller']) ? 1 : 0 }})">
            <div class="flex items-center justify-between">
              <span>Amount</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="window.loanTableInstance?.sortTable({{ $showUserColumn && in_array(auth()->user()->role, ['admin', 'broker', 'teller']) ? 2 : 1 }})">
            <div class="flex items-center justify-between">
              <span>Borrow Date</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="window.loanTableInstance?.sortTable({{ $showUserColumn && in_array(auth()->user()->role, ['admin', 'broker', 'teller']) ? 3 : 2 }})">
            <div class="flex items-center justify-between">
              <span>Duration</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="window.loanTableInstance?.sortTable({{ $showUserColumn && in_array(auth()->user()->role, ['admin', 'broker', 'teller']) ? 4 : 3 }})">
            <div class="flex items-center justify-between">
              <span>Status</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="window.loanTableInstance?.sortTable({{ $showUserColumn && in_array(auth()->user()->role, ['admin', 'broker', 'teller']) ? 5 : 4 }})">
            <div class="flex items-center justify-between">
              <span>Broker Fees</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="window.loanTableInstance?.sortTable({{ $showUserColumn && in_array(auth()->user()->role, ['admin', 'broker', 'teller']) ? 6 : 5 }})">
            <div class="flex items-center justify-between">
              <span>Penalty</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          @if(auth()->user()->role === 'admin')
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="window.loanTableInstance?.sortTable({{ $showUserColumn && in_array(auth()->user()->role, ['admin', 'broker', 'teller']) ? 7 : 6 }})">
            <div class="flex items-center justify-between">
              <span>Type</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          @endif
          <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
         </tr>
      </thead>
      
      <thead class="sm:hidden">
        <tr class="border-gray-100 border-y dark:border-gray-800">
          @if($showUserColumn)
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrower</th>
          @endif
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
         </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="loansTableBody">
        @forelse($loans as $loan)
        @php
          $dueDate = \Carbon\Carbon::parse($loan->borrow_date);
          $dueDate->add($loan->loanType->period, $loan->loanType->unit);
          $remaining_days = $dueDate->diffInDays(now(), false);
          $total_repayments = $loan->total_repayments ?? $loan->repayments->sum('amount');
          
          if ($loan->status === 'disbursed' && $remaining_days > 0) {
              $status_display = 'overdue';
          } else if ($loan->status === 'disbursed' && $remaining_days <= 0) {
              $status_display = 'due';
          } else {
              $status_display = $loan->status;
          }
        @endphp
        
        <tr class="loan-row hover:bg-gray-50 transition duration-150" 
            data-loan-id="{{ $loan->id }}" 
            data-user-id="{{ $loan->user_id }}"
            data-loan-type="{{ $loan->loanType->name ?? '' }}"
            data-status="{{ $loan->status }}"
            data-amount="{{ $loan->amount }}"
            data-borrow-date="{{ $loan->borrow_date->format('Y-m-d') }}"
            data-due-date="{{ $dueDate->format('Y-m-d') }}">
          @if($showUserColumn && in_array(auth()->user()->role, ['admin', 'broker', 'teller']))
          <td class="py-3 hidden sm:table-cell">
            <div class="flex items-center gap-3">
              <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}">
                  <span class="text-blue-600 font-medium">{{ ucfirst(substr($loan->user->name, 0, 1)) }}</span>
                </a>    
              </div>
              <div>
                <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}" class="loan-borrower">
                  <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $loan->user->name }}</p>
                </a>
                <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-type">{{ $loan->user->email }}</span>
              </div>
            </div>
          </td>
          @endif

          <td class="py-3 hidden sm:table-cell">
            <div>
              <p class="text-gray-800 text-theme-sm dark:text-white/90 loan-amount" data-sort-value="{{ $loan->amount }}">KES {{ number_format($loan->amount, 2) }}</p>
              <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-paid">Paid: KES {{ number_format($total_repayments, 2) }}</span>
            </div>
          </td>
        
          <td class="py-3 hidden sm:table-cell">
            <div>
              <p class="text-gray-800 text-theme-sm dark:text-white/90 loan-date">{{ $loan->borrow_date->format('M d, Y') }}</p>
              <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-period">Due: {{ $dueDate->format('M d, Y') }}</span>
            </div>
          </td>
        
          <td class="py-3 hidden sm:table-cell">
            <div class="text-sm text-gray-500 loan-period">{{ $loan->loanType->period }} {{ $loan->loanType->unit }}</div>
          </td>
        
          <td class="py-3">
            <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium loan-status" data-sort-value="{{ $status_display }}">{{ ucfirst($loan->status) }}</span>
          </td>
        
          <td class="py-3 hidden sm:table-cell">
            <div class="text-sm text-gray-500">
              @if(auth()->user()->role === 'broker') KES {{ number_format($loan->broker_interest_amount ?? 0, 2) }} @else {{ $loan->loanType->interest_rate }}% @endif
            </div>
          </td>
        
          <td class="py-3 hidden sm:table-cell">
            <div class="text-sm text-gray-500">
              @if(auth()->user()->role === 'broker') KES {{ number_format($loan->broker_penalty_amount ?? 0, 2) }} @else {{ $loan->loanType->penalty_rate }}% @endif
            </div>
          </td>
        
          @if(auth()->user()->role === 'admin')
          <td class="py-3 hidden sm:table-cell">
            <div class="text-sm text-gray-500 loan-type">{{ $loan->loanType->name ?? 'Standard' }}</div>
          </td>
          @endif
        
          <td class="py-3 text-right">
            <div class="flex justify-end space-x-3">
              <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}" class="text-blue-600 hover:text-blue-900" title="View">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              </a>

              @if($loan->consent && in_array(auth()->user()->role, ['admin', 'teller', 'broker']))
              <button @click="openPdfModal({{ $loan->id }}, '{{ addslashes($loan->user->name) }}\'s Loan (KES {{ number_format($loan->amount, 2) }})')" class="text-purple-600 hover:text-purple-900" title="View Agreement">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
              </button>
              @endif

              @if(in_array(auth()->user()->role, ['admin', 'broker', 'teller']) && $loan->status !== 'rejected')
              <button 
                  @click="openEditModal({{ $loan->id }})"
                  class="text-green-600 hover:text-green-900" 
                  title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
              </button>
              @endif

              @if(in_array(auth()->user()->role, ['admin', 'broker']))
              <button @click="openDeleteModal({{ $loan->id }}, '{{ addslashes($loan->user->name) }}\'s Loan (KES {{ number_format($loan->amount, 2) }})')" class="text-red-600 hover:text-red-900" title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
              </button>
              @endif
            </div>
          </td>
        
          @if($showUserColumn)
          <td class="py-3 sm:hidden">
            <div class="flex items-center gap-3">
              <div class="h-[40px] w-[40px] overflow-hidden rounded-md bg-blue-100 flex items-center justify-center">
                <span class="text-blue-600 font-medium">{{ ucfirst(substr($loan->user->name, 0, 1)) }}</span>
              </div>
              <div>
                <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}" class="loan-borrower">
                  <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $loan->user->name }}</p>
                </a>
                <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-type">KES {{ number_format($loan->amount, 2) }}</span>
              </div>
            </div>
          </td>
          @endif
        
          <td class="py-3 sm:hidden">
            <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium loan-status" data-sort-value="{{ $status_display }}">{{ ucfirst($loan->status) }}</span>
          </td>
        
          <td class="py-3 sm:hidden text-right">
            <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}" class="text-blue-600 hover:text-blue-900 inline-block mr-2" title="View">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
            </a>
          
            @if($loan->consent && in_array(auth()->user()->role, ['admin', 'teller', 'broker']))
            <button @click="openPdfModal({{ $loan->id }}, '{{ addslashes($loan->user->name) }}\'s Loan (KES {{ number_format($loan->amount, 2) }})')" class="text-purple-600 hover:text-purple-900 inline-block mr-2" title="View Agreement">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
              </svg>
            </button>
            @endif
          
            @if(in_array(auth()->user()->role, ['admin', 'broker', 'teller']) && $loan->status !== 'rejected')
            <button 
                @click="openEditModal({{ $loan->id }})"
                class="text-green-600 hover:text-green-900 inline-block mr-2" 
                title="Edit">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
              </svg>
            </button>
            @endif
          
            @if(in_array(auth()->user()->role, ['admin', 'broker']))
            <button @click="openDeleteModal({{ $loan->id }}, '{{ addslashes($loan->user->name) }}\'s Loan (KES {{ number_format($loan->amount, 2) }})')" class="text-red-600 hover:text-red-900 inline-block mr-2" title="Delete">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
              </svg>
            </button>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="{{ $showUserColumn && in_array(auth()->user()->role, ['admin', 'broker', 'teller']) ? 9 : 8 }}" class="py-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No loans found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

    <div class="flex flex-col items-center justify-between px-2 py-4 sm:flex-row sm:px-0">
      <div class="hidden sm:flex">
        <p class="text-sm text-gray-700 dark:text-gray-400">
          Showing <span id="paginationStart">1</span> to <span id="paginationEnd">10</span> of <span id="paginationTotal">{{ count($loans) }}</span> results
        </p>
      </div>
      <div class="flex-1 flex justify-between sm:justify-end">
        <button id="prevPage" class="relative inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
          Previous
        </button>
        <div id="paginationNumbers" class="hidden sm:flex"></div>
        <button id="nextPage" class="relative ml-3 inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
          Next
        </button>
      </div>
    </div>
  </div>

  <!-- Loans Create Modal (Slideover) -->
  @include('partials.modal.loans-create-modal')

  <!-- PDF Agreement Modal -->
  <div x-show="isPdfModalOpen" class="fixed inset-0 flex items-center justify-center p-5 overflow-y-auto modal z-99999" style="display: none;">
    <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]" @click="closePdfModal()"></div>
    <div @click.outside="closePdfModal()" class="relative w-full max-w-6xl rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10 max-h-[90vh] flex flex-col">
      <button @click="closePdfModal()" class="group absolute right-3 top-3 z-999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
        <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""/>
        </svg>
      </button>

      <h4 class="mb-6 text-2xl font-semibold text-gray-800 dark:text-white/90">Loan Agreement - <span x-text="pdfLoanName"></span></h4>
      
      <div class="flex-1 overflow-hidden">
        <div x-show="pdfLoading" class="h-full flex items-center justify-center">
          <div class="text-center">
            <svg class="animate-spin mx-auto h-12 w-12 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-gray-600 dark:text-gray-400">Loading agreement...</p>
          </div>
        </div>
        
        <iframe x-show="!pdfLoading && pdfUrl" :src="pdfUrl" class="w-full h-full min-h-[500px] border rounded-lg" frameborder="0"></iframe>
        
        <div x-show="!pdfLoading && !pdfUrl" class="h-full flex items-center justify-center">
          <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No agreement available</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This loan doesn't have a signed agreement yet.</p>
          </div>
        </div>
      </div>
      
      <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-800">
        <a :href="pdfUrl" :download="'loan_agreement_' + pdfLoanId + '.pdf'" x-show="pdfUrl" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Download PDF
        </a>
        <button @click="closePdfModal()" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">Close</button>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div x-show="isDeleteModalOpen" class="fixed inset-0 flex items-center justify-center p-5 overflow-y-auto modal z-99999" style="display: none;">
    <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]" @click="closeDeleteModal()"></div>
    <div @click.outside="closeDeleteModal()" class="relative w-full max-w-md rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10">
      <button @click="closeDeleteModal()" class="group absolute right-3 top-3 z-999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
        <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""/>
        </svg>
      </button>

      <div class="text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
          <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </div>
        
        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Delete Loan</h3>
        
        <div class="mt-2">
          <p class="text-sm text-gray-600 dark:text-gray-400">Are you sure you want to delete <span class="font-semibold" x-text="deleteLoanName"></span>?</p>
          <p class="mt-2 text-sm text-red-600 dark:text-red-400">This action cannot be undone. All associated data will be permanently removed.</p>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-3">
        <button type="button" @click="closeDeleteModal()" class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 sm:w-auto">Cancel</button>
        <button type="button" @click="submitDeleteForm()" :disabled="isDeleteSubmitting" class="flex justify-center w-full px-4 py-3 text-sm font-medium text-white rounded-lg bg-red-600 shadow-theme-xs hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto">
          <span x-show="!isDeleteSubmitting">Delete Loan</span>
          <span x-show="isDeleteSubmitting" class="flex items-center">
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

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<script>
function loanTable() {
    return {
        // Modal states
        isPdfModalOpen: false,
        isDeleteModalOpen: false,
        
        // PDF modal data
        pdfLoanId: null,
        pdfLoanName: '',
        pdfUrl: null,
        pdfLoading: false,
        
        // Delete modal data
        deleteLoanId: null,
        deleteLoanName: '',
        isDeleteSubmitting: false,
        
        // Table data
        allLoans: [],
        filteredLoans: [],
        
        init() {
            this.allLoans = Array.from(document.querySelectorAll('.loan-row'));
            this.filteredLoans = [...this.allLoans];
            this.initializeStatusColors();
            
            // Set up table instance for external access
            window.loanTableInstance = this;
            
            console.log('LoanTable initialized with', this.allLoans.length, 'loans');
        },
        
        // Modal methods
        openCreateModal(data = null) {
            console.log('openCreateModal called with data:', data);
            if (typeof window.openLoansCreateModal === 'function') {
                window.openLoansCreateModal(data);
            } else {
                console.warn('Loans create modal not initialized. Make sure the modal partial is included.');
            }
        },
        
        async openEditModal(loanId) {
            console.log('openEditModal called for loan ID:', loanId);
            
            try {
                // Fetch the full loan data using the edit route with AJAX
                const response = await fetch(`/loans/${loanId}/edit`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Failed to fetch loan data: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Fetched loan data:', data);
                
                if (typeof window.openLoansCreateModal === 'function') {
                    window.openLoansCreateModal({
                        edit: data,
                        userId: data.user_id,
                        userName: data.user_name || ''
                    });
                } else {
                    window.location.href = `/loans/${loanId}/edit`;
                }
            } catch (error) {
                console.error('Error fetching loan data:', error);
                // Fallback: Try to extract from DOM
                this.openEditModalFromDOM(loanId);
            }
        },
        
        openPdfModal(loanId, loanName) {
            this.pdfLoanId = loanId;
            this.pdfLoanName = loanName;
            this.pdfUrl = `/loans/${loanId}/agreement/show`;
            this.pdfLoading = true;
            this.isPdfModalOpen = true;
            document.body.style.overflow = 'hidden';
            setTimeout(() => { this.pdfLoading = false; }, 500);
        },
        
        closePdfModal() {
            this.isPdfModalOpen = false;
            document.body.style.overflow = '';
        },
        
        openDeleteModal(loanId, loanName) {
            this.deleteLoanId = loanId;
            this.deleteLoanName = loanName;
            this.isDeleteModalOpen = true;
            document.body.style.overflow = 'hidden';
        },
        
        closeDeleteModal() {
            this.isDeleteModalOpen = false;
            document.body.style.overflow = '';
        },
        
        async submitDeleteForm() {
            this.isDeleteSubmitting = true;
            
            try {
                const response = await fetch(`/loans/${this.deleteLoanId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('success', 'Deleted!', data.message);
                    } else {
                        alert(data.message);
                    }
                    this.closeDeleteModal();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(data.message || 'Failed to delete loan');
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof window.showAlert === 'function') {
                    window.showAlert('error', 'Error!', error.message || 'Failed to delete loan');
                } else {
                    alert('Error: ' + error.message);
                }
            } finally {
                this.isDeleteSubmitting = false;
            }
        },
        
        initializeStatusColors() {
            const statusColors = {
                'disbursed': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'approved': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                'rejected': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'repaid': 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-300',
                'overdue': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                'due': 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                'active': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
                'defaulted': 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200',
                'default': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
            };
            
            document.querySelectorAll('.loan-status').forEach(el => {
                const status = el.textContent.trim().toLowerCase();
                const colors = statusColors[status] || statusColors['default'];
                el.className = 'rounded-full px-2 py-0.5 text-xs font-medium loan-status';
                el.classList.add(...colors.split(' '));
            });
        },
        
        sortTable(columnIndex) {
            console.log('Sorting column', columnIndex);
        }
    };
}

window.loanTableInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof Alpine === 'undefined') {
        console.warn('Alpine.js is not loaded. Make sure to include Alpine.js in your layout.');
    }
});
</script>