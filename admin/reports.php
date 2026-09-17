<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminLogin();

// Daily revenue (last 14 days)
$daily = mysqli_query($conn, "
    SELECT DATE(paid_at) as d, SUM(amount) as total
    FROM payments WHERE status='success' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(paid_at) ORDER BY d DESC
");

// Monthly revenue (last 6 months)
$monthly = mysqli_query($conn, "
    SELECT DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total
    FROM payments WHERE status='success'
    GROUP BY ym ORDER BY ym DESC LIMIT 6
");

// Movie-wise sales
$movieWise = mysqli_query($conn, "
    SELECT m.title, COUNT(b.id) as bookings, SUM(b.total_amount) as revenue
    FROM bookings b
    JOIN shows sh ON b.show_id = sh.id
    JOIN movies m ON sh.movie_id = m.id
    WHERE b.status = 'confirmed'
    GROUP BY m.id ORDER BY revenue DESC
");

// Theatre-wise sales
$theatreWise = mysqli_query($conn, "
    SELECT t.name, COUNT(b.id) as bookings, SUM(b.total_amount) as revenue
    FROM bookings b
    JOIN shows sh ON b.show_id = sh.id
    JOIN theatres t ON sh.theatre_id = t.id
    WHERE b.status = 'confirmed'
    GROUP BY t.id ORDER BY revenue DESC
");

$pageTitle = "Reports";
$activePage = "reports";
include 'includes/admin_header.php';
?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">Daily Revenue (Last 14 Days)</h6>
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle table-sm">
                    <thead><tr><th>Date</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    <?php if (mysqli_num_rows($daily) === 0): ?>
                        <tr><td colspan="2" class="text-secondary text-center">No data yet.</td></tr>
                    <?php endif; ?>
                    <?php while ($d = mysqli_fetch_assoc($daily)): ?>
                        <tr><td><?= date('d M Y', strtotime($d['d'])) ?></td><td class="text-end">₹<?= number_format($d['total'],0) ?></td></tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">Monthly Revenue (Last 6 Months)</h6>
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle table-sm">
                    <thead><tr><th>Month</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    <?php if (mysqli_num_rows($monthly) === 0): ?>
                        <tr><td colspan="2" class="text-secondary text-center">No data yet.</td></tr>
                    <?php endif; ?>
                    <?php while ($m = mysqli_fetch_assoc($monthly)): ?>
                        <tr><td><?= date('F Y', strtotime($m['ym'].'-01')) ?></td><td class="text-end">₹<?= number_format($m['total'],0) ?></td></tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">Movie-wise Sales</h6>
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle table-sm">
                    <thead><tr><th>Movie</th><th>Bookings</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    <?php if (mysqli_num_rows($movieWise) === 0): ?>
                        <tr><td colspan="3" class="text-secondary text-center">No data yet.</td></tr>
                    <?php endif; ?>
                    <?php while ($m = mysqli_fetch_assoc($movieWise)): ?>
                        <tr><td><?= sanitize($m['title']) ?></td><td><?= (int)$m['bookings'] ?></td><td class="text-end">₹<?= number_format($m['revenue'],0) ?></td></tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">Theatre-wise Sales</h6>
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle table-sm">
                    <thead><tr><th>Theatre</th><th>Bookings</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    <?php if (mysqli_num_rows($theatreWise) === 0): ?>
                        <tr><td colspan="3" class="text-secondary text-center">No data yet.</td></tr>
                    <?php endif; ?>
                    <?php while ($t = mysqli_fetch_assoc($theatreWise)): ?>
                        <tr><td><?= sanitize($t['name']) ?></td><td><?= (int)$t['bookings'] ?></td><td class="text-end">₹<?= number_format($t['revenue'],0) ?></td></tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
