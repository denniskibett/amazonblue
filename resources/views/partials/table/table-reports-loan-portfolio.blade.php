<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Loan ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Borrower</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Borrow Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Repayment %</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($data['recent_loans'] ?? [] as $loan)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">#{{ $loan['id'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['borrower'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">KES {{ number_format($loan['amount'] ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['type'] ?? 'Standard' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full
                        {{ $loan['status'] === 'repaid' ? 'bg-green-100 text-green-800' :
                           ($loan['status'] === 'disbursed' ? 'bg-blue-100 text-blue-800' :
                           ($loan['status'] === 'approved' ? 'bg-yellow-100 text-yellow-800' :
                           ($loan['status'] === 'rejected' ? 'bg-red-100 text-red-800' :
                           ($loan['status'] === 'pending' ? 'bg-gray-100 text-gray-800' :
                           'bg-gray-100 text-gray-800')))) }}">
                        {{ ucfirst($loan['status'] ?? 'Unknown') }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $loan['borrow_date'] ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    @if(($loan['repayment_percentage'] ?? 0) >= 100)
                        <span class="text-green-600 font-medium">{{ number_format($loan['repayment_percentage'] ?? 0, 1) }}%</span>
                    @elseif(($loan['repayment_percentage'] ?? 0) >= 50)
                        <span class="text-yellow-600">{{ number_format($loan['repayment_percentage'] ?? 0, 1) }}%</span>
                    @else
                        <span class="text-red-600">{{ number_format($loan['repayment_percentage'] ?? 0, 1) }}%</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No loans found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>