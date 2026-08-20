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
    } elseif ($action === 'promote_year') {
        // Shift single volunteer year: I -> II, II -> III, III -> Alumni
        $vStmt = $pdo->prepare("SELECT year, mobile FROM volunteers WHERE user_id = ?");
        $vStmt->execute([$user_id]);
        $vol = $vStmt->fetch();
        $currentYear = $vol['year'] ?? 'I';

        if ($currentYear === 'I') {
            $pdo->prepare("UPDATE volunteers SET year = 'II' WHERE user_id = ?")->execute([$user_id]);
            $msg = "Volunteer promoted to II Year!";
        } elseif ($currentYear === 'II') {
            $pdo->prepare("UPDATE volunteers SET year = 'III' WHERE user_id = ?")->execute([$user_id]);
            $msg = "Volunteer promoted to III Year!";
        } elseif ($currentYear === 'III') {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE users SET role = 'alumni' WHERE id = ?")->execute([$user_id]);
                $mobile = $vol['mobile'] ?? '';
                $aCheck = $pdo->prepare("SELECT id FROM alumni WHERE user_id = ?");
                $aCheck->execute([$user_id]);
                if (!$aCheck->fetch()) {
                    $pdo->prepare("INSERT INTO alumni (user_id, batch_year, mobile) VALUES (?, ?, ?)")->execute([$user_id, date('Y'), $mobile]);
                }
                $pdo->commit();
                $msg = "III Year Volunteer successfully graduated to Alumni Network!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $msg = "Error promoting to alumni: " . $e->getMessage();
            }
        }
    } elseif ($action === 'promote_all_batch') {
        // Bulk academic year advancement for all approved volunteers
        $pdo->beginTransaction();
        try {
            // 1. III Year -> Alumni
            $finalVols = $pdo->query("SELECT v.user_id, v.mobile FROM volunteers v JOIN users u ON v.user_id = u.id WHERE u.role = 'volunteer' AND u.status = 'approved' AND v.year = 'III'")->fetchAll();
            foreach ($finalVols as $fv) {
                $uid = $fv['user_id'];
                $pdo->prepare("UPDATE users SET role = 'alumni' WHERE id = ?")->execute([$uid]);
                $aCheck = $pdo->prepare("SELECT id FROM alumni WHERE user_id = ?");
                $aCheck->execute([$uid]);
                if (!$aCheck->fetch()) {
                    $pdo->prepare("INSERT INTO alumni (user_id, batch_year, mobile) VALUES (?, ?, ?)")->execute([$uid, date('Y'), $fv['mobile']]);
                }
            }
            // 2. II Year -> III Year
            $pdo->exec("UPDATE volunteers v JOIN users u ON v.user_id = u.id SET v.year = 'III' WHERE u.role = 'volunteer' AND u.status = 'approved' AND v.year = 'II'");
            // 3. I Year -> II Year
            $pdo->exec("UPDATE volunteers v JOIN users u ON v.user_id = u.id SET v.year = 'II' WHERE u.role = 'volunteer' AND u.status = 'approved' AND v.year = 'I'");

            $pdo->commit();
            $msg = "Academic Year completed! All volunteers promoted (I➔II, II➔III, III➔Alumni).";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Bulk promotion error: " . $e->getMessage();
        }
    } elseif ($action === 'edit_volunteer') {
        $name            = trim($_POST['name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = trim($_POST['password'] ?? '');
        $register_number = trim($_POST['register_number'] ?? '');
        $department      = trim($_POST['department'] ?? '');
        $year            = $_POST['year'] ?? 'I';
        $blood_group     = $_POST['blood_group'] ?? 'A+';
        $mobile          = trim($_POST['mobile'] ?? '');

        $pdo->beginTransaction();
        try {
            if (!empty($password)) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $password, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $user_id]);
            }

            $stmt = $pdo->prepare("UPDATE volunteers SET register_number = ?, department = ?, year = ?, blood_group = ?, mobile = ? WHERE user_id = ?");
            $stmt->execute([$register_number, $department, $year, $blood_group, $mobile, $user_id]);

            $pdo->commit();
            $msg = "Volunteer profile updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Failed to update profile: " . $e->getMessage();
        }
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

$query_data = "SELECT u.id, u.name, u.email, u.password, u.status, u.profile_photo, u.created_at, v.register_number, v.department, v.year, v.blood_group, v.mobile,
                      (SELECT COALESCE(SUM(hours), 0) FROM attendance WHERE user_id = u.id) as total_hours,
                      (SELECT COUNT(DISTINCT event_id) FROM attendance WHERE user_id = u.id AND event_id IS NOT NULL) as events_count
               FROM users u 
               LEFT JOIN volunteers v ON u.id = v.user_id 
               WHERE $where ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query_data);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pre-fetch participated events & attendance history for displayed volunteers
