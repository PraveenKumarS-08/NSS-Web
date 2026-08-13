<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('alumni');

$msg = '';
$error = '';

// Handle Alumni Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_alumni_profile'])) {
    $mobile = trim($_POST['mobile'] ?? '');
    $batch_year = trim($_POST['batch_year'] ?? '');
    $current_position = trim($_POST['current_position'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');

    $stmt = $pdo->prepare("UPDATE alumni SET mobile = ?, batch_year = ?, current_position = ?, company = ?, linkedin_url = ? WHERE user_id = ?");
    $stmt->execute([$mobile, $batch_year, $current_position, $company, $linkedin_url, $_SESSION['user_id']]);
    $msg = "Alumni profile updated successfully!";
}

// Fetch Alumni Profile
$stmt = $pdo->prepare("SELECT u.*, a.* FROM users u JOIN alumni a ON u.id = a.user_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

// Fetch other approved Alumni network members
$alumni_network = $pdo->query("
    SELECT u.name, u.email, a.batch_year, a.current_position, a.company, a.linkedin_url
    FROM users u JOIN alumni a ON u.id = a.user_id
    WHERE u.status = 'approved' AND u.id != " . (int)$_SESSION['user_id'] . "
    ORDER BY a.batch_year DESC LIMIT 10
")->fetchAll();

// Fetch Announcements for Alumni
$stmt = $pdo->query("SELECT * FROM announcements WHERE target_role IN ('all', 'alumni') ORDER BY created_at DESC LIMIT 5");
$announcements = $stmt->fetchAll();

// Fetch Latest Events
$events = $pdo->query("SELECT title, event_date, location, status FROM events ORDER BY event_date DESC LIMIT 5")->fetchAll();

$pageTitle = 'Alumni Portal | NSS TNGPTC';
require_once '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <aside class="sidebar glass-panel" id="alumniSidebar">
        <div class="sidebar-header">
            <?= function_exists('getNssLogoSvg') ? getNssLogoSvg(36) : '<img src="../assets/images/nss-logo.png" alt="NSS Logo">' ?>
            <div>
                <h3>Alumni Portal</h3>
                <span style="font-size:0.75rem; color:#f4a11d; font-weight:600;">NSS TNGPTC Madurai</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="alumni.php" class="active"><i class="fas fa-home"></i> Overview</a>
            <a href="#alumniProfileCard"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="#networkCard"><i class="fas fa-users"></i> Alumni Network</a>
            <a href="#alumniEventsCard"><i class="fas fa-calendar-alt"></i> NSS Events</a>
            <a href="../logout.php" class="text-danger" style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-outline btn-sm sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h2>Welcome, <?= htmlspecialchars($profile['name'] ?? $_SESSION['name']) ?>!</h2>
                    <p class="text-muted" style="margin:0; font-size:0.85rem;"><i class="fas fa-heart text-danger"></i> NSS Motto: <strong>"Not Me, But You"</strong></p>
                </div>
            </div>
            <div class="user-info">
                <span class="badge bg-info text-white"><i class="fas fa-graduation-cap"></i> NSS Alumni (Batch <?= htmlspecialchars($profile['batch_year'] ?? 'N/A') ?>)</span>
            </div>
        </header>

        <?php if ($msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="stats-grid">
            <div class="stat-card glass-panel">
                <h4>Batch Year</h4>
                <div class="stat-value text-primary"><?= htmlspecialchars($profile['batch_year'] ?? 'N/A') ?></div>
            </div>
            <div class="stat-card glass-panel">
                <h4>Current Role</h4>
                <div class="stat-value text-accent" style="font-size:1.3rem;"><?= htmlspecialchars($profile['current_position'] ?? 'NSS Member') ?></div>
            </div>
            <div class="stat-card glass-panel">
                <h4>Organization</h4>
                <div class="stat-value text-success" style="font-size:1.3rem;"><?= htmlspecialchars($profile['company'] ?? 'TNGPTC') ?></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Alumni Profile Edit Card -->
            <div class="dashboard-card glass-panel" id="alumniProfileCard" style="grid-column: span 6;">
                <h3><i class="fas fa-user-edit text-primary"></i> Edit Alumni Profile</h3>
                <form method="POST">
                    <div class="form-group mb-3">
                        <label>Full Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($profile['name']) ?>" disabled style="background:#f1f5f9;">
                    </div>
                    <div class="form-group mb-3">
                        <label>Email Address</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($profile['email']) ?>" disabled style="background:#f1f5f9;">
                    </div>
                    <div class="row">
                        <div class="col-6 form-group mb-3">
                            <label>Batch Year</label>
                            <input type="text" name="batch_year" class="form-control" value="<?= htmlspecialchars($profile['batch_year'] ?? '') ?>" placeholder="e.g. 2022" required>
                        </div>
                        <div class="col-6 form-group mb-3">
                            <label>Mobile Number</label>
                            <input type="tel" name="mobile" class="form-control" value="<?= htmlspecialchars($profile['mobile'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label>Current Position / Title</label>
                        <input type="text" name="current_position" class="form-control" value="<?= htmlspecialchars($profile['current_position'] ?? '') ?>" placeholder="e.g. Design Engineer">
                    </div>
                    <div class="form-group mb-3">
                        <label>Company / Institution</label>
                        <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($profile['company'] ?? '') ?>" placeholder="e.g. L&T Construction">
                    </div>
                    <div class="form-group mb-3">
                        <label>LinkedIn Profile URL</label>
                        <input type="url" name="linkedin_url" class="form-control" value="<?= htmlspecialchars($profile['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.in/in/yourname">
                    </div>
                    <button type="submit" name="update_alumni_profile" value="1" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Save Profile</button>
                </form>
            </div>

            <!-- Alumni Network Directory -->
            <div class="dashboard-card glass-panel" id="networkCard" style="grid-column: span 6;">
                <h3><i class="fas fa-users text-success"></i> NSS Alumni Network Directory</h3>
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name & Batch</th>
                                <th>Position & Company</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumni_network as $alm): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($alm['name']) ?></strong><br>
                                    <span class="badge bg-primary text-white">Batch <?= htmlspecialchars($alm['batch_year']) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($alm['current_position'] ?? 'Alumnus') ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($alm['company'] ?? 'TNGPTC') ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($alm['linkedin_url'])): ?>
                                        <a href="<?= htmlspecialchars($alm['linkedin_url']) ?>" target="_blank" class="btn btn-sm btn-outline"><i class="fab fa-linkedin"></i> Profile</a>
                                    <?php else: ?>
                                        <small class="text-muted"><?= htmlspecialchars($alm['email']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$alumni_network): ?>
                            <tr><td colspan="3" class="text-center py-3">No other alumni members registered yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Events List -->
            <div class="dashboard-card glass-panel" id="alumniEventsCard" style="grid-column: span 12;">
                <h3><i class="fas fa-calendar-alt text-primary"></i> NSS College Events & Alumni Guest Invitations</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Date & Time</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                                <td><?= date('d M Y, h:i A', strtotime($ev['event_date'])) ?></td>
                                <td><?= htmlspecialchars($ev['location']) ?></td>
                                <td><span class="badge status-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
