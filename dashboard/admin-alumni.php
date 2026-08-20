<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$msg = $_GET['msg'] ?? '';

// Handle actions (Approve / Reject / Edit / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'alumni'");
        $stmt->execute([$status, $user_id]);
        $msg = "Alumni account status updated to $status.";
    } elseif ($action === 'edit_alumni') {
        $name             = trim($_POST['name'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = trim($_POST['password'] ?? '');
        $batch_year       = trim($_POST['batch_year'] ?? '');
        $current_position = trim($_POST['current_position'] ?? '');
        $company          = trim($_POST['company'] ?? '');
        $mobile           = trim($_POST['mobile'] ?? '');
        $linkedin_url     = trim($_POST['linkedin_url'] ?? '');

        $pdo->beginTransaction();
        try {
            if (!empty($password)) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $password, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $user_id]);
            }

            $stmt = $pdo->prepare("UPDATE alumni SET batch_year = ?, current_position = ?, company = ?, mobile = ?, linkedin_url = ? WHERE user_id = ?");
            $stmt->execute([$batch_year, $current_position, $company, $mobile, $linkedin_url, $user_id]);

            $pdo->commit();
            $msg = "Alumni member details updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Failed to update alumni record: " . $e->getMessage();
        }
    } elseif ($action === 'delete_alumni') {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM alumni WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'alumni'")->execute([$user_id]);
            $pdo->commit();
            $msg = "Alumni member deleted successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Error deleting alumni: " . $e->getMessage();
        }
    }
}

