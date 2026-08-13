<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../classes/VolunteerService.php';

$service = new VolunteerService();
$action = $_REQUEST['action'] ?? 'list';

if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['user_id'] ?? 0;

switch ($action) {
    case 'list':
        echo json_encode($service->getVolunteers($_REQUEST));
        break;

    case 'profile':
        $targetId = (int)($_REQUEST['user_id'] ?? $userId);
        echo json_encode($service->getProfile($targetId));
        break;

    case 'update_profile':
        echo json_encode($service->updateProfile($userId, $_POST));
        break;

    case 'update_status':
        $targetId = (int)($_POST['user_id'] ?? 0);
        $status   = $_POST['status'] ?? 'pending';
        echo json_encode($service->updateStatus($targetId, $status));
        break;

    case 'promote_alumni':
        $targetId = (int)($_POST['user_id'] ?? 0);
        echo json_encode($service->promoteToAlumni($targetId));
        break;

    case 'dept_stats':
        echo json_encode(['success' => true, 'dept_stats' => $service->getDepartmentStats()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
