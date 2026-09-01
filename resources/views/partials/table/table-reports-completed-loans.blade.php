<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Loan ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Borrower</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Borrow Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completion Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration (Days)</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Interest</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Early Settlement</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($data['loans'] ?? [] as $loan)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">#{{ $loan['id'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['borrower'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($loan['amount'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['type'] ?? 'Standard' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['borrow_date'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['completion_date'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['duration_days'] ?? 0 }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($loan['total_interest'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    @if($loan['early_settlement'] ?? false)
                        <span class="text-green-600 font-medium">Yes</span>
                    @else
                        <span class="text-gray-500">No</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No completed loans found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>