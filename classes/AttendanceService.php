<?php
require_once __DIR__ . '/Database.php';

/**
 * Tier 2: Business Logic - Attendance Service
 */
class AttendanceService {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Opens connection via Tier 3 __construct()
    }

    /**
     * Save Event Attendance
     */
    public function saveEventAttendance($eventId, $presentIds, $hoursMap) {
        if ($eventId <= 0) {
            return ['success' => false, 'message' => 'Please select an event.'];
        }

        $count = 0;
        foreach ($presentIds as $uid) {
            $uid = (int)$uid;
            $hrs = (float)($hoursMap[$uid] ?? 0.0);
            if ($hrs <= 0) $hrs = 4.0;

            $existing = $this->db->fetch("SELECT id FROM attendance WHERE user_id = ? AND event_id = ? AND type = 'event'", [$uid, $eventId]);
            if ($existing) {
                $this->db->execute("UPDATE attendance SET hours = ?, marked_at = CURRENT_TIMESTAMP WHERE id = ?", [$hrs, $existing['id']]);
            } else {
                $this->db->execute("INSERT INTO attendance (user_id, event_id, hours, type, attendance_date) VALUES (?, ?, ?, 'event', CURRENT_DATE)", [$uid, $eventId, $hrs]);
            }
            $count++;
        }

        return ['success' => true, 'message' => "Saved event attendance for $count volunteers!"];
    }

    /**
     * Save Parade Attendance
     */
    public function saveParadeAttendance($paradeDate, $paradeType, $presentIds, $hoursMap) {
        $count = 0;
        $defaultHrs = ($paradeType === 'parade') ? 1.0 : 0.5;

        foreach ($presentIds as $uid) {
            $uid = (int)$uid;
            $hrs = (float)($hoursMap[$uid] ?? $defaultHrs);

            $existing = $this->db->fetch("SELECT id FROM attendance WHERE user_id = ? AND attendance_date = ? AND type = ?", [$uid, $paradeDate, $paradeType]);
            if ($existing) {
                $this->db->execute("UPDATE attendance SET hours = ?, marked_at = CURRENT_TIMESTAMP WHERE id = ?", [$hrs, $existing['id']]);
            } else {
                // Pass NULL for event_id for parade/practice attendance
                $this->db->execute("INSERT INTO attendance (user_id, event_id, hours, type, attendance_date) VALUES (?, NULL, ?, ?, ?)", [$uid, $hrs, $paradeType, $paradeDate]);
            }
            $count++;
        }

        return ['success' => true, 'message' => "Saved parade attendance for $count volunteers on $paradeDate!"];
    }

    /**
     * Direct Custom Credit Hours
     */
    public function directCredit($userId, $hours, $reason) {
        if ($userId <= 0 || $hours <= 0) {
            return ['success' => false, 'message' => 'Please select a volunteer and enter valid hours (> 0).'];
        }

        // Pass NULL for event_id for custom credit
        $this->db->execute("INSERT INTO attendance (user_id, event_id, hours, type, attendance_date) VALUES (?, NULL, ?, 'special', CURRENT_DATE)", [$userId, $hours]);
        return ['success' => true, 'message' => "Directly credited $hours NSS hours to volunteer!"];
    }

    /**
     * Delete Attendance Record
     */
    public function deleteAttendance($attId) {
        $this->db->execute("DELETE FROM attendance WHERE id = ?", [$attId]);
        return ['success' => true, 'message' => 'Attendance record deleted.'];
    }

    /**
     * Get Attendance Log History for Admin
     */
    public function getRecentAttendanceLog($limit = 50) {
        return $this->db->fetchAll("
            SELECT a.*, u.name, v.register_number, v.department,
                   COALESCE(e.title, CASE WHEN a.type='parade' THEN 'Regular Parade' WHEN a.type='parade_practice' THEN 'Parade Practice' ELSE 'Special Duty' END) as event_title
            FROM attendance a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN volunteers v ON u.id = v.user_id
            LEFT JOIN events e ON a.event_id = e.id
            ORDER BY a.marked_at DESC LIMIT ?
        ", [(int)$limit]);
    }

    /**
     * Get Volunteer Attendance History Log
     */
    public function getVolunteerHistory($userId) {
        return $this->db->fetchAll("
            SELECT a.hours, a.marked_at, a.type,
                   COALESCE(e.title, CASE WHEN a.type='parade' THEN 'Regular Parade' WHEN a.type='parade_practice' THEN 'Parade Practice' ELSE 'Special Duty' END) as event_title,
                   COALESCE(e.location, 'College Campus') as location
            FROM attendance a
            LEFT JOIN events e ON a.event_id = e.id
            WHERE a.user_id = ?
            ORDER BY a.marked_at DESC
        ", [$userId]);
    }
}
