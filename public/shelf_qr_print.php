<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

// Shelf names always come from items.shelf; ?all=1 prints every shelf, ?s[]=... only those picked.
$counts = $pdo->query(
    "SELECT shelf, COUNT(*) FROM items WHERE is_active = 1 AND shelf IS NOT NULL AND shelf <> '' GROUP BY shelf"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$shelves = array_keys($counts);
if (empty($_GET['all'])) {
    $picked = array_map(fn ($v) => trim((string) $v), (array) ($_GET['s'] ?? []));
    $shelves = array_values(array_intersect($shelves, $picked));
}
natcasesort($shelves);

?>
<!doctype html>
<html lang="th" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR ช่องเก็บของ</title>
<link rel="icon" type="image/svg+xml" href="<?= asset('assets/favicon.svg') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@500;700&display=swap" rel="stylesheet">
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
      <div class="qr-shelf"><?= e($s) ?></div>
      <div class="qr-count">ของที่ต้องนับ <?= (int) $counts[$s] ?> รายการ</div>
      <div class="qr-code" data-url="<?= e(shelf_url($s)) ?>"></div>
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
