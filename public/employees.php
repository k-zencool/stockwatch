<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $fullName = trim($_POST['full_name'] ?? '');
    $employeeCode = trim($_POST['employee_code'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');

    if ($fullName === '') {
        flash('error', 'กรุณากรอกชื่อพนักงาน');
        redirect('employees.php?open=add');
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO employees (employee_code, full_name, department, position) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$employeeCode ?: null, $fullName, $department ?: null, $position ?: null]);
        flash('success', 'เพิ่มพนักงานเรียบร้อยแล้ว');
    }
    redirect('employees.php');
}

$departments = $pdo->query(
    "SELECT DISTINCT department FROM employees
     WHERE department IS NOT NULL AND department <> '' ORDER BY department"
)->fetchAll(PDO::FETCH_COLUMN);

$filterDepartment = $_GET['department'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterKeyword = trim($_GET['q'] ?? '');

// Chips for the filters in effect; each links back to this view with that one filter dropped.
$activeFilters = [];
$removeFilterUrl = function (string $key): string {
    $q = $_GET;
    unset($q[$key], $q['page']);
    $qs = http_build_query($q);
    return 'employees.php' . ($qs ? '?' . $qs : '');
};
if ($filterDepartment !== '') {
    $activeFilters[] = ['label' => 'แผนก: ' . $filterDepartment, 'url' => $removeFilterUrl('department')];
}
if ($filterStatus !== '') {
    $activeFilters[] = [
        'label' => 'สถานะ: ' . ($filterStatus === 'inactive' ? 'พ้นสภาพ' : 'ทำงานอยู่'),
        'url' => $removeFilterUrl('status'),
    ];
}
if ($filterKeyword !== '') {
    $activeFilters[] = ['label' => 'คำค้น: ' . $filterKeyword, 'url' => $removeFilterUrl('q')];
}
$advancedCount = count($activeFilters) - ($filterKeyword !== '' ? 1 : 0);

$conditions = [];
$params = [];
if ($filterDepartment !== '') { $conditions[] = 'e.department = ?'; $params[] = $filterDepartment; }
if ($filterStatus !== '') { $conditions[] = 'e.is_active = ?'; $params[] = $filterStatus === 'inactive' ? 0 : 1; }
if ($filterKeyword !== '') {
    $conditions[] = '(e.full_name LIKE ? OR e.employee_code LIKE ? OR e.position LIKE ?)';
    $params[] = "%$filterKeyword%"; $params[] = "%$filterKeyword%"; $params[] = "%$filterKeyword%";
}
$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM employees e $where");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$sql = "SELECT e.*, (SELECT COUNT(*) FROM incidents i WHERE i.employee_id = e.id) AS incident_count
        FROM employees e
        $where
        ORDER BY e.is_active DESC, e.employee_code IS NULL, e.employee_code, e.full_name
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

$pageTitle = 'พนักงาน';
$activeNav = 'employees';
require __DIR__ . '/../src/views/header.php';
?>
<div class="page-toolbar">
  <button type="button" class="btn btn-primary" data-open-modal="addModal"><?= icon('plus', 'icon icon-sm') ?> เพิ่มพนักงานใหม่</button>
</div>

<div class="panel panel-slim">
  <form method="get" class="filter-bar" data-autosubmit>
    <div class="input-icon filter-search">
      <?= icon('search', 'icon icon-sm') ?>
      <input type="text" name="q" value="<?= e($filterKeyword) ?>" placeholder="ค้นหาชื่อ รหัสพนักงาน หรือตำแหน่ง แล้วกด Enter">
    </div>
    <button type="button" class="btn btn-ghost" data-menu="advancedFilters" aria-expanded="false">
      <?= icon('filter', 'icon icon-sm') ?> ตัวกรอง
      <?php if ($advancedCount > 0): ?><span class="badge badge-info"><?= $advancedCount ?></span><?php endif; ?>
    </button>

    <div class="filter-menu-panel" id="advancedFilters" hidden>
      <div class="filter-menu-head"><?= icon('filter', 'icon icon-sm') ?> กรองรายชื่อ</div>
      <label>แผนก
        <select name="department">
          <option value="">ทั้งหมด</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= e($dept) ?>" <?= $filterDepartment === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>สถานะ
        <select name="status">
          <option value="">ทั้งหมด</option>
          <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>ทำงานอยู่</option>
          <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>พ้นสภาพ</option>
        </select>
      </label>
    </div>
  </form>

  <?php if ($activeFilters): ?>
    <div class="filter-chips">
      <?php foreach ($activeFilters as $chip): ?>
        <a class="filter-chip" href="<?= e($chip['url']) ?>" title="เอาตัวกรองนี้ออก"><?= e($chip['label']) ?><?= icon('x', 'icon icon-xs') ?></a>
      <?php endforeach; ?>
      <a class="filter-chip filter-chip-clear" href="employees.php"><?= icon('x', 'icon icon-xs') ?> ล้างทั้งหมด</a>
    </div>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-header">
    <h2><?= icon('users', 'icon icon-sm') ?> รายชื่อพนักงานทั้งหมด (<?= $totalRows ?>)</h2>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>รหัส</th><th>ชื่อ-นามสกุล</th><th>แผนก</th><th>ตำแหน่ง</th><th>จำนวนครั้งที่ไม่เบิก</th><th>สถานะ</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$employees): ?>
          <tr><td colspan="7" class="empty-row"><?= icon('users', 'icon icon-lg') ?><span>ไม่พบพนักงาน</span></td></tr>
        <?php endif; ?>
        <?php foreach ($employees as $emp): ?>
          <tr class="<?= $emp['is_active'] ? '' : 'row-inactive' ?>">
            <td><?= e($emp['employee_code'] ?? '-') ?></td>
            <td><?= e($emp['full_name']) ?></td>
            <td><?= e($emp['department'] ?? '-') ?></td>
            <td><?= e($emp['position'] ?? '-') ?></td>
            <td><span class="badge <?= $emp['incident_count'] >= 3 ? 'badge-danger' : 'badge-neutral' ?>"><?= $emp['incident_count'] >= 3 ? icon('alert-triangle', 'icon icon-xs') : '' ?><?= $emp['incident_count'] ?></span></td>
            <td><?= $emp['is_active'] ? '<span class="badge badge-success">ทำงานอยู่</span>' : '<span class="badge badge-neutral">พ้นสภาพ</span>' ?></td>
            <td class="row-actions">
              <button type="button" class="link-edit" title="แก้ไข"
                data-edit-modal="editModal"
                data-id="<?= $emp['id'] ?>"
                data-active="<?= $emp['is_active'] ? '1' : '0' ?>"
                data-incidents="<?= $emp['incident_count'] ?>"
                data-field-employee_code="<?= e($emp['employee_code'] ?? '') ?>"
                data-field-full_name="<?= e($emp['full_name']) ?>"
                data-field-department="<?= e($emp['department'] ?? '') ?>"
                data-field-position="<?= e($emp['position'] ?? '') ?>"><?= icon('edit', 'icon icon-sm') ?></button>
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
        <a href="employees.php?<?= $qs ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<dialog id="addModal" class="modal">
  <div class="modal-head">
    <h2><?= icon('plus', 'icon icon-sm') ?> เพิ่มพนักงานใหม่</h2>
    <button type="button" class="modal-close" data-close-modal aria-label="ปิด"><?= icon('x', 'icon icon-sm') ?></button>
  </div>
  <div class="modal-body">
    <form method="post" class="grid-form">
      <input type="hidden" name="action" value="add">
      <label>รหัสพนักงาน
        <input type="text" name="employee_code" placeholder="เช่น EMP021">
      </label>
      <label>ชื่อ-นามสกุล
        <input type="text" name="full_name" required>
      </label>
      <label>แผนก
        <input type="text" name="department" placeholder="เช่น ทั่วไป">
      </label>
      <label>ตำแหน่ง
        <input type="text" name="position" placeholder="เช่น เจ้าหน้าที่">
      </label>
      <div class="grid-span-2">
        <button type="submit" class="btn btn-primary"><?= icon('plus', 'icon icon-sm') ?> เพิ่มพนักงาน</button>
      </div>
    </form>
  </div>
