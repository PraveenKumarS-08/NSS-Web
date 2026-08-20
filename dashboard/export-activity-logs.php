<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$search      = trim($_GET['search'] ?? '');
$action_type = $_GET['action_type'] ?? 'all';

$where = "1=1";
$params = [];

if ($action_type !== 'all') {
    $where .= " AND action_type = ?";
    $params[] = $action_type;
}

if (!empty($search)) {
    $where .= " AND (user_name LIKE ? OR description LIKE ? OR ip_address LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
}

$stmt = $pdo->prepare("SELECT * FROM user_activity_logs WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "student_activity_logs_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Log ID', 'Timestamp', 'User Name', 'Role', 'Action Type', 'Description', 'IP Address']);

foreach ($logs as $l) {
    fputcsv($output, [
        $l['id'],
        $l['created_at'],
        $l['user_name'],
        $l['user_role'],
        $l['action_type'],
        $l['description'],
        $l['ip_address']
    ]);
}

fclose($output);
exit;
