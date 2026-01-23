<div
  class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6"
  x-data="loanStatsChart()"
  x-init="init()"
>
  <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
    <div class="w-full">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        Loan Statistics
      </h3>
      <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400">
        Loan activity overview
      </p>
    </div>

    <div class="flex items-start w-full gap-3 sm:justify-end">
      <div class="inline-flex w-fit items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
        <button
          @click="updateChart('loans')"
          :class="selected === 'loans' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
          class="px-3 py-2 font-medium rounded-md text-theme-sm hover:text-gray-900 dark:hover:text-white"
        >
          Loans
        </button>
        <button
          @click="updateChart('disbursements')"
          :class="selected === 'disbursements' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
          class="px-3 py-2 font-medium rounded-md text-theme-sm hover:text-gray-900 dark:hover:text-white"
        >
          Disbursements
        </button>
        <button
          @click="updateChart('repayments')"
          :class="selected === 'repayments' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
          class="px-3 py-2 font-medium rounded-md text-theme-sm hover:text-gray-900 dark:hover:text-white"
        >
          Repayments
        </button>
      </div>

      <div class="relative w-fit">
        <input
          id="loanStatsDatePicker"
          class="datepicker h-10 w-full max-w-11 rounded-lg border border-gray-200 bg-white py-2.5 pl-[34px] pr-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs focus:outline-none focus:ring-0 focus-visible:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 xl:max-w-fit xl:pl-11"
          placeholder="Select dates"
          data-class="flatpickr-right"
          readonly="readonly"
        />
        <div
          class="absolute inset-0 right-auto flex items-center pointer-events-none left-4"
        >
          <svg
            class="fill-gray-700 dark:fill-gray-400"
            width="20"
            height="20"
            viewBox="0 0 20 20"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M6.66683 1.54199C7.08104 1.54199 7.41683 1.87778 7.41683 2.29199V3.00033H12.5835V2.29199C12.5835 1.87778 12.9193 1.54199 13.3335 1.54199C13.7477 1.54199 14.0835 1.87778 14.0835 2.29199V3.00033L15.4168 3.00033C16.5214 3.00033 17.4168 3.89576 17.4168 5.00033V7.50033V15.8337C17.4168 16.9382 16.5214 17.8337 15.4168 17.8337H4.5835C3.47893 17.8337 2.5835 16.9382 2.5835 15.8337V7.50033V5.00033C2.5835 3.89576 3.47893 3.00033 4.5835 3.00033L5.91683 3.00033V2.29199C5.91683 1.87778 6.25262 1.54199 6.66683 1.54199ZM6.66683 4.50033H4.5835C4.30735 4.50033 4.0835 4.72418 4.0835 5.00033V6.75033H15.9168V5.00033C15.9168 4.72418 15.693 4.50033 15.4168 4.50033H13.3335H6.66683ZM15.9168 8.25033H4.0835V15.8337C4.0835 16.1098 4.30735 16.3337 4.5835 16.3337H15.4168C15.693 16.3337 15.9168 16.1098 15.9168 15.8337V8.25033Z"
              fill=""
            />
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
// Loan Statistics Chart Component
function loanStatsChart() {
  return {
    chart: null,
    selected: 'loans',
    chartData: {!! json_encode($chartData ?? [
      'months' => [],
      'loans' => [],
      'disbursements' => [],
      'repayments' => []
    ]) !!},

    init() {
      console.log('Initializing chart with data:', this.chartData);
      this.initChart();
      this.initDatePicker();
    },

    initChart() {
      const months = this.chartData.months || [];
      const loans = this.chartData.loans || [];
      const disbursements = this.chartData.disbursements || [];
      const repayments = this.chartData.repayments || [];

      const options = {
        series: [
          { name: "Loans", data: loans },
          { name: "Disbursements", data: disbursements },
          { name: "Repayments", data: repayments }
        ],
        legend: {
          show: false,
          position: "top",
          horizontalAlign: "left",
        },
        colors: ["#1E3A8A", "#3B82F6", "#BFDBFE"], // Distinct blues
        chart: {
          height: 310,
          type: "area",
          toolbar: { show: false },
          animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 800,
          }
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
          hover: { size: 7 }
        },
        grid: {
          borderColor: '#f1f1f1',
          xaxis: { lines: { show: false } },
          yaxis: { lines: { show: true } }
        },
        dataLabels: { enabled: false },
        tooltip: {
          enabled: true,
          x: { format: "MMM yyyy" },
          y: {
            formatter: function (value, { seriesIndex }) {
              return 'KES ' + value.toLocaleString();
            }
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
              fontFamily: 'inherit' // use layout's font
            }
          }
        },
        yaxis: {
          labels: {
            formatter: function (value) {
              return value.toLocaleString();
            },
            style: {
              colors: '#6b7280',
              fontSize: '12px',
              fontFamily: 'inherit' // use layout's font
            }
          }
        }
      };

      const chartEl = document.querySelector("#loanStatsChart");
      if (chartEl) {
        this.chart = new ApexCharts(chartEl, options);
        this.chart.render();
      } else {
        console.error('Chart container not found');
      }
    },

    initDatePicker() {
      const datePicker = document.querySelector("#loanStatsDatePicker");
      if (datePicker) {
        flatpickr(datePicker, {
          mode: "range",
          dateFormat: "d-m-Y",
          defaultDate: [
            new Date(new Date().setMonth(new Date().getMonth() - 5)),
            new Date()
          ],
          onChange: (selectedDates) => {
            if (selectedDates.length === 2) {
              console.log("Date range selected:", selectedDates);
              // Optionally use: this.filterDataByDateRange(...)
            }
          }
        });
      }
    },

    updateChart(type) {
      this.selected = type;
      const widths = ["loans", "disbursements", "repayments"].map(t =>
        t === type ? "3" : "2"
      );
      this.chart.updateOptions({
        stroke: { width: widths }
      });
    },

    filterDataByDateRange(startDate, endDate) {
      console.log('Filtering data from', startDate, 'to', endDate);
    }
  };
}

// Fallback for when Alpine.js is not available
document.addEventListener('DOMContentLoaded', function () {
  const chartData = {!! json_encode($chartData ?? [
    'months' => [],
    'loans' => [],
    'disbursements' => [],
    'repayments' => []
  ]) !!};

  console.log('Fallback chart data:', chartData);

  if (typeof Alpine === 'undefined') {
    const options = {
      series: [
        { name: "Loans", data: chartData.loans || [] },
        { name: "Disbursements", data: chartData.disbursements || [] },
        { name: "Repayments", data: chartData.repayments || [] }
      ],
      chart: {
        type: 'area',
        height: 350,
        toolbar: { show: false }
      },
      xaxis: {
        categories: chartData.months || [],
        labels: {
          style: {
            fontFamily: 'inherit',
            fontSize: '12px'
          }
        }
      },
      colors: ["#1E3A8A", "#3B82F6", "#BFDBFE"], // Distinct blues
      stroke: {
        curve: "smooth",
        width: [3, 2, 2]
      }
    };

    const chartEl = document.querySelector("#loanStatsChart");
    if (chartEl) {
      const fallbackChart = new ApexCharts(chartEl, options);
      fallbackChart.render();
    }
  }
});
</script>
