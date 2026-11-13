    </main>
  </div>
  <?php $nav = $activeNav ?? ''; $inMore = in_array($nav, ['stock', 'employees', 'reports'], true); ?>
  <nav class="bottom-nav">
    <a href="dashboard.php" class="<?= $nav === 'dashboard' ? 'active' : '' ?>"><?= icon('grid') ?><span>ภาพรวม</span></a>
    <a href="incidents.php" class="<?= $nav === 'incidents' ? 'active' : '' ?>"><?= icon('clipboard') ?><span>บันทึก</span></a>
    <a href="stock.php?scan=1" class="bottom-nav-scan" aria-label="สแกน QR"><span class="scan-fab"><?= icon('camera') ?></span><span>สแกน</span></a>
    <a href="items.php" class="<?= $nav === 'items' ? 'active' : '' ?>"><?= icon('archive') ?><span>คลังของ</span></a>
    <button type="button" class="<?= $inMore ? 'active' : '' ?>" id="moreMenuBtn" aria-haspopup="dialog"><?= icon('menu') ?><span>เมนู</span></button>
  </nav>
  <dialog id="moreMenu" class="sheet" aria-label="เมนู">
    <div class="sheet-grip"></div>
    <div class="sheet-grid">
      <a href="incidents.php?open=add" class="sheet-tile"><?= icon('plus') ?><span>บันทึกรายการใหม่</span></a>
      <a href="stock.php" class="sheet-tile <?= $nav === 'stock' ? 'active' : '' ?>"><?= icon('qr') ?><span>เช็คสต็อก / ปริ้น QR</span></a>
      <a href="employees.php" class="sheet-tile <?= $nav === 'employees' ? 'active' : '' ?>"><?= icon('users') ?><span>พนักงาน</span></a>
      <a href="reports.php" class="sheet-tile <?= $nav === 'reports' ? 'active' : '' ?>"><?= icon('chart') ?><span>รายงานสรุป</span></a>
    </div>
    <div class="sheet-list">
      <button type="button" class="sheet-row" id="sheetTheme"><?= icon('moon', 'icon icon-sm') ?><span>สลับโหมดมืด/สว่าง</span></button>
      <a href="logout.php" class="sheet-row sheet-row-danger"><?= icon('logout', 'icon icon-sm') ?><span>ออกจากระบบ</span></a>
    </div>
    <button type="button" class="btn btn-ghost sheet-close" data-sheet-close>ปิด</button>
  </dialog>
</div>
<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
