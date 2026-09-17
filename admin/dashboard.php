<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminLogin();

$totalMovies = mysqli_query($conn, "SELECT COUNT(*) c FROM movies")->fetch_assoc()['c'];
$totalUsers = mysqli_query($conn, "SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$totalBookings = mysqli_query($conn, "SELECT COUNT(*) c FROM bookings WHERE status = 'confirmed'")->fetch_assoc()['c'];
$todayRevenue = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) s FROM payments WHERE DATE(paid_at) = CURDATE() AND status='success'")->fetch_assoc()['s'];
$runningShows = mysqli_query($conn, "SELECT COUNT(*) c FROM shows WHERE show_date >= CURDATE()")->fetch_assoc()['c'];

$recentBookings = mysqli_query($conn, "
    SELECT b.booking_code, b.total_amount, b.status, b.created_at, u.name AS user_name, m.title
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN shows sh ON b.show_id = sh.id
    JOIN movies m ON sh.movie_id = m.id
    ORDER BY b.created_at DESC LIMIT 8
");

$pageTitle = "Dashboard";
$activePage = "dashboard";
include 'includes/admin_header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="icon-box" style="background:rgba(229,9,20,.15); color:#e50914;"><i class="fa-solid fa-clapperboard"></i></div>
            <div><p class="text-secondary small mb-0">Total Movies</p><h4 class="mb-0"><?= (int)$totalMovies ?></h4></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="icon-box" style="background:rgba(52,152,219,.15); color:#3498db;"><i class="fa-solid fa-users"></i></div>
            <div><p class="text-secondary small mb-0">Total Users</p><h4 class="mb-0"><?= (int)$totalUsers ?></h4></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="icon-box" style="background:rgba(46,204,113,.15); color:#2ecc71;"><i class="fa-solid fa-ticket"></i></div>
            <div><p class="text-secondary small mb-0">Total Bookings</p><h4 class="mb-0"><?= (int)$totalBookings ?></h4></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="icon-box" style="background:rgba(245,197,24,.15); color:#f5c518;"><i class="fa-solid fa-indian-rupee-sign"></i></div>
            <div><p class="text-secondary small mb-0">Today's Revenue</p><h4 class="mb-0">₹<?= number_format($todayRevenue, 0) ?></h4></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">Recent Bookings</h6>
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead><tr><th>Code</th><th>User</th><th>Movie</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (mysqli_num_rows($recentBookings) === 0): ?>
                        <tr><td colspan="5" class="text-secondary text-center">No bookings yet.</td></tr>
                    <?php endif; ?>
                    <?php while ($b = mysqli_fetch_assoc($recentBookings)):
                        $colors = ['confirmed'=>'success','pending'=>'warning','cancelled'=>'danger'];
                    ?>
                        <tr>
                            <td><?= sanitize($b['booking_code']) ?></td>
                            <td><?= sanitize($b['user_name']) ?></td>
                            <td><?= sanitize($b['title']) ?></td>
                            <td>₹<?= number_format($b['total_amount'], 0) ?></td>
                            <td><span class="badge bg-<?= $colors[$b['status']] ?>"><?= sanitize($b['status']) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">Quick Stats</h6>
            <div class="d-flex justify-content-between py-2 border-bottom" style="border-color:var(--cb-border) !important;">
                <span class="text-secondary">Upcoming Shows</span><strong><?= (int)$runningShows ?></strong>
            </div>
            <div class="d-flex justify-content-between py-2">
                <span class="text-secondary">Today's Revenue</span><strong class="text-warning">₹<?= number_format($todayRevenue, 0) ?></strong>
            </div>
            <a href="shows.php" class="btn btn-outline-warning w-100 mt-3 btn-sm">Manage Shows</a>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
