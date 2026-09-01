<div 
    class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6"
    x-data="customerBalanceChart()"
    x-init="init()"
>
    <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
        <div class="w-full">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 font-outfit">
                Customer Balance
            </h3>
            <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400 font-outfit">
                Total borrowed, repaid, and outstanding balance per customer
            </p>
        </div>
    </div>
    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <div id="customerBalanceChart" class="-ml-4 min-w-[700px] pl-2"></div>
    </div>
</div>

<script>
// ============================================================
// CUSTOMER BALANCE CHART
// ============================================================

(function() {
    'use strict';
    
    let chartInstance = null;
    let chartRendered = false;

    // Get chart data from PHP
    const chartData = @json($reportData ?? []);

    function getChartColors() {
        const computedStyle = getComputedStyle(document.documentElement);
        const primaryColor = computedStyle.getPropertyValue('--primary-color').trim() || '#1E3A8A';
        const secondaryColor = computedStyle.getPropertyValue('--secondary-color').trim() || '#3B82F6';
        const secondaryLight = secondaryColor + '80';
        return { primaryColor, secondaryColor, secondaryLight };
    }

    function getFontFamily() {
        return 'Outfit, sans-serif';
    }

    function renderChart() {
        if (chartRendered) {
            console.log('Chart already rendered, skipping...');
            return;
        }

        const chartEl = document.querySelector("#customerBalanceChart");
        if (!chartEl) {
            console.error('Chart container #customerBalanceChart not found!');
            return;
        }

        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts is not loaded!');
            return;
        }

        const customers = chartData.customers || [];
        if (customers.length === 0) {
            chartEl.innerHTML = '<div class="text-center py-10 text-gray-500 font-outfit">No customer data available</div>';
            chartRendered = true;
            return;
        }

        const labels = customers.map(c => c.name).slice(0, 15);
        const borrowed = customers.map(c => c.total_borrowed).slice(0, 15);
        const repaid = customers.map(c => c.total_repaid).slice(0, 15);
        const balance = customers.map(c => c.balance).slice(0, 15);

        const { primaryColor, secondaryColor, secondaryLight } = getChartColors();
        const fontFamily = getFontFamily();

        const options = {
            series: [
                { name: 'Total Borrowed', data: borrowed },
                { name: 'Total Repaid', data: repaid },
                { name: 'Outstanding Balance', data: balance }
            ],
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
            colors: [primaryColor, '#10B981', '#EF4444'],
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
                console.log('Customer Balance Chart rendered successfully');
                window.customerBalanceChartInstance = chartInstance;
            }).catch(error => {
                console.error('Chart render error:', error);
            });
        } catch (error) {
            console.error('Error creating chart:', error);
        }
    }

    // ============================================================
    // ALPINE.JS COMPONENT
    // ============================================================
    
    window.customerBalanceChart = function() {
        return {
            init() {
                console.log('Customer Balance Chart component initializing...');
                this.$nextTick(() => {
                    const chartEl = document.querySelector("#customerBalanceChart");
                    if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
                        console.log('Chart already rendered');
                        return;
                    }
                    renderChart();
                });
            }
        };
    };

    // ============================================================
    // INITIAL RENDER
    // ============================================================

    function initChartOnReady() {
        const chartEl = document.querySelector("#customerBalanceChart");
        if (chartEl && chartEl.querySelector('.apexcharts-canvas')) {
            chartRendered = true;
            return;
        }
        
        if (typeof Alpine !== 'undefined' && document.querySelector('[x-data="customerBalanceChart()"]')?.__x) {
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
        const chartEl = document.querySelector("#customerBalanceChart");
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

    window.renderCustomerBalanceChart = renderChart;

    console.log('Customer Balance Chart script loaded');

})();
</script>