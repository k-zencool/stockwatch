<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM incidents WHERE id = ?");
$stmt->execute([$id]);
$incident = $stmt->fetch();
if (!$incident) {
    flash('error', 'ไม่พบรายการที่ต้องการแก้ไข');
    redirect('incidents.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            "UPDATE incidents SET employee_id=?, external_name=?, external_department=?, item_id=?, category_id=?, reason_id=?, item_name=?, incident_date=?, note=? WHERE id=?"
        );
        $stmt->execute([
            $employeeId, $externalName ?: null, $externalDepartment ?: null,
            $itemId, $categoryId, $reasonId, $itemName, $incidentDate, $note ?: null, $id,
        ]);
        flash('success', 'แก้ไขรายการเรียบร้อยแล้ว');
        redirect('incidents.php');
    }
}

$employees = $pdo->query(
    "SELECT id, full_name, department FROM employees
     ORDER BY employee_code IS NULL, employee_code, full_name"
)->fetchAll();
$categories = $pdo->query("SELECT id, name FROM item_categories ORDER BY name")->fetchAll();
$reasons = $pdo->query("SELECT id, name FROM reasons ORDER BY name")->fetchAll();
$itemsForJs = $pdo->query(
    "SELECT it.id, it.name, it.code, it.category_id, c.name AS category_name
     FROM items it LEFT JOIN item_categories c ON c.id = it.category_id
     WHERE it.is_active = 1 ORDER BY it.name"
)->fetchAll();

$currentChoice = $incident['employee_id'] ? 'emp:' . $incident['employee_id'] : 'other';
$currentEmployeeLabel = '';
foreach ($employees as $emp) {
    if ($emp['id'] == $incident['employee_id']) {
        $currentEmployeeLabel = $emp['full_name'] . ($emp['department'] ? ' (' . $emp['department'] . ')' : '');
        break;
    }
}
$currentCategoryName = '';
foreach ($categories as $cat) {
    if ($cat['id'] == $incident['category_id']) {
        $currentCategoryName = $cat['name'];
        break;
    }
}

$pageTitle = 'แก้ไขบันทึก';
$activeNav = 'incidents';
require __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div class="panel-header"><h2><?= icon('edit', 'icon icon-sm') ?> แก้ไขรายการ</h2></div>
  <form method="post" class="grid-form">
    <label>พนักงาน
      <div class="combobox" id="employeeCombobox">
        <input type="hidden" name="employee_choice" class="combobox-value" value="<?= e($currentChoice) ?>">
        <div class="input-icon">
          <?= icon('user', 'icon icon-sm') ?>
          <input type="text" class="combobox-search" autocomplete="off" value="<?= e($currentEmployeeLabel) ?>" placeholder="พิมพ์ค้นหาชื่อพนักงาน">
        </div>
        <div class="combobox-menu"></div>
      </div>
    </label>
    <label>วันที่
      <input type="date" name="incident_date" value="<?= e($incident['incident_date']) ?>" required>
    </label>

    <div class="grid-span-2 external-employee-box" id="externalEmployeeBox">
      <div class="grid-form">
        <label>ชื่อ-นามสกุล (แผนกอื่น)
          <input type="text" name="external_name" value="<?= e($incident['external_name'] ?? '') ?>" placeholder="ชื่อ-นามสกุล">
        </label>
        <label>แผนกต้นสังกัด
          <input type="text" name="external_department" value="<?= e($incident['external_department'] ?? '') ?>" placeholder="เช่น แผนกบัญชี">
        </label>
      </div>
    </div>

    <label class="grid-span-2">รายการของที่ไม่เบิก (ค้นหาในคลัง หรือพิมพ์ชื่อใหม่)
      <div class="combobox" id="itemCombobox">
        <input type="hidden" name="item_id" class="combobox-value" value="<?= e((string) ($incident['item_id'] ?? '')) ?>">
        <input type="hidden" name="category_id" class="combobox-category-value" value="<?= e((string) ($incident['category_id'] ?? '')) ?>">
        <div class="input-icon">
          <?= icon('search', 'icon icon-sm') ?>
          <input type="text" name="item_name" class="combobox-search" value="<?= e($incident['item_name']) ?>" autocomplete="off" required>
        </div>
        <div class="combobox-menu"></div>
        <span class="combobox-selected-tag <?= $currentCategoryName ? 'show' : '' ?>"><?= icon('tag', 'icon icon-xs') ?> <span class="combobox-category-text"><?= e($currentCategoryName) ?></span></span>
      </div>
      <p class="field-hint">หมวดหมู่จะยึดตามข้อมูลของชิ้นนั้นในคลังโดยอัตโนมัติ — ถ้าพิมพ์ชื่อใหม่ที่ยังไม่มีในคลัง จะไม่ถูกระบุหมวดหมู่</p>
    </label>
    <label class="grid-span-2">สาเหตุที่ไม่เบิก
      <select name="reason_id" required>
        <option value="">-- เลือกสาเหตุ --</option>
        <?php foreach ($reasons as $reason): ?>
          <option value="<?= $reason['id'] ?>" <?= $incident['reason_id'] == $reason['id'] ? 'selected' : '' ?>><?= e($reason['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="grid-span-2">หมายเหตุ
      <input type="text" name="note" value="<?= e($incident['note'] ?? '') ?>">
    </label>
    <div class="grid-span-2">
      <button type="submit" class="btn btn-primary"><?= icon('check-circle', 'icon icon-sm') ?> บันทึกการแก้ไข</button>
      <a href="incidents.php" class="btn btn-ghost"><?= icon('x', 'icon icon-sm') ?> ยกเลิก</a>
    </div>
  </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var ITEMS = <?= json_encode($itemsForJs, JSON_UNESCAPED_UNICODE) ?>;
  var EMPLOYEES = <?= json_encode($employees, JSON_UNESCAPED_UNICODE) ?>;
  initItemCombobox(document.getElementById('itemCombobox'), ITEMS);
  initEmployeeCombobox(document.getElementById('employeeCombobox'), EMPLOYEES, 'externalEmployeeBox');
});
</script>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
