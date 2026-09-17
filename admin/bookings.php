<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminLogin();

if (isset($_GET['cancel'])) {
    $id = (int)$_GET['cancel'];
    mysqli_query($conn, "UPDATE bookings SET status = 'cancelled' WHERE id = $id");
    flash('success', 'Booking cancelled.');
    header("Location: bookings.php");
    exit();
}

$statusFilter = sanitize($_GET['status'] ?? '');
$sql = "
    SELECT b.*, u.name AS user_name, u.email, m.title, t.name AS theatre_name, sh.show_date, sh.show_time,
           (SELECT p.method FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) as pay_method
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN shows sh ON b.show_id = sh.id
    JOIN movies m ON sh.movie_id = m.id
    JOIN theatres t ON sh.theatre_id = t.id
";
if ($statusFilter) {
    $sql .= " WHERE b.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}
$sql .= " ORDER BY b.created_at DESC";
$bookings = mysqli_query($conn, $sql);
$success = flash('success');

$pageTitle = "Manage Bookings";
$activePage = "bookings";
include 'includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success auto-dismiss"><?= sanitize($success) ?></div><?php endif; ?>

<div class="cb-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0">All Bookings (<?= mysqli_num_rows($bookings) ?>)</h6>
        <div class="btn-group btn-group-sm">
            <a href="bookings.php" class="btn btn-outline-light <?= $statusFilter==='' ? 'active' : '' ?>">All</a>
            <a href="bookings.php?status=confirmed" class="btn btn-outline-light <?= $statusFilter==='confirmed' ? 'active' : '' ?>">Confirmed</a>
            <a href="bookings.php?status=pending" class="btn btn-outline-light <?= $statusFilter==='pending' ? 'active' : '' ?>">Pending</a>
            <a href="bookings.php?status=cancelled" class="btn btn-outline-light <?= $statusFilter==='cancelled' ? 'active' : '' ?>">Cancelled</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle">
            <thead><tr><th>Code</th><th>User</th><th>Movie</th><th>Theatre</th><th>Show</th><th>Amount</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (mysqli_num_rows($bookings) === 0): ?>
                <tr><td colspan="9" class="text-secondary text-center">No bookings found.</td></tr>
            <?php endif; ?>
            <?php while ($b = mysqli_fetch_assoc($bookings)):
                $colors = ['confirmed'=>'success','pending'=>'warning','cancelled'=>'danger'];
            ?>
                <tr>
                    <td><?= sanitize($b['booking_code']) ?></td>
                    <td><?= sanitize($b['user_name']) ?><br><span class="small text-secondary"><?= sanitize($b['email']) ?></span></td>
                    <td><?= sanitize($b['title']) ?></td>
                    <td class="small"><?= sanitize($b['theatre_name']) ?></td>
                    <td class="small"><?= date('d M, h:i A', strtotime($b['show_date'].' '.$b['show_time'])) ?></td>
                    <td>₹<?= number_format($b['total_amount'], 0) ?></td>
                    <td class="small"><?= sanitize($b['pay_method'] ?? '-') ?></td>
                    <td><span class="badge bg-<?= $colors[$b['status']] ?>"><?= sanitize($b['status']) ?></span></td>
                    <td>
                        <?php if ($b['status'] === 'confirmed'): ?>
                            <a href="../pdf/generate_ticket.php?booking_id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-light" target="_blank" title="Print Ticket"><i class="fa-solid fa-print"></i></a>
                            <a href="bookings.php?cancel=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this booking?');"><i class="fa-solid fa-ban"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
