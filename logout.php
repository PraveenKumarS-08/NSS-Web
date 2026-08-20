<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['name'])) {
    logUserActivity($pdo, $_SESSION['user_id'], $_SESSION['name'], $_SESSION['user_role'] ?? 'volunteer', 'Logout', 'User logged out of portal session');
}

logout();

header("Location: login.php");
exit;
?>
