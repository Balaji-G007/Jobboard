<?php
// employer/post_job.php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';
checkRole('employer');

$uid     = $_SESSION['user_id'];
$name    = $_SESSION['name'];
$success = $error = '';
$editing = false;
$job     = [];

// Edit mode
$edit_id = (int)($_GET['edit'] ?? 0);
if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id=? AND employer_id=?");
    $stmt->execute([$edit_id, $uid]);
    $job = $stmt->fetch();
    if ($job) $editing = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $company     = trim($_POST['company'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $type        = $_POST['type'] ?? 'Full-time';
    $description = trim($_POST['description'] ?? '');
    $requirements= trim($_POST['requirements'] ?? '');
    $salary      = trim($_POST['salary'] ?? '');
    $status      = $_POST['status'] ?? 'open';

    if (!$title || !$company || !$location || !$description) {
        $error = 'Title, company, location, and description are required.';
    } else {
        if ($editing) {
            $stmt = $conn->prepare("UPDATE jobs SET title=?,company=?,location=?,type=?,description=?,requirements=?,salary=?,status=? WHERE id=? AND employer_id=?");
            $stmt->execute([$title,$company,$location,$type,$description,$requirements,$salary,$status,$edit_id,$uid]);
            $success = 'Job updated successfully!';
        } else {
            $stmt = $conn->prepare("INSERT INTO jobs (employer_id,title,company,location,type,description,requirements,salary) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$uid,$title,$company,$location,$type,$description,$requirements,$salary]);
            $success = 'Job posted successfully! 🎉';
        }
        // Refresh job data
        if ($editing) {
            $stmt2 = $conn->prepare("SELECT * FROM jobs WHERE id=? AND employer_id=?");
            $stmt2->execute([$edit_id,$uid]); $job = $stmt2->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= $editing?'Edit Job':'Post a Job' ?> – JobBoard</title>
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
      <a href="post_job.php" class="active"><span class="icon">➕</span> Post a Job</a>
      <a href="view_applicants.php"><span class="icon">👥</span> Applicants</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../auth/logout.php" class="logout-btn">⬅ Logout</a>
    </div>
  </aside>

  <main class="dash-main">
    <div class="topbar">
      <h1><?= $editing ? 'Edit <span>Job</span>' : 'Post a <span>Job</span>' ?></h1>
      <a href="dashboard.php" class="btn-outline btn-sm">← Back</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <div class="card">
      <form method="POST">
        <div class="grid-2">
          <div class="form-group">
            <label>Job Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Frontend Developer" value="<?= htmlspecialchars($_POST['title'] ?? $job['title'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Company Name *</label>
            <input type="text" name="company" class="form-control" placeholder="e.g. Acme Corp" value="<?= htmlspecialchars($_POST['company'] ?? $job['company'] ?? '') ?>" required>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Location *</label>
            <input type="text" name="location" class="form-control" placeholder="e.g. Chennai, Tamil Nadu" value="<?= htmlspecialchars($_POST['location'] ?? $job['location'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Job Type</label>
            <select name="type" class="form-control">
              <?php foreach (['Full-time','Part-time','Remote','Internship','Contract'] as $t): ?>
                <option value="<?= $t ?>" <?= (($_POST['type']??$job['type']??'Full-time')===$t)?'selected':'' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>Salary / Range (optional)</label>
            <input type="text" name="salary" class="form-control" placeholder="e.g. ₹4-6 LPA or $60k" value="<?= htmlspecialchars($_POST['salary'] ?? $job['salary'] ?? '') ?>">
          </div>
          <?php if ($editing): ?>
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
              <option value="open"   <?= (($_POST['status']??$job['status']??'open')==='open')  ?'selected':'' ?>>Open</option>
              <option value="closed" <?= (($_POST['status']??$job['status']??'')==='closed')?'selected':'' ?>>Closed</option>
            </select>
          </div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label>Job Description *</label>
          <textarea name="description" class="form-control" rows="7" placeholder="Describe the role, responsibilities, and what you're looking for..." required><?= htmlspecialchars($_POST['description'] ?? $job['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label>Requirements (one per line)</label>
          <textarea name="requirements" class="form-control" rows="5" placeholder="2+ years PHP experience&#10;Strong MySQL skills&#10;Good communication"><?= htmlspecialchars($_POST['requirements'] ?? $job['requirements'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:12px;align-items:center">
          <button type="submit" class="btn-primary"><?= $editing ? '💾 Update Job' : '🚀 Post Job' ?></button>
          <?php if ($editing): ?>
            <a href="post_job.php" class="btn-outline">+ Post New Job</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- My Jobs List -->
    <?php
    $my_jobs = $conn->query("SELECT j.*, COUNT(a.id) as app_count FROM jobs j LEFT JOIN applications a ON j.id=a.job_id WHERE j.employer_id=$uid GROUP BY j.id ORDER BY j.created_at DESC")->fetchAll();
    ?>
    <?php if (!empty($my_jobs)): ?>
    <div class="section-card" style="margin-top:32px">
      <div class="section-head"><h2>Your Job Postings</h2></div>
      <div class="table-wrap" style="border:none;border-radius:0">
        <table>
          <thead><tr><th>Title</th><th>Type</th><th>Location</th><th>Applicants</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($my_jobs as $j): ?>
            <tr>
              <td><strong><?= htmlspecialchars($j['title']) ?></strong></td>
              <td><?= htmlspecialchars($j['type']) ?></td>
              <td><?= htmlspecialchars($j['location']) ?></td>
              <td><span class="badge badge-open"><?= $j['app_count'] ?> applied</span></td>
              <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
              <td style="display:flex;gap:8px">
                <a href="post_job.php?edit=<?= $j['id'] ?>" class="btn-outline btn-sm">✏️ Edit</a>
                <a href="view_applicants.php?job_id=<?= $j['id'] ?>" class="btn-primary btn-sm">👥 View</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
