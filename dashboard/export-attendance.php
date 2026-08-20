<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin('admin');

// Set download headers BEFORE any output
$filename = "NSS_Attendance_Report_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// CSV Header
fputcsv($output, [
    'S.No',
    'Student Name',
    'Email',
    'Register Number',
    'Department',
    'Year',
    'Activity Type',
    'Activity Title',
    'Hours',
    'Date'
]);

// Build a safe query using only confirmed columns
// attendance: id, user_id, event_id, type, attendance_date, category, description, hours, marked_at
// users: id, name, email
// volunteers: user_id, register_number, department, year
// events: id, title
try {
    $rows = $pdo->query("
        SELECT 
            u.name AS student_name,
            u.email AS student_email,
            v.register_number,
            v.department,
            v.year,
            a.type AS activity_type,
            a.category AS activity_category,
            a.description AS activity_desc,
            e.title AS event_title,
            a.hours,
            a.attendance_date,
            a.marked_at
        FROM attendance a
        INNER JOIN users u ON a.user_id = u.id
        LEFT JOIN volunteers v ON u.id = v.user_id
        LEFT JOIN events e ON a.event_id = e.id
        ORDER BY a.marked_at DESC, a.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $sno = 1;
    foreach ($rows as $r) {
        // Determine activity title
        $title = '';
        if (!empty($r['event_title'])) {
            $title = $r['event_title'];
        } elseif (!empty($r['activity_desc'])) {
            $title = $r['activity_desc'];
        } else {
            $title = ucfirst(str_replace('_', ' ', $r['activity_type'] ?? 'NSS Activity'));
        }

        // Determine date string
        $dateVal = $r['attendance_date'] ?? '';
        if (empty($dateVal) || $dateVal === '0000-00-00') {
            $dateVal = $r['marked_at'] ?? '';
        }
        $dateStr = (!empty($dateVal) && strtotime($dateVal)) ? date('d-M-Y', strtotime($dateVal)) : 'N/A';

        fputcsv($output, [
            $sno++,
            $r['student_name'] ?? 'N/A',
            $r['student_email'] ?? 'N/A',
            $r['register_number'] ?? 'N/A',
            $r['department'] ?? 'N/A',
            ($r['year'] ?? '-') . ' Year',
            ucfirst(str_replace('_', ' ', $r['activity_type'] ?? 'event')),
            $title,
            number_format((float)($r['hours'] ?? 0), 1) . ' hrs',
            $dateStr
        ]);
    }

    if ($sno === 1) {
        fputcsv($output, ['', 'No attendance records found', '', '', '', '', '', '', '', '']);
    }
} catch (Exception $e) {
    fputcsv($output, ['ERROR', 'Failed to fetch records: ' . $e->getMessage(), '', '', '', '', '', '', '', '']);
}

fclose($output);
exit;
