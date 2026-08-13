<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $target_role = $_POST['target_role'];
        
        if (empty($title) || empty($content)) {
            $error = "Title and content are required.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, target_role, created_by) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$title, $content, $target_role, $_SESSION['user_id']])) {
                $success = "Announcement posted successfully.";
            } else {
                $error = "Failed to post announcement.";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $ann_id = (int)$_POST['ann_id'];
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$ann_id]);
        $success = "Announcement deleted.";
    }
}

// Fetch announcements
$stmt = $pdo->query("SELECT a.*, u.name as author_name FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC");
$announcements = $stmt->fetchAll();

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../css/dashboard.css">

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <h2>Manage Announcements</h2>
        </header>

        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="dashboard-card glass-panel mb-4">
            <h3>Post New Announcement</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Content *</label>
                    <textarea name="content" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-row" style="display:flex; gap:15px; align-items:flex-end;">
                    <div class="form-group" style="flex:1;">
                        <label>Target Audience *</label>
                        <select name="target_role" class="form-control">
                            <option value="all">Everyone (Public & Users)</option>
                            <option value="volunteers">Volunteers Only</option>
                            <option value="alumni">Alumni Only</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary px-5">Post Announcement</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="dashboard-card glass-panel">
            <h3>Recent Announcements</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Target</th>
                            <th>Announcement Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($announcements as $ann): ?>
                        <tr>
                            <td style="white-space: nowrap;"><?= date('d M Y', strtotime($ann['created_at'])) ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst($ann['target_role']) ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($ann['title']) ?></strong>
                                <p class="mb-0 text-muted" style="font-size: 0.9em;"><?= nl2br(htmlspecialchars(substr($ann['content'], 0, 100))) ?><?= strlen($ann['content']) > 100 ? '...' : '' ?></p>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this announcement?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="ann_id" value="<?= $ann['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(!$announcements): ?>
                        <tr><td colspan="4" class="text-center">No announcements found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
