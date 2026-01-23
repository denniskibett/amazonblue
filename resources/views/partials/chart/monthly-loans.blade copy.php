<div
  class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6"
  x-data="chartData()"
  x-init="initChart()"
>
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
      <span x-text="periodTitle"></span> Performance
    </h3>

    <div class="flex items-center space-x-4">
      <!-- Period Filter -->
      <div x-data="{ openPeriod: false }" class="relative">
        <button
          @click="openPeriod = !openPeriod"
          class="flex items-center px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          <span x-text="periodLabel"></span>
          <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
        <div
          x-show="openPeriod"
          @click.outside="openPeriod = false"
          class="absolute right-0 z-40 w-40 p-2 mt-1 space-y-1 bg-white border border-gray-200 rounded-2xl shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark"
        >
          <button
            class="flex w-full px-3 py-2 font-medium text-left rounded-lg text-theme-xs hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-300"
            :class="period === 'day' ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400'"
            @click="changePeriod('day')"
          >
            Daily
          </button>
          <button
            class="flex w-full px-3 py-2 font-medium text-left rounded-lg text-theme-xs hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-300"
            :class="period === 'week' ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400'"
            @click="changePeriod('week')"
          >
            Weekly
          </button>
          <button
            class="flex w-full px-3 py-2 font-medium text-left rounded-lg text-theme-xs hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-300"
            :class="period === 'month' ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400'"
            @click="changePeriod('month')"
          >
            Monthly
          </button>
          <button
            class="flex w-full px-3 py-2 font-medium text-left rounded-lg text-theme-xs hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-300"
            :class="period === 'year' ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400'"
            @click="changePeriod('year')"
          >
            Yearly
          </button>
        </div>
      </div>

      <!-- Options Menu -->
      <div x-data="{ openDropDown: false }" class="relative">
        <button
          @click="openDropDown = !openDropDown"
          :class="openDropDown ? 'text-gray-700 dark:text-white' : 'text-gray-400 hover:text-gray-700 dark:hover:text-white'"
        >
          <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M10.2441 6C10.2441 5.0335 11.0276 4.25 11.9941 4.25H12.0041C12.9706 4.25 13.7541 5.0335 13.7541 6C13.7541 6.9665 12.9706 7.75 12.0041 7.75H11.9941C11.0276 7.75 10.2441 6.9665 10.2441 6ZM10.2441 18C10.2441 17.0335 11.0276 16.25 11.9941 16.25H12.0041C12.9706 16.25 13.7541 17.0335 13.7541 18C13.7541 18.9665 12.9706 19.75 12.0041 19.75H11.9941C11.0276 19.75 10.2441 18.9665 10.2441 18ZM11.9941 10.25C11.0276 10.25 10.2441 11.0335 10.2441 12C10.2441 12.9665 11.0276 13.75 11.9941 13.75H12.0041C12.9706 13.75 13.7541 12.9665 13.7541 12C13.7541 11.0335 12.9706 10.25 12.0041 10.25H11.9941Z"
              fill=""
            />
          </svg>
        </button>
        <div
          x-show="openDropDown"
          @click.outside="openDropDown = false"
          class="absolute right-0 z-40 w-40 p-2 space-y-1 bg-white border border-gray-200 top-full rounded-2xl shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark"
        >
          <button
            class="flex w-full px-3 py-2 font-medium text-left text-gray-500 rounded-lg text-theme-xs hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
            @click="exportChart"
          >
            Export as PNG
          </button>
          <button
            class="flex w-full px-3 py-2 font-medium text-left text-gray-500 rounded-lg text-theme-xs hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
            @click="resetZoom"
          >
            Reset View
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <div class="-ml-5 min-w-[650px] pl-2 xl:min-w-full">
      <div id="chartOne" class="-ml-5 h-full min-w-[650px] pl-2 xl:min-w-full"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
  function chartData() {
    return {
      chart: null,
      period: 'month', // Default period
      periodLabel: 'Monthly',
      periodTitle: 'Monthly',
      monthlyData: [], // Will be populated from PHP
      
      initChart() {
        // Get monthly data from PHP
        this.monthlyData = @json($monthlyData);
        this.updateChart();
      },
      
      updateChart() {
        // Filter out future months and empty data
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth(); // 0-indexed
        
        const filteredData = this.monthlyData.filter(item => {
          const [monthName, year] = item.month.split(' ');
          const monthIndex = this.getMonthIndex(monthName);
          return (
            parseInt(year) < currentYear || 
            (parseInt(year) === currentYear && monthIndex <= currentMonth)
          );
        });

        // Prepare data based on current period
        let categories, loansData, disbursementsData, repaymentsData;
        
        if (this.period === 'month') {
          categories = filteredData.map(item => item.month);
          loansData = filteredData.map(item => item.loans);
          disbursementsData = filteredData.map(item => item.disbursements);
          repaymentsData = filteredData.map(item => item.repayments);
        } 
        else if (this.period === 'year') {
          // Aggregate data by year
          const yearlyData = {};
          filteredData.forEach(item => {
            const year = item.month.split(' ')[1];
            if (!yearlyData[year]) {
              yearlyData[year] = {
                loans: 0,
                disbursements: 0,
                repayments: 0
              };
            }
            yearlyData[year].loans += item.loans;
            yearlyData[year].disbursements += item.disbursements;
            yearlyData[year].repayments += item.repayments;
          });
          
          categories = Object.keys(yearlyData);
          loansData = Object.values(yearlyData).map(item => item.loans);
          disbursementsData = Object.values(yearlyData).map(item => item.disbursements);
          repaymentsData = Object.values(yearlyData).map(item => item.repayments);
        }
        else {
          // For day/week - show recent data (demo only)
          const recentData = filteredData.slice(-7);
          categories = recentData.map(item => item.month);
          loansData = recentData.map(item => item.loans);
          disbursementsData = recentData.map(item => item.disbursements);
          repaymentsData = recentData.map(item => item.repayments);
        }

        // Destroy existing chart if exists
        if (this.chart) {
          this.chart.destroy();
        }

        // Chart configuration
        const options = {
          series: [
            {
              name: 'Loans',
              data: loansData
            },
            {
              name: 'Disbursements (KES)',
              data: disbursementsData
            },
            {
              name: 'Repayments (KES)',
              data: repaymentsData
            }
          ],
          chart: {
            type: 'bar',
            height: 350,
            stacked: false,
            toolbar: {
              show: true,
              tools: {
                download: true,
                selection: false,
                zoom: false,
                zoomin: false,
                zoomout: false,
                pan: false,
                reset: true
              }
            },
            fontFamily: 'inherit'
          },
          plotOptions: {
            bar: {
              horizontal: false,
              columnWidth: '55%',
              borderRadius: 4,
              borderRadiusApplication: 'end'
            },
          },
          colors: ['#3C50E0', '#10B981', '#F97316'],
          dataLabels: {
            enabled: false
          },
          stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
          },
          xaxis: {
            categories: categories,
            labels: {
              style: {
                colors: '#6B7280',
                fontSize: '12px',
                fontFamily: 'inherit'
              }
            },
            axisBorder: {
              show: false
            },
            axisTicks: {
              show: false
            }
          },
          yaxis: {
            labels: {
              style: {
                colors: '#6B7280',
                fontSize: '12px',
                fontFamily: 'inherit'
              },
              formatter: function (val) {
                return val.toLocaleString();
              }
            }
          },
          grid: {
            borderColor: '#F1F5F9',
            strokeDashArray: 4,
            yaxis: {
              lines: {
                show: true
              }
            }
          },
          fill: {
            opacity: 1
          },
          legend: {
            position: 'top',
            horizontalAlign: 'left',
            fontFamily: 'inherit',
            markers: {
              radius: 12,
              width: 12,
              height: 12
            },
            itemMargin: {
              horizontal: 10
            }
          },
          tooltip: {
            y: {
              formatter: function (val, { seriesIndex }) {
                if (seriesIndex === 0) {
                  return val + ' loans';
                }
                return 'KES ' + val.toLocaleString();
              }
            },
            style: {
              fontFamily: 'inherit'
            }
          },
          responsive: [{
            breakpoint: 768,
            options: {
              chart: {
                height: 300
              },
              legend: {
                position: 'bottom'
              }
            }
          }]
        };

        this.chart = new ApexCharts(document.querySelector("#chartOne"), options);
        this.chart.render();
      },
      
      changePeriod(newPeriod) {
        this.period = newPeriod;
        this.periodLabel = 
          newPeriod === 'day' ? 'Daily' : 
          newPeriod === 'week' ? 'Weekly' : 
          newPeriod === 'month' ? 'Monthly' : 'Yearly';
          
        this.periodTitle = this.periodLabel;
        this.updateChart();
      },
      
      getMonthIndex(monthName) {
        const months = [
          'January', 'February', 'March', 'April', 'May', 'June',
          'July', 'August', 'September', 'October', 'November', 'December'
        ];
        return months.indexOf(monthName);
      },
      
      exportChart() {
        if (this.chart) {
          this.chart.dataURI().then(({ imgURI }) => {
            const link = document.createElement('a');
            link.href = imgURI;
            link.download = 'performance-' + new Date().toISOString().slice(0, 10) + '.png';
            link.click();
          });
        }
      },
      
      resetZoom() {
        if (this.chart) {
          this.chart.reset();
        }
      }
    };
  }
</script>