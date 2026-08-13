<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$departments = [
    "Civil Engineering (Shift I)",
    "Civil Engineering Tamil Medium (Shift II)",
    "Mechanical Engineering (Shift I)",
    "Mechanical Engineering Tamil Medium (Shift II)",
    "Electrical and Electronics Engineering (Shift I/II)",
    "Computer Engineering (Shift I)",
    "Mechanical Engineering (Sandwich)",
    "Plastic Technology (Sandwich)",
    "Polymer Technology (Sandwich)",
    "Web Designing (Shift II)",
    "Logistic Technology (Shift I)",
    "Civil Engineering (Part-Time)",
    "Mechanical Engineering (Part-Time)",
    "Electrical and Electronics Engineering (Part-Time)",
    "Robotics",
    "Printing Technology",
    "Others"
];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $batch_year = trim($_POST['batch_year'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $current_position = trim($_POST['current_position'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_captcha = trim($_POST['captcha_input'] ?? '');
    $session_captcha = $_SESSION['captcha_code'] ?? '';

    // Strong password pattern
    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/';

    if (empty($name) || empty($email) || empty($mobile) || empty($batch_year) || empty($password)) {
        $error = "Name, Email, Mobile, Batch Year, and Password are required.";
    } elseif ($user_captcha === '' || strtolower($user_captcha) !== strtolower($session_captcha)) {
        $error = "Invalid Security Captcha code. Please try again.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!preg_match($password_pattern, $password)) {
        $error = "Password must be at least 8 characters long and contain at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special symbol (@$!%*?&#).";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email is already registered.";
            } else {
                $pdo->beginTransaction();

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'alumni', 'pending')");
                $stmt->execute([$name, $email, $hashed_password]);
                $user_id = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO alumni (user_id, batch_year, current_position, company, linkedin_url, mobile) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $batch_year, $current_position, $company, $linkedin_url, $mobile]);

                $pdo->commit();
                header("Location: login.php?registered=1");
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Registration failed. Please try again.";
        }
    }
}

// Generate Captcha Code for Session
$captcha_code = substr(str_shuffle("23456789ABCDEFGHJKLMNPQRSTUVWXYZ"), 0, 5);
$_SESSION['captcha_code'] = $captcha_code;

$pageTitle = 'Alumni Registration | NSS TNGPTC Madurai';
require_once 'includes/header.php';
?>

<style>
.alumni-reg-wrapper {
    display: grid;
    grid-template-columns: 380px 1fr;
    min-height: calc(100vh - 68px);
}
.alumni-sidebar {
    background: linear-gradient(160deg, #0d233a 0%, #1b365d 60%, #243b5e 100%);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2.5rem;
    text-align: center;
}
.alumni-sidebar .nss-logo-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 3px solid #f4a11d;
    margin-bottom: 1.5rem;
    object-fit: contain;
    background: rgba(255,255,255,0.05);
    padding: 8px;
}
.alumni-form-panel {
    background: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 3rem 4rem;
}
.alumni-form-panel .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.captcha-box {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f1f5f9;
    padding: 8px 16px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
}
.captcha-text {
    font-family: 'Outfit', monospace;
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: 5px;
    color: #1b365d;
    user-select: none;
}
@media (max-width: 900px) {
    .alumni-reg-wrapper { grid-template-columns: 1fr; }
    .alumni-sidebar { padding: 2rem 1.5rem; }
    .alumni-form-panel { padding: 2rem 1.5rem; }
    .alumni-form-panel .form-row { grid-template-columns: 1fr; }
}
</style>

