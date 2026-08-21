<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$error = '';
$success = '';

// Handle Create / Edit / Delete Event
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $event_id = (int)($_POST['event_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_date = $_POST['event_date'] ?? '';
        $end_date = $_POST['end_date'] ?? null;
        $location = trim($_POST['location'] ?? '');
        $category = $_POST['category'] ?? 'General';
        $max_participants = (int)($_POST['max_participants'] ?? 0);
        $status = $_POST['status'] ?? 'upcoming';

        // Handle custom category
        if ($category === '__custom__') {
            $category = trim($_POST['custom_category'] ?? 'General');
            if (empty($category)) $category = 'General';
        }

        if (empty($title) || empty($event_date) || empty($location)) {
            $error = "Title, Start Date, and Location are required.";
        } else {
            // Handle Image Upload organized by Year & Category
            $image_path = $_POST['existing_image'] ?? null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $event_year = !empty($event_date) ? date('Y', strtotime($event_date)) : date('Y');
                $cat_slug   = preg_replace('/[^a-zA-Z0-9_\-]/', '_', str_replace(' ', '_', $category));

                $upload_rel = "uploads/events/{$event_year}/{$cat_slug}/";
                $upload_dir = '../' . $upload_rel;
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (in_array($file_ext, $allowed) && $_FILES['image']['size'] <= 10 * 1024 * 1024) {
                    $new_name = uniqid('event_') . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                        $image_path = $upload_rel . $new_name;
                    }
                } else {
                    $error = "Invalid image format or size over 10MB.";
                }
            }

            if (!$error) {
                $end_date_val = !empty($end_date) ? $end_date : null;
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, end_date, location, category, max_participants, image, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $event_date, $end_date_val, $location, $category, $max_participants, $image_path, $image_path, $status]);
                    $success = "Event created successfully!";
                } else {
                    $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, event_date=?, end_date=?, location=?, category=?, max_participants=?, image=?, image_path=?, status=? WHERE id=?");
                    $stmt->execute([$title, $description, $event_date, $end_date_val, $location, $category, $max_participants, $image_path, $image_path, $status, $event_id]);
                    $success = "Event updated successfully!";
                }
            }
        }
    } elseif ($action === 'delete') {
        $event_id = (int)$_POST['event_id'];
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $success = "Event deleted successfully.";
    } elseif ($action === 'toggle_status') {
        $event_id = (int)$_POST['event_id'];
        $new_status = $_POST['new_status'];
        $stmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $event_id]);
        $success = "Event status updated.";
    }
}

// Filters & Search
$filter_status = $_GET['status'] ?? 'all';
$filter_cat    = $_GET['category'] ?? 'all';
$search        = trim($_GET['search'] ?? '');

$where_clauses = ["1=1"];
$params = [];

if ($filter_status !== 'all') {
    $where_clauses[] = "e.status = ?";
    $params[] = $filter_status;
}

if ($filter_cat !== 'all') {
    $where_clauses[] = "e.category = ?";
    $params[] = $filter_cat;
}

if (!empty($search)) {
    $where_clauses[] = "(e.title LIKE ? OR e.location LIKE ? OR e.description LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
}

$where_sql = implode(' AND ', $where_clauses);

// Fetch events with registrations count
$stmt = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) as reg_count 
    FROM events e 
    WHERE $where_sql 
    ORDER BY e.event_date DESC
");
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Overall counters
$total_all       = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$total_upcoming  = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming'")->fetchColumn();
$total_ongoing   = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'ongoing'")->fetchColumn();
$total_completed = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'completed'")->fetchColumn();
$total_regs      = (int)$pdo->query("SELECT COUNT(*) FROM event_registrations")->fetchColumn();

