<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/session.php';
require_once __DIR__ . '/../src/helpers.php';

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount > 0) {
    redirect('login.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($username === '' || $fullName === '' || $password === '') {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    } elseif ($password !== $confirm) {
        $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, "admin")');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName]);
        redirect('login.php?setup=1');
    }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="UTF-8">
<script>(function(){try{var t=localStorage.getItem('kp_theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>ตั้งค่าเริ่มต้น - ระบบติดตามการเบิกของ</title>
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
  <p class="auth-sub">ตั้งค่าบัญชีผู้ดูแลระบบคนแรก (ทำครั้งเดียว)</p>
  <?php if ($error): ?><div class="alert alert-error"><?= icon('alert-circle') ?><span><?= e($error) ?></span></div><?php endif; ?>
  <form method="post" class="auth-form">
    <label>ชื่อ-นามสกุล
      <div class="input-icon"><?= icon('user') ?><input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required></div>
    </label>
    <label>ชื่อผู้ใช้
      <div class="input-icon"><?= icon('user') ?><input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required></div>
    </label>
    <label>รหัสผ่าน
      <div class="input-icon"><?= icon('lock') ?><input type="password" name="password" required minlength="6"></div>
    </label>
    <label>ยืนยันรหัสผ่าน
      <div class="input-icon"><?= icon('lock') ?><input type="password" name="confirm" required minlength="6"></div>
    </label>
    <button type="submit" class="btn btn-primary btn-block">สร้างบัญชีผู้ดูแลระบบ</button>
  </form>
</div>
</body>
</html>
