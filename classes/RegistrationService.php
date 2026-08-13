<?php
require_once __DIR__ . '/Database.php';

/**
 * Tier 2: Business Logic - Event Registrations Service
 */
class RegistrationService {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Opens connection via Tier 3 __construct()
    }

    /**
     * Get Event Registrations Directory with Filters
     */
    public function getRegistrations($eventId = 0, $department = 'all', $search = '') {
        $where = "1=1";
        $params = [];

        if ($eventId > 0) {
            $where .= " AND r.event_id = ?";
            $params[] = $eventId;
        }

        if ($department !== 'all') {
            $where .= " AND (r.department = ? OR v.department = ?)";
            $params[] = $department;
            $params[] = $department;
        }

        if (!empty($search)) {
            $where .= " AND (u.name LIKE ? OR v.register_number LIKE ? OR r.student_mobile LIKE ? OR r.parent_mobile LIKE ? OR r.address LIKE ?)";
            $sp = "%$search%";
            $params = array_merge($params, [$sp, $sp, $sp, $sp, $sp]);
        }

        $sql = "
            SELECT r.*, e.title as event_title, e.event_date, e.category as event_category,
                   u.name as student_name, u.email as student_email,
                   v.register_number as vol_regno, v.department as vol_dept, v.mobile as vol_mobile
            FROM event_registrations r
            JOIN events e ON r.event_id = e.id
            JOIN users u ON r.user_id = u.id
            LEFT JOIN volunteers v ON u.id = v.user_id
            WHERE $where
            ORDER BY r.registered_at DESC
        ";

        $registrations = $this->db->fetchAll($sql, $params);
        return ['success' => true, 'registrations' => $registrations];
    }
}
