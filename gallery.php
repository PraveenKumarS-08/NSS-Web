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
?>

<div class="page-hero">
    <h1 class="page-title">NSS Activity Photo Gallery</h1>
    <p>Glimpses of Service, Leadership & Community Impact</p>
</div>

<section class="section" style="padding: 50px 5%; max-width: 1400px; margin: 0 auto;">
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <div style="text-align: right; margin-bottom: 25px;">
        <a href="dashboard/admin-gallery.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Gallery Photos</a>
    </div>
    <?php endif; ?>

    <?php if (!empty($groupedByYear)): ?>
        <!-- Filter Controls Bar: Category & Year -->
        <div style="display:flex; justify-content:center; align-items:center; gap:20px; margin-bottom:40px; flex-wrap:wrap;">
            <div class="gallery-filters" style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center;">
                <?php foreach ($categories as $idx => $cat): ?>
                    <button type="button" class="filter-btn <?= $idx === 0 ? 'active' : '' ?>" data-filter="<?= strtolower($cat) ?>"><?= $cat ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Year Selector Dropdown -->
            <div style="display:flex; align-items:center; gap:8px; background:var(--bg); padding:6px 16px; border-radius:30px; border:1px solid var(--border);">
                <i class="fas fa-calendar-alt text-primary"></i>
                <span style="font-weight:700; font-size:0.88rem; color:var(--primary);">Year:</span>
                <select id="yearFilterSelect" style="background:none; border:none; font-weight:700; color:var(--primary); cursor:pointer; font-size:0.9rem; outline:none;">
                    <option value="all">All Years</option>
                    <?php foreach (array_keys($groupedByYear) as $y): ?>
                        <option value="<?= $y ?>"><?= $y ?> Academic Year</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Gallery Grouped By Year Sections -->
        <?php foreach ($groupedByYear as $year => $photos): ?>
        <div class="year-section mb-5" data-year="<?= $year ?>">
            <div style="display:flex; align-items:center; gap:15px; margin-bottom:24px;">
                <h2 style="font-family:'Outfit',sans-serif; color:var(--primary); margin:0; font-size:2rem; font-weight:800;">
                    <i class="fas fa-calendar-alt text-accent"></i> <?= $year ?> Activities
                </h2>
                <div style="flex:1; height:2px; background:linear-gradient(90deg, var(--accent) 0%, var(--border) 100%);"></div>
            </div>

            <div class="gallery-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
                <?php foreach ($photos as $img): 
                    $path = $img['image_path'] ?? $img['image'] ?? '';
                    if (!empty($path) && strpos($path, 'uploads/') !== 0 && strpos($path, 'http') !== 0) {
                        $path = 'uploads/gallery/' . $path;
                    }
                ?>
                    <div class="gallery-item" data-category="<?= strtolower($img['category'] ?? 'all') ?>" data-year="<?= $year ?>" data-aos="zoom-in" onclick="openLightbox('<?= htmlspecialchars($path) ?>', '<?= htmlspecialchars($img['title'] ?? '') ?>')">
                        <img src="<?= htmlspecialchars($path) ?>" alt="<?= htmlspecialchars($img['title'] ?? 'NSS Photo') ?>" style="width:100%; height:250px; object-fit:cover; border-radius:12px;">
                        <div class="gallery-overlay">
                            <h3 class="gallery-title" style="color:white; margin:0; font-size:1.1rem;"><?= htmlspecialchars($img['title'] ?? 'NSS Activity') ?></h3>
                            <span class="gallery-cat" style="color:var(--accent); font-size:0.85rem; font-weight:600;"><?= htmlspecialchars($img['category'] ?? 'General') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Clean Empty State when no photos exist -->
        <div style="text-align:center; padding:5rem 1rem; background:#fff; border-radius:20px; border:1px solid var(--border); box-shadow:0 4px 20px rgba(0,0,0,0.04); max-width:650px; margin:20px auto;" data-aos="fade-up">
            <div style="width:85px; height:85px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:inline-flex; align-items:center; justify-content:center; font-size:2.4rem; margin-bottom:1.25rem;">
                <i class="fas fa-images"></i>
            </div>
            <h2 style="color:var(--primary); font-family:'Outfit',sans-serif; margin-bottom:0.6rem; font-size:1.8rem; font-weight:700;">No Activity Photos Uploaded Yet</h2>
            <p style="color:var(--text-muted); font-size:1rem; line-height:1.7; margin:0 0 1.75rem;">
                High-resolution glimpses from our upcoming blood donation camps, tree plantation drives, special camps, and cultural events will be displayed here.
            </p>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="dashboard/admin-gallery.php" class="btn btn-primary" style="padding:0.8rem 1.8rem; border-radius:12px; font-weight:700;">
                    <i class="fas fa-cloud-upload-alt"></i> Upload First Photos
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
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
    const yearSelect = document.getElementById('yearFilterSelect');
    const yearSections = document.querySelectorAll('.year-section');
    const items = document.querySelectorAll('.gallery-item');

    let activeCat = 'all';
    let activeYear = 'all';

    function applyFilters() {
        yearSections.forEach(sec => {
            const secYear = sec.getAttribute('data-year');
            if (activeYear === 'all' || activeYear === secYear) {
                sec.style.display = 'block';
            } else {
                sec.style.display = 'none';
            }
        });

        items.forEach(item => {
            const itemCat = item.getAttribute('data-category');
            const itemYear = item.getAttribute('data-year');

            const matchCat = (activeCat === 'all' || itemCat === activeCat);
            const matchYear = (activeYear === 'all' || activeYear === itemYear);

            if (matchCat && matchYear) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeCat = btn.getAttribute('data-filter');
            applyFilters();
        });
    });

    if (yearSelect) {
        yearSelect.addEventListener('change', () => {
            activeYear = yearSelect.value;
            applyFilters();
        });
    }
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

<style>
@media (max-width: 768px) {
    .gallery-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 14px !important; }
    .gallery-grid img { height: 180px !important; }
    .page-hero { padding: 80px 4% 30px !important; }
    .page-hero .page-title { font-size: 2rem !important; }
    .gallery-filters { gap: 6px !important; }
    .filter-btn { font-size: 0.8rem; padding: 0.45rem 0.85rem; }
    .year-section h2 { font-size: 1.5rem !important; }
}
@media (max-width: 480px) {
    .gallery-grid { grid-template-columns: 1fr !important; gap: 12px !important; }
    .gallery-grid img { height: 220px !important; border-radius: 10px !important; }
    .page-hero .page-title { font-size: 1.65rem !important; }
    #galleryLightbox img { max-width: 95vw !important; max-height: 70vh !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
