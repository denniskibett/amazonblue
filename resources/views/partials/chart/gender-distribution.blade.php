<div 
    class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6"
    x-data="genderDistributionChart()"
    x-init="init()"
>
    <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
        <div class="w-full">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 font-outfit">
                Gender Distribution
            </h3>
            <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400 font-outfit">
                Customer breakdown by gender
            </p>
        </div>
    </div>
    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div id="genderDistributionChart" class="-ml-4 min-w-[700px] pl-2"></div>
    </div>
</div>

<script>
// ============================================================
// GENDER DISTRIBUTION CHART
// ============================================================

(function() {
    'use strict';
    
    let chartInstance = null;
    let chartRendered = false;

    const chartData = @json($reportData ?? []);

    function getChartColors() {
        const computedStyle = getComputedStyle(document.documentElement);
        const secondaryColor = computedStyle.getPropertyValue('--secondary-color').trim() || '#3B82F6';
        return { secondaryColor };
    }

    function getFontFamily() {
        return 'Outfit, sans-serif';
    }

    function renderChart() {
        if (chartRendered) {
            console.log('Chart already rendered, skipping...');
            return;
        }

        const chartEl = document.querySelector("#genderDistributionChart");
        if (!chartEl) {
            console.error('Chart container #genderDistributionChart not found!');
            return;
        }

        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts is not loaded!');
            return;
        }

        const demographics = chartData.demographics || {};
        const genderData = demographics.gender || { male: 0, female: 0, other: 0 };

        if (Object.values(genderData).every(v => v === 0)) {
            chartEl.innerHTML = '<div class="text-center py-10 text-gray-500 font-outfit">No gender data available</div>';
            chartRendered = true;
            return;
        }

        const { secondaryColor } = getChartColors();
        const fontFamily = getFontFamily();

        const options = {
            series: Object.values(genderData),
            labels: ['Male', 'Female', 'Other'],
            colors: [secondaryColor, '#EC4899', '#8B5CF6'],
            chart: {
                type: 'pie',
                height: 350,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                },
                fontFamily: fontFamily
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                labels: {
                    style: {
                        fontFamily: fontFamily,
                        fontSize: '13px',
                        fontWeight: 500
                    }
                }
            },
            dataLabels: {
                enabled: true,
                style: {
                    fontFamily: fontFamily,
                    fontSize: '12px',
                    fontWeight: 400
                },
                formatter: function(value, { seriesIndex, w }) {
                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                    return ((value / total) * 100).toFixed(1) + '%';
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' customers';
                    }
                },
                style: {
                    fontFamily: fontFamily
                }
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
                console.log('Gender Distribution Chart rendered successfully');
                window.genderDistributionChartInstance = chartInstance;
            }).catch(error => {
                console.error('Chart render error:', error);
            });
        } catch (error) {
            console.error('Error creating chart:', error);
        }
    }

    window.genderDistributionChart = function() {
        return {
            init() {
                console.log('Gender Distribution Chart component initializing...');
                this.$nextTick(() => {
                    const chartEl = document.querySelector("#genderDistributionChart");
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
        const chartEl = document.querySelector("#genderDistributionChart");
        if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
            chartRendered = true;
            return;
        }
        
        if (typeof Alpine !== 'undefined' && document.querySelector('[x-data="genderDistributionChart()"]')?.__x) {
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
        const chartEl = document.querySelector("#genderDistributionChart");
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

    window.renderGenderDistributionChart = renderChart;

    console.log('Gender Distribution Chart script loaded');

})();
</script>