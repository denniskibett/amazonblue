<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Average Repayment Period</h3>
  </div>
  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <div id="chart-average-repayment-period" class="-ml-5 h-full min-w-[650px] pl-2 xl:min-w-full text-3xl font-bold text-center text-indigo-600 dark:text-indigo-400">
      {{ $stats['average_repayment_days'] ?? 0 }}
    </div>
  </div>
</div>
