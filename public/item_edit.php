<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash('error', 'ไม่พบรายการที่ต้องการแก้ไข');
    redirect('items.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'toggle_active') {
        $stmt = $pdo->prepare("UPDATE items SET is_active = 1 - is_active WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'เปลี่ยนสถานะรายการเรียบร้อยแล้ว');
        redirect('items.php');
    }

    $name = trim($_POST['name'] ?? '');
    $categoryId = ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null;
    $code = trim($_POST['code'] ?? '');
    $unit = trim($_POST['unit'] ?? '');

    if ($name === '') {
        flash('error', 'กรุณากรอกชื่อของ/อะไหล่');
    } else {
        $stmt = $pdo->prepare(
            "UPDATE items SET name=?, category_id=?, code=?, unit=? WHERE id=?"
        );
        $stmt->execute([$name, $categoryId, $code ?: null, $unit ?: null, $id]);
        flash('success', 'บันทึกข้อมูลรายการเรียบร้อยแล้ว');
        redirect('items.php');
    }
}

$categories = $pdo->query("SELECT id, name FROM item_categories ORDER BY name")->fetchAll();

$pageTitle = 'แก้ไขรายการในคลัง';
$activeNav = 'items';
require __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div class="panel-header"><h2><?= icon('edit', 'icon icon-sm') ?> แก้ไขรายการในคลัง</h2></div>
  <form method="post" class="grid-form">
    <label>ชื่อของ/อะไหล่
      <input type="text" name="name" value="<?= e($item['name']) ?>" required>
    </label>
    <label>หมวดหมู่
      <select name="category_id">
        <option value="">-- ไม่ระบุ --</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $item['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>รหัส/SKU
      <input type="text" name="code" value="<?= e($item['code'] ?? '') ?>">
    </label>
    <label>หน่วยนับ
      <input type="text" name="unit" value="<?= e($item['unit'] ?? '') ?>">
    </label>
    <div class="grid-span-2">
      <button type="submit" class="btn btn-primary"><?= icon('check-circle', 'icon icon-sm') ?> บันทึกการแก้ไข</button>
      <a href="items.php" class="btn btn-ghost"><?= icon('x', 'icon icon-sm') ?> ยกเลิก</a>
    </div>
  </form>
  <form method="post" class="inline-toggle">
    <input type="hidden" name="action" value="toggle_active">
    <button type="submit" class="btn <?= $item['is_active'] ? 'btn-danger-outline' : 'btn-secondary' ?>">
      <?= $item['is_active'] ? icon('x', 'icon icon-sm') : icon('check-circle', 'icon icon-sm') ?>
      <?= $item['is_active'] ? 'ปิดใช้งาน' : 'เปิดใช้งานอีกครั้ง' ?>
    </button>
  </form>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
