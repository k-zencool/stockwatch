<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM reasons WHERE id = ?");
$stmt->execute([$id]);
$reason = $stmt->fetch();
if (!$reason) {
    flash('error', 'ไม่พบสาเหตุที่ต้องการแก้ไข');
    redirect('incidents.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM reasons WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'ลบสาเหตุเรียบร้อยแล้ว (รายการบันทึกที่เคยใช้สาเหตุนี้จะกลายเป็น "ไม่ระบุสาเหตุ")');
        redirect('incidents.php');
    }

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        flash('error', 'กรุณากรอกชื่อสาเหตุ');
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reasons WHERE LOWER(name) = LOWER(?) AND id != ?');
        $stmt->execute([$name, $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            flash('error', 'มีสาเหตุนี้อยู่แล้ว');
        } else {
            $stmt = $pdo->prepare("UPDATE reasons SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            flash('success', 'บันทึกชื่อสาเหตุเรียบร้อยแล้ว');
            redirect('incidents.php');
        }
    }
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM incidents WHERE reason_id = ?');
$stmt->execute([$id]);
$usageCount = (int) $stmt->fetchColumn();

$pageTitle = 'แก้ไขสาเหตุ';
$activeNav = 'incidents';
require __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div class="panel-header"><h2><?= icon('edit', 'icon icon-sm') ?> แก้ไขสาเหตุ</h2></div>
  <form method="post" class="grid-form">
    <label class="grid-span-2">ชื่อสาเหตุ
      <input type="text" name="name" value="<?= e($reason['name']) ?>" required>
    </label>
    <div class="grid-span-2">
      <button type="submit" class="btn btn-primary"><?= icon('check-circle', 'icon icon-sm') ?> บันทึกการแก้ไข</button>
      <a href="incidents.php" class="btn btn-ghost"><?= icon('x', 'icon icon-sm') ?> ยกเลิก</a>
    </div>
  </form>

  <div class="alert alert-error" style="margin-top: 20px;">
    <?= icon('alert-triangle', 'icon icon-sm') ?>
    <span>
      สาเหตุนี้ถูกใช้ในรายการบันทึกไปแล้ว <?= $usageCount ?> ครั้ง
      ถ้าลบ รายการบันทึกเหล่านี้จะกลายเป็น "ไม่ระบุสาเหตุ" ทันที (ข้อมูลจะไม่หายไป)
    </span>
  </div>
  <form method="post" class="inline-toggle" onsubmit="return confirm('ยืนยันลบสาเหตุนี้? การกระทำนี้ย้อนกลับไม่ได้');">
    <input type="hidden" name="action" value="delete">
    <button type="submit" class="btn btn-danger-outline"><?= icon('trash', 'icon icon-sm') ?> ลบสาเหตุนี้</button>
  </form>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
