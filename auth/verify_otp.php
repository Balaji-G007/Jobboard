<?php
// auth/verify_otp.php
session_start();
require_once '../config/db.php';

$base = '/jobboard';

if (!isset($_SESSION['otp_email'])) {
    header("Location: $base/auth/register.php"); exit;
}

$error = '';
$email = $_SESSION['otp_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered = trim($_POST['otp'] ?? '');

    $stmt = $conn->prepare("SELECT id, name, role, otp_code, otp_expires_at, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'User not found.';
    } elseif ($user['is_verified']) {
        header("Location: $base/auth/login.php"); exit;
    } elseif (strtotime($user['otp_expires_at']) < time()) {
        $error = 'OTP expired. Please register again.';
    } elseif ($entered != $user['otp_code']) {
        $error = 'Incorrect OTP. Try again.';
    } else {
        // Mark verified
        $conn->prepare("UPDATE users SET is_verified=1, otp_code=NULL, otp_expires_at=NULL WHERE id=?")
             ->execute([$user['id']]);

        unset($_SESSION['otp_email'], $_SESSION['otp_code']);

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['name'];

        // Redirect by role
        if ($user['role'] === 'admin')
            header("Location: $base/admin/dashboard.php");
        elseif ($user['role'] === 'employer')
            header("Location: $base/employer/dashboard.php");
        else
            header("Location: $base/seeker/dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Verify OTP – JobBoard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg, #0f0f14);
}

.otp-inputs {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin: 24px 0;
}

.otp-inputs input {
  width: 50px;
  height: 58px;
  text-align: center;
  font-size: 1.5rem;
  font-weight: 700;
  background: var(--bg-input, #1e1e28);
  border: 2px solid var(--border, rgba(127,119,221,0.18));
  border-radius: 10px;
  color: var(--text, #e8e6f8);
  transition: border-color .2s;
}

.otp-inputs input:focus {
  outline: none;
  border-color: var(--purple, #7F77DD);
}
</style>
</head>
<body>

<div class="form-wrap" style="text-align:center; width:100%; max-width:420px;">

  <a href="<?= $base ?>/index.php" class="logo" style="display:block; text-align:center; margin-bottom:20px; font-size:1.5rem; font-weight:800; color:#fff; text-decoration:none;">
    Job<span style="color:#7F77DD;">Board</span>
  </a>

  <h2 style="margin-bottom:.4rem;">Verify OTP 🔐</h2>
  <p class="subtitle">Enter the 6-digit code sent to<br><strong style="color:#AFA9EC;"><?= htmlspecialchars($email) ?></strong></p>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- DEV HELPER: shows OTP — REMOVE IN PRODUCTION -->
  <?php if (isset($_SESSION['otp_code'])): ?>
    <div class="alert alert-success" style="font-size:.85rem;">
      🔧 Dev OTP: <strong><?= $_SESSION['otp_code'] ?></strong>
    </div>
  <?php endif; ?>

  <form method="POST" id="otpForm">
    <div class="otp-inputs">
      <?php for ($i = 1; $i <= 6; $i++): ?>
        <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]" autocomplete="off">
      <?php endfor; ?>
    </div>
    <input type="hidden" name="otp" id="otpHidden">
    <button type="submit" class="btn-primary" style="width:100%; justify-content:center; padding:.75rem; font-size:1rem;">
      Verify & Continue →
    </button>
  </form>

  <div class="form-footer">
    Wrong email? <a href="<?= $base ?>/auth/register.php">Go back</a>
  </div>

</div>

<script>
const digits = document.querySelectorAll('.otp-digit');
const hidden = document.getElementById('otpHidden');
const form   = document.getElementById('otpForm');

digits.forEach((el, i) => {
  el.addEventListener('input', () => {
    el.value = el.value.replace(/\D/, '');
    if (el.value && i < digits.length - 1) digits[i + 1].focus();
    hidden.value = [...digits].map(d => d.value).join('');
  });
  el.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !el.value && i > 0) digits[i - 1].focus();
  });
  el.addEventListener('paste', e => {
    e.preventDefault();
    const paste = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    paste.split('').forEach((ch, idx) => {
      if (digits[idx]) digits[idx].value = ch;
    });
    hidden.value = paste;
    if (digits[paste.length - 1]) digits[paste.length - 1].focus();
  });
});

form.addEventListener('submit', () => {
  hidden.value = [...digits].map(d => d.value).join('');
});

// Auto focus first box
digits[0].focus();
</script>

</body>
</html>