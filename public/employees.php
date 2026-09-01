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
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO employees (employee_code, full_name, department, position) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$employeeCode ?: null, $fullName, $department ?: null, $position ?: null]);
        flash('success', 'เพิ่มพนักงานเรียบร้อยแล้ว');
    }
    redirect('employees.php');
}

$employees = $pdo->query(
    "SELECT e.*, (SELECT COUNT(*) FROM incidents i WHERE i.employee_id = e.id) AS incident_count
     FROM employees e ORDER BY e.is_active DESC, e.full_name"
)->fetchAll();

$pageTitle = 'พนักงาน';
$activeNav = 'employees';
require __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div class="panel-header"><h2><?= icon('plus', 'icon icon-sm') ?> เพิ่มพนักงานใหม่</h2></div>
  <form method="post" class="grid-form">
    <input type="hidden" name="action" value="add">
    <label>รหัสพนักงาน
      <input type="text" name="employee_code" placeholder="เช่น EMP021">
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
      <button type="submit" class="btn btn-primary"><?= icon('plus', 'icon icon-sm') ?> เพิ่มพนักงาน</button>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-header"><h2><?= icon('users', 'icon icon-sm') ?> รายชื่อพนักงานทั้งหมด (<?= count($employees) ?>)</h2></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>รหัส</th><th>ชื่อ-นามสกุล</th><th>แผนก</th><th>ตำแหน่ง</th><th>จำนวนครั้งที่ไม่เบิก</th><th>สถานะ</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($employees as $emp): ?>
          <tr class="<?= $emp['is_active'] ? '' : 'row-inactive' ?>">
            <td><?= e($emp['employee_code'] ?? '-') ?></td>
            <td><?= e($emp['full_name']) ?></td>
            <td><?= e($emp['department'] ?? '-') ?></td>
            <td><?= e($emp['position'] ?? '-') ?></td>
            <td><span class="badge <?= $emp['incident_count'] >= 3 ? 'badge-danger' : 'badge-neutral' ?>"><?= $emp['incident_count'] >= 3 ? icon('alert-triangle', 'icon icon-xs') : '' ?><?= $emp['incident_count'] ?></span></td>
            <td><?= $emp['is_active'] ? '<span class="badge badge-success">ทำงานอยู่</span>' : '<span class="badge badge-neutral">พ้นสภาพ</span>' ?></td>
            <td class="row-actions">
              <a href="employee_edit.php?id=<?= $emp['id'] ?>" class="link-edit" title="แก้ไข"><?= icon('edit', 'icon icon-sm') ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
