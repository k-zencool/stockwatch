<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$shelves = array_values(array_unique(array_filter(array_map(
    fn ($s) => trim((string) $s),
    (array) ($_GET['s'] ?? [])
), fn ($s) => $s !== '')));
natcasesort($shelves);

$counts = [];
if ($shelves) {
    $in = implode(',', array_fill(0, count($shelves), '?'));
    $stmt = $pdo->prepare("SELECT shelf, COUNT(*) c FROM items WHERE is_active = 1 AND shelf IN ($in) GROUP BY shelf");
    $stmt->execute(array_values($shelves));
    $counts = array_column($stmt->fetchAll(), 'c', 'shelf');
}
?>
<!doctype html>
<html lang="th" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR ช่องเก็บของ</title>
<link rel="icon" type="image/svg+xml" href="<?= asset('assets/favicon.svg') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
</head>
<body class="print-body">
<div class="print-toolbar no-print">
  <button onclick="window.print()" class="btn btn-primary"><?= icon('printer', 'icon icon-sm') ?> พิมพ์ QR (<?= count($shelves) ?> ช่อง)</button>
</div>
<?php if (!$shelves): ?>
  <p style="text-align:center">ยังไม่ได้เลือกช่องเก็บของ</p>
<?php endif; ?>
<div class="qr-sheet">
  <?php foreach ($shelves as $s): ?>
    <div class="qr-label">
      <div class="qr-code" data-url="<?= e(shelf_url($s)) ?>"></div>
      <div class="qr-shelf"><?= e($s) ?></div>
      <div class="qr-meta"><?= (int) ($counts[$s] ?? 0) ?> รายการ · สแกนเพื่อนับสต็อก</div>
    </div>
  <?php endforeach; ?>
</div>
<script>
document.querySelectorAll('.qr-code').forEach(function (el) {
  var qr = qrcode(0, 'M');
  qr.addData(el.dataset.url);
  qr.make();
  el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
});
</script>
</body>
</html>
