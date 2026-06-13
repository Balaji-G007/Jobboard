<?php
// admin/manage_jobs.php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit;
}

$msg = '';

// Delete job
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->prepare("DELETE FROM jobs WHERE id=?")->execute([$id]);
    $msg = 'Job deleted successfully.';
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $job = $conn->query("SELECT status FROM jobs WHERE id=$id")->fetch();
    $new = $job['status'] === 'open' ? 'closed' : 'open';
    $conn->prepare("UPDATE jobs SET status=? WHERE id=?")->execute([$new, $id]);
    $msg = "Job marked as $new.";
}

// Search/filter
$q      = trim($_GET['q'] ?? '');
$type   = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';

$where  = ['1=1'];
$params = [];
if ($q)      { $where[] = "(j.title LIKE ? OR j.company LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($type)   { $where[] = "j.type=?";   $params[] = $type; }
if ($status) { $where[] = "j.status=?"; $params[] = $status; }

$stmt = $conn->prepare("
    SELECT j.*, u.name as employer_name,
           (SELECT COUNT(*) FROM applications WHERE job_id=j.id) as app_count
    FROM jobs j JOIN users u ON j.employer_id=u.id
    WHERE " . implode(' AND ',$where) . "
    ORDER BY j.created_at DESC
");
$stmt->execute($params);
$jobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Manage Jobs – Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo">Job<span>Board</span> <small style="font-size:.55rem;color:var(--danger);font-family:'Inter',sans-serif;font-weight:700;letter-spacing:1px">ADMIN</small></div>
    <nav>
      <a href="dashboard.php"><span class="icon">📊</span> Dashboard</a>
      <a href="manage_jobs.php" class="active"><span class="icon">💼</span> Manage Jobs</a>
      <a href="manage_users.php"><span class="icon">👥</span> Manage Users</a>
      <a href="../index.php" target="_blank"><span class="icon">🌐</span> View Site</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../auth/logout.php" class="logout-btn">⬅ Logout</a>
    </div>
  </aside>

  <main class="dash-main">
    <div class="topbar">
      <h1>Manage <span>Jobs</span> 💼</h1>
      <div style="color:var(--muted);font-size:.88rem"><?= count($jobs) ?> jobs found</div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <!-- Filters -->
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px">
      <input type="text" name="q" class="form-control" style="flex:1;min-width:200px" placeholder="🔍 Search title or company..." value="<?= htmlspecialchars($q) ?>">
      <select name="type" class="form-control" style="width:160px">
        <option value="">All Types</option>
        <?php foreach(['Full-time','Part-time','Remote','Internship','Contract'] as $t): ?>
          <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="form-control" style="width:140px">
        <option value="">All Status</option>
        <option value="open"   <?= $status==='open'  ?'selected':'' ?>>Open</option>
        <option value="closed" <?= $status==='closed'?'selected':'' ?>>Closed</option>
      </select>
      <button type="submit" class="btn-primary btn-sm">Filter</button>
      <a href="manage_jobs.php" class="btn-outline btn-sm">Clear</a>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Job Title</th><th>Employer</th><th>Type</th>
            <th>Applicants</th><th>Status</th><th>Posted</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($jobs)): ?>
            <tr><td colspan="8" class="empty-state">No jobs found.</td></tr>
          <?php else: foreach ($jobs as $i => $j): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td>
              <strong><?= htmlspecialchars($j['title']) ?></strong><br>
              <small style="color:var(--muted)">🏢 <?= htmlspecialchars($j['company']) ?></small>
            </td>
            <td><?= htmlspecialchars($j['employer_name']) ?></td>
            <td><span class="tag tag-type" style="font-size:.72rem"><?= $j['type'] ?></span></td>
            <td><span class="badge badge-open"><?= $j['app_count'] ?> applied</span></td>
            <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
            <td style="font-size:.78rem;color:var(--muted)"><?= date('d M Y',strtotime($j['created_at'])) ?></td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="manage_jobs.php?toggle=<?= $j['id'] ?>" class="btn-warn btn-sm" onclick="return confirm('Toggle status?')">
                  <?= $j['status']==='open'?'🔒 Close':'🔓 Open' ?>
                </a>
                <a href="manage_jobs.php?delete=<?= $j['id'] ?>" class="btn-danger btn-sm" onclick="return confirm('Delete this job and all its applications?')">🗑 Delete</a>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
</body>
</html>
