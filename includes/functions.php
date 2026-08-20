<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format datetime to human readable time ago
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $strTime = array("second", "minute", "hour", "day", "month", "year");
    $length = array("60","60","24","30","12","10");

    $currentTime = time();
    if($currentTime >= $timestamp) {
        $diff = time()- $timestamp;
        for($i = 0; $diff >= $length[$i] && $i < count($length)-1; $i++) {
            $diff = $diff / $length[$i];
        }
        $diff = round($diff);
        return $diff . " " . $strTime[$i] . "(s) ago";
    }
    return "just now";
}

/**
 * Format date
 */
function formatDate($datetime, $format = 'd M Y') {
    return date($format, strtotime($datetime));
}

/**
 * Calculate days until event
 */
function countdownDays($event_date) {
    $now = new DateTime();
    $event = new DateTime($event_date);
    
    if ($now > $event) {
        return 0; // Event passed
    }
    
    $interval = $now->diff($event);
    return $interval->days;
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $k => $v) {
            $input[$k] = sanitize($v);
        }
        return $input;
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash message helper
 */
function flashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = [
            'type' => $_SESSION['flash_type'] ?? 'info',
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Log user activity into user_activity_logs table
 */
function logUserActivity($pdo, $userId, $userName, $userRole = 'volunteer', $actionType = 'General', $description = '') {
    if (!isset($pdo) || !$pdo || !$userId) return;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, user_name, user_role, action_type, description, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $userName, $userRole, $actionType, $description, $ip]);
    } catch (Exception $e) {}
}

/**
 * Automatically delete finished events after completion date/time
 */
function autoDeleteFinishedEvents($pdo) {
    if (!isset($pdo) || !$pdo) return;
    try {
        // Query events that are marked completed or past their date
        $stmt = $pdo->query("
            SELECT id, title FROM events 
            WHERE status = 'completed' 
               OR (end_date IS NOT NULL AND end_date < NOW())
               OR (end_date IS NULL AND event_date < NOW() - INTERVAL 1 DAY)
        ");
        $finished_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($finished_events) {
            $ids = array_column($finished_events, 'id');
            // Never auto-delete system event 99 (Parade system event)
            $ids = array_diff($ids, [99]);

            if (!empty($ids)) {
                $id_list = implode(',', array_map('intval', $ids));
                
                // Reassign attendance records to System Event #99 so student credited hours remain intact
                $pdo->exec("UPDATE attendance SET event_id = 99 WHERE event_id IN ($id_list)");

                // Delete event registrations
                $pdo->exec("DELETE FROM event_registrations WHERE event_id IN ($id_list)");

                // Delete finished events
                $pdo->exec("DELETE FROM events WHERE id IN ($id_list)");
            }
        }
    } catch (Exception $e) {}
}

/**
 * Automatically update event statuses in DB and auto-delete finished events
 */
function autoUpdateEventStatuses($pdo) {
    if (!isset($pdo) || !$pdo) return;
    try {
        // Mark upcoming events as 'ongoing' if starting
        $pdo->exec("
            UPDATE events 
            SET status = 'ongoing' 
            WHERE status = 'upcoming' 
              AND event_date <= NOW() 
              AND event_date >= NOW() - INTERVAL 1 DAY
        ");
        
        // Mark events as 'completed' if ended
        $pdo->exec("
            UPDATE events 
            SET status = 'completed' 
            WHERE (status = 'upcoming' OR status = 'ongoing') 
              AND ((end_date IS NOT NULL AND end_date < NOW()) OR (end_date IS NULL AND event_date < NOW() - INTERVAL 1 DAY))
        ");

        // Automatically delete finished events
        autoDeleteFinishedEvents($pdo);
    } catch (Exception $e) {}
}

/**
 * Get NSS Logo HTML or IMG element
 */
function getNssLogoImg($size = 48, $rootPath = '') {
    $src = $rootPath . 'assets/images/nss-logo.png';
    return '<img src="' . htmlspecialchars($src) . '" alt="NSS Logo" style="width:' . intval($size) . 'px; height:' . intval($size) . 'px; border-radius:50%; object-fit:contain;">';
}
