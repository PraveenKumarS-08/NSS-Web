<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../classes/MessageService.php';

$service = new MessageService();
$action = $_REQUEST['action'] ?? 'send';

switch ($action) {
    case 'send':
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        echo json_encode($service->sendMessage($name, $email, $subject, $message));
        break;

    case 'list':
        echo json_encode($service->getMessages());
        break;

    case 'mark_read':
        $id = (int)($_POST['id'] ?? 0);
        echo json_encode($service->markRead($id));
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
