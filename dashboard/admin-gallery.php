<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$error = '';
$success = '';

// Handle Multi-Image Upload / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $base_title = trim($_POST['title'] ?? '');
        $category   = trim($_POST['category'] ?? 'General');
        if ($category === '__custom__') {
            $category = trim($_POST['custom_category'] ?? 'General');
            if (empty($category)) $category = 'General';
        }
        $year       = (int)($_POST['year'] ?? date('Y'));

        if ($year < 2000 || $year > 2099) $year = (int)date('Y');

        // Prepare Category folder slug
        $cat_slug = preg_replace('/[^a-zA-Z0-9_\-]/', '_', str_replace(' ', '_', $category));

        // Create structured directory: uploads/gallery/{YEAR}/{CATEGORY}/
        $rel_dir    = "uploads/gallery/{$year}/{$cat_slug}/";
        $target_dir = "../" . $rel_dir;

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $uploaded_count = 0;
        $file_errors = [];

        // Check if multiple images uploaded
        $files = $_FILES['images'] ?? null;
        if ($files && is_array($files['name'])) {
            $total_files = count($files['name']);

            for ($i = 0; $i < $total_files; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $files['tmp_name'][$i];
                    $orig_name = $files['name'][$i];
                    $file_size = $files['size'][$i];
                    $file_ext  = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (in_array($file_ext, $allowed)) {
                        if ($file_size <= 10 * 1024 * 1024) { // 10MB limit per image
                            $new_filename = uniqid('gal_') . '.' . $file_ext;
                            $dest_path    = $target_dir . $new_filename;
                            $db_path      = $rel_dir . $new_filename;

                            if (move_uploaded_file($tmp_name, $dest_path)) {
                                $img_title = !empty($base_title) ? ($total_files > 1 ? "$base_title (" . ($i + 1) . ")" : $base_title) : pathinfo($orig_name, PATHINFO_FILENAME);

                                $stmt = $pdo->prepare("INSERT INTO gallery (title, image_path, category, year) VALUES (?, ?, ?, ?)");
                                if ($stmt->execute([$img_title, $db_path, $category, $year])) {
                                    $uploaded_count++;
                                }
                            }
                        } else {
                            $file_errors[] = "$orig_name exceeds 10MB limit.";
                        }
                    } else {
                        $file_errors[] = "$orig_name invalid format.";
                    }
                }
            }

            if ($uploaded_count > 0) {
                $success = "Successfully uploaded {$uploaded_count} photo(s) into folder {$rel_dir}!";
            } else {
                $error = !empty($file_errors) ? implode(' ', $file_errors) : "No valid images were uploaded.";
            }
        } else {
            $error = "Please select at least 1 image to upload.";
        }
    } elseif ($action === 'delete') {
        $image_id = (int)$_POST['image_id'];

        $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
        $stmt->execute([$image_id]);
        $img = $stmt->fetch();

        if ($img) {
            if (!empty($img['image_path']) && file_exists('../' . $img['image_path'])) {
                @unlink('../' . $img['image_path']);
            }
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $stmt->execute([$image_id]);
            $success = "Image deleted successfully.";
        }
    }
}

// Search & Filtering
$filter_year = $_GET['year'] ?? 'all';
$filter_cat  = $_GET['category'] ?? 'all';
$search      = trim($_GET['search'] ?? '');

$where = "1=1";
$params = [];

if ($filter_year !== 'all') {
    $where .= " AND COALESCE(year, YEAR(created_at)) = ?";
    $params[] = (int)$filter_year;
}

if ($filter_cat !== 'all') {
    $where .= " AND category = ?";
    $params[] = $filter_cat;
}

if (!empty($search)) {
    $where .= " AND (title LIKE ? OR category LIKE ? OR image_path LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
}

$stmt = $pdo->prepare("SELECT *, COALESCE(year, YEAR(created_at), 2026) as photo_year FROM gallery WHERE $where ORDER BY photo_year DESC, created_at DESC");
$stmt->execute($params);
$gallery = $stmt->fetchAll();

