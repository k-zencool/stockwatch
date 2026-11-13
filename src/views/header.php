<?php $user = current_user(); $initial = mb_substr($user['full_name'] ?? '?', 0, 1); ?>
<!doctype html>
<html lang="th">
<head>
<meta charset="UTF-8">
<script>(function(){try{var t=localStorage.getItem('kp_theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title><?= e($pageTitle ?? 'ระบบติดตามการเบิกของ') ?></title>
<meta name="theme-color" content="#102532" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0a0f16" media="(prefers-color-scheme: dark)">
<meta name="application-name" content="StockWatch">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="StockWatch">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&display=swap" rel="stylesheet">
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
      <a href="dashboard.php" title="ภาพรวม" class="<?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>"><?= icon('grid') ?><span>ภาพรวม</span></a>
      <a href="incidents.php" title="บันทึกรายการ" class="<?= ($activeNav ?? '') === 'incidents' ? 'active' : '' ?>"><?= icon('clipboard') ?><span>บันทึกรายการ</span></a>
      <a href="items.php" title="คลังของ/อะไหล่" class="<?= ($activeNav ?? '') === 'items' ? 'active' : '' ?>"><?= icon('archive') ?><span>คลังของ/อะไหล่</span></a>
      <a href="stock.php" title="เช็คสต็อก" class="<?= ($activeNav ?? '') === 'stock' ? 'active' : '' ?>"><?= icon('qr') ?><span>เช็คสต็อก</span></a>
      <a href="employees.php" title="พนักงาน" class="<?= ($activeNav ?? '') === 'employees' ? 'active' : '' ?>"><?= icon('users') ?><span>พนักงาน</span></a>
      <a href="reports.php" title="รายงานสรุป" class="<?= ($activeNav ?? '') === 'reports' ? 'active' : '' ?>"><?= icon('chart') ?><span>รายงานสรุป</span></a>
    </nav>
    <div class="sidebar-user">
      <button type="button" class="sidebar-install" id="installApp" title="ติดตั้งเป็นแอป"><?= icon('download', 'icon icon-sm') ?><span>ติดตั้งเป็นแอป</span></button>
      <div class="sidebar-user-info">
        <span class="avatar"><?= e($initial) ?></span>
        <div>
          <div class="sidebar-user-name"><?= e($user['full_name'] ?? '') ?></div>
          <div class="sidebar-user-role"><?= ($user['role'] ?? '') === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้จัดการ' ?></div>
        </div>
      </div>
      <a href="logout.php" class="logout-link" title="ออกจากระบบ"><?= icon('logout') ?><span>ออกจากระบบ</span></a>
      <div class="sidebar-collapse-row">
        <button type="button" class="sidebar-collapse" id="sidebarToggle" title="ย่อ/ขยายเมนู" aria-label="ย่อ/ขยายเมนู"><?= icon('chevrons-left', 'icon icon-sm') ?></button>
      </div>
    </div>
  </aside>
  <div class="main-area">
    <div class="offline-banner" id="offlineBanner"><?= icon('wifi-off', 'icon icon-sm') ?> ออฟไลน์อยู่ — ข้อมูลอาจไม่เป็นปัจจุบัน</div>
    <header class="topbar">
      <h1 class="page-title"><?= e($pageTitle ?? '') ?></h1>
      <div class="topbar-user">
        <button type="button" class="theme-toggle" id="themeToggle" aria-label="สลับโหมดมืด/สว่าง" title="สลับโหมดมืด/สว่าง">
          <span class="theme-icon theme-icon-sun"><?= icon('sun', 'icon icon-sm') ?></span>
          <span class="theme-icon theme-icon-moon"><?= icon('moon', 'icon icon-sm') ?></span>
        </button>
        <span class="avatar avatar-sm" title="<?= e($user['full_name'] ?? '') ?>"><?= e($initial) ?></span>
        <a href="logout.php" class="topbar-logout" title="ออกจากระบบ" aria-label="ออกจากระบบ"><?= icon('logout', 'icon icon-sm') ?></a>
      </div>
    </header>
    <main class="content">
<?php $flashSuccess = flash('success'); $flashError = flash('error'); ?>
<?php if ($flashSuccess): ?><div class="alert alert-success"><?= icon('check-circle') ?><span><?= e($flashSuccess) ?></span></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert-error"><?= icon('alert-circle') ?><span><?= e($flashError) ?></span></div><?php endif; ?>
