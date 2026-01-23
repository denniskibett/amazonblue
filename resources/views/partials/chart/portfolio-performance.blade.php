<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Portfolio Performance</h3>
        <div class="flex items-center space-x-2">
            <select id="timePeriod" class="text-sm border rounded-lg px-3 py-1 bg-gray-50 dark:bg-gray-800">
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last Quarter</option>
                <option value="365">Last Year</option>
            </select>
        </div>
    </div>
    
    <div id="portfolioPerformanceChart" class="h-80"></div>
    
    <div class="flex justify-center mt-4 space-x-6 text-sm">
        <div class="flex items-center">
            <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
            <span>Disbursed</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
            <span>Repayments</span>
        </div>
        <div class="flex items-center">
            <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
            <span>Defaults</span>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 2. Portfolio Performance Chart
    const performanceCtx = document.getElementById('portfolioPerformanceChart').getContext('2d');
    const performanceChart = new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($loan_details->pluck('borrow_date')->map(function($date) { 
                return \Carbon\Carbon::parse($date)->format('M Y'); 
            })->unique() !!},
            datasets: [
                {
                    label: 'Disbursed',
                    data: {!! json_encode($loan_details->groupBy(function($item) { 
                        return \Carbon\Carbon::parse($item['borrow_date'])->format('M Y'); 
                    })->map(function($group) { 
                        return array_sum(array_column($group, 'principal')); 
                    }) !!},
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Repayments',
                    data: {!! json_encode($loan_details->groupBy(function($item) { 
                        return \Carbon\Carbon::parse($item['borrow_date'])->format('M Y'); 
                    })->map(function($group) { 
                        return array_sum(array_column($group, 'total_repayments')); 
                    }) !!},
                    borderColor: 'rgba(16, 185, 129, 1)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Defaults',
                    data: {!! json_encode($loan_details->groupBy(function($item) { 
                        return \Carbon\Carbon::parse($item['borrow_date'])->format('M Y'); 
                    })->map(function($group) { 
                        return array_sum(array_column($group, 'penalty')); 
                    }) !!},
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (KES)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': KES ' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>