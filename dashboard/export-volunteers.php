<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

// Fetch all approved volunteers
$stmt = $pdo->query("
    SELECT u.name, u.email, u.status, u.created_at,
           v.register_number, v.department, v.year, v.blood_group, v.mobile
    FROM users u
    LEFT JOIN volunteers v ON u.id = v.user_id
    WHERE u.role = 'volunteer'
    ORDER BY u.created_at DESC
");
$volunteers = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=NSS_Volunteers_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header row
fputcsv($output, [
    'Name', 'Register Number', 'Department', 'Year',
    'Blood Group', 'Mobile', 'Email', 'Status', 'Registered On'
]);

// Data rows
foreach ($volunteers as $v) {
    fputcsv($output, [
        $v['name'],
        $v['register_number'] ?? 'N/A',
        $v['department'] ?? 'N/A',
        $v['year'] ?? 'N/A',
        $v['blood_group'] ?? 'N/A',
        $v['mobile'] ?? 'N/A',
        $v['email'],
        ucfirst($v['status']),
        date('d-m-Y', strtotime($v['created_at']))
    ]);
}

fclose($output);
exit;
