<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$error = '';
$success = '';

// Handle Image Upload / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'upload') {
        $title = $_POST['title'] ?? '';
        $category = $_POST['category'] ?? 'General';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/gallery/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($file_ext, $allowed)) {
                if ($_FILES['image']['size'] <= 5 * 1024 * 1024) {
                    $new_name = uniqid('gal_') . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                        $image_path = 'uploads/gallery/' . $new_name;
                        
                        $stmt = $pdo->prepare("INSERT INTO gallery (title, image_path, category) VALUES (?, ?, ?)");
                        if ($stmt->execute([$title, $image_path, $category])) {
                            $success = "Image uploaded successfully.";
                        } else {
                            $error = "Database error.";
                        }
                    } else {
                        $error = "Failed to move uploaded file.";
                    }
                } else {
                    $error = "File size exceeds 5MB limit.";
                }
            } else {
                $error = "Invalid file format. Only JPG, PNG, WEBP allowed.";
            }
        } else {
            $error = "Please select an image to upload.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $image_id = (int)$_POST['image_id'];
        
        $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch();
        
        if ($image) {
            if (file_exists('../' . $image['image_path'])) {
                unlink('../' . $image['image_path']);
            }
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $stmt->execute([$image_id]);
            $success = "Image deleted successfully.";
        }
    }
}

// Fetch gallery items
$stmt = $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC");
$gallery = $stmt->fetchAll();

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../css/dashboard.css">
<style>
    .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
    .gallery-item { position: relative; border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .gallery-item img { width: 100%; height: 150px; object-fit: cover; display: block; }
    .gallery-info { padding: 10px; }
    .gallery-info h5 { margin: 0 0 5px 0; font-size: 14px; }
    .gallery-actions { position: absolute; top: 5px; right: 5px; }
    
    .upload-area { border: 2px dashed var(--accent-color); padding: 40px; text-align: center; border-radius: 10px; cursor: pointer; transition: all 0.3s; }
    .upload-area:hover { background: rgba(244, 161, 29, 0.05); }
    .upload-area input[type="file"] { display: none; }
</style>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <h2>Gallery Management</h2>
        </header>

        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="dashboard-card glass-panel mb-4">
            <h3>Upload New Photo</h3>
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="action" value="upload">
                
                <label class="upload-area mb-3 d-block" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-cloud-upload-alt fa-3x mb-2 text-accent"></i>
                    <p>Click to select image or drag and drop here<br><small class="text-muted">JPG, PNG, WEBP (Max 5MB)</small></p>
                    <p id="fileNameDisplay" class="text-primary mt-2" style="font-weight: bold;"></p>
                    <input type="file" name="image" id="fileInput" accept="image/*" required onchange="document.getElementById('fileNameDisplay').textContent = this.files[0].name;">
                </label>
                
                <div class="form-row" style="display:flex; gap:15px;">
                    <div class="form-group" style="flex: 2;">
                        <input type="text" name="title" class="form-control" placeholder="Image Title (Optional)">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <input type="text" name="category" class="form-control" placeholder="Category (e.g., Camp, Blood Donation)" value="General">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="dashboard-card glass-panel">
            <h3>Gallery Grid</h3>
            <div class="gallery-grid mt-3">
                <?php foreach($gallery as $img): ?>
                <div class="gallery-item">
                    <img src="../<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($img['title']) ?>">
                    <div class="gallery-info">
                        <h5><?= htmlspecialchars($img['title'] ?: 'Untitled') ?></h5>
                        <small class="badge bg-secondary"><?= htmlspecialchars($img['category']) ?></small>
                        <small class="text-muted d-block mt-1"><?= date('d M Y', strtotime($img['created_at'])) ?></small>
                    </div>
                    <div class="gallery-actions">
                        <form method="POST" onsubmit="return confirm('Delete this image?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if(!$gallery): ?>
                <p class="text-center text-muted">No images in gallery yet.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
