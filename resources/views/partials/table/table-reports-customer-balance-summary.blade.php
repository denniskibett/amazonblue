<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Borrowed</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Repaid</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Outstanding Balance</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Active Loans</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Repayment %</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Repayment</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($data['customers'] ?? [] as $customer)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $customer['name'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $customer['email'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($customer['total_borrowed'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($customer['total_repaid'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="{{ ($customer['outstanding_balance'] ?? 0) > 0 ? 'text-red-600 font-medium' : 'text-green-600' }}">
                        KES {{ number_format($customer['outstanding_balance'] ?? 0, 2) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $customer['active_loans'] ?? 0 }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    @if(($customer['repayment_percentage'] ?? 0) >= 100)
                        <span class="text-green-600 font-medium">{{ number_format($customer['repayment_percentage'] ?? 0, 1) }}%</span>
                    @elseif(($customer['repayment_percentage'] ?? 0) >= 50)
                        <span class="text-yellow-600">{{ number_format($customer['repayment_percentage'] ?? 0, 1) }}%</span>
                    @else
                        <span class="text-red-600">{{ number_format($customer['repayment_percentage'] ?? 0, 1) }}%</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $customer['last_repayment'] ?? 'Never' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No customers found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>