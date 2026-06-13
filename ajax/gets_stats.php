<?php
// ajax/get_stats.php
// Returns live stats as JSON for dashboard auto-refresh
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Only allow logged-in admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stats = [
    'total_users'     => (int)$conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn(),
    'total_seekers'   => (int)$conn->query("SELECT COUNT(*) FROM users WHERE role='seeker'")->fetchColumn(),
    'total_employers' => (int)$conn->query("SELECT COUNT(*) FROM users WHERE role='employer'")->fetchColumn(),
    'total_jobs'      => (int)$conn->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
    'open_jobs'       => (int)$conn->query("SELECT COUNT(*) FROM jobs WHERE status='open'")->fetchColumn(),
    'closed_jobs'     => (int)$conn->query("SELECT COUNT(*) FROM jobs WHERE status='closed'")->fetchColumn(),
    'total_apps'      => (int)$conn->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
    'pending_apps'    => (int)$conn->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn(),
    'reviewed_apps'   => (int)$conn->query("SELECT COUNT(*) FROM applications WHERE status='reviewed'")->fetchColumn(),
    'accepted_apps'   => (int)$conn->query("SELECT COUNT(*) FROM applications WHERE status='accepted'")->fetchColumn(),
    'rejected_apps'   => (int)$conn->query("SELECT COUNT(*) FROM applications WHERE status='rejected'")->fetchColumn(),

    // Jobs posted per month (last 6)
    'jobs_monthly' => $conn->query("
        SELECT DATE_FORMAT(created_at,'%b') as month, COUNT(*) as count
        FROM jobs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY created_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC),

    // Apps per month (last 6)
    'apps_monthly' => $conn->query("
        SELECT DATE_FORMAT(applied_at,'%b') as month, COUNT(*) as count
        FROM applications WHERE applied_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(applied_at), MONTH(applied_at) ORDER BY applied_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC),

    // Status breakdown
    'status_breakdown' => $conn->query("
        SELECT status, COUNT(*) as count FROM applications GROUP BY status
    ")->fetchAll(PDO::FETCH_ASSOC),

    // Type breakdown
    'type_breakdown' => $conn->query("
        SELECT type, COUNT(*) as count FROM jobs GROUP BY type
    ")->fetchAll(PDO::FETCH_ASSOC),

    'timestamp' => date('Y-m-d H:i:s'),
];

echo json_encode($stats);