</dialog>

<dialog id="editModal" class="modal">
  <div class="modal-head">
    <h2><?= icon('edit', 'icon icon-sm') ?> แก้ไขข้อมูลพนักงาน</h2>
    <button type="button" class="modal-close" data-close-modal aria-label="ปิด"><?= icon('x', 'icon icon-sm') ?></button>
  </div>
  <div class="modal-body">
    <form method="post" action="employee_edit.php" class="grid-form">
      <input type="hidden" name="id" value="">
      <label>รหัสพนักงาน
        <input type="text" name="employee_code">
      </label>
      <label>ชื่อ-นามสกุล
        <input type="text" name="full_name" required>
      </label>
      <label>แผนก
        <input type="text" name="department">
      </label>
      <label>ตำแหน่ง
        <input type="text" name="position">
      </label>
      <div class="grid-span-2">
        <button type="submit" class="btn btn-primary"><?= icon('check-circle', 'icon icon-sm') ?> บันทึกการแก้ไข</button>
        <button type="button" class="btn btn-ghost" data-close-modal><?= icon('x', 'icon icon-sm') ?> ยกเลิก</button>
      </div>
    </form>

    <div class="modal-danger-zone">
      <form method="post" action="employee_edit.php">
        <input type="hidden" name="id" value="">
        <input type="hidden" name="action" value="toggle_active">
        <button type="submit" class="btn btn-danger-outline" data-toggle-btn
          data-label-off="ปิดใช้งาน (พ้นสภาพ)" data-label-on="เปิดใช้งานอีกครั้ง">ปิดใช้งาน (พ้นสภาพ)</button>
      </form>
      <form method="post" action="employee_edit.php"
        onsubmit="return confirm('ยืนยันลบพนักงานคนนี้? การกระทำนี้ย้อนกลับไม่ได้');">
        <input type="hidden" name="id" value="">
        <input type="hidden" name="action" value="delete">
        <button type="submit" class="btn btn-danger-outline"><?= icon('trash', 'icon icon-sm') ?> ลบพนักงาน</button>
      </form>
      <p class="field-hint">
        พนักงานคนนี้มีประวัติถูกบันทึกไม่เบิกอยู่ <strong data-incident-count>0</strong> รายการ —
        ถ้าลบ ประวัติจะยังอยู่ครบ โดยระบบจะเก็บชื่อไว้เป็นข้อความแทน
        (ถ้าแค่ต้องการซ่อนจากรายการเลือก ให้ใช้ "ปิดใช้งาน" จะเหมาะกว่า)
      </p>
    </div>
  </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function () {
  initModals();
  initRowEditModals();
  <?php if (($_GET['open'] ?? '') === 'add'): ?>
  document.getElementById('addModal').showModal();
  <?php elseif (($_GET['open'] ?? '') === 'edit' && ($_GET['id'] ?? '') !== ''): ?>
  var editBtn = document.querySelector('[data-edit-modal="editModal"][data-id="<?= (int) $_GET['id'] ?>"]');
  if (editBtn) editBtn.click();
  <?php endif; ?>
});
</script>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
