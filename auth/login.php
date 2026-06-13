<?php
// auth/login.php
session_start();
require_once '../config/db.php';

$base = '/jobboard';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin')         header("Location: $base/admin/dashboard.php");
    elseif ($_SESSION['role'] === 'employer')  header("Location: $base/employer/dashboard.php");
    else                                        header("Location: $base/seeker/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'No account found with that email.';
    } elseif (!$user['is_verified']) {
        $_SESSION['otp_email'] = $email;
        header("Location: $base/auth/verify_otp.php"); exit;
    } elseif (!password_verify($pass, $user['password'])) {
        $error = 'Incorrect password.';
    } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['name'];

        if ($user['role'] === 'admin')        header("Location: $base/admin/dashboard.php");
        elseif ($user['role'] === 'employer') header("Location: $base/employer/dashboard.php");
        else                                  header("Location: $base/seeker/dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Login – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-box">
    <a href="<?= $base ?>/index.php" class="logo" style="display:block;text-align:center;margin-bottom:20px;font-size:1.4rem">Job<span>Board</span></a>
    <h2>Welcome Back 👋</h2>
    <p class="sub">Login to your JobBoard account</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@email.com"
               value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" placeholder="Your password" required>
      </div>
      <button type="submit" class="btn-primary btn-block">Login →</button>
    </form>

    <div class="auth-link">Don't have an account? <a href="<?= $base ?>/auth/register.php">Register</a></div>
  </div>
</div>
</body>
</html>
