<?php $user = current_user(); $initial = mb_substr($user['full_name'] ?? '?', 0, 1); ?>
<!doctype html>
<html lang="th">
<head>
<meta charset="UTF-8">
<script>(function(){try{var t=localStorage.getItem('kp_theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle ?? 'ระบบติดตามการเบิกของ') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="<?= asset('assets/favicon.svg') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <span class="brand-icon"><?= icon('box') ?></span>
      <span>ระบบติดตามการเบิกของ</span>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="<?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>"><?= icon('grid') ?><span>ภาพรวม</span></a>
      <a href="incidents.php" class="<?= ($activeNav ?? '') === 'incidents' ? 'active' : '' ?>"><?= icon('clipboard') ?><span>บันทึกรายการ</span></a>
      <a href="items.php" class="<?= ($activeNav ?? '') === 'items' ? 'active' : '' ?>"><?= icon('archive') ?><span>คลังของ/อะไหล่</span></a>
      <a href="employees.php" class="<?= ($activeNav ?? '') === 'employees' ? 'active' : '' ?>"><?= icon('users') ?><span>พนักงาน</span></a>
      <a href="reports.php" class="<?= ($activeNav ?? '') === 'reports' ? 'active' : '' ?>"><?= icon('chart') ?><span>รายงานสรุป</span></a>
    </nav>
    <div class="sidebar-user">
      <div class="sidebar-user-info">
        <span class="avatar"><?= e($initial) ?></span>
        <div>
          <div class="sidebar-user-name"><?= e($user['full_name'] ?? '') ?></div>
          <div class="sidebar-user-role"><?= ($user['role'] ?? '') === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้จัดการ' ?></div>
        </div>
      </div>
      <a href="logout.php" class="logout-link"><?= icon('logout') ?><span>ออกจากระบบ</span></a>
    </div>
  </aside>
  <div class="main-area">
    <header class="topbar">
      <h1 class="page-title"><?= e($pageTitle ?? '') ?></h1>
      <div class="topbar-user">
        <button type="button" class="theme-toggle" id="themeToggle" aria-label="สลับโหมดมืด/สว่าง" title="สลับโหมดมืด/สว่าง">
          <span class="theme-icon theme-icon-sun"><?= icon('sun', 'icon icon-sm') ?></span>
          <span class="theme-icon theme-icon-moon"><?= icon('moon', 'icon icon-sm') ?></span>
        </button>
        <span class="avatar avatar-sm"><?= e($initial) ?></span>
        <a href="logout.php" class="topbar-logout" title="ออกจากระบบ" aria-label="ออกจากระบบ"><?= icon('logout', 'icon icon-sm') ?></a>
      </div>
    </header>
    <main class="content">
<?php $flashSuccess = flash('success'); $flashError = flash('error'); ?>
<?php if ($flashSuccess): ?><div class="alert alert-success"><?= icon('check-circle') ?><span><?= e($flashSuccess) ?></span></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= icon('alert-circle') ?><span><?= e($flashError) ?></span></div><?php endif; ?>
