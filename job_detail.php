<?php
// job_detail.php
session_start();
require_once 'config/db.php';

$id  = (int)($_GET['id'] ?? 0);
$job = $conn->prepare("SELECT j.*, u.name as employer_name FROM jobs j JOIN users u ON j.employer_id=u.id WHERE j.id=?");
$job->execute([$id]);
$job = $job->fetch();

if (!$job) { header('Location: jobs.php'); exit; }

$already_applied = false;
$error = $success = '';

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'seeker') {
    $chk = $conn->prepare("SELECT id FROM applications WHERE job_id=? AND seeker_id=?");
    $chk->execute([$id, $_SESSION['user_id']]);
    $already_applied = (bool)$chk->fetch();
}

// Handle Apply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['role'] === 'seeker') {
    if ($already_applied) {
        $error = 'You have already applied for this job.';
    } else {
        $cover  = trim($_POST['cover_letter'] ?? '');
        $resume_path = '';

        // Handle resume upload
        if (!empty($_FILES['resume']['name'])) {
            $ext   = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','doc','docx'];
            if (!in_array($ext, $allowed)) {
                $error = 'Resume must be PDF, DOC, or DOCX.';
            } elseif ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
                $error = 'Resume file size must be under 5MB.';
            } else {
                $filename     = 'resume_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $resume_path  = 'assets/uploads/resumes/' . $filename;
                move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path);
            }
        } else {
            // Check if profile has resume
            $pf = $conn->prepare("SELECT resume FROM profiles WHERE user_id=?");
            $pf->execute([$_SESSION['user_id']]);
            $pf = $pf->fetch();
            $resume_path = $pf['resume'] ?? 'no_resume';
        }

        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO applications (job_id,seeker_id,resume_submitted,cover_letter) VALUES (?,?,?,?)");
            $stmt->execute([$id, $_SESSION['user_id'], $resume_path, $cover]);
            $success = 'Application submitted successfully! 🎉';
            $already_applied = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= htmlspecialchars($job['title']) ?> – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
.job-detail-wrap { display:grid; grid-template-columns:1fr 360px; gap:28px; padding:40px 0 60px; }
.job-body h1 { font-family:'Syne',sans-serif; font-size:1.9rem; font-weight:800; margin-bottom:8px; }
.job-meta-row { display:flex; gap:12px; flex-wrap:wrap; margin:16px 0 24px; }
.job-section { margin-bottom:28px; }
.job-section h2 { font-size:1rem; font-weight:700; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid var(--border); }
.job-section p, .job-section li { color:var(--muted); font-size:0.92rem; line-height:1.8; }
.job-section ul { padding-left:20px; }
.apply-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:28px; position:sticky; top:80px; }
.apply-card h2 { font-size:1.1rem; font-weight:700; margin-bottom:20px; }
@media(max-width:800px){ .job-detail-wrap { grid-template-columns:1fr; } .apply-card { position:static; } }
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="container">
  <div style="padding-top:28px">
    <a href="jobs.php" style="color:var(--muted);font-size:.88rem;text-decoration:none">← Back to Jobs</a>
  </div>

  <div class="job-detail-wrap">
    <!-- Left: Job Info -->
    <div class="job-body">
      <h1><?= htmlspecialchars($job['title']) ?></h1>
      <div style="color:var(--muted);font-size:.95rem">🏢 <?= htmlspecialchars($job['company']) ?> &nbsp;·&nbsp; Posted by <?= htmlspecialchars($job['employer_name']) ?></div>

      <div class="job-meta-row">
        <span class="tag tag-loc">📍 <?= htmlspecialchars($job['location']) ?></span>
        <span class="tag tag-type">🕐 <?= htmlspecialchars($job['type']) ?></span>
        <?php if ($job['salary']): ?><span class="tag tag-sal">💰 <?= htmlspecialchars($job['salary']) ?></span><?php endif; ?>
        <span class="badge <?= $job['status']==='open'?'badge-open':'badge-closed' ?>"><?= ucfirst($job['status']) ?></span>
      </div>

      <div class="job-section">
        <h2>Job Description</h2>
        <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
      </div>

      <?php if ($job['requirements']): ?>
      <div class="job-section">
        <h2>Requirements</h2>
        <ul>
          <?php foreach (explode("\n", $job['requirements']) as $req): ?>
            <?php if (trim($req)): ?><li><?= htmlspecialchars(trim($req)) ?></li><?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div class="job-section">
        <h2>Posted On</h2>
        <p><?= date('d F Y', strtotime($job['created_at'])) ?></p>
      </div>
    </div>

    <!-- Right: Apply Card -->
    <div>
      <div class="apply-card">
        <h2>Apply for this Job</h2>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
          <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['user_id'])): ?>
          <p class="text-muted" style="margin-bottom:16px;font-size:.88rem">You need to login as a Job Seeker to apply.</p>
          <a href="auth/login.php" class="btn-primary btn-block">Login to Apply</a>
          <a href="auth/register.php" class="btn-outline btn-block" style="margin-top:10px">Create Account</a>

        <?php elseif ($_SESSION['role'] === 'employer'): ?>
          <div class="alert alert-warn">Employers cannot apply for jobs.</div>

        <?php elseif ($job['status'] === 'closed'): ?>
          <div class="alert alert-error">This job is no longer accepting applications.</div>

        <?php elseif ($already_applied): ?>
          <div class="alert alert-info">✅ You've already applied for this job!</div>
          <a href="seeker/my_application.php" class="btn-outline btn-block" style="margin-top:12px">View My Applications</a>

        <?php else: ?>
          <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
              <label>Cover Letter (optional)</label>
              <textarea name="cover_letter" class="form-control" placeholder="Tell the employer why you're a great fit..." rows="5"></textarea>
            </div>
            <div class="form-group">
              <label>Upload Resume (PDF/DOC)</label>
              <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
              <small class="text-muted">Max 5MB. Leave empty to use your profile resume.</small>
            </div>
            <button type="submit" class="btn-success btn-block">Submit Application 🚀</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
