<?php
$pageTitle = 'NSS Unit | Tamil Nadu Government Polytechnic College Madurai-11';
require_once __DIR__ . '/includes/header.php';

// Dynamic site settings & stats with auto-count fallbacks
$settings = [
    'stat_1_label' => 'ACTIVE VOLUNTEERS',
    'stat_1_val'   => '',
    'stat_2_label' => 'CAMPS & DRIVES',
    'stat_2_val'   => '',
    'stat_3_label' => 'YEARS OF SERVICE',
    'stat_3_val'   => '75+',
    'stat_4_label' => 'ALUMNI NETWORK',
    'stat_4_val'   => '500+'
];

$stat1_val = '250+';
$stat2_val = '48+';
$stat3_val = '75+';
$stat4_val = '500+';

$recentEvents = [];
$galleryImages = [];
$announcements = [];
$heroSlides = [];

try {
    if (isset($pdo)) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT)");
        $dbSettings = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($dbSettings as $k => $v) {
            $settings[$k] = $v;
        }

        $dbVolunteers = (int)($pdo->query("SELECT COUNT(*) FROM users WHERE role='volunteer' AND status='approved'")->fetchColumn() ?: 0);
        $dbEvents     = (int)($pdo->query("SELECT COUNT(*) FROM events")->fetchColumn() ?: 0);
        $dbAlumniUsers= (int)($pdo->query("SELECT COUNT(*) FROM users WHERE role='alumni' AND status='approved'")->fetchColumn() ?: 0);
        $dbAlumniTable= (int)($pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn() ?: 0);
        $dbAlumni     = max($dbAlumniUsers, $dbAlumniTable);

        $stat1_val = $dbVolunteers > 0 ? $dbVolunteers : '250+';
        $stat2_val = $dbEvents > 0 ? $dbEvents : '48+';
        $stat3_val = '75+';
        $stat4_val = $dbAlumni > 0 ? $dbAlumni : '500+';

        $recentEvents = $pdo->query("SELECT * FROM events ORDER BY event_date DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
        $galleryImages = $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
        $announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
        $heroSlides = $pdo->query("SELECT * FROM hero_slides ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { }

if (empty($recentEvents)) {
    $recentEvents = [
        ['id' => 1, 'title' => 'Blood Donation Camp 2026', 'category' => 'Health', 'event_date' => date('Y-m-d H:i:s', strtotime('+10 days')), 'location' => 'College Premises, Madurai-11', 'description' => 'Annual blood donation camp in collaboration with Government Rajaji Hospital, Madurai.', 'image' => 'assets/images/nss-logo.png'],
        ['id' => 2, 'title' => 'Mega Tree Plantation Drive', 'category' => 'Environment', 'event_date' => date('Y-m-d H:i:s', strtotime('+18 days')), 'location' => 'Campus & Bye-Pass Road', 'description' => 'Planting 500 indigenous saplings under Green India Clean India initiative.', 'image' => 'assets/images/nss-logo.png'],
        ['id' => 3, 'title' => 'Village Adoption Special Camp', 'category' => 'Community', 'event_date' => date('Y-m-d H:i:s', strtotime('+30 days')), 'location' => 'Alanganallur Village, Madurai', 'description' => 'Annual special camp focusing on village sanitation, literacy and free medical health checkup.', 'image' => 'assets/images/nss-logo.png']
    ];
}

// Fallback hero background slide images
if (empty($heroSlides)) {
    $heroSlides = [
        ['image_path' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=1600&auto=format&fit=crop&q=80', 'title' => 'Tree Plantation & Environmental Protection'],
        ['image_path' => 'https://images.unsplash.com/photo-1615461066841-6116e61058f4?w=1600&auto=format&fit=crop&q=80', 'title' => 'Blood Donation Drive'],
        ['image_path' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=1600&auto=format&fit=crop&q=80', 'title' => 'Youth Leadership & Social Service']
    ];
}
?>

<style>
/* ===== Custom Index Page Styles ===== */
.hero-swiper-container {
    position: relative;
    height: 92vh;
    overflow: hidden;
    color: white;
}

.hero-swiper-container .swiper-slide {
    position: relative;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-swiper-container .swiper-slide::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(13,35,58,0.75) 0%, rgba(27,54,93,0.85) 60%, rgba(13,35,58,0.95) 100%);
}

.hero-content-overlay {
    position: relative;
    z-index: 10;
    max-width: 920px;
    padding: 2.5rem 1.5rem;
    text-align: center;
    margin: 0 auto;
}

.hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(244, 161, 29, 0.5);
    padding: 0.5rem 1.4rem;
    border-radius: 30px;
    font-size: 0.92rem;
    color: #f4a11d;
    font-weight: 700;
    margin-bottom: 1.5rem;
    backdrop-filter: blur(10px);
}

.hero-title-main {
    font-family: 'Outfit', sans-serif;
    font-size: 4.2rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 0.5rem;
    letter-spacing: -1.5px;
    background: linear-gradient(135deg, #ffffff 0%, #f4a11d 50%, #ffffff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-motto-tamil {
    font-family: 'Outfit', sans-serif;
    font-size: 1.8rem;
    color: #f4a11d;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 0 2px 10px rgba(0,0,0,0.4);
}

.hero-desc {
    font-size: 1.2rem;
    color: rgba(255,255,255,0.9);
    margin-bottom: 2.5rem;
    line-height: 1.7;
    max-width: 780px;
    margin-left: auto;
    margin-right: auto;
}

.hero-buttons {
    display: flex;
    gap: 1.2rem;
    justify-content: center;
    flex-wrap: wrap;
}

.hero-buttons .btn-primary {
    background: linear-gradient(135deg, #f4a11d 0%, #d98200 100%);
    color: #0d233a !important;
    font-weight: 800;
    padding: 0.85rem 2.2rem;
    font-size: 1.05rem;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(244, 161, 29, 0.4);
}

.hero-buttons .btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(244, 161, 29, 0.55);
}

.hero-buttons .btn-outline {
    border: 2px solid rgba(255, 255, 255, 0.6);
    color: #ffffff !important;
    padding: 0.85rem 2.2rem;
    font-size: 1.05rem;
    border-radius: 12px;
}

.hero-buttons .btn-outline:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: #ffffff;
    transform: translateY(-3px);
}

/* Stats Floating Ribbon — Redesigned */
.stats-ribbon {
    position: relative;
    z-index: 20;
    margin-top: -70px;
    padding: 0 5%;
}

.stats-card-row {
    max-width: 1200px;
    margin: 0 auto;
    background: linear-gradient(135deg, #0d233a 0%, #1b365d 50%, #162e4f 100%);
    border-radius: 22px;
    box-shadow: 0 20px 60px rgba(13, 35, 58, 0.35), 0 0 0 1px rgba(244,161,29,0.15);
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    overflow: hidden;
    position: relative;
}

.stats-card-row::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(244,161,29,0.08) 0%, transparent 50%, rgba(244,161,29,0.05) 100%);
    pointer-events: none;
}

.stat-box {
    padding: 2.5rem 1.5rem 2rem;
    text-align: center;
    border-right: 1px solid rgba(255,255,255,0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
}

.stat-box:last-child { border-right: none; }

.stat-box:hover {
    background: rgba(244,161,29,0.08);
    transform: translateY(-4px);
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(244,161,29,0.12);
    border: 1px solid rgba(244,161,29,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.3rem;
    color: #f4a11d;
    transition: all 0.3s ease;
}

.stat-box:hover .stat-icon {
    background: rgba(244,161,29,0.2);
    transform: scale(1.1) rotate(-5deg);
    box-shadow: 0 4px 20px rgba(244,161,29,0.25);
}

.stat-number {
    font-family: 'Outfit', sans-serif;
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(180deg, #ffffff 30%, #f4a11d 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.35rem;
    line-height: 1;
}

.stat-label {
    color: rgba(255,255,255,0.6);
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
}

/* Philosophy Section */
.philosophy-section {
    padding: 100px 5% 80px;
    max-width: 1300px;
    margin: 0 auto;
}

.section-head {
    text-align: center;
    margin-bottom: 60px;
}

.section-head h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 2.5rem;
    color: #1b365d;
    margin-bottom: 0.75rem;
}

.section-head p {
    color: #64748b;
    font-size: 1.05rem;
    max-width: 650px;
    margin: 0 auto;
}

.gold-bar {
    width: 60px;
    height: 4px;
    background: #f4a11d;
    margin: 0.75rem auto 1rem;
    border-radius: 2px;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
}

.value-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 2rem;
    transition: all 0.35s ease;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    position: relative;
    overflow: hidden;
}

.value-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #1b365d, #f4a11d);
}

.value-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(27, 54, 93, 0.12);
    border-color: #1b365d;
}

