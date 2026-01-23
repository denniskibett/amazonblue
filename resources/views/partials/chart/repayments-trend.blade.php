<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Repayments Trend</h3>
  </div>
  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <div id="chart-repayments-trend" class="-ml-5 h-full min-w-[650px] pl-2 xl:min-w-full"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const options = {
        chart: { type: 'line', height: 350 },
        series: [{
            name: 'Repayments',
            data: window.loanStats.repayments_trend || [] // requires array of daily/weekly sums from controller
        }],
        xaxis: { categories: window.loanStats.repayments_dates || [] },
        colors: ['#10B981']
    };
    const chart = new ApexCharts(document.querySelector("#chart-repayments-trend"), options);
    chart.render();
});
</script>