// Distinct categories for filter
$cats_list = $pdo->query("SELECT DISTINCT category FROM events WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Event & Camp Management | NSS Admin';
require_once '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <!-- Top Header -->
        <header class="topbar glass-panel d-flex justify-content-between align-items-center mb-4" style="flex-wrap:wrap; gap:16px;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:45px; height:45px; border-radius:12px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <h2 style="margin:0; font-size:1.5rem; color:var(--primary); font-family:var(--font-heading);">Event & Camp Management</h2>
                    <p style="margin:0; color:var(--text-muted); font-size:0.85rem;">Organize NSS camps, cleanliness drives, rallies, and special events</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="admin-registrations.php" class="btn btn-outline" style="border-radius:10px;">
                    <i class="fas fa-users"></i> View Registrations (<?= $total_regs ?>)
                </a>
                <button class="btn btn-primary" onclick="openEventModal('add')" style="border-radius:10px;">
                    <i class="fas fa-plus-circle"></i> Add New Event / Camp
                </button>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success mb-4"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error mb-4"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Stat Counter Ribbon -->
        <div class="dashboard-grid mb-4" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
            <div class="stat-card glass-panel" style="padding:1.25rem; border-radius:14px; border-left:4px solid var(--primary); display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <span style="font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">All Activities</span>
                    <h3 style="font-size:1.8rem; margin:4px 0 0; color:var(--text-dark);"><?= $total_all ?></h3>
                </div>
                <div style="width:42px; height:42px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>

            <div class="stat-card glass-panel" style="padding:1.25rem; border-radius:14px; border-left:4px solid var(--accent); display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <span style="font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Upcoming Camps</span>
                    <h3 style="font-size:1.8rem; margin:4px 0 0; color:var(--accent);"><?= $total_upcoming ?></h3>
                </div>
                <div style="width:42px; height:42px; border-radius:50%; background:var(--accent-subtle); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                    <i class="fas fa-clock"></i>
                </div>
            </div>

            <div class="stat-card glass-panel" style="padding:1.25rem; border-radius:14px; border-left:4px solid #16a34a; display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <span style="font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Ongoing Now</span>
                    <h3 style="font-size:1.8rem; margin:4px 0 0; color:#16a34a;"><?= $total_ongoing ?></h3>
                </div>
                <div style="width:42px; height:42px; border-radius:50%; background:rgba(22,163,74,0.12); color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                    <i class="fas fa-broadcast-tower"></i>
                </div>
            </div>

            <div class="stat-card glass-panel" style="padding:1.25rem; border-radius:14px; border-left:4px solid #64748b; display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <span style="font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Completed</span>
                    <h3 style="font-size:1.8rem; margin:4px 0 0; color:#64748b;"><?= $total_completed ?></h3>
                </div>
                <div style="width:42px; height:42px; border-radius:50%; background:rgba(100,116,139,0.12); color:#64748b; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Search Controls Bar -->
        <div class="glass-panel mb-4" style="padding:1.2rem; border-radius:14px;">
            <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                <!-- Search Input -->
                <div style="flex:1; min-width:220px; position:relative;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                    <input type="text" name="search" class="form-control" placeholder="Search event title, venue, keywords..." value="<?= htmlspecialchars($search) ?>" style="padding-left:36px; border-radius:10px;">
                </div>

                <!-- Status Filter -->
                <div style="min-width:160px;">
                    <select name="status" class="form-control" onchange="this.form.submit()" style="border-radius:10px;">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="upcoming" <?= $filter_status === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                        <option value="ongoing" <?= $filter_status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                        <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="postponed" <?= $filter_status === 'postponed' ? 'selected' : '' ?>>Postponed</option>
                        <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div style="min-width:160px;">
                    <select name="category" class="form-control" onchange="this.form.submit()" style="border-radius:10px;">
                        <option value="all" <?= $filter_cat === 'all' ? 'selected' : '' ?>>All Categories</option>
                        <?php foreach ($cats_list as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $filter_cat === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="border-radius:10px; padding:0.6rem 1.2rem;">
                    <i class="fas fa-filter"></i> Filter
                </button>

                <?php if ($filter_status !== 'all' || $filter_cat !== 'all' || !empty($search)): ?>
                    <a href="admin-events.php" class="btn btn-outline" style="border-radius:10px; padding:0.6rem 1rem;" title="Clear Filters">
                        <i class="fas fa-times"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Events List Table -->
        <div class="dashboard-card glass-panel span-12" style="border-radius:16px; overflow:hidden; padding:0;">
            <div class="table-responsive">
                <table class="table" style="margin-bottom:0; width:100%; border-collapse:collapse;">
                    <thead style="background:var(--bg); border-bottom:2px solid var(--border);">
                        <tr>
                            <th style="padding:14px 16px; width:70px; text-align:center;">Banner</th>
                            <th style="padding:14px 16px;">Event Details</th>
                            <th style="padding:14px 16px;">Category</th>
                            <th style="padding:14px 16px;">Schedule</th>
                            <th style="padding:14px 16px;">Location</th>
                            <th style="padding:14px 16px; text-align:center;">Registrations</th>
                            <th style="padding:14px 16px; text-align:center;">Status</th>
                            <th style="padding:14px 16px; text-align:right; width:160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev): 
                            $imgSrc = !empty($ev['image']) ? '../' . $ev['image'] : (!empty($ev['image_path']) ? '../' . $ev['image_path'] : '../assets/images/nss-logo.png');
                            $startTs = strtotime($ev['event_date']);
                            $endTs   = !empty($ev['end_date']) ? strtotime($ev['end_date']) : null;
                        ?>
                        <tr style="border-bottom:1px solid var(--border); vertical-align:middle;">
                            <td style="padding:14px 16px; text-align:center;">
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Event" style="width:52px; height:52px; border-radius:10px; object-fit:cover; border:1px solid var(--border); box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                            </td>
                            <td style="padding:14px 16px;">
                                <strong style="font-size:1rem; color:var(--text-dark); display:block; margin-bottom:4px;"><?= htmlspecialchars($ev['title']) ?></strong>
                                <span style="color:var(--text-muted); font-size:0.85rem; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                    <?= htmlspecialchars($ev['description'] ?? 'No description provided.') ?>
                                </span>
                            </td>
                            <td style="padding:14px 16px;">
                                <span class="badge" style="background:var(--primary-subtle); color:var(--primary); border:1px solid var(--border-accent); padding:4px 10px; border-radius:20px; font-weight:600; font-size:0.8rem;">
                                    <?= htmlspecialchars($ev['category']) ?>
                                </span>
                            </td>
                            <td style="padding:14px 16px; font-size:0.88rem;">
                                <div style="color:var(--text-dark); font-weight:600;"><i class="fas fa-calendar-day text-accent"></i> <?= date('d M Y', $startTs) ?></div>
                                <small style="color:var(--text-muted);"><i class="fas fa-clock"></i> <?= date('h:i A', $startTs) ?><?= $endTs ? ' - ' . date('h:i A', $endTs) : '' ?></small>
                            </td>
                            <td style="padding:14px 16px; font-size:0.88rem; color:var(--text-muted);">
                                <i class="fas fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($ev['location']) ?>
                            </td>
                            <td style="padding:14px 16px; text-align:center;">
                                <a href="admin-registrations.php?event_id=<?= $ev['id'] ?>" class="badge" style="background:rgba(230,81,0,0.12); color:var(--accent); border:1px solid var(--accent); padding:6px 12px; border-radius:20px; font-weight:700; text-decoration:none; display:inline-block;" title="View Volunteers Registered">
                                    <i class="fas fa-user-check"></i> <?= $ev['reg_count'] ?> Enrolled
                                </a>
                            </td>
                            <td style="padding:14px 16px; text-align:center;">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                    <select name="new_status" onchange="this.form.submit()" class="form-control" style="padding:0.35rem 0.6rem; font-size:0.82rem; font-weight:700; border-radius:8px; width:125px; margin:0 auto; cursor:pointer;
                                        <?php if ($ev['status'] === 'upcoming'): ?>
                                            background:#e0f2fe; color:#0369a1; border-color:#bae6fd;
                                        <?php elseif ($ev['status'] === 'ongoing'): ?>
                                            background:#dcfce7; color:#15803d; border-color:#bbf7d0;
                                        <?php elseif ($ev['status'] === 'completed'): ?>
                                            background:#f1f5f9; color:#475569; border-color:#cbd5e1;
                                        <?php elseif ($ev['status'] === 'postponed'): ?>
                                            background:#fef3c7; color:#b45309; border-color:#fde68a;
                                        <?php else: ?>
                                            background:#fee2e2; color:#b91c1c; border-color:#fecaca;
                                        <?php endif; ?>">
                                        <option value="upcoming" <?= $ev['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                        <option value="ongoing" <?= $ev['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                        <option value="completed" <?= $ev['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="postponed" <?= $ev['status'] === 'postponed' ? 'selected' : '' ?>>Postponed</option>
                                        <option value="cancelled" <?= $ev['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>
                            <td style="padding:14px 16px; text-align:right;">
                                <div style="display:inline-flex; gap:6px; align-items:center;">
                                    <button type="button" class="btn btn-sm btn-outline" onclick='editEvent(<?= json_encode($ev) ?>)' style="border-radius:8px; padding:6px 12px; font-weight:600;" title="Edit Event Details">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to permanently delete this event?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline text-danger" style="border-radius:8px; padding:6px 10px;" title="Delete Event">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$events): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:3rem 1rem;">
                                <div style="width:70px; height:70px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:inline-flex; align-items:center; justify-content:center; font-size:2rem; margin-bottom:1rem;">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <h4 style="color:var(--text-dark); margin:0 0 6px;">No Events Found</h4>
                                <p style="color:var(--text-muted); margin:0 0 1.25rem; font-size:0.92rem;">There are currently no events matching your criteria.</p>
                                <button type="button" class="btn btn-primary" onclick="openEventModal('add')" style="border-radius:10px;">
                                    <i class="fas fa-plus"></i> Create First Event
                                </button>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal for Create / Edit Event -->
<div id="eventModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.7); backdrop-filter:blur(5px); z-index:2000; align-items:center; justify-content:center; padding:1rem;">
    <div style="background:white; border-radius:18px; width:100%; max-width:680px; padding:2rem; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.3); border:1px solid var(--border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border); padding-bottom:1rem;">
            <h3 id="modalTitle" style="color:var(--primary); margin:0; font-family:var(--font-heading); font-size:1.35rem;"><i class="fas fa-calendar-plus text-accent"></i> Add New Event / Camp</h3>
            <button type="button" onclick="closeEventModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="eventForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="event_id" id="eventId" value="">
            <input type="hidden" name="existing_image" id="existingImage" value="">

            <div class="form-group mb-3">
                <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Event / Camp Title *</label>
                <input type="text" name="title" id="eventTitle" class="form-control" placeholder="e.g. Mega Blood Donation Camp 2026 / 7-Day Special Camp" required style="border-radius:10px;">
            </div>

            <!-- Category with Custom Option -->
            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Category *</label>
                    <select name="category" id="eventCategory" class="form-control" required onchange="toggleCustomCategory()" style="border-radius:10px;">
                        <option value="Health">Health & Blood Donation</option>
                        <option value="Environment">Environment & Tree Plantation</option>
                        <option value="Community">Village Adoption & Service</option>
                        <option value="Camps">7-Day Special Camp</option>
                        <option value="Cultural">Cultural & Awareness</option>
                        <option value="Sports">Sports & Yoga</option>
                        <option value="Education">Education & Literacy</option>
                        <option value="General">General NSS Event</option>
                        <option value="__custom__">✏️ Enter Custom Category...</option>
                    </select>
                    <input type="text" name="custom_category" id="customCategoryInput" class="form-control" placeholder="Type your custom category..." style="display:none; margin-top:8px; border-radius:10px;">
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Status *</label>
                    <select name="status" id="eventStatus" class="form-control" required style="border-radius:10px;">
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="postponed">Postponed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <!-- Start Date & End Date -->
            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);"><i class="fas fa-play-circle text-accent"></i> Start Date & Time *</label>
                    <input type="datetime-local" name="event_date" id="eventDate" class="form-control" required style="border-radius:10px;">
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);"><i class="fas fa-stop-circle text-accent"></i> End Date & Time</label>
                    <input type="datetime-local" name="end_date" id="eventEndDate" class="form-control" style="border-radius:10px;">
                    <small class="text-muted">Optional — leave blank for single-day events</small>
                </div>
            </div>

            <div class="form-row mb-3" style="display:grid; grid-template-columns:2fr 1fr; gap:15px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Location / Venue *</label>
                    <input type="text" name="location" id="eventLocation" class="form-control" placeholder="e.g. College Premises / Adopted Village" required style="border-radius:10px;">
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Max Volunteers</label>
                    <input type="number" name="max_participants" id="eventMaxParticipants" class="form-control" placeholder="0 = unlimited" value="0" min="0" style="border-radius:10px;">
                </div>
            </div>

            <div class="form-group mb-3">
                <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Event Description</label>
                <textarea name="description" id="eventDescription" class="form-control" rows="3" placeholder="Detailed description of the NSS activity, schedule, and volunteer requirements..." style="border-radius:10px;"></textarea>
            </div>

            <!-- Image Upload with Live Preview -->
            <div class="form-group mb-4">
                <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Event Banner / Photo</label>
                <div id="imagePreviewContainer" style="display:none; margin-bottom:12px; text-align:center;">
                    <img id="imagePreview" src="" alt="Preview" style="max-width:100%; max-height:200px; border-radius:12px; border:2px solid var(--border); object-fit:cover;">
                    <button type="button" onclick="clearImagePreview()" style="display:block; margin:8px auto 0; background:none; border:none; color:#ef4444; cursor:pointer; font-size:0.85rem; font-weight:600;">
                        <i class="fas fa-times"></i> Remove Image
                    </button>
                </div>
                <label id="imageDropZone" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:1.5rem; border:2px dashed var(--border); border-radius:12px; cursor:pointer; transition:all 0.3s; background:var(--bg); gap:8px;">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem; color:var(--text-muted);"></i>
                    <span style="font-weight:600; color:var(--text-dark);">Click to upload or drag & drop</span>
                    <small style="color:var(--text-muted);">JPG, PNG, WEBP, GIF (Max 10MB)</small>
                    <input type="file" name="image" id="eventImageInput" accept="image/*" style="display:none;" onchange="previewImage(this)">
                </label>
            </div>

            <div class="d-flex justify-content-end gap-2" style="border-top:1px solid var(--border); padding-top:1rem;">
                <button type="button" class="btn btn-outline" onclick="closeEventModal()" style="border-radius:10px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="border-radius:10px;"><i class="fas fa-save"></i> Save Event</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCustomCategory() {
    const sel = document.getElementById('eventCategory');
    const customInput = document.getElementById('customCategoryInput');
    if (sel.value === '__custom__') {
        customInput.style.display = 'block';
        customInput.required = true;
        customInput.focus();
    } else {
        customInput.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
}

function previewImage(input) {
    const container = document.getElementById('imagePreviewContainer');
    const preview = document.getElementById('imagePreview');
    const dropZone = document.getElementById('imageDropZone');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
            dropZone.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
}

function clearImagePreview() {
    document.getElementById('imagePreviewContainer').style.display = 'none';
    document.getElementById('imageDropZone').style.display = 'flex';
    document.getElementById('eventImageInput').value = '';
    document.getElementById('existingImage').value = '';
}

// Make the drop zone clickable & support Drag and Drop
const eventDropZone = document.getElementById('imageDropZone');
const eventFileInput = document.getElementById('eventImageInput');

if (eventDropZone) {
    eventDropZone.addEventListener('click', function() {
        eventFileInput.click();
    });

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        eventDropZone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        eventDropZone.addEventListener(eventName, function() {
            eventDropZone.style.borderColor = '#f4a11d';
            eventDropZone.style.background = '#fffbeb';
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        eventDropZone.addEventListener(eventName, function() {
            eventDropZone.style.borderColor = '#cbd5e1';
            eventDropZone.style.background = '#f8fafc';
        }, false);
    });

    eventDropZone.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length) {
            eventFileInput.files = dt.files;
            previewImage(eventFileInput);
        }
    }, false);
}

