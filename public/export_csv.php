<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$stmt = $pdo->prepare(
    "SELECT i.incident_date,
     COALESCE(e.full_name, i.external_name) AS display_name,
     COALESCE(e.department, i.external_department) AS display_department,
     (e.id IS NULL) AS is_external,
     i.item_name, c.name AS category_name, r.name AS reason_name, i.note
     FROM incidents i
     LEFT JOIN employees e ON e.id = i.employee_id
     LEFT JOIN item_categories c ON c.id = i.category_id
     LEFT JOIN reasons r ON r.id = i.reason_id
     WHERE i.incident_date BETWEEN ? AND ?
     ORDER BY i.incident_date DESC"
);
$stmt->execute([$dateFrom, $dateTo]);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="report_' . $dateFrom . '_to_' . $dateTo . '.csv"');

echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['วันที่', 'ชื่อพนักงาน', 'ประเภทพนักงาน', 'แผนก', 'รายการที่ไม่เบิก', 'หมวดหมู่', 'สาเหตุ', 'หมายเหตุ']);
foreach ($rows as $row) {
    fputcsv($out, [
        $row['incident_date'],
        $row['display_name'],
        $row['is_external'] ? 'แผนกอื่น' : 'พนักงานภายใน',
        $row['display_department'] ?? '-',
        $row['item_name'],
        $row['category_name'] ?? '-',
        $row['reason_name'] ?? '-',
        $row['note'] ?? '',
    ]);
}
fclose($out);
