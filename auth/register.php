<?php
// auth/register.php
session_start();
require_once '../config/db.php';

$base  = '/jobboard';
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = $_POST['role'] ?? 'seeker';

    if (!$name || !$email || !$pass) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'Email already registered. <a href="login.php">Login?</a>';
        } else {
            $otp     = rand(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $hash    = password_hash($pass, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,otp,otp_expires_at) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $email, $hash, $role, $otp, $expires]);

            $_SESSION['otp_email'] = $email;
            $_SESSION['otp_code']  = $otp;

            header("Location: $base/auth/verify_otp.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Register – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-box">
    <a href="<?= $base ?>/index.php" class="logo" style="display:block;text-align:center;margin-bottom:20px;font-size:1.4rem">Job<span>Board</span></a>
    <h2>Create Account 🚀</h2>
    <p class="sub">Join JobBoard — find or post jobs today</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" class="form-control" placeholder="John Doe"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
      </div>
      <div class="form-group">
        <label>I am a...</label>
        <select name="role" class="form-control">
          <option value="seeker"   <?= ($_POST['role']??'seeker')==='seeker'  ?'selected':'' ?>>Job Seeker</option>
          <option value="employer" <?= ($_POST['role']??'')==='employer'      ?'selected':'' ?>>Employer / Recruiter</option>
        </select>
      </div>
      <button type="submit" class="btn-primary btn-block">Create Account →</button>
    </form>

    <div class="auth-link">Already have an account? <a href="<?= $base ?>/auth/login.php">Login</a></div>
  </div>
</div>
</body>
</html>
