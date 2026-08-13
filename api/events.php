<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../classes/EventService.php';

$service = new EventService();
$action = $_REQUEST['action'] ?? 'list';

if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['user_id'] ?? 0;

switch ($action) {
    case 'list':
        echo json_encode($service->getEvents($userId));
        break;

    case 'detail':
        $id = (int)($_REQUEST['id'] ?? 0);
        echo json_encode($service->getEventDetail($id, $userId));
        break;

    case 'register_event':
        $eventId = (int)($_POST['event_id'] ?? 0);
        echo json_encode($service->registerForEvent($eventId, $userId, $_POST));
        break;

    case 'save_event':
        echo json_encode($service->saveEvent($_POST));
        break;

    case 'update_status':
        $eventId = (int)($_POST['event_id'] ?? 0);
        $status  = $_POST['status'] ?? 'upcoming';
        echo json_encode($service->updateStatus($eventId, $status));
        break;

    case 'delete_event':
        $eventId = (int)($_POST['event_id'] ?? 0);
        echo json_encode($service->deleteEvent($eventId));
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