.value-icon {
    width: 56px;
    height: 56px;
    background: rgba(27, 54, 93, 0.08);
    color: #1b365d;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1.25rem;
    transition: all 0.3s ease;
}

.value-card:hover .value-icon {
    background: #1b365d;
    color: #f4a11d;
    transform: scale(1.1);
}

.value-card h3 {
    font-size: 1.25rem;
    color: #1b365d;
    margin-bottom: 0.5rem;
}

.value-card p {
    color: #64748b;
    font-size: 0.92rem;
    line-height: 1.6;
    margin: 0;
}

/* FAQ Accordion */
.faq-section {
    background: #f8fafc;
    padding: 90px 5%;
    border-top: 1px solid #e2e8f0;
}

.faq-container {
    max-width: 850px;
    margin: 0 auto;
}

.faq-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-question {
    padding: 1.25rem 1.5rem;
    font-weight: 700;
    color: #1b365d;
    font-size: 1.05rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    user-select: none;
}

.faq-question i {
    color: #f4a11d;
    transition: transform 0.3s ease;
}

.faq-answer {
    padding: 0 1.5rem 1.25rem;
    color: #475569;
    font-size: 0.95rem;
    line-height: 1.7;
    display: none;
}

.faq-item.open .faq-answer {
    display: block;
}

