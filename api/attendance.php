<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../classes/AttendanceService.php';

$service = new AttendanceService();
$action = $_REQUEST['action'] ?? 'log';

if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['user_id'] ?? 0;

switch ($action) {
    case 'save_event_attendance':
        $eventId    = (int)($_POST['event_id'] ?? 0);
        $presentIds = $_POST['present'] ?? [];
        $hoursMap   = $_POST['hours_map'] ?? [];
        echo json_encode($service->saveEventAttendance($eventId, $presentIds, $hoursMap));
        break;

    case 'save_parade_attendance':
        $paradeDate = $_POST['parade_date'] ?? date('Y-m-d');
        $paradeType = $_POST['parade_type'] ?? 'parade';
        $presentIds = $_POST['present'] ?? [];
        $hoursMap   = $_POST['hours_map'] ?? [];
        echo json_encode($service->saveParadeAttendance($paradeDate, $paradeType, $presentIds, $hoursMap));
        break;

    case 'direct_credit':
        $targetId = (int)($_POST['user_id'] ?? 0);
        $hours    = (float)($_POST['custom_hours'] ?? 0);
        $reason   = trim($_POST['credit_reason'] ?? '');
        echo json_encode($service->directCredit($targetId, $hours, $reason));
        break;

    case 'delete':
        $attId = (int)($_POST['id'] ?? 0);
        echo json_encode($service->deleteAttendance($attId));
        break;

    case 'log':
        echo json_encode(['success' => true, 'log' => $service->getRecentAttendanceLog()]);
        break;

    case 'my_history':
        echo json_encode(['success' => true, 'history' => $service->getVolunteerHistory($userId)]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
