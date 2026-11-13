<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$shelf = trim((string) ($_GET['s'] ?? $_POST['s'] ?? ''));
if ($shelf === '') {
    redirect('stock.php');
}

$stmt = $pdo->prepare(
    "SELECT i.id, i.code, i.name, i.unit,
            (SELECT sc.qty FROM stock_counts sc WHERE sc.item_id = i.id ORDER BY sc.counted_at DESC, sc.id DESC LIMIT 1) AS last_qty,
            (SELECT MAX(sc.counted_at) FROM stock_counts sc WHERE sc.item_id = i.id) AS last_at
     FROM items i
     WHERE i.is_active = 1 AND i.shelf = ?
     ORDER BY i.code, i.name"
);
$stmt->execute([$shelf]);
$items = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qtys = $_POST['qty'] ?? [];
    $valid = array_column($items, 'id');
    $now = gmdate('Y-m-d H:i:s');
    $ins = $pdo->prepare('INSERT INTO stock_counts (item_id, qty, counted_by, counted_at) VALUES (?, ?, ?, ?)');
    $saved = 0;
    $pdo->beginTransaction();
    foreach ($qtys as $itemId => $qty) {
        $qty = trim((string) $qty);
        if ($qty === '' || !in_array((int) $itemId, $valid, true) || !preg_match('/^\d{1,6}$/', $qty)) {
            continue;
        }
        $ins->execute([(int) $itemId, (int) $qty, current_user()['id'], $now]);
        $saved++;
    }
    $pdo->commit();
    if ($saved === 0) {
        flash('error', 'ยังไม่ได้กรอกจำนวนที่นับได้');
        redirect('shelf.php?s=' . rawurlencode($shelf));
    }
    flash('success', "บันทึกผลนับช่อง {$shelf} แล้ว {$saved} รายการ");
    redirect('stock.php');
}

$pageTitle = 'นับสต็อก ช่อง ' . $shelf;
$activeNav = 'stock';
require __DIR__ . '/../src/views/header.php';
?>
<div class="page-toolbar">
  <a href="stock.php" class="btn btn-ghost"><?= icon('chevrons-left', 'icon icon-sm') ?> กลับ</a>
</div>

<?php if (!$items): ?>
  <div class="panel"><p class="empty-row"><?= icon('archive', 'icon icon-lg') ?><span>ไม่พบของในช่อง <?= e($shelf) ?></span></p></div>
<?php else: ?>
<form method="post" class="panel">
  <input type="hidden" name="s" value="<?= e($shelf) ?>">
  <div class="panel-header">
    <h2><?= icon('archive', 'icon icon-sm') ?> ช่อง <?= e($shelf) ?> (<?= count($items) ?> รายการ)</h2>
  </div>
  <div class="count-list">
    <?php foreach ($items as $it): ?>
      <label class="count-row">
        <span class="count-info">
          <span class="count-name"><?= e($it['name']) ?></span>
          <span class="count-meta"><?= e($it['code'] ?? '-') ?>
            · นับล่าสุด: <?= $it['last_at'] !== null ? (int) $it['last_qty'] . ' ' . e($it['unit'] ?? '') . ' (' . e(thai_datetime($it['last_at'])) . ')' : 'ยังไม่เคยนับ' ?></span>
        </span>
        <input type="number" name="qty[<?= (int) $it['id'] ?>]" min="0" max="999999" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="จำนวน" class="count-input">
      </label>
    <?php endforeach; ?>
  </div>
  <div class="count-actions">
    <button type="submit" class="btn btn-primary"><?= icon('check-circle', 'icon icon-sm') ?> บันทึกผลนับ</button>
    <p class="field-hint">กรอกเฉพาะรายการที่นับ ช่องที่เว้นว่างจะไม่ถูกบันทึก</p>
  </div>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
