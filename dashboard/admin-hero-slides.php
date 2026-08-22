<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$error = '';
$success = '';
66
// Handle Create / Edit / Delete / Toggle Slide
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $slide_id   = (int)($_POST['slide_id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $caption    = trim($_POST['caption'] ?? '');
        $order_num  = (int)($_POST['order_num'] ?? 0);
        $is_active  = isset($_POST['is_active']) ? 1 : 0;
        $image_path = $_POST['existing_image'] ?? '';

        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/hero/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed) && $_FILES['image']['size'] <= 10 * 1024 * 1024) {
                $new_name = 'hero_' . uniqid() . '.' . $file_ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                    $image_path = 'uploads/hero/' . $new_name;
                }
            } else {
                $error = "Invalid image format (JPG, PNG, WEBP allowed) or file size exceeds 10MB.";
            }
        } elseif (!empty($_POST['image_url']) && empty($image_path)) {
            // Support direct image URL input
            $image_path = trim($_POST['image_url']);
        }

        if (empty($image_path) && !$error) {
            $error = "Please upload an image or provide a valid image URL for the slide.";
        }

        if (!$error) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO hero_slides (image_path, title, caption, order_num, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$image_path, $title, $caption, $order_num, $is_active]);
                $success = "New hero slide added successfully!";
            } else {
                $stmt = $pdo->prepare("UPDATE hero_slides SET image_path = ?, title = ?, caption = ?, order_num = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$image_path, $title, $caption, $order_num, $is_active, $slide_id]);
                $success = "Hero slide updated successfully!";
            }
        }
    } elseif ($action === 'delete') {
        $slide_id = (int)$_POST['slide_id'];
        $stmt = $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
        $stmt->execute([$slide_id]);
        $success = "Hero slide removed successfully.";
    } elseif ($action === 'toggle_active') {
        $slide_id = (int)$_POST['slide_id'];
        $new_val  = (int)$_POST['new_status'];
        $stmt = $pdo->prepare("UPDATE hero_slides SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_val, $slide_id]);
        $success = "Slide visibility updated.";
    } elseif ($action === 'reorder') {
        // AJAX drag-and-drop reorder
        $input = json_decode(file_get_contents('php://input'), true);
        if (!empty($input['order']) && is_array($input['order'])) {
            $stmt = $pdo->prepare("UPDATE hero_slides SET order_num = ? WHERE id = ?");
            foreach ($input['order'] as $index => $id) {
                $stmt->execute([$index, (int)$id]);
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

// Handle AJAX reorder (GET-style fallback via custom header)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REORDER_REQUEST'])) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!empty($input['order']) && is_array($input['order'])) {
        $stmt = $pdo->prepare("UPDATE hero_slides SET order_num = ? WHERE id = ?");
        foreach ($input['order'] as $index => $id) {
            $stmt->execute([$index, (int)$id]);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// Fetch all slides
$stmt = $pdo->query("SELECT * FROM hero_slides ORDER BY order_num ASC, created_at DESC");
$slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts
$total_slides  = count($slides);
$active_slides = count(array_filter($slides, fn($s) => $s['is_active'] == 1));

$pageTitle = 'Manage Homepage Hero Slides | NSS Admin';
require_once '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <!-- Top Header -->
        <header class="topbar glass-panel d-flex justify-content-between align-items-center mb-4" style="flex-wrap:wrap; gap:16px;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:45px; height:45px; border-radius:12px; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <div>
                    <h2 style="margin:0; font-size:1.5rem; color:var(--primary); font-family:var(--font-heading);">Homepage Hero Slider</h2>
                    <p style="margin:0; color:var(--text-muted); font-size:0.85rem;">Upload and configure fullscreen carousel slides on the public landing page</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="../index.php" target="_blank" class="btn btn-outline" style="border-radius:10px;">
                    <i class="fas fa-external-link-alt"></i> Preview Live Homepage
                </a>
                <button class="btn btn-primary" onclick="openSlideModal('add')" style="border-radius:10px;">
                    <i class="fas fa-plus-circle"></i> Add New Slide
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
                    <span style="font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Total Carousel Slides</span>
                    <h3 style="font-size:1.8rem; margin:4px 0 0; color:var(--text-dark);"><?= $total_slides ?></h3>
                </div>
                <div style="width:42px; height:42px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                    <i class="fas fa-images"></i>
                </div>
            </div>

            <div class="stat-card glass-panel" style="padding:1.25rem; border-radius:14px; border-left:4px solid #16a34a; display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <span style="font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Active on Homepage</span>
                    <h3 style="font-size:1.8rem; margin:4px 0 0; color:#16a34a;"><?= $active_slides ?></h3>
                </div>
                <div style="width:42px; height:42px; border-radius:50%; background:rgba(22,163,74,0.12); color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                    <i class="fas fa-eye"></i>
                </div>
            </div>

            <div class="stat-card glass-panel" style="padding:1.25rem; border-radius:14px; border-left:4px solid #64748b; display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <span style="font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Hidden / Inactive</span>
                    <h3 style="font-size:1.8rem; margin:4px 0 0; color:#64748b;"><?= $total_slides - $active_slides ?></h3>
                </div>
                <div style="width:42px; height:42px; border-radius:50%; background:rgba(100,116,139,0.12); color:#64748b; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                    <i class="fas fa-eye-slash"></i>
                </div>
            </div>
        </div>

        <!-- Reorder Toast Notification -->
        <div id="reorderToast" style="display:none; position:fixed; bottom:30px; left:50%; transform:translateX(-50%); background:var(--primary); color:#fff; padding:12px 28px; border-radius:12px; font-size:0.9rem; font-weight:600; box-shadow:0 8px 30px rgba(92,6,26,0.3); z-index:3000; transition:all 0.3s ease;">
            <i class="fas fa-check-circle"></i> Slide order saved!
        </div>

        <!-- Slides List Grid -->
        <div class="dashboard-card glass-panel span-12" style="border-radius:16px; padding:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:10px;">
                <h3 style="margin:0; font-size:1.15rem; color:var(--primary);"><i class="fas fa-layer-group text-accent"></i> Configured Slides</h3>
                <small style="color:var(--text-muted);">Slides are displayed in order on the homepage auto-scroll carousel.</small>
            </div>

            <?php if (!empty($slides)): ?>
                <!-- Drag & Drop Info Bar -->
                <div style="background:linear-gradient(135deg, var(--primary-subtle), rgba(230,81,0,0.08)); border:1px solid rgba(92,6,26,0.15); border-radius:10px; padding:10px 16px; margin-bottom:1.25rem; display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; border-radius:8px; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.85rem; flex-shrink:0;">
                        <i class="fas fa-arrows-alt"></i>
                    </div>
                    <span style="font-size:0.85rem; color:var(--text-dark);">
                        <strong>Drag & Drop Enabled</strong> — Grab the <i class="fas fa-grip-vertical"></i> handle on each slide card to reorder. Changes are saved automatically.
                    </span>
                </div>

                <div id="slideSortableGrid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:20px;">
                    <?php foreach ($slides as $i => $s): 
                        $imgPath = strpos($s['image_path'], 'http') === 0 ? $s['image_path'] : '../' . $s['image_path'];
                    ?>
                    <div class="slide-card" data-slide-id="<?= $s['id'] ?>" style="background:#fff; border:1px solid var(--border); border-radius:14px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 4px 15px rgba(0,0,0,0.04); position:relative; transition:box-shadow 0.25s, transform 0.25s;">
                        <!-- Drag Handle -->
                        <div class="drag-handle" style="position:absolute; top:0; left:0; right:0; height:36px; background:linear-gradient(180deg, rgba(15,23,42,0.65) 0%, transparent 100%); display:flex; align-items:center; justify-content:center; cursor:grab; z-index:10; opacity:0.7; transition:opacity 0.2s;" onmouseenter="this.style.opacity='1'" onmouseleave="this.style.opacity='0.7'">
                            <i class="fas fa-grip-horizontal" style="color:#fff; font-size:1.1rem; text-shadow:0 1px 3px rgba(0,0,0,0.5);"></i>
                        </div>

                        <!-- Slide Image Preview -->
                        <div style="position:relative; width:100%; height:180px; background:#0f172a;">
                            <img src="<?= htmlspecialchars($imgPath) ?>" alt="Slide" style="width:100%; height:100%; object-fit:cover;">
                            <div class="order-badge" style="position:absolute; top:10px; left:10px; background:rgba(15,23,42,0.75); backdrop-filter:blur(4px); color:#fff; font-size:0.75rem; font-weight:700; padding:4px 10px; border-radius:20px;">
                                Order #<span class="order-num"><?= $s['order_num'] ?></span>
                            </div>
                            <div style="position:absolute; top:10px; right:10px;">
                                <?php if ($s['is_active']): ?>
                                    <span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; font-weight:700; font-size:0.75rem;">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; font-weight:700; font-size:0.75rem;">
                                        <i class="fas fa-eye-slash"></i> Hidden
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Slide Content & Metadata -->
                        <div style="padding:1.25rem; flex:1; display:flex; flex-direction:column; justify-content:space-between;">
                            <div>
                                <h4 style="margin:0 0 6px; font-size:1.05rem; color:var(--text-dark);">
                                    <?= !empty($s['title']) ? htmlspecialchars($s['title']) : '<em class="text-muted">Default Title</em>' ?>
                                </h4>
                                <p style="margin:0; font-size:0.85rem; color:var(--text-muted); line-height:1.4;">
                                    <?= !empty($s['caption']) ? htmlspecialchars($s['caption']) : 'Default Tamil motto & descriptions' ?>
                                </p>
                            </div>

                            <div style="border-top:1px solid var(--border); margin-top:1rem; padding-top:0.85rem; display:flex; justify-content:space-between; align-items:center;">
                                <!-- Toggle Active Switch -->
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="slide_id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $s['is_active'] ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-sm <?= $s['is_active'] ? 'btn-outline' : 'btn-primary' ?>" style="font-size:0.78rem; padding:4px 10px; border-radius:6px;" title="Toggle visibility">
                                        <?= $s['is_active'] ? '<i class="fas fa-eye-slash"></i> Hide' : '<i class="fas fa-eye"></i> Show' ?>
                                    </button>
                                </form>

                                <div style="display:flex; gap:6px;">
                                    <button type="button" class="btn btn-sm btn-outline" onclick='editSlide(<?= json_encode($s) ?>)' style="border-radius:6px; padding:4px 10px; font-weight:600;">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this hero slide permanently?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="slide_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline text-danger" style="border-radius:6px; padding:4px 8px;" title="Delete Slide">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:3.5rem 1rem;">
                    <div style="width:75px; height:75px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:inline-flex; align-items:center; justify-content:center; font-size:2rem; margin-bottom:1rem;">
                        <i class="fas fa-images"></i>
                    </div>
                    <h3 style="color:var(--text-dark); margin:0 0 6px;">No Custom Hero Slides Added</h3>
                    <p style="color:var(--text-muted); margin:0 0 1.25rem; font-size:0.92rem;">Upload high-definition photos of your college, camps, or parade drills to feature on the homepage.</p>
                    <button type="button" class="btn btn-primary" onclick="openSlideModal('add')" style="border-radius:10px;">
                        <i class="fas fa-plus"></i> Add First Slide
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal for Create / Edit Slide -->
<div id="slideModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.7); backdrop-filter:blur(5px); z-index:2000; align-items:center; justify-content:center; padding:1rem;">
    <div style="background:white; border-radius:18px; width:100%; max-width:620px; padding:2rem; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.3); border:1px solid var(--border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--border); padding-bottom:1rem;">
            <h3 id="modalTitle" style="color:var(--primary); margin:0; font-family:var(--font-heading); font-size:1.35rem;"><i class="fas fa-sliders-h text-accent"></i> Add New Hero Slide</h3>
            <button type="button" onclick="closeSlideModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="slideForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="slide_id" id="slideId" value="">
            <input type="hidden" name="existing_image" id="existingImage" value="">

            <div class="form-group mb-3">
                <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Slide Headline / Title (Optional)</label>
                <input type="text" name="title" id="slideTitle" class="form-control" placeholder="e.g. Tree Plantation & Environmental Protection" style="border-radius:10px;">
                <small class="text-muted">Leave blank to use default "Not Me, But You"</small>
            </div>

            <div class="form-group mb-3">
                <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Slide Subtitle / Caption (Optional)</label>
                <input type="text" name="caption" id="slideCaption" class="form-control" placeholder="e.g. Building youth leadership and social responsibility" style="border-radius:10px;">
            </div>

            <div class="form-row mb-3" style="display:grid; grid-template-columns:1fr 1fr; gap:15px; align-items:center;">
                <div class="form-group">
                    <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Display Order Number</label>
                    <input type="number" name="order_num" id="slideOrder" class="form-control" value="0" min="0" style="border-radius:10px;">
                    <small class="text-muted">Lower numbers show first (0, 1, 2...)</small>
                </div>
                <div class="form-group" style="padding-top:1.2rem;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:0.9rem; color:var(--text-dark);">
                        <input type="checkbox" name="is_active" id="slideActive" value="1" checked style="width:18px; height:18px; accent-color:var(--primary);">
                        Active on Homepage
                    </label>
                </div>
            </div>

            <!-- Slide Image Upload -->
            <div class="form-group mb-4">
                <label style="font-weight:600; font-size:0.85rem; color:var(--text-dark);">Slide Background Image * (16:9 recommended, min 1600x900)</label>
                
                <div id="imagePreviewContainer" style="display:none; margin-bottom:12px; text-align:center;">
                    <img id="imagePreview" src="" alt="Preview" style="max-width:100%; max-height:220px; border-radius:12px; border:2px solid var(--border); object-fit:cover;">
                    <button type="button" onclick="clearImagePreview()" style="display:block; margin:8px auto 0; background:none; border:none; color:#ef4444; cursor:pointer; font-size:0.85rem; font-weight:600;">
                        <i class="fas fa-times"></i> Change / Remove Image
                    </button>
                </div>

                <label id="imageDropZone" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:1.5rem; border:2px dashed var(--border); border-radius:12px; cursor:pointer; transition:all 0.3s; background:var(--bg); gap:8px;">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem; color:var(--text-muted);"></i>
                    <span style="font-weight:600; color:var(--text-dark);">Click to upload high-res photo or drag & drop</span>
                    <small style="color:var(--text-muted);">JPG, PNG, WEBP (Max 10MB)</small>
                    <input type="file" name="image" id="slideImageInput" accept="image/*" style="display:none;" onchange="previewSlideImage(this)">
                </label>

                <!-- Or image URL -->
                <div style="margin-top:10px;">
                    <span style="font-size:0.8rem; color:var(--text-muted);">Or paste direct Image URL:</span>
                    <input type="url" name="image_url" id="slideImageUrl" class="form-control" placeholder="https://..." style="border-radius:10px; margin-top:4px; font-size:0.85rem;" oninput="previewUrlImage(this.value)">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2" style="border-top:1px solid var(--border); padding-top:1rem;">
                <button type="button" class="btn btn-outline" onclick="closeSlideModal()" style="border-radius:10px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="border-radius:10px;"><i class="fas fa-save"></i> Save Slide</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewSlideImage(input) {
    const container = document.getElementById('imagePreviewContainer');
    const preview = document.getElementById('imagePreview');
    const dropZone = document.getElementById('imageDropZone');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 10 * 1024 * 1024) {
            alert('File size exceeds 10MB limit.');
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

function previewUrlImage(url) {
    if (url && url.startsWith('http')) {
        const container = document.getElementById('imagePreviewContainer');
        const preview = document.getElementById('imagePreview');
        const dropZone = document.getElementById('imageDropZone');
        preview.src = url;
        container.style.display = 'block';
        dropZone.style.display = 'none';
    }
}

function clearImagePreview() {
    document.getElementById('imagePreviewContainer').style.display = 'none';
    document.getElementById('imageDropZone').style.display = 'flex';
    document.getElementById('slideImageInput').value = '';
    document.getElementById('slideImageUrl').value = '';
    document.getElementById('existingImage').value = '';
}

function openSlideModal(mode) {
    document.getElementById('formAction').value = mode;
    document.getElementById('modalTitle').innerHTML = mode === 'add' ? '<i class="fas fa-plus-circle text-accent"></i> Add New Hero Slide' : '<i class="fas fa-edit text-accent"></i> Edit Hero Slide';
    if (mode === 'add') {
        document.getElementById('slideForm').reset();
        document.getElementById('slideId').value = '';
        document.getElementById('existingImage').value = '';
        document.getElementById('slideActive').checked = true;
        clearImagePreview();
    }
    document.getElementById('slideModal').style.display = 'flex';
}

function closeSlideModal() {
    document.getElementById('slideModal').style.display = 'none';
}

function editSlide(s) {
    openSlideModal('edit');
    document.getElementById('slideId').value = s.id;
    document.getElementById('slideTitle').value = s.title || '';
    document.getElementById('slideCaption').value = s.caption || '';
    document.getElementById('slideOrder').value = s.order_num || 0;
    document.getElementById('slideActive').checked = s.is_active == 1;
    document.getElementById('existingImage').value = s.image_path || '';

    if (s.image_path) {
        const imgPath = s.image_path.startsWith('http') ? s.image_path : '../' + s.image_path;
        document.getElementById('imagePreview').src = imgPath;
        document.getElementById('imagePreviewContainer').style.display = 'block';
        document.getElementById('imageDropZone').style.display = 'none';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
