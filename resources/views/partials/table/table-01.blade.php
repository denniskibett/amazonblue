<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
  <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Loans Due</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Showing <span id="showingStart">1</span> to <span id="showingEnd">10</span> of <span id="totalCount">{{ count($dueLoans) }}</span> entries
      </p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
      <div class="flex items-center">
        <label for="entriesPerPage" class="text-sm text-gray-500 dark:text-gray-400 mr-2 hidden sm:inline">Show:</label>
        <div class="relative">
          <select id="entriesPerPage" class="appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 pr-8">
            <option value="5">5</option>
            <option value="10">10</option>
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
      
      <button class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
        See all
      </button>
    </div>
  </div>

  <div class="w-full overflow-x-auto">
    <table class="min-w-full" id="loansTable">
      <!-- Desktop table header -->
      <thead class="hidden sm:table-header-group">
        <tr class="border-gray-100 border-y dark:border-gray-800">
          <th class="py-3 cursor-pointer" onclick="sortTable(0)">
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                Borrower
              </p>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th class="py-3 cursor-pointer" onclick="sortTable(1)">
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                Loan Details
              </p>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th class="py-3 cursor-pointer" onclick="sortTable(2)">
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                Amount
              </p>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th class="py-3 cursor-pointer" onclick="sortTable(3)">
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                Status
              </p>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th class="py-3 cursor-pointer" onclick="sortTable(4)">
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                Due Date
              </p>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
        </tr>
      </thead>
      
      <!-- Mobile table header -->
      <thead class="sm:hidden">
        <tr class="border-gray-100 border-y dark:border-gray-800">
          <th class="py-3 cursor-pointer" onclick="sortTable(0)">
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                Borrower
              </p>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th class="py-3 cursor-pointer" onclick="sortTable(3)">
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                Status
              </p>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th class="py-3 cursor-pointer" onclick="sortTable(4)">
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                Due Date
              </p>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="loansTableBody">
        @if(Auth::user()->role === 'admin')
          @foreach($dueLoans as $loan)
          <tr class="loan-row">
            <!-- Desktop cells -->
            <td class="py-3 hidden sm:table-cell">
              <div class="flex items-center">
                <div class="flex items-center gap-3">
                  <div class="h-[50px] w-[50px] overflow-hidden rounded-md">
                    <x-heroicon-s-user-circle class="h-full w-full text-gray-400" />
                  </div>
                  <div>
                    <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}" class="loan-borrower">
                      <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                        {{ strtoupper($loan->user->name) }}
                      </p>
                    </a>
                    <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-type">
                      {{ $loan->loanType->name ?? 'N/A' }}
                    </span>
                  </div>
                </div>
              </div>
            </td>

            <td class="py-3 hidden sm:table-cell">
              <div class="flex items-center">
                <div>
                  <p class="text-gray-800 text-theme-sm dark:text-white/90 loan-date">
                    {{ $loan->borrow_date->format('D, d M Y') }}
                  </p>
                  <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-period">
                    {{ $loan->loanType->period }} {{ $loan->loanType->unit }}
                  </span>
                </div>
              </div>
            </td>
            <td class="py-3 hidden sm:table-cell">
              <div class="flex items-center">
                <div>
                  <p class="text-gray-800 text-theme-sm dark:text-white/90 loan-amount" data-sort-value="{{ $loan->amount }}">
                    KES {{ number_format($loan->amount, 2) }}
                  </p>
                  <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-paid">
                    Paid: KES {{ number_format($loan->total_repayments, 2) }}
                  </span>
                </div>
              </div>
            </td>
            <td class="py-3">
              <div class="flex items-center">
                <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium loan-status" data-sort-value="{{ $loan->status }}">
                  {{ ucfirst($loan->status) }}
                </span>
              </div>
            </td>
            <td class="py-3">
              <div class="flex items-center">
                <p class="text-gray-500 text-theme-sm dark:text-gray-400 loan-due" data-sort-value="{{ $loan->remaining_days }}">
                  @if($loan->remaining_days < 0)
                    @if($loan->overdue_period['months'] > 0 || $loan->overdue_period['days'] > 0)
                      Overdue by 
                      @if($loan->overdue_period['months'] > 0)
                        {{ $loan->overdue_period['months'] }} month{{ $loan->overdue_period['months'] != 1 ? 's' : '' }}
                      @endif
                      @if($loan->overdue_period['months'] > 0 && $loan->overdue_period['days'] > 0)
                        and
                      @endif
                      @if($loan->overdue_period['days'] > 0)
                        {{ $loan->overdue_period['days'] }} day{{ $loan->overdue_period['days'] != 1 ? 's' : '' }}
                      @endif
                    @endif
                  @else
                    @if($loan->remaining_days == 0)
                      Due today
                    @elseif($loan->remaining_days == 1)
                      Due in 1 day
                    @elseif($loan->remaining_days < 7)
                      Due in {{ $loan->remaining_days }} days
                    @elseif($loan->remaining_days < 30)
                      @php
                        $weeks = floor($loan->remaining_days / 7);
                        $days = $loan->remaining_days % 7;
                      @endphp
                      Due in {{ $weeks }} week{{ $weeks != 1 ? 's' : '' }}
                      @if($days > 0)
                        and {{ $days }} day{{ $days != 1 ? 's' : '' }}
                      @endif
                    @else
                      @php
                        $months = floor($loan->remaining_days / 30);
                        $days = $loan->remaining_days % 30;
                      @endphp
                      Due in {{ $months }} month{{ $months != 1 ? 's' : '' }}
                      @if($days > 0)
                        and {{ $days }} day{{ $days != 1 ? 's' : '' }}
                      @endif
                    @endif
                  @endif
                </p>
              </div>
            </td>
            
            <!-- Mobile cells (simplified view) -->
            <td class="py-3 sm:hidden">
              <div class="flex items-center gap-3">
                <div class="h-[40px] w-[40px] overflow-hidden rounded-md">
                  <x-heroicon-s-user-circle class="h-full w-full text-gray-400" />
                </div>
                <div>
                  <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}" class="loan-borrower">
                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                      {{ strtoupper($loan->user->name) }}
                    </p>
                  </a>
                  <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-type">
                    {{ $loan->loanType->name ?? 'N/A' }}
                  </span>
                </div>
              </div>
            </td>
          </tr>
          @endforeach
        @elseif(Auth::user()->role === 'broker')
          @foreach($dueLoans as $loan)
          <tr class="loan-row">
            <!-- Desktop cells -->
            <td class="py-3 hidden sm:table-cell">
              <div class="flex items-center">
                <div class="flex items-center gap-3">
                  <div class="h-[50px] w-[50px] overflow-hidden rounded-md">
                    <x-heroicon-s-user-circle class="h-full w-full text-gray-400" />
                  </div>
                  <div>
                    <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}" class="loan-borrower">
                      <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                        {{ strtoupper($loan->user->name) }}
                      </p>
                    </a>
                    <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-type">
                      {{ $loan->loanType->name ?? 'N/A' }}
                    </span>
                  </div>
                </div>
              </div>
            </td>
            
            <td class="py-3 hidden sm:table-cell">
              <div class="flex items-center">
                <div>
                  <p class="text-gray-800 text-theme-sm dark:text-white/90 loan-date">
                    {{ $loan->borrow_date->format('D, d M Y') }}
                  </p>
                  <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-period">
                    {{ $loan->loanType->period }} {{ $loan->loanType->unit }}
                  </span>
                </div>
              </div>
            </td>
            <td class="py-3 hidden sm:table-cell">
              <div class="flex items-center">
                <div>
                  <p class="text-gray-800 text-theme-sm dark:text-white/90 loan-amount" data-sort-value="{{ $loan->amount }}">
                    KES {{ number_format($loan->amount, 2) }}
                  </p>
                  <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-paid">
                    Paid: KES {{ number_format($loan->total_repayments, 2) }}
                  </span>
                </div>
              </div>
            </td>
            <td class="py-3">
              <div class="flex items-center">
                <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium loan-status" data-sort-value="{{ $loan->status }}">
                  {{ ucfirst($loan->status) }}
                </span>
              </div>
            </td>
            <td class="py-3">
              <div class="flex items-center">
                <p class="text-gray-500 text-theme-sm dark:text-gray-400 loan-due" data-sort-value="{{ $loan->remaining_days }}">
                  @if($loan->remaining_days < 0)
                    @if($loan->overdue_period['months'] > 0 || $loan->overdue_period['days'] > 0)
                      Overdue by 
                      @if($loan->overdue_period['months'] > 0)
                        {{ $loan->overdue_period['months'] }} month{{ $loan->overdue_period['months'] != 1 ? 's' : '' }}
                      @endif
                      @if($loan->overdue_period['months'] > 0 && $loan->overdue_period['days'] > 0)
                        and
                      @endif
                      @if($loan->overdue_period['days'] > 0)
                        {{ $loan->overdue_period['days'] }} day{{ $loan->overdue_period['days'] != 1 ? 's' : '' }}
                      @endif
                    @endif
                  @else
                    @if($loan->remaining_days == 0)
                      Due today
                    @elseif($loan->remaining_days == 1)
                      Due in 1 day
                    @elseif($loan->remaining_days < 7)
                      Due in {{ $loan->remaining_days }} days
                    @elseif($loan->remaining_days < 30)
                      @php
                        $weeks = floor($loan->remaining_days / 7);
                        $days = $loan->remaining_days % 7;
                      @endphp
                      Due in {{ $weeks }} week{{ $weeks != 1 ? 's' : '' }}
                      @if($days > 0)
                        and {{ $days }} day{{ $days != 1 ? 's' : '' }}
                      @endif
                    @else
                      @php
                        $months = floor($loan->remaining_days / 30);
                        $days = $loan->remaining_days % 30;
                      @endphp
                      Due in {{ $months }} month{{ $months != 1 ? 's' : '' }}
                      @if($days > 0)
                        and {{ $days }} day{{ $days != 1 ? 's' : '' }}
                      @endif
                    @endif
                  @endif
                </p>
              </div>
            </td>
            
            <!-- Mobile cells (simplified view) -->
            <td class="py-3 sm:hidden">
              <div class="flex items-center gap-3">
                <div class="h-[40px] w-[40px] overflow-hidden rounded-md">
                  <x-heroicon-s-user-circle class="h-full w-full text-gray-400" />
                </div>
                <div>
                  <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}" class="loan-borrower">
                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                      {{ strtoupper($loan->user->name) }}
                    </p>
                  </a>
                  <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-type">
                    {{ $loan->loanType->name ?? 'N/A' }}
                  </span>
                </div>
              </div>
            </td>
          </tr>
          @endforeach
        @elseif(Auth::user()->role === 'borrower')
          @foreach($dueLoans->where('user_id', Auth::user()->id) as $loan)
          <tr class="loan-row">
            <!-- Desktop cells -->
            <td class="py-3 hidden sm:table-cell">
              <div class="flex items-center">
                <div class="flex items-center gap-3">
                  <div class="h-[50px] w-[50px] overflow-hidden rounded-md">
                    <x-heroicon-s-user-circle class="h-full w-full text-gray-400" />
                  </div>
                  <div>
                    <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}" class="loan-borrower">
                      <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                        {{ strtoupper($loan->user->name) }}
                      </p>
                    </a>
                    <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-type">
                      {{ $loan->loanType->name ?? 'N/A' }}
                    </span>
                  </div>
                </div>
              </div>
            </td>
            <td class="py-3 hidden sm:table-cell">
              <div class="flex items-center">
                <div>
                  <p class="text-gray-800 text-theme-sm dark:text-white/90 loan-date">
                    {{ $loan->borrow_date->format('D, d M Y') }}
                  </p>
                  <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-period">
                    {{ $loan->loanType->period }} {{ $loan->loanType->unit }}
                  </span>
                </div>
              </div>
            </td>
            <td class="py-3 hidden sm:table-cell">
              <div class="flex items-center">
                <div>
                  <p class="text-gray-800 text-theme-sm dark:text-white/90 loan-amount" data-sort-value="{{ $loan->amount }}">
                    KES {{ number_format($loan->amount, 2) }}
                  </p>
                  <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-paid">
                    Paid: KES {{ number_format($loan->total_repayments, 2) }}
                  </span>
                </div>
              </div>
            </td>
            <td class="py-3">
              <div class="flex items-center">
                <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium loan-status" data-sort-value="{{ $loan->status }}">
                  {{ ucfirst($loan->status) }}
                </span>
              </div>
            </td>
            <td class="py-3">
              <div class="flex items-center">
                <p class="text-gray-500 text-theme-sm dark:text-gray-400 loan-due" data-sort-value="{{ $loan->remaining_days }}">
                  @if($loan->remaining_days < 0)
                    @if($loan->overdue_period['months'] > 0 || $loan->overdue_period['days'] > 0)
                      Overdue by 
                      @if($loan->overdue_period['months'] > 0)
                        {{ $loan->overdue_period['months'] }} month{{ $loan->overdue_period['months'] != 1 ? 's' : '' }}
                      @endif
                      @if($loan->overdue_period['months'] > 0 && $loan->overdue_period['days'] > 0)
                        and
                      @endif
                      @if($loan->overdue_period['days'] > 0)
                        {{ $loan->overdue_period['days'] }} day{{ $loan->overdue_period['days'] != 1 ? 's' : '' }}
                      @endif
                    @endif
                  @else
                    @if($loan->remaining_days == 0)
                      Due today
                    @elseif($loan->remaining_days == 1)
                      Due in 1 day
                    @elseif($loan->remaining_days < 7)
                      Due in {{ $loan->remaining_days }} days
                    @elseif($loan->remaining_days < 30)
                      @php
                        $weeks = floor($loan->remaining_days / 7);
                        $days = $loan->remaining_days % 7;
                      @endphp
                      Due in {{ $weeks }} week{{ $weeks != 1 ? 's' : '' }}
                      @if($days > 0)
                        and {{ $days }} day{{ $days != 1 ? 's' : '' }}
                      @endif
                    @else
                      @php
                        $months = floor($loan->remaining_days / 30);
                        $days = $loan->remaining_days % 30;
                      @endphp
                      Due in {{ $months }} month{{ $months != 1 ? 's' : '' }}
                      @if($days > 0)
                        and {{ $days }} day{{ $days != 1 ? 's' : '' }}
                      @endif
                    @endif
                  @endif
                </p>
              </div>
            </td>
            
            <!-- Mobile cells (simplified view) -->
            <td class="py-3 sm:hidden">
              <div class="flex items-center gap-3">
                <div class="h-[40px] w-[40px] overflow-hidden rounded-md">
                  <x-heroicon-s-user-circle class="h-full w-full text-gray-400" />
                </div>
                <div>
                  <a href="{{ route('users.loans.show', ['user' => $loan->user_id, 'loan' => $loan->id]) }}" class="loan-borrower">
                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                      {{ strtoupper($loan->user->name) }}
                    </p>
                  </a>
                  <span class="text-gray-500 text-theme-xs dark:text-gray-400 loan-type">
                    {{ $loan->loanType->name ?? 'N/A' }}
                  </span>
                </div>
              </div>
            </td>
          </tr>
          @endforeach
        @endif
      </tbody>
    </table>
    
    <div id="noLoansMessage" class="py-8 text-center hidden">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No loans found</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria</p>
    </div>
    
    <!-- Pagination -->
    <div class="flex flex-col items-center justify-between px-2 py-4 sm:flex-row sm:px-0">
      <div class="hidden sm:flex">
        <p class="text-sm text-gray-700 dark:text-gray-400">
          Showing <span id="paginationStart">1</span> to <span id="paginationEnd">10</span> of <span id="paginationTotal">{{ count($dueLoans) }}</span> results
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
document.addEventListener('DOMContentLoaded', function() {
    // Table data and state
    let currentPage = 1;
    let entriesPerPage = parseInt(document.getElementById('entriesPerPage').value);
    let currentSortColumn = null;
    let sortDirection = 1; // 1 for ascending, -1 for descending
    let allLoans = Array.from(document.querySelectorAll('.loan-row'));
    let filteredLoans = [...allLoans];
    
    // DOM elements
    const searchInput = document.getElementById('loanSearch');
    const entriesPerPageSelect = document.getElementById('entriesPerPage');
    const noLoansMessage = document.getElementById('noLoansMessage');
    const showingStart = document.getElementById('showingStart');
    const showingEnd = document.getElementById('showingEnd');
    const totalCount = document.getElementById('totalCount');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const paginationNumbers = document.getElementById('paginationNumbers');
    const paginationStart = document.getElementById('paginationStart');
    const paginationEnd = document.getElementById('paginationEnd');
    const paginationTotal = document.getElementById('paginationTotal');
    
    // Status color mapping
    const statusColors = {
        'overdue': 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
        'due': 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
        'disbursed': 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        'pending': 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
        'default': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    };
    
    // Initialize status colors
    function initializeStatusColors() {
        document.querySelectorAll('.loan-status').forEach(statusElement => {
            const status = statusElement.getAttribute('data-sort-value').toLowerCase();
            applyStatusColor(statusElement, status);
        });
    }
    
    function applyStatusColor(element, status) {
        // Reset classes
        element.className = 'rounded-full px-2 py-0.5 text-theme-xs font-medium loan-status';
        
        // Apply color classes based on status
        const colorClasses = statusColors[status] || statusColors['default'];
        element.classList.add(...colorClasses.split(' '));
    }
    
    // Initialize with the total count from server-side data
    const initialTotalCount = parseInt(document.getElementById('totalCount').textContent);
    totalCount.textContent = initialTotalCount;
    paginationTotal.textContent = initialTotalCount;
    
    // Initialize status colors
    initializeStatusColors();
    
    // Initialize table
    updateTable();
    
    // Event listeners
    searchInput.addEventListener('input', function() {
        currentPage = 1;
        filterLoans();
        updateTable();
    });
    
    entriesPerPageSelect.addEventListener('change', function() {
        entriesPerPage = parseInt(this.value);
        currentPage = 1;
        updateTable();
    });
    
    prevPageBtn.addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            updateTable();
        }
    });
    
    nextPageBtn.addEventListener('click', function() {
        const totalPages = Math.ceil(filteredLoans.length / entriesPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            updateTable();
        }
    });
    
    
    // Functions
    function filterLoans() {
        const searchTerm = searchInput.value.toLowerCase();
        
        if (searchTerm === '') {
            filteredLoans = [...allLoans];
        } else {
            filteredLoans = allLoans.filter(row => {
                const borrower = row.querySelector('.loan-borrower').textContent.toLowerCase();
                const loanType = row.querySelector('.loan-type').textContent.toLowerCase();
                const loanDate = row.querySelector('.loan-date').textContent.toLowerCase();
                const loanAmount = row.querySelector('.loan-amount').textContent.toLowerCase();
                const loanStatus = row.querySelector('.loan-status').textContent.toLowerCase();
                const loanDue = row.querySelector('.loan-due').textContent.toLowerCase();
                
                return borrower.includes(searchTerm) || 
                       loanType.includes(searchTerm) || 
                       loanDate.includes(searchTerm) || 
                       loanAmount.includes(searchTerm) || 
                       loanStatus.includes(searchTerm) || 
                       loanDue.includes(searchTerm);
            });
        }
        
        // Update total count display
        totalCount.textContent = filteredLoans.length;
        paginationTotal.textContent = filteredLoans.length;
        
        // Apply sorting if any column is sorted
        if (currentSortColumn !== null) {
            sortTable(currentSortColumn, true);
        }
    }
    
    function updateTable() {
        const startIndex = (currentPage - 1) * entriesPerPage;
        const endIndex = startIndex + entriesPerPage;
        const paginatedLoans = filteredLoans.slice(startIndex, endIndex);
        
        // Hide all rows first
        allLoans.forEach(row => row.style.display = 'none');
        
        // Show only paginated rows
        paginatedLoans.forEach(row => row.style.display = '');
        
        // Update counters
        const total = filteredLoans.length;
        const showing = paginatedLoans.length;
        
        showingStart.textContent = startIndex + 1;
        showingEnd.textContent = Math.min(endIndex, total);
        
        paginationStart.textContent = startIndex + 1;
        paginationEnd.textContent = Math.min(endIndex, total);
        
        // Update pagination buttons
        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === Math.ceil(total / entriesPerPage);
        
        // Update pagination numbers
        updatePaginationNumbers();
        
        // Show/hide no results message
        if (filteredLoans.length === 0) {
            noLoansMessage.classList.remove('hidden');
        } else {
            noLoansMessage.classList.add('hidden');
        }
    }
    
    function updatePaginationNumbers() {
        const totalPages = Math.ceil(filteredLoans.length / entriesPerPage);
        paginationNumbers.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        // Always show first page
        addPageNumber(1);
        
        // Show ellipsis if needed
        if (currentPage > 3) {
            addEllipsis();
        }
        
        // Show current page and neighbors
        const startPage = Math.max(2, currentPage - 1);
        const endPage = Math.min(totalPages - 1, currentPage + 1);
        
        for (let i = startPage; i <= endPage; i++) {
            addPageNumber(i);
        }
        
        // Show ellipsis if needed
        if (currentPage < totalPages - 2) {
            addEllipsis();
        }
        
        // Always show last page if there's more than one page
        if (totalPages > 1) {
            addPageNumber(totalPages);
        }
    }
    
    function addPageNumber(page) {
        const pageBtn = document.createElement('button');
        pageBtn.className = `relative inline-flex items-center px-4 py-2 text-sm font-medium ${
            currentPage === page ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700'
        }`;
        pageBtn.textContent = page;
        pageBtn.addEventListener('click', () => {
            currentPage = page;
            updateTable();
        });
        paginationNumbers.appendChild(pageBtn);
    }
    
    function addEllipsis() {
        const ellipsis = document.createElement('span');
        ellipsis.className = 'relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-400';
        ellipsis.textContent = '...';
        paginationNumbers.appendChild(ellipsis);
    }
    
    window.sortTable = function(columnIndex, preserveFilter = false) {
        // Update sort direction if clicking the same column
        if (currentSortColumn === columnIndex) {
            sortDirection *= -1;
        } else {
            currentSortColumn = columnIndex;
            sortDirection = 1;
        }
        
        // Update sort icons
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
            `;
        });
        
        const currentIcon = document.querySelectorAll('th')[columnIndex].querySelector('.sort-icon');
        currentIcon.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M${sortDirection === 1 ? '19 9l-7 7-7-7' : '19 15l-7-7-7 7'}" />
            </svg>
        `;
        
        // Sort the filtered loans
        filteredLoans.sort((a, b) => {
            const cellA = a.querySelectorAll('td')[columnIndex];
            const cellB = b.querySelectorAll('td')[columnIndex];
            
            let valueA, valueB;
            
            // Get sort values from data attributes if available
            if (columnIndex === 2) { // Amount column
                valueA = parseFloat(cellA.querySelector('.loan-amount').getAttribute('data-sort-value'));
                valueB = parseFloat(cellB.querySelector('.loan-amount').getAttribute('data-sort-value'));
            } else if (columnIndex === 3) { // Status column
                valueA = cellA.querySelector('.loan-status').getAttribute('data-sort-value');
                valueB = cellB.querySelector('.loan-status').getAttribute('data-sort-value');
            } else if (columnIndex === 4) { // Due Date column
                valueA = parseFloat(cellA.querySelector('.loan-due').getAttribute('data-sort-value'));
                valueB = parseFloat(cellB.querySelector('.loan-due').getAttribute('data-sort-value'));
            } else {
                // For text columns (Borrower and Loan Details)
                valueA = cellA.textContent.trim().toLowerCase();
                valueB = cellB.textContent.trim().toLowerCase();
            }
            
            if (valueA < valueB) return -1 * sortDirection;
            if (valueA > valueB) return 1 * sortDirection;
            return 0;
        });
        
        // Update the table
        if (!preserveFilter) {
            currentPage = 1;
        }
        updateTable();
    };
});
// Custom select styling to fix double arrow issue
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('entriesPerPage');
    if (select) {
        select.classList.add('appearance-none');
        select.style.backgroundImage = 'none';
    }
});
</script>