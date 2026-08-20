<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('volunteer');

$msg = '';
$error = '';

// Fetch Volunteer Profile
$stmt = $pdo->prepare("SELECT u.*, v.* FROM users u JOIN volunteers v ON u.id = v.user_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

// Profile photo upload handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed) && $_FILES['profile_photo']['size'] <= 5 * 1024 * 1024) {
            $new_filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $dest_path    = $upload_dir . $new_filename;
            $db_path      = 'uploads/profiles/' . $new_filename;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest_path)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $stmt->execute([$db_path, $_SESSION['user_id']]);
                $_SESSION['user_data']['profile_photo'] = $db_path;
                $msg = "Profile picture updated successfully!";

                logUserActivity($pdo, $_SESSION['user_id'], $profile['name'], 'volunteer', 'Profile Photo Upload', "Uploaded new profile avatar picture");

                // Refresh profile
                $stmt = $pdo->prepare("SELECT u.*, v.* FROM users u JOIN volunteers v ON u.id = v.user_id WHERE u.id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $profile = $stmt->fetch();
            } else {
                $error = "Failed to save profile picture.";
            }
        } else {
            $error = "Invalid format or file size over 5MB. Only JPG, PNG, WEBP allowed.";
        }
    } else {
        $error = "Please select an image file to upload.";
    }
}

// Profile update handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $mobile = trim($_POST['mobile'] ?? '');
    $blood_group = $_POST['blood_group'] ?? 'O+';
    $year = $_POST['year'] ?? 'I';
    // Department cannot be changed by volunteer
    $department = $profile['department'];

    if (!empty($mobile)) {
        $stmt = $pdo->prepare("UPDATE volunteers SET mobile = ?, blood_group = ?, year = ? WHERE user_id = ?");
        $stmt->execute([$mobile, $blood_group, $year, $_SESSION['user_id']]);
        $msg = "Profile updated successfully!";

        logUserActivity($pdo, $_SESSION['user_id'], $profile['name'], 'volunteer', 'Profile Update', "Updated contact details: Mobile={$mobile}, Blood Group={$blood_group}, Year={$year}");

        // Refresh profile
        $stmt = $pdo->prepare("SELECT u.*, v.* FROM users u JOIN volunteers v ON u.id = v.user_id WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $profile = $stmt->fetch();
    } else {
        $error = "Mobile field is required.";
    }
}

// Fetch events stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$registered_events_count = $stmt->fetchColumn();

// Total hours served (officially marked by Admin in attendance table)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(hours), 0) FROM attendance WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$hours_served = (float)$stmt->fetchColumn();

// Total events attended (officially marked by Admin)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$events_attended = $stmt->fetchColumn();

