    </main>
  </div>
  <nav class="bottom-nav">
    <a href="dashboard.php" class="<?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>"><?= icon('grid') ?><span>ภาพรวม</span></a>
    <a href="incidents.php" class="<?= ($activeNav ?? '') === 'incidents' ? 'active' : '' ?>"><?= icon('clipboard') ?><span>บันทึก</span></a>
    <a href="items.php" class="<?= ($activeNav ?? '') === 'items' ? 'active' : '' ?>"><?= icon('archive') ?><span>คลังของ</span></a>
    <a href="employees.php" class="<?= ($activeNav ?? '') === 'employees' ? 'active' : '' ?>"><?= icon('users') ?><span>พนักงาน</span></a>
    <a href="reports.php" class="<?= ($activeNav ?? '') === 'reports' ? 'active' : '' ?>"><?= icon('chart') ?><span>รายงาน</span></a>
  </nav>
</div>
<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
