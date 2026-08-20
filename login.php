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

        if ($user) {
            $passwordValid = false;
            if (password_verify($password, $user['password'])) {
                $passwordValid = true;
            } elseif ($password === $user['password']) {
                $passwordValid = true;
            }

            if ($passwordValid) {
                if ($role !== 'admin' && $user['status'] === 'pending') {
                    $error = "Your account is pending approval by NSS Admin.";
                } elseif ($role !== 'admin' && $user['status'] === 'rejected') {
                    $error = "Your account application has been rejected. Please contact NSS office.";
                } else {
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['role']      = $user['role'];
                    $_SESSION['name']      = $user['name'];
                    $_SESSION['user_data'] = [
                        'name'          => $user['name'],
                        'email'         => $user['email'],
                        'status'        => $user['status'],
                        'profile_photo' => $user['profile_photo'] ?? null,
                    ];

                    logUserActivity($pdo, $user['id'], $user['name'], $user['role'], 'Login', 'Logged into ' . ucfirst($role) . ' portal');

                    if ($role === 'admin')     { header("Location: dashboard/admin.php");     exit; }
                    elseif ($role === 'volunteer') { header("Location: dashboard/volunteer.php"); exit; }
                    elseif ($role === 'alumni')    { header("Location: dashboard/alumni.php");    exit; }
                    exit;
                }
            } else {
                $error = "Invalid credentials for the selected (" . ucfirst($role) . ") login.";
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
                    <button type="button" id="togglePasswordBtn" title="Show/Hide Password" onclick="toggleLoginPassword()"
                        style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b; padding:4px; display:flex; align-items:center; justify-content:center; z-index:10;">
                        <i id="passwordEyeIcon" class="fas fa-eye" style="font-size:1.05rem;"></i>
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
function toggleLoginPassword() {
    const pwInput = document.getElementById('password');
    const eyeIcon = document.getElementById('passwordEyeIcon');
    if (!pwInput || !eyeIcon) return;
    
    if (pwInput.type === 'password') {
        pwInput.type = 'text';
        eyeIcon.className = 'fas fa-eye-slash text-primary';
    } else {
        pwInput.type = 'password';
        eyeIcon.className = 'fas fa-eye';
    }
    
    if (window.anime) {
        anime({
            targets: eyeIcon,
            scale: [1.3, 1],
            duration: 300,
            easing: 'easeOutBack'
        });
    }
}

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

            if (window.anime) {
                anime({
                    targets: clickedBtn,
                    scale: [0.95, 1],
                    duration: 300,
                    easing: 'easeOutCubic'
                });
            }
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

    // Anime.js Entrance Animation for Login Card
    if (window.anime) {
        anime({
            targets: '.login-card',
            translateY: [40, 0],
            opacity: [0, 1],
            easing: 'easeOutCubic',
            duration: 800,
            delay: 100
        });
        
        anime({
            targets: '.auth-tabs .tab-btn',
            opacity: [0, 1],
            translateY: [15, 0],
            delay: anime.stagger(100, {start: 300}),
            easing: 'easeOutQuad',
            duration: 500
        });

        anime({
            targets: '.form-group',
            opacity: [0, 1],
            translateX: [-20, 0],
            delay: anime.stagger(120, {start: 450}),
            easing: 'easeOutCubic',
            duration: 600
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
