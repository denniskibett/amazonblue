<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Savings Balance</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Deposits</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Withdrawals</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Interest Earned</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Activity</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($data['savings'] ?? [] as $saving)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $saving['name'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $saving['email'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="text-green-600 font-medium">KES {{ number_format($saving['savings_balance'] ?? 0, 2) }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($saving['total_deposits'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($saving['total_withdrawals'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($saving['interest_earned'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $saving['last_activity'] ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No savings data found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>