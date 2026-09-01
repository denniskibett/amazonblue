<div 
    class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6"
    x-data="statusDistributionChart()"
    x-init="init()"
>
    <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
        <div class="w-full">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 font-outfit">
                Loan Status Distribution
            </h3>
            <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400 font-outfit">
                Breakdown of loans by status
            </p>
        </div>
    </div>
    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div id="statusDistributionChart" class="-ml-4 min-w-[700px] pl-2"></div>
    </div>
</div>

<script>
// ============================================================
// STATUS DISTRIBUTION CHART
// ============================================================

(function() {
    'use strict';
    
    let chartInstance = null;
    let chartRendered = false;

    const chartData = @json($reportData ?? []);

    function getChartColors() {
        const computedStyle = getComputedStyle(document.documentElement);
        const primaryColor = computedStyle.getPropertyValue('--primary-color').trim() || '#1E3A8A';
        const secondaryColor = computedStyle.getPropertyValue('--secondary-color').trim() || '#3B82F6';
        return { primaryColor, secondaryColor };
    }

    function getFontFamily() {
        return 'Outfit, sans-serif';
    }

    function renderChart() {
        if (chartRendered) {
            console.log('Chart already rendered, skipping...');
            return;
        }

        const chartEl = document.querySelector("#statusDistributionChart");
        if (!chartEl) {
            console.error('Chart container #statusDistributionChart not found!');
            return;
        }

        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts is not loaded!');
            return;
        }

        const statuses = ['Pending', 'Approved', 'Disbursed', 'Rejected', 'Repaid'];
        const counts = [
            chartData.status_breakdown?.pending || 0,
            chartData.status_breakdown?.approved || 0,
            chartData.status_breakdown?.disbursed || 0,
            chartData.status_breakdown?.rejected || 0,
            chartData.status_breakdown?.repaid || 0
        ];

        if (counts.every(c => c === 0)) {
            chartEl.innerHTML = '<div class="text-center py-10 text-gray-500 font-outfit">No loan status data available</div>';
            chartRendered = true;
            return;
        }

        const { primaryColor, secondaryColor } = getChartColors();
        const fontFamily = getFontFamily();

        const options = {
            series: [{ name: 'Loan Count', data: counts }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                },
                fontFamily: fontFamily
            },
            colors: [secondaryColor],
            xaxis: {
                categories: statuses,
                labels: {
                    style: {
                        fontFamily: fontFamily,
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontFamily: fontFamily,
                        fontSize: '12px'
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                labels: {
                    style: {
                        fontFamily: fontFamily,
                        fontSize: '13px',
                        fontWeight: 500
                    }
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: false,
                    columnWidth: '55%',
                }
            },
            dataLabels: { enabled: false },
            grid: {
                borderColor: '#f1f1f1',
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } }
            }
        };

        try {
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }

            chartInstance = new ApexCharts(chartEl, options);
            chartInstance.render().then(() => {
                chartRendered = true;
                console.log('Status Distribution Chart rendered successfully');
                window.statusDistributionChartInstance = chartInstance;
            }).catch(error => {
                console.error('Chart render error:', error);
            });
        } catch (error) {
            console.error('Error creating chart:', error);
        }
    }

    window.statusDistributionChart = function() {
        return {
            init() {
                console.log('Status Distribution Chart component initializing...');
                this.$nextTick(() => {
                    const chartEl = document.querySelector("#statusDistributionChart");
                    if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
                        console.log('Chart already rendered');
                        return;
                    }
                    renderChart();
                });
            }
        };
    };

    function initChartOnReady() {
        const chartEl = document.querySelector("#statusDistributionChart");
        if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
            chartRendered = true;
            return;
        }
        
        if (typeof Alpine !== 'undefined' && document.querySelector('[x-data="statusDistributionChart()"]')?.__x) {
            return;
        }
        
        renderChart();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChartOnReady);
    } else {
        initChartOnReady();
    }

    document.addEventListener('alpine:initialized', function() {
        const chartEl = document.querySelector("#statusDistributionChart");
        if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
            chartRendered = true;
            return;
        }
        
        setTimeout(() => {
            if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
                chartRendered = true;
                return;
            }
            renderChart();
        }, 300);
    });

    window.renderStatusDistributionChart = renderChart;

    console.log('Status Distribution Chart script loaded');

})();
</script>