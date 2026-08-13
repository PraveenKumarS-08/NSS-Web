<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar glass-panel" id="adminSidebar">
    <div class="sidebar-header">
        <?= function_exists('getNssLogoSvg') ? getNssLogoSvg(36) : '<img src="../assets/images/nss-logo.png" alt="NSS Logo">' ?>
        <div>
            <h3>NSS Admin</h3>
            <span style="font-size:0.75rem; color:#f4a11d; font-weight:600;">TNGPTC Madurai-11</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="admin.php" class="<?= $current_page == 'admin.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="admin-volunteers.php" class="<?= $current_page == 'admin-volunteers.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Volunteers
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
        <a href="admin-messages.php" class="<?= $current_page == 'admin-messages.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i> Messages Inbox
        </a>
        <a href="../logout.php" class="text-danger" style="margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>
