<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) {
    flash('error', 'ไม่พบรายการที่ต้องการแก้ไข');
    redirect('items.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'toggle_active') {
        $stmt = $pdo->prepare("UPDATE items SET is_active = 1 - is_active WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'เปลี่ยนสถานะรายการเรียบร้อยแล้ว');
        redirect('items.php');
    }

    $name = trim($_POST['name'] ?? '');
    $categoryId = ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null;
    $code = trim($_POST['code'] ?? '');
    $unit = trim($_POST['unit'] ?? '');

    if ($name === '') {
        flash('error', 'กรุณากรอกชื่อของ/อะไหล่');
        redirect('items.php?open=edit&id=' . $id);
    }

    $stmt = $pdo->prepare(
        "UPDATE items SET name=?, category_id=?, code=?, unit=? WHERE id=?"
    );
    $stmt->execute([$name, $categoryId, $code ?: null, $unit ?: null, $id]);
    flash('success', 'บันทึกข้อมูลรายการเรียบร้อยแล้ว');
    redirect('items.php');
}

// The edit UI lives in a dialog on the list page; this file only handles the POSTs.
redirect('items.php?open=edit&id=' . $id);
