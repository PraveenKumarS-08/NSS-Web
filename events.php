<?php
session_start();
$pageTitle = 'Events & Camps | NSS TNGPTC Madurai';
require_once __DIR__ . '/includes/header.php';

$msg = '';
$error = '';
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch all events with user's registration status
$events = [];
try {
    if (isset($pdo)) {
        if ($user_id > 0) {
            $stmt = $pdo->prepare("
                SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) as reg_count,
                       (SELECT id FROM event_registrations r2 WHERE r2.event_id = e.id AND r2.user_id = ?) as user_registered_id
                FROM events e 
                ORDER BY e.event_date DESC
            ");
            $stmt->execute([$user_id]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->query("
                SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) as reg_count,
                       0 as user_registered_id
                FROM events e 
                ORDER BY e.event_date DESC
            ");
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) { }

if (empty($events)) {
    $events = [
        ['id' => 1, 'title' => 'Blood Donation Camp 2026', 'event_date' => date('Y-m-d H:i:s', strtotime('+10 days')), 'location' => 'College Premises, Madurai-11', 'category' => 'Health', 'description' => 'Annual blood donation camp organized by NSS Unit, TNGPTC Madurai. All students and faculty are welcome.', 'status' => 'upcoming', 'user_registered_id' => 0],
        ['id' => 2, 'title' => 'Tree Plantation Drive', 'event_date' => date('Y-m-d H:i:s', strtotime('+18 days')), 'location' => 'College Campus', 'category' => 'Environment', 'description' => 'Planting 500 saplings in and around the campus as part of Green India Mission.', 'status' => 'upcoming', 'user_registered_id' => 0],
        ['id' => 3, 'title' => 'Village Adoption Program', 'event_date' => date('Y-m-d H:i:s', strtotime('+30 days')), 'location' => 'Alanganallur Village', 'category' => 'Community', 'description' => 'Community development, health awareness, and literacy programs in adopted village.', 'status' => 'upcoming', 'user_registered_id' => 0],
        ['id' => 4, 'title' => 'Swachh Bharat Cleanliness Drive', 'event_date' => date('Y-m-d H:i:s', strtotime('-5 days')), 'location' => 'College & Surroundings', 'category' => 'Environment', 'description' => 'Campus and surrounding areas cleanliness drive held successfully with 120+ volunteers.', 'status' => 'completed', 'user_registered_id' => 0]
    ];
}
?>

<div class="page-hero" style="padding: 90px 5% 40px; text-align: center; background: linear-gradient(135deg, #1b365d 0%, #0d233a 100%); border-bottom: 3px solid #f4a11d;">
    <h1 class="page-title" style="font-size:2.8rem; color:white; margin-bottom: 0.5rem;">NSS Camps & Special Activities</h1>
    <p style="color:#f4a11d; font-size:1.1rem; font-weight:600;">Participate, Serve & Earn Verified NSS Hours</p>
</div>

<section class="section" style="padding: 50px 5%; max-width: 1300px; margin: 0 auto;">
    <?php if ($msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="filters" style="display: flex; justify-content: center; gap: 12px; margin-bottom: 40px; flex-wrap: wrap;">
        <button type="button" class="filter-btn active" data-filter="all">All Activities</button>
        <button type="button" class="filter-btn" data-filter="upcoming">Upcoming Camps</button>
        <button type="button" class="filter-btn" data-filter="ongoing">Ongoing Events</button>
        <button type="button" class="filter-btn" data-filter="completed">Completed Events</button>
    </div>

    <div class="events-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 28px;">
        <?php foreach ($events as $ev): 
            $eventTimestamp = strtotime($ev['event_date']);
            $now = time();
            $oneDay = 86400;
            
            // Precise Real-Time Event Status
            $rawStatus = strtolower($ev['status'] ?? 'upcoming');
            if ($rawStatus === 'postponed' || $rawStatus === 'cancelled') {
                $status = $rawStatus;
            } elseif ($eventTimestamp > $now) {
                $status = 'upcoming';
            } elseif ($eventTimestamp <= $now && $eventTimestamp >= ($now - $oneDay)) {
                $status = 'ongoing';
            } else {
                $status = 'completed';
            }

            $imgSrc = !empty($ev['image']) ? $ev['image'] : (!empty($ev['image_path']) ? $ev['image_path'] : null);
            $userRegistered = !empty($ev['user_registered_id']);
        ?>
        <div class="event-card event-item <?= $status ?>" data-aos="fade-up" style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 4px 20px rgba(0,0,0,0.04);">
            <?php if ($imgSrc): ?>
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($ev['title']) ?>" style="width:100%; height:200px; object-fit:cover;">
            <?php else: ?>
                <div style="width:100%; height:180px; background:linear-gradient(135deg, #1b365d 0%, #0d233a 100%); display:flex; align-items:center; justify-content:center; color:#f4a11d;">
                    <i class="fas fa-calendar-alt" style="font-size:3rem;"></i>
                </div>
            <?php endif; ?>

            <div class="event-body" style="padding: 1.5rem; flex:1; display:flex; flex-direction:column;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <span class="badge" style="background:#f0f4f8; color:#1b365d; padding:4px 12px; border-radius:20px; font-weight:600; font-size:0.8rem; border:1px solid rgba(27,54,93,0.15);"><?= htmlspecialchars($ev['category'] ?? 'General') ?></span>
                    
                    <?php if ($status === 'upcoming'): ?>
                        <span class="badge" style="background:#1b365d; color:#fff; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.8rem;"><i class="fas fa-clock"></i> Upcoming</span>
                    <?php elseif ($status === 'ongoing'): ?>
                        <span class="badge" style="background:#dcfce7; color:#166534; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.8rem; border:1px solid #bbf7d0;"><i class="fas fa-broadcast-tower fa-spin"></i> Ongoing Now</span>
                    <?php elseif ($status === 'completed'): ?>
                        <span class="badge" style="background:#f1f5f9; color:#475569; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.8rem;">Completed</span>
                    <?php elseif ($status === 'postponed'): ?>
                        <span class="badge" style="background:#fef3c7; color:#92400e; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.8rem;">Postponed</span>
                    <?php else: ?>
                        <span class="badge" style="background:#fee2e2; color:#991b1b; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.8rem;">Cancelled</span>
                    <?php endif; ?>
                </div>

                <h3 class="event-title" style="font-size:1.3rem; color:#0f172a; margin-bottom:0.75rem; font-family:'Outfit',sans-serif;"><?= htmlspecialchars($ev['title']) ?></h3>
                
                <div class="event-meta" style="margin-bottom:1.25rem; font-size:0.9rem; color:#64748b;">
                    <div style="margin-bottom:4px;"><i class="fas fa-calendar-alt" style="color:#1b365d; margin-right:6px;"></i> <?= date('F d, Y - h:i A', $eventTimestamp) ?></div>
                    <div><i class="fas fa-map-marker-alt" style="color:#b71c1c; margin-right:6px;"></i> <?= htmlspecialchars($ev['location']) ?></div>
                </div>

                <p style="color:#475569; font-size:0.92rem; line-height:1.6; margin-bottom:1.5rem; flex:1;"><?= htmlspecialchars($ev['description'] ?? '') ?></p>

                <div class="event-footer" style="margin-top:auto;">
                    <?php if ($status === 'completed'): ?>
                        <button class="btn btn-outline btn-block" style="width:100%; opacity:0.75; font-weight:600;" disabled>
                            <i class="fas fa-flag-checkered"></i> Event Over — Thanks for Participating!
                        </button>
                    <?php elseif ($status === 'postponed' || $status === 'cancelled'): ?>
                        <button class="btn btn-outline btn-block" style="width:100%; opacity:0.85; color:#92400e; background:#fffbe8;" disabled>
                            <i class="fas fa-pause-circle"></i> Event <?= ucfirst($status) ?>
                        </button>
                    <?php elseif ($userRegistered): ?>
                        <a href="event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-success btn-block" style="width:100%; text-align:center; background:#166534; border:none; color:white; font-weight:700;">
                            <i class="fas fa-check-circle"></i> Already Registered (View)
                        </a>
                    <?php else: ?>
                        <a href="event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-primary btn-block" style="width:100%; text-align:center;">
                            <i class="fas fa-edit"></i> Register for Event
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const items = document.querySelectorAll('.event-item');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const filterValue = btn.getAttribute('data-filter');
            
            items.forEach(item => {
                if (filterValue === 'all' || item.classList.contains(filterValue)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
