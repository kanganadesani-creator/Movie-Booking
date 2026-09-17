<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();

$userId = currentUserId();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $cancelId = (int)$_POST['cancel_booking_id'];
    $check = mysqli_prepare($conn, "
        SELECT b.id, sh.show_date, sh.show_time FROM bookings b
        JOIN shows sh ON b.show_id = sh.id
        WHERE b.id = ? AND b.user_id = ? AND b.status = 'confirmed'
    ");
    mysqli_stmt_bind_param($check, "ii", $cancelId, $userId);
    mysqli_stmt_execute($check);
    $bk = mysqli_stmt_get_result($check)->fetch_assoc();

    if ($bk) {
        $showDateTime = strtotime($bk['show_date'] . ' ' . $bk['show_time']);
        if ($showDateTime > time()) {
            $upd = mysqli_prepare($conn, "UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            mysqli_stmt_bind_param($upd, "i", $cancelId);
            mysqli_stmt_execute($upd);
            flash('success', 'Booking cancelled successfully.');
        } else {
            flash('error', 'Cannot cancel a booking for a show that has already started or passed.');
        }
    }
    header("Location: bookings.php");
    exit();
}

$stmt = mysqli_prepare($conn, "
    SELECT b.*, sh.show_date, sh.show_time, m.title, m.poster, t.name AS theatre_name
    FROM bookings b
    JOIN shows sh ON b.show_id = sh.id
    JOIN movies m ON sh.movie_id = m.id
    JOIN theatres t ON sh.theatre_id = t.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt);

$success = flash('success');
$errorMsg = flash('error');

$pageTitle = "My Bookings";
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container py-5">
    <h4 class="fw-bold mb-4"><i class="fa-solid fa-ticket text-warning"></i> My Bookings</h4>

    <?php if ($success): ?><div class="alert alert-success auto-dismiss"><?= sanitize($success) ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert alert-danger"><?= sanitize($errorMsg) ?></div><?php endif; ?>

    <?php if (mysqli_num_rows($bookings) === 0): ?>
        <div class="cb-card p-5 text-center">
            <i class="fa-solid fa-film fa-2x text-secondary mb-3"></i>
            <p class="text-secondary">You haven't booked any tickets yet.</p>
            <a href="../movies.php" class="btn btn-primary">Browse Movies</a>
        </div>
    <?php endif; ?>

    <?php while ($b = mysqli_fetch_assoc($bookings)):
        $statusColors = ['confirmed' => 'success', 'pending' => 'warning', 'cancelled' => 'danger'];
        $isPast = strtotime($b['show_date'] . ' ' . $b['show_time']) < time();
    ?>
        <div class="cb-card p-4 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex gap-3 align-items-center">
                <img src="<?= sanitize($b['poster']) ?>" style="width:55px;height:75px;object-fit:cover;border-radius:8px;">
                <div>
                    <h6 class="mb-1"><?= sanitize($b['title']) ?></h6>
                    <p class="small text-secondary mb-1"><?= sanitize($b['theatre_name']) ?></p>
                    <p class="small text-secondary mb-0"><?= date('d M Y, h:i A', strtotime($b['show_date'] . ' ' . $b['show_time'])) ?></p>
                </div>
            </div>
            <div class="text-center">
                <span class="badge bg-<?= $statusColors[$b['status']] ?> status-badge text-uppercase"><?= sanitize($b['status']) ?></span>
                <p class="small text-secondary mb-0 mt-1"><?= sanitize($b['booking_code']) ?></p>
            </div>
            <div class="text-end">
                <h6 class="text-warning mb-2">₹<?= number_format($b['total_amount'], 0) ?></h6>
                <div class="d-flex gap-2">
                    <?php if ($b['status'] === 'confirmed'): ?>
                        <a href="../pdf/generate_ticket.php?booking_id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-light" target="_blank">
                            <i class="fa-solid fa-download"></i>
                        </a>
                        <?php if (!$isPast): ?>
                            <form method="POST" onsubmit="return confirm('Cancel this booking?');">
                                <input type="hidden" name="cancel_booking_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                            </form>
                        <?php endif; ?>
                    <?php elseif ($b['status'] === 'pending'): ?>
                        <a href="../payment.php?booking_id=<?= $b['id'] ?>" class="btn btn-sm btn-warning">Complete Payment</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php include '../includes/footer.php'; ?>
