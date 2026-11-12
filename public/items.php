<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_category') {
    $categoryName = trim($_POST['category_name'] ?? '');
    $categoryOk = false;

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
            $categoryOk = true;
        }
    }
    redirect($categoryOk ? 'items.php' : 'items.php?open=categories');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name = trim($_POST['name'] ?? '');
    $categoryId = ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null;
    $code = trim($_POST['code'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $shelf = trim($_POST['shelf'] ?? '');

    if ($name === '') {
        flash('error', 'กรุณากรอกชื่อของ/อะไหล่');
        redirect('items.php?open=add');
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO items (name, category_id, code, shelf, unit) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $categoryId, $code ?: null, $shelf ?: null, $unit ?: null]);
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
$filterStatus = $_GET['status'] ?? '';
$filterKeyword = trim($_GET['q'] ?? '');

// Chips for the filters in effect; each links back to this view with that one filter dropped.
$activeFilters = [];
$removeFilterUrl = function (string $key): string {
    $q = $_GET;
    unset($q[$key], $q['page']);
    $qs = http_build_query($q);
    return 'items.php' . ($qs ? '?' . $qs : '');
};
if ($filterCategory !== '') {
    $name = '';
    foreach ($categories as $cat) {
        if ($cat['id'] == $filterCategory) { $name = $cat['name']; break; }
    }
    $activeFilters[] = ['label' => 'หมวดหมู่: ' . ($name ?: $filterCategory), 'url' => $removeFilterUrl('category_id')];
}
if ($filterStatus !== '') {
    $activeFilters[] = [
        'label' => 'สถานะ: ' . ($filterStatus === 'inactive' ? 'ปิดใช้งาน' : 'ใช้งานอยู่'),
        'url' => $removeFilterUrl('status'),
    ];
}
if ($filterKeyword !== '') {
    $activeFilters[] = ['label' => 'คำค้น: ' . $filterKeyword, 'url' => $removeFilterUrl('q')];
}
$advancedCount = count($activeFilters) - ($filterKeyword !== '' ? 1 : 0);

$conditions = [];
$params = [];
if ($filterCategory !== '') { $conditions[] = 'i.category_id = ?'; $params[] = (int) $filterCategory; }
if ($filterStatus !== '') { $conditions[] = 'i.is_active = ?'; $params[] = $filterStatus === 'inactive' ? 0 : 1; }
if ($filterKeyword !== '') {
    $conditions[] = '(i.name LIKE ? OR i.code LIKE ? OR i.shelf LIKE ?)';
    $params[] = "%$filterKeyword%"; $params[] = "%$filterKeyword%"; $params[] = "%$filterKeyword%";
}
$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items i $where");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$sql = "SELECT i.*, c.name AS category_name,
        (SELECT COUNT(*) FROM incidents inc WHERE inc.item_id = i.id) AS incident_count
        FROM items i
        LEFT JOIN item_categories c ON c.id = i.category_id
        $where
        ORDER BY i.is_active DESC, i.name
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$pageTitle = 'คลังของ/อะไหล่';
$activeNav = 'items';
require __DIR__ . '/../src/views/header.php';
?>
<div class="page-toolbar">
  <button type="button" class="btn btn-primary" data-open-modal="addModal"><?= icon('plus', 'icon icon-sm') ?> เพิ่มของเข้าคลัง</button>
  <button type="button" class="btn btn-ghost" data-open-modal="categoriesModal"><?= icon('tag', 'icon icon-sm') ?> จัดการหมวดหมู่</button>
</div>

<div class="panel panel-slim">
  <form method="get" class="filter-bar" data-autosubmit>
    <div class="input-icon filter-search">
      <?= icon('search', 'icon icon-sm') ?>
      <input type="text" name="q" value="<?= e($filterKeyword) ?>" placeholder="ค้นหาชื่อของ รหัส/SKU หรือ Shelf แล้วกด Enter">
    </div>
    <button type="button" class="btn btn-ghost" data-menu="advancedFilters" aria-expanded="false">
      <?= icon('filter', 'icon icon-sm') ?> ตัวกรอง
      <?php if ($advancedCount > 0): ?><span class="badge badge-info"><?= $advancedCount ?></span><?php endif; ?>
    </button>

    <div class="filter-menu-panel" id="advancedFilters" hidden>
      <div class="filter-menu-head"><?= icon('filter', 'icon icon-sm') ?> กรองรายการ</div>
      <label>หมวดหมู่
        <select name="category_id">
          <option value="">ทั้งหมด</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>สถานะ
        <select name="status">
          <option value="">ทั้งหมด</option>
          <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>ใช้งานอยู่</option>
          <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>ปิดใช้งาน</option>
        </select>
      </label>
    </div>
  </form>

  <?php if ($activeFilters): ?>
    <div class="filter-chips">
      <?php foreach ($activeFilters as $chip): ?>
        <a class="filter-chip" href="<?= e($chip['url']) ?>" title="เอาตัวกรองนี้ออก"><?= e($chip['label']) ?><?= icon('x', 'icon icon-xs') ?></a>
      <?php endforeach; ?>
      <a class="filter-chip filter-chip-clear" href="items.php"><?= icon('x', 'icon icon-xs') ?> ล้างทั้งหมด</a>
    </div>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel-header">
    <h2><?= icon('archive', 'icon icon-sm') ?> รายการในคลังทั้งหมด (<?= $totalRows ?>)</h2>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>รหัส</th><th>ชื่อ</th><th>หมวดหมู่</th><th>Shelf</th><th>หน่วยนับ</th><th>ถูกบันทึกไม่เบิกกี่ครั้ง</th><th>สถานะ</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="8" class="empty-row"><?= icon('archive', 'icon icon-lg') ?><span>ไม่พบรายการในคลัง</span></td></tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
          <tr class="<?= $item['is_active'] ? '' : 'row-inactive' ?>">
            <td><?= e($item['code'] ?? '-') ?></td>
            <td><?= e($item['name']) ?></td>
            <td><?= e($item['category_name'] ?? '-') ?></td>
            <td><?= e($item['shelf'] ?? '-') ?></td>
            <td><?= e($item['unit'] ?? '-') ?></td>
            <td><span class="badge <?= $item['incident_count'] >= 3 ? 'badge-danger' : 'badge-neutral' ?>"><?= $item['incident_count'] >= 3 ? icon('alert-triangle', 'icon icon-xs') : '' ?><?= $item['incident_count'] ?></span></td>
            <td><?= $item['is_active'] ? '<span class="badge badge-success">ใช้งานอยู่</span>' : '<span class="badge badge-neutral">ปิดใช้งาน</span>' ?></td>
            <td class="row-actions">
              <button type="button" class="link-edit" title="แก้ไข"
                data-edit-modal="editModal"
                data-id="<?= $item['id'] ?>"
                data-active="<?= $item['is_active'] ? '1' : '0' ?>"
                data-field-name="<?= e($item['name']) ?>"
                data-field-category_id="<?= e((string) ($item['category_id'] ?? '')) ?>"
                data-field-code="<?= e($item['code'] ?? '') ?>"
                data-field-shelf="<?= e($item['shelf'] ?? '') ?>"
                data-field-unit="<?= e($item['unit'] ?? '') ?>"><?= icon('edit', 'icon icon-sm') ?></button>
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
        <a href="items.php?<?= $qs ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<dialog id="categoriesModal" class="modal">
  <div class="modal-head">
    <h2><?= icon('tag', 'icon icon-sm') ?> จัดการหมวดหมู่</h2>
    <button type="button" class="modal-close" data-close-modal aria-label="ปิด"><?= icon('x', 'icon icon-sm') ?></button>
  </div>
  <div class="modal-body">
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
</dialog>

<dialog id="addModal" class="modal">
  <div class="modal-head">
    <h2><?= icon('plus', 'icon icon-sm') ?> เพิ่มของเข้าคลัง</h2>
    <button type="button" class="modal-close" data-close-modal aria-label="ปิด"><?= icon('x', 'icon icon-sm') ?></button>
  </div>
  <div class="modal-body">
    <form method="post" class="grid-form">
      <input type="hidden" name="action" value="add">
      <label class="grid-span-2">ชื่อของ/อะไหล่
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
      <label>Shelf (ตำแหน่งจัดเก็บ)
        <input type="text" name="shelf" placeholder="เช่น A3-3">
      </label>
      <label>หน่วยนับ
        <input type="text" name="unit" placeholder="เช่น ชิ้น, ด้าม, ม้วน">
      </label>
      <div class="grid-span-2">
        <button type="submit" class="btn btn-primary"><?= icon('plus', 'icon icon-sm') ?> เพิ่มในคลัง</button>
      </div>
    </form>
  </div>
</dialog>

<dialog id="editModal" class="modal">
  <div class="modal-head">
    <h2><?= icon('edit', 'icon icon-sm') ?> แก้ไขรายการในคลัง</h2>
    <button type="button" class="modal-close" data-close-modal aria-label="ปิด"><?= icon('x', 'icon icon-sm') ?></button>
  </div>
  <div class="modal-body">
    <form method="post" action="item_edit.php" class="grid-form">
      <input type="hidden" name="id" value="">
      <label class="grid-span-2">ชื่อของ/อะไหล่
        <input type="text" name="name" required>
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
        <input type="text" name="code">
      </label>
      <label>Shelf (ตำแหน่งจัดเก็บ)
        <input type="text" name="shelf">
      </label>
      <label>หน่วยนับ
        <input type="text" name="unit">
      </label>
      <div class="grid-span-2">
        <button type="submit" class="btn btn-primary"><?= icon('check-circle', 'icon icon-sm') ?> บันทึกการแก้ไข</button>
        <button type="button" class="btn btn-ghost" data-close-modal><?= icon('x', 'icon icon-sm') ?> ยกเลิก</button>
      </div>
    </form>

    <div class="modal-danger-zone">
      <form method="post" action="item_edit.php">
        <input type="hidden" name="id" value="">
        <input type="hidden" name="action" value="toggle_active">
        <button type="submit" class="btn btn-danger-outline" data-toggle-btn
          data-label-off="ปิดใช้งานรายการนี้" data-label-on="เปิดใช้งานอีกครั้ง">ปิดใช้งานรายการนี้</button>
      </form>
      <p class="field-hint">ปิดใช้งานแล้วรายการจะไม่ขึ้นในช่องค้นหาตอนบันทึก แต่ประวัติเดิมยังอยู่ครบ</p>
    </div>
  </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function () {
  initModals();
  initRowEditModals();
  <?php if (($_GET['open'] ?? '') === 'add'): ?>
  document.getElementById('addModal').showModal();
  <?php elseif (($_GET['open'] ?? '') === 'categories'): ?>
  document.getElementById('categoriesModal').showModal();
  <?php elseif (($_GET['open'] ?? '') === 'edit' && ($_GET['id'] ?? '') !== ''): ?>
  var editBtn = document.querySelector('[data-edit-modal="editModal"][data-id="<?= (int) $_GET['id'] ?>"]');
  if (editBtn) editBtn.click();
  <?php endif; ?>
});
</script>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
