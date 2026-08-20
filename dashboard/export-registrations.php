<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin('admin');

// Filter parameters
$filter_event = (int)($_GET['event_id'] ?? 0);
$filter_dept = $_GET['department'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = "1=1";
$params = [];

if ($filter_event > 0) {
    $where .= " AND r.event_id = ?";
    $params[] = $filter_event;
}

if ($filter_dept !== 'all') {
    $where .= " AND (r.department = ? OR v.department = ?)";
    $params[] = $filter_dept;
    $params[] = $filter_dept;
}

if (!empty($search)) {
    $where .= " AND (u.name LIKE ? OR v.register_number LIKE ? OR r.student_mobile LIKE ? OR r.parent_mobile LIKE ? OR r.address LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

$filename = "NSS_Event_Registrations_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

fputcsv($output, [
    'S.No',
    'Event Title',
    'Event Category',
    'Event Date',
    'Student Name',
    'Student Email',
    'Register Number',
    'Department',
    'Student Mobile',
    'Parent Mobile',
    'Age',
    'Year',
    'Address',
    'Remarks',
    'Registered On'
]);

try {
    $stmt = $pdo->prepare("
        SELECT r.*, e.title as event_title, e.event_date, e.category as event_category,
               u.name as student_name, u.email as student_email,
               v.register_number as vol_regno, v.department as vol_dept, v.mobile as vol_mobile
        FROM event_registrations r
        JOIN events e ON r.event_id = e.id
        JOIN users u ON r.user_id = u.id
        LEFT JOIN volunteers v ON u.id = v.user_id
        WHERE $where
        ORDER BY r.registered_at DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sno = 1;
    foreach ($rows as $r) {
        $dept = !empty($r['department']) ? $r['department'] : ($r['vol_dept'] ?? 'N/A');
        $regno = !empty($r['vol_regno']) ? $r['vol_regno'] : 'N/A';
        $studentMob = !empty($r['student_mobile']) ? $r['student_mobile'] : ($r['vol_mobile'] ?? 'N/A');
        $eventDate = (!empty($r['event_date']) && strtotime($r['event_date'])) ? date('d-M-Y h:i A', strtotime($r['event_date'])) : 'N/A';
        $regDate = (!empty($r['registered_at']) && strtotime($r['registered_at'])) ? date('d-M-Y h:i A', strtotime($r['registered_at'])) : 'N/A';

        fputcsv($output, [
            $sno++,
            $r['event_title'] ?? 'N/A',
            $r['event_category'] ?? 'N/A',
            $eventDate,
            $r['student_name'] ?? 'N/A',
            $r['student_email'] ?? 'N/A',
            $regno,
            $dept,
            $studentMob,
            $r['parent_mobile'] ?? 'N/A',
            $r['age'] ?? 'N/A',
            ($r['year'] ?? 'N/A') . ' Year',
            $r['address'] ?? 'N/A',
            $r['remarks'] ?? '',
            $regDate
        ]);
    }

    if ($sno === 1) {
        fputcsv($output, ['', 'No event registrations found', '', '', '', '', '', '', '', '', '', '', '', '', '']);
    }
} catch (Exception $e) {
    fputcsv($output, ['ERROR', 'Failed: ' . $e->getMessage()]);
}

fclose($output);
exit;
