<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_reason') {
    $reasonName = trim($_POST['reason_name'] ?? '');

    if ($reasonName === '') {
        flash('error', 'กรุณากรอกชื่อสาเหตุ');
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reasons WHERE LOWER(name) = LOWER(?)');
        $stmt->execute([$reasonName]);
        if ((int) $stmt->fetchColumn() > 0) {
            flash('error', 'มีสาเหตุนี้อยู่แล้ว');
        } else {
            $stmt = $pdo->prepare('INSERT INTO reasons (name) VALUES (?)');
            $stmt->execute([$reasonName]);
            flash('success', 'เพิ่มสาเหตุเรียบร้อยแล้ว');
        }
    }
    redirect('incidents.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $employeeChoice = $_POST['employee_choice'] ?? '';
    $employeeId = null;
    $externalName = null;
    $externalDepartment = null;

    if ($employeeChoice === 'other') {
        $externalName = trim($_POST['external_name'] ?? '');
        $externalDepartment = trim($_POST['external_department'] ?? '');
    } elseif (strpos($employeeChoice, 'emp:') === 0) {
        $employeeId = (int) substr($employeeChoice, 4);
    }

    $itemId = ($_POST['item_id'] ?? '') !== '' ? (int) $_POST['item_id'] : null;
    $reasonId = ($_POST['reason_id'] ?? '') !== '' ? (int) $_POST['reason_id'] : null;
    $itemName = trim($_POST['item_name'] ?? '');
    $incidentDate = $_POST['incident_date'] ?? '';
    $note = trim($_POST['note'] ?? '');

    $hasEmployee = ($employeeId !== null && $employeeId > 0) || ($externalName !== null && $externalName !== '');

    if (!$hasEmployee || $itemName === '' || $incidentDate === '' || !$reasonId) {
        flash('error', 'กรุณาเลือก/กรอกพนักงาน, รายการของ, สาเหตุ และวันที่ให้ครบถ้วน');
    } else {
        $categoryId = null;
        if ($itemId) {
            $stmt = $pdo->prepare('SELECT category_id FROM items WHERE id = ?');
            $stmt->execute([$itemId]);
            $itemCategory = $stmt->fetchColumn();
            $categoryId = $itemCategory ? (int) $itemCategory : null;
        }
        $stmt = $pdo->prepare(
            "INSERT INTO incidents (employee_id, external_name, external_department, item_id, category_id, reason_id, item_name, incident_date, note, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $employeeId, $externalName ?: null, $externalDepartment ?: null,
            $itemId, $categoryId, $reasonId, $itemName, $incidentDate, $note ?: null, current_user()['id'],
        ]);
        flash('success', 'บันทึกรายการเรียบร้อยแล้ว');
    }
    redirect('incidents.php');
}

$employees = $pdo->query("SELECT id, full_name, department FROM employees WHERE is_active = 1 ORDER BY full_name")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM item_categories ORDER BY name")->fetchAll();
$reasons = $pdo->query(
    "SELECT r.id, r.name, (SELECT COUNT(*) FROM incidents inc WHERE inc.reason_id = r.id) AS usage_count
     FROM reasons r ORDER BY r.name"
)->fetchAll();
$itemsForJs = $pdo->query(
    "SELECT it.id, it.name, it.code, it.category_id, c.name AS category_name
     FROM items it LEFT JOIN item_categories c ON c.id = it.category_id
     WHERE it.is_active = 1 ORDER BY it.name"
)->fetchAll();

$filterEmployee = $_GET['employee_id'] ?? '';
$filterCategory = $_GET['category_id'] ?? '';
$filterReason = $_GET['reason_id'] ?? '';
$filterFrom = $_GET['date_from'] ?? '';
$filterTo = $_GET['date_to'] ?? '';
$filterKeyword = trim($_GET['q'] ?? '');

$conditions = [];
$params = [];
if ($filterEmployee !== '') { $conditions[] = 'i.employee_id = ?'; $params[] = (int) $filterEmployee; }
if ($filterCategory !== '') { $conditions[] = 'i.category_id = ?'; $params[] = (int) $filterCategory; }
if ($filterReason !== '') { $conditions[] = 'i.reason_id = ?'; $params[] = (int) $filterReason; }
if ($filterFrom !== '') { $conditions[] = 'i.incident_date >= ?'; $params[] = $filterFrom; }
if ($filterTo !== '') { $conditions[] = 'i.incident_date <= ?'; $params[] = $filterTo; }
if ($filterKeyword !== '') {
    $conditions[] = '(i.item_name LIKE ? OR e.full_name LIKE ? OR i.external_name LIKE ?)';
    $params[] = "%$filterKeyword%"; $params[] = "%$filterKeyword%"; $params[] = "%$filterKeyword%";
}

