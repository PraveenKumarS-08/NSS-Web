<?php
require_once __DIR__ . '/Database.php';

/**
 * Tier 2: Business Logic - Contact Messages Service
 */
class MessageService {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Opens connection via Tier 3 __construct()
    }

    public function sendMessage($name, $email, $subject, $message) {
        if (empty($name) || empty($email) || empty($message)) {
            return ['success' => false, 'message' => 'Name, email, and message are required.'];
        }
        $this->db->execute("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)", [$name, $email, $subject, $message]);
        return ['success' => true, 'message' => 'Your message has been sent to NSS Admin successfully!'];
    }

    public function getMessages() {
        $messages = $this->db->fetchAll("SELECT * FROM contact_messages ORDER BY created_at DESC");
        return ['success' => true, 'messages' => $messages];
    }

    public function markRead($id) {
        $this->db->execute("UPDATE contact_messages SET is_read = 1 WHERE id = ?", [$id]);
        return ['success' => true, 'message' => 'Message marked as read.'];
    }
}