.faq-item.open .faq-question i {
    transform: rotate(180deg);
}

@media (max-width: 900px) {
    .hero-title-main { font-size: 2.8rem; }
    .hero-motto-tamil { font-size: 1.4rem; }
    .hero-desc { font-size: 1rem; padding: 0 0.5rem; }
    .hero-swiper-container { height: 85vh; }
    .stats-card-row { grid-template-columns: repeat(2, 1fr); }
    .stat-box { border-bottom: 1px solid rgba(255,255,255,0.08); }
    .stat-box:nth-child(2n) { border-right: none; }
    .stat-number { font-size: 2.4rem; }
    .philosophy-section { padding: 60px 4% 40px; }
    .section-head h2 { font-size: 2rem; }
    .faq-section { padding: 60px 4%; }
}
@media (max-width: 500px) {
    .hero-swiper-container { height: 100vh; }
    .hero-title-main { font-size: 2.2rem; letter-spacing: -0.5px; }
    .hero-motto-tamil { font-size: 1.15rem; }
    .hero-desc { font-size: 0.92rem; line-height: 1.6; }
    .hero-pill { font-size: 0.78rem; padding: 0.4rem 1rem; }
    .hero-buttons { flex-direction: column; gap: 0.75rem; }
    .hero-buttons .btn-primary, .hero-buttons .btn-outline {
        width: 100%; text-align: center; padding: 0.75rem 1.5rem; font-size: 0.95rem;
    }
    .stats-ribbon { margin-top: -50px; padding: 0 3%; }
    .stats-card-row { grid-template-columns: 1fr 1fr; border-radius: 16px; }
    .stat-box { padding: 1.5rem 1rem; }
    .stat-number { font-size: 2rem; }
    .stat-icon { width: 42px; height: 42px; font-size: 1.1rem; }
    .stat-label { font-size: 0.72rem; letter-spacing: 0.8px; }
    .philosophy-section { padding: 40px 4% 30px; }
    .values-grid { grid-template-columns: 1fr !important; gap: 16px; }
    .value-card { padding: 1.5rem; }
    .section-head h2 { font-size: 1.5rem; }
    .section-head p { font-size: 0.88rem; }
    .faq-section { padding: 40px 4%; }
    .faq-question { font-size: 0.95rem; padding: 1rem 1.25rem; }
    .faq-answer { font-size: 0.88rem; padding: 0 1.25rem 1rem; }
    .hero-content-overlay { padding: 1.5rem 1rem; }
}
</style>