$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM incidents i LEFT JOIN employees e ON e.id = i.employee_id $where");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$sql = "SELECT i.*,
        COALESCE(e.full_name, i.external_name) AS display_name,
        COALESCE(e.department, i.external_department) AS display_department,
        (e.id IS NULL) AS is_external,
        c.name AS category_name,
        r.name AS reason_name
        FROM incidents i
        LEFT JOIN employees e ON e.id = i.employee_id
        LEFT JOIN item_categories c ON c.id = i.category_id
        LEFT JOIN reasons r ON r.id = i.reason_id
        $where
        ORDER BY i.incident_date DESC, i.id DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'บันทึกรายการ';
$activeNav = 'incidents';
require __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div class="panel-header"><h2><?= icon('help-circle', 'icon icon-sm') ?> จัดการสาเหตุการไม่เบิก</h2></div>
  <form method="post" class="filter-form" style="margin-bottom: 16px;">
    <input type="hidden" name="action" value="add_reason">
    <div class="input-icon input-icon-inline"><?= icon('help-circle', 'icon icon-sm') ?><input type="text" name="reason_name" placeholder="สาเหตุใหม่ เช่น ลืมเบิก, รีบใช้งาน" required></div>
    <button type="submit" class="btn btn-primary"><?= icon('plus', 'icon icon-sm') ?> เพิ่มสาเหตุ</button>
  </form>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>สาเหตุ</th><th>จำนวนครั้งที่ถูกใช้</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$reasons): ?>
          <tr><td colspan="3" class="empty-row"><?= icon('help-circle', 'icon icon-lg') ?><span>ยังไม่มีสาเหตุ</span></td></tr>
        <?php endif; ?>
        <?php foreach ($reasons as $reason): ?>
          <tr>
            <td><?= e($reason['name']) ?></td>
            <td><span class="badge badge-neutral"><?= $reason['usage_count'] ?></span></td>
            <td class="row-actions">
              <a href="reason_edit.php?id=<?= $reason['id'] ?>" class="link-edit" title="แก้ไข/ลบ"><?= icon('edit', 'icon icon-sm') ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><h2><?= icon('plus', 'icon icon-sm') ?> เพิ่มบันทึกใหม่</h2></div>
  <form method="post" class="grid-form">
    <input type="hidden" name="action" value="add">
    <label>พนักงาน
      <select name="employee_choice" id="employeeSelect" required>
        <option value="">-- เลือกพนักงาน --</option>
        <?php foreach ($employees as $emp): ?>
          <option value="emp:<?= $emp['id'] ?>"><?= e($emp['full_name']) ?> (<?= e($emp['department']) ?>)</option>
        <?php endforeach; ?>
        <option value="other">+ พนักงานจากแผนกอื่น (ระบุชื่อเอง)</option>
      </select>
    </label>
    <label>วันที่
      <input type="date" name="incident_date" value="<?= date('Y-m-d') ?>" required>
    </label>

    <div class="grid-span-2 external-employee-box" id="externalEmployeeBox">
      <div class="grid-form">
        <label>ชื่อ-นามสกุล (แผนกอื่น)
          <input type="text" name="external_name" placeholder="ชื่อ-นามสกุล">
        </label>
        <label>แผนกต้นสังกัด
          <input type="text" name="external_department" placeholder="เช่น แผนกบัญชี">
        </label>
      </div>
    </div>

    <label class="grid-span-2">รายการของที่ไม่เบิก (ค้นหาในคลัง หรือพิมพ์ชื่อใหม่)
      <div class="combobox" id="itemCombobox">
        <input type="hidden" name="item_id" class="combobox-value" value="">
        <input type="hidden" name="category_id" class="combobox-category-value" value="">
        <div class="input-icon">
          <?= icon('search', 'icon icon-sm') ?>
          <input type="text" name="item_name" class="combobox-search" autocomplete="off" placeholder="พิมพ์ค้นหา เช่น ปากกา, ไขควง" required>
        </div>
        <div class="combobox-menu"></div>
        <span class="combobox-selected-tag"><?= icon('tag', 'icon icon-xs') ?> <span class="combobox-category-text"></span></span>
      </div>
      <p class="field-hint">หมวดหมู่จะยึดตามข้อมูลของชิ้นนั้นในคลังโดยอัตโนมัติ — ถ้าพิมพ์ชื่อใหม่ที่ยังไม่มีในคลัง จะไม่ถูกระบุหมวดหมู่ (ไปเพิ่มของชิ้นนั้นในคลังได้ที่หน้า "คลังของ/อะไหล่")</p>
    </label>
    <label class="grid-span-2">สาเหตุที่ไม่เบิก
      <select name="reason_id" required>
        <option value="">-- เลือกสาเหตุ --</option>
        <?php foreach ($reasons as $reason): ?>
          <option value="<?= $reason['id'] ?>"><?= e($reason['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="field-hint">ไม่เจอสาเหตุที่ต้องการ? เพิ่มใหม่ได้จากช่อง "จัดการสาเหตุการไม่เบิก" ด้านบน</p>
    </label>
    <label class="grid-span-2">หมายเหตุ
      <input type="text" name="note" placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)">
    </label>
    <div class="grid-span-2">
      <button type="submit" class="btn btn-primary"><?= icon('plus', 'icon icon-sm') ?> บันทึกรายการ</button>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-header"><h2><?= icon('filter', 'icon icon-sm') ?> ค้นหา / กรองรายการ</h2></div>
  <form method="get" class="filter-form">
    <select name="employee_id">
      <option value="">พนักงานทั้งหมด</option>
      <?php foreach ($employees as $emp): ?>
        <option value="<?= $emp['id'] ?>" <?= $filterEmployee == $emp['id'] ? 'selected' : '' ?>><?= e($emp['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="category_id">
      <option value="">หมวดหมู่ทั้งหมด</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="reason_id">
      <option value="">สาเหตุทั้งหมด</option>
      <?php foreach ($reasons as $reason): ?>
        <option value="<?= $reason['id'] ?>" <?= $filterReason == $reason['id'] ? 'selected' : '' ?>><?= e($reason['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?= e($filterFrom) ?>">
    <input type="date" name="date_to" value="<?= e($filterTo) ?>">
    <div class="input-icon input-icon-inline"><?= icon('search', 'icon icon-sm') ?><input type="text" name="q" value="<?= e($filterKeyword) ?>" placeholder="ค้นหาชื่อ/รายการ"></div>
    <button type="submit" class="btn btn-secondary"><?= icon('filter', 'icon icon-sm') ?> กรอง</button>
    <a href="incidents.php" class="btn btn-ghost"><?= icon('x', 'icon icon-sm') ?> ล้าง</a>
  </form>
</div>

<div class="panel">
  <div class="panel-header">
    <h2><?= icon('clipboard', 'icon icon-sm') ?> รายการทั้งหมด (<?= $totalRows ?>)</h2>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>วันที่</th><th>พนักงาน</th><th>แผนก</th><th>รายการ</th><th>หมวดหมู่</th><th>สาเหตุ</th><th>หมายเหตุ</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" class="empty-row"><?= icon('inbox', 'icon icon-lg') ?><span>ไม่พบรายการ</span></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= e(thai_date($row['incident_date'])) ?></td>
            <td><?= e($row['display_name']) ?><?php if ($row['is_external']): ?> <span class="badge badge-info"><?= icon('tag', 'icon icon-xs') ?>แผนกอื่น</span><?php endif; ?></td>
            <td><?= e($row['display_department'] ?? '-') ?></td>
            <td><?= e($row['item_name']) ?></td>
            <td><?= e($row['category_name'] ?? '-') ?></td>
            <td><?= e($row['reason_name'] ?? '-') ?></td>
            <td><?= e($row['note'] ?? '') ?></td>
            <td class="row-actions">
              <a href="incident_edit.php?id=<?= $row['id'] ?>" class="link-edit" title="แก้ไข"><?= icon('edit', 'icon icon-sm') ?></a>
              <form method="post" action="incident_delete.php" onsubmit="return confirm('ยืนยันลบรายการนี้?');" style="display:inline">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <button type="submit" class="link-delete" title="ลบ"><?= icon('trash', 'icon icon-sm') ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php
      $queryWithoutPage = $_GET; unset($queryWithoutPage['page']);
      for ($p = 1; $p <= $totalPages; $p++):
          $qs = http_build_query(array_merge($queryWithoutPage, ['page' => $p]));
      ?>
        <a href="incidents.php?<?= $qs ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var ITEMS = <?= json_encode($itemsForJs, JSON_UNESCAPED_UNICODE) ?>;
  initItemCombobox(document.getElementById('itemCombobox'), ITEMS);
  initEmployeeToggle('employeeSelect', 'externalEmployeeBox');
});
</script>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