$user_ids = array_column($users, 'id');
$user_attendance = [];
if (!empty($user_ids)) {
    $in_clause = implode(',', array_map('intval', $user_ids));
    $att_stmt = $pdo->query("
        SELECT a.user_id, a.hours, a.marked_at, a.type, a.attendance_date, a.description,
               COALESCE(e.title, CASE a.type WHEN 'parade' THEN 'Regular NSS Parade & Drill' WHEN 'parade_practice' THEN 'Parade Practice' WHEN 'special' THEN 'Special Duty Credit' ELSE 'NSS Activity' END) as event_title,
               COALESCE(e.category, 'General') as event_category
        FROM attendance a
        LEFT JOIN events e ON a.event_id = e.id AND a.event_id > 0
        WHERE a.user_id IN ($in_clause)
        ORDER BY a.marked_at DESC
    ");
    while ($row = $att_stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_attendance[$row['user_id']][] = $row;
    }
}

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
        <header class="topbar glass-panel d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <h2><i class="fas fa-users text-primary"></i> Volunteer Directory & Category Filters</h2>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="badge status-approved" style="font-size:0.85rem; padding: 6px 14px;"><i class="fas fa-user-shield"></i> Logged in as: <strong><?= htmlspecialchars($_SESSION['name'] ?? 'NSS Admin') ?></strong></span>
                <form method="POST" style="margin:0;" onsubmit="return confirm('Promote ALL approved volunteers to next academic year? (I➔II, II➔III, III➔Alumni)');">
                    <input type="hidden" name="user_id" value="0">
                    <button type="submit" name="action" value="promote_all_batch" class="btn btn-primary" style="background:linear-gradient(135deg, #1b365d, #0d233a); border:1px solid #f4a11d;"><i class="fas fa-graduation-cap text-accent"></i> Promote Batch (End of Year)</button>
                </form>
                <a href="export-volunteers.php" class="btn btn-outline"><i class="fas fa-download"></i> Export Volunteers CSV</a>
            </div>
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
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <?php if (!empty($u['profile_photo'])): ?>
                                        <img src="../<?= htmlspecialchars($u['profile_photo']) ?>" alt="Avatar" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #f4a11d; flex-shrink:0;">
                                    <?php else: ?>
                                        <div style="width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,#1b365d,#0d233a); color:#f4a11d; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.05rem; flex-shrink:0; border:2px solid #cbd5e1;">
                                            <?= strtoupper(substr($u['name'] ?? 'V', 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($u['name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                                    </div>
                                </div>
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
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showVolunteerModal(<?= htmlspecialchars(json_encode($u)) ?>)"><i class="fas fa-eye"></i> View</button>
                                        <button type="button" class="btn btn-sm btn-outline text-accent" onclick="showEditVolunteerModal(<?= htmlspecialchars(json_encode($u)) ?>)" title="Edit Profile"><i class="fas fa-edit"></i> Edit</button>
                                        
                                        <!-- Shift Year Button: I -> II, II -> III, III -> Alumni -->
                                        <button type="submit" name="action" value="promote_year" class="btn btn-sm btn-outline" style="color:#1b365d; border-color:#1b365d;" onclick="return confirm('Shift student to next academic year? (Current: <?= $u['year'] ?> Yr)');" title="Promote to Next Year">
                                            <i class="fas fa-level-up-alt"></i> Shift Yr
                                        </button>
                                        
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
            <div style="grid-column: span 2; background:#fffbe8; padding:8px 12px; border-radius:8px; border:1px solid #fde68a;">
                <span style="color:#92400e; font-size:0.8rem; font-weight:700;"><i class="fas fa-key"></i> ACCOUNT PASSWORD</span><br>
                <code id="mPassword" style="color:#b71c1c; font-weight:700; font-size:1rem;">-</code>
            </div>
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

        <!-- Participated Events & Activities Table -->
        <div style="margin-bottom:1.5rem;">
            <h4 style="color:#1b365d; font-family:'Outfit',sans-serif; margin-bottom:0.75rem; font-size:1.05rem;"><i class="fas fa-calendar-check text-primary"></i> Participated Events & NSS Activity Log</h4>
            <div style="max-height:200px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:10px; background:#ffffff;">
                <table class="table" style="margin:0; font-size:0.84rem;">
                    <thead>
                        <tr>
                            <th>Event / Activity</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Hours</th>
                        </tr>
                    </thead>
                    <tbody id="mAttListBody">
                        <tr><td colspan="4" class="text-center py-3 text-muted">Loading history...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a id="mLogLink" href="admin-activity-logs.php" target="_blank" class="btn btn-outline flex-1" style="color:#1b365d; border-color:#cbd5e1; text-align:center;"><i class="fas fa-history text-accent"></i> View Activity Logs</a>
            <button type="button" class="btn btn-primary flex-1" onclick="closeVolunteerModal()"><i class="fas fa-check"></i> Close Details</button>
        </div>
    </div>
</div>

<!-- Edit Volunteer Modal -->
<div id="editVolunteerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#ffffff; width:90%; max-width:650px; border-radius:18px; padding:2rem; box-shadow:0 20px 40px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto; border:1px solid #e2e8f0; position:relative;">
        <button type="button" onclick="closeEditVolunteerModal()" style="position:absolute; top:20px; right:20px; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#64748b;"><i class="fas fa-times"></i></button>
        <h3 style="color:#1b365d; margin-top:0; margin-bottom:1.5rem;"><i class="fas fa-user-edit text-accent"></i> Edit Volunteer Profile</h3>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit_volunteer">
            <input type="hidden" name="user_id" id="editUserId">

            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Full Name *</label>
                    <input type="text" name="name" id="editName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Email Address *</label>
                    <input type="email" name="email" id="editEmail" class="form-control" required>
                </div>
            </div>

            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Register Number *</label>
                    <input type="text" name="register_number" id="editRegNo" class="form-control" required>
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Mobile Number *</label>
                    <input type="text" name="mobile" id="editMobile" class="form-control" required>
                </div>
            </div>

            <div class="form-group mb-3">
                <label style="font-weight:600; font-size:0.85rem;">Department *</label>
                <select name="department" id="editDept" class="form-control" required>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Year *</label>
                    <select name="year" id="editYear" class="form-control" required>
                        <option value="I">I Year</option>
                        <option value="II">II Year</option>
                        <option value="III">III Year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Blood Group *</label>
                    <select name="blood_group" id="editBloodGroup" class="form-control" required>
                        <?php foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                            <option value="<?= $bg ?>"><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group mb-4">
                <label style="font-weight:600; font-size:0.85rem;">Password <small class="text-muted">(Leave blank to keep unchanged)</small></label>
                <input type="text" name="password" id="editPassword" class="form-control" placeholder="New Password">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="closeEditVolunteerModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
const userAttMap = <?= json_encode($user_attendance) ?>;

function showVolunteerModal(u) {
    document.getElementById('mName').textContent = u.name || 'Volunteer';
    document.getElementById('mEmail').textContent = u.email || '';
    const mAvatar = document.getElementById('mAvatar');
    if (u.profile_photo) {
        mAvatar.innerHTML = `<img src="../${escapeHtml(u.profile_photo)}" alt="Avatar" style="width:100%; height:100%; border-radius:50%; object-fit:cover; border:2px solid #f4a11d;">`;
    } else {
        mAvatar.textContent = (u.name || 'V').charAt(0).toUpperCase();
    }
    document.getElementById('mRegNo').textContent = u.register_number || 'N/A';
    document.getElementById('mDept').textContent = u.department || 'N/A';
    document.getElementById('mYearBg').textContent = (u.year || 'I') + ' Year | ' + (u.blood_group || 'N/A');
    document.getElementById('mMobile').textContent = u.mobile || 'N/A';
    document.getElementById('mPassword').textContent = u.password || '(Encrypted)';
    document.getElementById('mHours').textContent = (parseFloat(u.total_hours) || 0).toFixed(1) + ' hrs';
    document.getElementById('mEvents').textContent = u.events_count || 0;
    document.getElementById('mLogLink').href = 'admin-activity-logs.php?search=' + encodeURIComponent(u.name || '');

    // Populate participated events log
    const tbody = document.getElementById('mAttListBody');
    tbody.innerHTML = '';
    const list = userAttMap[u.id] || [];

    if (list.length > 0) {
        list.forEach(att => {
            const tr = document.createElement('tr');
            const dt = att.attendance_date || att.marked_at || '';
            const dtStr = dt ? new Date(dt).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) : 'N/A';
            
            tr.innerHTML = `
                <td><strong>${escapeHtml(att.event_title)}</strong></td>
                <td><span class="badge bg-primary text-white" style="font-size:0.7rem;">${escapeHtml(att.event_category || 'General')}</span></td>
                <td><small class="text-muted">${dtStr}</small></td>
                <td><strong class="text-success">+${parseFloat(att.hours).toFixed(1)} hrs</strong></td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-muted">No credited events or parade records found.</td></tr>`;
    }

    document.getElementById('volunteerModal').style.display = 'flex';
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function closeVolunteerModal() {
    document.getElementById('volunteerModal').style.display = 'none';
}

function showEditVolunteerModal(u) {
    document.getElementById('editUserId').value = u.id;
    document.getElementById('editName').value = u.name || '';
    document.getElementById('editEmail').value = u.email || '';
    document.getElementById('editRegNo').value = u.register_number || '';
    document.getElementById('editMobile').value = u.mobile || '';
    document.getElementById('editDept').value = u.department || '';
    document.getElementById('editYear').value = u.year || 'I';
    document.getElementById('editBloodGroup').value = u.blood_group || 'A+';
    document.getElementById('editPassword').value = u.password || '';
    document.getElementById('editVolunteerModal').style.display = 'flex';
}

function closeEditVolunteerModal() {
    document.getElementById('editVolunteerModal').style.display = 'none';
}
</script>

<?php require_once '../includes/header.php'; ?>
