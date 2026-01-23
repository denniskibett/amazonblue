<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Outstanding at Due Date</h3>
  </div>
  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <div id="chart-outstanding-at-due" class="-ml-5 h-full min-w-[650px] pl-2 xl:min-w-full"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const options = {
        chart: { type: 'bar', height: 350 },
        series: [{
            name: 'Outstanding',
            data: window.loanStats.loan_details.map(l => l.outstanding_at_due)
        }],
        xaxis: { categories: window.loanStats.loan_details.map((l, i) => 'Loan ' + (i + 1)) },
        colors: ['#EF4444']
    };
    const chart = new ApexCharts(document.querySelector("#chart-outstanding-at-due"), options);
    chart.render();
});
</script>
