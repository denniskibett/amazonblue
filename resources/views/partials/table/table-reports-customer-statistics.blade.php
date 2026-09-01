<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metric</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Details</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Summary Metrics -->
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">Total Customers</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $data['summary']['total_customers'] ?? 0 }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">-</td>
            </tr>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">Active Customers</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $data['summary']['active_customers'] ?? 0 }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">-</td>
            </tr>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">Customer Growth Rate</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ number_format($data['summary']['customer_growth_rate'] ?? 0, 1) }}%</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">-</td>
            </tr>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">Avg Loans Per Customer</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ number_format($data['summary']['average_loans_per_customer'] ?? 0, 1) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">-</td>
            </tr>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">Repeat Borrowing Rate</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ number_format($data['summary']['repeat_borrowing_rate'] ?? 0, 1) }}%</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">-</td>
            </tr>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">Churn Rate</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ number_format($data['summary']['churn_rate'] ?? 0, 1) }}%</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">-</td>
            </tr>

            <!-- Demographics -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Demographics</td>
            </tr>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">Gender</td>
                <td colspan="2" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex flex-wrap gap-2">
                        @foreach($data['demographics']['gender'] ?? [] as $gender => $count)
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700">
                            {{ ucfirst($gender) }}: {{ $count }}
                        </span>
                        @endforeach
                    </div>
                </td>
            </tr>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">Age Groups</td>
                <td colspan="2" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex flex-wrap gap-2">
                        @foreach($data['demographics']['age_groups'] ?? [] as $age => $count)
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700">
                            {{ str_replace('_', '-', $age) }}: {{ $count }}
                        </span>
                        @endforeach
                    </div>
                </td>
            </tr>
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">Marital Status</td>
                <td colspan="2" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex flex-wrap gap-2">
                        @foreach($data['demographics']['marital_status'] ?? [] as $status => $count)
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700">
                            {{ ucfirst($status) }}: {{ $count }}
                        </span>
                        @endforeach
                    </div>
                </td>
            </tr>

            <!-- Behavior -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Behavior</td>
            </tr>
            @foreach($data['behavior'] ?? [] as $key => $value)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $value }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full
                        {{ $key === 'on_time_payers' ? 'bg-green-100 text-green-800' :
                           ($key === 'late_payers' ? 'bg-yellow-100 text-yellow-800' :
                           ($key === 'defaulters' ? 'bg-red-100 text-red-800' :
                           'bg-gray-100 text-gray-800')) }}">
                        {{ $key === 'on_time_payers' ? 'Good' : ($key === 'late_payers' ? 'Warning' : 'Critical') }}
                    </span>
                </td>
            </tr>
            @endforeach

            <!-- Channel Preference -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Channel Preference</td>
            </tr>
            @foreach($data['channel_preference'] ?? [] as $channel => $percentage)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $channel)) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $percentage }}%</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>