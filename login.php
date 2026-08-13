<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['user_role'] ?? '') === 'admin') { header("Location: dashboard/admin.php"); exit; }
    elseif (($_SESSION['user_role'] ?? '') === 'volunteer') { header("Location: dashboard/volunteer.php"); exit; }
    elseif (($_SESSION['user_role'] ?? '') === 'alumni') { header("Location: dashboard/alumni.php"); exit; }
    exit;
}

$error = '';
$selected_role = $_POST['role'] ?? 'volunteer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'volunteer';
    $selected_role = $role;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($role !== 'admin' && $user['status'] === 'pending') {
                $error = "Your account is pending approval by NSS Admin.";
            } elseif ($role !== 'admin' && $user['status'] === 'rejected') {
                $error = "Your account application has been rejected. Please contact NSS office.";
            } else {
                // Use consistent session keys that match auth.php
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_role'] = $user['role'];  // used by currentRole()
                $_SESSION['role']      = $user['role'];  // keep for compatibility
                $_SESSION['name']      = $user['name'];
                $_SESSION['user_data'] = [
                    'name'          => $user['name'],
                    'email'         => $user['email'],
                    'status'        => $user['status'],
                    'profile_photo' => $user['profile_photo'] ?? null,
                ];

                if ($role === 'admin')     { header("Location: dashboard/admin.php");     exit; }
                elseif ($role === 'volunteer') { header("Location: dashboard/volunteer.php"); exit; }
                elseif ($role === 'alumni')    { header("Location: dashboard/alumni.php");    exit; }
                exit;
            }
        } else {
            $error = "Invalid credentials for the selected (" . ucfirst($role) . ") login.";
        }
    } catch (PDOException $e) {
        $error = "System error during authentication. Please try again.";
    }
}
$pageTitle = 'Login | NSS TNGPTC Madurai';
require_once 'includes/header.php';
?>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo-badge" style="background:transparent; border:none; box-shadow:none;">
                <?= function_exists('getNssLogoImg') ? getNssLogoImg(64) : '<img src="assets/images/nss-logo.png" alt="NSS Logo" style="width:64px;height:64px;">' ?>
            </div>
            <h2>TNGPTC Madurai-11</h2>
            <p>National Service Scheme Portal Login</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i data-lucide="alert-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle"></i>
                <span>Registration successful! Your account is pending admin approval.</span>
            </div>
        <?php endif; ?>

        <!-- Role Selection Tabs -->
        <div class="auth-tabs" role="tablist">
            <button type="button" class="tab-btn <?= $selected_role === 'volunteer' ? 'active' : '' ?>" data-target="volunteer" role="tab">
                <i data-lucide="user-check"></i> Volunteer
            </button>
            <button type="button" class="tab-btn <?= $selected_role === 'alumni' ? 'active' : '' ?>" data-target="alumni" role="tab">
                <i data-lucide="graduation-cap"></i> Alumni
            </button>
            <button type="button" class="tab-btn <?= $selected_role === 'admin' ? 'active' : '' ?>" data-target="admin" role="tab">
                <i data-lucide="shield-check"></i> Admin
            </button>
        </div>

        <form method="POST" action="login.php" class="auth-form" id="loginForm">
            <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($selected_role) ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <i data-lucide="mail"></i>
                    <input type="email" name="email" id="email" class="form-control" required placeholder="name@domain.com">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon" style="position:relative;">
                    <i data-lucide="key-round"></i>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Enter password" style="padding-right: 2.8rem;">
                    <button type="button" id="togglePassword" title="Show/Hide Password"
                        style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b; padding:0; display:flex; align-items:center; z-index:5;">
                        <i id="eyeIcon" data-lucide="eye" style="width:18px; height:18px;"></i>
                    </button>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block loading-btn">
                    <span class="btn-text">Sign In as <span id="roleLabel"><?= ucfirst($selected_role) ?></span></span>
                    <span class="spinner" style="display:none;"></span>
                </button>
            </div>

            <div class="auth-footer-links">
                <a href="forgot-password.php" class="text-link" id="forgotPasswordLink" style="display: <?= $selected_role === 'admin' ? 'none' : 'inline-block' ?>;">Forgot password?</a>
                <div id="registerLinks" class="register-hint">
                    <?php if ($selected_role === 'volunteer'): ?>
                        <p>New Volunteer? <a href="register.php" class="text-accent">Register Here</a></p>
                    <?php elseif ($selected_role === 'alumni'): ?>
                        <p>New Alumni? <a href="register-alumni.php" class="text-accent">Register Here</a></p>
                    <?php else: ?>
                        <p class="admin-note"><i data-lucide="info"></i> Admin credentials are managed by College NSS Office</p>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab-btn');
    const roleInput = document.getElementById('roleInput');
    const registerLinks = document.getElementById('registerLinks');
    const roleLabel = document.getElementById('roleLabel');
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');

    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            tabs.forEach(t => t.classList.remove('active'));
            const clickedBtn = e.currentTarget;
            clickedBtn.classList.add('active');
            
            const role = clickedBtn.dataset.target;
            roleInput.value = role;
            if (roleLabel) roleLabel.textContent = role.charAt(0).toUpperCase() + role.slice(1);
            
            if (role === 'volunteer') {
                registerLinks.innerHTML = '<p>New Volunteer? <a href="register.php" class="text-accent">Register Here</a></p>';
                if (forgotPasswordLink) forgotPasswordLink.style.display = 'inline-block';
            } else if (role === 'alumni') {
                registerLinks.innerHTML = '<p>New Alumni? <a href="register-alumni.php" class="text-accent">Register Here</a></p>';
                if (forgotPasswordLink) forgotPasswordLink.style.display = 'inline-block';
            } else {
                registerLinks.innerHTML = '<p class="admin-note"><i data-lucide="info"></i> Admin credentials are managed by College NSS Office</p>';
                if (forgotPasswordLink) forgotPasswordLink.style.display = 'none';
            }
            if (window.lucide) lucide.createIcons();
        });
    });

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                btn.classList.add('is-loading');
                const btnText = btn.querySelector('.btn-text');
                const spinner = btn.querySelector('.spinner');
                if (btnText) btnText.style.display = 'none';
                if (spinner) spinner.style.display = 'inline-block';
            }
        });
    }

    // Show / Hide Password Toggle
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (toggleBtn && passwordInput && eyeIcon) {
        toggleBtn.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            eyeIcon.setAttribute('data-lucide', isHidden ? 'eye-off' : 'eye');
            if (window.lucide) lucide.createIcons();
            passwordInput.focus();
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
