<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('admin');

$current_admin_id = $_SESSION['user_id'] ?? 0;
$msg = '';
$error = '';

// Handle Password Change & Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_self') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';

        if (empty($name) || empty($email)) {
            $error = "Name and email cannot be empty.";
        } else {
            try {
                if (!empty($new_password)) {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = 'admin'");
                    $stmt->execute([$name, $email, $new_password, $current_admin_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'admin'");
                    $stmt->execute([$name, $email, $current_admin_id]);
                }
                $_SESSION['name'] = $name;
                $msg = "Your admin profile & password updated successfully!";
            } catch (Exception $e) {
                $error = "Failed to update profile: " . $e->getMessage();
            }
        }
    } elseif ($action === 'manage_admin_account') {
        $target_id = (int)($_POST['target_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = "Name, Email, and Password are required.";
        } else {
            try {
                if ($target_id > 0) {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = 'admin'");
                    $stmt->execute([$name, $email, $password, $target_id]);
                    $msg = "Admin account details updated successfully!";
                } else {
                    // Check duplicate
                    $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $chk->execute([$email]);
                    if ($chk->fetch()) {
                        $error = "An account with this email already exists.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'approved')");
                        $stmt->execute([$name, $email, $password]);
                        $msg = "New Admin account created successfully!";
                    }
                }
            } catch (Exception $e) {
                $error = "Error updating admin account: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_admin_account') {
        $target_id = (int)($_POST['target_id'] ?? 0);
        if ($target_id === $current_admin_id) {
            $error = "You cannot delete your own active session admin account.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$target_id]);
            $msg = "Admin account removed successfully.";
        }
    }
}

// Fetch current admin profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$current_admin_id]);
$current_admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all 5 admin accounts
$all_admins = $pdo->query("SELECT * FROM users WHERE role = 'admin' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin Profile & Password Reset | NSS Admin';
require_once '../includes/header.php';
?>

<style>
.admin-profile-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 1.5rem;
}
@media (max-width: 992px) {
    .admin-profile-grid { grid-template-columns: 1fr; }
}
.pass-field-wrap {
    position: relative;
}
.pass-field-wrap input {
    padding-right: 42px;
}
.pass-toggle-btn {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: #64748b; cursor: pointer; font-size: 1rem;
}
.pass-toggle-btn:hover { color: #f4a11d; }
</style>

<div class="dashboard-wrapper">
    <?php include 'includes/admin-sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="topbar glass-panel">
            <div class="d-flex align-items-center gap-3">
                <h2><i class="fas fa-user-shield text-primary"></i> Admin Accounts & Self Password Reset</h2>
            </div>
            <span class="badge status-approved" style="font-size:0.85rem; padding:6px 14px;"><i class="fas fa-lock"></i> Logged in as: <?= htmlspecialchars($current_admin['name'] ?? 'Admin') ?></span>
        </header>

        <?php if ($msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="admin-profile-grid">
            <!-- Left Card: Reset My Own Password & Profile -->
            <div class="dashboard-card glass-panel">
                <h3><i class="fas fa-key text-accent"></i> Reset My Password & Profile</h3>
                <p style="color:#64748b; font-size:0.88rem; margin-bottom:1.25rem;">
                    Update your display name, login email, or change your account password.
                </p>

                <form method="POST">
                    <input type="hidden" name="action" value="update_self">

                    <div class="form-group mb-3">
                        <label style="font-weight:600; font-size:0.85rem;">Display Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($current_admin['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-weight:600; font-size:0.85rem;">Login Email Address *</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($current_admin['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group mb-4">
                        <label style="font-weight:600; font-size:0.85rem;">New Password <small class="text-muted">(Leave blank to keep unchanged)</small></label>
                        <div class="pass-field-wrap">
                            <input type="password" name="new_password" id="selfPassword" class="form-control" placeholder="Enter new password...">
                            <button type="button" class="pass-toggle-btn" onclick="togglePassVisibility('selfPassword', this)"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-save"></i> Save Changes & Update Password</button>
                </form>
            </div>

            <!-- Right Card: All Admin Accounts Directory (5 Admins Access) -->
            <div class="dashboard-card glass-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 style="margin:0;"><i class="fas fa-users-gear text-primary"></i> Admin Access Accounts (<?= count($all_admins) ?>)</h3>
                    <button type="button" class="btn btn-sm btn-outline" onclick="openAddAdminModal()"><i class="fas fa-plus"></i> Add New Admin</button>
                </div>
                <p style="color:#64748b; font-size:0.88rem; margin-bottom:1rem;">
                    Configured Admin Logins for Programme Officers, Web Admins, and Student Leads.
                </p>

                <div class="table-responsive">
                    <table class="table" style="font-size:0.88rem;">
                        <thead>
                            <tr>
                                <th>Admin Name</th>
                                <th>Login Email</th>
                                <th>Password</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_admins as $adm): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($adm['name']) ?></strong>
                                    <?php if ($adm['id'] == $current_admin_id): ?>
                                        <span class="badge bg-primary text-white" style="font-size:0.7rem; padding:2px 6px;">You</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($adm['email']) ?></td>
                                <td>
                                    <div class="pass-field-wrap" style="max-width:160px;">
                                        <input type="password" readonly value="<?= htmlspecialchars($adm['password']) ?>" class="form-control admin-row-pass" style="padding:4px 32px 4px 8px; font-size:0.82rem; height:32px;">
                                        <button type="button" class="pass-toggle-btn" style="right:6px;" onclick="toggleRowPass(this)"><i class="fas fa-eye"></i></button>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline text-accent" onclick='openEditAdminModal(<?= json_encode($adm) ?>)' title="Edit/Reset Password"><i class="fas fa-key"></i> Edit</button>
                                        <?php if ($adm['id'] != $current_admin_id && count($all_admins) > 1): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this admin account?');">
                                                <input type="hidden" name="action" value="delete_admin_account">
                                                <input type="hidden" name="target_id" value="<?= $adm['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline text-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal for Managing Admin Account -->
<div id="adminManageModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.7); backdrop-filter:blur(5px); z-index:2000; align-items:center; justify-content:center; padding:1rem;">
    <div style="background:white; border-radius:16px; width:100%; max-width:500px; padding:2rem; box-shadow:0 20px 50px rgba(0,0,0,0.3);" class="modal-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 id="admModalTitle" style="color:#1b365d; margin:0;"><i class="fas fa-user-plus text-accent"></i> Admin Account</h3>
            <button type="button" onclick="closeAdminManageModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#64748b;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="manage_admin_account">
            <input type="hidden" name="target_id" id="admTargetId" value="0">

            <div class="form-group mb-3">
                <label style="font-weight:600; font-size:0.85rem;">Admin Full Name *</label>
                <input type="text" name="name" id="admName" class="form-control" placeholder="e.g. Kumar R (Programme Officer)" required>
            </div>

            <div class="form-group mb-3">
                <label style="font-weight:600; font-size:0.85rem;">Login Email *</label>
                <input type="email" name="email" id="admEmail" class="form-control" placeholder="e.g. kumar.po@tngptcmadurai.com" required>
            </div>

            <div class="form-group mb-4">
                <label style="font-weight:600; font-size:0.85rem;">Account Password *</label>
                <div class="pass-field-wrap">
                    <input type="text" name="password" id="admPassword" class="form-control" placeholder="Password..." required>
                    <button type="button" class="pass-toggle-btn" onclick="togglePassVisibility('admPassword', this)"><i class="fas fa-eye-slash"></i></button>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline" onclick="closeAdminManageModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Admin Account</button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function toggleRowPass(btn) {
    const wrap = btn.closest('.pass-field-wrap');
    const input = wrap.querySelector('input');
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function openAddAdminModal() {
    document.getElementById('admModalTitle').innerHTML = '<i class="fas fa-user-plus text-accent"></i> Add New Admin Account';
    document.getElementById('admTargetId').value = 0;
    document.getElementById('admName').value = '';
    document.getElementById('admEmail').value = '';
    document.getElementById('admPassword').value = '';
    document.getElementById('adminManageModal').style.display = 'flex';
}

function openEditAdminModal(adm) {
    document.getElementById('admModalTitle').innerHTML = '<i class="fas fa-user-edit text-accent"></i> Edit Admin Account';
    document.getElementById('admTargetId').value = adm.id;
    document.getElementById('admName').value = adm.name || '';
    document.getElementById('admEmail').value = adm.email || '';
    document.getElementById('admPassword').value = adm.password || '';
    document.getElementById('adminManageModal').style.display = 'flex';
}

function closeAdminManageModal() {
    document.getElementById('adminManageModal').style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>
