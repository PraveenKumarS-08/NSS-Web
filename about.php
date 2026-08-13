<?php
$pageTitle = 'About NSS Unit | TNGPTC Madurai';
require_once __DIR__ . '/includes/header.php';
?>

<style>
.about-hero {
    padding: 100px 5% 50px;
    text-align: center;
    background: linear-gradient(135deg, #0d233a 0%, #1b365d 100%);
    color: white;
    border-bottom: 3px solid #f4a11d;
}

.about-hero h1 {
    font-size: 3rem;
    font-family: 'Outfit', sans-serif;
    color: white;
    margin-bottom: 0.5rem;
}

.about-hero p {
    color: #f4a11d;
    font-size: 1.15rem;
    font-weight: 600;
}

.about-section {
    padding: 80px 5%;
    max-width: 1300px;
    margin: 0 auto;
}

.about-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    margin-bottom: 90px;
}

.about-split h2 {
    font-size: 2.3rem;
    color: #1b365d;
    margin-bottom: 1.25rem;
    font-family: 'Outfit', sans-serif;
}

.about-split p {
    color: #475569;
    font-size: 1.05rem;
    line-height: 1.8;
    margin-bottom: 1.25rem;
}

.emblem-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 2.5rem;
    text-align: center;
    box-shadow: 0 10px 40px rgba(27, 54, 93, 0.08);
}

.emblem-card img {
    width: 140px;
    height: 140px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    border: 4px solid #f4a11d;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.emblem-card h3 {
    color: #1b365d;
    margin-bottom: 0.5rem;
}

.emblem-card p {
    color: #64748b;
    font-size: 0.95rem;
    line-height: 1.6;
}

/* Objectives Grid */
.objectives-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 90px;
}

.objective-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.75rem;
    border-left: 4px solid #1b365d;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    transition: all 0.3s ease;
}

.objective-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(27, 54, 93, 0.1);
    border-left-color: #f4a11d;
}

.objective-item i {
    color: #f4a11d;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.objective-item h4 {
    color: #1b365d;
    font-size: 1.15rem;
    margin-bottom: 0.5rem;
}

.objective-item p {
    color: #64748b;
    font-size: 0.92rem;
    line-height: 1.6;
    margin: 0;
}

/* Officers Grid */
.officers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}

.officer-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 2.2rem 1.5rem;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    transition: all 0.3s ease;
}

.officer-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(27,54,93,0.12);
    border-color: #1b365d;
}

.officer-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: #1b365d;
    color: #f4a11d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto 1.25rem;
    border: 3px solid #f4a11d;
}

.officer-card h3 {
    color: #1b365d;
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}

.officer-card .role-badge {
    display: inline-block;
    background: #f0f4f8;
    color: #1b365d;
    font-weight: 700;
    font-size: 0.82rem;
    padding: 4px 12px;
    border-radius: 20px;
    margin-bottom: 1rem;
    border: 1px solid rgba(27,54,93,0.15);
}

.officer-card p {
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.6;
    margin: 0;
}

@media (max-width: 900px) {
    .about-split { grid-template-columns: 1fr; }
}
</style>

<div class="about-hero">
    <h1 data-aos="fade-down">About NSS TNGPTC Madurai</h1>
    <p data-aos="fade-up">"Not Me, But You" • "எனக்கல்ல, உனக்கே"</p>
</div>

