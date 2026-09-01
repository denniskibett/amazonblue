<div x-data="reportChart()" x-init="init()" class="w-full">
    <!-- Chart filters and container are handled by the parent -->
    <div id="reportChartContainer">
        <!-- Individual chart partials will be rendered here -->
    </div>
</div>

<script>
document.addEventListener('alpine:initialized', function() {
    window.reportChart = function() {
        return {
            selected: '{{ $defaultChart ?? 'default' }}',
            chartInstance: null,
            chartData: @json($reportData),
            
            init() {
                console.log('Report chart initializing...');
                this.renderChart(this.selected);
            },
            
            updateChart(type) {
                this.selected = type;
                console.log('Switching to chart:', type);
                this.renderChart(type);
            },
            
            renderChart(type) {
                const container = document.getElementById('reportChartContainer');
                if (!container) return;
                
                // Clear container
                container.innerHTML = '';
                
                // Create chart container
                const chartDiv = document.createElement('div');
                chartDiv.id = 'reportChart';
                chartDiv.className = '-ml-4 min-w-[700px] pl-2';
                container.appendChild(chartDiv);
                
                // Dispatch event to load the specific chart partial
                const event = new CustomEvent('loadChart', {
                    detail: {
                        type: type,
                        data: this.chartData,
                        container: chartDiv
                    }
                });
                document.dispatchEvent(event);
            }
        };
    };
});

// Listen for chart loading events
document.addEventListener('loadChart', function(e) {
    const { type, data, container } = e.detail;
    
    // Load the appropriate chart partial based on type
    const chartMap = {
        'customer-balance': 'customer-balance',
        'role-distribution': 'role-distribution',
        'outstanding-balance': 'outstanding-balance',
        'repayment-percentage': 'repayment-percentage',
        'gender-distribution': 'gender-distribution',
        'age-distribution': 'age-distribution',
        'customer-behavior': 'customer-behavior',
        'savings-balance': 'savings-balance',
        'status-distribution': 'status-distribution',
        'loan-type-distribution': 'loan-type-distribution',
        'loan-size-distribution': 'loan-size-distribution',
        'amount-vs-balance': 'amount-vs-balance',
        'active-repayment-percentage': 'active-repayment-percentage',
        'principal-vs-interest': 'principal-vs-interest',
        'duration': 'duration',
        'default-breakdown': 'default-breakdown',
        'recovery-status': 'recovery-status',
        'expected-repayments': 'expected-repayments',
        'repayment-status-breakdown': 'repayment-status-breakdown',
        'disbursement-amounts': 'disbursement-amounts',
        'disbursement-channel': 'disbursement-channel',
        'group-loan-status': 'group-loan-status',
        'financial-summary': 'financial-summary',
        'performance-metrics': 'performance-metrics',
        'cashflow-trend': 'cashflow-trend',
        'running-balance': 'running-balance',
        'journal-entries': 'journal-entries',
        'credit-score-distribution': 'credit-score-distribution',
        'bureau-listings': 'bureau-listings',
        'default-amounts': 'default-amounts',
        'default-vs-cleared': 'default-vs-cleared'
    };
    
    const chartName = chartMap[type] || 'default';
    
    // Use Alpine to load the partial
    if (window.Alpine) {
        // Dynamically load the chart partial
        fetch(`/partials/charts/${chartName}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                // Initialize chart after partial is loaded
                setTimeout(() => {
                    if (window[`initChart${chartName}`]) {
                        window[`initChart${chartName}`](data);
                    }
                }, 100);
            })
            .catch(error => {
                console.error('Error loading chart:', error);
                container.innerHTML = '<div class="text-center py-10 text-gray-500">Chart not available</div>';
            });
    }
});
</script>