// Attendance history list
$stmt = $pdo->prepare("
    SELECT a.hours, a.marked_at, e.title as event_title, e.event_date, e.location
    FROM attendance a
    LEFT JOIN events e ON a.event_id = e.id
    WHERE a.user_id = ?
    ORDER BY a.marked_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$attendance_history = $stmt->fetchAll();

// Announcements
$stmt = $pdo->query("SELECT * FROM announcements WHERE target_role IN ('all', 'volunteer') ORDER BY created_at DESC LIMIT 5");
$announcements = $stmt->fetchAll();

// Registered Events
$stmt = $pdo->prepare("SELECT e.title, e.event_date, e.location, e.status FROM events e JOIN event_registrations r ON e.id = r.event_id WHERE r.user_id = ? ORDER BY e.event_date DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$events = $stmt->fetchAll();

$pageTitle = 'Volunteer Dashboard | NSS TNGPTC';
require_once '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Volunteer Sidebar -->
    <aside class="sidebar glass-panel" id="volunteerSidebar">
        <div class="sidebar-header">
            <?= function_exists('getNssLogoSvg') ? getNssLogoSvg(36) : '<img src="../assets/images/nss-logo.png" alt="NSS Logo">' ?>
            <div>
                <h3>Volunteer Portal</h3>
                <span style="font-size:0.75rem; color:#f4a11d; font-weight:600;">NSS Unit Madurai-11</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="volunteer.php" class="active"><i class="fas fa-home"></i> Dashboard Overview</a>
            <a href="#profileCard"><i class="fas fa-user-edit"></i> Edit Profile</a>
            <a href="#attendanceCard"><i class="fas fa-user-check"></i> Attendance & Hours</a>
            <a href="#eventsCard"><i class="fas fa-calendar-alt"></i> My Events</a>
            <a href="#announcementsCard"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="../logout.php" class="text-danger" style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <h2>Welcome, <?= htmlspecialchars($profile['name'] ?? $_SESSION['name']) ?>!</h2>
                    <p class="text-muted" style="margin:0; font-size:0.85rem;"><i class="fas fa-award text-warning"></i> Motto: <strong>"Not Me, But You"</strong> (எனக்கல்ல, உனக்கே)</p>
                </div>
            </div>
            <div class="user-info d-flex align-items-center gap-2">
                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Volunteer (<?= htmlspecialchars($profile['register_number'] ?? 'Approved') ?>)</span>
            </div>
        </header>

        <?php if ($msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card glass-panel">
                <h4>NSS Hours Credited</h4>
                <div class="stat-value text-primary"><?= number_format($hours_served, 1) ?> hrs</div>
                <small class="text-muted">Target: 240.0 Hours for Certificate</small>
            </div>
            <div class="stat-card glass-panel">
                <h4>Events Attended</h4>
                <div class="stat-value text-success"><?= $events_attended ?></div>
                <small class="text-muted">Verified by Admin</small>
            </div>
            <div class="stat-card glass-panel">
                <h4>Events Registered</h4>
                <div class="stat-value text-accent"><?= $registered_events_count ?></div>
                <small class="text-muted">Enrolled camps & drives</small>
            </div>
            <div class="stat-card glass-panel">
                <h4>Blood Group</h4>
                <div class="stat-value"><span class="badge bg-danger text-white" style="font-size:1.4rem; padding:0.4rem 1rem;"><?= htmlspecialchars($profile['blood_group'] ?? 'N/A') ?></span></div>
            </div>
        </div>

        <!-- High-Contrast Hours Progress Bar -->
        <?php $progress_pct = min(100, round(($hours_served / 240.0) * 100, 1)); ?>
        <div class="dashboard-card glass-panel mb-4 span-12">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h3 style="margin:0;"><i class="fas fa-award text-warning"></i> NSS 240 Hours Completion Progress</h3>
                <div style="font-size:1.15rem; font-weight:800; color:#1b365d; background:#f1f5f9; padding:6px 16px; border-radius:20px; border:1px solid #cbd5e1;">
                    <?= number_format($hours_served, 1) ?> / 240.0 Hours <span style="color:#166534; font-weight:800;">(<?= $progress_pct ?>%)</span>
                </div>
            </div>
            <div style="background: #e2e8f0; height: 28px; border-radius: 14px; overflow: hidden; margin: 0.8rem 0; border: 1px solid #cbd5e1; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); position:relative;">
                <div style="background: linear-gradient(90deg, #1b365d 0%, #166534 50%, #f4a11d 100%); width: <?= max(4, $progress_pct) ?>%; height: 100%; border-radius:14px; transition: width 1s ease;"></div>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:0.85rem; font-weight:700; color:#475569;">
                <span>0 Hrs (Start)</span>
                <span style="color:#166534; font-weight:800;"><?= number_format($hours_served, 1) ?> Hrs Earned</span>
                <span style="color:#1b365d;">240 Hrs (Certificate Goal)</span>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Profile Card & Edit Form (Department Locked) -->
            <div class="dashboard-card glass-panel" id="profileCard" style="grid-column: span 6;">
                <h3><i class="fas fa-user-edit text-primary"></i> My Volunteer Profile & Photo</h3>
                
                <!-- Avatar Upload Badge -->
                <div style="text-align:center; margin-bottom:1.5rem; padding-bottom:1.25rem; border-bottom:1px solid #e2e8f0;">
                    <div style="position:relative; width:105px; height:105px; margin:0 auto 10px;">
                        <?php if (!empty($profile['profile_photo'])): ?>
                            <img src="../<?= htmlspecialchars($profile['profile_photo']) ?>" alt="Avatar" style="width:100%; height:100%; border-radius:50%; object-fit:cover; border:3px solid #f4a11d; box-shadow:0 4px 15px rgba(0,0,0,0.15);">
                        <?php else: ?>
                            <div style="width:100%; height:100%; border-radius:50%; background:linear-gradient(135deg,#1b365d 0%,#0d233a 100%); color:#f4a11d; display:flex; align-items:center; justify-content:center; font-size:2.5rem; font-weight:800; border:3px solid #f4a11d;">
                                <?= strtoupper(substr($profile['name'] ?? 'V', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <label for="profilePhotoInput" style="position:absolute; bottom:0; right:0; background:#f4a11d; color:#0d233a; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.3); border:2px solid white;" title="Upload / Change Profile Picture">
                            <i class="fas fa-camera" style="font-size:0.9rem;"></i>
                        </label>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" id="avatarForm" style="display:inline-block;">
                        <input type="hidden" name="upload_avatar" value="1">
                        <input type="file" name="profile_photo" id="profilePhotoInput" accept="image/*" style="display:none;" onchange="document.getElementById('avatarForm').submit();">
                    </form>
                    <small class="text-muted d-block">Click camera icon to upload profile picture (Max 5MB)</small>
                </div>

                <form method="POST" action="volunteer.php">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group mb-3">
                        <label>Full Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($profile['name']) ?>" readonly disabled style="background:#f1f5f9;">
                    </div>
                    <div class="form-group mb-3">
                        <label>Register Number</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($profile['register_number'] ?? '') ?>" readonly disabled style="background:#f1f5f9;">
                    </div>
                    <div class="form-group mb-3">
                        <label>Department <small style="color:#b71c1c; font-weight:700;">(Locked by Admin)</small></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($profile['department'] ?? '') ?>" readonly disabled style="background:#e2e8f0; cursor:not-allowed; font-weight:700; color:#1b365d;">
                    </div>
                    <div class="row">
                        <div class="col-6 form-group mb-3">
                            <label>Year</label>
                            <select name="year" class="form-control" required>
                                <option value="I" <?= ($profile['year'] ?? '') === 'I' ? 'selected' : '' ?>>I Year</option>
                                <option value="II" <?= ($profile['year'] ?? '') === 'II' ? 'selected' : '' ?>>II Year</option>
                                <option value="III" <?= ($profile['year'] ?? '') === 'III' ? 'selected' : '' ?>>III Year</option>
                            </select>
                        </div>
                        <div class="col-6 form-group mb-3">
                            <label>Blood Group</label>
                            <select name="blood_group" class="form-control" required>
                                <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                                    <option value="<?= $bg ?>" <?= ($profile['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label>Mobile Number</label>
                        <input type="tel" name="mobile" class="form-control" value="<?= htmlspecialchars($profile['mobile'] ?? '') ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Save Profile Changes</button>
                </form>
            </div>

            <!-- Official Attendance Log Card -->
            <div class="dashboard-card glass-panel" id="attendanceCard" style="grid-column: span 6;">
                <h3><i class="fas fa-user-check text-success"></i> Verified Attendance History</h3>
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Date</th>
                                <th>NSS Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_history as $att): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($att['event_title'] ?? 'Parade / Special Duty') ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($att['location'] ?? 'College Campus') ?></small>
                                </td>
                                <td><small><?= date('d M Y', strtotime($att['marked_at'])) ?></small></td>
                                <td><span class="badge bg-success text-white">+<?= floatval($att['hours']) ?> hrs</span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$attendance_history): ?>
                            <tr><td colspan="3" class="text-center py-4">No attendance records credited yet by Admin. Participate in NSS camps to earn hours!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Registered Events -->
            <div class="dashboard-card glass-panel" id="eventsCard" style="grid-column: span 6;">
                <h3><i class="fas fa-calendar-alt text-primary"></i> My Registered Events</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                                <td><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
                                <td><span class="badge status-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$events): ?>
                            <tr><td colspan="3" class="text-center">No events registered yet. <a href="../events.php">Browse Events</a></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Announcements -->
            <div class="dashboard-card glass-panel" id="announcementsCard" style="grid-column: span 6;">
                <h3><i class="fas fa-bullhorn text-accent"></i> NSS Announcements</h3>
                <ul class="announcement-list" style="list-style:none; padding:0; margin:0;">
                    <?php foreach ($announcements as $ann): ?>
                    <li style="border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem; margin-bottom:0.75rem;">
                        <h4 style="margin:0 0 0.25rem 0; font-size:1rem; color:#1b365d;"><?= htmlspecialchars($ann['title']) ?></h4>
                        <p style="margin:0; font-size:0.88rem; color:#475569;"><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                        <small class="text-muted"><i class="fas fa-clock"></i> <?= date('d M Y, h:i A', strtotime($ann['created_at'])) ?></small>
                    </li>
                    <?php endforeach; ?>
                    <?php if (!$announcements): ?>
                        <li>No announcements at present.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