<div class="alumni-reg-wrapper">
    <div class="alumni-sidebar" data-aos="fade-right">
        <img src="assets/images/nss-logo.png" alt="NSS Logo" class="nss-logo-large">
        <h2 style="color:white; font-family:'Outfit',sans-serif;">NSS Alumni Network</h2>
        <p style="color:#f4a11d; font-weight:700;">Once an NSS Volunteer, Always a Servant Leader</p>
    </div>

    <div class="alumni-form-panel" data-aos="fade-left">
        <h3 style="color:#1b365d; font-family:'Outfit',sans-serif;"><i class="fas fa-user-graduate text-accent"></i> Alumni Registration</h3>
        <p style="color:#64748b; font-size:0.92rem; margin-bottom:1.5rem;">Register to join the official NSS Alumni Directory of TNGPTC Madurai</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; padding:12px; border-radius:10px; margin-bottom:20px;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="alumniForm">
            <div class="form-row">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:4px;">Full Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="Your full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" style="width:100%; padding:0.7rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:4px;">Batch Year *</label>
                    <select name="batch_year" class="form-control" required style="width:100%; padding:0.7rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
                        <option value="">Select Batch Year</option>
                        <?php for ($y = 2026; $y >= 2010; $y--): ?>
                            <option value="<?= $y ?>" <?= (($_POST['batch_year'] ?? '') == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:4px;">Department *</label>
                <select name="department" class="form-control" required style="width:100%; padding:0.7rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>" <?= (($_POST['department'] ?? '') === $dept) ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:4px;">Current Designation / Role</label>
                    <input type="text" name="current_position" class="form-control" placeholder="e.g. Site Engineer / Lecturer / Student" value="<?= htmlspecialchars($_POST['current_position'] ?? '') ?>" style="width:100%; padding:0.7rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:4px;">Company / Organization / College</label>
                    <input type="text" name="company" class="form-control" placeholder="e.g. L&T / College / Higher Studies" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>" style="width:100%; padding:0.7rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:4px;">Email Address *</label>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" style="width:100%; padding:0.7rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:4px;">Mobile Number *</label>
                    <input type="tel" name="mobile" pattern="[0-9]{10}" class="form-control" placeholder="10-digit number" required value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>" style="width:100%; padding:0.7rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:4px;">LinkedIn Profile URL (Optional)</label>
                <input type="url" name="linkedin_url" class="form-control" placeholder="https://linkedin.com/in/yourprofile" value="<?= htmlspecialchars($_POST['linkedin_url'] ?? '') ?>" style="width:100%; padding:0.7rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
            </div>

            <div class="form-row">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:4px;">Password *</label>
                    <input type="password" name="password" id="pwd" class="form-control" placeholder="Min 8 chars (A-Z, a-z, 0-9, @#$)" minlength="8" required style="width:100%; padding:0.7rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
                    <small style="color:#64748b; font-size:0.75rem;">Must include uppercase, lowercase, number & symbol.</small>
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:4px;">Confirm Password *</label>
                    <input type="password" name="confirm_password" id="cpwd" class="form-control" placeholder="Re-enter password" minlength="8" required style="width:100%; padding:0.7rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
                </div>
            </div>

            <!-- Voice Captcha Component -->
            <div style="margin-bottom:1.5rem; background:#f8fafc; padding:1.25rem; border-radius:12px; border:1px solid #e2e8f0;">
                <label style="font-weight:600; font-size:0.85rem; color:#334155; display:block; margin-bottom:8px;">Security Captcha Verification *</label>
                <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
                    <div class="captcha-box">
                        <span class="captcha-text" id="captchaVal"><?= $captcha_code ?></span>
                    </div>
                    <button type="button" onclick="speakCaptcha()" class="btn btn-sm btn-outline" style="border-color:#1b365d; color:#1b365d;" title="Listen to Captcha Code">
                        <i class="fas fa-volume-up"></i> Listen
                    </button>
                    <button type="button" onclick="window.location.reload()" class="btn btn-sm btn-outline" title="Refresh Captcha">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <input type="text" name="captcha_input" class="form-control" placeholder="Enter 5-character Captcha shown above" required style="width:100%; padding:0.65rem 1rem; border:1.5px solid #e2e8f0; border-radius:10px;">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.85rem; font-size:1rem; font-weight:700; border-radius:10px; background:linear-gradient(135deg, #1b365d 0%, #0d233a 100%); border:none; color:white;"><i class="fas fa-user-check"></i> Register as Alumni</button>
            
            <p style="text-align:center; margin-top:1rem; color:#64748b; font-size:0.9rem;">
                Already registered? <a href="login.php" style="color:#1b365d; font-weight:700;">Login here →</a>
            </p>
        </form>
    </div>
</div>

<script>
function speakCaptcha() {
    const code = document.getElementById('captchaVal').textContent;
    if ('speechSynthesis' in window) {
        const textToSpeech = new SpeechSynthesisUtterance("Security Captcha Code is: " + code.split('').join(' '));
        textToSpeech.rate = 0.8;
        window.speechSynthesis.speak(textToSpeech);
    } else {
        alert("Text-to-Speech is not supported in your browser.");
    }
}

document.getElementById('alumniForm').addEventListener('submit', function(e) {
    const p1 = document.getElementById('pwd').value;
    const p2 = document.getElementById('cpwd').value;
    const pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/;

    if (p1 !== p2) {
        e.preventDefault();
        alert('Passwords do not match!');
        return;
    }
    if (!pattern.test(p1)) {
        e.preventDefault();
        alert('Password must be at least 8 characters long and contain at least 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special symbol (@$!%*?&#).');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
