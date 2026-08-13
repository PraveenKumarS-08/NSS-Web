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
 * Automatically update event statuses in DB based on current date & time
 * Note: Never sets status to 'cancelled' or 'postponed' automatically.
 */
function autoUpdateEventStatuses($pdo) {
    if (!isset($pdo) || !$pdo) return;
    try {
        // Mark upcoming events as 'ongoing' if event_date is today / starting (within 24 hours)
        $pdo->exec("
            UPDATE events 
            SET status = 'ongoing' 
            WHERE status = 'upcoming' 
              AND event_date <= NOW() 
              AND event_date >= NOW() - INTERVAL 1 DAY
        ");
        
        // Mark events as 'completed' if more than 24 hours passed since event_date
        $pdo->exec("
            UPDATE events 
            SET status = 'completed' 
            WHERE (status = 'upcoming' OR status = 'ongoing') 
              AND event_date < NOW() - INTERVAL 1 DAY
        ");
    } catch (Exception $e) {}
}

/**
 * Get NSS Logo HTML or IMG element
 */
function getNssLogoImg($size = 48, $rootPath = '') {
    $src = $rootPath . 'assets/images/nss-logo.png';
    return '<img src="' . htmlspecialchars($src) . '" alt="NSS Logo" style="width:' . intval($size) . 'px; height:' . intval($size) . 'px; border-radius:50%; object-fit:contain;">';
}
