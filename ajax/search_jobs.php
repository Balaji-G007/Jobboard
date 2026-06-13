<?php
// ajax/search_jobs.php
// Live job search — called by assets/js/search.js
require_once '../config/db.php';

header('Content-Type: application/json');

$q        = trim($_GET['q']    ?? '');
$type     = trim($_GET['type'] ?? '');
$location = trim($_GET['loc']  ?? '');

$where  = ["j.status = 'open'"];
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

$sql  = "SELECT j.id, j.title, j.company, j.location, j.type, j.salary, j.created_at
         FROM jobs j
         WHERE " . implode(' AND ', $where) . "
         ORDER BY j.created_at DESC
         LIMIT 30";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format dates
foreach ($jobs as &$job) {
    $job['posted'] = date('d M Y', strtotime($job['created_at']));
    unset($job['created_at']);
}

echo json_encode([
    'count' => count($jobs),
    'jobs'  => $jobs,
]);
