<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

// Filtering and Pagination
$search      = trim($_GET['search'] ?? '');
$action_type = $_GET['action_type'] ?? 'all';
$page        = max(1, (int)($_GET['page'] ?? 1));
$limit       = 25;
$offset      = ($page - 1) * $limit;

$where = "1=1";
$params = [];

if ($action_type !== 'all') {
    $where .= " AND action_type = ?";
    $params[] = $action_type;
}

if (!empty($search)) {
    $where .= " AND (user_name LIKE ? OR description LIKE ? OR ip_address LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
}

// Count total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM user_activity_logs WHERE $where");
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();
$total_pages   = max(1, ceil($total_records / $limit));

// Fetch records
$data_sql = "
    SELECT * FROM user_activity_logs 
    WHERE $where 
    ORDER BY created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct action types
$action_types = $pdo->query("SELECT DISTINCT action_type FROM user_activity_logs ORDER BY action_type ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Student Activity Logs | NSS Admin';
require_once '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-history text-primary"></i> Student Activity Logs & Audit Trail</h2>
                <small class="text-muted">Track real-time student portal logins, profile updates, and event actions</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge status-approved" style="font-size:0.85rem; padding: 6px 14px;">
                    <i class="fas fa-user-shield"></i> Logged in as: <strong><?= htmlspecialchars($_SESSION['name'] ?? 'NSS Admin') ?></strong>
                </span>
                <a href="export-activity-logs.php?<?= http_build_query($_GET) ?>" class="btn btn-outline text-primary" title="Export Activity CSV">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </header>

        <!-- Filters Form -->
        <div class="dashboard-card glass-panel mb-4">
            <form method="GET" class="d-flex gap-3 align-items-center flex-wrap">
                <div style="flex:2; min-width:220px;">
                    <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">SEARCH LOGS</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by student name, description, IP..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div style="flex:1; min-width:180px;">
                    <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">ACTION TYPE</label>
                    <select name="action_type" class="form-control" onchange="this.form.submit()">
                        <option value="all">All Action Types</option>
                        <?php foreach ($action_types as $act): ?>
                            <option value="<?= htmlspecialchars($act) ?>" <?= $action_type === $act ? 'selected' : '' ?>><?= htmlspecialchars($act) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="align-self:flex-end; display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="admin-activity-logs.php" class="btn btn-outline" title="Reset Filters"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <!-- Activity Table Card -->
        <div class="dashboard-card glass-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 style="margin:0;"><i class="fas fa-list-alt text-accent"></i> Real-time Activity Logs (<?= number_format($total_records) ?> entries)</h3>
            </div>

            <div class="table-responsive">
                <table class="table" style="font-size:0.9rem;">
                    <thead>
                        <tr>
                            <th style="width:180px;">Timestamp</th>
                            <th style="width:200px;">Student / User</th>
                            <th style="width:160px;">Action Type</th>
                            <th>Description</th>
                            <th style="width:130px;">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $badge_class = 'bg-secondary';
                            $act = strtolower($log['action_type']);
                            if (strpos($act, 'login') !== false) $badge_class = 'bg-success';
                            elseif (strpos($act, 'logout') !== false) $badge_class = 'bg-dark';
                            elseif (strpos($act, 'event') !== false) $badge_class = 'bg-primary';
                            elseif (strpos($act, 'profile') !== false) $badge_class = 'bg-warning text-dark';
                        ?>
                        <tr>
                            <td>
                                <strong style="color:#1b365d;"><?= date('d M Y', strtotime($log['created_at'])) ?></strong><br>
                                <small class="text-muted"><i class="fas fa-clock"></i> <?= date('h:i:s A', strtotime($log['created_at'])) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($log['user_name']) ?></strong><br>
                                <span class="badge bg-secondary" style="font-size:0.7rem;"><?= ucfirst($log['user_role'] ?? 'volunteer') ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $badge_class ?>" style="padding:5px 10px; border-radius:12px; font-weight:700;">
                                    <?= htmlspecialchars($log['action_type']) ?>
                                </span>
                            </td>
                            <td style="color:#334155; line-height:1.4;">
                                <?= htmlspecialchars($log['description']) ?>
                            </td>
                            <td>
                                <code style="font-size:0.8rem; background:#f1f5f9; padding:2px 6px; border-radius:4px;"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></code>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$logs): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No student activity logged yet matching your filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3" style="border-top:1px solid #e2e8f0;">
                <small class="text-muted">Showing page <?= $page ?> of <?= $total_pages ?> (<?= number_format($total_records) ?> entries)</small>
                <div class="pagination d-flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-sm btn-outline">&laquo; Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-sm btn-outline">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
