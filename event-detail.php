<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$event_id = (int)($_GET['id'] ?? 0);
if ($event_id <= 0) {
    header("Location: events.php");
    exit;
}

// Fetch event details
$stmt = $pdo->prepare("SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) as reg_count FROM events e WHERE e.id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    // Auto-heal fallback event if absent from DB
    try {
        $pdo->exec("
            INSERT INTO events (id, title, description, event_date, end_date, location, category, status, created_by) VALUES
            (1, 'Blood Donation Camp 2026', 'Annual blood donation camp organized by NSS Unit, TNGPTC Madurai in collaboration with Rajaji Government Hospital.', '2026-09-15 09:00:00', '2026-09-15 17:00:00', 'College Premises, Madurai-11', 'Health', 'upcoming', 1),
            (2, 'Tree Plantation & Clean Campus Drive', 'Planting 500 indigenous saplings in and around the campus as part of Green India Mission.', '2026-10-01 08:00:00', '2026-10-01 13:00:00', 'College Campus', 'Environment', 'upcoming', 1),
            (3, 'Village Adoption Program - Phase III', 'NSS volunteers serving the adopted village - community development, health awareness, and literacy programs.', '2026-11-10 07:00:00', '2026-11-10 18:00:00', 'Alanganallur Village, Madurai', 'Community', 'upcoming', 1),
            (4, 'National Disaster Management Workshop', 'Hands-on training session on emergency first-aid, fire safety, and disaster relief techniques.', '2026-12-05 10:00:00', '2026-12-05 16:00:00', 'College Auditorium', 'Training', 'upcoming', 1)
            ON DUPLICATE KEY UPDATE title=VALUES(title);
        ");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {}
}

if (!$event) {
    header("Location: events.php");
    exit;
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

$user_id = $_SESSION['user_id'] ?? 0;
$is_registered = false;
$user_reg_info = null;

if ($user_id > 0) {
    $rStmt = $pdo->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $rStmt->execute([$event_id, $user_id]);
    $user_reg_info = $rStmt->fetch(PDO::FETCH_ASSOC);
    if ($user_reg_info) {
        $is_registered = true;
    }
}

// Fetch user defaults if logged in
$user_default_name = $_SESSION['name'] ?? '';
$user_default_regno = '';
$user_default_dept = '';
$user_default_year = '';
$user_default_mobile = '';

if ($user_id > 0) {
    $uStmt = $pdo->prepare("SELECT u.name, v.register_number, v.department, v.year, v.mobile FROM users u LEFT JOIN volunteers v ON u.id = v.user_id WHERE u.id = ?");
    $uStmt->execute([$user_id]);
    $uData = $uStmt->fetch();
    if ($uData) {
        $user_default_name = $uData['name'] ?? $user_default_name;
        $user_default_regno = $uData['register_number'] ?? '';
        $user_default_dept = $uData['department'] ?? '';
        $user_default_year = $uData['year'] ?? '';
        $user_default_mobile = $uData['mobile'] ?? '';
    }
}

$msg = '';
$error = '';

// Process Detailed Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_registration'])) {
    if (!$user_id) {
        header("Location: login.php");
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $register_number = trim($_POST['register_number'] ?? '');
    $student_mobile = trim($_POST['student_mobile'] ?? '');
    $parent_mobile = trim($_POST['parent_mobile'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $year = $_POST['year'] ?? '';
    $department = $_POST['department'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    try {
        $stmt = $pdo->prepare("
            INSERT INTO event_registrations (event_id, user_id, student_mobile, parent_mobile, address, age, year, department, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$event_id, $user_id, $student_mobile, $parent_mobile, $address, $age, $year, $department, $remarks]);
        $msg = "Congratulations! You have successfully registered for " . htmlspecialchars($event['title']) . ".";
        $is_registered = true;

        logUserActivity($pdo, $user_id, $_SESSION['name'] ?? 'Volunteer', 'volunteer', 'Event Registration', "Registered for event: " . $event['title']);
    } catch (PDOException $e) {
        $errCode = $e->errorInfo[1] ?? 0;
        if ($errCode == 1062) {
            $msg = "You are already registered for this event.";
            $is_registered = true;
        } else {
            $chk = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
            $chk->execute([$event_id, $user_id]);
            if ($chk->fetch()) {
                $msg = "You are already registered for this event.";
                $is_registered = true;
            } else {
                $error = "Registration note: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

$eventTimestamp = strtotime($event['event_date']);
$now = time();

// Determine Status
$rawStatus = strtolower($event['status'] ?? 'upcoming');
if ($rawStatus === 'postponed' || $rawStatus === 'cancelled') {
    $status = $rawStatus;
} elseif ($eventTimestamp > $now) {
    $status = 'upcoming';
} elseif ($eventTimestamp <= $now && $eventTimestamp >= ($now - 86400)) {
    $status = 'ongoing';
} else {
    $status = 'completed';
}

$imgSrc = !empty($event['image']) ? $event['image'] : (!empty($event['image_path']) ? $event['image_path'] : null);

$pageTitle = htmlspecialchars($event['title']) . ' | NSS TNGPTC Madurai';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <span class="badge" style="background:var(--accent); color:#ffffff; padding:6px 16px; border-radius:20px; font-weight:700; margin-bottom:12px; display:inline-block; font-size:0.85rem;"><?= htmlspecialchars($event['category'] ?? 'General') ?></span>
    <h1 class="page-title" style="font-size:2.8rem; color:white; margin-bottom: 0.5rem;"><?= htmlspecialchars($event['title']) ?></h1>
    <p style="color:#cbd5e1; font-size:1.1rem;"><i class="fas fa-map-marker-alt text-accent"></i> <?= htmlspecialchars($event['location']) ?> • <i class="fas fa-calendar-alt text-accent"></i> <?= date('F d, Y - h:i A', $eventTimestamp) ?></p>
</div>

<style>
.event-detail-grid {
    display: grid;
    grid-template-columns: 1.8fr 1.2fr;
    gap: 40px;
    align-items: start;
}
@media (max-width: 900px) {
    .event-detail-grid {
        grid-template-columns: 1fr !important;
        gap: 24px !important;
    }
    .event-detail-grid img {
        height: auto !important;
        max-height: 280px !important;
    }
    .page-hero {
        padding: 70px 4% 30px !important;
    }
    .page-hero .page-title {
        font-size: 2rem !important;
    }
    .form-row {
        grid-template-columns: 1fr !important;
    }
}
@media (max-width: 480px) {
    .page-hero .page-title {
        font-size: 1.6rem !important;
    }
    .glass-panel {
        padding: 1.25rem !important;
    }
}
</style>

<section class="section" style="padding: 50px 5%; max-width: 1100px; margin: 0 auto;">
    <?php if ($msg): ?>
        <div class="alert alert-success" style="margin-bottom:30px;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom:30px;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="event-detail-grid">
        <!-- Left: Event Details -->
        <div>
            <?php if ($imgSrc): ?>
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($event['title']) ?>" style="width:100%; height:380px; object-fit:cover; border-radius:18px; margin-bottom:30px; box-shadow:0 10px 30px rgba(0,0,0,0.1); border:1px solid var(--border);">
            <?php endif; ?>

            <div class="glass-panel" style="background:#ffffff; border:1px solid var(--border); border-radius:18px; padding:2rem; margin-bottom:30px; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
                <h2 style="color:var(--primary); font-family:'Outfit',sans-serif; margin-bottom:1rem; font-size:1.6rem;">About This Activity</h2>
                <p style="color:var(--text-muted); font-size:1.02rem; line-height:1.8; white-space:pre-line; margin:0;"><?= htmlspecialchars($event['description'] ?? 'No detailed description available.') ?></p>
            </div>
        </div>

        <!-- Right: Registration Box / Status -->
        <div class="glass-panel" style="background:#ffffff; border:1px solid var(--border); border-radius:18px; padding:2rem; box-shadow:0 10px 30px rgba(0,0,0,0.04); position:sticky; top:90px;">
            <h3 style="color:var(--primary); font-family:'Outfit',sans-serif; margin-bottom:1.25rem; font-size:1.35rem;"><i class="fas fa-clipboard-check text-accent"></i> Registration Info</h3>

            <div style="margin-bottom:1.5rem; background:var(--bg); padding:1.25rem; border-radius:12px; border:1px solid var(--border);">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="color:var(--text-muted); font-size:0.9rem;">Status:</span>
                    <?php if ($status === 'upcoming'): ?>
                        <span class="badge" style="background:var(--primary); color:#ffffff; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.82rem;">Upcoming</span>
                    <?php elseif ($status === 'ongoing'): ?>
                        <span class="badge" style="background:#dcfce7; color:#166534; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.82rem;"><i class="fas fa-spinner fa-spin"></i> Ongoing Now</span>
                    <?php elseif ($status === 'completed'): ?>
                        <span class="badge" style="background:#f1f5f9; color:#475569; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.82rem;">Completed</span>
                    <?php elseif ($status === 'postponed'): ?>
                        <span class="badge" style="background:#fef3c7; color:#92400e; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.82rem;">Postponed</span>
                    <?php else: ?>
                        <span class="badge" style="background:#fee2e2; color:#991b1b; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.82rem;">Cancelled</span>
                    <?php endif; ?>
                </div>

                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="color:var(--text-muted); font-size:0.9rem;">Total Enrolled:</span>
                    <strong style="color:var(--primary); font-size:1rem;"><?= $event['reg_count'] ?> Volunteers</strong>
                </div>

                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-muted); font-size:0.9rem;">Date & Time:</span>
                    <strong style="color:var(--primary); font-size:0.88rem;"><?= date('d M Y, h:i A', $eventTimestamp) ?></strong>
                </div>
            </div>

            <!-- Action Button / Form -->
            <?php if ($status === 'completed'): ?>
                <button class="btn btn-outline btn-block" style="width:100%; padding:0.85rem; opacity:0.8; cursor:not-allowed;" disabled>
                    <i class="fas fa-flag-checkered"></i> Event Over — Thanks for Participating!
                </button>
            <?php elseif ($status === 'postponed' || $status === 'cancelled'): ?>
                <button class="btn btn-outline btn-block" style="width:100%; padding:0.85rem; color:#92400e; border-color:#fde68a; background:#fffbe8; cursor:not-allowed;" disabled>
                    <i class="fas fa-exclamation-triangle"></i> Event <?= ucfirst($status) ?>
                </button>
            <?php elseif ($is_registered): ?>
                <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#166534; padding:1.25rem; border-radius:12px; text-align:center;">
                    <i class="fas fa-check-circle" style="font-size:2rem; margin-bottom:8px;"></i>
                    <h4 style="margin:0 0 4px; font-size:1.1rem;">Already Registered!</h4>
                    <p style="margin:0; font-size:0.85rem;">You are officially enrolled for this NSS event.</p>
                </div>
                <button class="btn btn-success btn-block mt-3" style="width:100%; padding:0.85rem; background:#166534; border:none; color:white; font-weight:700;" disabled>
                    <i class="fas fa-user-check"></i> Already Registered
                </button>
            <?php elseif ($user_id > 0): ?>
                <!-- Registration Form -->
                <form method="POST">
                    <input type="hidden" name="submit_registration" value="1">

                    <div class="form-group mb-2">
                        <label style="font-weight:600; font-size:0.82rem; color:#334155; display:block; margin-bottom:4px;">Full Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user_default_name) ?>" required style="width:100%; padding:0.6rem 0.8rem; border:1px solid #cbd5e1; border-radius:8px;">
                    </div>

                    <div class="form-group mb-2">
                        <label style="font-weight:600; font-size:0.82rem; color:#334155; display:block; margin-bottom:4px;">Register Number *</label>
                        <input type="text" name="register_number" class="form-control" value="<?= htmlspecialchars($user_default_regno) ?>" placeholder="e.g. 2024CE001" required style="width:100%; padding:0.6rem 0.8rem; border:1px solid #cbd5e1; border-radius:8px;">
                    </div>

                    <div class="form-row mb-2" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div class="form-group">
                            <label style="font-weight:600; font-size:0.82rem; color:#334155; display:block; margin-bottom:4px;">Your Mobile *</label>
                            <input type="tel" name="student_mobile" pattern="[0-9]{10}" class="form-control" value="<?= htmlspecialchars($user_default_mobile) ?>" placeholder="10-digit mobile" required style="width:100%; padding:0.6rem 0.8rem; border:1px solid #cbd5e1; border-radius:8px;">
                        </div>
                        <div class="form-group">
                            <label style="font-weight:600; font-size:0.82rem; color:#334155; display:block; margin-bottom:4px;">Parent Mobile *</label>
                            <input type="tel" name="parent_mobile" pattern="[0-9]{10}" class="form-control" placeholder="10-digit mobile" required style="width:100%; padding:0.6rem 0.8rem; border:1px solid #cbd5e1; border-radius:8px;">
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label style="font-weight:600; font-size:0.82rem; color:#334155; display:block; margin-bottom:4px;">Department *</label>
                        <select name="department" class="form-control" required style="width:100%; padding:0.6rem 0.8rem; border:1px solid #cbd5e1; border-radius:8px;">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" <?= $user_default_dept === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row mb-2" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div class="form-group">
                            <label style="font-weight:600; font-size:0.82rem; color:#334155; display:block; margin-bottom:4px;">Year *</label>
                            <select name="year" class="form-control" required style="width:100%; padding:0.6rem 0.8rem; border:1px solid #cbd5e1; border-radius:8px;">
                                <option value="">Year</option>
                                <option value="I" <?= $user_default_year === 'I' ? 'selected' : '' ?>>I Year</option>
                                <option value="II" <?= $user_default_year === 'II' ? 'selected' : '' ?>>II Year</option>
                                <option value="III" <?= $user_default_year === 'III' ? 'selected' : '' ?>>III Year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="font-weight:600; font-size:0.82rem; color:#334155; display:block; margin-bottom:4px;">Age *</label>
                            <input type="number" name="age" class="form-control" placeholder="18" min="15" max="30" required style="width:100%; padding:0.6rem 0.8rem; border:1px solid #cbd5e1; border-radius:8px;">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-weight:600; font-size:0.82rem; color:#334155; display:block; margin-bottom:4px;">Address *</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Full residential address..." required style="width:100%; padding:0.6rem 0.8rem; border:1px solid #cbd5e1; border-radius:8px;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.8rem; font-weight:700;"><i class="fas fa-edit"></i> Complete Event Registration</button>
                </form>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-block" style="width:100%; text-align:center; padding:0.8rem;"><i class="fas fa-sign-in-alt"></i> Login to Register</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
