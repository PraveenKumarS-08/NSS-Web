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

// Fetch events
$stmt = $pdo->query("SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) as reg_count FROM events e ORDER BY e.event_date DESC");
$events = $stmt->fetchAll();

$pageTitle = 'Manage Events | NSS Admin';
require_once '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <h2><i class="fas fa-calendar-alt text-primary"></i> Event & Camp Management</h2>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge status-approved" style="font-size:0.85rem; padding: 6px 14px;"><i class="fas fa-user-shield"></i> Logged in as: <strong><?= htmlspecialchars($_SESSION['name'] ?? 'NSS Admin') ?></strong></span>
                <button class="btn btn-primary" onclick="openEventModal('add')"><i class="fas fa-plus"></i> Add New Event</button>
            </div>
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
                            <th>Start Date</th>
                            <th>End Date</th>
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
                            <td><?= !empty($ev['end_date']) ? date('d M Y, h:i A', strtotime($ev['end_date'])) : '<span class="text-muted">—</span>' ?></td>
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
                        <tr><td colspan="9" class="text-center py-4">No events found. Click "Add New Event" to create one.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal for Create / Edit Event -->
<div id="eventModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.7); backdrop-filter:blur(5px); z-index:2000; align-items:center; justify-content:center; padding:1rem;">
    <div style="background:white; border-radius:16px; width:100%; max-width:650px; padding:2rem; max-height:90vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 id="modalTitle" style="color:#1b365d; margin:0;"><i class="fas fa-calendar-plus text-accent"></i> Add New Event</h3>
            <button type="button" onclick="closeEventModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#64748b;">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="eventForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="event_id" id="eventId" value="">
            <input type="hidden" name="existing_image" id="existingImage" value="">

            <div class="form-group mb-3">
                <label style="font-weight:600; font-size:0.85rem;">Event Title *</label>
                <input type="text" name="title" id="eventTitle" class="form-control" placeholder="e.g. Mega Blood Donation Camp 2026" required>
            </div>

            <!-- Category with Custom Option -->
            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Category *</label>
                    <select name="category" id="eventCategory" class="form-control" required onchange="toggleCustomCategory()">
                        <option value="Health">Health & Blood Donation</option>
                        <option value="Environment">Environment & Plantation</option>
                        <option value="Community">Village Adoption & Service</option>
                        <option value="Cultural">Cultural & Awareness</option>
                        <option value="Sports">Sports & Fitness</option>
                        <option value="Education">Education & Literacy</option>
                        <option value="General">General NSS Event</option>
                        <option value="__custom__">✏️ Enter Custom Category...</option>
                    </select>
                    <input type="text" name="custom_category" id="customCategoryInput" class="form-control" placeholder="Type your custom category..." style="display:none; margin-top:8px;">
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Status *</label>
                    <select name="status" id="eventStatus" class="form-control" required>
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
                    <label style="font-weight:600; font-size:0.85rem;"><i class="fas fa-play-circle text-accent"></i> Start Date & Time *</label>
                    <input type="datetime-local" name="event_date" id="eventDate" class="form-control" required>
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;"><i class="fas fa-stop-circle text-accent"></i> End Date & Time</label>
                    <input type="datetime-local" name="end_date" id="eventEndDate" class="form-control">
                    <small class="text-muted">Optional — leave blank for single-day events</small>
                </div>
            </div>

            <div class="form-row mb-3" style="display:grid; grid-template-columns:2fr 1fr; gap:15px;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Location *</label>
                    <input type="text" name="location" id="eventLocation" class="form-control" placeholder="e.g. College Premises, Madurai-11" required>
                </div>
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem;">Max Participants</label>
                    <input type="number" name="max_participants" id="eventMaxParticipants" class="form-control" placeholder="0 = unlimited" value="0" min="0">
                </div>
            </div>

            <div class="form-group mb-3">
                <label style="font-weight:600; font-size:0.85rem;">Event Description</label>
                <textarea name="description" id="eventDescription" class="form-control" rows="3" placeholder="Detailed description of the NSS activity..."></textarea>
            </div>

            <!-- Image Upload with Live Preview -->
            <div class="form-group mb-4">
                <label style="font-weight:600; font-size:0.85rem;">Event Banner / Photo</label>
                <div id="imagePreviewContainer" style="display:none; margin-bottom:12px; text-align:center;">
                    <img id="imagePreview" src="" alt="Preview" style="max-width:100%; max-height:200px; border-radius:12px; border:2px solid #e2e8f0; object-fit:cover;">
                    <button type="button" onclick="clearImagePreview()" style="display:block; margin:8px auto 0; background:none; border:none; color:#ef4444; cursor:pointer; font-size:0.85rem; font-weight:600;">
                        <i class="fas fa-times"></i> Remove Image
                    </button>
                </div>
                <label id="imageDropZone" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:1.5rem; border:2px dashed #cbd5e1; border-radius:12px; cursor:pointer; transition:all 0.3s; background:#f8fafc; gap:8px;"
                    onmouseover="this.style.borderColor='#f4a11d'; this.style.background='#fffbeb';"
                    onmouseout="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem; color:#94a3b8;"></i>
                    <span style="font-weight:600; color:#475569;">Click to upload or drag & drop</span>
                    <small style="color:#94a3b8;">JPG, PNG, WEBP, GIF (Max 5MB)</small>
                    <input type="file" name="image" id="eventImageInput" accept="image/*" style="display:none;" onchange="previewImage(this)">
                </label>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline" onclick="closeEventModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Event</button>
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
