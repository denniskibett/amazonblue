@php
    // Set default values if not provided
    $showUserColumn = $showUserColumn ?? true;
    $showCreateButton = $showCreateButton ?? true;
    $loans = $loans ?? [];
    $context = $context ?? 'user-profile';
    
    // Ensure variables exist with defaults
    $users = $users ?? collect();
    $loanTypes = $loanTypes ?? collect();
    $guarantors = $guarantors ?? collect();
    $loanOfficers = $loanOfficers ?? collect();
    
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
          @click="openCreateModal()"
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
        
        <tr class="loan-row hover:bg-gray-50 transition duration-150" data-loan-id="{{ $loan->id }}">
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
              <a href="{{ route('loans.edit', $loan->id) }}" class="text-green-600 hover:text-green-900" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
              </a>
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
            <a href="{{ route('loans.edit', $loan->id) }}" class="text-green-600 hover:text-green-900 inline-block mr-2" title="Edit">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
              </svg>
            </a>
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

  <!-- Create Loan Modal -->
  <div x-show="isCreateModalOpen" 
       class="fixed inset-0 z-99999 overflow-y-auto" 
       style="display: none;"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    <div class="flex items-center justify-center min-h-screen p-5">
      <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]" @click="closeCreateModal()"></div>
      
      <div class="relative w-full max-w-4xl rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10 z-50 max-h-[90vh] overflow-y-auto">
        <button @click="closeCreateModal()" class="group absolute right-3 top-3 z-999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
          <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""/>
          </svg>
        </button>

        <div class="pr-4">
          <h4 class="mb-6 text-2xl font-semibold text-gray-800 dark:text-white/90">Create New Loan</h4>

          <form id="createLoanForm" @submit.prevent="submitCreateForm" class="space-y-6">
            @csrf

            @if(auth()->user()->role === 'admin' && !isset($user))
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Select User</label>
              <div class="relative z-20 bg-transparent">
                <select name="user_id" x-model="createFormData.user_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                  <option value="">-- Select User --</option>
                  @foreach($users as $userOption)
                    <option value="{{ $userOption->id }}">{{ $userOption->name }} ({{ $userOption->email }} - {{ ucfirst($userOption->role) }})</option>
                  @endforeach
                </select>
              </div>
              <template x-if="createFormErrors.user_id">
                <p class="mt-1 text-sm text-red-500" x-text="createFormErrors.user_id[0]"></p>
              </template>
            </div>
            @elseif(auth()->user()->role === 'broker')
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Select Your Borrower</label>
              <div class="relative z-20 bg-transparent">
                <select name="user_id" x-model="createFormData.user_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                  <option value="">-- Select Borrower --</option>
                  @foreach($users as $userOption)
                    <option value="{{ $userOption->id }}">{{ $userOption->name }} ({{ $userOption->email }})</option>
                  @endforeach
                </select>
              </div>
              <template x-if="createFormErrors.user_id">
                <p class="mt-1 text-sm text-red-500" x-text="createFormErrors.user_id[0]"></p>
              </template>
            </div>
            <input type="hidden" name="broker_status" value="1">
            @else
            <input type="hidden" name="user_id" value="{{ $user->id ?? auth()->id() }}">
            <input type="hidden" name="broker_status" value="0">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Borrower Name</label>
              <p class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                {{ $user->name ?? auth()->user()->name }}
              </p>
            </div>
            @endif

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Loan Amount (KES)</label>
                <input type="number" step="0.01" min="1" name="amount" x-model="createFormData.amount" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                <template x-if="createFormErrors.amount">
                  <p class="mt-1 text-sm text-red-500" x-text="createFormErrors.amount[0]"></p>
                </template>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Borrow Date</label>
                <div class="relative">
                  <input type="date" name="borrow_date" x-model="createFormData.borrow_date" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                </div>
                <template x-if="createFormErrors.borrow_date">
                  <p class="mt-1 text-sm text-red-500" x-text="createFormErrors.borrow_date[0]"></p>
                </template>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Loan Type</label>
                <div class="relative z-20 bg-transparent">
                  <select name="loan_type_id" x-model="createFormData.loan_type_id" @change="calculateDueDate()" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                    <option value="">-- Select Loan Type --</option>
                    @foreach($loanTypes as $loanType)
                      <option value="{{ $loanType->id }}" data-period="{{ $loanType->period }}" data-unit="{{ $loanType->unit }}">
                        {{ $loanType->name }} ({{ $loanType->interest_rate }}% interest)
                      </option>
                    @endforeach
                  </select>
                </div>
                <template x-if="createFormErrors.loan_type_id">
                  <p class="mt-1 text-sm text-red-500" x-text="createFormErrors.loan_type_id[0]"></p>
                </template>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Due Date</label>
                <p class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" x-text="dueDateDisplay"></p>
                <input type="hidden" name="due_date" x-model="createFormData.due_date">
              </div>
            </div>

            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Loan Status</label>
              <div class="relative z-20 bg-transparent">
                <select name="status" x-model="createFormData.status" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="disbursed">Disbursed</option>
                  <option value="repaid">Repaid</option>
                </select>
              </div>
            </div>
            @else
            <input type="hidden" name="status" value="pending">
            @endif

            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Transaction Type</label>
              <div class="relative z-20 bg-transparent">
                <select name="broker_status" x-model="createFormData.broker_status" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" required>
                  <option value="0">Direct Transaction</option>
                  <option value="1">Broker Transaction</option>
                </select>
              </div>
            </div>
            @endif

            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Reason for Loan <span class="text-red-500">*</span></label>
              <textarea name="reason" x-model="createFormData.reason" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" rows="3" required></textarea>
              <template x-if="createFormErrors.reason">
                <p class="mt-1 text-sm text-red-500" x-text="createFormErrors.reason[0]"></p>
              </template>
            </div>

            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller' || auth()->user()->role === 'borrower')
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Guarantor</label>
                <div class="relative z-20 bg-transparent">
                  <select name="guarantor_id" x-model="createFormData.guarantor_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                    <option value="">-- Select Guarantor (Optional) --</option>
                    @foreach($guarantors as $guarantor)
                      <option value="{{ $guarantor->id }}">{{ $guarantor->name }} ({{ $guarantor->email }})</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Relationship to Guarantor</label>
                <input type="text" name="guarantor_relationship" x-model="createFormData.guarantor_relationship" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="e.g., Friend, Relative, Colleague">
              </div>
            </div>
            @endif

            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Loan Officer</label>
              <div class="relative z-20 bg-transparent">
                <select name="loan_officer_id" x-model="createFormData.loan_officer_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                  <option value="">-- Select Loan Officer (Optional) --</option>
                  @foreach($loanOfficers as $officer)
                    <option value="{{ $officer->id }}">{{ $officer->name }} ({{ ucfirst($officer->role) }})</option>
                  @endforeach
                </select>
              </div>
            </div>
            @endif

            <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
              <h4 class="text-lg font-medium mb-4 text-gray-700 dark:text-white/90">Digital Signature</h4>
              
              @php
                  $selectedUserId = isset($user) ? $user->id : (auth()->user()->role === 'borrower' ? auth()->id() : null);
                  $selectedUser = $selectedUserId ? \App\Models\User::find($selectedUserId) : null;
                  $hasExistingSignature = $selectedUser && $selectedUser->signature;
                  $existingSignatureUrl = $hasExistingSignature ? asset('storage/' . $selectedUser->signature) : null;
              @endphp

              @if($hasExistingSignature && $selectedUser && $existingSignatureUrl)
              <div class="mb-6">
                  <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                          <div class="flex-shrink-0">
                              <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-green-200 dark:border-green-700 shadow-sm">
                                  <div class="w-32 h-32 flex items-center justify-center bg-transparent">
                                      <img src="{{ $existingSignatureUrl }}" 
                                          alt="Existing signature of {{ $selectedUser->name }}"
                                          class="max-w-full max-h-full object-contain">
                                  </div>
                              </div>
                          </div>
                          <div class="flex-1">
                              <h4 class="font-semibold text-green-800 dark:text-green-300 text-lg">{{ $selectedUser->name }}</h4>
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
                          <p class="text-xs text-blue-600 dark:text-blue-400">The existing signature will be used for the loan agreement unless you create a new one below.</p>
                      </div>
                      <div class="flex items-center space-x-2">
                          <input type="checkbox" id="use-existing-signature" x-model="useExistingSignature" class="rounded border-blue-300 text-blue-600 focus:ring-blue-500 dark:border-blue-600 dark:bg-blue-900">
                          <label for="use-existing-signature" class="text-sm text-blue-800 dark:text-blue-300">Use Existing Signature</label>
                      </div>
                  </div>
              </div>
              @endif

              <div id="signature-creation-section" class="@if($hasExistingSignature) border-t border-gray-200 dark:border-gray-700 pt-6 @endif">
                  <h4 class="text-md font-medium mb-4 text-gray-700 dark:text-gray-300">
                      @if($hasExistingSignature) Create New Signature (Optional - will replace existing) @else Create Digital Signature @endif
                  </h4>
                  
                  <div>
                      <div class="mb-4">
                          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
                              @if($hasExistingSignature) Draw a new signature below to replace the existing one @else Draw Your Signature in the Square Below @endif
                          </label>
                          <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 bg-white dark:bg-gray-900">
                              <div class="flex justify-center">
                                  <div class="signature-pad relative">
                                      <canvas id="signature-canvas" class="border border-gray-300 rounded-lg bg-white" 
                                              style="touch-action: none; width: 400px; height: 400px; max-width: 100%;"></canvas>
                                  </div>
                              </div>
                              
                              <div class="mt-4 flex flex-col sm:flex-row gap-2 justify-center items-center">
                                  <button type="button" @click="clearSignature()" class="px-4 py-2 text-sm bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">Clear Signature</button>
                                  <button type="button" @click="saveSignature()" class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                      @if($hasExistingSignature) Save New Signature @else Save Signature @endif
                                  </button>
                              </div>
                          </div>
                          <div id="signature-status" class="mt-2 text-sm text-center" x-text="signatureStatus"></div>
                          <input type="hidden" name="signature_data" x-model="signatureData">
                          <input type="hidden" name="use_existing_signature" x-model="useExistingSignature">
                          
                          <div x-show="showSignaturePreview" class="mt-6">
                              <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                  <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-center">Signature Preview</h4>
                                  <div class="flex flex-col items-center gap-4">
                                      <div class="flex-shrink-0">
                                          <div class="bg-white dark:bg-gray-900 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
                                              <div class="w-64 h-64 flex items-center justify-center bg-transparent">
                                                  <img :src="signatureData" x-show="signatureData" class="max-w-full max-h-full object-contain" alt="Signature Preview">
                                              </div>
                                          </div>
                                      </div>
                                      <div class="text-center">
                                          <p class="text-sm text-gray-600 dark:text-gray-400"><strong>File name:</strong> <span x-text="signatureFilename"></span></p>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
            </div>

            <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
              <div class="flex items-start space-x-3">
                <input type="checkbox" name="consent" id="consent" value="1" x-model="createFormData.consent" class="mt-1 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800">
                <div>
                  <label for="consent" class="text-sm font-medium text-gray-700 dark:text-gray-400">
                    I agree to the terms and conditions of the loan agreement
                  </label>
                  <p class="text-xs text-gray-500 mt-1">By checking this box, you acknowledge that you have read, understood, and agree to be bound by all terms and conditions of the loan agreement.</p>
                </div>
              </div>
              <template x-if="createFormErrors.consent">
                <p class="mt-1 text-sm text-red-500" x-text="createFormErrors.consent[0]"></p>
              </template>
            </div>

            <div class="flex items-center justify-end w-full gap-3 pt-6">
              <button type="button" @click="closeCreateModal()" class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 sm:w-auto">Cancel</button>
              <button type="submit" :disabled="isCreateSubmitting" class="flex justify-center w-full px-4 py-3 text-sm font-medium text-white rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto">
                <span x-show="!isCreateSubmitting">Create Loan</span>
                <span x-show="isCreateSubmitting" class="flex items-center">
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
  </div>

  <!-- Custom Alert Modal (Styled to match form modal) -->
  <div x-show="isAlertModalOpen" 
       class="fixed inset-0 z-999999 overflow-y-auto" 
       style="display: none;"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    <div class="flex items-center justify-center min-h-screen p-5">
      <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]" @click="closeAlertModal()"></div>
      
      <div class="relative w-full max-w-2xl rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10 z-50 max-h-[90vh] overflow-y-auto">
        <button @click="closeAlertModal()" class="group absolute right-3 top-3 z-999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
          <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""/>
          </svg>
        </button>

        <div class="text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full" 
               :class="alertType === 'success' ? 'bg-green-100 dark:bg-green-900/30' : (alertType === 'error' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30')">
            <svg x-show="alertType === 'success'" class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <svg x-show="alertType === 'error'" class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <svg x-show="alertType === 'warning'" class="h-8 w-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <svg x-show="alertType === 'info'" class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          
          <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white" x-text="alertTitle"></h3>
          
          <div class="mt-2">
            <p class="text-sm text-gray-600 dark:text-gray-400" x-html="alertMessage"></p>
          </div>
          
          <!-- Multiple active loans display -->
          <div x-show="alertData && alertData.active_loans && alertData.active_loans.length > 0" class="mt-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 text-left">
              <h4 class="font-semibold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Active Loans (<span x-text="alertData.active_loans.length"></span>)
              </h4>
              <div class="space-y-3 max-h-60 overflow-y-auto">
                <template x-for="(loan, index) in alertData.active_loans" :key="index">
                  <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                      <div>
                        <p><strong class="text-gray-700 dark:text-gray-300">Amount:</strong> <span class="text-gray-900 dark:text-white font-semibold" x-text="'KES ' + formatNumber(loan.amount)"></span></p>
                        <p><strong class="text-gray-700 dark:text-gray-300">Loan Type:</strong> <span class="text-gray-900 dark:text-white" x-text="loan.loan_type"></span></p>
                        <p><strong class="text-gray-700 dark:text-gray-300">Borrow Date:</strong> <span class="text-gray-900 dark:text-white" x-text="loan.borrow_date_formatted"></span></p>
                      </div>
                      <div>
                        <p><strong class="text-gray-700 dark:text-gray-300">Due Date:</strong> <span class="text-gray-900 dark:text-white" x-text="loan.due_date_formatted"></span></p>
                        <p><strong class="text-gray-700 dark:text-gray-300">Status:</strong> 
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" 
                                :class="loan.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                       (loan.status === 'approved' ? 'bg-blue-100 text-blue-800' : 
                                       (loan.status === 'disbursed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'))">
                            <span x-text="loan.status_display"></span>
                          </span>
                        </p>
                        <p><strong class="text-gray-700 dark:text-gray-300">Days Until Due:</strong> 
                          <span class="font-medium" :class="loan.days_until_due > 0 ? 'text-green-600' : (loan.days_until_due === 0 ? 'text-yellow-600' : 'text-red-600')">
                            <span x-text="loan.days_until_due_text"></span>
                          </span>
                        </p>
                      </div>
                    </div>
                    <p x-show="loan.outstanding_balance > 0" class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                      <strong class="text-gray-700 dark:text-gray-300">Outstanding Balance:</strong> 
                      <span class="text-red-600 font-bold" x-text="'KES ' + formatNumber(loan.outstanding_balance)"></span>
                    </p>
                  </div>
                </template>
              </div>
            </div>
            <div class="mt-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3">
              <p class="text-sm text-yellow-800 dark:text-yellow-300 flex items-start gap-2">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>This borrower has <strong x-text="alertData.active_loans.length"></strong> active loan(s). Creating another loan may affect their credit score and repayment capacity.</span>
              </p>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-center gap-3">
          <button x-show="alertConfirmText" 
                  @click="confirmAlert()" 
                  class="flex justify-center px-6 py-2.5 text-sm font-medium text-white rounded-lg bg-brand-500 shadow-theme-xs hover:bg-brand-600 transition-colors"
                  x-text="alertConfirmText">
          </button>
          <button x-show="alertCancelText" 
                  @click="cancelAlert()" 
                  class="flex justify-center px-6 py-2.5 text-sm font-medium text-gray-700 bg-white rounded-lg border border-gray-300 shadow-theme-xs hover:bg-gray-50 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                  x-text="alertCancelText">
          </button>
        </div>
      </div>
    </div>
  </div>

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
        isCreateModalOpen: false,
        isPdfModalOpen: false,
        isDeleteModalOpen: false,
        isAlertModalOpen: false,
        
        // Alert modal properties
        alertType: 'info',
        alertTitle: '',
        alertMessage: '',
        alertConfirmText: '',
        alertCancelText: '',
        alertData: null,
        alertResolve: null,
        alertReject: null,
        pendingFormData: null,
        
        // Create form data
        createFormData: {
            user_id: '',
            amount: '',
            borrow_date: new Date().toISOString().split('T')[0],
            loan_type_id: '',
            status: 'pending',
            broker_status: '0',
            reason: '',
            guarantor_id: '',
            guarantor_relationship: '',
            loan_officer_id: '',
            consent: false,
            due_date: '',
            signature_data: '',
            use_existing_signature: '1'
        },
        createFormErrors: {},
        isCreateSubmitting: false,
        dueDateDisplay: '',
        
        // Signature related properties
        signaturePad: null,
        signatureData: '',
        signatureStatus: '',
        showSignaturePreview: false,
        signatureFilename: '',
        useExistingSignature: true,
        canvasSize: 400,
        
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
            this.calculateDueDate();
            
            this.$watch('createFormData.borrow_date', () => this.calculateDueDate());
            this.$watch('createFormData.loan_type_id', () => this.calculateDueDate());
            
            this.$watch('isCreateModalOpen', (value) => {
                if (value) {
                    setTimeout(() => this.initializeSignaturePad(), 100);
                }
            });
            
            this.initializeStatusColors();
        },
        
        // Custom Alert Modal Methods
        showAlert(options) {
            return new Promise((resolve, reject) => {
                this.alertType = options.type || 'info';
                this.alertTitle = options.title || '';
                this.alertMessage = options.message || '';
                this.alertConfirmText = options.confirmText || '';
                this.alertCancelText = options.cancelText || '';
                this.alertData = options.data || null;
                this.pendingFormData = options.formData || null;
                this.alertResolve = resolve;
                this.alertReject = reject;
                this.isAlertModalOpen = true;
                document.body.style.overflow = 'hidden';
            });
        },
        
        closeAlertModal() {
            this.isAlertModalOpen = false;
            if (this.alertReject) {
                this.alertReject('closed');
            }
            this.alertResolve = null;
            this.alertReject = null;
            this.pendingFormData = null;
            document.body.style.overflow = '';
        },
        
        async confirmAlert() {
            this.isAlertModalOpen = false;
            if (this.alertResolve) {
                this.alertResolve(true);
            }
            
            // If we have pending form data and this was a duplicate alert, proceed with creation
            if (this.pendingFormData) {
                // Close the create modal first
                this.closeCreateModal();
                
                // Show loading
                this.isCreateSubmitting = true;
                
                try {
                    // Add force create header
                    const response = await fetch('{{ route("loans.store") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Force-Create': 'true'
                        },
                        body: this.pendingFormData
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success) {
                        await this.showAlert({
                            type: 'success',
                            title: 'Success!',
                            message: 'Loan created successfully! The page will now refresh.',
                            confirmText: 'OK'
                        });
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to create loan');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    await this.showAlert({
                        type: 'error',
                        title: 'Error!',
                        message: error.message || 'Failed to create loan',
                        confirmText: 'OK'
                    });
                } finally {
                    this.isCreateSubmitting = false;
                    this.pendingFormData = null;
                }
            }
            
            this.alertResolve = null;
            this.alertReject = null;
            document.body.style.overflow = '';
        },
        
        cancelAlert() {
            this.isAlertModalOpen = false;
            if (this.alertResolve) {
                this.alertResolve(false);
            }
            
            // If user cancels, close all modals and refresh the page
            this.closeCreateModal();
            
            // Show a brief message then refresh
            setTimeout(() => {
                window.location.reload();
            }, 100);
            
            this.alertResolve = null;
            this.alertReject = null;
            this.pendingFormData = null;
            document.body.style.overflow = '';
        },
        
        formatNumber(num) {
            return parseFloat(num).toLocaleString();
        },
        
        // Modal methods
        openCreateModal() {
            this.isCreateModalOpen = true;
            this.createFormErrors = {};
            this.createFormData = {
                ...this.createFormData,
                amount: '',
                loan_type_id: '',
                reason: '',
                guarantor_id: '',
                guarantor_relationship: '',
                loan_officer_id: '',
                consent: false,
                signature_data: '',
                use_existing_signature: '1'
            };
            
            const hasExistingSignature = {{ $hasExistingSignature ? 'true' : 'false' }};
            this.useExistingSignature = hasExistingSignature;
            this.createFormData.use_existing_signature = hasExistingSignature ? '1' : '0';
            
            document.body.style.overflow = 'hidden';
        },
        
        closeCreateModal() {
            this.isCreateModalOpen = false;
            this.showSignaturePreview = false;
            this.signatureData = '';
            if (this.signaturePad) this.signaturePad.clear();
            document.body.style.overflow = '';
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
        
        async submitCreateForm() {
            this.isCreateSubmitting = true;
            this.createFormErrors = {};
            
            if (!this.createFormData.consent) {
                await this.showAlert({
                    type: 'warning',
                    title: 'Consent Required',
                    message: 'You must agree to the terms and conditions to proceed.',
                    confirmText: 'OK'
                });
                this.isCreateSubmitting = false;
                return;
            }
            
            const useExisting = this.createFormData.use_existing_signature === '1';
            if (!useExisting && this.signaturePad && !this.signaturePad.isEmpty() && (!this.signatureData || this.signatureData.trim() === '')) {
                this.signatureData = this.getFullSquareSignature();
                this.createFormData.signature_data = this.signatureData;
            }
            
            const form = document.getElementById('createLoanForm');
            const formData = new FormData(form);
            if (this.signatureData) formData.set('signature_data', this.signatureData);
            
            try {
                const response = await fetch('{{ route("loans.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        this.createFormErrors = data.errors;
                        let errorMessage = Object.values(data.errors).flat().join('\n');
                        await this.showAlert({
                            type: 'error',
                            title: 'Validation Error',
                            message: errorMessage,
                            confirmText: 'OK'
                        });
                    } else if (data.duplicate && data.active_loans && data.active_loans.length > 0) {
                        // Format dates for display
                        data.active_loans.forEach(loan => {
                            loan.borrow_date_formatted = new Date(loan.borrow_date).toLocaleDateString();
                            loan.due_date_formatted = loan.due_date_formatted;
                        });
                        
                        // Store form data for retry
                        const clonedFormData = new FormData(form);
                        if (this.signatureData) clonedFormData.set('signature_data', this.signatureData);
                        
                        const confirmed = await this.showAlert({
                            type: 'warning',
                            title: 'Active Loans Found',
                            message: data.message,
                            confirmText: 'Yes, Create Another',
                            cancelText: 'No, Refresh Page',
                            data: data,
                            formData: clonedFormData
                        });
                        
                        if (confirmed) {
                            // Alert will handle the creation via confirmAlert
                            this.isCreateSubmitting = false;
                            return;
                        } else {
                            // Cancel - will refresh page
                            this.isCreateSubmitting = false;
                            return;
                        }
                    } else {
                        throw new Error(data.message || 'Failed to create loan');
                    }
                } else if (data.success) {
                    await this.showAlert({
                        type: 'success',
                        title: 'Success!',
                        message: data.message,
                        confirmText: 'OK'
                    });
                    window.location.reload();
                } else {
                    await this.showAlert({
                        type: 'error',
                        title: 'Error',
                        message: data.message,
                        confirmText: 'OK'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                await this.showAlert({
                    type: 'error',
                    title: 'Error!',
                    message: error.message || 'Failed to create loan',
                    confirmText: 'OK'
                });
            } finally {
                this.isCreateSubmitting = false;
            }
        },
        
        async submitDeleteForm() {
            this.isDeleteSubmitting = true;
            
            try {
                const response = await fetch(`/loans/${this.deleteLoanId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    await this.showAlert({
                        type: 'success',
                        title: 'Deleted!',
                        message: data.message,
                        confirmText: 'OK'
                    });
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Failed to delete loan');
                }
            } catch (error) {
                console.error('Error:', error);
                await this.showAlert({
                    type: 'error',
                    title: 'Error!',
                    message: error.message || 'Failed to delete loan',
                    confirmText: 'OK'
                });
            } finally {
                this.isDeleteSubmitting = false;
            }
        },
        
        calculateDueDate() {
            const borrowDate = new Date(this.createFormData.borrow_date);
            if (isNaN(borrowDate.getTime())) {
                this.dueDateDisplay = '';
                this.createFormData.due_date = '';
                return;
            }
            
            let period = 10;
            let unit = 'days';
            
            if (this.createFormData.loan_type_id) {
                const select = document.querySelector('select[name="loan_type_id"]');
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
            this.createFormData.due_date = this.dueDateDisplay;
        },
        
        initializeSignaturePad() {
            const canvas = document.querySelector('#signature-canvas');
            if (!canvas || this.signaturePad) return;
            
            canvas.width = this.canvasSize;
            canvas.height = this.canvasSize;
            canvas.style.width = this.canvasSize + 'px';
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
                    this.signatureData = this.getFullSquareSignature();
                    this.createFormData.signature_data = this.signatureData;
                    this.useExistingSignature = false;
                    this.createFormData.use_existing_signature = '0';
                }
            });
        },
        
        getFullSquareSignature() {
            return this.signaturePad.toDataURL('image/png');
        },
        
        clearSignature() {
            if (this.signaturePad) {
                this.signaturePad.clear();
                this.signatureData = '';
                this.createFormData.signature_data = '';
                this.updateSignatureStatus('Signature cleared', 'text-gray-600');
                this.showSignaturePreview = false;
                
                const hasExistingSignature = {{ $hasExistingSignature ? 'true' : 'false' }};
                if (hasExistingSignature) {
                    this.useExistingSignature = true;
                    this.createFormData.use_existing_signature = '1';
                }
            }
        },
        
        saveSignature() {
            if (!this.signaturePad || this.signaturePad.isEmpty()) {
                this.updateSignatureStatus('Please provide a signature first', 'text-red-500');
                return;
            }
            
            this.signatureData = this.getFullSquareSignature();
            this.createFormData.signature_data = this.signatureData;
            this.useExistingSignature = false;
            this.createFormData.use_existing_signature = '0';
            
            const hasExistingSignature = {{ $hasExistingSignature ? 'true' : 'false' }};
            this.updateSignatureStatus(hasExistingSignature ? 'New signature captured! This will replace the existing one.' : 'Signature captured successfully!', 'text-green-500');
            this.showSignaturePreview = true;
            this.updateSignatureFilename();
        },
        
        updateSignatureStatus(message, className) {
            this.signatureStatus = message;
            const statusEl = document.getElementById('signature-status');
            if (statusEl) {
                statusEl.textContent = message;
                statusEl.className = 'mt-2 text-sm ' + className;
            }
        },
        
        updateSignatureFilename() {
            const userSelect = document.querySelector('select[name="user_id"]');
            let userName = 'user';
            
            if (userSelect && userSelect.selectedIndex > 0) {
                userName = userSelect.options[userSelect.selectedIndex].text.split(' (')[0].replace(/[^a-zA-Z0-9]/g, '_');
            } else {
                const borrowerNameField = document.querySelector('p.dark\\:bg-dark-900');
                if (borrowerNameField) userName = borrowerNameField.textContent.trim().replace(/[^a-zA-Z0-9]/g, '_');
            }
            
            this.signatureFilename = `signature_${userName}.png`;
        },
        
        initializeStatusColors() {
            const statusColors = {
                'disbursed': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'approved': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                'rejected': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'repaid': 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-300',
                'overdue': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                'default': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
            };
            
            document.querySelectorAll('.loan-status').forEach(el => {
                const status = el.textContent.trim().toLowerCase();
                el.className = 'rounded-full px-2 py-0.5 text-xs font-medium loan-status';
                const colors = statusColors[status] || statusColors['default'];
                el.classList.add(...colors.split(' '));
            });
        },
        
        sortTable(columnIndex) {
            // Sorting logic
            console.log('Sorting column', columnIndex);
        }
    };
}

window.loanTableInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof Alpine === 'undefined') console.warn('Alpine.js is not loaded.');
});
</script>