// Distinct years & categories for filters
$years_list = $pdo->query("SELECT DISTINCT COALESCE(year, YEAR(created_at), 2026) as yr FROM gallery ORDER BY yr DESC")->fetchAll(PDO::FETCH_COLUMN);
$cats_list  = $pdo->query("SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Gallery & Photo Upload | NSS Admin';
require_once '../includes/header.php';
?>

<style>
.drag-drop-zone {
    border: 2px dashed #cbd5e1;
    background: #f8fafc;
    border-radius: 16px;
    padding: 2.5rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}
.drag-drop-zone:hover, .drag-drop-zone.dragover {
    border-color: #f4a11d;
    background: #fffbeb;
    box-shadow: 0 10px 30px rgba(244, 161, 29, 0.15);
}
.drag-drop-zone input[type="file"] { display: none; }
.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 12px;
    margin-top: 1.25rem;
    max-height: 280px;
    overflow-y: auto;
    padding: 8px;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.preview-thumb-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    height: 100px;
    border: 1px solid #cbd5e1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.preview-thumb-card img {
    width: 100%; height: 100%; object-fit: cover;
}
.preview-thumb-card .file-name {
    position: absolute; bottom: 0; inset-x: 0;
    background: rgba(15,23,42,0.85); color: white;
    font-size: 0.68rem; padding: 2px 4px; text-align: center;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.gallery-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}
