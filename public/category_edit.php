<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM item_categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();
if (!$category) {
    flash('error', 'ไม่พบหมวดหมู่ที่ต้องการแก้ไข');
    redirect('items.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM item_categories WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'ลบหมวดหมู่เรียบร้อยแล้ว (ของและรายการบันทึกที่เคยอยู่หมวดนี้จะกลายเป็น "ไม่ระบุหมวดหมู่")');
        redirect('items.php');
    }

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        flash('error', 'กรุณากรอกชื่อหมวดหมู่');
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM item_categories WHERE LOWER(name) = LOWER(?) AND id != ?');
        $stmt->execute([$name, $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            flash('error', 'มีหมวดหมู่ชื่อนี้อยู่แล้ว');
        } else {
            $stmt = $pdo->prepare("UPDATE item_categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            flash('success', 'บันทึกชื่อหมวดหมู่เรียบร้อยแล้ว');
            redirect('items.php');
        }
    }
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE category_id = ?');
$stmt->execute([$id]);
$itemCount = (int) $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM incidents WHERE category_id = ?');
$stmt->execute([$id]);
$incidentCount = (int) $stmt->fetchColumn();

$pageTitle = 'แก้ไขหมวดหมู่';
$activeNav = 'items';
require __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div class="panel-header"><h2><?= icon('edit', 'icon icon-sm') ?> แก้ไขหมวดหมู่</h2></div>
  <form method="post" class="grid-form">
    <label class="grid-span-2">ชื่อหมวดหมู่
      <input type="text" name="name" value="<?= e($category['name']) ?>" required>
    </label>
    <div class="grid-span-2">
      <button type="submit" class="btn btn-primary"><?= icon('check-circle', 'icon icon-sm') ?> บันทึกการแก้ไข</button>
      <a href="items.php" class="btn btn-ghost"><?= icon('x', 'icon icon-sm') ?> ยกเลิก</a>
    </div>
  </form>

  <div class="alert alert-error" style="margin-top: 20px;">
    <?= icon('alert-triangle', 'icon icon-sm') ?>
    <span>
      หมวดหมู่นี้ถูกใช้อยู่กับของในคลัง <?= $itemCount ?> รายการ และมีการบันทึกไม่เบิกไปแล้ว <?= $incidentCount ?> ครั้ง
      ถ้าลบ ของและรายการบันทึกเหล่านี้จะกลายเป็น "ไม่ระบุหมวดหมู่" ทันที (ข้อมูลจะไม่หายไป)
    </span>
  </div>
  <form method="post" class="inline-toggle" onsubmit="return confirm('ยืนยันลบหมวดหมู่นี้? การกระทำนี้ย้อนกลับไม่ได้');">
    <input type="hidden" name="action" value="delete">
    <button type="submit" class="btn btn-danger-outline"><?= icon('trash', 'icon icon-sm') ?> ลบหมวดหมู่นี้</button>
  </form>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
