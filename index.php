<?php
// index.php — Homepage
session_start();
require_once 'config/db.php';

// Stats for hero
$total_jobs     = $conn->query("SELECT COUNT(*) FROM jobs WHERE status='open'")->fetchColumn();
$total_seekers  = $conn->query("SELECT COUNT(*) FROM users WHERE role='seeker'")->fetchColumn();
$total_employers= $conn->query("SELECT COUNT(*) FROM users WHERE role='employer'")->fetchColumn();

// Featured jobs
$featured = $conn->query("SELECT * FROM jobs WHERE status='open' ORDER BY created_at DESC LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>JobBoard – Find Your Dream Job</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
.hero {
  text-align:center; padding:90px 20px 70px;
  background: radial-gradient(ellipse at top, rgba(108,99,255,.15) 0%, transparent 60%);
}
.hero h1 {
  font-family:'Syne',sans-serif; font-size:clamp(2rem,5vw,3.6rem); font-weight:800;
  line-height:1.15; margin-bottom:20px; letter-spacing:-1px;
}
.hero h1 span { color:var(--accent); }
.hero p { color:var(--muted); font-size:1.1rem; max-width:560px; margin:0 auto 36px; }
.hero-btns { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }

.search-bar {
  display:flex; gap:0; max-width:600px; margin:0 auto 60px;
  background:var(--card); border:1px solid var(--border); border-radius:12px; overflow:hidden;
}
.search-bar input {
  flex:1; padding:16px 20px; background:transparent; border:none;
  color:var(--text); font-size:0.95rem; font-family:'Inter',sans-serif;
}
.search-bar input:focus { outline:none; }
.search-bar button {
  padding:14px 28px; background:var(--accent); border:none; color:#fff;
  font-size:0.92rem; font-weight:600; cursor:pointer; font-family:'Inter',sans-serif;
  transition:background .2s;
}
.search-bar button:hover { background:#5a52e0; }

.counter-row { display:flex; justify-content:center; gap:48px; margin:0 auto 80px; flex-wrap:wrap; }
.counter-item { text-align:center; }
.counter-item .num { font-family:'Syne',sans-serif; font-size:2.2rem; font-weight:800; color:var(--accent2); }
.counter-item .lbl { font-size:0.85rem; color:var(--muted); margin-top:4px; }

.section-title { font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:800; margin-bottom:8px; }
.section-sub   { color:var(--muted); font-size:0.92rem; margin-bottom:32px; }

.how-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; margin-bottom:80px; }
.how-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:28px 24px; }
.how-card .step { font-size:2rem; margin-bottom:16px; }
.how-card h3 { font-size:1rem; font-weight:700; margin-bottom:8px; }
.how-card p  { color:var(--muted); font-size:0.88rem; }

@media(max-width:700px){
  .how-grid { grid-template-columns:1fr; }
  .counter-row { gap:28px; }
}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<main>
  <!-- Hero -->
  <section class="hero container">
    <h1>Find Your <span>Dream Job</span><br>or Hire Top Talent</h1>
    <p>JobBoard connects ambitious professionals with great companies. Your next opportunity is one click away.</p>

    <form class="search-bar" action="jobs.php" method="GET">
      <input type="text" name="q" placeholder="🔍  Search job title, company, or keyword...">
      <button type="submit">Search Jobs</button>
    </form>

    <div class="hero-btns">
      <a href="jobs.php" class="btn-primary">Browse All Jobs</a>
      <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="auth/register.php?role=employer" class="btn-outline">Post a Job →</a>
      <?php endif; ?>
    </div>
  </section>

  <!-- Counters -->
  <div class="container">
    <div class="counter-row">
      <div class="counter-item"><div class="num"><?= $total_jobs ?>+</div><div class="lbl">Open Jobs</div></div>
      <div class="counter-item"><div class="num"><?= $total_seekers ?>+</div><div class="lbl">Job Seekers</div></div>
      <div class="counter-item"><div class="num"><?= $total_employers ?>+</div><div class="lbl">Companies Hiring</div></div>
    </div>

    <!-- Featured Jobs -->
    <div class="section-title">Latest Jobs</div>
    <div class="section-sub">Fresh opportunities updated daily</div>
    <div class="grid-3" style="margin-bottom:40px">
      <?php if (empty($featured)): ?>
        <div class="empty-state" style="grid-column:span 3">No jobs posted yet. Be the first to post!</div>
      <?php else: foreach ($featured as $job): ?>
        <a href="job_detail.php?id=<?= $job['id'] ?>" class="job-card-public">
          <h3><?= htmlspecialchars($job['title']) ?></h3>
          <div class="company">🏢 <?= htmlspecialchars($job['company']) ?></div>
          <p style="font-size:.82rem;color:var(--muted);margin-bottom:10px"><?= substr(strip_tags($job['description']),0,90) ?>...</p>
          <div class="tags">
            <span class="tag tag-loc">📍 <?= htmlspecialchars($job['location']) ?></span>
            <span class="tag tag-type"><?= htmlspecialchars($job['type']) ?></span>
            <?php if ($job['salary']): ?>
              <span class="tag tag-sal">💰 <?= htmlspecialchars($job['salary']) ?></span>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; endif; ?>
    </div>
    <div style="text-align:center;margin-bottom:80px">
      <a href="jobs.php" class="btn-primary">View All Jobs →</a>
    </div>

    <!-- How it works -->
    <div class="section-title">How It Works</div>
    <div class="section-sub">Get started in 3 simple steps</div>
    <div class="how-grid">
      <div class="how-card"><div class="step">📝</div><h3>Create Account</h3><p>Register as a job seeker or employer. Verify your email with a secure OTP.</p></div>
      <div class="how-card"><div class="step">🔍</div><h3>Find or Post Jobs</h3><p>Seekers browse and apply. Employers post openings and manage applicants.</p></div>
      <div class="how-card"><div class="step">🎉</div><h3>Get Hired</h3><p>Track your application status in real time and land your dream role.</p></div>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
