<div id="roleDistributionChart" class="w-full"></div>

<script>
function initChartRoleDistribution(data) {
    const customers = data.customers || [];
    const roles = customers.reduce((acc, c) => {
        acc[c.role] = (acc[c.role] || 0) + 1;
        return acc;
    }, {});
    
    const options = {
        series: Object.values(roles),
        labels: Object.keys(roles).map(r => r.charAt(0).toUpperCase() + r.slice(1)),
        colors: ['#1E3A8A', '#3B82F6'],
        chart: {
            type: 'pie',
            height: 350,
            toolbar: { show: false }
        },
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            labels: { style: { fontFamily: 'Outfit, sans-serif', fontSize: '13px', fontWeight: 500 } }
        },
        dataLabels: {
            enabled: true,
            style: { fontFamily: 'Outfit, sans-serif', fontSize: '12px', fontWeight: 400 },
            formatter: function(value, { seriesIndex, w }) {
                const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                return ((value / total) * 100).toFixed(1) + '%';
            }
        },
        tooltip: { y: { formatter: function(value) { return value + ' customers'; } } }
    };
    
    const chartEl = document.getElementById('roleDistributionChart');
    if (chartEl && typeof ApexCharts !== 'undefined') {
        if (window._roleDistributionChart) window._roleDistributionChart.destroy();
        window._roleDistributionChart = new ApexCharts(chartEl, options);
        window._roleDistributionChart.render();
    }
}
</script>