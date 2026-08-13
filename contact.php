<?php
$pageTitle = 'Contact Us | TNGPTC Madurai';
require_once __DIR__ . '/includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = "Name, Email, and Message are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, is_read) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$name, $email, $subject, $message]);
            $success = "Thank you! Your message has been sent to the NSS Programme Officer. We will respond shortly.";
        } catch (PDOException $e) {
            $error = "Unable to send message. Please try again or call the office.";
        }
    }
}
?>

<div class="page-hero" style="padding: 90px 5% 40px; text-align: center; background: linear-gradient(135deg, #1b365d 0%, #0d233a 100%); border-bottom: 3px solid #f4a11d;">
    <h1 class="page-title" style="font-size:2.8rem; color:white; margin-bottom: 0.5rem;">Contact Us</h1>
    <p style="color:#f4a11d; font-size:1.1rem; font-weight:600;">Tamil Nadu Government Polytechnic College, Madurai-625011</p>
</div>

<section class="section" style="padding: 60px 5%; max-width: 1300px; margin: 0 auto;">
    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom:30px;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom:30px;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap: 40px; align-items:start;">
        <!-- Left: Contact Form -->
        <div class="glass-panel" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; padding:2.5rem; box-shadow:0 10px 30px rgba(0,0,0,0.04);" data-aos="fade-right">
            <h2 style="color:#1b365d; font-family:'Outfit',sans-serif; margin-bottom:0.5rem; font-size:1.8rem;">Send Us a Message</h2>
            <p style="color:#64748b; font-size:0.95rem; margin-bottom:1.75rem;">Have questions regarding NSS volunteer enrollment, camps, or blood donation? Drop us a note!</p>

            <form method="POST">
                <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:1.25rem;">
                    <div class="form-group">
                        <label style="font-weight:600; font-size:0.88rem; color:#334155; display:block; margin-bottom:6px;">Your Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Full name" required style="width:100%; padding:0.75rem 1rem; border:1px solid #cbd5e1; border-radius:10px; background:#f8fafc;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600; font-size:0.88rem; color:#334155; display:block; margin-bottom:6px;">Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com" required style="width:100%; padding:0.75rem 1rem; border:1px solid #cbd5e1; border-radius:10px; background:#f8fafc;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label style="font-weight:600; font-size:0.88rem; color:#334155; display:block; margin-bottom:6px;">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="e.g. Blood Camp Volunteer Inquiry / NSS Certificate" style="width:100%; padding:0.75rem 1rem; border:1px solid #cbd5e1; border-radius:10px; background:#f8fafc;">
                </div>

                <div class="form-group" style="margin-bottom:1.75rem;">
                    <label style="font-weight:600; font-size:0.88rem; color:#334155; display:block; margin-bottom:6px;">Message *</label>
                    <textarea name="message" class="form-control" rows="5" placeholder="Write your message details here..." required style="width:100%; padding:0.75rem 1rem; border:1px solid #cbd5e1; border-radius:10px; background:#f8fafc;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; padding:0.85rem; font-size:1.05rem; font-weight:700; border-radius:10px;"><i class="fas fa-paper-plane"></i> Send Message</button>
            </form>
        </div>

        <!-- Right: Official Info & Map -->
        <div style="display:flex; flex-direction:column; gap:24px;" data-aos="fade-left">
            <div class="glass-panel" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; padding:2rem; box-shadow:0 10px 30px rgba(0,0,0,0.04);">
                <h3 style="color:#1b365d; font-family:'Outfit',sans-serif; margin-bottom:1.25rem; font-size:1.35rem;"><i class="fas fa-building text-accent"></i> College Contact Information</h3>

                <div style="display:flex; gap:16px; margin-bottom:1.25rem; align-items:flex-start;">
                    <div style="width:40px; height:40px; border-radius:50%; background:rgba(27,54,93,0.08); color:#1b365d; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fas fa-map-marker-alt" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <strong style="color:#0f172a; display:block;">Institution Campus:</strong>
                        <span style="color:#475569; font-size:0.92rem; line-height:1.5;">Tamil Nadu Government Polytechnic College,<br>Bye-Pass Road, Madurai - 625011, Tamil Nadu.</span>
                        <a href="https://maps.app.goo.gl/4MPY8FHAcP7gcyeFA" target="_blank" style="display:inline-block; margin-top:4px; color:#f4a11d; font-weight:600; font-size:0.85rem;"><i class="fas fa-external-link-alt"></i> View on Google Maps</a>
                    </div>
                </div>

                <div style="display:flex; gap:16px; margin-bottom:1.25rem; align-items:flex-start;">
                    <div style="width:40px; height:40px; border-radius:50%; background:rgba(27,54,93,0.08); color:#1b365d; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fas fa-phone" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <strong style="color:#0f172a; display:block;">Phone Number:</strong>
                        <span style="color:#475569; font-size:0.92rem;">Office: (0452) 2370461</span>
                    </div>
                </div>

                <div style="display:flex; gap:16px; margin-bottom:1.25rem; align-items:flex-start;">
                    <div style="width:40px; height:40px; border-radius:50%; background:rgba(27,54,93,0.08); color:#1b365d; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fas fa-envelope" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <strong style="color:#0f172a; display:block;">Email Addresses:</strong>
                        <span style="color:#475569; font-size:0.92rem;">tngptcmadurai@gmail.com</span>
                    </div>
                </div>

                <div style="display:flex; gap:16px; align-items:flex-start;">
                    <div style="width:40px; height:40px; border-radius:50%; background:rgba(27,54,93,0.08); color:#1b365d; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fas fa-clock" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <strong style="color:#0f172a; display:block;">College Working Hours:</strong>
                        <span style="color:#475569; font-size:0.92rem;">Monday - Friday: 09:30 AM - 05:00 PM</span>
                    </div>
                </div>
            </div>

            <!-- Map Embed Card -->
            <div class="glass-panel" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.04);">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3930.138883652877!2d78.0965!3d9.9238!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3b00c58e0a35dbfd%3A0x6b726617a7e28b80!2sTamil%20Nadu%20Government%20Polytechnic%20College!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin" width="100%" height="240" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
