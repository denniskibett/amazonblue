    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center border border-blue-200 dark:border-blue-800">
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">KES {{ number_format($totalDisbursements, 2) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Total Disbursements</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center border border-green-200 dark:border-green-800">
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">KES {{ number_format($totalRepayments, 2) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Total Repayments</p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center border border-purple-200 dark:border-purple-800">
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">KES {{ number_format($totalFees, 2) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Total Processing Fees</p>
        </div>
        <div class="bg-{{ $outstandingBalance > 0 ? 'red' : 'green' }}-50 dark:bg-{{ $outstandingBalance > 0 ? 'red' : 'green' }}-900/20 rounded-lg p-4 text-center border border-{{ $outstandingBalance > 0 ? 'red' : 'green' }}-200 dark:border-{{ $outstandingBalance > 0 ? 'red' : 'green' }}-800">
            <p class="text-2xl font-bold text-{{ $outstandingBalance > 0 ? 'red' : 'green' }}-600 dark:text-{{ $outstandingBalance > 0 ? 'red' : 'green' }}-400">KES {{ number_format($outstandingBalance, 2) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Outstanding Balance</p>
        </div>
    </div>