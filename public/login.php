<?php
require_once __DIR__ . '/../src/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount === 0) {
    redirect('setup.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        login_user($user);
        redirect('dashboard.php');
    }
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
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
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
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
