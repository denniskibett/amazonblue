<div id="savingsBalanceChart" class="w-full"></div>

<script>
function initChartSavingsBalance(data) {
    const savings = data.savings || [];
    const labels = savings.map(s => s.name).slice(0, 15);
    const balances = savings.map(s => s.savings_balance).slice(0, 15);
    const deposits = savings.map(s => s.total_deposits).slice(0, 15);
    const withdrawals = savings.map(s => s.total_withdrawals).slice(0, 15);
    
    const options = {
        series: [
            { name: 'Savings Balance', data: balances },
            { name: 'Total Deposits', data: deposits },
            { name: 'Total Withdrawals', data: withdrawals }
        ],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: { show: false }
        },
        colors: ['#10B981', '#3B82F6', '#EF4444'],
        xaxis: {
            categories: labels,
            labels: { rotate: -45, style: { fontFamily: 'Outfit, sans-serif', fontSize: '12px' } }
        },
        yaxis: {
            labels: {
                formatter: function(value) {
                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                    if (value >= 1000) return (value / 1000).toFixed(1) + 'K';
                    return value.toLocaleString();
                }
            }
        },
        tooltip: { y: { formatter: function(value) { return 'KES ' + value.toLocaleString(); } } },
        plotOptions: { bar: { borderRadius: 4, horizontal: false, columnWidth: '55%' } },
        dataLabels: { enabled: false }
    };
    
    const chartEl = document.getElementById('savingsBalanceChart');
    if (chartEl && typeof ApexCharts !== 'undefined') {
        if (window._savingsBalanceChart) window._savingsBalanceChart.destroy();
        window._savingsBalanceChart = new ApexCharts(chartEl, options);
        window._savingsBalanceChart.render();
    }
}
</script>