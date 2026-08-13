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

        $dbVolunteers = (int)($pdo->query("SELECT COUNT(*) FROM users WHERE role='volunteer'")->fetchColumn() ?: 0);
        $dbEvents     = (int)($pdo->query("SELECT COUNT(*) FROM events")->fetchColumn() ?: 0);
        $dbAlumni     = (int)($pdo->query("SELECT COUNT(*) FROM users WHERE role='alumni'")->fetchColumn() ?: 0);

        $stat1_val = !empty($settings['stat_1_val']) ? $settings['stat_1_val'] : ($dbVolunteers > 0 ? $dbVolunteers : '250+');
        $stat2_val = !empty($settings['stat_2_val']) ? $settings['stat_2_val'] : ($dbEvents > 0 ? $dbEvents : '48+');
        $stat3_val = !empty($settings['stat_3_val']) ? $settings['stat_3_val'] : '75+';
        $stat4_val = !empty($settings['stat_4_val']) ? $settings['stat_4_val'] : ($dbAlumni > 0 ? $dbAlumni . '+' : '500+');

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

/* Stats Floating Ribbon */
.stats-ribbon {
    position: relative;
    z-index: 20;
    margin-top: -60px;
    padding: 0 5%;
}

.stats-card-row {
    max-width: 1200px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 15px 45px rgba(13, 35, 58, 0.1);
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.stat-box {
    padding: 2.2rem 1.5rem;
    text-align: center;
    border-right: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.stat-box:last-child { border-right: none; }
.stat-box:hover { background: #f8fafc; transform: translateY(-3px); }

.stat-number {
    font-family: 'Outfit', sans-serif;
    font-size: 3rem;
    font-weight: 800;
    color: #1b365d;
    margin-bottom: 0.2rem;
    line-height: 1;
}

.stat-label {
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
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
    .stats-card-row { grid-template-columns: repeat(2, 1fr); }
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

<!-- ===== STATS RIBBON ===== -->
<div class="stats-ribbon">
    <div class="stats-card-row" data-aos="fade-up">
        <div class="stat-box">
            <div class="stat-number counter"><?= htmlspecialchars($stat1_val) ?></div>
            <div class="stat-label"><?= htmlspecialchars($settings['stat_1_label']) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-number counter"><?= htmlspecialchars($stat2_val) ?></div>
            <div class="stat-label"><?= htmlspecialchars($settings['stat_2_label']) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-number counter"><?= htmlspecialchars($stat3_val) ?></div>
            <div class="stat-label"><?= htmlspecialchars($settings['stat_3_label']) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-number counter"><?= htmlspecialchars($stat4_val) ?></div>
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
            <div class="faq-answer">All regular diploma engineering students of 1st, 2nd, and 3rd year across all departments (Shift I, Shift II, Sandwich, Part-Time) are eligible to register.</div>
        </div>

        <div class="faq-item">
            <div class="faq-question">What is the 240 Hours requirement for NSS Certificate? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">To earn the official NSS Certificate, a volunteer must complete at least 240 hours of verified service over 2 years (including regular camps, blood donation, parades, and special camps).</div>
        </div>

        <div class="faq-item">
            <div class="faq-question">How is attendance and NSS hours credited? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">Attendance is credited solely by the official NSS Admin / Programme Officer after verifying volunteer participation in camps, parade practices, and events.</div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Are alumni allowed to join the NSS Network? <i class="fas fa-chevron-down"></i></div>
            <div class="faq-answer">Yes! Past NSS volunteers of TNGPTC can register under the Alumni portal to mentor current students and participate in major college camps.</div>
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

    // Number Counter Animation
    const counters = document.querySelectorAll('.counter');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const text = el.textContent;
                const target = parseInt(text.replace(/[^0-9]/g, ''));
                if (!isNaN(target) && target > 0) {
                    anime({
                        targets: { val: 0 },
                        val: target,
                        round: 1,
                        duration: 2000,
                        easing: 'easeOutExpo',
                        update: function(a) {
                            el.textContent = Math.round(a.animations[0].currentValue) + (text.includes('+') ? '+' : '');
                        }
                    });
                }
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.4 });
    counters.forEach(c => observer.observe(c));

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
