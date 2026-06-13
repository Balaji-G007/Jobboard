<?php
// employer/dashboard.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header('Location: /jobboard/auth/login.php'); exit;
}

$employer_id = $_SESSION['user_id'];

// ── Stats (removed status='open' — column doesn't exist) ──
$stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ?");
$stmt->execute([$employer_id]); $total_jobs = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ?");
$stmt->execute([$employer_id]); $total_apps = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'pending'");
$stmt->execute([$employer_id]); $pending_apps = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'accepted'");
$stmt->execute([$employer_id]); $accepted_apps = $stmt->fetchColumn();

// ── Jobs with applicant count (fixed: removed j.type, j.status) ──
$stmt = $conn->prepare("
    SELECT j.id, j.title, j.location, j.category, j.salary, j.created_at,
           COUNT(a.id) AS applicant_count
    FROM jobs j
    LEFT JOIN applications a ON j.id = a.job_id
    WHERE j.employer_id = ?
    GROUP BY j.id
    ORDER BY j.created_at DESC
    LIMIT 10
");
$stmt->execute([$employer_id]);
$jobs = $stmt->fetchAll();

// ── Recent applicants ──
$stmt = $conn->prepare("
    SELECT a.id, a.status, a.applied_at,
           j.title AS job_title,
           u.name AS seeker_name, u.email AS seeker_email
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.seeker_id = u.id
    WHERE j.employer_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 8
");
$stmt->execute([$employer_id]);
$recent_apps = $stmt->fetchAll();

// ── Current user ──
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$employer_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Employer Dashboard – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body{font-family:'Inter',sans-serif;background:#0f1117;color:#e2e8f0;margin:0}
.em-layout{display:flex;min-height:100vh}
.em-sidebar{width:240px;min-height:100vh;background:#13151f;border-right:1px solid #1e2235;display:flex;flex-direction:column;padding:24px 16px;position:sticky;top:0;height:100vh}
.em-brand{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:#fff;padding:0 8px 28px;border-bottom:1px solid #1e2235;margin-bottom:20px}
.em-brand span{color:#7F77DD}
.em-nav{display:flex;flex-direction:column;gap:4px;flex:1}
.em-nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:#94a3b8;text-decoration:none;font-size:14px;font-weight:500;transition:all .2s}
.em-nav a:hover,.em-nav a.active{background:rgba(127,119,221,0.12);color:#AFA9EC}
.em-nav a.active{border-left:3px solid #7F77DD;padding-left:9px}
.em-sidebar-footer{padding-top:20px;border-top:1px solid #1e2235}
.em-user-info{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.em-avatar{width:36px;height:36px;border-radius:50%;background:#7F77DD;display:grid;place-items:center;font-weight:700;font-size:15px;color:#fff}
.em-user-name{font-size:12px;color:#e2e8f0;font-weight:500}
.em-user-role{font-size:11px;color:#7F77DD;margin-top:2px}
.em-logout{font-size:12px;color:#64748b;text-decoration:none;padding:6px 12px;display:block;border-radius:8px;transition:all .2s}
.em-logout:hover{background:rgba(127,119,221,0.1);color:#AFA9EC}
.em-main{flex:1;padding:32px;overflow-y:auto}
.em-topbar{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:32px;gap:16px;flex-wrap:wrap}
.em-page-title{font-size:26px;font-weight:800;color:#fff;margin:0 0 4px;font-family:'Syne',sans-serif}
.em-page-sub{font-size:13px;color:#64748b;margin:0}
.em-topbar-actions{display:flex;gap:10px;align-items:center}
.em-btn-primary{background:#7F77DD;color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;transition:background .2s}
.em-btn-primary:hover{background:#534AB7}
.em-btn-secondary{border:1px solid #1e2235;color:#94a3b8;padding:9px 18px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:500;transition:all .2s}
.em-btn-secondary:hover{border-color:#7F77DD;color:#AFA9EC}
.em-link{color:#7F77DD;text-decoration:none;font-size:13px}
.em-link:hover{color:#AFA9EC}
.em-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.em-stat-card{background:#13151f;border:1px solid #1e2235;border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:10px;transition:border-color .2s}
.em-stat-card:hover{border-color:rgba(127,119,221,0.4)}
.em-stat-icon{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;font-size:18px}
.em-stat-num{font-size:28px;font-weight:700;color:#fff}
.em-stat-label{font-size:12px;color:#64748b;font-weight:500}
.em-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.em-section{background:#13151f;border:1px solid #1e2235;border-radius:14px;padding:22px}
.em-section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.em-section-title{font-size:16px;font-weight:600;color:#fff;margin:0}
.em-job-list{display:flex;flex-direction:column;gap:12px}
.em-job-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px;background:#0f1117;border:1px solid #1e2235;border-radius:10px;transition:border-color .2s}
.em-job-row:hover{border-color:rgba(127,119,221,0.3)}
.em-job-title{font-weight:600;color:#e2e8f0;font-size:14px;margin-bottom:4px}
.em-job-meta{font-size:11px;color:#64748b;display:flex;gap:4px;flex-wrap:wrap;align-items:center}
.em-applicant-badge{display:flex;align-items:center;gap:8px;flex-shrink:0}
.em-badge-num{font-size:20px;font-weight:700;color:#7F77DD}
.em-badge-label{font-size:11px;color:#64748b}
.em-review-btn{font-size:12px;color:#7F77DD;text-decoration:none;font-weight:600}
.em-review-btn:hover{color:#AFA9EC}
.em-app-list{display:flex;flex-direction:column;gap:10px}
.em-app-row{display:flex;align-items:center;gap:12px;padding:12px;background:#0f1117;border:1px solid #1e2235;border-radius:10px;transition:border-color .2s}
.em-app-row:hover{border-color:rgba(127,119,221,0.3)}
.em-app-avatar{width:34px;height:34px;border-radius:50%;background:#7F77DD;display:grid;place-items:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0}
.em-app-info{flex:1;min-width:0}
.em-app-name{font-size:13px;font-weight:600;color:#e2e8f0}
.em-app-job{font-size:11px;color:#64748b;margin-top:2px}
.em-status{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.em-status-pending{background:#f59e0b18;color:#f59e0b}
.em-status-reviewed{background:rgba(127,119,221,0.12);color:#AFA9EC}
.em-status-accepted{background:#00d4aa18;color:#00d4aa}
.em-status-rejected{background:#ef444418;color:#ef4444}
.em-empty{text-align:center;padding:28px;color:#64748b;font-size:14px}
.em-empty-icon{font-size:32px;margin-bottom:10px}
.em-tag{background:#1e2235;color:#94a3b8;padding:2px 8px;border-radius:6px;font-size:11px}
@media(max-width:1100px){.em-grid-2{grid-template-columns:1fr}}
@media(max-width:900px){.em-sidebar{display:none}.em-stats{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div class="em-layout">

  <!-- SIDEBAR -->
  <aside class="em-sidebar">
    <div class="em-brand">Job<span>Board</span></div>
    <nav class="em-nav">
      <a href="dashboard.php" class="active"><span>🏠</span> Dashboard</a>
      <a href="post_job.php"><span>➕</span> Post a Job</a>
      <a href="view_applicants.php"><span>👥</span> View Applicants</a>
      <a href="../jobs.php"><span>🌐</span> View Site</a>
    </nav>
    <div class="em-sidebar-footer">
      <div class="em-user-info">
        <div class="em-avatar"><?= strtoupper(substr($user['name'] ?? 'E', 0, 1)) ?></div>
        <div>
          <div class="em-user-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
          <div class="em-user-role">Employer</div>
        </div>
      </div>
      <a href="/jobboard/auth/logout.php" class="em-logout">⬅ Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="em-main">
    <header class="em-topbar">
      <div>
        <h1 class="em-page-title">Welcome, <?= htmlspecialchars(explode(' ', $user['name'] ?? 'there')[0]) ?> 🏢</h1>
        <p class="em-page-sub">Manage your job postings and applicants</p>
      </div>
      <div class="em-topbar-actions">
        <a href="view_applicants.php" class="em-btn-secondary">View Applicants</a>
        <a href="post_job.php" class="em-btn-primary">+ Post a Job</a>
      </div>
    </header>

    <!-- Stats -->
    <section class="em-stats">
      <div class="em-stat-card">
        <div class="em-stat-icon" style="background:rgba(127,119,221,0.12)">📝</div>
        <div class="em-stat-num"><?= $total_jobs ?></div>
        <div class="em-stat-label">Total Jobs Posted</div>
      </div>
      <div class="em-stat-card">
        <div class="em-stat-icon" style="background:#00d4aa18">👥</div>
        <div class="em-stat-num"><?= $total_apps ?></div>
        <div class="em-stat-label">Total Applicants</div>
      </div>
      <div class="em-stat-card">
        <div class="em-stat-icon" style="background:#f59e0b18">⏳</div>
        <div class="em-stat-num"><?= $pending_apps ?></div>
        <div class="em-stat-label">Pending Reviews</div>
      </div>
      <div class="em-stat-card">
        <div class="em-stat-icon" style="background:#00d4aa18">✅</div>
        <div class="em-stat-num"><?= $accepted_apps ?></div>
        <div class="em-stat-label">Accepted</div>
      </div>
    </section>

    <div class="em-grid-2">
      <!-- My Jobs -->
      <section class="em-section">
        <div class="em-section-header">
          <h2 class="em-section-title">My Job Posts</h2>
          <a href="post_job.php" class="em-link">+ New Job</a>
        </div>
        <?php if (empty($jobs)): ?>
          <div class="em-empty">
            <div class="em-empty-icon">📭</div>
            <p>No jobs posted yet. <a href="post_job.php" class="em-link">Post your first job</a>.</p>
          </div>
        <?php else: ?>
          <div class="em-job-list">
            <?php foreach ($jobs as $job): ?>
            <div class="em-job-row">
              <div>
                <div class="em-job-title"><?= htmlspecialchars($job['title']) ?></div>
                <div class="em-job-meta">
                  <span>📍 <?= htmlspecialchars($job['location']) ?></span>
                  <span>·</span>
                  <span class="em-tag"><?= htmlspecialchars($job['category']) ?></span>
                  <span>·</span>
                  <span style="color:#00d4aa"><?= htmlspecialchars($job['salary']) ?></span>
                </div>
              </div>
              <div class="em-applicant-badge">
                <div>
                  <div class="em-badge-num"><?= $job['applicant_count'] ?></div>
                  <div class="em-badge-label">applicants</div>
                </div>
                <a href="view_applicants.php?job_id=<?= $job['id'] ?>" class="em-review-btn">Review →</a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <!-- Recent Applicants -->
      <section class="em-section">
        <div class="em-section-header">
          <h2 class="em-section-title">Recent Applicants</h2>
          <a href="view_applicants.php" class="em-link">View all →</a>
        </div>
        <?php if (empty($recent_apps)): ?>
          <div class="em-empty">
            <div class="em-empty-icon">🙋</div>
            <p>No applicants yet.</p>
          </div>
        <?php else: ?>
          <div class="em-app-list">
            <?php foreach ($recent_apps as $app): ?>
            <div class="em-app-row">
              <div class="em-app-avatar"><?= strtoupper(substr($app['seeker_name'] ?? 'U', 0, 1)) ?></div>
              <div class="em-app-info">
                <div class="em-app-name"><?= htmlspecialchars($app['seeker_name']) ?></div>
                <div class="em-app-job">Applied for: <strong><?= htmlspecialchars($app['job_title']) ?></strong></div>
                <div style="font-size:11px;color:#475569;margin-top:2px"><?= date('d M Y', strtotime($app['applied_at'])) ?></div>
              </div>
              <div>
                <span class="em-status em-status-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </main>
</div>
</body>
</html>
