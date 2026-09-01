<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gender</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Loans</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Borrowed</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Repaid</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Balance</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($data['customers'] ?? [] as $customer)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $customer['name'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $customer['email'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $customer['phone'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full {{ $customer['role'] === 'borrower' ? 'bg-blue-100 text-blue-800' : ($customer['role'] === 'broker' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') }}">
                        {{ ucfirst($customer['role'] ?? 'Unknown') }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full {{ $customer['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $customer['status'] ?? 'Inactive' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($customer['gender'] ?? '-') }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $customer['total_loans'] ?? 0 }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($customer['total_borrowed'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($customer['total_repaid'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="{{ ($customer['balance'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                        KES {{ number_format($customer['balance'] ?? 0, 2) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No customers found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>