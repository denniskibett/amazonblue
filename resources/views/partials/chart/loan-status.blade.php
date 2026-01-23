<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
      Loan Status
    </h3>
  </div>

  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <canvas id="loanStatusChart"></canvas>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctxStatus = document.getElementById('loanStatusChart').getContext('2d');
  const loanStatusChart = new Chart(ctxStatus, {
    type: 'line',
    data: {
      labels: ['Requested', 'Disbursed', 'Repaid'], // adjust if using monthly
      datasets: [{
        label: 'Loan Status',
        data: [
          {{ $stats['total_requested'] ?? 0 }},
          {{ $stats['total_disbursed'] ?? 0 }},
          {{ $stats['total_repayments'] ?? 0 }}
        ],
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.3,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
</script>
