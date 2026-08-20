<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$settings_msg = '';

// Ensure site_settings table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT)");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_counter_settings') {
    $keys = ['stat_1_label', 'stat_1_val', 'stat_2_label', 'stat_2_val', 'stat_3_label', 'stat_3_val', 'stat_4_label', 'stat_4_val'];
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($keys as $key) {
        $val = trim($_POST[$key] ?? '');
        $stmt->execute([$key, $val]);
    }
    $settings_msg = "Homepage counter settings updated successfully!";
}

// Fetch current site settings
$site_settings = [
    'stat_1_label' => 'ACTIVE VOLUNTEERS',
    'stat_1_val'   => '',
    'stat_2_label' => 'CAMPS & DRIVES',
    'stat_2_val'   => '',
    'stat_3_label' => 'YEARS OF SERVICE',
    'stat_3_val'   => '75+',
    'stat_4_label' => 'ALUMNI NETWORK',
    'stat_4_val'   => '500+'
];
$dbSettings = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach ($dbSettings as $k => $v) {
    $site_settings[$k] = $v;
}

// Fetch counts (Only count approved active members)
$total_volunteers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='volunteer' AND status='approved'")->fetchColumn();
$dbAlumniUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='alumni' AND status='approved'")->fetchColumn();
$dbAlumniTable   = (int)$pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$total_alumni     = max($dbAlumniUsers, $dbAlumniTable);
$pending_approvals= (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending' AND role != 'admin'")->fetchColumn();
$total_events     = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$total_gallery    = (int)$pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();

// Fetch dept stats for chart (grouped by trimmed department)
$dept_stats = $pdo->query("SELECT TRIM(v.department) as dept_name, COUNT(*) as count FROM volunteers v JOIN users u ON v.user_id = u.id WHERE u.role = 'volunteer' AND u.status = 'approved' AND v.department IS NOT NULL AND v.department != '' GROUP BY TRIM(v.department)")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch academic year stats for chart (Replacing Activity Status Overview)
$year_stats = $pdo->query("SELECT CONCAT(v.year, ' Year') as year_name, COUNT(*) as count FROM volunteers v JOIN users u ON v.user_id = u.id WHERE u.role = 'volunteer' AND u.status = 'approved' AND v.year IS NOT NULL AND v.year != '' GROUP BY v.year ORDER BY FIELD(v.year, 'I', 'II', 'III')")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch recent pending users
$recent_pending = $pdo->query("SELECT id, name, email, role, created_at FROM users WHERE status='pending' AND role != 'admin' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Admin Dashboard | NSS TNGPTC Madurai';
require_once '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <h2><i class="fas fa-gauge text-primary"></i> Admin Overview Dashboard</h2>
            <div class="user-info">
                <span class="badge status-approved" style="font-size:0.85rem; padding: 6px 14px;"><i class="fas fa-user-shield"></i> Logged in as: <strong><?= htmlspecialchars($_SESSION['name'] ?? 'NSS Admin') ?></strong></span>
            </div>
        </header>

        <!-- Stats Grid with Alumni Count Card -->
        <div class="stats-grid">
            <div class="stat-card glass-panel" data-aos="fade-up" data-aos-delay="100">
                <h4>Total Volunteers</h4>
                <div class="stat-value"><?= $total_volunteers ?></div>
            </div>
            <div class="stat-card glass-panel" data-aos="fade-up" data-aos-delay="200">
                <h4>NSS Alumni Network</h4>
                <div class="stat-value text-primary"><?= $total_alumni ?></div>
            </div>
            <div class="stat-card glass-panel" data-aos="fade-up" data-aos-delay="300">
                <h4>Pending Approvals</h4>
                <div class="stat-value text-accent"><?= $pending_approvals ?></div>
            </div>
            <div class="stat-card glass-panel" data-aos="fade-up" data-aos-delay="400">
                <h4>Events & Camps</h4>
                <div class="stat-value"><?= $total_events ?></div>
            </div>
            <div class="stat-card glass-panel" data-aos="fade-up" data-aos-delay="500">
                <h4>Gallery Images</h4>
                <div class="stat-value"><?= $total_gallery ?></div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div style="display:grid; grid-template-columns: 1.6fr 1fr; gap:24px; margin-bottom:24px;">
            <div class="dashboard-card glass-panel">
                <h3><i class="fas fa-chart-bar text-accent"></i> Volunteers by Department</h3>
                <div style="position:relative; height:320px;">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>

            <div class="dashboard-card glass-panel">
                <h3><i class="fas fa-graduation-cap text-accent"></i> Volunteers by Academic Year</h3>
                <div style="position:relative; height:320px;">
                    <canvas id="yearChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Approvals & Quick Actions -->
        <div style="display:grid; grid-template-columns: 1.6fr 1fr; gap:24px;">
            <div class="dashboard-card glass-panel">
                <h3><i class="fas fa-user-clock text-primary"></i> Recent Pending Approvals</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name & Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_pending as $user): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($user['name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($user['email']) ?></small></td>
                                <td><span class="badge status-pending"><?= ucfirst($user['role']) ?></span></td>
                                <td>
                                    <form method="POST" action="admin-volunteers.php" style="display:inline-flex; gap:6px;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-primary" title="Approve"><i class="fas fa-check"></i></button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline text-danger" title="Reject"><i class="fas fa-times"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(!$recent_pending): ?>
                            <tr><td colspan="3" class="text-center py-3">No pending registration approvals.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="dashboard-card glass-panel">
                <h3><i class="fas fa-bolt text-accent"></i> Quick Admin Actions</h3>
                <div class="quick-actions" style="display:flex; flex-direction:column; gap:12px;">
                    <a href="admin-events.php" class="btn btn-primary" style="text-align:center;"><i class="fas fa-plus"></i> Add New Event / Camp</a>
                    <a href="admin-gallery.php" class="btn btn-outline" style="text-align:center;"><i class="fas fa-upload"></i> Upload Gallery Photos</a>
                    <a href="admin-attendance.php" class="btn btn-outline" style="text-align:center;"><i class="fas fa-user-check"></i> Manage Attendance & Hours</a>
                    <a href="admin-announcements.php" class="btn btn-outline" style="text-align:center;"><i class="fas fa-bullhorn"></i> Post Announcement</a>
                </div>
            </div>
        </div>

        <!-- Homepage Counter Settings Manager -->
        <div class="dashboard-card glass-panel mt-4" style="margin-top:24px;">
            <h3><i class="fas fa-sliders-h text-accent"></i> Customize Homepage Counter Bar</h3>
            <p class="text-muted" style="font-size:0.9rem; margin-bottom:1.5rem;">
                Customize the titles and numbers displayed in the floating counter ribbon on the public homepage. Leave "Custom Value" blank to auto-calculate from live database counts.
            </p>

            <?php if (!empty($settings_msg)): ?>
                <div class="alert alert-success" style="margin-bottom:1.5rem;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($settings_msg) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="save_counter_settings">
                
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-bottom:1.5rem;">
                    <!-- Box 1 -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:1.25rem; border-radius:12px;">
                        <h5 style="color:#1b365d; margin-bottom:0.75rem;"><i class="fas fa-users text-primary"></i> Counter Box 1</h5>
                        <div class="form-group mb-2">
                            <label style="font-size:0.85rem; font-weight:600;">Title / Label</label>
                            <input type="text" name="stat_1_label" class="form-control" value="<?= htmlspecialchars($site_settings['stat_1_label']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size:0.85rem; font-weight:600;">Custom Value <small class="text-muted">(Blank = Auto DB Count)</small></label>
                            <input type="text" name="stat_1_val" class="form-control" placeholder="Auto DB Count" value="<?= htmlspecialchars($site_settings['stat_1_val']) ?>">
                        </div>
                    </div>

                    <!-- Box 2 -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:1.25rem; border-radius:12px;">
                        <h5 style="color:#1b365d; margin-bottom:0.75rem;"><i class="fas fa-calendar-check text-primary"></i> Counter Box 2</h5>
                        <div class="form-group mb-2">
                            <label style="font-size:0.85rem; font-weight:600;">Title / Label</label>
                            <input type="text" name="stat_2_label" class="form-control" value="<?= htmlspecialchars($site_settings['stat_2_label']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size:0.85rem; font-weight:600;">Custom Value <small class="text-muted">(Blank = Auto DB Count)</small></label>
                            <input type="text" name="stat_2_val" class="form-control" placeholder="Auto DB Count" value="<?= htmlspecialchars($site_settings['stat_2_val']) ?>">
                        </div>
                    </div>

                    <!-- Box 3 -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:1.25rem; border-radius:12px;">
                        <h5 style="color:#1b365d; margin-bottom:0.75rem;"><i class="fas fa-award text-accent"></i> Counter Box 3</h5>
                        <div class="form-group mb-2">
                            <label style="font-size:0.85rem; font-weight:600;">Title / Label</label>
                            <input type="text" name="stat_3_label" class="form-control" value="<?= htmlspecialchars($site_settings['stat_3_label']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size:0.85rem; font-weight:600;">Custom Value <small class="text-muted">(e.g. 75+)</small></label>
                            <input type="text" name="stat_3_val" class="form-control" value="<?= htmlspecialchars($site_settings['stat_3_val']) ?>" required>
                        </div>
                    </div>

                    <!-- Box 4 -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:1.25rem; border-radius:12px;">
                        <h5 style="color:#1b365d; margin-bottom:0.75rem;"><i class="fas fa-user-graduate text-primary"></i> Counter Box 4</h5>
                        <div class="form-group mb-2">
                            <label style="font-size:0.85rem; font-weight:600;">Title / Label</label>
                            <input type="text" name="stat_4_label" class="form-control" value="<?= htmlspecialchars($site_settings['stat_4_label']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size:0.85rem; font-weight:600;">Custom Value <small class="text-muted">(e.g. 500+)</small></label>
                            <input type="text" name="stat_4_val" class="form-control" value="<?= htmlspecialchars($site_settings['stat_4_val']) ?>" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Counter Settings</button>
            </form>
        </div>
    </main>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Department Bar Chart
    const deptCtx = document.getElementById('deptChart');
    if (deptCtx) {
        const deptLabels = <?= json_encode(array_keys($dept_stats ?: [])) ?>;
        const deptData = <?= json_encode(array_values($dept_stats ?: [])) ?>;
        
        new Chart(deptCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: deptLabels.map(l => l.length > 22 ? l.substring(0, 20) + '...' : l),
                datasets: [{
                    label: 'Approved Volunteers',
                    data: deptData,
                    backgroundColor: 'rgba(27, 54, 93, 0.85)',
                    borderColor: '#f4a11d',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    barPercentage: 0.65
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1, color: '#475569' }, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { ticks: { color: '#475569', maxRotation: 45, font: { size: 10 } }, grid: { display: false } }
                }
            }
        });
    }

    // 2. Volunteers by Academic Year Doughnut Chart
    const yearCtx = document.getElementById('yearChart');
    if (yearCtx) {
        const yearLabels = <?= json_encode(array_keys($year_stats ?: ['I Year' => 0, 'II Year' => 0, 'III Year' => 0])) ?>;
        const yearData = <?= json_encode(array_values($year_stats ?: ['I Year' => 0, 'II Year' => 0, 'III Year' => 0])) ?>;
        
        new Chart(yearCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: yearLabels,
                datasets: [{
                    data: yearData,
                    backgroundColor: ['#1b365d', '#f4a11d', '#166534', '#0284c7'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 } } }
                }
            }
        });
    }

    // 3. Anime.js Entrance Animations for Admin Stat Cards & Dashboard Layout
    if (window.anime) {
        anime({
            targets: '.stat-card',
            scale: [0.9, 1],
            opacity: [0, 1],
            translateY: [20, 0],
            delay: anime.stagger(100),
            easing: 'easeOutCubic',
            duration: 600
        });

        anime({
            targets: '.dashboard-card',
            opacity: [0, 1],
            translateY: [25, 0],
            delay: anime.stagger(120, {start: 300}),
            easing: 'easeOutQuad',
            duration: 700
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
