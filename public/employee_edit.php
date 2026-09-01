<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();
if (!$employee) {
    flash('error', 'ไม่พบพนักงานที่ต้องการแก้ไข');
    redirect('employees.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'toggle_active') {
        $stmt = $pdo->prepare("UPDATE employees SET is_active = 1 - is_active WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'เปลี่ยนสถานะพนักงานเรียบร้อยแล้ว');
        redirect('employees.php');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $employeeCode = trim($_POST['employee_code'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');

    if ($fullName === '') {
        flash('error', 'กรุณากรอกชื่อพนักงาน');
    } else {
        $stmt = $pdo->prepare(
            "UPDATE employees SET employee_code=?, full_name=?, department=?, position=? WHERE id=?"
        );
        $stmt->execute([$employeeCode ?: null, $fullName, $department ?: null, $position ?: null, $id]);
        flash('success', 'บันทึกข้อมูลพนักงานเรียบร้อยแล้ว');
        redirect('employees.php');
    }
}

$pageTitle = 'แก้ไขพนักงาน';
$activeNav = 'employees';
require __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div class="panel-header"><h2><?= icon('edit', 'icon icon-sm') ?> แก้ไขข้อมูลพนักงาน</h2></div>
  <form method="post" class="grid-form">
    <label>รหัสพนักงาน
      <input type="text" name="employee_code" value="<?= e($employee['employee_code'] ?? '') ?>">
    </label>
    <label>ชื่อ-นามสกุล
      <input type="text" name="full_name" value="<?= e($employee['full_name']) ?>" required>
    </label>
    <label>แผนก
      <input type="text" name="department" value="<?= e($employee['department'] ?? '') ?>">
    </label>
    <label>ตำแหน่ง
      <input type="text" name="position" value="<?= e($employee['position'] ?? '') ?>">
    </label>
    <div class="grid-span-2">
      <button type="submit" class="btn btn-primary"><?= icon('check-circle', 'icon icon-sm') ?> บันทึกการแก้ไข</button>
      <a href="employees.php" class="btn btn-ghost"><?= icon('x', 'icon icon-sm') ?> ยกเลิก</a>
    </div>
  </form>
  <form method="post" class="inline-toggle">
    <input type="hidden" name="action" value="toggle_active">
    <button type="submit" class="btn <?= $employee['is_active'] ? 'btn-danger-outline' : 'btn-secondary' ?>">
      <?= $employee['is_active'] ? icon('x', 'icon icon-sm') : icon('check-circle', 'icon icon-sm') ?>
      <?= $employee['is_active'] ? 'ปิดใช้งาน (พ้นสภาพ)' : 'เปิดใช้งานอีกครั้ง' ?>
    </button>
  </form>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
