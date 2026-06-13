<?php
// employer/view_applicants.php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';
checkRole('employer');

$uid    = $_SESSION['user_id'];
$job_id = (int)($_GET['job_id'] ?? 0);

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['status'])) {
    $allowed = ['pending','reviewed','accepted','rejected'];
    if (in_array($_POST['status'], $allowed)) {
        $stmt = $conn->prepare("UPDATE applications SET status=? WHERE id=? AND job_id IN (SELECT id FROM jobs WHERE employer_id=?)");
        $stmt->execute([$_POST['status'], (int)$_POST['app_id'], $uid]);
    }
    header('Location: view_applicants.php' . ($job_id ? "?job_id=$job_id" : ''));
    exit;
}

// Get employer's jobs for filter dropdown
$my_jobs = $conn->query("SELECT id, title FROM jobs WHERE employer_id=$uid ORDER BY created_at DESC")->fetchAll();

// Build applicants query
$where  = ["j.employer_id=$uid"];
$params = [];
if ($job_id) { $where[] = "j.id=?"; $params[] = $job_id; }

$applicants = $conn->prepare("
    SELECT a.*, u.name as seeker_name, u.email as seeker_email,
           j.title as job_title, j.id as job_id
    FROM applications a
    JOIN jobs j ON a.job_id=j.id
    JOIN users u ON a.seeker_id=u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.applied_at DESC
");
$applicants->execute($params);
$applicants = $applicants->fetchAll();

// Counts
$counts = ['pending'=>0,'reviewed'=>0,'accepted'=>0,'rejected'=>0];
foreach ($applicants as $a) $counts[$a['status']]++;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>View Applicants – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.filter-row { display:flex; gap:12px; align-items:center; margin-bottom:24px; flex-wrap:wrap; }
.filter-row select { padding:10px 16px; background:var(--card); border:1px solid var(--border); border-radius:9px; color:var(--text); font-size:.88rem; font-family:'Inter',sans-serif; }
.filter-row select:focus { outline:none; border-color:var(--accent); }
.mini-stats { display:flex; gap:12px; margin-bottom:24px; flex-wrap:wrap; }
.mini-stat { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:12px 18px; text-align:center; min-width:90px; }
.mini-stat .num { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; }
.mini-stat .lbl { font-size:.72rem; color:var(--muted); text-transform:uppercase; }
.app-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:20px 24px; margin-bottom:14px; display:flex; gap:20px; align-items:flex-start; }
.app-card .avatar { width:44px; height:44px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff; font-size:.95rem; flex-shrink:0; }
.app-card-info { flex:1; }
.app-card-info h3 { font-size:.95rem; font-weight:700; margin-bottom:3px; }
.app-card-info .meta { font-size:.8rem; color:var(--muted); margin-bottom:10px; }
.app-card-info .cover { font-size:.85rem; color:var(--text); background:var(--surface); border-radius:8px; padding:10px 14px; margin-bottom:12px; border-left:3px solid var(--accent); }
.status-form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.status-form select { padding:7px 12px; background:var(--surface); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:.82rem; font-family:'Inter',sans-serif; }
</style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo">Job<span>Board</span></div>
    <nav>
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="post_job.php"><span class="icon">➕</span> Post a Job</a>
      <a href="view_applicants.php" class="active"><span class="icon">👥</span> Applicants</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../auth/logout.php" class="logout-btn">⬅ Logout</a>
    </div>
  </aside>

  <main class="dash-main">
    <div class="topbar">
      <h1>View <span>Applicants</span></h1>
      <a href="post_job.php" class="btn-primary btn-sm">+ Post Job</a>
    </div>

    <!-- Filter -->
    <div class="filter-row">
      <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <select name="job_id" onchange="this.form.submit()">
          <option value="">All Jobs</option>
          <?php foreach ($my_jobs as $j): ?>
            <option value="<?= $j['id'] ?>" <?= $job_id==$j['id']?'selected':'' ?>><?= htmlspecialchars($j['title']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($job_id): ?><a href="view_applicants.php" class="btn-outline btn-sm">Clear Filter</a><?php endif; ?>
      </form>
    </div>

    <!-- Mini stats -->
    <div class="mini-stats">
      <div class="mini-stat"><div class="num"><?= count($applicants) ?></div><div class="lbl">Total</div></div>
      <div class="mini-stat"><div class="num" style="color:var(--warn)"><?= $counts['pending'] ?></div><div class="lbl">Pending</div></div>
      <div class="mini-stat"><div class="num" style="color:var(--accent)"><?= $counts['reviewed'] ?></div><div class="lbl">Reviewed</div></div>
      <div class="mini-stat"><div class="num" style="color:var(--accent2)"><?= $counts['accepted'] ?></div><div class="lbl">Accepted</div></div>
      <div class="mini-stat"><div class="num" style="color:var(--danger)"><?= $counts['rejected'] ?></div><div class="lbl">Rejected</div></div>
    </div>

    <!-- Applicant Cards -->
    <?php if (empty($applicants)): ?>
      <div class="section-card">
        <div class="empty-state">
          <div style="font-size:3rem;margin-bottom:16px">👥</div>
          No applicants yet. Post a job to start receiving applications!
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($applicants as $a): ?>
        <div class="app-card">
          <div class="avatar"><?= strtoupper(substr($a['seeker_name'],0,1)) ?></div>
          <div class="app-card-info">
            <h3><?= htmlspecialchars($a['seeker_name']) ?></h3>
            <div class="meta">
              📧 <?= htmlspecialchars($a['seeker_email']) ?>
              &nbsp;·&nbsp; Applied for: <strong><?= htmlspecialchars($a['job_title']) ?></strong>
              &nbsp;·&nbsp; <?= date('d M Y', strtotime($a['applied_at'])) ?>
            </div>

            <?php if ($a['cover_letter']): ?>
              <div class="cover">"<?= htmlspecialchars(substr($a['cover_letter'],0,200)) ?><?= strlen($a['cover_letter'])>200?'...':'' ?>"</div>
            <?php endif; ?>

            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
              <?php if ($a['resume_submitted'] && $a['resume_submitted'] !== 'no_resume'): ?>
                <a href="../<?= htmlspecialchars($a['resume_submitted']) ?>" target="_blank" class="btn-outline btn-sm">📄 View Resume</a>
              <?php endif; ?>

              <!-- Status update form -->
              <form method="POST" class="status-form">
                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                <?php if ($job_id): ?><input type="hidden" name="job_id_filter" value="<?= $job_id ?>"><?php endif; ?>
                <select name="status">
                  <?php foreach (['pending','reviewed','accepted','rejected'] as $s): ?>
                    <option value="<?= $s ?>" <?= $a['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-primary btn-sm">Update</button>
              </form>

              <span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
