<?php
$isDashboard = (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false);
$root = $isDashboard ? '../' : '';
?>
    </main>

    <footer class="footer <?= $isDashboard ? 'dashboard-footer' : '' ?>">
        <div class="footer-container">
            <div class="footer-col col-brand">
                <div class="footer-logo">
                    <?= function_exists('getNssLogoImg') ? getNssLogoImg(48, $root) : '<img src="' . $root . 'assets/images/nss-logo.png" style="width:48px;height:48px;border-radius:50%;">' ?>
                    <div>
                        <h2 style="font-size: 1.25rem; margin:0; color:#ffffff;">TNGPTC Madurai-11</h2>
                        <p style="font-size: 0.8rem; color: #f4a11d; margin:0; font-weight:600;">National Service Scheme</p>
                    </div>
                </div>
                <p>Tamil Nadu Government Polytechnic College, Madurai-625011. Empowering youth through community service & leadership.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-x-twitter"></i></a>
                </div>
            </div>
            
            <div class="footer-col col-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?= $root ?>index.php"><i class="fas fa-angle-right"></i> Home</a></li>
                    <li><a href="<?= $root ?>about.php"><i class="fas fa-angle-right"></i> About Us</a></li>
                    <li><a href="<?= $root ?>events.php"><i class="fas fa-angle-right"></i> Events & Camps</a></li>
                    <li><a href="<?= $root ?>gallery.php"><i class="fas fa-angle-right"></i> Photo Gallery</a></li>
                    <li><a href="<?= $root ?>contact.php"><i class="fas fa-angle-right"></i> Contact Office</a></li>
                </ul>
            </div>
            
            <div class="footer-col col-contact">
                <h3>Contact Info</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> TNGPTC, T.P.K Road, Madurai - 625011</li>
                    <li><i class="fas fa-phone"></i> <a href="tel:+914522370461" title="Call NSS College Office" style="color:inherit; text-decoration:none;">(0452) 2370461</a></li>
                    <li><i class="fas fa-envelope"></i> <a href="mailto:tnptc116@gmail.com" title="Email NSS Office" style="color:inherit; text-decoration:none;">tnptc116@gmail.com</a></li>
                </ul>
            </div>
            
            <div class="footer-col col-motto">
                <h3>Our Motto</h3>
                <div class="motto-box">
                    <h4>"Not Me, But You"</h4>
                    <p style="font-size:0.85rem; color:#f4a11d; margin-top:4px;">எனக்கல்ல, உனக்கே</p>
                </div>
                <div class="badge-area" style="text-align:center;">
                    <span class="nss-badge">NSS Unit • TNGPTC</span>
                </div>
            </div>
        </div>

        <!-- Student Developers Attribution Strip -->
        <div style="background: rgba(255,255,255,0.03); border-top: 1px solid rgba(255,255,255,0.08); padding: 1rem 5%; text-align: center;">
            <div style="max-width: 1000px; margin: 0 auto; display: flex; align-items: center; justify-content: center; gap: 0.75rem; flex-wrap: wrap; color: #94a3b8; font-size: 0.88rem;">
                <i class="fas fa-code text-accent" style="font-size:1rem;"></i>
                <span>Website Designed & Developed by 3rd Year CSE Students (Batch 2024-2027):</span>
                <strong style="color: #ffffff; background: rgba(244,161,29,0.15); padding: 4px 14px; border-radius: 20px; border: 1px solid rgba(244,161,29,0.3);">
                    <i class="fas fa-user-graduate text-accent"></i> <a href="https://linkedin.com/in/santosh-nagendran" target="_blank" rel="noopener noreferrer" style="color:#ffffff; text-decoration:none;" onmouseover="this.style.color='#f4a11d'" onmouseout="this.style.color='#ffffff'">Santosh N</a> & Praveen Kumar S
                </strong>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> NSS Unit TNGPTC Madurai-11. All Rights Reserved. Designed for Community Empowerment.</p>
        </div>
    </footer>

    <style>
        .footer { background: linear-gradient(180deg, #0b1120 0%, #0a0f1a 100%); color: #cbd5e1; padding-top: 3.5rem; border-top: 3px solid #f4a11d; }
        .footer.dashboard-footer { margin-left: 260px; }
        .footer-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 2.5rem; max-width: 1400px; margin: 0 auto; padding: 0 5%; }
        .footer-col h3 { color: #f4a11d; margin-bottom: 1.25rem; font-size: 1.15rem; font-family: 'Outfit', sans-serif; font-weight: 700; }
        .footer-logo { display: flex; align-items: center; gap: 0.85rem; margin-bottom: 1rem; }
        .footer-col p { color: #94a3b8; line-height: 1.6; font-size: 0.9rem; }
        .social-links { display: flex; gap: 0.85rem; margin-top: 1.25rem; }
        .social-links a { width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.06); display: flex; align-items: center; justify-content: center; color: #cbd5e1; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.08); }
        .social-links a:hover { background: #1b365d; color: #f4a11d; transform: translateY(-3px); box-shadow: 0 4px 15px rgba(27,54,93,0.4); }
        .col-links ul, .col-contact ul { list-style: none; padding: 0; margin: 0; }
        .col-links ul li, .col-contact ul li { margin-bottom: 0.75rem; color: #94a3b8; font-size: 0.9rem; display: flex; align-items: center; gap: 0.6rem; }
        .col-links a { color: #94a3b8; text-decoration: none; transition: all 0.25s ease; }
        .col-links a:hover { color: #f4a11d; padding-left: 4px; }
        .col-contact i { color: #f4a11d; width: 18px; }
        .motto-box { background: rgba(27, 54, 93, 0.5); border: 1px solid rgba(244, 161, 29, 0.3); padding: 1rem; border-radius: 10px; text-align: center; margin-bottom: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .motto-box h4 { margin: 0; color: #ffffff; font-size: 1.15rem; font-family: 'Outfit', sans-serif; }
        .nss-badge { display: inline-block; background: #b71c1c; color: white; padding: 0.4rem 1.1rem; border-radius: 20px; font-weight: 700; font-size: 0.82rem; border: 1px solid #f4a11d; }
        .footer-bottom { text-align: center; padding: 1.25rem; margin-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.06); color: #64748b; font-size: 0.85rem; }
        @media (max-width: 992px) { .footer.dashboard-footer { margin-left: 0; } }
        @media (max-width: 768px) {
            .footer-container { grid-template-columns: 1fr 1fr; gap: 1.5rem; padding: 0 4%; }
            .footer { padding-top: 2.5rem; }
        }
        @media (max-width: 480px) {
            .footer-container { grid-template-columns: 1fr; gap: 1.25rem; }
            .footer-col h3 { font-size: 1rem; margin-bottom: 0.75rem; }
            .footer-col p { font-size: 0.85rem; }
            .footer-bottom { font-size: 0.78rem; padding: 1rem; }
            .footer-logo h2 { font-size: 1.05rem !important; }
        }
    </style>

    <!-- JS Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Main JS -->
    <script src="<?= $root ?>js/main.js"></script>
    <?php if ($isDashboard): ?>
    <script src="<?= $root ?>js/dashboard.js"></script>
    <?php endif; ?>
    
    <!-- Initializers -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Hide Loader
            const loader = document.getElementById('loader');
            if(loader) {
                setTimeout(() => { 
                    loader.style.opacity = '0';
                    setTimeout(() => { loader.style.display = 'none'; }, 400);
                }, 300);
            }
            
            // Init Lucide Icons
            if(window.lucide) lucide.createIcons();
            
            // Init AOS
            if(window.AOS) {
                AOS.init({
                    duration: 700,
                    once: true,
                    offset: 60,
                    easing: 'ease-out-cubic'
                });
            }
            
            // Mobile Menu Toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const navLinks = document.querySelector('.nav-links');
            if(menuToggle && navLinks) {
                menuToggle.addEventListener('click', () => {
                    navLinks.classList.toggle('mobile-open');
                });
            }
        });
    </script>
</body>
</html>
