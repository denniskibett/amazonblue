<div 
    class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6"
    x-data="loanStatsChart()"
    x-init="init()"
>
    <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
        <div class="w-full">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 font-outfit">
                Loan Statistics
            </h3>
            <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400 font-outfit">
                Loan activity overview
            </p>
        </div>

        <div class="flex items-start w-full gap-3 sm:justify-end">
            <div class="inline-flex w-fit items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                <button
                    @click="updateChart('year')"
                    :class="selected === 'year' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                    class="px-3 py-2 font-medium rounded-md text-theme-sm hover:text-gray-900 dark:hover:text-white font-outfit"
                >
                    Year
                </button>
                <button
                    @click="updateChart('month')"
                    :class="selected === 'month' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                    class="px-3 py-2 font-medium rounded-md text-theme-sm hover:text-gray-900 dark:hover:text-white font-outfit"
                >
                    Month
                </button>
                <button
                    @click="updateChart('week')"
                    :class="selected === 'week' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                    class="px-3 py-2 font-medium rounded-md text-theme-sm hover:text-gray-900 dark:hover:text-white font-outfit"
                >
                    Week
                </button>
                <button
                    @click="updateChart('day')"
                    :class="selected === 'day' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                    class="px-3 py-2 font-medium rounded-md text-theme-sm hover:text-gray-900 dark:hover:text-white font-outfit"
                >
                    Day
                </button>
            </div>

            <div class="relative w-fit">
                <input
                    id="loanStatsDatePicker"
                    class="datepicker h-10 w-full max-w-48 rounded-lg border border-gray-200 bg-white py-2.5 pl-[34px] pr-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs focus:outline-none focus:ring-0 focus-visible:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 xl:max-w-fit xl:pl-11 font-outfit"
                    placeholder="Select dates"
                    data-class="flatpickr-right"
                    readonly="readonly"
                />
                <div class="absolute inset-0 right-auto flex items-center pointer-events-none left-4">
                    <svg class="fill-gray-700 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66683 1.54199C7.08104 1.54199 7.41683 1.87778 7.41683 2.29199V3.00033H12.5835V2.29199C12.5835 1.87778 12.9193 1.54199 13.3335 1.54199C13.7477 1.54199 14.0835 1.87778 14.0835 2.29199V3.00033L15.4168 3.00033C16.5214 3.00033 17.4168 3.89576 17.4168 5.00033V7.50033V15.8337C17.4168 16.9382 16.5214 17.8337 15.4168 17.8337H4.5835C3.47893 17.8337 2.5835 16.9382 2.5835 15.8337V7.50033V5.00033C2.5835 3.89576 3.47893 3.00033 4.5835 3.00033L5.91683 3.00033V2.29199C5.91683 1.87778 6.25262 1.54199 6.66683 1.54199ZM6.66683 4.50033H4.5835C4.30735 4.50033 4.0835 4.72418 4.0835 5.00033V6.75033H15.9168V5.00033C15.9168 4.72418 15.693 4.50033 15.4168 4.50033H13.3335H6.66683ZM15.9168 8.25033H4.0835V15.8337C4.0835 16.1098 4.30735 16.3337 4.5835 16.3337H15.4168C15.693 16.3337 15.9168 16.1098 15.9168 15.8337V8.25033Z" fill=""/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div id="loanStatsChart" class="-ml-4 min-w-[700px] pl-2"></div>
    </div>
</div>

<script>
// ============================================================
// LOAN STATISTICS CHART - FULL DAY/WEEK/MONTH/YEAR VIEW
// ============================================================

