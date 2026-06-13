<?php
// includes/header.php
$current = basename($_SERVER['PHP_SELF']);

// Detect if we're in a subfolder (admin/, seeker/, employer/, auth/)
$depth   = substr_count(str_replace('\\','/',dirname($_SERVER['PHP_SELF'])), '/');
$isRoot  = (dirname($_SERVER['PHP_SELF']) === '/jobboard' || dirname($_SERVER['PHP_SELF']) === '/jobbboard/');
$base    = '/jobboard'; // ← your XAMPP folder name
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container hflex">
    <a href="<?= $base ?>/index.php" class="logo">Job<span>Board</span></a>
    <nav class="main-nav">
      <a href="<?= $base ?>/jobs.php" class="<?= $current=='jobs.php'?'active':'' ?>">Browse Jobs</a>

      <?php if (isset($_SESSION['user_id'])): ?>

        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="<?= $base ?>/admin/dashboard.php">Dashboard</a>
          <a href="<?= $base ?>/admin/manage_jobs.php">Manage Jobs</a>
          <a href="<?= $base ?>/admin/manage_users.php">Manage Users</a>

        <?php elseif ($_SESSION['role'] === 'seeker'): ?>
          <a href="<?= $base ?>/seeker/dashboard.php">Dashboard</a>
          <a href="<?= $base ?>/seeker/my_application.php">My Applications</a>
          <a href="<?= $base ?>/seeker/profile.php">Profile</a>

        <?php else: ?>
          <a href="<?= $base ?>/employer/dashboard.php">Dashboard</a>
          <a href="<?= $base ?>/employer/post_job.php">Post Job</a>
          <a href="<?= $base ?>/employer/view_applicants.php">Applicants</a>
        <?php endif; ?>

        <span style="color:var(--muted);font-size:.85rem">Hi, <?= htmlspecialchars($_SESSION['name']) ?></span>
        <a href="<?= $base ?>/auth/logout.php" class="btn-outline">Logout</a>

      <?php else: ?>
        <a href="<?= $base ?>/auth/login.php" class="btn-outline">Login</a>
        <a href="<?= $base ?>/auth/register.php" class="btn-primary">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
