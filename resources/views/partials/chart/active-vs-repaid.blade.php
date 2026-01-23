<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Active vs Repaid Loans</h3>
  </div>
  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <div id="chart-active-vs-repaid" class="-ml-5 h-full min-w-[650px] pl-2 xl:min-w-full"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const options = {
        chart: { type: 'pie', height: 350 },
        series: [window.loanStats.activeLoans, window.loanStats.repaidLoans],
        labels: ['Active', 'Repaid'],
        colors: ['#F59E0B', '#16A34A']
    };
    const chart = new ApexCharts(document.querySelector("#chart-active-vs-repaid"), options);
    chart.render();
});
</script>
