<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-4">Portfolio At Risk (PAR)</h3>
    
    <div class="flex justify-between items-center mb-4">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Showing overdue loans by days past due</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="text-xs px-2 py-1 rounded bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                {{ $stats['total_late_loans'] }} Late Loans
            </span>
            <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400">
                Avg {{ round($stats['average_days_late']) }} Days Late
            </span>
        </div>
    </div>
    
    <div id="delinquencyHeatmap" class="h-64"></div>
    
    <div class="flex justify-center mt-4 space-x-4 text-xs">
        <div class="flex items-center">
            <span class="w-4 h-4 bg-green-100 rounded mr-1"></span>
            <span>1-30 Days</span>
        </div>
        <div class="flex items-center">
            <span class="w-4 h-4 bg-yellow-100 rounded mr-1"></span>
            <span>31-60 Days</span>
        </div>
        <div class="flex items-center">
            <span class="w-4 h-4 bg-orange-100 rounded mr-1"></span>
            <span>61-90 Days</span>
        </div>
        <div class="flex items-center">
            <span class="w-4 h-4 bg-red-100 rounded mr-1"></span>
            <span>90+ Days</span>
        </div>
    </div>
</div>