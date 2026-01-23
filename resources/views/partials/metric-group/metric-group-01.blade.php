<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
  @switch(Auth::user()->role)
    @case('admin')
      <!-- Total Loans -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          {{ $totalLoans }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Loans
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-theme-xs font-medium text-blue-600 dark:bg-blue-500/15 dark:text-blue-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ $loansThisMonth }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              this month
            </span>
          </div>
        </div>
      </div>

      <!-- Total Disbursements -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($totalDisbursements, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Disbursements
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-theme-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ number_format($disbursementsThisMonth, 2) }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              this month
            </span>
          </div>
        </div>
      </div>

      <!-- Total Repayments -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($totalRepayments, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Repayments
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-theme-xs font-medium text-green-600 dark:bg-green-500/15 dark:text-green-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ number_format($repaymentsThisMonth, 2) }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              this month
            </span>
          </div>
        </div>
      </div>

      <!-- Total Borrowers -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          {{ $borrowerCount }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Borrowers
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-orange-50 px-2 py-0.5 text-theme-xs font-medium text-orange-600 dark:bg-orange-500/15 dark:text-orange-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ $newBorrowersThisMonth }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              new
            </span>
          </div>
        </div>
      </div>
      @break

    @case('borrower')
      <!-- Total Loans -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          {{ $totalLoans }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Loans
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-theme-xs font-medium text-blue-600 dark:bg-blue-500/15 dark:text-blue-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ $loansThisMonth }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              this month
            </span>
          </div>
        </div>
      </div>

      <!-- Total Borrowed -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($totalBorrowed, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Borrowed
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-theme-xs font-medium text-blue-600 dark:bg-blue-500/15 dark:text-blue-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ number_format($borrowedThisMonth, 2) }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              this month
            </span>
          </div>
        </div>
      </div>

      <!-- Total Disbursements -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($totalDisbursements, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Disbursements
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-theme-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ number_format($disbursementsThisMonth, 2) }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              this month
            </span>
          </div>
        </div>
      </div>

      <!-- Total Repaid -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($totalRepayments, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Repaid
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-theme-xs font-medium text-green-600 dark:bg-green-500/15 dark:text-green-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ number_format($repaymentsThisMonth, 2) }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              this month
            </span>
          </div>
        </div>
      </div>
      @break

    @case('broker')
      <!-- Broker Cards -->
      <!-- Total Clients -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          {{ $clients }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Clients
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-theme-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ $newClientsThisMonth }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              new
            </span>
          </div>
        </div>
      </div>

      <!-- Active Loans -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          {{ $activeLoans }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Active Loans
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-theme-xs font-medium text-blue-600 dark:bg-blue-500/15 dark:text-blue-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ $activeLoansThisMonth ?? 0 }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              this month
            </span>
          </div>
        </div>
      </div>

      <!-- Total Interest -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($totalInterest, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Interest
            </p>
          </div>
          <span class="text-theme-xs text-gray-500 dark:text-gray-400">
            {{ $broker->interest_percentage }}% commission
          </span>
        </div>
      </div>

      <!-- Total Penalty -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($totalPenalty, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Total Penalty
            </p>
          </div>
          <span class="text-theme-xs text-gray-500 dark:text-gray-400">
            {{ $broker->penalty_percentage }}% commission
          </span>
        </div>
      </div>
      @break

    @case('teller')
      <!-- Teller Cards -->
      <!-- Today's Disbursements -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($todaysDisbursements, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Today's Disbursements
            </p>
          </div>
          <span class="text-theme-xs text-gray-500 dark:text-gray-400">
            KES {{ number_format($monthDisbursements, 2) }} this month
          </span>
        </div>
      </div>

      <!-- Month Disbursements -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($monthDisbursements, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Month Disbursements
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-theme-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ $disbursementsThisMonth ?? 0 }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              transactions
            </span>
          </div>
        </div>
      </div>

      <!-- Collected Repayments -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($collectedRepayments, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Collected Repayments
            </p>
          </div>
          <div class="flex items-center gap-1">
            <span class="flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-theme-xs font-medium text-green-600 dark:bg-green-500/15 dark:text-green-500">
              <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"/>
              </svg>
              {{ $repaymentsThisMonth ?? 0 }}
            </span>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">
              transactions
            </span>
          </div>
        </div>
      </div>

      <!-- Month Repayments -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
          KES {{ number_format($monthRepayments, 2) }}
        </h4>
        <div class="mt-4 flex items-end justify-between sm:mt-5">
          <div>
            <p class="text-theme-sm text-gray-700 dark:text-gray-400">
              Month Repayments
            </p>
          </div>
          <span class="text-theme-xs text-gray-500 dark:text-gray-400">
            KES {{ number_format($monthRepayments, 2) }} collected
          </span>
        </div>
      </div>
      @break
  @endswitch
</div>