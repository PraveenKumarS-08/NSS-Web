<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../classes/RegistrationService.php';

$service = new RegistrationService();
$action = $_REQUEST['action'] ?? 'list';

switch ($action) {
    case 'list':
        $eventId    = (int)($_REQUEST['event_id'] ?? 0);
        $department = $_REQUEST['department'] ?? 'all';
        $search     = trim($_REQUEST['search'] ?? '');
        echo json_encode($service->getRegistrations($eventId, $department, $search));
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
