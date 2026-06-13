<?php
// seeker/my_application.php

session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

checkRole('seeker');

$uid = $_SESSION['user_id'];
$name = $_SESSION['name'];

$stmt = $conn->prepare("
    SELECT a.*, j.title, j.location, j.job_type, j.salary
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE a.seeker_id = ?
    ORDER BY a.applied_at DESC
");

$stmt->execute([$uid]);
$apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Applications - JobBoard</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">

</head>
<body>

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-logo">
            Job<span>Board</span>
        </div>

        <nav>
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="../jobs.php">🔍 Browse Jobs</a>
            <a href="my_application.php" class="active">📋 My Applications</a>
            <a href="profile.php">👤 My Profile</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../auth/logout.php" class="logout-btn">⬅ Logout</a>
        </div>
    </aside>

    <main class="dash-main">

        <div class="topbar">
            <h1>My Applications</h1>
            <a href="../jobs.php" class="btn-primary">+ Apply More Jobs</a>
        </div>

        <?php if (empty($apps)): ?>

            <div class="section-card">
                <h3>No Applications Found</h3>
                <p>You haven't applied to any jobs yet.</p>
                <a href="../jobs.php" class="btn-primary">
                    Browse Jobs
                </a>
            </div>

        <?php else: ?>

            <div class="table-wrap">
                <table border="1" cellpadding="10" cellspacing="0">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Job Title</th>
                            <th>Location</th>
                            <th>Job Type</th>
                            <th>Salary</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($apps as $i => $a): ?>

                        <tr>

                            <td><?= $i + 1 ?></td>

                            <td>
                                <?= htmlspecialchars($a['title']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($a['location']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($a['job_type']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($a['salary']) ?>
                            </td>

                            <td>
                                <?= date('d M Y', strtotime($a['applied_at'])) ?>
                            </td>

                            <td>
                                <?= ucfirst($a['status']) ?>
                            </td>

                            <td>
                                <a href="../job_detail.php?id=<?= $a['job_id'] ?>">
                                    View Job
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>
            </div>

        <?php endif; ?>

    </main>

</div>

</body>
</html>