function openEventModal(mode) {
    document.getElementById('formAction').value = mode;
    document.getElementById('modalTitle').innerHTML = mode === 'add' ? '<i class="fas fa-calendar-plus text-accent"></i> Add New Event' : '<i class="fas fa-edit text-accent"></i> Edit Event';
    if (mode === 'add') {
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = '';
        document.getElementById('existingImage').value = '';
        clearImagePreview();
        toggleCustomCategory();
    }
    document.getElementById('eventModal').style.display = 'flex';

    if (window.anime) {
        anime({
            targets: '#eventModal > div',
            scale: [0.9, 1],
            opacity: [0, 1],
            duration: 300,
            easing: 'easeOutCubic'
        });
    }
}

function closeEventModal() {
    document.getElementById('eventModal').style.display = 'none';
}

function editEvent(ev) {
    openEventModal('edit');
    document.getElementById('eventId').value = ev.id;
    document.getElementById('eventTitle').value = ev.title;
    document.getElementById('eventStatus').value = ev.status;
    document.getElementById('eventLocation').value = ev.location;
    document.getElementById('eventDescription').value = ev.description || '';
    document.getElementById('existingImage').value = ev.image || ev.image_path || '';
    document.getElementById('eventMaxParticipants').value = ev.max_participants || 0;

    // Handle category - check if it's a preset or custom
    const catSelect = document.getElementById('eventCategory');
    const options = Array.from(catSelect.options).map(o => o.value);
    if (options.includes(ev.category)) {
        catSelect.value = ev.category;
        document.getElementById('customCategoryInput').style.display = 'none';
    } else {
        catSelect.value = '__custom__';
        document.getElementById('customCategoryInput').style.display = 'block';
        document.getElementById('customCategoryInput').value = ev.category;
    }

    // Format start date
    if (ev.event_date) {
        let d = new Date(ev.event_date);
        document.getElementById('eventDate').value = formatDateTimeLocal(d);
    }

    // Format end date
    if (ev.end_date) {
        let d = new Date(ev.end_date);
        document.getElementById('eventEndDate').value = formatDateTimeLocal(d);
    } else {
        document.getElementById('eventEndDate').value = '';
    }

    // Show existing image preview
    if (ev.image || ev.image_path) {
        const imgPath = ev.image || ev.image_path;
        document.getElementById('imagePreview').src = '../' + imgPath;
        document.getElementById('imagePreviewContainer').style.display = 'block';
        document.getElementById('imageDropZone').style.display = 'none';
    }
}

function formatDateTimeLocal(d) {
    return d.getFullYear() + '-' +
        String(d.getMonth()+1).padStart(2,'0') + '-' +
        String(d.getDate()).padStart(2,'0') + 'T' +
        String(d.getHours()).padStart(2,'0') + ':' +
        String(d.getMinutes()).padStart(2,'0');
}
</script>

<?php require_once '../includes/footer.php'; ?>
