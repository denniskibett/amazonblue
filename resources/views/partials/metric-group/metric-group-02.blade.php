<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
  @if(auth()->user()->role === 'admin')
    <!-- Admin Cards -->
    <!-- Total Loans -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ $stats['totalLoans'] ?? 0 }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Total Loans
          </p>
        </div>
      </div>
    </div>

    <!-- Loans Requested -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['total_requested'] ?? 0, 2) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Loans Requested (KES)
          </p>
        </div>
      </div>
    </div>

    <!-- Total Disbursed -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['total_disbursed'] ?? 0, 2) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Total Disbursed (KES)
          </p>
        </div>
      </div>
    </div>

    <!-- Total Repayments -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['total_repayments'] ?? 0, 2) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Total Repayments (KES)
          </p>
        </div>
      </div>
    </div>

    <!-- Broker Fees -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['total_broker_fees'] ?? 0, 2) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Broker Fees (KES)
          </p>
        </div>
      </div>
    </div>

    <!-- Net Earnings -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['net_earnings'] ?? 0, 2) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Net Earnings (KES)
          </p>
        </div>
      </div>
    </div>

    <!-- Total Penalties -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['total_penalties'] ?? 0, 2) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Total Penalties (KES)
          </p>
        </div>
      </div>
    </div>

    <!-- Average Days Late -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['average_days_late'] ?? 0, 1) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Avg. Days Late
          </p>
        </div>
        <span class="text-theme-xs text-gray-500 dark:text-gray-400">
          {{ $stats['total_late_loans'] ?? 0 }} late loans
        </span>
      </div>
    </div>

    <!-- Average Repayment Days -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['average_repayment_days'] ?? 0, 1) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Avg. Repayment Days
          </p>
        </div>
        <span class="text-theme-xs text-gray-500 dark:text-gray-400">
          @php
            $repaymentPeriods = $stats['repayment_periods'] ?? [];
            $repaymentCount = is_array($repaymentPeriods) ? count($repaymentPeriods) : 0;
          @endphp
          {{ $repaymentCount }} repaid loans
        </span>
      </div>
    </div>

  @elseif(auth()->user()->role === 'broker')
    <!-- Broker Cards -->
    <!-- Active Loans -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ $stats['activeLoans'] ?? 0 }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Active Loans
          </p>
        </div>
      </div>
    </div>

    <!-- Repaid Loans -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ $stats['repaidLoans'] ?? 0 }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Repaid Loans
          </p>
        </div>
      </div>
    </div>

    <!-- Total Earnings -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['brokerFees'] ?? 0, 2) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Total Earnings
          </p>
        </div>
      </div>
    </div>

    <!-- Clients -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ $stats['total_clients'] ?? 0 }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Clients
          </p>
        </div>
      </div>
    </div>

  @else
    <!-- Borrower Cards -->
    <!-- Active Loans -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ $stats['activeLoans'] ?? 0 }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Active Loans
          </p>
        </div>
      </div>
    </div>

    <!-- Total Borrowed -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['total_disbursed'] ?? 0, 2) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Total Borrowed
          </p>
        </div>
      </div>
    </div>

    <!-- Total Repaid -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ number_format($stats['total_repaid'] ?? 0, 2) }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Total Repaid
          </p>
        </div>
      </div>
    </div>

    <!-- Repaid Loans -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">
        {{ $stats['repaidLoans'] ?? 0 }}
      </h4>
      <div class="mt-4 flex items-end justify-between sm:mt-5">
        <div>
          <p class="text-theme-sm text-gray-700 dark:text-gray-400">
            Repaid Loans
          </p>
        </div>
      </div>
    </div>
  @endif
</div>