<section class="about-section">
    <!-- What is NSS -->
    <div class="about-split">
        <div class="about-text" data-aos="fade-right">
            <h2>National Service Scheme Overview</h2>
            <p>The National Service Scheme (NSS) is an Indian government-sponsored public service program conducted by the Ministry of Youth Affairs and Sports. Launched in 1969 on the Centenary year of Mahatma Gandhi, it aims to develop students' personality through community service.</p>
            <p>At Tamil Nadu Government Polytechnic College, Madurai-11, NSS is an integral part of student life. We empower diploma engineering students across all branches to address rural development, healthcare access, literacy, and environmental sustainability.</p>
        </div>
        <div class="emblem-card" data-aos="fade-left">
            <?= function_exists('getNssLogoImg') ? getNssLogoImg(140, '') : '<img src="assets/images/nss-logo.png" style="width:140px;height:140px;border-radius:50%;border:4px solid #f4a11d;">' ?>
            <h3>The NSS Badge & Wheel</h3>
            <p>The NSS symbol is based on the giant Rath Wheel of the famous Konark Sun Temple. It signifies continuous movement, creation, and preservation — symbolizing the path to national progress.</p>
        </div>
    </div>

    <!-- Objectives -->
    <div style="text-align:center; margin-bottom:40px;" data-aos="fade-up">
        <h2 style="font-family:'Outfit',sans-serif; color:#1b365d; font-size:2.3rem; margin-bottom:0.5rem;">Primary NSS Objectives</h2>
        <div style="width:60px; height:4px; background:#f4a11d; margin:0 auto 1rem; border-radius:2px;"></div>
        <p style="color:#64748b; font-size:1.05rem;">Core principles guiding our volunteer activities at TNGPTC Madurai</p>
    </div>

    <div class="objectives-grid">
        <div class="objective-item" data-aos="fade-up" data-aos-delay="100">
            <i class="fas fa-search-location"></i>
            <h4>Community Awareness</h4>
            <p>Understand the community in which volunteers serve and identify local socio-economic issues.</p>
        </div>

        <div class="objective-item" data-aos="fade-up" data-aos-delay="200">
            <i class="fas fa-hand-holding-heart"></i>
            <h4>Social & Civic Responsibility</h4>
            <p>Develop a deep sense of social duty and active commitment towards underprivileged communities.</p>
        </div>

        <div class="objective-item" data-aos="fade-up" data-aos-delay="300">
            <i class="fas fa-users-cog"></i>
            <h4>Leadership & Problem Solving</h4>
            <p>Apply technical diploma knowledge to solve real-life village infrastructure and health challenges.</p>
        </div>

        <div class="objective-item" data-aos="fade-up" data-aos-delay="400">
            <i class="fas fa-hands-wash"></i>
            <h4>Swachh Bharat & Environment</h4>
            <p>Drive clean campus initiatives, tree planting, and zero-plastic rural campaigns.</p>
        </div>

        <div class="objective-item" data-aos="fade-up" data-aos-delay="500">
            <i class="fas fa-first-aid"></i>
            <h4>Emergency & Health Service</h4>
            <p>Mobilize blood donation camps, disaster relief efforts, and health screening drives.</p>
        </div>

        <div class="objective-item" data-aos="fade-up" data-aos-delay="600">
            <i class="fas fa-certificate"></i>
            <h4>240 Hours Certification</h4>
            <p>Recognizing dedicated volunteers who complete 240 hours of verified service over 2 academic years.</p>
        </div>
    </div>

    <!-- NSS Officers Leadership -->
    <div style="text-align:center; margin-bottom:40px;" data-aos="fade-up">
        <h2 style="font-family:'Outfit',sans-serif; color:#1b365d; font-size:2.3rem; margin-bottom:0.5rem;">Unit Leadership & Guidance</h2>
        <div style="width:60px; height:4px; background:#f4a11d; margin:0 auto 1rem; border-radius:2px;"></div>
        <p style="color:#64748b; font-size:1.05rem;">Supervised by dedicated faculty programme officers and college administration</p>
    </div>

    <div class="officers-grid">
        <div class="officer-card" data-aos="fade-up" data-aos-delay="100">
            <div class="officer-avatar"><i class="fas fa-user-tie"></i></div>
            <h3>Principal / Patron</h3>
            <div class="role-badge">TNGPTC Madurai Administration</div>
            <p>Guiding overall NSS activities, camp approvals, and institution-wide community outreach programs.</p>
        </div>

        <div class="officer-card" data-aos="fade-up" data-aos-delay="200">
            <div class="officer-avatar"><i class="fas fa-user-shield"></i></div>
            <h3>Programme Officer — Unit I</h3>
            <div class="role-badge">Faculty Lead • Unit I</div>
            <p>Coordinating blood donation camps, health awareness rallies, and regular weekend volunteer service hours.</p>
        </div>

        <div class="officer-card" data-aos="fade-up" data-aos-delay="300">
            <div class="officer-avatar"><i class="fas fa-user-shield"></i></div>
            <h3>Programme Officer — Unit II</h3>
            <div class="role-badge">Faculty Lead • Unit II</div>
            <p>Managing village adoption special 7-day camps, parade drills, and green campus plantation drives.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
