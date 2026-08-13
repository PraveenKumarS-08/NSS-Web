<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$msg = '';
$error = '';
$activeTab = $_GET['tab'] ?? $_POST['tab'] ?? 'event';

// ===== 1. Handle Event Attendance =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_event_attendance'])) {
    $event_id = (int)$_POST['event_id'];
    $user_id = (int)$_POST['user_id'];
    $hours = (float)($_POST['hours'] ?? 4.0);
    $status = $_POST['status'] ?? 'present';

    if ($event_id > 0 && $user_id > 0) {
        if ($status === 'present') {
            $stmt = $pdo->prepare("SELECT id FROM attendance WHERE user_id = ? AND event_id = ? AND type = 'event'");
            $stmt->execute([$user_id, $event_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $update = $pdo->prepare("UPDATE attendance SET hours = ?, marked_at = CURRENT_TIMESTAMP WHERE id = ?");
                $update->execute([$hours, $existing['id']]);
                $msg = "Updated event attendance: {$hours} NSS hours credited.";
            } else {
                $insert = $pdo->prepare("INSERT INTO attendance (user_id, event_id, hours, type) VALUES (?, ?, ?, 'event')");
                $insert->execute([$user_id, $event_id, $hours]);
                $msg = "Attendance marked! {$hours} NSS hours credited.";
            }
        } else {
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE user_id = ? AND event_id = ? AND type = 'event'");
            $stmt->execute([$user_id, $event_id]);
            $msg = "Revoked event attendance record.";
        }
    }
    $activeTab = 'event';
}

// ===== 2. Handle Parade Attendance =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_parade'])) {
    $parade_type = $_POST['parade_type'] ?? 'parade';
    $parade_date = $_POST['parade_date'] ?? date('Y-m-d');
    $present_ids = $_POST['present'] ?? [];
    $hours_map = $_POST['hours_map'] ?? [];
    $count = 0;

    foreach ($present_ids as $uid) {
        $uid = (int)$uid;
        $hrs = (float)($hours_map[$uid] ?? ($parade_type === 'parade' ? 1.0 : 0.5));
        
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE user_id = ? AND attendance_date = ? AND type = ?");
        $stmt->execute([$uid, $parade_date, $parade_type]);
        $existing = $stmt->fetch();

        if ($existing) {
            $pdo->prepare("UPDATE attendance SET hours = ?, marked_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$hrs, $existing['id']]);
        } else {
            $pdo->prepare("INSERT INTO attendance (user_id, event_id, hours, type, attendance_date) VALUES (?, NULL, ?, ?, ?)")->execute([$uid, $hrs, $parade_type, $parade_date]);
        }
        $count++;
    }
    $msg = "Saved parade attendance! {$count} volunteers marked present for {$parade_date}.";
    $activeTab = 'parade';
}

// ===== 3. Handle Direct Custom Credit =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_credit'])) {
    $user_id = (int)$_POST['user_id'];
    $hours = (float)$_POST['custom_hours'];
    $reason = trim($_POST['credit_reason'] ?? 'Special Duty / NSS Contribution');

    if ($user_id > 0 && $hours > 0) {
        $stmt = $pdo->prepare("INSERT INTO attendance (user_id, event_id, hours, type, attendance_date) VALUES (?, NULL, ?, 'special', CURRENT_DATE)");
        $stmt->execute([$user_id, $hours]);
        $msg = "Directly credited {$hours} NSS hours to volunteer!";
    } else {
        $error = "Please select a volunteer and enter valid hours (> 0).";
    }
    $activeTab = 'direct';
}

// Handle Record Deletion
if (isset($_POST['delete_attendance_id'])) {
    $att_id = (int)$_POST['delete_attendance_id'];
    $pdo->prepare("DELETE FROM attendance WHERE id = ?")->execute([$att_id]);
    $msg = "Attendance record deleted.";
}

// Fetch Events List
$events = $pdo->query("SELECT id, title, event_date, location FROM events ORDER BY event_date DESC")->fetchAll();
$selected_event_id = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? ($events[0]['id'] ?? 0));

