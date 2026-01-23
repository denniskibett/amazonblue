
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-4 mb-8 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Loans</p>
                    <p class="text-2xl font-bold">{{ $user->loans->count() }}</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg">
                    <i class="fas fa-hand-holding-usd text-blue-600 dark:text-blue-400 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Loaned</p>
                    <p class="text-2xl font-bold">KES {{ number_format($user->loans->sum('amount')) }}</p>
                </div>
                <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg">
                    <i class="fas fa-money-bill-wave text-green-600 dark:text-green-400 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Disbursed</p>
                    <p class="text-2xl font-bold">KES {{ number_format($user->disbursements->sum('amount')) }}</p>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900/30 p-3 rounded-lg">
                    <i class="fas fa-file-invoice-dollar text-yellow-600 dark:text-yellow-400 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Repaid</p>
                    <p class="text-2xl font-bold">KES {{ number_format($user->repayments->sum('amount')) }}</p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-lg">
                    <i class="fas fa-coins text-purple-600 dark:text-purple-400 text-lg"></i>
                </div>
            </div>
        </div>
    </div>