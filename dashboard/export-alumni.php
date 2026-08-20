<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin('admin');

$filter_status = $_GET['status'] ?? 'all';
$filter_batch = $_GET['batch_year'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = "u.role = 'alumni'";
$params = [];

if ($filter_status !== 'all') {
    $where .= " AND u.status = ?";
    $params[] = $filter_status;
}
if ($filter_batch !== 'all') {
    $where .= " AND a.batch_year = ?";
    $params[] = $filter_batch;
}
if (!empty($search)) {
    $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR a.company LIKE ? OR a.current_position LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}

$filename = "NSS_Alumni_Directory_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, [
    'S.No',
    'Alumni Name',
    'Email Address',
    'Batch Year',
    'Current Position',
    'Company / Organization',
    'Mobile Number',
    'LinkedIn URL',
    'Account Status'
]);

$query = "
    SELECT u.name, u.email, u.status, a.batch_year, a.current_position, a.company, a.mobile, a.linkedin_url
    FROM users u
    LEFT JOIN alumni a ON u.id = a.user_id
    WHERE $where
    ORDER BY a.batch_year DESC, u.name ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sno = 1;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $sno++,
        $row['name'],
        $row['email'],
        $row['batch_year'] ?? 'N/A',
        $row['current_position'] ?? 'N/A',
        $row['company'] ?? 'N/A',
        $row['mobile'] ?? 'N/A',
        $row['linkedin_url'] ?? 'N/A',
        ucfirst($row['status'] ?? 'pending')
    ]);
}

if ($sno === 1) {
    fputcsv($output, ['', 'No alumni records found', '', '', '', '', '', '', '']);
}

fclose($output);
exit;
