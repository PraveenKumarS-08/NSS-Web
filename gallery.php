<?php
$pageTitle = 'Photo & Activity Gallery | NSS TNGPTC Madurai';
require_once __DIR__ . '/includes/header.php';

// Fetch gallery images from DB
$dbImages = [];
$groupedByYear = [];

try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT *, COALESCE(year, YEAR(created_at), 2026) as photo_year FROM gallery ORDER BY photo_year DESC, created_at DESC");
        $dbImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($dbImages as $img) {
            $yr = $img['photo_year'];
            $groupedByYear[$yr][] = $img;
        }
    }
} catch (Exception $e) {}

// Categories for filtering
$categories = ['All', 'Parade', 'Health', 'Environment', 'Camps', 'Community', 'Cultural'];

if (empty($groupedByYear)) {
    // Default sample grouping
    $groupedByYear[2026] = [
        ['title' => 'Annual Parade Drill 2026', 'category' => 'Parade', 'image_path' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=600&auto=format&fit=crop&q=60'],
        ['title' => 'Blood Donation Camp 2026', 'category' => 'Health', 'image_path' => 'https://images.unsplash.com/photo-1615461066841-6116e61058f4?w=600&auto=format&fit=crop&q=60'],
        ['title' => 'Green India Tree Plantation', 'category' => 'Environment', 'image_path' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=600&auto=format&fit=crop&q=60']
    ];
    $groupedByYear[2025] = [
        ['title' => 'Swachh Bharat Cleanliness Drive', 'category' => 'Environment', 'image_path' => 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=600&auto=format&fit=crop&q=60'],
        ['title' => 'Village Adoption Special Camp', 'category' => 'Camps', 'image_path' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&auto=format&fit=crop&q=60'],
        ['title' => 'National Youth Day Rally', 'category' => 'Cultural', 'image_path' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=600&auto=format&fit=crop&q=60']
    ];
}
?>

<div class="page-hero" style="padding: 90px 5% 40px; text-align: center; background: linear-gradient(135deg, #1b365d 0%, #0d233a 100%); border-bottom: 3px solid #f4a11d;">
    <h1 class="page-title" style="font-size:2.8rem; color:white; margin-bottom: 0.5rem;">NSS Activity Photo Gallery</h1>
    <p style="color:#f4a11d; font-size:1.1rem; font-weight:600;">Glimpses of Service, Leadership & Community Impact</p>
</div>

<section class="section" style="padding: 50px 5%; max-width: 1400px; margin: 0 auto;">
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <div style="text-align: right; margin-bottom: 25px;">
        <a href="dashboard/admin-gallery.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Gallery Photos</a>
    </div>
    <?php endif; ?>

    <div class="gallery-filters" style="display:flex; justify-content:center; gap:12px; margin-bottom:50px; flex-wrap:wrap;">
        <?php foreach ($categories as $idx => $cat): ?>
            <button type="button" class="filter-btn <?= $idx === 0 ? 'active' : '' ?>" data-filter="<?= strtolower($cat) ?>"><?= $cat ?></button>
        <?php endforeach; ?>
    </div>

    <!-- Gallery Grouped By Year Sections -->
    <?php foreach ($groupedByYear as $year => $photos): ?>
    <div class="year-section mb-5" data-year="<?= $year ?>">
        <div style="display:flex; align-items:center; gap:15px; margin-bottom:24px;">
            <h2 style="font-family:'Outfit',sans-serif; color:#1b365d; margin:0; font-size:2rem; font-weight:800;">
                <i class="fas fa-calendar-alt text-accent"></i> <?= $year ?> Activities
            </h2>
            <div style="flex:1; height:2px; background:linear-gradient(90deg, #f4a11d 0%, #e2e8f0 100%);"></div>
        </div>

        <div class="gallery-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
            <?php foreach ($photos as $img): 
                $path = $img['image_path'] ?? $img['image'] ?? '';
                if (!empty($path) && strpos($path, 'uploads/') !== 0 && strpos($path, 'http') !== 0) {
                    $path = 'uploads/gallery/' . $path;
                }
            ?>
                <div class="gallery-item" data-category="<?= strtolower($img['category'] ?? 'all') ?>" data-aos="zoom-in" onclick="openLightbox('<?= htmlspecialchars($path) ?>', '<?= htmlspecialchars($img['title'] ?? '') ?>')">
                    <img src="<?= htmlspecialchars($path) ?>" alt="<?= htmlspecialchars($img['title'] ?? 'NSS Photo') ?>" style="width:100%; height:250px; object-fit:cover; border-radius:12px;">
                    <div class="gallery-overlay">
                        <h3 class="gallery-title" style="color:white; margin:0; font-size:1.1rem;"><?= htmlspecialchars($img['title'] ?? 'NSS Activity') ?></h3>
                        <span class="gallery-cat" style="color:#f4a11d; font-size:0.85rem; font-weight:600;"><?= htmlspecialchars($img['category'] ?? 'General') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<!-- Lightbox Modal -->
<div id="galleryLightbox" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.9); z-index:9999; align-items:center; justify-content:center; padding:20px;" onclick="closeLightbox()">
    <div style="position:relative; max-width:900px; width:100%; text-align:center;" onclick="event.stopPropagation()">
        <button type="button" onclick="closeLightbox()" style="position:absolute; top:-40px; right:0; background:none; border:none; color:white; font-size:2rem; cursor:pointer;">&times;</button>
        <img id="lightboxImg" src="" alt="Full View" style="max-width:100%; max-height:80vh; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.5);">
        <h3 id="lightboxCaption" style="color:white; margin-top:15px; font-family:'Outfit',sans-serif;"></h3>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const items = document.querySelectorAll('.gallery-item');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const cat = btn.getAttribute('data-filter');
            
            items.forEach(item => {
                const itemCat = item.getAttribute('data-category');
                if (cat === 'all' || itemCat === cat) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});

function openLightbox(src, caption) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightboxCaption').textContent = caption;
    document.getElementById('galleryLightbox').style.display = 'flex';
}

function closeLightbox() {
    document.getElementById('galleryLightbox').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
