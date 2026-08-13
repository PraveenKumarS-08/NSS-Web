<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

// Handle mark as read / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'mark_read') {
        $msg_id = (int)$_POST['msg_id'];
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$msg_id]);
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $msg_id = (int)$_POST['msg_id'];
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$msg_id]);
    }
}

// Fetch messages
$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../css/dashboard.css">
<style>
    .msg-unread { background-color: rgba(26, 107, 60, 0.05); font-weight: bold; }
</style>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <h2>Contact Messages Inbox</h2>
        </header>

        <div class="dashboard-card glass-panel">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sender</th>
                            <th>Subject & Message</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($messages as $msg): ?>
                        <tr class="<?= !$msg['is_read'] ? 'msg-unread' : '' ?>">
                            <td style="white-space: nowrap;"><?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($msg['name']) ?></strong><br>
                                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="text-muted"><small><?= htmlspecialchars($msg['email']) ?></small></a>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($msg['subject']) ?></strong>
                                <p class="mb-0" style="font-size: 0.9em; color: #555;"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                            </td>
                            <td>
                                <?php if(!$msg['is_read']): ?>
                                    <span class="badge bg-warning text-dark">Unread</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Read</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php if(!$msg['is_read']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" title="Mark as Read"><i class="fas fa-envelope-open"></i></button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this message permanently?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(!$messages): ?>
                        <tr><td colspan="5" class="text-center">No messages received yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
