<?php
require_once __DIR__ . '/../src/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount === 0) {
    redirect('setup.php');
}

const LOGIN_MAX_ATTEMPTS = 8;
const LOGIN_LOCKOUT_MINUTES = 15;

$clientIp = client_ip();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL ? MINUTE)'
    );
    $stmt->execute([$clientIp, LOGIN_LOCKOUT_MINUTES]);
    $recentFailures = (int) $stmt->fetchColumn();

    if ($recentFailures >= LOGIN_MAX_ATTEMPTS) {
        $error = 'พยายามเข้าสู่ระบบผิดหลายครั้งเกินไป กรุณารอ ' . LOGIN_LOCKOUT_MINUTES . ' นาทีแล้วลองใหม่';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip = ?');
            $stmt->execute([$clientIp]);
            session_regenerate_id(true); // new session id on privilege change
            login_user($user);
            redirect('dashboard.php');
        }

        $stmt = $pdo->prepare('INSERT INTO login_attempts (ip, username) VALUES (?, ?)');
        $stmt->execute([$clientIp, mb_substr($username, 0, 50)]);
        $remaining = LOGIN_MAX_ATTEMPTS - $recentFailures - 1;
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
            . ($remaining > 0 && $remaining <= 3 ? " (เหลืออีก {$remaining} ครั้งก่อนถูกล็อกชั่วคราว)" : '');
    }
}
$justSetup = isset($_GET['setup']);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="UTF-8">
<script>(function(){try{var t=localStorage.getItem('kp_theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>เข้าสู่ระบบ - ระบบติดตามการเบิกของ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&display=swap" rel="stylesheet">
<meta name="theme-color" content="#102532">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
<link rel="icon" type="image/svg+xml" href="<?= asset('assets/favicon.svg') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
</head>
<body class="auth-body">
<div class="auth-card">
  <div class="auth-logo"><?= icon('box') ?></div>
  <div class="auth-brand">ระบบติดตามการเบิกของ</div>
  <p class="auth-sub">เข้าสู่ระบบเพื่อบันทึกและติดตามการเบิกของ</p>
  <?php if ($justSetup): ?><div class="alert alert-success"><?= icon('check-circle') ?><span>สร้างบัญชีผู้ดูแลระบบสำเร็จ กรุณาเข้าสู่ระบบ</span></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= icon('alert-circle') ?><span><?= e($error) ?></span></div><?php endif; ?>
  <form method="post" class="auth-form">
    <label>ชื่อผู้ใช้
      <div class="input-icon"><?= icon('user') ?><input type="text" name="username" required autofocus></div>
    </label>
    <label>รหัสผ่าน
      <div class="input-icon"><?= icon('lock') ?><input type="password" name="password" required></div>
    </label>
    <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
  </form>
</div>
</body>
</html>
