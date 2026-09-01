<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metric</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Performance</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Financial Summary -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Financial Summary</td>
            </tr>
            @foreach($data['financial_summary'] ?? [] as $key => $value)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    @if(strpos($key, 'rate') !== false || strpos($key, 'yield') !== false)
                        {{ number_format($value, 2) }}%
                    @else
                        KES {{ number_format($value, 2) }}
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    @if($key === 'portfolio_yield')
                        <span class="px-2 py-1 text-xs rounded-full {{ $value >= 10 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $value >= 10 ? 'Good' : 'Needs Improvement' }}
                        </span>
                    @elseif($key === 'net_portfolio_value')
                        <span class="px-2 py-1 text-xs rounded-full {{ $value > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $value > 0 ? 'Positive' : 'Negative' }}
                        </span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
            </tr>
            @endforeach

            <!-- Performance Metrics -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Performance Metrics</td>
            </tr>
            @foreach($data['performance_metrics'] ?? [] as $key => $value)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ number_format($value, 2) }}%</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    @php
                        $good = $key === 'repayment_rate' ? $value >= 80 : ($key === 'customer_satisfaction' ? $value >= 70 : ($key === 'average_interest_rate' ? $value <= 20 : $value <= 10));
                    @endphp
                    <span class="px-2 py-1 text-xs rounded-full {{ $good ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $good ? 'Good' : 'Needs Improvement' }}
                    </span>
                </td>
            </tr>
            @endforeach

            <!-- Loan Status -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Loan Status</td>
            </tr>
            @foreach($data['by_status'] ?? [] as $status => $count)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst($status) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $count }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full
                        {{ $status === 'repaid' ? 'bg-green-100 text-green-800' :
                           ($status === 'disbursed' ? 'bg-blue-100 text-blue-800' :
                           ($status === 'approved' ? 'bg-yellow-100 text-yellow-800' :
                           ($status === 'rejected' ? 'bg-red-100 text-red-800' :
                           'bg-gray-100 text-gray-800'))) }}">
                        {{ $count > 0 ? 'Active' : 'No Data' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>