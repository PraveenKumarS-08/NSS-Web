<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login to access a page, optionally checking for a specific role
 */
function requireLogin($role = null) {
    // Detect how deep in the directory tree we are
    $isInDashboard = strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false;
    $root = $isInDashboard ? '../' : '';

    if (!isLoggedIn()) {
        header("Location: {$root}login.php");
        exit;
    }

    if ($role !== null && currentRole() !== $role) {
        // Wrong role — send back to home
        header("Location: {$root}index.php");
        exit;
    }
}

/**
 * Get current user data from session
 */
function currentUser() {
    return isLoggedIn() ? $_SESSION['user_data'] : null;
}

/**
 * Get current user role
 */
function currentRole() {
    return isLoggedIn() ? $_SESSION['user_role'] : null;
}

/**
 * Check if the user's account is approved
 */
function isApproved() {
    return isLoggedIn() && isset($_SESSION['user_data']['status']) && $_SESSION['user_data']['status'] === 'approved';
}

/**
 * Attempt to log in a user
 */
function login($email, $password, $role, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_data'] = [
            'name' => $user['name'],
            'email' => $user['email'],
            'status' => $user['status'],
            'profile_photo' => $user['profile_photo']
        ];
        
        return true;
    }
    return false;
}

/**
 * Log out the user
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session.
    session_destroy();
}

/**
 * Generate CSRF token
 */
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}
?>
