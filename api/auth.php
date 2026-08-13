<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../classes/AuthService.php';

$auth = new AuthService();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'login':
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'volunteer';
        echo json_encode($auth->login($email, $password, $role));
        break;

    case 'register_volunteer':
        echo json_encode($auth->registerVolunteer($_POST));
        break;

    case 'check_session':
        echo json_encode($auth->checkSession());
        break;

    case 'logout':
        echo json_encode($auth->logout());
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
