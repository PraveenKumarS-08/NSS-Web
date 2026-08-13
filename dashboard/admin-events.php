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
        $location = trim($_POST['location'] ?? '');
        $category = $_POST['category'] ?? 'General';
        $max_participants = (int)($_POST['max_participants'] ?? 0);
        $status = $_POST['status'] ?? 'upcoming';

        if (empty($title) || empty($event_date) || empty($location)) {
            $error = "Title, Event Date, and Location are required.";
        } else {
            // Handle Image Upload
            $image_path = $_POST['existing_image'] ?? null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/events/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (in_array($file_ext, $allowed) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
                    $new_name = uniqid('event_') . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                        $image_path = 'uploads/events/' . $new_name;
                    }
                } else {
                    $error = "Invalid image format or size over 5MB.";
                }
            }

            if (!$error) {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, location, category, max_participants, image, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $event_date, $location, $category, $max_participants, $image_path, $image_path, $status]);
                    $success = "Event created successfully!";
                } else {
                    $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, event_date=?, location=?, category=?, max_participants=?, image=?, image_path=?, status=? WHERE id=?");
                    $stmt->execute([$title, $description, $event_date, $location, $category, $max_participants, $image_path, $image_path, $status, $event_id]);
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

// Fetch events
$stmt = $pdo->query("SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) as reg_count FROM events e ORDER BY e.event_date DESC");
$events = $stmt->fetchAll();

$pageTitle = 'Manage Events | NSS Admin';
require_once '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <div class="d-flex align-items-center gap-3">
                <h2><i class="fas fa-calendar-alt text-primary"></i> Event & Camp Management</h2>
            </div>
            <button class="btn btn-primary" onclick="openEventModal('add')"><i class="fas fa-plus"></i> Add New Event</button>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Events List Table -->
        <div class="dashboard-card glass-panel span-12">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Event Details</th>
                            <th>Category</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Registrations</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev): 
                            $imgSrc = !empty($ev['image']) ? '../' . $ev['image'] : (!empty($ev['image_path']) ? '../' . $ev['image_path'] : '../assets/images/nss-logo.png');
                        ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Event" style="width: 55px; height: 55px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0;">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($ev['title']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars(substr($ev['description'] ?? '', 0, 70)) ?>...</small>
                            </td>
                            <td><span class="badge bg-primary text-white"><?= htmlspecialchars($ev['category']) ?></span></td>
                            <td><?= date('d M Y, h:i A', strtotime($ev['event_date'])) ?></td>
                            <td><?= htmlspecialchars($ev['location']) ?></td>
                            <td><span class="badge bg-secondary"><?= $ev['reg_count'] ?> registered</span></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                    <select name="new_status" onchange="this.form.submit()" class="form-control" style="padding:0.25rem 0.5rem; font-size:0.85rem; width:120px;">
                                        <option value="upcoming" <?= $ev['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                        <option value="ongoing" <?= $ev['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                        <option value="completed" <?= $ev['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="postponed" <?= $ev['status'] === 'postponed' ? 'selected' : '' ?>>Postponed</option>
                                        <option value="cancelled" <?= $ev['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline" onclick='editEvent(<?= json_encode($ev) ?>)'>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event permanently?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline text-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$events): ?>
                        <tr><td colspan="8" class="text-center py-4">No events found. Click "Add New Event" to create one.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal for Create / Edit Event -->
<div id="eventModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.7); backdrop-filter:blur(5px); z-index:2000; align-items:center; justify-content:center; padding:1rem;">
    <div style="background:white; border-radius:16px; width:100%; max-width:600px; padding:2rem; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 id="modalTitle" style="color:#1b365d; margin:0;"><i class="fas fa-calendar-plus text-accent"></i> Add New Event</h3>
            <button type="button" onclick="closeEventModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#64748b;">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="eventForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="event_id" id="eventId" value="">
            <input type="hidden" name="existing_image" id="existingImage" value="">

            <div class="form-group mb-3">
                <label>Event Title *</label>
                <input type="text" name="title" id="eventTitle" class="form-control" placeholder="e.g. Mega Blood Donation Camp 2026" required>
            </div>

            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" id="eventCategory" class="form-control" required>
                        <option value="Health">Health & Blood Donation</option>
                        <option value="Environment">Environment & Plantation</option>
                        <option value="Community">Village Adoption & Service</option>
                        <option value="Cultural">Cultural & Awareness</option>
                        <option value="General">General NSS Event</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="eventStatus" class="form-control" required>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="postponed">Postponed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label>Event Date & Time *</label>
                    <input type="datetime-local" name="event_date" id="eventDate" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Location *</label>
                    <input type="text" name="location" id="eventLocation" class="form-control" placeholder="e.g. College Premises, Madurai-11" required>
                </div>
            </div>

            <div class="form-group mb-3">
                <label>Event Description</label>
                <textarea name="description" id="eventDescription" class="form-control" rows="4" placeholder="Detailed description of the NSS activity..."></textarea>
            </div>

            <div class="form-group mb-4">
                <label>Event Banner / Photo</label>
                <input type="file" name="image" accept="image/*" class="form-control">
                <small class="text-muted">Allowed: JPG, PNG, WEBP (Max 5MB)</small>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline" onclick="closeEventModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Event</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEventModal(mode) {
    document.getElementById('formAction').value = mode;
    document.getElementById('modalTitle').innerHTML = mode === 'add' ? '<i class="fas fa-calendar-plus text-accent"></i> Add New Event' : '<i class="fas fa-edit text-accent"></i> Edit Event';
    if (mode === 'add') {
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = '';
        document.getElementById('existingImage').value = '';
    }
    document.getElementById('eventModal').style.display = 'flex';
}

function closeEventModal() {
    document.getElementById('eventModal').style.display = 'none';
}

function editEvent(ev) {
    openEventModal('edit');
    document.getElementById('eventId').value = ev.id;
    document.getElementById('eventTitle').value = ev.title;
    document.getElementById('eventCategory').value = ev.category;
    document.getElementById('eventStatus').value = ev.status;
    document.getElementById('eventLocation').value = ev.location;
    document.getElementById('eventDescription').value = ev.description || '';
    document.getElementById('existingImage').value = ev.image || ev.image_path || '';
    
    // Format datetime-local string (YYYY-MM-DDTHH:MM)
    if (ev.event_date) {
        let d = new Date(ev.event_date);
        let iso = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + 'T' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
        document.getElementById('eventDate').value = iso;
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
