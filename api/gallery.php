<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../classes/GalleryService.php';

$service = new GalleryService();
$action = $_REQUEST['action'] ?? 'list';

switch ($action) {
    case 'list':
        $category = $_REQUEST['category'] ?? 'all';
        $year     = $_REQUEST['year'] ?? 'all';
        echo json_encode($service->getGalleryPhotos($category, $year));
        break;

    case 'upload':
        $title    = trim($_POST['title'] ?? '');
        $category = $_POST['category'] ?? 'General';
        $year     = $_POST['year'] ?? date('Y');
        $imgPath  = trim($_POST['image_path'] ?? '');
        echo json_encode($service->addPhoto($title, $imgPath, $category, $year));
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        echo json_encode($service->deletePhoto($id));
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
