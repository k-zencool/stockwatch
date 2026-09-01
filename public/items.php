<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_category') {
    $categoryName = trim($_POST['category_name'] ?? '');

    if ($categoryName === '') {
        flash('error', 'กรุณากรอกชื่อหมวดหมู่');
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM item_categories WHERE LOWER(name) = LOWER(?)');
        $stmt->execute([$categoryName]);
        if ((int) $stmt->fetchColumn() > 0) {
            flash('error', 'มีหมวดหมู่ชื่อนี้อยู่แล้ว');
        } else {
            $stmt = $pdo->prepare('INSERT INTO item_categories (name) VALUES (?)');
            $stmt->execute([$categoryName]);
            flash('success', 'เพิ่มหมวดหมู่เรียบร้อยแล้ว');
        }
    }
    redirect('items.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name = trim($_POST['name'] ?? '');
    $categoryId = ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null;
    $code = trim($_POST['code'] ?? '');
    $unit = trim($_POST['unit'] ?? '');

    if ($name === '') {
        flash('error', 'กรุณากรอกชื่อของ/อะไหล่');
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO items (name, category_id, code, unit) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $categoryId, $code ?: null, $unit ?: null]);
        flash('success', 'เพิ่มรายการในคลังเรียบร้อยแล้ว');
    }
    redirect('items.php');
}

$categories = $pdo->query(
    "SELECT c.id, c.name,
     (SELECT COUNT(*) FROM items i WHERE i.category_id = c.id) AS item_count,
     (SELECT COUNT(*) FROM incidents inc WHERE inc.category_id = c.id) AS incident_count
     FROM item_categories c ORDER BY c.name"
)->fetchAll();

$filterCategory = $_GET['category_id'] ?? '';
$filterKeyword = trim($_GET['q'] ?? '');

$conditions = [];
$params = [];
if ($filterCategory !== '') { $conditions[] = 'i.category_id = ?'; $params[] = (int) $filterCategory; }
if ($filterKeyword !== '') { $conditions[] = '(i.name LIKE ? OR i.code LIKE ?)'; $params[] = "%$filterKeyword%"; $params[] = "%$filterKeyword%"; }
$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$sql = "SELECT i.*, c.name AS category_name,
        (SELECT COUNT(*) FROM incidents inc WHERE inc.item_id = i.id) AS incident_count
        FROM items i
        LEFT JOIN item_categories c ON c.id = i.category_id
        $where
        ORDER BY i.is_active DESC, i.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$pageTitle = 'คลังของ/อะไหล่';
$activeNav = 'items';
require __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div class="panel-header"><h2><?= icon('tag', 'icon icon-sm') ?> จัดการหมวดหมู่</h2></div>
  <form method="post" class="filter-form" style="margin-bottom: 16px;">
    <input type="hidden" name="action" value="add_category">
    <div class="input-icon input-icon-inline"><?= icon('tag', 'icon icon-sm') ?><input type="text" name="category_name" placeholder="ชื่อหมวดหมู่ใหม่ เช่น อุปกรณ์ทำความสะอาด" required></div>
    <button type="submit" class="btn btn-primary"><?= icon('plus', 'icon icon-sm') ?> เพิ่มหมวดหมู่</button>
  </form>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>ชื่อหมวดหมู่</th><th>จำนวนของในคลัง</th><th>จำนวนครั้งที่บันทึกไม่เบิก</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$categories): ?>
          <tr><td colspan="4" class="empty-row"><?= icon('tag', 'icon icon-lg') ?><span>ยังไม่มีหมวดหมู่</span></td></tr>
        <?php endif; ?>
        <?php foreach ($categories as $cat): ?>
          <tr>
            <td><?= e($cat['name']) ?></td>
            <td><span class="badge badge-neutral"><?= $cat['item_count'] ?></span></td>
            <td><span class="badge <?= $cat['incident_count'] >= 3 ? 'badge-danger' : 'badge-neutral' ?>"><?= $cat['incident_count'] ?></span></td>
            <td class="row-actions">
              <a href="category_edit.php?id=<?= $cat['id'] ?>" class="link-edit" title="แก้ไข/ลบ"><?= icon('edit', 'icon icon-sm') ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><h2><?= icon('plus', 'icon icon-sm') ?> เพิ่มรายการในคลัง</h2></div>
  <form method="post" class="grid-form">
    <input type="hidden" name="action" value="add">
    <label>ชื่อของ/อะไหล่
      <input type="text" name="name" placeholder="เช่น ปากกาลูกลื่น, ไขควงแฉก" required>
    </label>
    <label>หมวดหมู่
      <select name="category_id">
        <option value="">-- ไม่ระบุ --</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>รหัส/SKU
      <input type="text" name="code" placeholder="เช่น STA-001">
    </label>
    <label>หน่วยนับ
      <input type="text" name="unit" placeholder="เช่น ชิ้น, ด้าม, ม้วน">
    </label>
    <div class="grid-span-2">
      <button type="submit" class="btn btn-primary"><?= icon('plus', 'icon icon-sm') ?> เพิ่มในคลัง</button>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-header"><h2><?= icon('search', 'icon icon-sm') ?> ค้นหา / กรอง</h2></div>
  <form method="get" class="filter-form">
    <select name="category_id">
      <option value="">หมวดหมู่ทั้งหมด</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <div class="input-icon input-icon-inline"><?= icon('search', 'icon icon-sm') ?><input type="text" name="q" value="<?= e($filterKeyword) ?>" placeholder="ค้นหาชื่อ/รหัส"></div>
    <button type="submit" class="btn btn-secondary"><?= icon('filter', 'icon icon-sm') ?> กรอง</button>
    <a href="items.php" class="btn btn-ghost"><?= icon('x', 'icon icon-sm') ?> ล้าง</a>
  </form>
</div>

<div class="panel">
  <div class="panel-header"><h2><?= icon('archive', 'icon icon-sm') ?> รายการในคลังทั้งหมด (<?= count($items) ?>)</h2></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>รหัส</th><th>ชื่อ</th><th>หมวดหมู่</th><th>หน่วยนับ</th><th>ถูกบันทึกไม่เบิกกี่ครั้ง</th><th>สถานะ</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="7" class="empty-row"><?= icon('archive', 'icon icon-lg') ?><span>ยังไม่มีรายการในคลัง</span></td></tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
          <tr class="<?= $item['is_active'] ? '' : 'row-inactive' ?>">
            <td><?= e($item['code'] ?? '-') ?></td>
            <td><?= e($item['name']) ?></td>
            <td><?= e($item['category_name'] ?? '-') ?></td>
            <td><?= e($item['unit'] ?? '-') ?></td>
            <td><span class="badge <?= $item['incident_count'] >= 3 ? 'badge-danger' : 'badge-neutral' ?>"><?= $item['incident_count'] >= 3 ? icon('alert-triangle', 'icon icon-xs') : '' ?><?= $item['incident_count'] ?></span></td>
            <td><?= $item['is_active'] ? '<span class="badge badge-success">ใช้งานอยู่</span>' : '<span class="badge badge-neutral">ปิดใช้งาน</span>' ?></td>
            <td class="row-actions">
              <a href="item_edit.php?id=<?= $item['id'] ?>" class="link-edit" title="แก้ไข"><?= icon('edit', 'icon icon-sm') ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
