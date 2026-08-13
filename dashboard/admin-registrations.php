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
           u.name as student_name, u.email as student_email,
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

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <div class="d-flex align-items-center gap-3">
                <h2><i class="fas fa-clipboard-list text-primary"></i> Event Registrations Directory</h2>
            </div>
            <span class="badge status-approved"><i class="fas fa-list-check"></i> Total Enrolled: <?= count($registrations) ?></span>
        </header>

        <!-- Filter Controls Bar -->
        <div class="dashboard-card glass-panel mb-4">
            <form method="GET" class="d-flex gap-3 align-items-center flex-wrap">
                <div style="flex:1; min-width:200px;">
                    <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:2px;">SEARCH VOLUNTEER</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, Reg No, Mobile, Address..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div style="width:220px;">
                    <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:2px;">FILTER BY EVENT</label>
                    <select name="event_id" class="form-control" onchange="this.form.submit()">
                        <option value="0">All Events & Camps</option>
                        <?php foreach ($events_list as $ev): ?>
                            <option value="<?= $ev['id'] ?>" <?= $filter_event == $ev['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ev['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="width:200px;">
                    <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:2px;">DEPARTMENT</label>
                    <select name="department" class="form-control" onchange="this.form.submit()">
                        <option value="all">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>" <?= $filter_dept === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="align-self:flex-end;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="dashboard-card glass-panel">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Event & Category</th>
                            <th>Student Details</th>
                            <th>Reg Number / Dept</th>
                            <th>Contact Mobiles</th>
                            <th>Age / Year</th>
                            <th>Address & Remarks</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): 
                            $dept = !empty($reg['department']) ? $reg['department'] : ($reg['vol_dept'] ?? 'N/A');
                            $regno = !empty($reg['vol_regno']) ? $reg['vol_regno'] : 'N/A';
                            $studentMob = !empty($reg['student_mobile']) ? $reg['student_mobile'] : ($reg['vol_mobile'] ?? 'N/A');
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($reg['event_title']) ?></strong><br>
                                <span class="badge bg-primary text-white" style="font-size:0.75rem;"><?= htmlspecialchars($reg['event_category']) ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($reg['student_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($reg['student_email']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($regno) ?></strong><br>
                                <small><?= htmlspecialchars($dept) ?></small>
                            </td>
                            <td>
                                <div><i class="fas fa-phone text-primary" style="font-size:0.8rem;"></i> Student: <strong><?= htmlspecialchars($studentMob) ?></strong></div>
                                <div><i class="fas fa-user-shield text-accent" style="font-size:0.8rem;"></i> Parent: <strong><?= htmlspecialchars($reg['parent_mobile'] ?? 'N/A') ?></strong></div>
                            </td>
                            <td>
                                <?= htmlspecialchars($reg['age'] ?? 'N/A') ?> yrs<br>
                                <span class="badge bg-secondary"><?= htmlspecialchars($reg['year'] ?? 'N/A') ?> Yr</span>
                            </td>
                            <td>
                                <div style="max-width:240px; font-size:0.88rem; color:#334155; line-height:1.4;">
                                    <i class="fas fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($reg['address'] ?? 'N/A') ?>
                                </div>
                                <?php if (!empty($reg['remarks'])): ?>
                                    <div style="font-size:0.8rem; color:#f4a11d; margin-top:3px;">
                                        <i class="fas fa-sticky-note"></i> <?= htmlspecialchars($reg['remarks']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted"><?= date('d M Y, h:i A', strtotime($reg['registered_at'])) ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$registrations): ?>
                        <tr><td colspan="7" class="text-center py-4">No event registrations matching the filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