(function() {
    'use strict';
    
    let chartInstance = null;
    let chartRendered = false;
    let allData = null;
    let currentFilter = 'month';
    let currentDateRange = null;
    let originalMonthlyData = null;

    // Get chart data from PHP
    const chartData = @json($monthlyData);
    
    // Store original monthly data
    originalMonthlyData = {
        labels: chartData.labels || [],
        loans: chartData.loanData || [],
        disbursements: chartData.disbursementData || [],
        repayments: chartData.repaymentData || [],
        dates: chartData.dates || []
    };

    // Generate dates from labels if not provided
    if (originalMonthlyData.dates.length === 0 && originalMonthlyData.labels.length > 0) {
        originalMonthlyData.dates = originalMonthlyData.labels.map(label => {
            const parts = label.split(' ');
            if (parts.length === 2) {
                const monthMap = {
                    'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5,
                    'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11
                };
                const month = monthMap[parts[0]];
                const year = parseInt(parts[1]);
                if (!isNaN(month) && !isNaN(year)) {
                    return new Date(year, month, 1);
                }
            }
            return new Date();
        });
    }

    // Get colors from CSS variables
    function getChartColors() {
        const computedStyle = getComputedStyle(document.documentElement);
        const primaryColor = computedStyle.getPropertyValue('--primary-color').trim() || '#1E3A8A';
        const secondaryColor = computedStyle.getPropertyValue('--secondary-color').trim() || '#3B82F6';
        const secondaryLight = secondaryColor + '80';
        return { primaryColor, secondaryColor, secondaryLight };
    }

    // Get font family
    function getFontFamily() {
        return 'Outfit, sans-serif';
    }

    // Get week number from date (ISO week)
    function getWeekNumber(date) {
        const d = new Date(date);
        d.setHours(0, 0, 0, 0);
        // Thursday in current week decides the year
        d.setDate(d.getDate() + 3 - (d.getDay() + 6) % 7);
        // January 4 is always in week 1
        const week1 = new Date(d.getFullYear(), 0, 4);
        // Calculate week number
        return 1 + Math.round(((d - week1) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
    }

    // Get week start date
    function getWeekStartDate(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        d.setDate(diff);
        d.setHours(0, 0, 0, 0);
        return d;
    }

    // Generate full daily data from monthly data
    function generateFullDailyData(monthlyData) {
        if (!monthlyData || !monthlyData.labels || monthlyData.labels.length === 0) {
            return monthlyData;
        }

        const dailyLabels = [];
        const dailyLoans = [];
        const dailyDisbursements = [];
        const dailyRepayments = [];
        const dailyDates = [];

        // Get the date range from the data
        const firstDate = monthlyData.dates[0] || parseMonthLabel(monthlyData.labels[0]);
        const lastDate = monthlyData.dates[monthlyData.dates.length - 1] || parseMonthLabel(monthlyData.labels[monthlyData.labels.length - 1]);
        
        if (!firstDate || !lastDate) return monthlyData;

        // Create a map of monthly data by month key
        const monthlyMap = {};
        monthlyData.labels.forEach((label, index) => {
            const date = monthlyData.dates[index] || parseMonthLabel(label);
            if (date) {
                const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
                monthlyMap[key] = {
                    loans: monthlyData.loans[index] || 0,
                    disbursements: monthlyData.disbursements[index] || 0,
                    repayments: monthlyData.repayments[index] || 0,
                    date: date
                };
            }
        });

        // Generate all days from first to last date
        const startDate = new Date(firstDate);
        startDate.setDate(1);
        const endDate = new Date(lastDate);
        endDate.setMonth(endDate.getMonth() + 1);
        endDate.setDate(0);

        const currentDate = new Date(startDate);
        while (currentDate <= endDate) {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const day = currentDate.getDate();
            
            const monthKey = `${year}-${String(month + 1).padStart(2, '0')}`;
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            // Get monthly data for this month
            const monthlyDataPoint = monthlyMap[monthKey] || { loans: 0, disbursements: 0, repayments: 0 };
            
            // Distribute monthly data evenly across days
            const dailyLoan = monthlyDataPoint.loans / daysInMonth;
            const dailyDisburse = monthlyDataPoint.disbursements / daysInMonth;
            const dailyRepay = monthlyDataPoint.repayments / daysInMonth;

            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const label = `${monthNames[month]} ${day}`;
            
            dailyLabels.push(label);
            dailyLoans.push(dailyLoan);
            dailyDisbursements.push(dailyDisburse);
            dailyRepayments.push(dailyRepay);
            dailyDates.push(new Date(currentDate));

            currentDate.setDate(currentDate.getDate() + 1);
        }

        return {
            labels: dailyLabels,
            loans: dailyLoans,
            disbursements: dailyDisbursements,
            repayments: dailyRepayments,
            dates: dailyDates
        };
    }

    // Generate full weekly data from monthly data
    function generateFullWeeklyData(monthlyData) {
        if (!monthlyData || !monthlyData.labels || monthlyData.labels.length === 0) {
            return monthlyData;
        }

        // Get the date range from the data
        const firstDate = monthlyData.dates[0] || parseMonthLabel(monthlyData.labels[0]);
        const lastDate = monthlyData.dates[monthlyData.dates.length - 1] || parseMonthLabel(monthlyData.labels[monthlyData.labels.length - 1]);
        
        if (!firstDate || !lastDate) return monthlyData;

        // Create a map of monthly data by month key
        const monthlyMap = {};
        monthlyData.labels.forEach((label, index) => {
            const date = monthlyData.dates[index] || parseMonthLabel(label);
            if (date) {
                const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
                monthlyMap[key] = {
                    loans: monthlyData.loans[index] || 0,
                    disbursements: monthlyData.disbursements[index] || 0,
                    repayments: monthlyData.repayments[index] || 0,
                    date: date
                };
            }
        });

        // Get the first day of the week for the start date
        const startDate = getWeekStartDate(firstDate);
        const endDate = new Date(lastDate);
        endDate.setHours(23, 59, 59, 999);

        const weeklyLabels = [];
        const weeklyLoans = [];
        const weeklyDisbursements = [];
        const weeklyRepayments = [];
        const weeklyDates = [];

        const currentDate = new Date(startDate);
        let weekCounter = 0;

        while (currentDate <= endDate) {
            const weekStart = new Date(currentDate);
            const weekEnd = new Date(currentDate);
            weekEnd.setDate(weekEnd.getDate() + 6);
            
            // Get week number
            const weekNum = getWeekNumber(currentDate);
            const year = currentDate.getFullYear();
            
            // Aggregate data for this week from monthly data
            let weekLoans = 0;
            let weekDisbursements = 0;
            let weekRepayments = 0;
            
            // Calculate which months this week spans
            const monthKeys = new Set();
            const tempDate = new Date(weekStart);
            for (let i = 0; i < 7; i++) {
                const monthKey = `${tempDate.getFullYear()}-${String(tempDate.getMonth() + 1).padStart(2, '0')}`;
                monthKeys.add(monthKey);
                tempDate.setDate(tempDate.getDate() + 1);
            }
            
            // Get data from each month in this week
            monthKeys.forEach(monthKey => {
                if (monthlyMap[monthKey]) {
                    const data = monthlyMap[monthKey];
                    // Add monthly data (will be distributed across weeks)
                    weekLoans += data.loans;
                    weekDisbursements += data.disbursements;
                    weekRepayments += data.repayments;
                }
            });
            
            // If we have data, divide by number of months this week spans
            const monthCount = monthKeys.size;
            if (monthCount > 0) {
                weekLoans = weekLoans / monthCount;
                weekDisbursements = weekDisbursements / monthCount;
                weekRepayments = weekRepayments / monthCount;
            }

            const label = `W${weekNum} ${year}`;
            
            weeklyLabels.push(label);
            weeklyLoans.push(weekLoans);
            weeklyDisbursements.push(weekDisbursements);
            weeklyRepayments.push(weekRepayments);
            weeklyDates.push(new Date(currentDate));

            // Move to next week
            currentDate.setDate(currentDate.getDate() + 7);
            weekCounter++;
        }

        return {
            labels: weeklyLabels,
            loans: weeklyLoans,
            disbursements: weeklyDisbursements,
            repayments: weeklyRepayments,
            dates: weeklyDates
        };
    }

    // Generate full monthly data
    function generateFullMonthlyData(monthlyData) {
        return monthlyData;
    }

    // Generate full yearly data from monthly data
    function generateFullYearlyData(monthlyData) {
        if (!monthlyData || !monthlyData.labels || monthlyData.labels.length === 0) {
            return monthlyData;
        }

        const yearlyMap = {};
        
        monthlyData.labels.forEach((label, index) => {
            const date = monthlyData.dates[index] || parseMonthLabel(label);
            if (date) {
                const year = date.getFullYear();
                const key = `${year}`;
                
                if (!yearlyMap[key]) {
                    yearlyMap[key] = {
                        loans: 0,
                        disbursements: 0,
                        repayments: 0,
                        count: 0,
                        date: date
                    };
                }
                
                yearlyMap[key].loans += monthlyData.loans[index] || 0;
                yearlyMap[key].disbursements += monthlyData.disbursements[index] || 0;
                yearlyMap[key].repayments += monthlyData.repayments[index] || 0;
                yearlyMap[key].count++;
            }
        });

        const sortedKeys = Object.keys(yearlyMap).sort();
        const result = {
            labels: [],
            loans: [],
            disbursements: [],
            repayments: [],
            dates: []
        };

        sortedKeys.forEach(key => {
            const item = yearlyMap[key];
            result.labels.push(key);
            result.loans.push(item.loans);
            result.disbursements.push(item.disbursements);
            result.repayments.push(item.repayments);
            result.dates.push(item.date);
        });

        return result;
    }

    // Main data preparation function
    function prepareDataByPeriod(data, periodType) {
        if (!data || !data.labels || data.labels.length === 0) {
            return data;
        }

        switch(periodType) {
            case 'day':
                return generateFullDailyData(data);
            case 'week':
                return generateFullWeeklyData(data);
            case 'month':
                return generateFullMonthlyData(data);
            case 'year':
                return generateFullYearlyData(data);
            default:
                return data;
        }
    }

    // Main chart render function
    function renderChart(data, filterType) {
        const chartEl = document.querySelector("#loanStatsChart");
        if (!chartEl) {
            console.error('Chart container #loanStatsChart not found!');
            return;
        }

        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts is not loaded!');
            return;
        }

        // Prepare data based on selected period
        const preparedData = prepareDataByPeriod(data || allData, filterType || currentFilter);
        const months = preparedData.labels || [];
        const loans = preparedData.loans || [];
        const disbursements = preparedData.disbursements || [];
        const repayments = preparedData.repayments || [];

        console.log('Rendering chart with filter:', filterType || currentFilter);
        console.log('Data points:', months.length);
        console.log('First 10 labels:', months.slice(0, 10));
        console.log('Last 10 labels:', months.slice(-10));

        if (months.length === 0) {
            chartEl.innerHTML = '<div class="text-center py-10 text-gray-500 font-outfit">No data available for selected period</div>';
            chartRendered = true;
            return;
        }

        const { primaryColor, secondaryColor, secondaryLight } = getChartColors();
        const fontFamily = getFontFamily();

        const options = {
            series: [
                { name: "Loans", data: loans },
                { name: "Disbursements", data: disbursements },
                { name: "Repayments", data: repayments }
            ],
            legend: {
                show: true,
                position: "top",
                horizontalAlign: "left",
                labels: {
                    colors: '#6b7280',
                    useSeriesColors: false,
                    style: {
                        fontFamily: fontFamily,
                        fontSize: '13px',
                        fontWeight: 500
                    }
                },
                markers: {
                    width: 12,
                    height: 12,
                    strokeWidth: 0,
                    radius: 4
                }
            },
            colors: [primaryColor, secondaryColor, secondaryLight],
            chart: {
                height: 310,
                type: "area",
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                },
                fontFamily: fontFamily
            },
            fill: {
                gradient: {
                    enabled: true,
                    opacityFrom: 0.55,
                    opacityTo: 0,
                }
            },
            stroke: {
                curve: "smooth",
                width: ["3", "2", "2"]
            },
            markers: {
                size: 5,
                hover: { size: 7 },
                strokeColors: '#fff',
                strokeWidth: 2
            },
            grid: {
                borderColor: '#f1f1f1',
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } },
                padding: {
                    top: 0,
                    right: 10,
                    bottom: 0,
                    left: 10
                }
            },
            dataLabels: { enabled: false },
            tooltip: {
                enabled: true,
                x: { 
                    format: "MMM dd, yyyy",
                    style: {
                        fontFamily: fontFamily
                    }
                },
                y: {
                    formatter: function (value) {
                        return 'KES ' + value.toLocaleString();
                    }
                },
                style: {
                    fontFamily: fontFamily
                }
            },
            xaxis: {
                type: "category",
                categories: months,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    style: {
                        colors: '#6b7280',
                        fontSize: '12px',
                        fontFamily: fontFamily,
                        fontWeight: 400
                    },
                    rotate: (filterType === 'day' || filterType === 'week') ? -45 : 0,
                    rotateAlways: (filterType === 'day' || filterType === 'week'),
                    maxHeight: (filterType === 'day' || filterType === 'week') ? 80 : undefined,
                    trim: false,
                    hideOverlappingLabels: filterType !== 'day' && filterType !== 'week'
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        if (value >= 1000000) {
                            return (value / 1000000).toFixed(1) + 'M';
                        } else if (value >= 1000) {
                            return (value / 1000).toFixed(1) + 'K';
                        }
                        return value.toLocaleString();
                    },
                    style: {
                        colors: '#6b7280',
                        fontSize: '12px',
                        fontFamily: fontFamily,
                        fontWeight: 400
                    }
                }
            }
        };

        try {
            // Destroy existing chart
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }

            chartInstance = new ApexCharts(chartEl, options);
            chartInstance.render().then(() => {
                chartRendered = true;
                console.log('Chart rendered successfully with', months.length, 'data points');
                console.log('Filter:', filterType || currentFilter);
                window.loanChartInstance = chartInstance;
            }).catch(error => {
                console.error('Chart render error:', error);
            });
        } catch (error) {
            console.error('Error creating chart:', error);
        }
    }

    // ============================================================
    // DATE RANGE FILTERING
    // ============================================================

    function filterDataByDateRange(startDate, endDate, filterType) {
        console.log('Filtering data from', startDate, 'to', endDate, 'with filter:', filterType);

        if (!startDate || !endDate) {
            console.log('Invalid date range, showing all data');
            renderChart(originalMonthlyData, filterType || currentFilter);
            return;
        }

        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            console.log('Invalid date objects, showing all data');
            renderChart(originalMonthlyData, filterType || currentFilter);
            return;
        }

        currentDateRange = { start, end };

        const allMonths = originalMonthlyData.labels || [];
        const allLoans = originalMonthlyData.loans || [];
        const allDisbursements = originalMonthlyData.disbursements || [];
        const allRepayments = originalMonthlyData.repayments || [];
        const allDates = originalMonthlyData.dates || [];

        const filteredMonths = [];
        const filteredLoans = [];
        const filteredDisbursements = [];
        const filteredRepayments = [];
        const filteredDates = [];

        start.setHours(0, 0, 0, 0);
        end.setHours(23, 59, 59, 999);

        allMonths.forEach((monthLabel, index) => {
            const dateObj = allDates[index] || parseMonthLabel(monthLabel);
            
            if (dateObj && !isNaN(dateObj.getTime())) {
                const dateToCheck = new Date(dateObj);
                dateToCheck.setHours(12, 0, 0, 0);
                
                if (dateToCheck >= start && dateToCheck <= end) {
                    filteredMonths.push(monthLabel);
                    filteredLoans.push(allLoans[index] || 0);
                    filteredDisbursements.push(allDisbursements[index] || 0);
                    filteredRepayments.push(allRepayments[index] || 0);
                    filteredDates.push(dateObj);
                }
            }
        });

        console.log('Filtered data:', {
            original: allMonths.length,
            filtered: filteredMonths.length,
            range: `${start.toLocaleDateString()} - ${end.toLocaleDateString()}`
        });

        const filteredData = {
            labels: filteredMonths,
            loans: filteredLoans,
            disbursements: filteredDisbursements,
            repayments: filteredRepayments,
            dates: filteredDates
        };

        chartRendered = false;
        renderChart(filteredData, filterType || currentFilter);
    }

    // Helper to parse month label
    function parseMonthLabel(label) {
        const parts = label.split(' ');
        if (parts.length === 2) {
            const monthMap = {
                'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5,
                'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11
            };
            const month = monthMap[parts[0]];
            const year = parseInt(parts[1]);
            if (!isNaN(month) && !isNaN(year)) {
                return new Date(year, month, 1);
            }
        }
        return null;
    }

    // ============================================================
    // ALPINE.JS COMPONENT
    // ============================================================
    
    window.loanStatsChart = function() {
        return {
            chart: null,
            selected: 'month',
            datePickerInstance: null,
            chartData: {
                months: originalMonthlyData.labels || [],
                loans: originalMonthlyData.loans || [],
                disbursements: originalMonthlyData.disbursements || [],
                repayments: originalMonthlyData.repayments || []
            },

            init() {
                console.log('Alpine component initializing...');
                
                this.$nextTick(() => {
                    const chartEl = document.querySelector("#loanStatsChart");
                    if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
                        console.log('Chart already rendered');
                        this.chart = window.loanChartInstance || null;
                        this.initDatePicker();
                        return;
                    }
                    
                    // Set all data
                    allData = originalMonthlyData;
                    renderChart(allData, 'month');
                    
                    setTimeout(() => {
                        this.chart = window.loanChartInstance || null;
                    }, 200);
                    
                    this.initDatePicker();
                });
            },

            initDatePicker() {
                const datePicker = document.querySelector("#loanStatsDatePicker");
                if (datePicker && typeof flatpickr !== 'undefined') {
                    // Set default to show all data (from first to last)
                    const firstDate = originalMonthlyData.dates[0] || new Date();
                    const lastDate = originalMonthlyData.dates[originalMonthlyData.dates.length - 1] || new Date();
                    
                    this.datePickerInstance = flatpickr(datePicker, {
                        mode: "range",
                        dateFormat: "M d, Y",
                        altInput: true,
                        altFormat: "M d, Y",
                        defaultDate: [firstDate, lastDate],
                        onChange: (selectedDates) => {
                            if (selectedDates.length === 2) {
                                const startDate = selectedDates[0];
                                const endDate = selectedDates[1];
                                console.log("Date range selected:", startDate, "to", endDate);
                                
                                filterDataByDateRange(startDate, endDate, this.selected);
                                
                                setTimeout(() => {
                                    this.chart = window.loanChartInstance || null;
                                }, 200);
                            }
                        }
                    });
                    
                    console.log('Date picker initialized with range:', firstDate, 'to', lastDate);
                } else {
                    console.warn('Date picker not found or flatpickr not loaded');
                }
            },

            updateChart(type) {
                this.selected = type;
                currentFilter = type;
                
                console.log('Updating chart filter to:', type);
                
                if (this.datePickerInstance) {
                    const selectedDates = this.datePickerInstance.selectedDates;
                    if (selectedDates && selectedDates.length === 2) {
                        filterDataByDateRange(selectedDates[0], selectedDates[1], type);
                    } else {
                        // Show all data
                        renderChart(originalMonthlyData, type);
                    }
                } else {
                    chartRendered = false;
                    renderChart(originalMonthlyData, type);
                    
                    setTimeout(() => {
                        this.chart = window.loanChartInstance || null;
                    }, 200);
                }
                
                setTimeout(() => {
                    this.chart = window.loanChartInstance || null;
                }, 200);
            },

            filterData(startDate, endDate) {
                filterDataByDateRange(startDate, endDate, this.selected);
                setTimeout(() => {
                    this.chart = window.loanChartInstance || null;
                }, 200);
            }
        };
    };

    // ============================================================
    // INITIAL RENDER
    // ============================================================

    function initChartOnReady() {
        const chartEl = document.querySelector("#loanStatsChart");
        if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
            console.log('Chart already exists');
            chartRendered = true;
            return;
        }
        
        if (typeof Alpine !== 'undefined' && document.querySelector('[x-data]')?.__x) {
            console.log('Alpine already initialized, letting Alpine handle chart');
            return;
        }
        
        allData = originalMonthlyData;
        renderChart(allData, 'month');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChartOnReady);
    } else {
        initChartOnReady();
    }

    document.addEventListener('alpine:initialized', function() {
        console.log('Alpine initialized event fired');
        
        const chartEl = document.querySelector("#loanStatsChart");
        if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
            console.log('Chart already rendered');
            return;
        }
        
        setTimeout(() => {
            if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
                console.log('Chart rendered by Alpine');
                chartRendered = true;
                return;
            }
            allData = originalMonthlyData;
            renderChart(allData, 'month');
        }, 300);
    });

    window.renderLoanChart = renderChart;
    window.filterLoanChartData = filterDataByDateRange;
    window.loanChartAllData = originalMonthlyData;

    console.log('Monthly Loans Chart script loaded');
    console.log('Total data points:', originalMonthlyData.labels.length);
    console.log('Date range:', originalMonthlyData.dates[0], 'to', originalMonthlyData.dates[originalMonthlyData.dates.length - 1]);

})();
</script>