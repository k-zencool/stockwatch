<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$stmt = $pdo->prepare(
    "SELECT COALESCE(e.full_name, i.external_name) AS full_name,
     COALESCE(e.department, i.external_department) AS department,
     (e.id IS NULL) AS is_external,
     COUNT(i.id) AS cnt
     FROM incidents i LEFT JOIN employees e ON e.id = i.employee_id
     WHERE i.incident_date BETWEEN ? AND ?
     GROUP BY e.id, i.external_name, i.external_department
     ORDER BY cnt DESC"
);
$stmt->execute([$dateFrom, $dateTo]);
$offenders = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT i.incident_date,
     COALESCE(e.full_name, i.external_name) AS display_name,
     COALESCE(e.department, i.external_department) AS display_department,
     (e.id IS NULL) AS is_external,
     i.item_name, c.name AS category_name, r.name AS reason_name, i.note
     FROM incidents i LEFT JOIN employees e ON e.id = i.employee_id
     LEFT JOIN item_categories c ON c.id = i.category_id
     LEFT JOIN reasons r ON r.id = i.reason_id
     WHERE i.incident_date BETWEEN ? AND ?
     ORDER BY i.incident_date DESC"
);
$stmt->execute([$dateFrom, $dateTo]);
$detail = $stmt->fetchAll();

$generatedAt = date('d/m/Y H:i');
?>
<!doctype html>
<html lang="th" data-theme="light">
<head>
<meta charset="UTF-8">
<title>รายงานสรุป <?= e($dateFrom) ?> ถึง <?= e($dateTo) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="print-body">
<div class="print-toolbar no-print">
  <button onclick="window.print()" class="btn btn-primary"><?= icon('printer', 'icon icon-sm') ?> พิมพ์ / บันทึกเป็น PDF</button>
</div>
<div class="print-sheet">
  <h1>รายงานสรุปการไม่เบิกของ</h1>
  <p>ช่วงวันที่: <?= e(thai_date($dateFrom)) ?> ถึง <?= e(thai_date($dateTo)) ?></p>
  <p class="print-meta">พิมพ์เมื่อ: <?= e($generatedAt) ?></p>

  <h2>อันดับพนักงาน</h2>
  <table class="print-table">
    <thead><tr><th>#</th><th>ชื่อ</th><th>ประเภท</th><th>แผนก</th><th>จำนวนครั้ง</th></tr></thead>
    <tbody>
      <?php foreach ($offenders as $i => $row): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($row['full_name']) ?></td>
          <td><?= $row['is_external'] ? 'แผนกอื่น' : 'พนักงานภายใน' ?></td>
          <td><?= e($row['department'] ?? '-') ?></td>
          <td><?= $row['cnt'] ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$offenders): ?><tr><td colspan="5">ไม่มีข้อมูล</td></tr><?php endif; ?>
    </tbody>
  </table>

  <h2>รายละเอียดทั้งหมด</h2>
  <table class="print-table">
    <thead><tr><th>วันที่</th><th>ชื่อ</th><th>ประเภท</th><th>แผนก</th><th>รายการ</th><th>หมวดหมู่</th><th>สาเหตุ</th><th>หมายเหตุ</th></tr></thead>
    <tbody>
      <?php foreach ($detail as $row): ?>
        <tr>
          <td><?= e(thai_date($row['incident_date'])) ?></td>
          <td><?= e($row['display_name']) ?></td>
          <td><?= $row['is_external'] ? 'แผนกอื่น' : 'พนักงานภายใน' ?></td>
          <td><?= e($row['display_department'] ?? '-') ?></td>
          <td><?= e($row['item_name']) ?></td>
          <td><?= e($row['category_name'] ?? '-') ?></td>
          <td><?= e($row['reason_name'] ?? '-') ?></td>
          <td><?= e($row['note'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$detail): ?><tr><td colspan="8">ไม่มีข้อมูล</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
