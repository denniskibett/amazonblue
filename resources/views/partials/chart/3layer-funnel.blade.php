<div class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
  <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
    <div class="w-full">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        Loan Funnel
      </h3>
      <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400">
        Filter by timeframe
      </p>
    </div>

    <div class="flex items-start w-full gap-3 sm:justify-end">
      <div x-data="{ timeframe: 'daily' }" class="inline-flex w-fit items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
        <button @click="timeframe = 'daily'" :class="timeframe === 'daily' ? 'bg-white text-gray-900 dark:bg-gray-800 dark:text-white shadow-theme-xs' : 'text-gray-500 dark:text-gray-400'" class="px-3 py-2 font-medium rounded-md text-theme-sm">Daily</button>
        <button @click="timeframe = 'weekly'" :class="timeframe === 'weekly' ? 'bg-white text-gray-900 dark:bg-gray-800 dark:text-white shadow-theme-xs' : 'text-gray-500 dark:text-gray-400'" class="px-3 py-2 font-medium rounded-md text-theme-sm">Weekly</button>
        <button @click="timeframe = 'monthly'" :class="timeframe === 'monthly' ? 'bg-white text-gray-900 dark:bg-gray-800 dark:text-white shadow-theme-xs' : 'text-gray-500 dark:text-gray-400'" class="px-3 py-2 font-medium rounded-md text-theme-sm">Monthly</button>
        <button @click="timeframe = 'yearly'" :class="timeframe === 'yearly' ? 'bg-white text-gray-900 dark:bg-gray-800 dark:text-white shadow-theme-xs' : 'text-gray-500 dark:text-gray-400'" class="px-3 py-2 font-medium rounded-md text-theme-sm">Yearly</button>
      </div>
    </div>
  </div>

  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <div id="chart-funnel" class="-ml-4 min-w-[700px] pl-2"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const options = {
        chart: { type: 'bar', height: 350 },
        series: [{
            name: 'Requested',
            data: [window.loanStats.total_requested] // Replace with filtered data
        },{
            name: 'Disbursed',
            data: [window.loanStats.total_disbursed]
        },{
            name: 'Repaid',
            data: [window.loanStats.total_repayments]
        }],
        xaxis: { categories: ['Loans Funnel'] },
        colors: ['#2563EB', '#22C55E', '#F59E0B']
    };

    const chart = new ApexCharts(document.querySelector("#chart-funnel"), options);
    chart.render();
});
</script>
