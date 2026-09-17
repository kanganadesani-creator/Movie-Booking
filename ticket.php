<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$bookingId = (int)($_GET['booking_id'] ?? 0);
$userId = currentUserId();

$stmt = mysqli_prepare($conn, "
    SELECT b.*, sh.show_date, sh.show_time, m.title AS movie_title, t.name AS theatre_name, t.address, sc.screen_name, u.name AS user_name, u.email
    FROM bookings b
    JOIN shows sh ON b.show_id = sh.id
    JOIN movies m ON sh.movie_id = m.id
    JOIN theatres t ON sh.theatre_id = t.id
    JOIN screens sc ON sh.screen_id = sc.id
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ? AND b.user_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $bookingId, $userId);
mysqli_stmt_execute($stmt);
$booking = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$booking || $booking['status'] !== 'confirmed') {
    header("Location: user/bookings.php");
    exit();
}

$seatStmt = mysqli_prepare($conn, "SELECT s.seat_label FROM booking_seats bs JOIN seats s ON bs.seat_id = s.id WHERE bs.booking_id = ? ORDER BY s.seat_label");
mysqli_stmt_bind_param($seatStmt, "i", $bookingId);
mysqli_stmt_execute($seatStmt);
$seatsResult = mysqli_stmt_get_result($seatStmt);
$seatLabels = [];
while ($r = mysqli_fetch_assoc($seatsResult)) $seatLabels[] = $r['seat_label'];

$pageTitle = "Booking Confirmed";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="text-center mb-4">
                <i class="fa-solid fa-circle-check text-success" style="font-size:3.5rem;"></i>
                <h3 class="fw-bold mt-3">Booking Confirmed!</h3>
                <p class="text-secondary">Your e-ticket is ready. Show this at the theatre entrance.</p>
            </div>

            <div class="ticket-card p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-4">
                    <div>
                        <h4 class="fw-bold mb-1"><?= sanitize($booking['movie_title']) ?></h4>
                        <p class="text-secondary mb-0"><?= sanitize($booking['theatre_name']) ?> &bull; <?= sanitize($booking['screen_name']) ?></p>
                        <p class="text-secondary small"><?= sanitize($booking['address']) ?></p>
                    </div>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($booking['booking_code']) ?>" alt="QR Code" width="90" height="90">
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <p class="text-secondary small mb-0">Date</p>
                        <p class="fw-semibold"><?= date('d M Y', strtotime($booking['show_date'])) ?></p>
                    </div>
                    <div class="col-6 col-md-3">
                        <p class="text-secondary small mb-0">Time</p>
                        <p class="fw-semibold"><?= date('h:i A', strtotime($booking['show_time'])) ?></p>
                    </div>
                    <div class="col-6 col-md-3">
                        <p class="text-secondary small mb-0">Seats</p>
                        <p class="fw-semibold"><?= sanitize(implode(', ', $seatLabels)) ?></p>
                    </div>
                    <div class="col-6 col-md-3">
                        <p class="text-secondary small mb-0">Amount Paid</p>
                        <p class="fw-semibold text-warning">₹<?= number_format($booking['total_amount'], 0) ?></p>
                    </div>
                </div>

                <hr class="border-secondary">

                <div class="d-flex justify-content-between flex-wrap">
                    <div>
                        <p class="text-secondary small mb-0">Booking ID</p>
                        <p class="fw-semibold"><?= sanitize($booking['booking_code']) ?></p>
                    </div>
                    <div>
                        <p class="text-secondary small mb-0">Booked By</p>
                        <p class="fw-semibold"><?= sanitize($booking['user_name']) ?></p>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3 mt-4 justify-content-center flex-wrap">
                <a href="pdf/generate_ticket.php?booking_id=<?= $bookingId ?>" class="btn btn-primary px-4" target="_blank">
                    <i class="fa-solid fa-download"></i> Download PDF Ticket
                </a>
                <a href="user/bookings.php" class="btn btn-outline-light px-4">View My Bookings</a>
                <a href="index.php" class="btn btn-outline-light px-4">Back to Home</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
