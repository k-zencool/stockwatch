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
     ORDER BY cnt DESC LIMIT 10"
);
$stmt->execute([$dateFrom, $dateTo]);
$topOffenders = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(incident_date, '%Y-%m-%d') AS d, COUNT(*) AS cnt
     FROM incidents WHERE incident_date BETWEEN ? AND ?
     GROUP BY d ORDER BY d"
);
$stmt->execute([$dateFrom, $dateTo]);
$byDate = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT COALESCE(c.name, 'ไม่ระบุหมวดหมู่') AS name, COUNT(*) AS cnt
     FROM incidents i LEFT JOIN item_categories c ON c.id = i.category_id
     WHERE i.incident_date BETWEEN ? AND ?
     GROUP BY c.id ORDER BY cnt DESC"
);
$stmt->execute([$dateFrom, $dateTo]);
$byCategory = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT COALESCE(r.name, 'ไม่ระบุสาเหตุ') AS name, COUNT(*) AS cnt
     FROM incidents i LEFT JOIN reasons r ON r.id = i.reason_id
     WHERE i.incident_date BETWEEN ? AND ?
     GROUP BY r.id ORDER BY cnt DESC"
);
$stmt->execute([$dateFrom, $dateTo]);
$byReason = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM incidents WHERE incident_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo]);
$totalInRange = (int) $stmt->fetchColumn();

$exportQuery = http_build_query(['date_from' => $dateFrom, 'date_to' => $dateTo]);

$pageTitle = 'รายงานสรุป';
$activeNav = 'reports';
require __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div class="panel-header"><h2><?= icon('filter', 'icon icon-sm') ?> เลือกช่วงเวลา</h2></div>
  <form method="get" class="filter-form">
    <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
    <span class="filter-sep">ถึง</span>
    <input type="date" name="date_to" value="<?= e($dateTo) ?>">
    <button type="submit" class="btn btn-secondary">แสดงรายงาน</button>
    <a href="export_csv.php?<?= $exportQuery ?>" class="btn btn-ghost"><?= icon('download', 'icon icon-sm') ?> Export CSV/Excel</a>
    <a href="report_print.php?<?= $exportQuery ?>" target="_blank" class="btn btn-ghost"><?= icon('printer', 'icon icon-sm') ?> Export PDF</a>
  </form>
</div>

<div class="cards-grid">
  <div class="stat-card">
    <div class="stat-card-top">
      <div class="stat-label">จำนวนรายการในช่วงที่เลือก</div>
      <span class="stat-icon stat-icon-blue"><?= icon('inbox') ?></span>
    </div>
    <div class="stat-value"><?= $totalInRange ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-card-top">
      <div class="stat-label">อันดับ 1</div>
      <span class="stat-icon stat-icon-danger"><?= icon('award') ?></span>
    </div>
    <div class="stat-value stat-value-sm"><?= $topOffenders ? e($topOffenders[0]['full_name']) . ' (' . $topOffenders[0]['cnt'] . ')' : '-' ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-card-top">
      <div class="stat-label">หมวดหมู่ที่หายบ่อยสุด</div>
      <span class="stat-icon stat-icon-navy"><?= icon('box') ?></span>
    </div>
    <div class="stat-value stat-value-sm"><?= $byCategory ? e($byCategory[0]['name']) . ' (' . $byCategory[0]['cnt'] . ')' : '-' ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-card-top">
      <div class="stat-label">สาเหตุที่พบบ่อยสุด</div>
      <span class="stat-icon stat-icon-blue"><?= icon('help-circle') ?></span>
    </div>
    <div class="stat-value stat-value-sm"><?= $byReason ? e($byReason[0]['name']) . ' (' . $byReason[0]['cnt'] . ')' : '-' ?></div>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><h2><?= icon('chart', 'icon icon-sm') ?> แนวโน้มรายวัน</h2></div>
  <canvas id="trendChart" height="90"></canvas>
</div>

<div class="panel">
  <div class="panel-header"><h2><?= icon('award', 'icon icon-sm') ?> อันดับพนักงานที่ไม่เบิกของบ่อยที่สุด</h2></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>ชื่อ</th><th>แผนก</th><th>จำนวนครั้ง</th></tr></thead>
      <tbody>
        <?php if (!$topOffenders): ?>
          <tr><td colspan="4" class="empty-row"><?= icon('inbox', 'icon icon-lg') ?><span>ไม่มีข้อมูลในช่วงนี้</span></td></tr>
        <?php endif; ?>
        <?php foreach ($topOffenders as $i => $row): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= e($row['full_name']) ?><?php if ($row['is_external']): ?> <span class="badge badge-info"><?= icon('tag', 'icon icon-xs') ?>แผนกอื่น</span><?php endif; ?></td>
            <td><?= e($row['department'] ?? '-') ?></td>
            <td><span class="badge badge-danger"><?= icon('alert-triangle', 'icon icon-xs') ?><?= $row['cnt'] ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="two-col">
  <div class="panel">
    <div class="panel-header"><h2><?= icon('box', 'icon icon-sm') ?> สรุปตามประเภทของ</h2></div>
    <canvas id="categoryChart" height="220"></canvas>
  </div>
  <div class="panel">
    <div class="panel-header"><h2><?= icon('help-circle', 'icon icon-sm') ?> สรุปตามสาเหตุที่ไม่เบิก</h2></div>
    <canvas id="reasonChart" height="220"></canvas>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const trendData = <?= json_encode($byDate) ?>;
  const catData = <?= json_encode($byCategory) ?>;
  const reasonData = <?= json_encode($byReason, JSON_UNESCAPED_UNICODE) ?>;

  const rootStyle = getComputedStyle(document.documentElement);
  const cssVar = (name) => rootStyle.getPropertyValue(name).trim();
  const textColor = cssVar('--text') || '#1e1b33';
  const mutedColor = cssVar('--muted') || '#6b7280';
  const gridColor = cssVar('--border') || '#e6e7f2';

  Chart.defaults.color = mutedColor;
  Chart.defaults.font.family = "'Prompt', sans-serif";

  new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
      labels: trendData.map(r => r.d),
      datasets: [{ label: 'จำนวนรายการ', data: trendData.map(r => r.cnt), backgroundColor: '#6366f1', borderRadius: 6 }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0, color: mutedColor }, grid: { color: gridColor } },
        x: { ticks: { color: mutedColor }, grid: { display: false } }
      }
    }
  });

  new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
      labels: catData.map(r => r.name),
      datasets: [{ data: catData.map(r => r.cnt), backgroundColor: ['#4f46e5', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#94a3b8'] }]
    },
    options: { responsive: true, plugins: { legend: { labels: { color: textColor } } } }
  });

  new Chart(document.getElementById('reasonChart'), {
    type: 'bar',
    data: {
      labels: reasonData.map(r => r.name),
      datasets: [{ label: 'จำนวนครั้ง', data: reasonData.map(r => r.cnt), backgroundColor: '#ec4899', borderRadius: 6 }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { beginAtZero: true, ticks: { precision: 0, color: mutedColor }, grid: { color: gridColor } },
        y: { ticks: { color: textColor }, grid: { display: false } }
      }
    }
  });
});
</script>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
