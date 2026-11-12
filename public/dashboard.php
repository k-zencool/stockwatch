<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

$totalEmployees = (int) $pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM incidents WHERE incident_date BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$monthCount = (int) $stmt->fetchColumn();

$totalCount = (int) $pdo->query("SELECT COUNT(*) FROM incidents")->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COALESCE(e.full_name, i.external_name) AS full_name, COUNT(i.id) AS cnt
     FROM incidents i LEFT JOIN employees e ON e.id = i.employee_id
     WHERE i.incident_date BETWEEN ? AND ?
     GROUP BY e.id, i.external_name, i.external_department
     ORDER BY cnt DESC LIMIT 1"
);
$stmt->execute([$monthStart, $monthEnd]);
$topThisMonth = $stmt->fetch();

$recent = $pdo->query(
    "SELECT i.*,
     COALESCE(e.full_name, i.external_name) AS display_name,
     COALESCE(e.department, i.external_department) AS display_department,
     (e.id IS NULL) AS is_external,
     c.name AS category_name,
     r.name AS reason_name, i.reason_text
     FROM incidents i
     LEFT JOIN employees e ON e.id = i.employee_id
     LEFT JOIN item_categories c ON c.id = i.category_id
     LEFT JOIN reasons r ON r.id = i.reason_id
     ORDER BY i.incident_date DESC, i.id DESC
     LIMIT 8"
)->fetchAll();

$pageTitle = 'ภาพรวม';
$activeNav = 'dashboard';
require __DIR__ . '/../src/views/header.php';
?>
<div class="cards-grid">
  <div class="stat-card">
    <div class="stat-card-top">
      <div class="stat-label">รายการไม่เบิกของเดือนนี้</div>
      <span class="stat-icon stat-icon-blue"><?= icon('inbox') ?></span>
    </div>
    <div class="stat-value"><?= $monthCount ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-card-top">
      <div class="stat-label">รายการทั้งหมด (สะสม)</div>
      <span class="stat-icon stat-icon-navy"><?= icon('box') ?></span>
    </div>
    <div class="stat-value"><?= $totalCount ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-card-top">
      <div class="stat-label">พนักงานที่ยังทำงานอยู่</div>
      <span class="stat-icon stat-icon-navy"><?= icon('users') ?></span>
    </div>
    <div class="stat-value"><?= $totalEmployees ?></div>
  </div>
  <div class="stat-card stat-card-accent">
    <div class="stat-card-top">
      <div class="stat-label">อันดับ 1 เดือนนี้</div>
      <span class="stat-icon stat-icon-danger"><?= icon('award') ?></span>
    </div>
    <div class="stat-value stat-value-sm"><?= $topThisMonth ? e($topThisMonth['full_name']) . ' (' . $topThisMonth['cnt'] . ')' : '-' ?></div>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <h2>รายการล่าสุด</h2>
    <a href="incidents.php" class="btn btn-ghost btn-sm">ดูทั้งหมด <?= icon('chevron-right', 'icon icon-sm') ?></a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>วันที่</th><th>พนักงาน</th><th>แผนก</th><th>ของที่ไม่เบิก</th><th>หมวดหมู่</th><th>กะพนักงาน</th><th>สาเหตุ</th></tr>
      </thead>
      <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="6" class="empty-row"><?= icon('inbox', 'icon icon-lg') ?><span>ยังไม่มีรายการบันทึก</span></td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $row): ?>
          <tr>
            <td><?= e(thai_date($row['incident_date'])) ?></td>
            <td><?= e($row['display_name']) ?><?php if ($row['is_external']): ?> <span class="badge badge-info"><?= icon('tag', 'icon icon-xs') ?>แผนกอื่น</span><?php endif; ?></td>
            <td><?= e($row['display_department'] ?? '-') ?></td>
            <td><?= e($row['item_name']) ?></td>
            <td><?= e($row['category_name'] ?? '-') ?></td>
            <td><?= e($row['reason_name'] ?? '-') ?></td>
            <td><?= e($row['reason_text'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
