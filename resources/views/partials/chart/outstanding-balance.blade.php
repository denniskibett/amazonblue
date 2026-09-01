<div id="outstandingBalanceChart" class="w-full"></div>

<script>
function initChartOutstandingBalance(data) {
    const customers = data.customers || [];
    const labels = customers.map(c => c.name).slice(0, 15);
    const balances = customers.map(c => c.outstanding_balance).slice(0, 15);
    
    const options = {
        series: [{ name: 'Outstanding Balance', data: balances }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: { show: false }
        },
        colors: ['#3B82F6'],
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
    
    const chartEl = document.getElementById('outstandingBalanceChart');
    if (chartEl && typeof ApexCharts !== 'undefined') {
        if (window._outstandingBalanceChart) window._outstandingBalanceChart.destroy();
        window._outstandingBalanceChart = new ApexCharts(chartEl, options);
        window._outstandingBalanceChart.render();
    }
}
</script>