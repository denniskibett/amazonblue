<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Loan Durations (days)</h3>
  </div>
  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <div id="chart-loan-durations" class="-ml-5 h-full min-w-[650px] pl-2 xl:min-w-full"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const options = {
        chart: { type: 'boxPlot', height: 350 },
        series: [{
            name: 'Durations',
            data: window.loanStats.loan_durations || []
        }],
        xaxis: { categories: ['Loans'] },
        colors: ['#1E40AF']
    };
    const chart = new ApexCharts(document.querySelector("#chart-loan-durations"), options);
    chart.render();
});
</script>