// Search and Filter
$filter_status = $_GET['status'] ?? 'all';
$filter_batch = $_GET['batch_year'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = "u.role = 'alumni'";
$params = [];

if ($filter_status !== 'all') {
    $where .= " AND u.status = ?";
    $params[] = $filter_status;
}
if ($filter_batch !== 'all') {
    $where .= " AND a.batch_year = ?";
    $params[] = $filter_batch;
}
if (!empty($search)) {
    $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR a.company LIKE ? OR a.current_position LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}

$query = "SELECT u.id, u.name, u.email, u.password, u.status, u.created_at,
                 a.batch_year, a.current_position, a.company, a.mobile, a.linkedin_url
          FROM users u 
          LEFT JOIN alumni a ON u.id = a.user_id 
          WHERE $where ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$alumni_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Unique batch years for filter
$batch_years = $pdo->query("SELECT DISTINCT batch_year FROM alumni WHERE batch_year IS NOT NULL AND batch_year != '' ORDER BY batch_year DESC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Alumni Directory | NSS Admin';
require_once '../includes/header.php';
?>

<style>
/* Scoped styles for Alumni Directory alignment */
.alumni-filter-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 14px;
    align-items: end;
}
.alumni-table th, .alumni-table td {
    padding: 0.8rem 1rem;
    font-size: 0.88rem;
    vertical-align: middle;
}
.alumni-table .text-muted { color: #64748b; }
.alumni-table .badge { font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; }
@media (max-width: 900px) {
    .alumni-filter-grid { grid-template-columns: 1fr; }
}
</style>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <h2><i class="fas fa-user-graduate text-primary"></i> NSS Alumni Network Directory</h2>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge status-approved" style="font-size:0.85rem; padding:6px 14px;"><i class="fas fa-user-shield"></i> Logged in as: <strong><?= htmlspecialchars($_SESSION['name'] ?? 'NSS Admin') ?></strong></span>
                <a href="export-alumni.php?<?= http_build_query($_GET) ?>" class="btn btn-primary btn-sm"><i class="fas fa-file-csv"></i> Export Alumni CSV</a>
                <span class="badge status-approved" style="font-size:0.85rem; padding:6px 14px;"><i class="fas fa-users"></i> Total Alumni: <?= count($alumni_list) ?></span>
            </div>
        </header>

        <?php if ($msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <div class="dashboard-card glass-panel mb-4" style="padding:1.25rem 1.5rem;">
            <form method="GET">
                <div class="alumni-filter-grid">
                    <div class="form-group">
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">SEARCH ALUMNI</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, Email, Company, Role..." value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="form-group">
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">STATUS</label>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="all">All Statuses</option>
                            <option value="approved" <?= $filter_status === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">BATCH YEAR</label>
                        <select name="batch_year" class="form-control" onchange="this.form.submit()">
                            <option value="all">All Batches</option>
                            <?php foreach ($batch_years as $by): ?>
                                <option value="<?= htmlspecialchars($by) ?>" <?= $filter_batch === $by ? 'selected' : '' ?>><?= htmlspecialchars($by) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="display:flex; gap:8px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                        <a href="admin-alumni.php" class="btn btn-outline" title="Reset Filters"><i class="fas fa-undo"></i></a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Alumni Table -->
        <div class="dashboard-card glass-panel" style="padding:0; overflow:hidden;">
            <div class="table-responsive" style="border:none;">
                <table class="table alumni-table" id="alumniTable" style="margin:0;">
                    <thead>
                        <tr>
                            <th>Alumni Member</th>
                            <th>Batch Year</th>
                            <th>Current Role & Company</th>
                            <th>Contact Mobile</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumni_list as $a): ?>
                        <tr class="alumni-row">
                            <td>
                                <strong><?= htmlspecialchars($a['name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($a['email']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($a['batch_year'] ?? 'N/A') ?> Batch</span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($a['current_position'] ?? 'N/A') ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($a['company'] ?? 'N/A') ?></small>
                            </td>
                            <td><?= htmlspecialchars($a['mobile'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($a['status'] === 'approved'): ?>
                                    <span class="badge status-approved" style="background:#dcfce7 !important; color:#166534 !important; font-weight:700; padding:5px 12px; border-radius:20px; border:1px solid #bbf7d0;"><i class="fas fa-check-circle"></i> Approved</span>
                                <?php elseif ($a['status'] === 'pending'): ?>
                                    <span class="badge status-pending" style="background:#fef3c7 !important; color:#92400e !important; font-weight:700; padding:5px 12px; border-radius:20px; border:1px solid #fde68a;"><i class="fas fa-clock"></i> Pending</span>
                                <?php else: ?>
                                    <span class="badge status-rejected" style="background:#fee2e2 !important; color:#991b1b !important; font-weight:700; padding:5px 12px; border-radius:20px; border:1px solid #fecaca;"><i class="fas fa-times-circle"></i> Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline-flex; gap:6px; flex-wrap:wrap;">
                                    <input type="hidden" name="user_id" value="<?= $a['id'] ?>">
                                    <?php if ($a['status'] === 'pending'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-primary" title="Approve"><i class="fas fa-check"></i> Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline text-danger" title="Reject"><i class="fas fa-times"></i> Reject</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="showViewAlumniModal(<?= htmlspecialchars(json_encode($a)) ?>)"><i class="fas fa-eye"></i> View</button>
                                        <button type="button" class="btn btn-sm btn-outline text-accent" onclick="showEditAlumniModal(<?= htmlspecialchars(json_encode($a)) ?>)" title="Edit Profile"><i class="fas fa-edit"></i> Edit</button>
                                        <button type="submit" name="action" value="delete_alumni" class="btn btn-sm btn-outline text-danger" onclick="return confirm('Permanently remove this alumni profile?');" title="Delete"><i class="fas fa-trash"></i></button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$alumni_list): ?>
                        <tr><td colspan="6" class="text-center py-4">No alumni members found matching your filter criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- View Alumni Modal -->
<div id="viewAlumniModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#ffffff; width:90%; max-width:600px; border-radius:18px; padding:2rem; box-shadow:0 20px 40px rgba(0,0,0,0.2); border:1px solid #e2e8f0; position:relative;" class="modal-card">
        <button type="button" onclick="closeViewAlumniModal()" style="position:absolute; top:20px; right:20px; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#64748b;"><i class="fas fa-times"></i></button>
        
        <div style="text-align:center; margin-bottom:1.5rem;">
            <div style="width:70px; height:70px; border-radius:50%; background:linear-gradient(135deg, #1b365d 0%, #0d233a 100%); color:#f4a11d; display:flex; align-items:center; justify-content:center; font-size:1.8rem; font-weight:700; margin:0 auto 12px;" id="vaAvatar">
                A
            </div>
            <h2 id="vaName" style="color:#1b365d; margin:0 0 4px; font-family:'Outfit',sans-serif;">Alumni Name</h2>
            <p id="vaEmail" style="color:#64748b; margin:0; font-size:0.9rem;">email@example.com</p>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; background:#f8fafc; padding:1.25rem; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:1.5rem;">
            <div><span style="color:#64748b; font-size:0.8rem;">BATCH YEAR</span><br><strong id="vaBatch" style="color:#0f172a;">-</strong></div>
            <div><span style="color:#64748b; font-size:0.8rem;">MOBILE NUMBER</span><br><strong id="vaMobile" style="color:#0f172a;">-</strong></div>
            <div><span style="color:#64748b; font-size:0.8rem;">POSITION</span><br><strong id="vaPosition" style="color:#0f172a;">-</strong></div>
            <div><span style="color:#64748b; font-size:0.8rem;">COMPANY / ORG</span><br><strong id="vaCompany" style="color:#0f172a;">-</strong></div>
            <div style="grid-column: span 2; background:#fffbe8; padding:8px 12px; border-radius:8px; border:1px solid #fde68a;">
                <span style="color:#92400e; font-size:0.8rem; font-weight:700;"><i class="fas fa-key"></i> ACCOUNT PASSWORD</span><br>
                <code id="vaPassword" style="color:#b71c1c; font-weight:700; font-size:1rem;">-</code>
            </div>
        </div>

        <button type="button" class="btn btn-outline btn-block" onclick="closeViewAlumniModal()"><i class="fas fa-check"></i> Close</button>
    </div>
</div>

<!-- Edit Alumni Modal -->
<div id="editAlumniModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#ffffff; width:90%; max-width:600px; border-radius:18px; padding:2rem; box-shadow:0 20px 40px rgba(0,0,0,0.2); border:1px solid #e2e8f0; position:relative;" class="modal-card">
        <button type="button" onclick="closeEditAlumniModal()" style="position:absolute; top:20px; right:20px; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#64748b;"><i class="fas fa-times"></i></button>
        <h3 style="color:#1b365d; margin-top:0; margin-bottom:1.5rem;"><i class="fas fa-user-edit text-accent"></i> Edit Alumni Member Profile</h3>

        <form method="POST">
            <input type="hidden" name="action" value="edit_alumni">
            <input type="hidden" name="user_id" id="eaUserId">

            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Full Name *</label>
                    <input type="text" name="name" id="eaName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Email Address *</label>
                    <input type="email" name="email" id="eaEmail" class="form-control" required>
                </div>
            </div>

            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Batch Year *</label>
                    <input type="number" name="batch_year" id="eaBatch" class="form-control" required placeholder="e.g. 2024">
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Mobile Number *</label>
                    <input type="text" name="mobile" id="eaMobile" class="form-control" required>
                </div>
            </div>

            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Current Position / Title</label>
                    <input type="text" name="current_position" id="eaPosition" class="form-control" placeholder="e.g. Software Engineer">
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Company / Organization</label>
                    <input type="text" name="company" id="eaCompany" class="form-control" placeholder="e.g. TCS / Cognizant">
                </div>
            </div>

            <div class="form-group mb-3">
                <label style="font-weight:600; font-size:0.85rem;">LinkedIn Profile URL</label>
                <input type="url" name="linkedin_url" id="eaLinkedin" class="form-control" placeholder="https://linkedin.com/in/...">
            </div>

            <div class="form-group mb-4">
                <label style="font-weight:600; font-size:0.85rem;">Account Password <small class="text-muted">(Leave blank to keep unchanged)</small></label>
                <input type="text" name="password" id="eaPassword" class="form-control" placeholder="New Password">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="closeEditAlumniModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Alumni Profile</button>
            </div>
        </form>
    </div>
</div>

<script>
function showViewAlumniModal(a) {
    document.getElementById('vaName').textContent = a.name || 'Alumni';
    document.getElementById('vaEmail').textContent = a.email || '';
    document.getElementById('vaAvatar').textContent = (a.name || 'A').charAt(0).toUpperCase();
    document.getElementById('vaBatch').textContent = (a.batch_year || 'N/A') + ' Batch';
    document.getElementById('vaMobile').textContent = a.mobile || 'N/A';
    document.getElementById('vaPosition').textContent = a.current_position || 'N/A';
    document.getElementById('vaCompany').textContent = a.company || 'N/A';
    document.getElementById('vaPassword').textContent = a.password || '(Encrypted)';
    document.getElementById('viewAlumniModal').style.display = 'flex';

    if (window.anime) {
        anime({
            targets: '#viewAlumniModal .modal-card',
            scale: [0.85, 1],
            opacity: [0, 1],
            duration: 350,
            easing: 'easeOutCubic'
        });
    }
}

function closeViewAlumniModal() {
    document.getElementById('viewAlumniModal').style.display = 'none';
}

function showEditAlumniModal(a) {
    document.getElementById('eaUserId').value = a.id;
    document.getElementById('eaName').value = a.name || '';
    document.getElementById('eaEmail').value = a.email || '';
    document.getElementById('eaBatch').value = a.batch_year || '';
    document.getElementById('eaMobile').value = a.mobile || '';
    document.getElementById('eaPosition').value = a.current_position || '';
    document.getElementById('eaCompany').value = a.company || '';
    document.getElementById('eaLinkedin').value = a.linkedin_url || '';
    document.getElementById('eaPassword').value = a.password || '';
    document.getElementById('editAlumniModal').style.display = 'flex';

    if (window.anime) {
        anime({
            targets: '#editAlumniModal .modal-card',
            scale: [0.85, 1],
            opacity: [0, 1],
            duration: 350,
            easing: 'easeOutCubic'
        });
    }
}

function closeEditAlumniModal() {
    document.getElementById('editAlumniModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.anime) {
        anime({
            targets: '.alumni-row',
            opacity: [0, 1],
            translateY: [15, 0],
            delay: anime.stagger(60),
            easing: 'easeOutCubic',
            duration: 500
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
