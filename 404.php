<?php
http_response_code(404);
$pageTitle = '404 - Page Not Found | NSS TNGPTC Madurai';
require_once __DIR__ . '/includes/header.php';
?>

<div style="min-height:75vh; display:flex; align-items:center; justify-content:center; padding:60px 5%; text-align:center;">
    <div style="max-width:650px; background:#ffffff; border:1px solid #e2e8f0; border-radius:24px; padding:3.5rem 2rem; box-shadow:0 20px 60px rgba(13,35,58,0.08);" data-aos="zoom-in">
        <div style="display:inline-flex; align-items:center; justify-content:center; width:90px; height:90px; border-radius:50%; background:rgba(244,161,29,0.15); color:#d98200; font-size:2.8rem; margin-bottom:1.5rem;">
            <i class="fas fa-compass"></i>
        </div>
        <h1 style="font-size:4rem; font-weight:800; color:#1b365d; margin:0 0 0.5rem; line-height:1; font-family:'Outfit',sans-serif;">404</h1>
        <h2 style="font-size:1.6rem; color:#0f172a; margin-bottom:1rem; font-family:'Outfit',sans-serif;">Page Not Found</h2>
        <p style="color:#64748b; font-size:1.05rem; line-height:1.7; margin-bottom:2rem;">
            The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>
        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="<?= $root ?>index.php" class="btn btn-primary" style="padding:0.75rem 1.75rem; border-radius:12px; font-weight:700;">
                <i class="fas fa-home"></i> Back to Homepage
            </a>
            <a href="<?= $root ?>events.php" class="btn btn-outline" style="padding:0.75rem 1.75rem; border-radius:12px; font-weight:700; color:#1b365d; border-color:#1b365d;">
                <i class="fas fa-calendar-alt"></i> View Events
            </a>
            <a href="<?= $root ?>contact.php" class="btn btn-outline" style="padding:0.75rem 1.75rem; border-radius:12px; font-weight:700; color:#1b365d; border-color:#1b365d;">
                <i class="fas fa-envelope"></i> Contact Office
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
