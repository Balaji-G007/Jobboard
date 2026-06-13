<?php
// seeker/dashboard.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seeker') {
    header('Location: /jobboard/auth/login.php'); exit;
}

$seeker_id = $_SESSION['user_id'];

// ── Stats ──
$total_apps = $conn->prepare("SELECT COUNT(*) FROM applications WHERE seeker_id = ?");
$total_apps->execute([$seeker_id]);
$total_apps = $total_apps->fetchColumn();

$pending = $conn->prepare("SELECT COUNT(*) FROM applications WHERE seeker_id = ? AND status = 'pending'");
$pending->execute([$seeker_id]); $pending = $pending->fetchColumn();

$accepted = $conn->prepare("SELECT COUNT(*) FROM applications WHERE seeker_id = ? AND status = 'accepted'");
$accepted->execute([$seeker_id]); $accepted = $accepted->fetchColumn();

$rejected = $conn->prepare("SELECT COUNT(*) FROM applications WHERE seeker_id = ? AND status = 'rejected'");
$rejected->execute([$seeker_id]); $rejected = $rejected->fetchColumn();

// ── Recent applications (fixed: removed j.type, using j.category) ──
$stmt = $conn->prepare("
    SELECT a.id, a.status, a.applied_at, j.title, j.location, j.category, j.salary
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE a.seeker_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 10
");
$stmt->execute([$seeker_id]);
$apps = $stmt->fetchAll();

// ── Profile ──
$pstmt = $conn->prepare("SELECT * FROM profiles WHERE user_id = ?");
$pstmt->execute([$seeker_id]);
$profile = $pstmt->fetch();

$ustmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$ustmt->execute([$seeker_id]);
$user = $ustmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Dashboard – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body{font-family:'Inter',sans-serif;background:#0f1117;color:#e2e8f0;margin:0}
.sk-layout{display:flex;min-height:100vh}
.sk-sidebar{width:240px;min-height:100vh;background:#13151f;border-right:1px solid #1e2235;display:flex;flex-direction:column;padding:24px 16px;position:sticky;top:0;height:100vh}
.sk-brand{display:flex;align-items:center;gap:10px;padding:0 8px 28px;border-bottom:1px solid #1e2235;margin-bottom:20px;font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:#fff}
.sk-brand span{color:#7F77DD}
.sk-nav{display:flex;flex-direction:column;gap:4px;flex:1}
.sk-nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:#94a3b8;text-decoration:none;font-size:14px;font-weight:500;transition:all .2s}
.sk-nav a:hover,.sk-nav a.active{background:rgba(127,119,221,0.12);color:#AFA9EC}
.sk-nav a.active{border-left:3px solid #7F77DD;padding-left:9px}
.sk-sidebar-footer{padding-top:20px;border-top:1px solid #1e2235}
.sk-user-info{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.sk-avatar{width:36px;height:36px;border-radius:50%;background:#7F77DD;display:grid;place-items:center;font-weight:700;font-size:15px;color:#fff}
.sk-user-name{font-size:12px;color:#e2e8f0;font-weight:500}
.sk-user-role{font-size:11px;color:#7F77DD;margin-top:2px}
.sk-logout{font-size:12px;color:#64748b;text-decoration:none;padding:6px 12px;display:block;border-radius:8px;transition:all .2s}
.sk-logout:hover{background:rgba(127,119,221,0.1);color:#AFA9EC}
.sk-main{flex:1;padding:32px;overflow-y:auto}
.sk-topbar{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:1rem}
.sk-page-title{font-size:26px;font-weight:800;color:#fff;margin:0 0 4px;font-family:'Syne',sans-serif}
.sk-page-sub{font-size:13px;color:#64748b;margin:0}
.sk-btn-primary{background:#7F77DD;color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;transition:background .2s}
.sk-btn-primary:hover{background:#534AB7}
.sk-link{color:#7F77DD;text-decoration:none;font-size:13px}
.sk-link:hover{color:#AFA9EC}
.sk-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.sk-stat-card{background:#13151f;border:1px solid #1e2235;border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:10px;transition:border-color .2s}
.sk-stat-card:hover{border-color:rgba(127,119,221,0.4)}
.sk-stat-icon{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;font-size:18px}
.sk-stat-num{font-size:28px;font-weight:700;color:#fff}
.sk-stat-label{font-size:12px;color:#64748b;font-weight:500}
.sk-section{background:#13151f;border:1px solid #1e2235;border-radius:14px;padding:22px;margin-bottom:20px}
.sk-section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.sk-section-title{font-size:16px;font-weight:600;color:#fff;margin:0}
.sk-table-wrap{overflow-x:auto}
.sk-table{width:100%;border-collapse:collapse;font-size:13px}
.sk-table th{text-align:left;padding:10px 14px;color:#64748b;font-weight:500;border-bottom:1px solid #1e2235}
.sk-table td{padding:12px 14px;border-bottom:1px solid #1a1d2b;vertical-align:middle}
.sk-table tr:last-child td{border-bottom:none}
.sk-table tr:hover td{background:#1a1d2b55}
.sk-job-title{font-weight:600;color:#e2e8f0}
.sk-tag{background:rgba(127,119,221,0.12);color:#AFA9EC;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:500}
.sk-status{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600}
.sk-status-pending{background:#f59e0b18;color:#f59e0b}
.sk-status-reviewed{background:rgba(127,119,221,0.12);color:#AFA9EC}
.sk-status-accepted{background:#00d4aa18;color:#00d4aa}
.sk-status-rejected{background:#ef444418;color:#ef4444}
.sk-empty{text-align:center;padding:36px;color:#64748b;font-size:14px}
.sk-empty-icon{font-size:36px;margin-bottom:12px}
.sk-profile-card{display:flex;flex-direction:column;gap:14px}
.sk-profile-row{display:flex;gap:16px;align-items:flex-start;font-size:14px}
.sk-profile-label{min-width:80px;color:#64748b;font-weight:500}
@media(max-width:900px){.sk-sidebar{display:none}.sk-stats{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div class="sk-layout">

  <!-- SIDEBAR -->
  <aside class="sk-sidebar">
    <div class="sk-brand">Job<span>Board</span></div>
    <nav class="sk-nav">
      <a href="dashboard.php" class="active"><span>🏠</span> Dashboard</a>
      <a href="my_application.php"><span>📋</span> My Applications</a>
      <a href="../jobs.php"><span>🔍</span> Browse Jobs</a>
      <a href="profile.php"><span>👤</span> Profile</a>
    </nav>
    <div class="sk-sidebar-footer">
      <div class="sk-user-info">
        <div class="sk-avatar"><?= strtoupper(substr($user['name'] ?? 'S', 0, 1)) ?></div>
        <div>
          <div class="sk-user-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
          <div class="sk-user-role">Job Seeker</div>
        </div>
      </div>
      <a href="/jobboard/auth/logout.php" class="sk-logout">⬅ Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="sk-main">
    <header class="sk-topbar">
      <div>
        <h1 class="sk-page-title">Welcome back, <?= htmlspecialchars(explode(' ', $user['name'] ?? 'there')[0]) ?> 👋</h1>
        <p class="sk-page-sub">Track your job applications</p>
      </div>
      <a href="/jobboard/jobs.php" class="sk-btn-primary">Browse Jobs</a>
    </header>

    <!-- Stats -->
    <section class="sk-stats">
      <div class="sk-stat-card">
        <div class="sk-stat-icon" style="background:rgba(127,119,221,0.12);">📤</div>
        <div class="sk-stat-num"><?= $total_apps ?></div>
        <div class="sk-stat-label">Total Applied</div>
      </div>
      <div class="sk-stat-card">
        <div class="sk-stat-icon" style="background:#f59e0b18;">⏳</div>
        <div class="sk-stat-num"><?= $pending ?></div>
        <div class="sk-stat-label">Pending Review</div>
      </div>
      <div class="sk-stat-card">
        <div class="sk-stat-icon" style="background:#00d4aa18;">✅</div>
        <div class="sk-stat-num"><?= $accepted ?></div>
        <div class="sk-stat-label">Accepted</div>
      </div>
      <div class="sk-stat-card">
        <div class="sk-stat-icon" style="background:#ef444418;">❌</div>
        <div class="sk-stat-num"><?= $rejected ?></div>
        <div class="sk-stat-label">Rejected</div>
      </div>
    </section>

    <!-- Recent Applications -->
    <section class="sk-section">
      <div class="sk-section-header">
        <h2 class="sk-section-title">Recent Applications</h2>
        <a href="my_application.php" class="sk-link">View all →</a>
      </div>
      <?php if (empty($apps)): ?>
        <div class="sk-empty">
          <div class="sk-empty-icon">🔍</div>
          <p>No applications yet. <a href="/jobboard/jobs.php" class="sk-link">Browse jobs</a> to get started.</p>
        </div>
      <?php else: ?>
        <div class="sk-table-wrap">
          <table class="sk-table">
            <thead>
              <tr><th>Job Title</th><th>Category</th><th>Location</th><th>Salary</th><th>Applied On</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach ($apps as $app): ?>
              <tr>
                <td class="sk-job-title"><?= htmlspecialchars($app['title']) ?></td>
                <td><span class="sk-tag"><?= htmlspecialchars($app['category']) ?></span></td>
                <td style="color:#64748b"><?= htmlspecialchars($app['location']) ?></td>
                <td style="color:#00d4aa;font-size:.82rem"><?= htmlspecialchars($app['salary']) ?></td>
                <td style="color:#64748b;font-size:.82rem"><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
                <td><span class="sk-status sk-status-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <!-- Profile Preview -->
    <section class="sk-section">
      <div class="sk-section-header">
        <h2 class="sk-section-title">Your Profile</h2>
        <a href="profile.php" class="sk-link">Edit →</a>
      </div>
      <?php if ($profile): ?>
        <div class="sk-profile-card">
          <div class="sk-profile-row">
            <span class="sk-profile-label">Skills</span>
            <span><?= htmlspecialchars($profile['skills'] ?? '—') ?></span>
          </div>
          <div class="sk-profile-row">
            <span class="sk-profile-label">Resume</span>
            <span>
              <?= $profile['resume_path']
                ? '<a href="/jobboard/assets/uploads/resumes/'.htmlspecialchars($profile['resume_path']).'" class="sk-link" target="_blank">View Resume</a>'
                : 'Not uploaded' ?>
            </span>
          </div>
        </div>
      <?php else: ?>
        <div class="sk-empty">
          <p>Profile not set up yet. <a href="profile.php" class="sk-link">Complete your profile</a> to stand out.</p>
        </div>
      <?php endif; ?>
    </section>

  </main>
</div>
</body>
</html>