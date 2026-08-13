<?php
$pageTitle = 'Forgot Password | NSS TNGPTC Madurai';
require_once 'includes/header.php';
?>

<div style="min-height: 70vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-panel" style="max-width: 480px; width: 100%; padding: 2.5rem; border-radius: 16px; text-align: center;">
        <div style="width: 80px; height: 80px; background: rgba(244,161,29,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
            <i data-lucide="lock-keyhole" style="width:40px; height:40px; color: var(--accent);"></i>
        </div>
        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 0.5rem;">Forgot Password?</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem; line-height: 1.7;">
            Password reset via email is not yet configured. Please contact the <strong>NSS Programme Officer</strong> to reset your account password.
        </p>
        <div class="glass-panel" style="padding: 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; text-align: left;">
            <p style="margin: 0.4rem 0;"><i data-lucide="mail" style="width:16px; height:16px; vertical-align: middle; margin-right: 8px; color: var(--accent);"></i> <strong>Email:</strong> tngptcmadurai@gmail.com</p>
            <p style="margin: 0.4rem 0;"><i data-lucide="phone" style="width:16px; height:16px; vertical-align: middle; margin-right: 8px; color: var(--accent);"></i> <strong>Phone:</strong> (0452) 2370461</p>
            <p style="margin: 0.4rem 0;"><i data-lucide="map-pin" style="width:16px; height:16px; vertical-align: middle; margin-right: 8px; color: var(--accent);"></i> <strong>Office:</strong> TNGPTC, Bye-Pass Road, Madurai - 625011</p>
        </div>
        <a href="login.php" class="btn btn-primary" style="width: 100%; text-align: center; padding: 0.85rem;">
            <i data-lucide="arrow-left" style="width:16px; height:16px; vertical-align: middle; margin-right: 6px;"></i>
            Back to Login
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