<!-- ===== AUTO-SCROLLING HERO SLIDER ===== -->
<div class="hero-swiper-container swiper myHeroSwiper">
    <div class="swiper-wrapper">
        <?php foreach ($heroSlides as $slide): ?>
        <div class="swiper-slide" style="background-image: url('<?= htmlspecialchars($slide['image_path']) ?>');">
            <div class="hero-content-overlay" data-aos="zoom-in">
                <div class="hero-pill">
                    <i class="fas fa-award"></i> National Service Scheme • TNGPTC Madurai-11
                </div>
                <h1 class="hero-title-main">Not Me, But You</h1>
                <div class="hero-motto-tamil">எனக்கல்ல, உனக்கே</div>
                <p class="hero-desc">
                    Welcome to the official NSS portal of Tamil Nadu Government Polytechnic College, Madurai.
                    Building youth leadership, social responsibility, and national unity through selfless service.
                </p>
                <div class="hero-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="events.php" class="btn btn-primary"><i class="fas fa-calendar-alt"></i> Upcoming Events</a>
                        <?php
                        $dashLink = 'dashboard/volunteer.php';
                        $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'volunteer';
                        if ($role === 'admin') $dashLink = 'dashboard/admin.php';
                        elseif ($role === 'alumni') $dashLink = 'dashboard/alumni.php';
                        ?>
                        <a href="<?= $dashLink ?>" class="btn btn-outline"><i class="fas fa-gauge"></i> My Dashboard</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Join as Volunteer</a>
                        <a href="events.php" class="btn btn-outline"><i class="fas fa-calendar-alt"></i> Upcoming Events</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="swiper-pagination"></div>
</div>

<!-- ===== STATS RIBBON (Redesigned) ===== -->
<div class="stats-ribbon">
    <div class="stats-card-row" data-aos="fade-up">
        <div class="stat-box">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number nss-counter" data-target="<?= htmlspecialchars($stat1_val) ?>">0</div>
            <div class="stat-label"><?= htmlspecialchars($settings['stat_1_label']) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-icon"><i class="fas fa-campground"></i></div>
            <div class="stat-number nss-counter" data-target="<?= htmlspecialchars($stat2_val) ?>">0</div>
            <div class="stat-label"><?= htmlspecialchars($settings['stat_2_label']) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-icon"><i class="fas fa-award"></i></div>
            <div class="stat-number nss-counter" data-target="<?= htmlspecialchars($stat3_val) ?>">0</div>
            <div class="stat-label"><?= htmlspecialchars($settings['stat_3_label']) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-number nss-counter" data-target="<?= htmlspecialchars($stat4_val) ?>">0</div>
            <div class="stat-label"><?= htmlspecialchars($settings['stat_4_label']) ?></div>
        </div>
    </div>
</div>

<!-- ===== CORE SERVICES & PILLARS ===== -->
<section class="philosophy-section">
    <div class="section-head" data-aos="fade-up">
        <h2>Our Core Pillars of Service</h2>
        <div class="gold-bar"></div>
        <p>Guided by the NSS motto <strong>"எனக்கல்ல, உனக்கே"</strong>, our volunteers engage in continuous campus & community outreach.</p>
    </div>

    <div class="values-grid">
        <div class="value-card" data-aos="fade-up" data-aos-delay="100">
            <div class="value-icon"><i class="fas fa-graduation-cap"></i></div>
            <h3>College Admission Assistance</h3>
            <p>Guiding fresh diploma aspirants and parents during college admission counselling, registration help desks, and document guidance.</p>
        </div>

        <div class="value-card" data-aos="fade-up" data-aos-delay="200">
            <div class="value-icon"><i class="fas fa-tasks"></i></div>
            <h3>Event Organization</h3>
            <p>Managing flagship institutional functions, polytechnic sports meets, technical symposiums, and disaster response drills.</p>
        </div>

        <div class="value-card" data-aos="fade-up" data-aos-delay="300">
            <div class="value-icon"><i class="fas fa-heartbeat"></i></div>
            <h3>Health & Blood Camps</h3>
            <p>Annual blood donation drives, health screening, and awareness rallies saving thousands of lives across Madurai district.</p>
        </div>

        <div class="value-card" data-aos="fade-up" data-aos-delay="400">
            <div class="value-icon"><i class="fas fa-seedling"></i></div>
            <h3>Green India Mission</h3>
            <p>Tree sapling plantation drives, plastic elimination campaigns, and Swachh Bharat cleanliness programs.</p>
        </div>

        <div class="value-card" data-aos="fade-up" data-aos-delay="500">
            <div class="value-icon"><i class="fas fa-home"></i></div>
            <h3>Village Adoption Camps</h3>
            <p>Rural special camps bringing literacy, digital awareness, medical camps, and civic infrastructure support to adopted villages.</p>
        </div>

        <div class="value-card" data-aos="fade-up" data-aos-delay="600">
            <div class="value-icon"><i class="fas fa-user-shield"></i></div>
            <h3>Youth Leadership</h3>
            <p>Fostering discipline, public speaking, parade drills, and leadership among polytechnic diploma engineers.</p>
        </div>
    </div>
