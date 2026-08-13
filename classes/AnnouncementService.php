<?php
require_once __DIR__ . '/Database.php';

/**
 * Tier 2: Business Logic - Announcement Service
 */
class AnnouncementService {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Opens connection via Tier 3 __construct()
    }

    public function getAnnouncements($role = 'all') {
        if ($role === 'admin') {
            $announcements = $this->db->fetchAll("SELECT * FROM announcements ORDER BY created_at DESC");
        } else {
            $announcements = $this->db->fetchAll("SELECT * FROM announcements WHERE target_role IN ('all', ?) ORDER BY created_at DESC", [$role]);
        }
        return ['success' => true, 'announcements' => $announcements];
    }

    public function createAnnouncement($title, $content, $target_role = 'all') {
        if (empty($title) || empty($content)) {
            return ['success' => false, 'message' => 'Title and content are required.'];
        }
        $this->db->execute("INSERT INTO announcements (title, content, target_role) VALUES (?, ?, ?)", [$title, $content, $target_role]);
        return ['success' => true, 'message' => 'Announcement posted successfully.'];
    }

    public function deleteAnnouncement($id) {
        $this->db->execute("DELETE FROM announcements WHERE id = ?", [$id]);
        return ['success' => true, 'message' => 'Announcement deleted.'];
    }
}
