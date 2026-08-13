<?php
require_once __DIR__ . '/Database.php';

/**
 * Tier 2: Business Logic - Volunteer Service
 */
class VolunteerService {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Opens connection via Tier 3 __construct()
    }

    /**
     * Get Volunteers with Multi-Criteria Filters & Pagination
     */
    public function getVolunteers($filters) {
        $filter_status = $filters['status'] ?? 'all';
        $filter_dept   = $filters['department'] ?? 'all';
        $filter_year   = $filters['year'] ?? 'all';
        $filter_bg     = $filters['blood_group'] ?? 'all';
        $search        = trim($filters['search'] ?? '');
        $page          = max(1, (int)($filters['page'] ?? 1));
        $limit         = 25;
        $offset        = ($page - 1) * $limit;

        $where = "u.role = 'volunteer'";
        $params = [];

        if ($filter_status !== 'all') {
            $where .= " AND u.status = ?";
            $params[] = $filter_status;
        }
        if ($filter_dept !== 'all') {
            $where .= " AND v.department = ?";
            $params[] = $filter_dept;
        }
        if ($filter_year !== 'all') {
            $where .= " AND v.year = ?";
            $params[] = $filter_year;
        }
        if ($filter_bg !== 'all') {
            $where .= " AND v.blood_group = ?";
            $params[] = $filter_bg;
        }
        if (!empty($search)) {
            $where .= " AND (u.name LIKE ? OR v.department LIKE ? OR v.register_number LIKE ? OR u.email LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }

        $total_records = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM users u LEFT JOIN volunteers v ON u.id = v.user_id WHERE $where", $params);
        $total_pages   = max(1, ceil($total_records / $limit));

        $sql = "SELECT u.id, u.name, u.email, u.status, u.created_at, v.register_number, v.department, v.year, v.blood_group, v.mobile,
                       (SELECT COALESCE(SUM(hours), 0) FROM attendance WHERE user_id = u.id) as total_hours,
                       (SELECT COUNT(DISTINCT event_id) FROM attendance WHERE user_id = u.id AND event_id IS NOT NULL) as events_count
                FROM users u 
                LEFT JOIN volunteers v ON u.id = v.user_id 
                WHERE $where ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";

        $users = $this->db->fetchAll($sql, $params);

        return [
            'success'       => true,
            'volunteers'    => $users,
            'total_records' => $total_records,
            'total_pages'   => $total_pages,
            'current_page'  => $page
        ];
    }

    /**
     * Get Volunteer Profile by User ID
     */
    public function getProfile($userId) {
        $profile = $this->db->fetch("SELECT u.*, v.* FROM users u JOIN volunteers v ON u.id = v.user_id WHERE u.id = ?", [$userId]);
        if (!$profile) {
            return ['success' => false, 'message' => 'Volunteer profile not found.'];
        }

        $hours_served = (float)$this->db->fetchColumn("SELECT COALESCE(SUM(hours), 0) FROM attendance WHERE user_id = ?", [$userId]);
        $events_attended = (int)$this->db->fetchColumn("SELECT COUNT(DISTINCT event_id) FROM attendance WHERE user_id = ? AND event_id IS NOT NULL", [$userId]);
        $registered_count = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM event_registrations WHERE user_id = ?", [$userId]);

        return [
            'success'          => true,
            'profile'          => $profile,
            'hours_served'     => $hours_served,
            'events_attended'  => $events_attended,
            'registered_count' => $registered_count
        ];
    }

    /**
     * Update Volunteer Profile (Department Locked)
     */
    public function updateProfile($userId, $data) {
        $mobile      = trim($data['mobile'] ?? '');
        $blood_group = $data['blood_group'] ?? 'O+';
        $year        = $data['year'] ?? 'I';

        if (empty($mobile)) {
            return ['success' => false, 'message' => 'Mobile number is required.'];
        }

        $this->db->execute("UPDATE volunteers SET mobile = ?, blood_group = ?, year = ? WHERE user_id = ?", [$mobile, $blood_group, $year, $userId]);
        return ['success' => true, 'message' => 'Profile updated successfully. (Department remains locked by Admin)'];
    }

    /**
     * Admin: Approve or Reject Volunteer
     */
    public function updateStatus($userId, $status) {
        if (!in_array($status, ['approved', 'rejected', 'pending'])) {
            return ['success' => false, 'message' => 'Invalid status.'];
        }
        $this->db->execute("UPDATE users SET status = ? WHERE id = ?", [$status, $userId]);
        return ['success' => true, 'message' => "User status updated to $status."];
    }

    /**
     * Admin: Promote Volunteer to Alumni
     */
    public function promoteToAlumni($userId) {
        $this->db->beginTransaction();
        try {
            $this->db->execute("UPDATE users SET role = 'alumni' WHERE id = ?", [$userId]);

            $vol = $this->db->fetch("SELECT mobile FROM volunteers WHERE user_id = ?", [$userId]);
            $mobile = $vol['mobile'] ?? '';

            $existing = $this->db->fetch("SELECT id FROM alumni WHERE user_id = ?", [$userId]);
            if (!$existing) {
                $this->db->execute("INSERT INTO alumni (user_id, batch_year, mobile) VALUES (?, ?, ?)", [$userId, date('Y'), $mobile]);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Volunteer successfully promoted to Alumni role!'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Promotion failed: ' . $e->getMessage()];
        }
    }

    /**
     * Admin Dashboard: Department Aggregation Stats for Chart.js
     */
    public function getDepartmentStats() {
        $rows = $this->db->fetchAll("
            SELECT TRIM(v.department) as dept_name, COUNT(*) as count 
            FROM volunteers v 
            JOIN users u ON v.user_id = u.id 
            WHERE u.role = 'volunteer' AND u.status = 'approved' AND v.department IS NOT NULL AND v.department != '' 
            GROUP BY TRIM(v.department)
        ");

        $dept_stats = [];
        foreach ($rows as $r) {
            $dept_stats[$r['dept_name']] = (int)$r['count'];
        }
        return $dept_stats;
    }
}
