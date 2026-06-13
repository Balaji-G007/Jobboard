<?php
// jobs.php — Public job listing with AJAX search
session_start();
require_once 'config/db.php';

$q        = trim($_GET['q']   ?? '');
$type     = $_GET['type']     ?? '';
$location = trim($_GET['loc'] ?? '');

// Build query
$where = ["j.status = 'open'"];
$params = [];

if ($q) {
    $where[]  = "(j.title LIKE ? OR j.company LIKE ? OR j.description LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
if ($type) {
    $where[]  = "j.type = ?";
    $params[] = $type;
}
if ($location) {
    $where[]  = "j.location LIKE ?";
    $params[] = "%$location%";
}

$sql  = "SELECT j.* FROM jobs j WHERE " . implode(' AND ', $where) . " ORDER BY j.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Browse Jobs – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<!-- ✅ UPDATED: css path changed from css/style.css → assets/css/style.css -->
<link rel="stylesheet" href="assets/css/style.css">
<style>
.page-hero { padding:40px 0 32px; }
.page-hero h1 { font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; margin-bottom:6px; }
.filter-bar {
  display:flex; gap:12px; flex-wrap:wrap; padding:20px 24px;
  background:var(--card); border:1px solid var(--border); border-radius:var(--radius); margin-bottom:28px;
}
.filter-bar input, .filter-bar select {
  flex:1; min-width:160px; padding:11px 16px;
  background:var(--surface); border:1px solid var(--border);
  border-radius:9px; color:var(--text); font-size:0.88rem; font-family:'Inter',sans-serif;
}
.filter-bar input:focus, .filter-bar select:focus { outline:none; border-color:var(--accent); }
.filter-bar select option { background:var(--surface); }
.jobs-list { display:flex; flex-direction:column; gap:14px; }
.job-row-card {
  background:var(--card); border:1px solid var(--border); border-radius:var(--radius);
  padding:20px 24px; display:flex; align-items:center; justify-content:space-between;
  gap:20px; transition:border-color .2s, transform .15s; text-decoration:none; color:var(--text);
}
.job-row-card:hover { border-color:var(--accent); transform:translateX(4px); }
.job-row-left h3 { font-size:1rem; font-weight:700; margin-bottom:4px; }
.job-row-left .company { font-size:0.85rem; color:var(--muted); margin-bottom:10px; }
.job-row-right { display:flex; align-items:center; gap:12px; flex-shrink:0; }
.results-count { color:var(--muted); font-size:0.88rem; margin-bottom:16px; }
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="container">
  <div class="page-hero">
    <h1>Browse Jobs</h1>
    <p class="text-muted">Find your next opportunity from <?= count($jobs) ?> open positions</p>
  </div>

  <!-- Filters — ✅ ADDED: id="filterForm", id="searchQ", id="searchLoc", id="searchType" -->
  <form class="filter-bar" method="GET" id="filterForm">
    <input id="searchQ" type="text" name="q" placeholder="🔍 Job title or keyword" value="<?= htmlspecialchars($q) ?>">
    <input id="searchLoc" type="text" name="loc" placeholder="📍 Location" value="<?= htmlspecialchars($location) ?>">
    <select id="searchType" name="type">
      <option value="">All Types</option>
      <?php foreach (['Full-time','Part-time','Remote','Internship','Contract'] as $t): ?>
        <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-primary btn-sm">Filter</button>
    <a href="jobs.php" class="btn-outline btn-sm">Clear</a>
  </form>

  <!-- ✅ ADDED: id="resultsCount" -->
  <div class="results-count" id="resultsCount"><?= count($jobs) ?> job<?= count($jobs)!=1?'s':'' ?> found</div>

  <!-- Job List — ✅ ADDED: id="jobsList" -->
  <div class="jobs-list" id="jobsList">
    <?php if (empty($jobs)): ?>
      <div class="empty-state">No jobs match your search. <a href="jobs.php" style="color:var(--accent)">Clear filters</a></div>
    <?php else: foreach ($jobs as $job): ?>
      <a href="job_detail.php?id=<?= $job['id'] ?>" class="job-row-card">
        <div class="job-row-left">
          <h3><?= htmlspecialchars($job['title']) ?></h3>
          <div class="company">🏢 <?= htmlspecialchars($job['company']) ?></div>
          <div class="tags">
            <span class="tag tag-loc">📍 <?= htmlspecialchars($job['location']) ?></span>
            <span class="tag tag-type"><?= htmlspecialchars($job['type']) ?></span>
            <?php if ($job['salary']): ?>
              <span class="tag tag-sal">💰 <?= htmlspecialchars($job['salary']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="job-row-right">
          <span style="font-size:.78rem;color:var(--muted)"><?= date('d M Y', strtotime($job['created_at'])) ?></span>
          <span class="btn-primary btn-sm">View →</span>
        </div>
      </a>
    <?php endforeach; endif; ?>
  </div>
</main>

<?php include 'includes/footer.php'; ?>

<!-- ✅ ADDED: search.js at bottom for AJAX real-time search -->
<script src="assets/js/search.js"></script>
</body>
</html>
