<?php
// seeker/profile.php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';
checkRole('seeker');

$uid  = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Load user + profile
$user    = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch();
$profile = $conn->query("SELECT * FROM profiles WHERE user_id=$uid")->fetch();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio        = trim($_POST['bio'] ?? '');
    $skills     = trim($_POST['skills'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $education  = trim($_POST['education'] ?? '');
    $linkedin   = trim($_POST['linkedin'] ?? '');
    $github     = trim($_POST['github'] ?? '');
    $resume_path = $profile['resume'] ?? '';

    // Handle resume upload
    if (!empty($_FILES['resume']['name'])) {
        $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf','doc','docx'])) {
            $error = 'Resume must be PDF, DOC, or DOCX.';
        } elseif ($_FILES['resume']['size'] > 5*1024*1024) {
            $error = 'Resume must be under 5MB.';
        } else {
            $fname       = 'resume_' . $uid . '_' . time() . '.' . $ext;
            $resume_path = 'assets/uploads/resumes/' . $fname;
            move_uploaded_file($_FILES['resume']['tmp_name'], '../' . $resume_path);
        }
    }

    if (!$error) {
        if ($profile) {
            $stmt = $conn->prepare("UPDATE profiles SET bio=?,skills=?,experience=?,education=?,resume=?,linkedin=?,github=? WHERE user_id=?");
            $stmt->execute([$bio,$skills,$experience,$education,$resume_path,$linkedin,$github,$uid]);
        } else {
            $stmt = $conn->prepare("INSERT INTO profiles (user_id,bio,skills,experience,education,resume,linkedin,github) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$uid,$bio,$skills,$experience,$education,$resume_path,$linkedin,$github]);
        }
        $success = 'Profile updated successfully!';
        $profile = $conn->query("SELECT * FROM profiles WHERE user_id=$uid")->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>My Profile – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.profile-header {
  display:flex; align-items:center; gap:20px;
  background:var(--card); border:1px solid var(--border); border-radius:var(--radius);
  padding:28px; margin-bottom:28px;
}
.profile-avatar {
  width:72px; height:72px; border-radius:50%; background:var(--accent);
  display:flex; align-items:center; justify-content:center;
  font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; color:#fff; flex-shrink:0;
}
.profile-header h2 { font-family:'Syne',sans-serif; font-size:1.4rem; font-weight:800; }
.profile-header p  { color:var(--muted); font-size:.88rem; margin-top:4px; }
</style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo">Job<span>Board</span></div>
    <nav>
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="../jobs.php"><span class="icon">🔍</span> Browse Jobs</a>
      <a href="my_application.php"><span class="icon">📋</span> My Applications</a>
      <a href="profile.php" class="active"><span class="icon">👤</span> My Profile</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../auth/logout.php" class="logout-btn">⬅ Logout</a>
    </div>
  </aside>

  <main class="dash-main">
    <div class="topbar">
      <h1>My <span>Profile</span></h1>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <div class="profile-header">
      <div class="profile-avatar"><?= strtoupper(substr($name,0,1)) ?></div>
      <div>
        <h2><?= htmlspecialchars($name) ?></h2>
        <p>📧 <?= htmlspecialchars($user['email']) ?></p>
        <p>🗓 Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
      </div>
    </div>

    <div class="card">
      <form method="POST" enctype="multipart/form-data">
        <div class="grid-2">
          <div class="form-group">
            <label>Bio / Summary</label>
            <textarea name="bio" class="form-control" rows="4" placeholder="Tell employers about yourself..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Skills (comma separated)</label>
            <textarea name="skills" class="form-control" rows="4" placeholder="e.g. PHP, MySQL, React, Node.js..."><?= htmlspecialchars($profile['skills'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="form-group">
          <label>Work Experience</label>
          <textarea name="experience" class="form-control" rows="5" placeholder="Describe your work history..."><?= htmlspecialchars($profile['experience'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label>Education</label>
          <textarea name="education" class="form-control" rows="3" placeholder="Your educational background..."><?= htmlspecialchars($profile['education'] ?? '') ?></textarea>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label>LinkedIn URL</label>
            <input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/yourname" value="<?= htmlspecialchars($profile['linkedin'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>GitHub URL</label>
            <input type="url" name="github" class="form-control" placeholder="https://github.com/yourname" value="<?= htmlspecialchars($profile['github'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Upload Resume (PDF/DOC/DOCX — Max 5MB)</label>
          <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
          <?php if (!empty($profile['resume'])): ?>
            <small class="text-muted">Current: <a href="../<?= htmlspecialchars($profile['resume']) ?>" target="_blank" style="color:var(--accent2)">View Resume</a></small>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn-primary">💾 Save Profile</button>
      </form>
    </div>
  </main>
</div>
</body>
</html>
