<?php
require_once __DIR__ . '/Database.php';

/**
 * Tier 2: Business Logic - Gallery Service
 */
class GalleryService {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Opens connection via Tier 3 __construct()
    }

    public function getGalleryPhotos($category = 'all', $year = 'all') {
        $where = "1=1";
        $params = [];

        if ($category !== 'all') {
            $where .= " AND category = ?";
            $params[] = $category;
        }

        if ($year !== 'all') {
            $where .= " AND year = ?";
            $params[] = $year;
        }

        $photos = $this->db->fetchAll("SELECT * FROM gallery WHERE $where ORDER BY created_at DESC", $params);
        return ['success' => true, 'photos' => $photos];
    }

    public function addPhoto($title, $image_path, $category = 'General', $year = null) {
        if (empty($year)) $year = date('Y');
        $this->db->execute("INSERT INTO gallery (title, image_path, category, year) VALUES (?, ?, ?, ?)", [$title, $image_path, $category, $year]);
        return ['success' => true, 'message' => 'Photo added to gallery.'];
    }

    public function deletePhoto($photoId) {
        $this->db->execute("DELETE FROM gallery WHERE id = ?", [$photoId]);
        return ['success' => true, 'message' => 'Photo deleted from gallery.'];
    }
}
