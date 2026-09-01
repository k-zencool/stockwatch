<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM incidents WHERE id = ?");
    $stmt->execute([$id]);
    flash('success', 'ลบรายการเรียบร้อยแล้ว');
}
redirect('incidents.php');