</section>

<!-- ===== FAQ ACCORDION ===== -->
<section class="faq-section">
    <div class="section-head" data-aos="fade-up">
        <h2>Frequently Asked Questions</h2>
        <div class="gold-bar"></div>
        <p>Clear answers to common questions about NSS registration, attendance, and certificates.</p>
    </div>

    <div class="faq-container" data-aos="fade-up">
        <div class="faq-item">
            <div class="faq-question">Who is eligible to join NSS at TNGPTC Madurai? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">All regular/shift diploma engineering students of 1st and 2nd year across all departments (Civil, Mechanical, EEE, ECE, Computer, Plastic, Polymer, Robotics, Printing, etc. - Shift I, Shift II, Sandwich, and Part-Time) are eligible to register as NSS volunteers.</div>
        </div>

        <div class="faq-item">
            <div class="faq-question">How do I register as a student volunteer on this portal? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">Click on the <strong>"Join as Volunteer"</strong> button in the navigation bar, fill in your diploma register number, department, academic year, blood group, and mobile details. After submission, your registration request will be verified and approved by the<b> NSS Admin.</b></div>
        </div>


        <div class="faq-item">
            <div class="faq-question">How are NSS attendance and service hours credited to my profile? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">Service hours are logged and credited directly by the NSS Programme Officers (Kumar R Sir & Mahalakshmi G Mam or Your Seniors) after verifying your active presence in parade drills, blood donation camps, and outreach events. You can log into your Student Volunteer Dashboard to track your real time hours progress.</div>
        </div>


        <div class="faq-item">
            <div class="faq-question">How can alumni register and stay connected with the NSS Network? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">Former NSS volunteers of TNGPTC Madurai can register via the Alumni Registration portal. Once approved, alumni can mentor current diploma volunteers, attend special events, contribute as guest speakers, and join annual NSS Alumni meets.</div>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Swiper Auto-Scrolling Hero Slider Init
    if (typeof Swiper !== 'undefined') {
        new Swiper('.myHeroSwiper', {
            loop: true,
            autoplay: {
                delay: 4500,
                disableOnInteraction: false,
            },
            effect: 'fade',
            fadeEffect: { crossFade: true },
            speed: 1200,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
        });
    }

    // Counter animation moved to separate script block after footer.php

    // FAQ Accordion
    document.querySelectorAll('.faq-question').forEach(q => {
        q.addEventListener('click', () => {
            const item = q.parentElement;
            item.classList.toggle('open');
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Counter Animation: runs AFTER footer.php so anime.js is guaranteed loaded -->
<script>
(function() {
    var counters = document.querySelectorAll('.nss-counter');
    if (!counters.length) return;

    function animateCounter(el) {
        var raw = el.getAttribute('data-target') || '0';
        var target = parseInt(raw.replace(/[^0-9]/g, ''), 10);
        var hasPlus = raw.indexOf('+') !== -1;
        if (isNaN(target) || target <= 0) {
            el.textContent = raw;
            return;
        }

        if (typeof anime !== 'undefined') {
            var obj = { val: 0 };
            anime({
                targets: obj,
                val: target,
                round: 1,
                duration: 2200,
                easing: 'easeOutExpo',
                update: function() {
                    el.textContent = Math.round(obj.val) + (hasPlus ? '+' : '');
                }
            });
        } else {
            // Pure JS easeOut fallback
            var duration = 2000;
            var startTime = null;
            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.round(eased * target) + (hasPlus ? '+' : '');
                if (progress < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        counters.forEach(function(c) { observer.observe(c); });
    } else {
        // Fallback for old browsers
        counters.forEach(function(c) { animateCounter(c); });
    }
})();
</script>
