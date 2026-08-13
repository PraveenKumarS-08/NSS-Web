<?php
require_once __DIR__ . '/Database.php';

/**
 * Tier 2: Business Logic - Authentication Service
 */
class AuthService {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Opens connection via Tier 3 __construct()
    }

    /**
     * User Login Process
     */
    public function login($email, $password, $role) {
        $user = $this->db->fetch("SELECT * FROM users WHERE email = ? AND role = ?", [$email, $role]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'No account found with this email for the selected role.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid password. Please try again.'];
        }

        if ($user['status'] === 'pending') {
            return ['success' => false, 'message' => 'Your registration is currently pending admin approval.'];
        }

        if ($user['status'] === 'rejected') {
            return ['success' => false, 'message' => 'Your registration request was rejected by NSS Admin.'];
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['name']      = $user['name'];
        $_SESSION['user_data'] = $user;

        return [
            'success' => true,
            'message' => 'Login successful!',
            'role'    => $user['role'],
            'user'    => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role']
            ]
        ];
    }

    /**
     * Register Volunteer
     */
    public function registerVolunteer($data) {
        $name            = trim($data['name'] ?? '');
        $email           = trim($data['email'] ?? '');
        $password        = $data['password'] ?? '';
        $department      = trim($data['department'] ?? '');
        $year            = $data['year'] ?? '';
        $blood_group     = $data['blood_group'] ?? '';
        $mobile          = trim($data['mobile'] ?? '');
        $register_number = trim($data['register_number'] ?? '');

        if (empty($name) || empty($email) || empty($password) || empty($register_number)) {
            return ['success' => false, 'message' => 'All required fields must be completed.'];
        }

        // Strong password regex requirement
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/', $password)) {
            return ['success' => false, 'message' => 'Password must be at least 8 chars with 1 uppercase, 1 lowercase, 1 number, and 1 special symbol.'];
        }

        // Check duplicate email
        $existing = $this->db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }

        // Check duplicate register number
        $existingReg = $this->db->fetch("SELECT id FROM volunteers WHERE register_number = ?", [$register_number]);
        if ($existingReg) {
            return ['success' => false, 'message' => 'A volunteer with this Register Number is already registered.'];
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'volunteer', 'pending')",
                [$name, $email, $hashed]
            );
            $userId = $this->db->lastInsertId();

            $this->db->execute(
                "INSERT INTO volunteers (user_id, department, year, blood_group, mobile, register_number) VALUES (?, ?, ?, ?, ?, ?)",
                [$userId, $department, $year, $blood_group, $mobile, $register_number]
            );

            $this->db->commit();
            return ['success' => true, 'message' => 'Volunteer registration successful! Please wait for NSS Admin approval.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check active session status
     */
    public function checkSession() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            return [
                'logged_in' => true,
                'user_id'   => $_SESSION['user_id'],
                'role'      => $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'volunteer',
                'name'      => $_SESSION['name'] ?? 'User'
            ];
        }
        return ['logged_in' => false];
    }

    /**
     * Logout
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }
}
