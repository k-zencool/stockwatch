<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();
if (!$employee) {
    flash('error', 'ไม่พบพนักงานที่ต้องการแก้ไข');
    redirect('employees.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'toggle_active') {
        $stmt = $pdo->prepare("UPDATE employees SET is_active = 1 - is_active WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'เปลี่ยนสถานะพนักงานเรียบร้อยแล้ว');
        redirect('employees.php');
    }

    if (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM incidents WHERE employee_id = ?');
        $stmt->execute([$id]);
        $linkedCount = (int) $stmt->fetchColumn();

        try {
            $pdo->beginTransaction();
            if ($linkedCount > 0) {
                // incidents.employee_id is a RESTRICT foreign key, so the history has to be
                // detached first. The name/department is copied onto those rows so the
                // records still say who didn't requisition the item.
                $stmt = $pdo->prepare(
                    "UPDATE incidents SET employee_id = NULL, external_name = ?, external_department = ?
                     WHERE employee_id = ?"
                );
                $stmt->execute([$employee['full_name'], $employee['department'], $id]);
            }
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            $pdo->commit();
        } catch (PDOException $ex) {
            $pdo->rollBack();
            flash('error', 'ลบพนักงานไม่สำเร็จ: ' . $ex->getMessage());
            redirect('employee_edit.php?id=' . $id);
        }

        flash('success', $linkedCount > 0
            ? 'ลบพนักงานเรียบร้อยแล้ว — ประวัติ ' . $linkedCount . ' รายการยังอยู่ครบ โดยเก็บชื่อไว้เป็นข้อความแทน'
            : 'ลบพนักงานเรียบร้อยแล้ว');
        redirect('employees.php');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $employeeCode = trim($_POST['employee_code'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');

    if ($fullName === '') {
        flash('error', 'กรุณากรอกชื่อพนักงาน');
        redirect('employees.php?open=edit&id=' . $id);
    }

    $stmt = $pdo->prepare(
        "UPDATE employees SET employee_code=?, full_name=?, department=?, position=? WHERE id=?"
    );
    $stmt->execute([$employeeCode ?: null, $fullName, $department ?: null, $position ?: null, $id]);
    flash('success', 'บันทึกข้อมูลพนักงานเรียบร้อยแล้ว');
    redirect('employees.php');
}

// The edit UI lives in a dialog on the list page; this file only handles the POSTs.
redirect('employees.php?open=edit&id=' . $id);
