<?php
// admin/manage_users.php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit;
}

$msg = '';

// Delete user
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== $_SESSION['user_id']) {
        $conn->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        $msg = 'User deleted.';
    }
}

// Toggle verify
if (isset($_GET['verify'])) {
    $id   = (int)$_GET['verify'];
    $user = $conn->query("SELECT is_verified FROM users WHERE id=$id")->fetch();
    $new  = $user['is_verified'] ? 0 : 1;
    $conn->prepare("UPDATE users SET is_verified=? WHERE id=?")->execute([$new,$id]);
    $msg  = $new ? 'User verified.' : 'User unverified.';
}

// Search/filter
$q    = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? '';

$where  = ["role != 'admin'"];
$params = [];
if ($q)    { $where[] = "(name LIKE ? OR email LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($role) { $where[] = "role=?"; $params[] = $role; }

$stmt = $conn->prepare("SELECT * FROM users WHERE " . implode(' AND ',$where) . " ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Per-user job/app count
$job_counts = [];
$app_counts = [];
foreach ($conn->query("SELECT employer_id, COUNT(*) as c FROM jobs GROUP BY employer_id")->fetchAll() as $r)
    $job_counts[$r['employer_id']] = $r['c'];
foreach ($conn->query("SELECT seeker_id, COUNT(*) as c FROM applications GROUP BY seeker_id")->fetchAll() as $r)
    $app_counts[$r['seeker_id']] = $r['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Manage Users – Admin</title>
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
      <a href="manage_jobs.php"><span class="icon">💼</span> Manage Jobs</a>
      <a href="manage_users.php" class="active"><span class="icon">👥</span> Manage Users</a>
      <a href="../index.php" target="_blank"><span class="icon">🌐</span> View Site</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../auth/logout.php" class="logout-btn">⬅ Logout</a>
    </div>
  </aside>

  <main class="dash-main">
    <div class="topbar">
      <h1>Manage <span>Users</span> 👥</h1>
      <div style="color:var(--muted);font-size:.88rem"><?= count($users) ?> users</div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <!-- Filters -->
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px">
      <input type="text" name="q" class="form-control" style="flex:1;min-width:200px" placeholder="🔍 Search name or email..." value="<?= htmlspecialchars($q) ?>">
      <select name="role" class="form-control" style="width:160px">
        <option value="">All Roles</option>
        <option value="seeker"   <?= $role==='seeker'  ?'selected':'' ?>>Seeker</option>
        <option value="employer" <?= $role==='employer'?'selected':'' ?>>Employer</option>
      </select>
      <button type="submit" class="btn-primary btn-sm">Filter</button>
      <a href="manage_users.php" class="btn-outline btn-sm">Clear</a>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Name</th><th>Email</th><th>Role</th>
            <th>Activity</th><th>Verified</th><th>Joined</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr><td colspan="8" class="empty-state">No users found.</td></tr>
          <?php else: foreach ($users as $i => $u): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:34px;height:34px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:#fff;flex-shrink:0">
                  <?= strtoupper(substr($u['name'],0,1)) ?>
                </div>
                <strong><?= htmlspecialchars($u['name']) ?></strong>
              </div>
            </td>
            <td style="font-size:.82rem;color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge <?= $u['role']==='employer'?'badge-reviewed':'badge-open' ?>"><?= ucfirst($u['role']) ?></span></td>
            <td>
              <?php if ($u['role']==='employer'): ?>
                <span class="badge badge-open"><?= $job_counts[$u['id']] ?? 0 ?> jobs</span>
              <?php else: ?>
                <span class="badge badge-accepted"><?= $app_counts[$u['id']] ?? 0 ?> apps</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center">
              <a href="manage_users.php?verify=<?= $u['id'] ?>" title="Toggle verification" style="text-decoration:none;font-size:1.1rem">
                <?= $u['is_verified'] ? '✅' : '❌' ?>
              </a>
            </td>
            <td style="font-size:.78rem;color:var(--muted)"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
            <td>
              <a href="manage_users.php?delete=<?= $u['id'] ?>" class="btn-danger btn-sm" onclick="return confirm('Delete this user and all their data?')">🗑 Delete</a>
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
