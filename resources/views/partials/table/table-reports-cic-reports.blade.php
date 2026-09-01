<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metric</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Summary -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Summary</td>
            </tr>
            @foreach($data['summary'] ?? [] as $key => $value)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ number_format($value) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full
                        {{ $key === 'active_listings' ? 'bg-green-100 text-green-800' :
                           ($key === 'default_listings' ? 'bg-red-100 text-red-800' :
                           ($key === 'cleared_listings' ? 'bg-blue-100 text-blue-800' :
                           'bg-gray-100 text-gray-800')) }}">
                        {{ $key === 'active_listings' ? 'Active' :
                           ($key === 'default_listings' ? 'Default' :
                           ($key === 'cleared_listings' ? 'Cleared' : 'Total')) }}
                    </span>
                </td>
            </tr>
            @endforeach

            <!-- Credit Scores -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Credit Score Distribution</td>
            </tr>
            @foreach($data['credit_scores'] ?? [] as $score => $details)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst($score) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    {{ $details['count'] ?? 0 }} ({{ number_format($details['percentage'] ?? 0, 1) }}%)
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full
                        {{ $score === 'excellent' ? 'bg-green-100 text-green-800' :
                           ($score === 'good' ? 'bg-blue-100 text-blue-800' :
                           ($score === 'fair' ? 'bg-yellow-100 text-yellow-800' :
                           ($score === 'poor' ? 'bg-orange-100 text-orange-800' :
                           'bg-red-100 text-red-800'))) }}">
                        {{ ucfirst($score) }}
                    </span>
                </td>
            </tr>
            @endforeach

            <!-- Bureau Data -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Bureau Data</td>
            </tr>
            @foreach($data['bureau_data'] ?? [] as $bureau => $details)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst($bureau) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    Submissions: {{ $details['submissions'] ?? 0 }} | Listings: {{ $details['listings'] ?? 0 }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full {{ ($details['listings'] ?? 0) > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ ($details['listings'] ?? 0) > 0 ? 'Active' : 'No Listings' }}
                    </span>
                </td>
            </tr>
            @endforeach

            <!-- Compliance -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Compliance</td>
            </tr>
            @foreach($data['cic_compliance'] ?? [] as $key => $value)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    @if(strpos($key, 'rate') !== false || strpos($key, 'accuracy') !== false)
                        {{ number_format($value, 1) }}%
                    @else
                        {{ $value }}
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    @if($key === 'compliance_rate' || $key === 'reporting_accuracy')
                        <span class="px-2 py-1 text-xs rounded-full {{ $value >= 90 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $value >= 90 ? 'Compliant' : 'Needs Review' }}
                        </span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>