// Fetch Approved Volunteers for Event Tab
$event_volunteers = [];
if ($selected_event_id > 0) {
    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.name, u.email, v.register_number, v.department, v.year, v.blood_group, v.mobile,
               a.hours as credited_hours, a.id as attendance_id
        FROM users u
        JOIN volunteers v ON u.id = v.user_id
        LEFT JOIN attendance a ON (a.user_id = u.id AND a.event_id = ? AND a.type = 'event')
        WHERE u.role = 'volunteer' AND u.status = 'approved'
        ORDER BY v.department, u.name ASC
    ");
    $stmt->execute([$selected_event_id]);
    $event_volunteers = $stmt->fetchAll();
}

// Fetch Approved Volunteers for Parade Tab
$parade_date = $_GET['parade_date'] ?? date('Y-m-d');
$parade_type_filter = $_GET['parade_type'] ?? 'parade';

$stmt = $pdo->prepare("
    SELECT u.id as user_id, u.name, u.email, v.register_number, v.department, v.year,
           a.hours as credited_hours, a.id as attendance_id
    FROM users u
    JOIN volunteers v ON u.id = v.user_id
    LEFT JOIN attendance a ON (a.user_id = u.id AND a.attendance_date = ? AND a.type = ?)
    WHERE u.role = 'volunteer' AND u.status = 'approved'
    ORDER BY v.department, u.name ASC
");
$stmt->execute([$parade_date, $parade_type_filter]);
$parade_volunteers = $stmt->fetchAll();

// Fetch ALL Approved Volunteers for Direct Credit Tab
$all_approved_volunteers = $pdo->query("
    SELECT u.id as user_id, u.name, v.register_number, v.department
    FROM users u
    JOIN volunteers v ON u.id = v.user_id
    WHERE u.role = 'volunteer' AND u.status = 'approved'
    ORDER BY u.name ASC
")->fetchAll();

// Recent Attendance Log
$recent_attendance = $pdo->query("
    SELECT a.id, a.hours, a.marked_at, a.type, a.attendance_date, u.name, v.register_number, v.department, 
           COALESCE(e.title, CASE a.type WHEN 'parade' THEN 'Regular Parade' WHEN 'parade_practice' THEN 'Parade Practice' WHEN 'special' THEN 'Special Duty Credit' ELSE 'NSS Activity' END) as event_title
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN volunteers v ON u.id = v.user_id
    LEFT JOIN events e ON a.event_id = e.id AND a.event_id > 0
    ORDER BY a.marked_at DESC LIMIT 20
")->fetchAll();

$pageTitle = 'NSS Attendance Portal | Admin';
require_once '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <div class="d-flex align-items-center gap-3">
                <h2><i class="fas fa-user-check text-primary"></i> Volunteer Attendance & Hours Credit</h2>
            </div>
            <div class="user-info">
                <span class="badge status-approved"><i class="fas fa-shield-alt"></i> Admin Control</span>
            </div>
        </header>

        <?php if ($msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Mode Navigation Tabs -->
        <div style="display:flex; gap:10px; margin-bottom:25px; flex-wrap:wrap;">
            <button type="button" class="btn mode-tab-btn <?= $activeTab === 'event' ? 'btn-primary' : 'btn-outline' ?>" onclick="switchAttTab('event')">
                <i class="fas fa-calendar-alt"></i> 1. Event & Camp Attendance
            </button>
            <button type="button" class="btn mode-tab-btn <?= $activeTab === 'parade' ? 'btn-primary' : 'btn-outline' ?>" onclick="switchAttTab('parade')">
                <i class="fas fa-flag"></i> 2. Parade / Drill Attendance
            </button>
            <button type="button" class="btn mode-tab-btn <?= $activeTab === 'direct' ? 'btn-primary' : 'btn-outline' ?>" onclick="switchAttTab('direct')">
                <i class="fas fa-award"></i> 3. Direct Custom Hours Credit
            </button>
        </div>

        <!-- ===== MODE 1: EVENT ATTENDANCE ===== -->
        <div id="tab-event" class="att-panel" style="display:<?= $activeTab === 'event' ? 'block' : 'none' ?>;">
            <div class="dashboard-card glass-panel">
                <h3><i class="fas fa-calendar-check text-accent"></i> Select NSS Event / Camp</h3>
                <form method="GET" class="d-flex gap-3 align-items-center flex-wrap">
                    <input type="hidden" name="tab" value="event">
                    <select name="event_id" class="form-control" style="max-width:450px;" onchange="this.form.submit()">
                        <?php foreach ($events as $ev): ?>
                            <option value="<?= $ev['id'] ?>" <?= $ev['id'] == $selected_event_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ev['title']) ?> (<?= date('d M Y', strtotime($ev['event_date'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                </form>
            </div>

            <div class="dashboard-card glass-panel">
                <h3><i class="fas fa-users text-primary"></i> Volunteer Attendance & Hours Credit</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Volunteer Name</th>
                                <th>Reg No / Dept</th>
                                <th>Status</th>
                                <th>NSS Hours</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($event_volunteers as $vol): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($vol['name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($vol['email']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($vol['register_number'] ?? 'N/A') ?></strong><br>
                                    <small><?= htmlspecialchars($vol['department'] ?? '') ?> (<?= $vol['year'] ?? '' ?> Yr)</small>
                                </td>
                                <td>
                                    <?php if ($vol['attendance_id']): ?>
                                        <span class="badge status-approved"><i class="fas fa-check"></i> Present (<?= $vol['credited_hours'] ?> hrs)</span>
                                    <?php else: ?>
                                        <span class="badge status-pending"><i class="fas fa-clock"></i> Not Marked</span>
                                    <?php endif; ?>
                                </td>
                                <form method="POST">
                                    <input type="hidden" name="tab" value="event">
                                    <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                                    <input type="hidden" name="user_id" value="<?= $vol['user_id'] ?>">
                                    <td>
                                        <input type="number" step="0.5" name="hours" class="form-control" style="width:90px; padding:0.35rem 0.5rem;" value="<?= $vol['credited_hours'] ?: '4.0' ?>" required>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="mark_event_attendance" value="1" class="btn btn-sm btn-primary">
                                                <i class="fas fa-check"></i> Present
                                            </button>
                                            <?php if ($vol['attendance_id']): ?>
                                                <input type="hidden" name="status" value="absent">
                                                <button type="submit" name="mark_event_attendance" value="1" class="btn btn-sm btn-outline text-danger">
                                                    <i class="fas fa-times"></i> Revoke
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$event_volunteers): ?>
                            <tr><td colspan="5" class="text-center py-4">No volunteers found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== MODE 2: PARADE & PRACTICE ===== -->
        <div id="tab-parade" class="att-panel" style="display:<?= $activeTab === 'parade' ? 'block' : 'none' ?>;">
            <div class="dashboard-card glass-panel">
                <h3><i class="fas fa-flag text-accent"></i> Parade & Drill Session Selector</h3>
                <form method="GET" class="d-flex gap-3 align-items-center flex-wrap">
                    <input type="hidden" name="tab" value="parade">
                    <div>
                        <label style="font-weight:600; font-size:0.85rem; color:#475569; display:block; margin-bottom:4px;">Parade Date</label>
                        <input type="date" name="parade_date" class="form-control" value="<?= htmlspecialchars($parade_date) ?>" style="width:180px;">
                    </div>
                    <div>
                        <label style="font-weight:600; font-size:0.85rem; color:#475569; display:block; margin-bottom:4px;">Session Type</label>
                        <select name="parade_type" class="form-control" style="width:220px;">
                            <option value="parade" <?= $parade_type_filter === 'parade' ? 'selected' : '' ?>>Regular Parade (1.0 hr)</option>
                            <option value="parade_practice" <?= $parade_type_filter === 'parade_practice' ? 'selected' : '' ?>>Parade Practice (0.5 hr)</option>
                        </select>
                    </div>
                    <div style="align-self:flex-end;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Load Parade Sheet</button>
                    </div>
                </form>
            </div>

            <form method="POST">
                <input type="hidden" name="tab" value="parade">
                <input type="hidden" name="parade_date" value="<?= htmlspecialchars($parade_date) ?>">
                <input type="hidden" name="parade_type" value="<?= htmlspecialchars($parade_type_filter) ?>">

                <div class="dashboard-card glass-panel">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h3 style="margin:0;"><i class="fas fa-users-cog text-primary"></i> Parade Sheet — <?= date('d M Y', strtotime($parade_date)) ?></h3>
                        <button type="submit" name="bulk_parade" value="1" class="btn btn-primary"><i class="fas fa-save"></i> Save Parade Attendance</button>
                    </div>

                    <div style="margin-bottom:12px;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:700; color:#1b365d;">
                            <input type="checkbox" id="selectAllParade" style="width:18px; height:18px;"> Select All Present
                        </label>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">Present</th>
                                    <th>Volunteer</th>
                                    <th>Reg No / Dept</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parade_volunteers as $vol): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="present[]" value="<?= $vol['user_id'] ?>" class="parade-cb" style="width:18px; height:18px;" <?= $vol['attendance_id'] ? 'checked' : '' ?>>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($vol['name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($vol['email']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($vol['register_number'] ?? 'N/A') ?></strong><br>
                                        <small><?= htmlspecialchars($vol['department'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <input type="number" step="0.5" name="hours_map[<?= $vol['user_id'] ?>]" class="form-control" style="width:80px; padding:0.3rem 0.5rem;" value="<?= $vol['credited_hours'] ?: ($parade_type_filter === 'parade' ? '1.0' : '0.5') ?>">
                                    </td>
                                    <td>
                                        <?php if ($vol['attendance_id']): ?>
                                            <span class="badge status-approved"><i class="fas fa-check"></i> Marked Present (<?= $vol['credited_hours'] ?> hrs)</span>
                                        <?php else: ?>
                                            <span class="badge text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>

        <!-- ===== MODE 3: DIRECT CUSTOM CREDIT ===== -->
        <div id="tab-direct" class="att-panel" style="display:<?= $activeTab === 'direct' ? 'block' : 'none' ?>;">
            <div class="dashboard-card glass-panel" style="max-width:650px;">
                <h3><i class="fas fa-award text-accent"></i> Direct NSS Hours Award</h3>
                <p class="text-muted">Directly credit verified NSS hours to any volunteer for special duties, camps, or community service.</p>

                <form method="POST">
                    <input type="hidden" name="tab" value="direct">
                    <div class="form-group mb-3">
                        <label>Select Volunteer *</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">— Select Volunteer —</option>
                            <?php foreach ($all_approved_volunteers as $v): ?>
                                <option value="<?= $v['user_id'] ?>"><?= htmlspecialchars($v['name']) ?> (<?= htmlspecialchars($v['register_number']) ?> — <?= htmlspecialchars($v['department']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label>NSS Hours to Award *</label>
                        <input type="number" step="0.5" name="custom_hours" class="form-control" placeholder="e.g. 8.0" required>
                    </div>

                    <div class="form-group mb-4">
                        <label>Reason / Activity Description</label>
                        <input type="text" name="credit_reason" class="form-control" placeholder="e.g. Special Camp Duty / Republic Day Parade">
                    </div>

                    <button type="submit" name="direct_credit" value="1" class="btn btn-primary btn-block"><i class="fas fa-gift"></i> Credit NSS Hours</button>
                </form>
            </div>
        </div>

        <!-- ===== RECENT ATTENDANCE LOG ===== -->
        <div class="dashboard-card glass-panel mt-4">
            <h3><i class="fas fa-history text-accent"></i> Recent Official Attendance Credit Log</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Volunteer</th>
                            <th>Reg No / Dept</th>
                            <th>Activity / Camp</th>
                            <th>Type</th>
                            <th>Hours</th>
                            <th>Marked Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_attendance as $log): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($log['name']) ?></strong></td>
                            <td><?= htmlspecialchars($log['register_number'] ?? 'N/A') ?> (<?= htmlspecialchars($log['department'] ?? '') ?>)</td>
                            <td><?= htmlspecialchars($log['event_title']) ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst($log['type'] ?? 'Event') ?></span></td>
                            <td><span class="badge" style="background:#dcfce7; color:#166534; font-weight:700; border:1px solid #bbf7d0; padding:5px 12px; border-radius:20px;"><i class="fas fa-clock"></i> +<?= floatval($log['hours']) ?> Hours</span></td>
                            <td><small><?= date('d M Y, h:i A', strtotime($log['marked_at'])) ?></small></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this attendance credit log?');" style="display:inline;">
                                    <input type="hidden" name="delete_attendance_id" value="<?= $log['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline text-danger"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function switchAttTab(tabName) {
    document.querySelectorAll('.att-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.mode-tab-btn').forEach(b => {
        b.classList.remove('btn-primary');
        b.classList.add('btn-outline');
    });
    document.getElementById('tab-' + tabName).style.display = 'block';
    event.target.closest('.mode-tab-btn').classList.remove('btn-outline');
    event.target.closest('.mode-tab-btn').classList.add('btn-primary');
}

document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('selectAllParade');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.parade-cb').forEach(cb => cb.checked = this.checked);
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
