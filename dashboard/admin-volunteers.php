<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$msg = $_GET['msg'] ?? '';

// Handle actions (Approve / Reject / Promote to Alumni)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];

    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $user_id]);
        $msg = "User status updated to $status.";
    } elseif ($action === 'promote_alumni') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = 'alumni' WHERE id = ?");
            $stmt->execute([$user_id]);

            $vStmt = $pdo->prepare("SELECT mobile FROM volunteers WHERE user_id = ?");
            $vStmt->execute([$user_id]);
            $vol = $vStmt->fetch();
            $mobile = $vol['mobile'] ?? '';

            $aCheck = $pdo->prepare("SELECT id FROM alumni WHERE user_id = ?");
            $aCheck->execute([$user_id]);
            if (!$aCheck->fetch()) {
                $aStmt = $pdo->prepare("INSERT INTO alumni (user_id, batch_year, mobile) VALUES (?, ?, ?)");
                $aStmt->execute([$user_id, date('Y'), $mobile]);
            }

            $pdo->commit();
            $msg = "Volunteer successfully promoted to Alumni role!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Failed to promote volunteer: " . $e->getMessage();
        }
    }
}

// Multi-Criteria Filters
$filter_status = $_GET['status'] ?? 'all';
$filter_dept = $_GET['department'] ?? 'all';
$filter_year = $_GET['year'] ?? 'all';
$filter_bg = $_GET['blood_group'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Build query
$where = "u.role = 'volunteer'";
$params = [];

if ($filter_status !== 'all') {
    $where .= " AND u.status = ?";
    $params[] = $filter_status;
}
if ($filter_dept !== 'all') {
    $where .= " AND v.department = ?";
    $params[] = $filter_dept;
}
if ($filter_year !== 'all') {
    $where .= " AND v.year = ?";
    $params[] = $filter_year;
}
if ($filter_bg !== 'all') {
    $where .= " AND v.blood_group = ?";
    $params[] = $filter_bg;
}
if (!empty($search)) {
    $where .= " AND (u.name LIKE ? OR v.department LIKE ? OR v.register_number LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$limit = (int)$limit;
$offset = (int)$offset;

$query_count = "SELECT COUNT(*) FROM users u LEFT JOIN volunteers v ON u.id = v.user_id WHERE $where";
$stmt = $pdo->prepare($query_count);
$stmt->execute($params);
$total_records = (int)$stmt->fetchColumn();
$total_pages = max(1, ceil($total_records / $limit));

$query_data = "SELECT u.id, u.name, u.email, u.status, u.created_at, v.register_number, v.department, v.year, v.blood_group, v.mobile,
                      (SELECT COALESCE(SUM(hours), 0) FROM attendance WHERE user_id = u.id) as total_hours,
                      (SELECT COUNT(DISTINCT event_id) FROM attendance WHERE user_id = u.id AND event_id IS NOT NULL) as events_count
               FROM users u 
               LEFT JOIN volunteers v ON u.id = v.user_id 
               WHERE $where ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query_data);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

$pageTitle = 'Manage Volunteers | NSS Admin';
require_once '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <div class="d-flex align-items-center gap-3">
                <h2><i class="fas fa-users text-primary"></i> Volunteer Directory & Category Filters</h2>
            </div>
            <a href="export-volunteers.php" class="btn btn-outline"><i class="fas fa-download"></i> Export Volunteers CSV</a>
        </header>

        <?php if ($msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Aligned Filter Bar -->
        <div class="dashboard-card glass-panel mb-4">
            <form method="GET">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; align-items: end;">
                    <div>
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">SEARCH VOLUNTEER</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, Reg No, Email..." value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div>
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">DEPARTMENT</label>
                        <select name="department" class="form-control" onchange="this.form.submit()">
                            <option value="all">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= htmlspecialchars($d) ?>" <?= $filter_dept === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">YEAR</label>
                        <select name="year" class="form-control" onchange="this.form.submit()">
                            <option value="all">All Years</option>
                            <option value="I" <?= $filter_year === 'I' ? 'selected' : '' ?>>I Year</option>
                            <option value="II" <?= $filter_year === 'II' ? 'selected' : '' ?>>II Year</option>
                            <option value="III" <?= $filter_year === 'III' ? 'selected' : '' ?>>III Year</option>
                        </select>
                    </div>

                    <div>
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">BLOOD GROUP</label>
                        <select name="blood_group" class="form-control" onchange="this.form.submit()">
                            <option value="all">All Groups</option>
                            <?php foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                                <option value="<?= $bg ?>" <?= $filter_bg === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">STATUS</label>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $filter_status === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-filter"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Volunteers Table -->
        <div class="dashboard-card glass-panel">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Volunteer Name</th>
                            <th>Reg Number / Dept</th>
                            <th>Year & Blood</th>
                            <th>Hours / Events</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr style="cursor:pointer;" onclick="showVolunteerModal(<?= htmlspecialchars(json_encode($u)) ?>)">
                            <td>
                                <strong><?= htmlspecialchars($u['name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($u['register_number'] ?? 'N/A') ?></strong><br>
                                <small><?= htmlspecialchars($u['department'] ?? 'N/A') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($u['year'] ?? 'N/A') ?> Yr</span>
                                <span class="badge" style="background:#fee2e2; color:#b71c1c; font-weight:800; border:1px solid #fecaca; margin-left:4px;"><?= htmlspecialchars($u['blood_group'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <strong style="color:#166534;"><i class="fas fa-clock"></i> <?= floatval($u['total_hours']) ?> hrs</strong><br>
                                <small class="text-muted"><i class="fas fa-calendar-check"></i> <?= intval($u['events_count']) ?> events</small>
                            </td>
                            <td><?= htmlspecialchars($u['mobile'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($u['status'] === 'approved'): ?>
                                    <span class="badge status-approved" style="background:#dcfce7 !important; color:#166534 !important; font-weight:700; padding:5px 12px; border-radius:20px; border:1px solid #bbf7d0;"><i class="fas fa-check-circle"></i> Approved</span>
                                <?php elseif ($u['status'] === 'pending'): ?>
                                    <span class="badge status-pending" style="background:#fef3c7 !important; color:#92400e !important; font-weight:700; padding:5px 12px; border-radius:20px; border:1px solid #fde68a;"><i class="fas fa-clock"></i> Pending</span>
                                <?php else: ?>
                                    <span class="badge status-rejected" style="background:#fee2e2 !important; color:#991b1b !important; font-weight:700; padding:5px 12px; border-radius:20px; border:1px solid #fecaca;"><i class="fas fa-times-circle"></i> Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td onclick="event.stopPropagation();">
                                <form method="POST" style="display:inline-flex; gap:6px; flex-wrap:wrap;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <?php if ($u['status'] === 'pending'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-primary" title="Approve"><i class="fas fa-check"></i> Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline text-danger" title="Reject"><i class="fas fa-times"></i> Reject</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showVolunteerModal(<?= htmlspecialchars(json_encode($u)) ?>)"><i class="fas fa-eye"></i> View Profile</button>
                                        <button type="submit" name="action" value="promote_alumni" class="btn btn-sm btn-outline text-primary" onclick="return confirm('Promote this volunteer to Alumni network?');" title="Move to Alumni">
                                            <i class="fas fa-user-graduate"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$users): ?>
                        <tr><td colspan="7" class="text-center py-4">No volunteers matching your selected filter criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Volunteer Full Detail Modal -->
<div id="volunteerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#ffffff; width:90%; max-width:650px; border-radius:18px; padding:2rem; box-shadow:0 20px 40px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto; border:1px solid #e2e8f0; position:relative;">
        <button type="button" onclick="closeVolunteerModal()" style="position:absolute; top:20px; right:20px; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#64748b;"><i class="fas fa-times"></i></button>

        <div style="text-align:center; margin-bottom:1.5rem;">
            <div style="width:70px; height:70px; border-radius:50%; background:linear-gradient(135deg, #1b365d 0%, #0d233a 100%); color:#f4a11d; display:flex; align-items:center; justify-content:center; font-size:1.8rem; font-weight:700; margin:0 auto 12px;" id="mAvatar">
                V
            </div>
            <h2 id="mName" style="color:#1b365d; margin:0 0 4px; font-family:'Outfit',sans-serif;">Volunteer Name</h2>
            <p id="mEmail" style="color:#64748b; margin:0; font-size:0.9rem;">email@example.com</p>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; background:#f8fafc; padding:1.25rem; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:1.5rem;">
            <div><span style="color:#64748b; font-size:0.8rem;">REGISTER NUMBER</span><br><strong id="mRegNo" style="color:#0f172a;">-</strong></div>
            <div><span style="color:#64748b; font-size:0.8rem;">DEPARTMENT</span><br><strong id="mDept" style="color:#0f172a;">-</strong></div>
            <div><span style="color:#64748b; font-size:0.8rem;">YEAR / BLOOD GROUP</span><br><strong id="mYearBg" style="color:#0f172a;">-</strong></div>
            <div><span style="color:#64748b; font-size:0.8rem;">MOBILE NUMBER</span><br><strong id="mMobile" style="color:#0f172a;">-</strong></div>
        </div>

        <!-- Attendance Stats Badge Row -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:1.5rem;">
            <div style="background:#dcfce7; border:1px solid #bbf7d0; padding:1rem; border-radius:12px; text-align:center;">
                <h3 style="margin:0; color:#166534; font-size:1.6rem;" id="mHours">0.0</h3>
                <span style="color:#166534; font-weight:700; font-size:0.82rem;">Total NSS Hours (out of 240)</span>
            </div>
            <div style="background:#e0f2fe; border:1px solid #bae6fd; padding:1rem; border-radius:12px; text-align:center;">
                <h3 style="margin:0; color:#0369a1; font-size:1.6rem;" id="mEvents">0</h3>
                <span style="color:#0369a1; font-weight:700; font-size:0.82rem;">Camps / Events Participated</span>
            </div>
        </div>

        <button type="button" class="btn btn-outline btn-block" onclick="closeVolunteerModal()"><i class="fas fa-check"></i> Close Details</button>
    </div>
</div>

<script>
function showVolunteerModal(u) {
    document.getElementById('mName').textContent = u.name || 'Volunteer';
    document.getElementById('mEmail').textContent = u.email || '';
    document.getElementById('mAvatar').textContent = (u.name || 'V').charAt(0).toUpperCase();
    document.getElementById('mRegNo').textContent = u.register_number || 'N/A';
    document.getElementById('mDept').textContent = u.department || 'N/A';
    document.getElementById('mYearBg').textContent = (u.year || 'I') + ' Year | ' + (u.blood_group || 'N/A');
    document.getElementById('mMobile').textContent = u.mobile || 'N/A';
    document.getElementById('mHours').textContent = (parseFloat(u.total_hours) || 0).toFixed(1) + ' hrs';
    document.getElementById('mEvents').textContent = u.events_count || 0;
    document.getElementById('volunteerModal').style.display = 'flex';
}

function closeVolunteerModal() {
    document.getElementById('volunteerModal').style.display = 'none';
}
</script>

<?php require_once '../includes/header.php'; ?>