.gallery-card {
    background: #ffffff;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    transition: all 0.3s ease;
    position: relative;
}
.gallery-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(27,54,93,0.12);
}
.gallery-card img {
    width: 100%; height: 170px; object-fit: cover; display: block;
}
.gallery-card-body { padding: 0.9rem 1rem; }
.gallery-card-body h5 { margin: 0 0 6px; font-size: 0.92rem; color: #1b365d; font-weight: 700; }
.gallery-card-meta { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 4px; }
.folder-badge {
    font-size: 0.72rem; color: #64748b; font-family: monospace; display: block; word-break: break-all; margin-top: 4px;
}
</style>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <h2><i class="fas fa-images text-primary"></i> Photo Gallery Management</h2>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge status-approved" style="font-size:0.85rem; padding: 6px 14px;"><i class="fas fa-user-shield"></i> Logged in as: <strong><?= htmlspecialchars($_SESSION['name'] ?? 'NSS Admin') ?></strong></span>
                <span class="badge status-approved" style="font-size:0.85rem; padding: 6px 14px;"><i class="fas fa-camera"></i> Total Photos: <?= count($gallery) ?></span>
            </div>
        </header>

        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <!-- Multi-Image Upload Card -->
        <div class="dashboard-card glass-panel mb-4">
            <h3><i class="fas fa-cloud-upload-alt text-accent"></i> Drag & Drop Multi-Image Uploader (Batch Upload 10+ Photos)</h3>
            <p style="color:#64748b; font-size:0.88rem; margin-bottom:1.25rem;">
                Select or drag multiple photos at once. Photos will automatically be organized in structured subfolders by <strong>Year</strong> and <strong>Category</strong> (e.g. <code>uploads/gallery/2026/Blood_Donation/</code>).
            </p>

            <form method="POST" enctype="multipart/form-data" id="multiUploadForm">
                <input type="hidden" name="action" value="upload">

                <!-- Metadata Row -->
                <div class="form-row mb-3" style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label style="font-weight:600; font-size:0.85rem;">Academic / Event Year *</label>
                        <select name="year" class="form-control" required>
                            <?php 
                            $currY = (int)date('Y');
                            for ($y = $currY; $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?> Academic Year</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:600; font-size:0.85rem;">Category *</label>
                        <select name="category" id="uploadCategorySelect" class="form-control" required onchange="toggleCustomCatInput(this)">
                            <option value="Camps">Camps & Special Village Drives</option>
                            <option value="Blood Donation">Blood Donation & Health</option>
                            <option value="Plantation">Green India & Plantation</option>
                            <option value="Parade">Parade & Drills</option>
                            <option value="Cultural">Cultural & Awareness Rallies</option>
                            <option value="General">General NSS Activity</option>
                            <option value="__custom__">✏️ Enter Custom Category...</option>
                        </select>
                        <input type="text" name="custom_category" id="customCatInput" class="form-control" placeholder="Type custom category name..." style="display:none; margin-top:8px;">
                    </div>

                    <div class="form-group">
                        <label style="font-weight:600; font-size:0.85rem;">Batch Title Prefix <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Special Camp Day 1">
                    </div>
                </div>

                <!-- Drag and Drop Dropzone -->
                <div class="drag-drop-zone mb-3" id="dropZone" onclick="document.getElementById('multiFileInput').click()">
                    <i class="fas fa-images fa-3x text-accent mb-2"></i>
                    <h4 style="margin:0; color:#1b365d;">Drag & Drop 10+ Images Here</h4>
                    <p style="color:#64748b; font-size:0.85rem; margin:4px 0 0;">Or click to browse and select multiple files simultaneously (JPG, PNG, WEBP, GIF)</p>
                    <input type="file" name="images[]" id="multiFileInput" accept="image/*" multiple required onchange="handleFileSelect(this.files)">
                </div>

                <!-- Selected File Count & Preview Grid -->
                <div id="stagedPreviewSection" style="display:none; margin-bottom:1.25rem;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong style="color:#1b365d;" id="selectedCountText">0 Images Selected</strong>
                        <button type="button" class="btn btn-sm btn-outline text-danger" onclick="clearStagedFiles()"><i class="fas fa-times"></i> Clear All</button>
                    </div>
                    <div class="preview-grid" id="previewGrid"></div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg" id="uploadSubmitBtn"><i class="fas fa-upload"></i> Start Uploading Photos</button>
                </div>
            </form>
        </div>

        <!-- Filter & Search Controls -->
        <div class="dashboard-card glass-panel mb-4">
            <form method="GET" class="d-flex gap-3 align-items-center flex-wrap">
                <div style="flex:1; min-width:180px;">
                    <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">SEARCH GALLERY</label>
                    <input type="text" name="search" class="form-control" placeholder="Title, category, file path..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div style="width:160px;">
                    <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">YEAR</label>
                    <select name="year" class="form-control" onchange="this.form.submit()">
                        <option value="all">All Years</option>
                        <?php foreach ($years_list as $y): ?>
                            <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>><?= $y ?> Year</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="width:180px;">
                    <label style="font-size:0.78rem; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">CATEGORY</label>
                    <select name="category" class="form-control" onchange="this.form.submit()">
                        <option value="all">All Categories</option>
                        <?php foreach ($cats_list as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $filter_cat === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="align-self:flex-end; display:flex; gap:8px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                    <a href="admin-gallery.php" class="btn btn-outline" title="Reset Filters"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <!-- Gallery Grid Display -->
        <div class="dashboard-card glass-panel">
            <h3><i class="fas fa-th text-primary"></i> Uploaded Photo Directory</h3>
            <div class="gallery-card-grid mt-3">
                <?php foreach ($gallery as $img): 
                    $imgPath = '../' . $img['image_path'];
                ?>
                <div class="gallery-card">
                    <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($img['title']) ?>" loading="lazy">
                    <div class="gallery-card-body">
                        <h5><?= htmlspecialchars($img['title'] ?: 'Untitled Photo') ?></h5>
                        <div class="gallery-card-meta">
                            <span class="badge bg-primary text-white"><?= htmlspecialchars($img['category'] ?? 'General') ?></span>
                            <span class="badge bg-secondary"><?= htmlspecialchars($img['photo_year'] ?? '2026') ?></span>
                        </div>
                        <span class="folder-badge" title="<?= htmlspecialchars($img['image_path']) ?>">
                            <i class="fas fa-folder text-accent"></i> <?= htmlspecialchars($img['image_path']) ?>
                        </span>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2" style="border-top:1px solid #f1f5f9;">
                            <small class="text-muted"><?= date('d M Y', strtotime($img['created_at'])) ?></small>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this photo permanently?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline text-danger" title="Delete"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$gallery): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-images fa-3x mb-2" style="color:#cbd5e1;"></i>
                    <p>No photos found matching your criteria. Use the dropzone above to batch upload images.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function toggleCustomCatInput(sel) {
    const customInp = document.getElementById('customCatInput');
    if (sel.value === '__custom__') {
        customInp.style.display = 'block';
        customInp.required = true;
        customInp.focus();
    } else {
        customInp.style.display = 'none';
        customInp.required = false;
        customInp.value = '';
    }
}

const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('multiFileInput');
const previewGrid = document.getElementById('previewGrid');
const previewSec = document.getElementById('stagedPreviewSection');
const countText = document.getElementById('selectedCountText');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
});

dropZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    fileInput.files = files;
    handleFileSelect(files);
});

function handleFileSelect(files) {
    if (!files || !files.length) {
        previewSec.style.display = 'none';
        return;
    }

    previewGrid.innerHTML = '';
    countText.textContent = `${files.length} Photo(s) Selected for Upload`;
    previewSec.style.display = 'block';

    Array.from(files).forEach((file, index) => {
        if (!file.type.startsWith('image/')) return;

        const card = document.createElement('div');
        card.className = 'preview-thumb-card';

        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);

        const nameLabel = document.createElement('div');
        nameLabel.className = 'file-name';
        nameLabel.textContent = file.name;

        card.appendChild(img);
        card.appendChild(nameLabel);
        previewGrid.appendChild(card);
    });
}

function clearStagedFiles() {
    fileInput.value = '';
    previewGrid.innerHTML = '';
    previewSec.style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>
