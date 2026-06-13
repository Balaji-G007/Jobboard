// assets/js/charts.js
// Called from admin/dashboard.php with CHART_DATA object

function initCharts(data) {

  // ── Shared chart defaults ──
  Chart.defaults.color          = '#7b82a6';
  Chart.defaults.borderColor    = '#2a3050';
  Chart.defaults.font.family    = "'Inter', sans-serif";
  Chart.defaults.font.size      = 12;

  const ACCENT  = '#6c63ff';
  const ACCENT2 = '#00d4aa';
  const WARN    = '#f5a623';
  const DANGER  = '#ff5c5c';
  const PURPLE  = '#a78bfa';

  // ── Gradient helper ──
  function gradient(ctx, color1, color2) {
    const g = ctx.createLinearGradient(0, 0, 0, 280);
    g.addColorStop(0, color1);
    g.addColorStop(1, color2);
    return g;
  }

  // ── 1. Jobs Posted Bar Chart ──
  const jobsCtx = document.getElementById('jobsChart');
  if (jobsCtx) {
    const ctx = jobsCtx.getContext('2d');
    new Chart(jobsCtx, {
      type: 'bar',
      data: {
        labels: data.jobsLabels.length ? data.jobsLabels : ['No Data'],
        datasets: [{
          label: 'Jobs Posted',
          data: data.jobsCounts.length ? data.jobsCounts : [0],
          backgroundColor: gradient(ctx, 'rgba(108,99,255,0.8)', 'rgba(108,99,255,0.1)'),
          borderColor: ACCENT,
          borderWidth: 2,
          borderRadius: 8,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
          y: { grid: { color: 'rgba(42,48,80,.6)' }, ticks: { precision: 0 } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // ── 2. Applications Line Chart ──
  const appsCtx = document.getElementById('appsChart');
  if (appsCtx) {
    const ctx2 = appsCtx.getContext('2d');
    new Chart(appsCtx, {
      type: 'line',
      data: {
        labels: data.appsLabels.length ? data.appsLabels : ['No Data'],
        datasets: [{
          label: 'Applications',
          data: data.appsCounts.length ? data.appsCounts : [0],
          backgroundColor: gradient(ctx2, 'rgba(0,212,170,0.25)', 'rgba(0,212,170,0.01)'),
          borderColor: ACCENT2,
          borderWidth: 2.5,
          pointBackgroundColor: ACCENT2,
          pointRadius: 5,
          pointHoverRadius: 7,
          fill: true,
          tension: 0.4,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
          y: { grid: { color: 'rgba(42,48,80,.6)' }, ticks: { precision: 0 } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // ── 3. Application Status Doughnut ──
  const statusCtx = document.getElementById('statusChart');
  if (statusCtx) {
    const statusColors = {
      pending:  WARN,
      reviewed: ACCENT,
      accepted: ACCENT2,
      rejected: DANGER,
    };
    const colors = (data.statusLabels || []).map(l => statusColors[l] || PURPLE);

    new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: data.statusLabels.length ? data.statusLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)) : ['No Data'],
        datasets: [{
          data: data.statusCounts.length ? data.statusCounts : [1],
          backgroundColor: colors.length ? colors : ['#2a3050'],
          borderColor: '#1e2336',
          borderWidth: 3,
          hoverOffset: 8,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 }
          }
        }
      }
    });
  }

  // ── 4. Job Type Pie Chart ──
  const typeCtx = document.getElementById('typeChart');
  if (typeCtx) {
    const typeColors = ['#6c63ff','#00d4aa','#f5a623','#ff5c5c','#a78bfa'];
    new Chart(typeCtx, {
      type: 'pie',
      data: {
        labels: data.typeLabels.length ? data.typeLabels : ['No Data'],
        datasets: [{
          data: data.typeCounts.length ? data.typeCounts : [1],
          backgroundColor: data.typeLabels.length ? typeColors.slice(0, data.typeLabels.length) : ['#2a3050'],
          borderColor: '#1e2336',
          borderWidth: 3,
          hoverOffset: 8,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8 }
          }
        }
      }
    });
  }
}

// ── Auto-refresh stats every 60 seconds ──
function autoRefreshStats() {
  setInterval(() => {
    fetch('../ajax/get_stats.php')
      .then(r => r.json())
      .then(data => {
        // Update stat values if elements exist
        const map = {
          'stat-total-users':     data.total_users,
          'stat-total-jobs':      data.total_jobs,
          'stat-total-apps':      data.total_apps,
          'stat-pending':         data.pending_apps,
          'stat-accepted':        data.accepted_apps,
          'stat-open-jobs':       data.open_jobs,
          'stat-total-seekers':   data.total_seekers,
          'stat-total-employers': data.total_employers,
        };
        Object.entries(map).forEach(([id, val]) => {
          const el = document.getElementById(id);
          if (el) el.textContent = val;
        });
      })
      .catch(() => {}); // silently fail
  }, 60000);
}

document.addEventListener('DOMContentLoaded', autoRefreshStats);