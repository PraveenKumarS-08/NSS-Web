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

?>

<div class="page-hero">
    <h1 class="page-title">NSS Camps & Special Activities</h1>
    <p>Participate, Serve & Earn Verified NSS Hours</p>
</div>

<section class="section" style="padding: 50px 5%; max-width: 1300px; margin: 0 auto;">
    <?php if ($msg): ?>
        <div class="alert alert-success" style="margin-bottom:30px;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (!empty($events)): ?>
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
            <div class="event-card event-item <?= $status ?>" data-aos="fade-up" style="background:#fff; border:1px solid var(--border); border-radius:14px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 4px 20px rgba(0,0,0,0.04);">
                <?php if ($imgSrc): ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($ev['title']) ?>" style="width:100%; height:200px; object-fit:cover;">
                <?php else: ?>
                    <div style="width:100%; height:180px; background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); display:flex; align-items:center; justify-content:center; color:var(--accent);">
                        <i class="fas fa-calendar-alt" style="font-size:3rem;"></i>
                    </div>
                <?php endif; ?>

                <div class="event-body" style="padding: 1.5rem; flex:1; display:flex; flex-direction:column;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <span class="badge" style="background:var(--primary-subtle); color:var(--primary); padding:4px 12px; border-radius:20px; font-weight:600; font-size:0.8rem; border:1px solid var(--border-accent);"><?= htmlspecialchars($ev['category'] ?? 'General') ?></span>
                        
                        <?php if ($status === 'upcoming'): ?>
                            <span class="badge" style="background:var(--primary); color:#fff; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.8rem;"><i class="fas fa-clock"></i> Upcoming</span>
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

                    <h3 class="event-title" style="font-size:1.3rem; color:var(--primary); margin-bottom:0.75rem; font-family:'Outfit',sans-serif;"><?= htmlspecialchars($ev['title']) ?></h3>
                    
                    <div class="event-meta" style="margin-bottom:1.25rem; font-size:0.9rem; color:var(--text-muted);">
                        <div style="margin-bottom:4px;"><i class="fas fa-calendar-alt text-accent" style="margin-right:6px;"></i> <?= date('F d, Y - h:i A', $eventTimestamp) ?></div>
                        <div><i class="fas fa-map-marker-alt text-danger" style="margin-right:6px;"></i> <?= htmlspecialchars($ev['location']) ?></div>
                    </div>

                    <p style="color:var(--text-muted); font-size:0.92rem; line-height:1.6; margin-bottom:1.5rem; flex:1;"><?= htmlspecialchars($ev['description'] ?? '') ?></p>

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
    <?php else: ?>
        <!-- Clean Empty State when no events exist -->
        <div style="text-align:center; padding:5rem 1rem; background:#fff; border-radius:20px; border:1px solid var(--border); box-shadow:0 4px 20px rgba(0,0,0,0.04); max-width:650px; margin:20px auto;" data-aos="fade-up">
            <div style="width:85px; height:85px; border-radius:50%; background:var(--primary-subtle); color:var(--primary); display:inline-flex; align-items:center; justify-content:center; font-size:2.4rem; margin-bottom:1.25rem;">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h2 style="color:var(--primary); font-family:'Outfit',sans-serif; margin-bottom:0.6rem; font-size:1.8rem; font-weight:700;">No Events Currently Scheduled</h2>
            <p style="color:var(--text-muted); font-size:1rem; line-height:1.7; margin:0 0 1.75rem;">
                Upcoming blood donation camps, tree plantation drives, special camps, and orientation sessions will be posted here.
            </p>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="dashboard/admin-events.php" class="btn btn-primary" style="padding:0.8rem 1.8rem; border-radius:12px; font-weight:700;">
                    <i class="fas fa-plus-circle"></i> Create New Event in Admin Panel
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
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

<style>
@media (max-width: 768px) {
    .events-grid { grid-template-columns: 1fr !important; gap: 20px !important; }
    .page-hero { padding: 80px 4% 30px !important; }
    .page-hero .page-title { font-size: 2rem !important; }
    .filters { gap: 8px !important; }
    .filter-btn { font-size: 0.82rem; padding: 0.5rem 1rem; }
}
@media (max-width: 480px) {
    .events-grid { gap: 16px !important; }
    .page-hero .page-title { font-size: 1.65rem !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
