<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Borrower</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Defaults -->
            <tr class="bg-red-50 dark:bg-red-900/10">
                <td colspan="6" class="px-4 py-2 text-sm font-semibold text-red-700 dark:text-red-400">Defaulted Loans</td>
            </tr>
            @forelse($data['defaults'] ?? [] as $default)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $default['reference_number'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $default['borrower'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($default['amount'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $default['default_date'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Defaulted</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">CRB Submission</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">No defaulted loans</td>
            </tr>
            @endforelse

            <!-- Cleared -->
            <tr class="bg-green-50 dark:bg-green-900/10">
                <td colspan="6" class="px-4 py-2 text-sm font-semibold text-green-700 dark:text-green-400">Cleared Loans</td>
            </tr>
            @forelse($data['cleared'] ?? [] as $cleared)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $cleared['reference_number'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $cleared['borrower'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($cleared['amount'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $cleared['repayment_date'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Cleared</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">CRB Cleared</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">No cleared loans</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>