<?php
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['name'] ?? $_SESSION['user_data']['name'] ?? 'NSS Admin';
?>
<aside class="sidebar glass-panel" id="adminSidebar">
    <div class="sidebar-header">
        <?= function_exists('getNssLogoSvg') ? getNssLogoSvg(36) : '<img src="../assets/images/nss-logo.png" alt="NSS Logo">' ?>
        <div>
            <h3>NSS Admin</h3>
            <span style="font-size:0.75rem; color:#f4a11d; font-weight:600;">TNGPTC Madurai-11</span>
        </div>
    </div>

    <!-- Active Logged-in Admin User Card -->
    <div style="background: rgba(244,161,29,0.12); border: 1px solid rgba(244,161,29,0.25); padding: 10px 14px; border-radius: 12px; margin: 10px 12px 16px; display: flex; align-items: center; gap: 10px;">
        <div style="width: 32px; height: 32px; border-radius: 50%; background: #f4a11d; color: #0d233a; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.88rem; flex-shrink:0;">
            <?= strtoupper(substr($admin_name, 0, 1)) ?>
        </div>
        <div style="line-height: 1.25; overflow:hidden;">
            <span style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; display: block;">Active Admin</span>
            <strong style="color: #ffffff; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;" title="<?= htmlspecialchars($admin_name) ?>">
                <?= htmlspecialchars($admin_name) ?>
            </strong>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="admin.php" class="<?= $current_page == 'admin.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="admin-volunteers.php" class="<?= $current_page == 'admin-volunteers.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Volunteers
        </a>
        <a href="admin-activity-logs.php" class="<?= $current_page == 'admin-activity-logs.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i> Student Activity Logs
        </a>
        <a href="admin-alumni.php" class="<?= $current_page == 'admin-alumni.php' ? 'active' : '' ?>">
            <i class="fas fa-user-graduate"></i> Alumni Network
        </a>
        <a href="admin-registrations.php" class="<?= $current_page == 'admin-registrations.php' ? 'active' : '' ?>">
            <i class="fas fa-clipboard-list"></i> Event Registrations
        </a>
        <a href="admin-attendance.php" class="<?= $current_page == 'admin-attendance.php' ? 'active' : '' ?>">
            <i class="fas fa-user-check"></i> Attendance & Hours
        </a>
        <a href="admin-events.php" class="<?= $current_page == 'admin-events.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i> Events & Camps
        </a>
        <a href="admin-gallery.php" class="<?= $current_page == 'admin-gallery.php' ? 'active' : '' ?>">
            <i class="fas fa-images"></i> Photo Gallery
        </a>
        <a href="admin-announcements.php" class="<?= $current_page == 'admin-announcements.php' ? 'active' : '' ?>">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="admin-profile.php" class="<?= $current_page == 'admin-profile.php' ? 'active' : '' ?>">
            <i class="fas fa-key"></i> Admin Profile & Passwords
        </a>
        <a href="../logout.php" class="text-danger" style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>
