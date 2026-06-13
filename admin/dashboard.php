<?php
// admin/dashboard.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /jobboard/auth/login.php'); exit;
}

$name = $_SESSION['name'] ?? 'Admin';

// ── Stats ──
$total_users     = $conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$total_seekers   = $conn->query("SELECT COUNT(*) FROM users WHERE role='seeker'")->fetchColumn();
$total_employers = $conn->query("SELECT COUNT(*) FROM users WHERE role='employer'")->fetchColumn();
$total_jobs      = $conn->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$total_apps      = $conn->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$pending_apps    = $conn->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$accepted_apps   = $conn->query("SELECT COUNT(*) FROM applications WHERE status='accepted'")->fetchColumn();

// ── Charts data ──
$jobs_monthly = $conn->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') as month, COUNT(*) as count
    FROM jobs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY created_at ASC
")->fetchAll();

$apps_monthly = $conn->query("
    SELECT DATE_FORMAT(applied_at,'%b %Y') as month, COUNT(*) as count
    FROM applications WHERE applied_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(applied_at), MONTH(applied_at) ORDER BY applied_at ASC
")->fetchAll();

$status_data = $conn->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status")->fetchAll();
$cat_data    = $conn->query("SELECT category, COUNT(*) as count FROM jobs GROUP BY category")->fetchAll();

// ── Recent users ──
$recent_users = $conn->query("
    SELECT id, name, email, role, is_verified, created_at
    FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 8
")->fetchAll();

// ── Recent jobs (fixed: no company/type/status columns) ──
$recent_jobs = $conn->query("
    SELECT j.id, j.title, j.category, j.location, j.salary, j.created_at, u.name as employer_name
    FROM jobs j JOIN users u ON j.employer_id = u.id
    ORDER BY j.created_at DESC LIMIT 6
")->fetchAll();

$jobs_labels   = json_encode(array_column($jobs_monthly, 'month'));
$jobs_counts   = json_encode(array_column($jobs_monthly, 'count'));
$apps_labels   = json_encode(array_column($apps_monthly, 'month'));
$apps_counts   = json_encode(array_column($apps_monthly, 'count'));
$status_labels = json_encode(array_column($status_data,  'status'));
$status_counts = json_encode(array_column($status_data,  'count'));
$cat_labels    = json_encode(array_column($cat_data,     'category'));
$cat_counts    = json_encode(array_column($cat_data,     'count'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Admin Dashboard – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{font-family:'Inter',sans-serif;background:#0f1117;color:#e2e8f0;margin:0}
.layout{display:flex;min-height:100vh}
.sidebar{width:240px;background:#13151f;border-right:1px solid #1e2235;display:flex;flex-direction:column;padding:24px 16px;position:sticky;top:0;height:100vh}
.sidebar-logo{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:#fff;padding:0 8px 24px;border-bottom:1px solid #1e2235;margin-bottom:20px}
.sidebar-logo span{color:#7F77DD}
.sidebar nav{display:flex;flex-direction:column;gap:4px;flex:1}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:#94a3b8;text-decoration:none;font-size:14px;font-weight:500;transition:all .2s}
.sidebar nav a:hover,.sidebar nav a.active{background:rgba(127,119,221,0.12);color:#AFA9EC}
.sidebar nav a.active{border-left:3px solid #7F77DD;padding-left:9px}
.sidebar-bottom{padding-top:20px;border-top:1px solid #1e2235}
.logout-btn{display:block;font-size:12px;color:#64748b;text-decoration:none;padding:8px 12px;border-radius:8px;transition:all .2s}
.logout-btn:hover{background:rgba(127,119,221,0.12);color:#AFA9EC}
.dash-main{flex:1;padding:32px;overflow-y:auto}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px}
.topbar h1{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;margin:0;color:#fff}
.topbar h1 span{color:#7F77DD}
.user-badge{display:flex;align-items:center;gap:10px;font-size:14px;color:#94a3b8}
.avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;font-weight:700;font-size:14px;color:#fff;background:#7F77DD}
.stats-grid{display:grid;gap:16px}
.stat-card{background:#13151f;border:1px solid #1e2235;border-radius:14px;padding:20px;transition:border-color .2s}
.stat-card:hover{border-color:rgba(127,119,221,0.4)}
.stat-card.c1{border-top:3px solid #7F77DD}
.stat-card.c2{border-top:3px solid #00d4aa}
.stat-card.c3{border-top:3px solid #f59e0b}
.stat-card.c4{border-top:3px solid #AFA9EC}
.stat-label{font-size:12px;color:#64748b;font-weight:500;margin-bottom:8px}
.stat-value{font-size:28px;font-weight:700;color:#fff;margin-bottom:4px}
.stat-sub{font-size:11px;color:#475569}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.chart-card{background:#13151f;border:1px solid #1e2235;border-radius:14px;padding:22px}
.chart-head h2{font-size:14px;font-weight:600;color:#fff;margin:0 0 16px}
.chart-body{position:relative;height:200px}
.section-card{background:#13151f;border:1px solid #1e2235;border-radius:14px;overflow:hidden}
.section-head{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #1e2235}
.section-head h2{font-size:14px;font-weight:600;color:#fff;margin:0}
.section-head a{font-size:12px;color:#7F77DD;text-decoration:none}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}
th{text-align:left;padding:10px 16px;color:#64748b;font-weight:500;border-bottom:1px solid #1e2235}
td{padding:12px 16px;border-bottom:1px solid #1a1d2b;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#1a1d2b55}
.badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.badge-seeker{background:rgba(0,212,170,0.12);color:#00d4aa}
.badge-employer{background:rgba(127,119,221,0.12);color:#AFA9EC}
.tag-cat{background:#1e2235;color:#94a3b8;padding:2px 8px;border-radius:6px;font-size:11px}
</style>
</head>
<body>
<div class="layout">

  <aside class="sidebar">
    <div class="sidebar-logo">Job<span>Board</span> <small style="font-size:.5rem;color:#7F77DD;letter-spacing:1px">ADMIN</small></div>
    <nav>
      <a href="dashboard.php" class="active"><span>📊</span> Dashboard</a>
      <a href="manage_jobs.php"><span>💼</span> Manage Jobs</a>
      <a href="manage_users.php"><span>👥</span> Manage Users</a>
      <a href="../index.php" target="_blank"><span>🌐</span> View Site</a>
    </nav>
    <div class="sidebar-bottom">
      <div style="padding:0 0 12px;font-size:.78rem;color:#64748b">
        Logged in as<br><strong style="color:#fff"><?= htmlspecialchars($name) ?></strong>
      </div>
      <a href="/jobboard/auth/logout.php" class="logout-btn">⬅ Logout</a>
    </div>
  </aside>

  <main class="dash-main">
    <div class="topbar">
      <h1>Admin <span>Dashboard</span> 📊</h1>
      <div class="user-badge">
        <div class="avatar">A</div>
        <span>Administrator</span>
      </div>
    </div>

    <!-- Stats Row 1 -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
      <div class="stat-card c1"><div class="stat-label">Total Users</div><div class="stat-value"><?= $total_users ?></div><div class="stat-sub">Seekers + Employers</div></div>
      <div class="stat-card c2"><div class="stat-label">Job Seekers</div><div class="stat-value"><?= $total_seekers ?></div><div class="stat-sub">Registered</div></div>
      <div class="stat-card c3"><div class="stat-label">Employers</div><div class="stat-value"><?= $total_employers ?></div><div class="stat-sub">Registered</div></div>
      <div class="stat-card c4"><div class="stat-label">Total Jobs</div><div class="stat-value"><?= $total_jobs ?></div><div class="stat-sub">All listings</div></div>
    </div>

    <!-- Stats Row 2 -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-top:16px">
      <div class="stat-card c1"><div class="stat-label">Total Applications</div><div class="stat-value"><?= $total_apps ?></div><div class="stat-sub">All time</div></div>
      <div class="stat-card c3"><div class="stat-label">Pending Review</div><div class="stat-value"><?= $pending_apps ?></div><div class="stat-sub">Awaiting employer</div></div>
      <div class="stat-card c2"><div class="stat-label">Accepted</div><div class="stat-value"><?= $accepted_apps ?></div><div class="stat-sub">Successful hires</div></div>
    </div>

    <!-- Charts -->
    <div class="grid-2" style="margin-top:28px">
      <div class="chart-card">
        <div class="chart-head"><h2>Jobs Posted (Last 6 Months)</h2></div>
        <div class="chart-body"><canvas id="jobsChart"></canvas></div>
      </div>
      <div class="chart-card">
        <div class="chart-head"><h2>Applications (Last 6 Months)</h2></div>
        <div class="chart-body"><canvas id="appsChart"></canvas></div>
      </div>
    </div>

    <div class="grid-2" style="margin-top:24px">
      <div class="chart-card">
        <div class="chart-head"><h2>Application Status</h2></div>
        <div class="chart-body" style="display:flex;align-items:center;justify-content:center">
          <canvas id="statusChart"></canvas>
        </div>
      </div>
      <div class="chart-card">
        <div class="chart-head"><h2>Jobs by Category</h2></div>
        <div class="chart-body" style="display:flex;align-items:center;justify-content:center">
          <canvas id="catChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid-2" style="margin-top:24px">
      <div class="section-card">
        <div class="section-head">
          <h2>Recent Users</h2>
          <a href="manage_users.php">Manage all →</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Name</th><th>Role</th><th>Verified</th><th>Joined</th></tr></thead>
            <tbody>
              <?php foreach ($recent_users as $u): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($u['name']) ?></strong><br>
                  <small style="color:#64748b"><?= htmlspecialchars($u['email']) ?></small>
                </td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                <td><?= $u['is_verified'] ? '✅' : '❌' ?></td>
                <td style="font-size:.78rem;color:#64748b"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="section-card">
        <div class="section-head">
          <h2>Recent Jobs</h2>
          <a href="manage_jobs.php">Manage all →</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Title</th><th>Category</th><th>Location</th><th>Posted</th></tr></thead>
            <tbody>
              <?php foreach ($recent_jobs as $j): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($j['title']) ?></strong><br>
                  <small style="color:#64748b"><?= htmlspecialchars($j['employer_name']) ?></small>
                </td>
                <td><span class="tag-cat"><?= htmlspecialchars($j['category']) ?></span></td>
                <td style="color:#64748b;font-size:.82rem"><?= htmlspecialchars($j['location']) ?></td>
                <td style="font-size:.78rem;color:#64748b"><?= date('d M Y', strtotime($j['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
const c = Chart.defaults;
c.color = '#94a3b8';
c.borderColor = '#1e2235';

const jobsChart = new Chart(document.getElementById('jobsChart'), {
  type: 'bar',
  data: {
    labels: <?= $jobs_labels ?>,
    datasets: [{ label: 'Jobs', data: <?= $jobs_counts ?>, backgroundColor: 'rgba(127,119,221,0.7)', borderRadius: 6 }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#1e2235' } }, y: { grid: { color: '#1e2235' }, ticks: { stepSize: 1 } } } }
});

const appsChart = new Chart(document.getElementById('appsChart'), {
  type: 'line',
  data: {
    labels: <?= $apps_labels ?>,
    datasets: [{ label: 'Applications', data: <?= $apps_counts ?>, borderColor: '#00d4aa', backgroundColor: 'rgba(0,212,170,0.1)', tension: 0.4, fill: true }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#1e2235' } }, y: { grid: { color: '#1e2235' }, ticks: { stepSize: 1 } } } }
});

const statusChart = new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: <?= $status_labels ?>,
    datasets: [{ data: <?= $status_counts ?>, backgroundColor: ['#7F77DD','#00d4aa','#f59e0b','#ef4444'], borderWidth: 0 }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 } } } } }
});

const catChart = new Chart(document.getElementById('catChart'), {
  type: 'doughnut',
  data: {
    labels: <?= $cat_labels ?>,
    datasets: [{ data: <?= $cat_counts ?>, backgroundColor: ['#7F77DD','#00d4aa','#f59e0b','#ef4444','#06b6d4','#8b5cf6','#ec4899','#14b8a6'], borderWidth: 0 }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 10, font: { size: 10 } } } } }
});
</script>
</body>
</html>