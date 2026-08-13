<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include shared context files
@include_once __DIR__ . '/db.php';
@include_once __DIR__ . '/functions.php';
@include_once __DIR__ . '/auth.php';

// Auto sync event statuses with current time
if (function_exists('autoUpdateEventStatuses') && isset($pdo)) {
    autoUpdateEventStatuses($pdo);
}

$pageTitle = $pageTitle ?? 'NSS Unit | TNGPTC Madurai';
$currentPage = basename($_SERVER['PHP_SELF']);
$isDashboard = (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false);
$root = $isDashboard ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="National Service Scheme Unit of Tamil Nadu Government Polytechnic College, Madurai-11. Not Me, But You.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Libraries -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= $root ?>css/style.css">
    <?php if ($isDashboard): ?>
    <link rel="stylesheet" href="<?= $root ?>css/dashboard.css">
    <?php endif; ?>
    
    <!-- FontAwesome & Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --nss-blue: #1b365d;
            --nss-navy: #0d233a;
            --nss-red: #b71c1c;
            --nss-gold: #f4a11d;
            --nss-gold-dark: #d98200;
            --primary: #1b365d;
            --primary-dark: #0d233a;
            --accent: #f4a11d;
            --light-bg: #f8fafc;
            --glass-bg: rgba(255, 255, 255, 0.96);
            --glass-border: rgba(226, 232, 240, 0.9);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }

        /* Navbar Layout */
        .navbar {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 4%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: #0f172a;
        }

        .brand-text h1 { 
            margin: 0; 
            font-size: 1.15rem; 
            color: var(--nss-blue); 
            font-weight: 800; 
            line-height: 1.2;
        }

        .brand-text p { 
            margin: 0; 
            font-size: 0.76rem; 
            color: #475569; 
            font-weight: 600; 
        }

        .nss-motto-badge {
            background: linear-gradient(135deg, var(--nss-red) 0%, #8b0000 100%);
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 20px;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            box-shadow: 0 2px 6px rgba(183, 28, 28, 0.25);
            border: 1px solid var(--nss-gold);
        }

        .nav-links {
            display: flex;
            gap: 1.75rem;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #334155;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.25s ease;
            padding: 0.35rem 0;
            position: relative;
        }

        .nav-links a:hover, .nav-links a.active {
            color: var(--nss-blue);
        }

        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 100%;
            height: 2.5px;
            background: var(--nss-gold);
            border-radius: 2px;
        }

        .nav-actions { 
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: #0f172a;
            font-size: 1.4rem;
            cursor: pointer;
        }

        /* Loading Spinner */
        #loader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #ffffff; z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }

        .spinner {
            width: 44px; height: 44px;
            border: 4px solid #e2e8f0;
            border-radius: 50%;
            border-top-color: var(--nss-blue);
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Responsive Nav */
        @media (max-width: 900px) {
            .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 68px;
                left: 0;
                width: 100%;
                background: #ffffff;
                padding: 1.5rem;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                border-bottom: 1px solid #e2e8f0;
            }
            .nav-links.mobile-open {
                display: flex;
            }
            .menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <?php if (!$isDashboard): ?>
    <div id="loader"><div class="spinner"></div></div>
    <script>
        setTimeout(function() {
            var l = document.getElementById('loader');
            if (l) { l.style.opacity = '0'; setTimeout(function(){ l.style.display = 'none'; }, 300); }
        }, 500);
    </script>
    <?php endif; ?>
    
    <?php
    $flash = getFlashMessage();
    if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="position:fixed; top:80px; right:20px; z-index:2000; max-width:380px; padding:1rem 1.5rem; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,0.15);" id="flashAlert">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <script>setTimeout(()=>{ const a=document.getElementById('flashAlert'); if(a) a.style.display='none'; }, 4000);</script>
    <?php endif; ?>

    <nav class="navbar">
        <div class="nav-container">
            <a href="<?= $root ?>index.php" class="nav-brand">
                <div class="logo-wrapper">
                    <?= function_exists('getNssLogoImg') ? getNssLogoImg(44, $root) : '<img src="' . $root . 'assets/images/nss-logo.png" style="width:44px;height:44px;">' ?>
                </div>
                <div class="brand-text">
                    <div style="display:flex; align-items:center; gap:0.4rem; flex-wrap:wrap;">
                        <h1>TNGPTC Madurai-11</h1>
                        <span class="nss-motto-badge"><i class="fas fa-heart"></i> Not Me, But You</span>
                    </div>
                    <p>National Service Scheme • NSS Unit</p>
                </div>
            </a>
            
            <ul class="nav-links">
                <li><a href="<?= $root ?>index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="<?= $root ?>about.php" class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">About</a></li>
                <li><a href="<?= $root ?>events.php" class="<?= $currentPage === 'events.php' ? 'active' : '' ?>">Events</a></li>
                <li><a href="<?= $root ?>gallery.php" class="<?= $currentPage === 'gallery.php' ? 'active' : '' ?>">Gallery</a></li>
                <li><a href="<?= $root ?>contact.php" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">Contact</a></li>
            </ul>
            
            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $dashLink = 'dashboard/volunteer.php';
                    $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'volunteer';
                    if ($role === 'admin') $dashLink = 'dashboard/admin.php';
                    elseif ($role === 'alumni') $dashLink = 'dashboard/alumni.php';
                    ?>
                    <a href="<?= $root . $dashLink ?>" class="btn btn-outline"><i class="fas fa-gauge"></i> Dashboard</a>
                    <a href="<?= $root ?>logout.php" class="btn btn-primary"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="<?= $root ?>login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php endif; ?>
                <button class="menu-toggle" aria-label="Toggle Menu"><i data-lucide="menu"></i></button>
            </div>
        </div>
    </nav>
    <main style="margin-top: 68px;">
