<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
      Loan Funnel
    </h3>
  </div>

  <div class="max-w-full overflow-x-auto custom-scrollbar">
    <canvas id="loanFunnelChart"></canvas>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('loanFunnelChart').getContext('2d');
  const loanFunnelChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Requested', 'Disbursed', 'Repaid'],
      datasets: [{
        label: 'Loan Funnel (KES)',
        data: [
          {{ $stats['total_requested'] ?? 0 }},
          {{ $stats['total_disbursed'] ?? 0 }},
          {{ $stats['total_repayments'] ?? 0 }}
        ],
        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B'],
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
