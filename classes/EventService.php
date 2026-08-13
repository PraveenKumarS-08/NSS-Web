<?php
require_once __DIR__ . '/Database.php';

/**
 * Tier 2: Business Logic - Event Service
 */
class EventService {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Opens connection via Tier 3 __construct()
    }

    /**
     * Real-time auto-synchronization of event status with current date & time
     */
    private function autoSyncStatus() {
        try {
            // Mark upcoming events as 'ongoing' if event date is today (within 24h)
            $this->db->execute("
                UPDATE events 
                SET status = 'ongoing' 
                WHERE status = 'upcoming' AND event_date <= NOW() AND event_date >= NOW() - INTERVAL 1 DAY
            ");
            // Mark events as 'completed' if more than 24h passed
            $this->db->execute("
                UPDATE events 
                SET status = 'completed' 
                WHERE (status = 'upcoming' OR status = 'ongoing') AND event_date < NOW() - INTERVAL 1 DAY
            ");
        } catch (Exception $e) {}
    }

    /**
     * Get all events with optional volunteer registration status check
     */
    public function getEvents($userId = 0) {
        $this->autoSyncStatus();
        $now = time();
        $oneDay = 86400;

        if ($userId > 0) {
            $events = $this->db->fetchAll("
                SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) as reg_count,
                       (SELECT id FROM event_registrations r2 WHERE r2.event_id = e.id AND r2.user_id = ?) as user_registered_id
                FROM events e 
                ORDER BY e.event_date DESC
            ", [$userId]);
        } else {
            $events = $this->db->fetchAll("
                SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) as reg_count,
                       0 as user_registered_id
                FROM events e 
                ORDER BY e.event_date DESC
            ");
        }

        foreach ($events as &$ev) {
            $ts = strtotime($ev['event_date']);
            $rawStatus = strtolower($ev['status'] ?? 'upcoming');

            if ($rawStatus === 'postponed' || $rawStatus === 'cancelled') {
                $ev['calculated_status'] = $rawStatus;
            } elseif ($ts > $now) {
                $ev['calculated_status'] = 'upcoming';
            } elseif ($ts <= $now && $ts >= ($now - $oneDay)) {
                $ev['calculated_status'] = 'ongoing';
            } else {
                $ev['calculated_status'] = 'completed';
            }
            $ev['user_registered'] = !empty($ev['user_registered_id']);
        }

        return ['success' => true, 'events' => $events];
    }

    /**
     * Get single event detail
     */
    public function getEventDetail($eventId, $userId = 0) {
        $this->autoSyncStatus();
        $event = $this->db->fetch("
            SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) as reg_count
            FROM events e WHERE e.id = ?
        ", [$eventId]);

        if (!$event) {
            return ['success' => false, 'message' => 'Event not found.'];
        }

        $now = time();
        $ts = strtotime($event['event_date']);
        $rawStatus = strtolower($event['status'] ?? 'upcoming');

        if ($rawStatus === 'postponed' || $rawStatus === 'cancelled') {
            $event['calculated_status'] = $rawStatus;
        } elseif ($ts > $now) {
            $event['calculated_status'] = 'upcoming';
        } elseif ($ts <= $now && $ts >= ($now - 86400)) {
            $event['calculated_status'] = 'ongoing';
        } else {
            $event['calculated_status'] = 'completed';
        }

        $userReg = null;
        if ($userId > 0) {
            $userReg = $this->db->fetch("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?", [$eventId, $userId]);
        }

        return [
            'success' => true,
            'event'   => $event,
            'user_registered' => !empty($userReg),
            'registration_info' => $userReg
        ];
    }

    /**
     * Register Volunteer for Event
     */
    public function registerForEvent($eventId, $userId, $data) {
        if ($userId <= 0 || $eventId <= 0) {
            return ['success' => false, 'message' => 'Invalid parameters or user not logged in.'];
        }

        $student_mobile = trim($data['student_mobile'] ?? '');
        $parent_mobile  = trim($data['parent_mobile'] ?? '');
        $address        = trim($data['address'] ?? '');
        $age            = (int)($data['age'] ?? 0);
        $year           = $data['year'] ?? '';
        $department     = $data['department'] ?? '';
        $remarks        = trim($data['remarks'] ?? '');

        try {
            $this->db->execute("
                INSERT INTO event_registrations (event_id, user_id, student_mobile, parent_mobile, address, age, year, department, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$eventId, $userId, $student_mobile, $parent_mobile, $address, $age, $year, $department, $remarks]);

            return ['success' => true, 'message' => 'Successfully registered for event!'];
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                return ['success' => true, 'message' => 'You are already registered for this event.'];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Admin: Create or Update Event
     */
    public function saveEvent($data) {
        $id          = (int)($data['id'] ?? 0);
        $title       = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $event_date  = $data['event_date'] ?? '';
        $location    = trim($data['location'] ?? '');
        $category    = $data['category'] ?? 'General';
        $status      = $data['status'] ?? 'upcoming';
        $image       = trim($data['image'] ?? '');

        if (empty($title) || empty($event_date) || empty($location)) {
            return ['success' => false, 'message' => 'Title, Date & Time, and Location are required.'];
        }

        if ($id > 0) {
            $this->db->execute("
                UPDATE events SET title=?, description=?, event_date=?, location=?, category=?, status=?, image=? WHERE id=?
            ", [$title, $description, $event_date, $location, $category, $status, $image, $id]);
            return ['success' => true, 'message' => 'Event updated successfully.'];
        } else {
            $this->db->execute("
                INSERT INTO events (title, description, event_date, location, category, status, image)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [$title, $description, $event_date, $location, $category, $status, $image]);
            return ['success' => true, 'message' => 'New event created successfully.'];
        }
    }

    /**
     * Admin: Update Event Status
     */
    public function updateStatus($eventId, $status) {
        $this->db->execute("UPDATE events SET status = ? WHERE id = ?", [$status, $eventId]);
        return ['success' => true, 'message' => "Event status updated to $status."];
    }

    /**
     * Admin: Delete Event
     */
    public function deleteEvent($eventId) {
        $this->db->execute("DELETE FROM events WHERE id = ?", [$eventId]);
        return ['success' => true, 'message' => 'Event deleted successfully.'];
    }
}
