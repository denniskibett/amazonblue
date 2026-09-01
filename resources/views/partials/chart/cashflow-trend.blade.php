<div 
    class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6"
    x-data="cashflowTrendChart()"
    x-init="init()"
>
    <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
        <div class="w-full">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 font-outfit">
                Cashflow Trend
            </h3>
            <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400 font-outfit">
                Inflows, outflows, and net cashflow over time
            </p>
        </div>
    </div>
    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div id="cashflowTrendChart" class="-ml-4 min-w-[700px] pl-2"></div>
    </div>
</div>

<script>
// ============================================================
// CASHFLOW TREND CHART
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

        const chartEl = document.querySelector("#cashflowTrendChart");
        if (!chartEl) {
            console.error('Chart container #cashflowTrendChart not found!');
            return;
        }

        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts is not loaded!');
            return;
        }

        const dailyData = chartData.daily_data || [];
        if (dailyData.length === 0) {
            chartEl.innerHTML = '<div class="text-center py-10 text-gray-500 font-outfit">No cashflow data available</div>';
            chartRendered = true;
            return;
        }

        const labels = dailyData.map(d => d.date);
        const inflows = dailyData.map(d => d.inflows);
        const outflows = dailyData.map(d => d.outflows);
        const net = dailyData.map(d => d.net);

        const { primaryColor, secondaryColor } = getChartColors();
        const fontFamily = getFontFamily();

        const options = {
            series: [
                { name: 'Inflows', data: inflows },
                { name: 'Outflows', data: outflows },
                { name: 'Net Flow', data: net }
            ],
            chart: {
                type: 'area',
                height: 350,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                },
                fontFamily: fontFamily
            },
            colors: ['#10B981', '#EF4444', secondaryColor],
            fill: {
                gradient: {
                    enabled: true,
                    opacityFrom: 0.55,
                    opacityTo: 0,
                }
            },
            stroke: {
                curve: 'smooth',
                width: [3, 2, 2]
            },
            markers: {
                size: 5,
                hover: { size: 7 }
            },
            xaxis: {
                categories: labels,
                labels: {
                    rotate: -45,
                    style: {
                        fontFamily: fontFamily,
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(value) {
                        if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                        if (value >= 1000) return (value / 1000).toFixed(1) + 'K';
                        return value.toLocaleString();
                    },
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
            tooltip: {
                y: {
                    formatter: function(value) {
                        return 'KES ' + value.toLocaleString();
                    }
                },
                style: {
                    fontFamily: fontFamily
                }
            },
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
                console.log('Cashflow Trend Chart rendered successfully');
                window.cashflowTrendChartInstance = chartInstance;
            }).catch(error => {
                console.error('Chart render error:', error);
            });
        } catch (error) {
            console.error('Error creating chart:', error);
        }
    }

    window.cashflowTrendChart = function() {
        return {
            init() {
                console.log('Cashflow Trend Chart component initializing...');
                this.$nextTick(() => {
                    const chartEl = document.querySelector("#cashflowTrendChart");
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
        const chartEl = document.querySelector("#cashflowTrendChart");
        if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
            chartRendered = true;
            return;
        }
        
        if (typeof Alpine !== 'undefined' && document.querySelector('[x-data="cashflowTrendChart()"]')?.__x) {
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
        const chartEl = document.querySelector("#cashflowTrendChart");
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

    window.renderCashflowTrendChart = renderChart;

    console.log('Cashflow Trend Chart script loaded');

})();
</script>