<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

// Filter parameters
$filter_event = (int)($_GET['event_id'] ?? 0);
$filter_dept = $_GET['department'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = "1=1";
$params = [];

if ($filter_event > 0) {
    $where .= " AND r.event_id = ?";
    $params[] = $filter_event;
}

if ($filter_dept !== 'all') {
    $where .= " AND (r.department = ? OR v.department = ?)";
    $params[] = $filter_dept;
    $params[] = $filter_dept;
}

if (!empty($search)) {
    $where .= " AND (u.name LIKE ? OR v.register_number LIKE ? OR r.student_mobile LIKE ? OR r.parent_mobile LIKE ? OR r.address LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

// Fetch Registrations
$stmt = $pdo->prepare("
    SELECT r.*, e.title as event_title, e.event_date, e.category as event_category,
           u.name as student_name, u.email as student_email, u.profile_photo,
           v.register_number as vol_regno, v.department as vol_dept, v.mobile as vol_mobile
    FROM event_registrations r
    JOIN events e ON r.event_id = e.id
    JOIN users u ON r.user_id = u.id
    LEFT JOIN volunteers v ON u.id = v.user_id
    WHERE $where
    ORDER BY r.registered_at DESC
");
$stmt->execute($params);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Events List for Filter
$events_list = $pdo->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();

$departments = [
    "Civil Engineering (Shift I)",
    "Civil Engineering Tamil Medium (Shift II)",
    "Mechanical Engineering (Shift I)",
    "Mechanical Engineering Tamil Medium (Shift II)",
    "Electrical and Electronics Engineering (Shift I/II)",
    "Computer Engineering (Shift I)",
    "Mechanical Engineering (Sandwich)",
    "Plastic Technology (Sandwich)",
    "Polymer Technology (Sandwich)",
    "Web Designing (Shift II)",
    "Logistic Technology (Shift I)",
    "Civil Engineering (Part-Time)",
    "Mechanical Engineering (Part-Time)",
    "Electrical and Electronics Engineering (Part-Time)",
    "Robotics",
    "Printing Technology"
];
$pageTitle = 'Event Registrations Directory | NSS Admin';
require_once '../includes/header.php';
?>

<style>
/* Scoped styles for Event Registrations page alignment */
.reg-filter-grid {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1.5fr auto;
    gap: 14px;
    align-items: end;
}
.reg-table th, .reg-table td {
    padding: 0.75rem 0.9rem;
    font-size: 0.88rem;
    vertical-align: top;
    white-space: nowrap;
}
.reg-table td.wrap-cell {
    white-space: normal;
    max-width: 220px;
    word-break: break-word;
}
.reg-table .text-muted { color: #64748b; }
.reg-table .badge { font-size: 0.72rem; padding: 3px 8px; border-radius: 6px; }
@media (max-width: 900px) {
    .reg-filter-grid { grid-template-columns: 1fr; }
}
</style>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <h2><i class="fas fa-clipboard-list text-primary"></i> Event Registrations Directory</h2>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge status-approved" style="font-size:0.85rem; padding:6px 14px;"><i class="fas fa-user-shield"></i> Logged in as: <strong><?= htmlspecialchars($_SESSION['name'] ?? 'NSS Admin') ?></strong></span>
                <a href="export-registrations.php?<?= http_build_query($_GET) ?>" class="btn btn-primary btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
                <span class="badge status-approved" style="font-size:0.85rem; padding:6px 14px;"><i class="fas fa-list-check"></i> Total Enrolled: <?= count($registrations) ?></span>
            </div>
        </header>

        <!-- Filter Controls Bar -->
        <div class="dashboard-card glass-panel mb-4" style="padding:1.25rem 1.5rem;">
            <form method="GET">
                <div class="reg-filter-grid">
                    <div class="form-group">
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">SEARCH VOLUNTEER</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, Reg No, Mobile, Address..." value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="form-group">
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">FILTER BY EVENT</label>
                        <select name="event_id" class="form-control" onchange="this.form.submit()">
                            <option value="0">All Events & Camps</option>
                            <?php foreach ($events_list as $ev): ?>
                                <option value="<?= $ev['id'] ?>" <?= $filter_event == $ev['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ev['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">DEPARTMENT</label>
                        <select name="department" class="form-control" onchange="this.form.submit()">
                            <option value="all">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" <?= $filter_dept === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="display:flex; gap:8px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                        <a href="admin-registrations.php" class="btn btn-outline" title="Reset Filters"><i class="fas fa-undo"></i></a>
                        <a href="export-registrations.php?<?= http_build_query($_GET) ?>" class="btn btn-outline text-success" title="Export CSV"><i class="fas fa-download"></i> CSV</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Registrations Table -->
        <div class="dashboard-card glass-panel" style="padding:0; overflow:hidden;">
            <div class="table-responsive" style="border:none;">
                <table class="table reg-table" style="margin:0;">
                    <thead>
                        <tr>
                            <th style="width:30px;">#</th>
                            <th>Event</th>
                            <th>Student</th>
                            <th>Reg No / Dept</th>
                            <th>Contact</th>
                            <th>Year</th>
                            <th>Address</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sno = 1; foreach ($registrations as $reg): 
                            $dept = !empty($reg['department']) ? $reg['department'] : ($reg['vol_dept'] ?? 'N/A');
                            $regno = !empty($reg['vol_regno']) ? $reg['vol_regno'] : 'N/A';
                            $studentMob = !empty($reg['student_mobile']) ? $reg['student_mobile'] : ($reg['vol_mobile'] ?? 'N/A');
                        ?>
                        <tr>
                            <td style="color:#94a3b8; font-weight:600;"><?= $sno++ ?></td>
                            <td>
                                <strong style="color:#1b365d;"><?= htmlspecialchars($reg['event_title']) ?></strong><br>
                                <span class="badge bg-primary text-white"><?= htmlspecialchars($reg['event_category']) ?></span>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if (!empty($reg['profile_photo'])): ?>
                                        <img src="../<?= htmlspecialchars($reg['profile_photo']) ?>" alt="Avatar" style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid #f4a11d; flex-shrink:0;">
                                    <?php else: ?>
                                        <div style="width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#1b365d,#0d233a); color:#f4a11d; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.95rem; flex-shrink:0; border:2px solid #cbd5e1;">
                                            <?= strtoupper(substr($reg['student_name'] ?? 'S', 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($reg['student_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($reg['student_email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="wrap-cell">
                                <strong style="color:#1b365d;"><?= htmlspecialchars($regno) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($dept) ?></small>
                            </td>
                            <td>
                                <div style="font-size:0.84rem;"><i class="fas fa-phone text-primary" style="font-size:0.7rem; width:14px;"></i> <?= htmlspecialchars($studentMob) ?></div>
                                <div style="font-size:0.84rem;"><i class="fas fa-user-shield text-accent" style="font-size:0.7rem; width:14px;"></i> <?= htmlspecialchars($reg['parent_mobile'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($reg['year'] ?? 'N/A') ?></span>
                            </td>
                            <td class="wrap-cell">
                                <div style="font-size:0.84rem; color:#334155; line-height:1.4;">
                                    <?= htmlspecialchars($reg['address'] ?? 'N/A') ?>
                                </div>
                                <?php if (!empty($reg['remarks'])): ?>
                                    <div style="font-size:0.78rem; color:#f4a11d; margin-top:3px;">
                                        <i class="fas fa-sticky-note"></i> <?= htmlspecialchars($reg['remarks']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted"><?= date('d M Y', strtotime($reg['registered_at'])) ?></small><br>
                                <small class="text-muted" style="font-size:0.75rem;"><?= date('h:i A', strtotime($reg['registered_at'])) ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$registrations): ?>
                        <tr><td colspan="8" class="text-center" style="padding:2.5rem; color:#94a3b8;">
                            <i class="fas fa-inbox" style="font-size:2rem; display:block; margin-bottom:0.5rem;"></i>
                            No event registrations matching the filters.
                        </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>

