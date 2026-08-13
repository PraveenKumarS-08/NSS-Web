<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../classes/AnnouncementService.php';

$service = new AnnouncementService();
$action = $_REQUEST['action'] ?? 'list';

if (session_status() === PHP_SESSION_NONE) session_start();
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'all';

switch ($action) {
    case 'list':
        echo json_encode($service->getAnnouncements($userRole));
        break;

    case 'create':
        $title      = trim($_POST['title'] ?? '');
        $content    = trim($_POST['content'] ?? '');
        $targetRole = $_POST['target_role'] ?? 'all';
        echo json_encode($service->createAnnouncement($title, $content, $targetRole));
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        echo json_encode($service->deleteAnnouncement($id));
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
