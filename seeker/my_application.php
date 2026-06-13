<?php
// seeker/my_application.php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';
checkRole('seeker');

$uid = $_SESSION['user_id'];
$name= $_SESSION['name'];

$apps = $conn->query("
    SELECT a.*, j.title, j.company, j.location, j.type, j.salary
    FROM applications a JOIN jobs j ON a.job_id=j.id
    WHERE a.seeker_id=$uid ORDER BY a.applied_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>My Applications – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo">Job<span>Board</span></div>
    <nav>
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="../jobs.php"><span class="icon">🔍</span> Browse Jobs</a>
      <a href="my_application.php" class="active"><span class="icon">📋</span> My Applications</a>
      <a href="profile.php"><span class="icon">👤</span> My Profile</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../auth/logout.php" class="logout-btn">⬅ Logout</a>
    </div>
  </aside>

  <main class="dash-main">
    <div class="topbar">
      <h1>My <span>Applications</span></h1>
      <a href="../jobs.php" class="btn-primary btn-sm">+ Apply More Jobs</a>
    </div>

    <?php if (empty($apps)): ?>
      <div class="section-card">
        <div class="empty-state">
          <div style="font-size:3rem;margin-bottom:16px">📋</div>
          You haven't applied to any jobs yet.<br><br>
          <a href="../jobs.php" class="btn-primary">Browse Jobs →</a>
        </div>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Job Title</th>
              <th>Company</th>
              <th>Location</th>
              <th>Type</th>
              <th>Applied On</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($apps as $i => $a): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
              <td><?= htmlspecialchars($a['company']) ?></td>
              <td><?= htmlspecialchars($a['location']) ?></td>
              <td><span class="tag tag-type"><?= htmlspecialchars($a['type']) ?></span></td>
              <td><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
              <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
              <td><a href="../job_detail.php?id=<?= $a['job_id'] ?>" class="btn-outline btn-sm">View Job</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Status legend -->
      <div style="display:flex;gap:16px;margin-top:20px;flex-wrap:wrap">
        <span class="badge badge-pending">Pending — Under review</span>
        <span class="badge badge-reviewed">Reviewed — Employer saw it</span>
        <span class="badge badge-accepted">Accepted — Congratulations!</span>
        <span class="badge badge-rejected">Rejected — Keep applying!</span>
      </div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
