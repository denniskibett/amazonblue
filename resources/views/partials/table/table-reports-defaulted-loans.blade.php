<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Loan ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Borrower</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Default Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recovered</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Remaining</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recovery Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Default Reason</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($data['loans'] ?? [] as $loan)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">#{{ $loan['id'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['borrower'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($loan['amount'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['default_date'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($loan['total_recovered'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="text-red-600 font-medium">KES {{ number_format($loan['remaining_balance'] ?? 0, 2) }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full {{ ($loan['recovery_status'] ?? '') === 'Partial Recovery' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' }}">
                        {{ $loan['recovery_status'] ?? 'Unknown' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['default_reason'] ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No defaulted loans found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>