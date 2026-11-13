<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$keyword = trim($_GET['q'] ?? '');
$params = [];
$where = "WHERE i.is_active = 1 AND i.shelf IS NOT NULL AND i.shelf <> ''";
if ($keyword !== '') {
    $where .= ' AND i.shelf LIKE ?';
    $params[] = "%$keyword%";
}

// One row per shelf: how many items it holds and when it was last counted.
$stmt = $pdo->prepare(
    "SELECT i.shelf, COUNT(*) AS item_count,
            SUM(lc.last_at IS NOT NULL) AS counted_items,
            MAX(lc.last_at) AS last_counted
     FROM items i
     LEFT JOIN (SELECT item_id, MAX(counted_at) AS last_at FROM stock_counts GROUP BY item_id) lc
            ON lc.item_id = i.id
     $where
     GROUP BY i.shelf"
);
$stmt->execute($params);
$shelves = $stmt->fetchAll();
usort($shelves, fn ($a, $b) => strnatcasecmp($a['shelf'], $b['shelf']));

$noShelf = (int) $pdo->query(
    "SELECT COUNT(*) FROM items WHERE is_active = 1 AND (shelf IS NULL OR shelf = '')"
)->fetchColumn();

$pageTitle = 'เช็คสต็อก';
$activeNav = 'stock';
require __DIR__ . '/../src/views/header.php';
?>
<div class="page-toolbar">
  <button type="button" class="btn btn-primary" id="scanBtn"><?= icon('camera', 'icon icon-sm') ?> สแกน QR ช่องเก็บของ</button>
</div>

<div class="panel panel-slim">
  <form method="get" class="filter-bar">
    <div class="input-icon filter-search">
      <?= icon('search', 'icon icon-sm') ?>
      <input type="text" name="q" value="<?= e($keyword) ?>" placeholder="ค้นหา Shelf เช่น A3 แล้วกด Enter">
    </div>
  </form>
</div>

<form method="get" action="shelf_qr_print.php" target="_blank" class="panel">
  <div class="panel-header">
    <h2><?= icon('qr', 'icon icon-sm') ?> ช่องเก็บของ (<?= count($shelves) ?>)</h2>
    <div class="panel-actions">
      <button type="submit" class="btn btn-ghost" id="printSelected" disabled><?= icon('printer', 'icon icon-sm') ?> ปริ้น QR ที่เลือก</button>
      <a href="shelf_qr_print.php?all=1" target="_blank" class="btn btn-ghost"><?= icon('printer', 'icon icon-sm') ?> ปริ้นทุกช่อง</a>
    </div>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th><input type="checkbox" id="checkAll" aria-label="เลือกทั้งหมด"></th>
          <th>Shelf</th><th>จำนวนรายการ</th><th>นับแล้ว</th><th>นับล่าสุด</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$shelves): ?>
          <tr><td colspan="6" class="empty-row"><?= icon('archive', 'icon icon-lg') ?><span>ไม่พบช่องเก็บของ</span></td></tr>
        <?php endif; ?>
        <?php foreach ($shelves as $s): ?>
          <tr>
            <td><input type="checkbox" name="s[]" value="<?= e($s['shelf']) ?>" class="shelf-check" aria-label="เลือก <?= e($s['shelf']) ?>"></td>
            <td><a href="shelf.php?s=<?= e(rawurlencode($s['shelf'])) ?>"><strong><?= e($s['shelf']) ?></strong></a></td>
            <td><?= (int) $s['item_count'] ?></td>
            <td><span class="badge <?= $s['counted_items'] == $s['item_count'] ? 'badge-success' : 'badge-neutral' ?>"><?= (int) $s['counted_items'] ?>/<?= (int) $s['item_count'] ?></span></td>
            <td><?= e(thai_datetime($s['last_counted'])) ?></td>
            <td class="row-actions"><a href="shelf.php?s=<?= e(rawurlencode($s['shelf'])) ?>" class="btn btn-ghost btn-sm">นับ</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($noShelf > 0): ?>
    <p class="field-hint" style="padding: 0 16px 12px;">มีของ <?= $noShelf ?> รายการที่ยังไม่ได้ระบุ Shelf — ใส่ Shelf ได้ที่หน้าคลังของ แล้วจะขึ้นในหน้านี้</p>
  <?php endif; ?>
</form>

<dialog id="scanModal" class="modal">
  <div class="modal-head">
    <h2><?= icon('camera', 'icon icon-sm') ?> สแกน QR ช่องเก็บของ</h2>
    <button type="button" class="modal-close" data-close-modal aria-label="ปิด"><?= icon('x', 'icon icon-sm') ?></button>
  </div>
  <div class="modal-body">
    <div class="scan-view"><video id="scanVideo" playsinline muted></video><div class="scan-frame"></div></div>
    <p class="field-hint" id="scanStatus">เล็งกล้องไปที่ QR ของช่องเก็บของ</p>
  </div>
</dialog>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  initModals();

  var checks = document.querySelectorAll('.shelf-check');
  var printBtn = document.getElementById('printSelected');
  function refresh() { printBtn.disabled = !document.querySelector('.shelf-check:checked'); }
  checks.forEach(function (c) { c.addEventListener('change', refresh); });
  document.getElementById('checkAll').addEventListener('change', function () {
    var on = this.checked; checks.forEach(function (c) { c.checked = on; }); refresh();
  });

  var modal = document.getElementById('scanModal');
  var video = document.getElementById('scanVideo');
  var status = document.getElementById('scanStatus');
  var canvas = document.createElement('canvas');
  var ctx = canvas.getContext('2d', { willReadFrequently: true });
  var stream = null;

  function stop() {
    if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
  }
  modal.addEventListener('close', stop);

  // A shelf QR holds .../shelf.php?s=A3-3; a plain "A3-3" is accepted too.
  function shelfFrom(text) {
    try { var u = new URL(text); if (u.searchParams.get('s')) return u.searchParams.get('s'); } catch (e) {}
    return text.trim();
  }

  function tick() {
    if (!stream) return;
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
      canvas.width = video.videoWidth; canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      var img = ctx.getImageData(0, 0, canvas.width, canvas.height);
      var code = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
      if (code && code.data) {
        var shelf = shelfFrom(code.data);
        if (shelf) {
          status.textContent = 'เจอช่อง ' + shelf;
          stop();
          location.href = 'shelf.php?s=' + encodeURIComponent(shelf);
          return;
        }
      }
    }
    requestAnimationFrame(tick);
  }

  document.getElementById('scanBtn').addEventListener('click', function () {
    status.textContent = 'กำลังเปิดกล้อง...';
    modal.showModal();
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      status.textContent = 'อุปกรณ์นี้เปิดกล้องไม่ได้ ใช้แอปกล้องของมือถือสแกน QR แทนได้';
      return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
      .then(function (s) {
        stream = s; video.srcObject = s; video.play();
        status.textContent = 'เล็งกล้องไปที่ QR ของช่องเก็บของ';
        requestAnimationFrame(tick);
      })
      .catch(function () { status.textContent = 'เปิดกล้องไม่ได้ — กดอนุญาตให้ใช้กล้องในการตั้งค่าเบราว์เซอร์ก่อน'; });
  });

  // Opened from the scan button in the mobile bottom nav.
  if (new URLSearchParams(location.search).get('scan') === '1') {
    history.replaceState(null, '', 'stock.php');
    document.getElementById('scanBtn').click();
  }
});
</script>
<?php require __DIR__ . '/../src/views/footer.php'; ?>
