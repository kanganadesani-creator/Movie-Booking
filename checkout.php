<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$showId = (int)($_POST['show_id'] ?? $_GET['show_id'] ?? 0);
$seatIdsRaw = $_POST['seat_ids'] ?? $_GET['seat_ids'] ?? '';
$seatIds = array_filter(array_map('intval', explode(',', $seatIdsRaw)));

if ($showId <= 0 || empty($seatIds)) {
    header("Location: movies.php");
    exit();
}

$stmt = mysqli_prepare($conn, "
    SELECT sh.*, m.title, m.poster, t.name AS theatre_name, sc.screen_name
    FROM shows sh
    JOIN movies m ON sh.movie_id = m.id
    JOIN theatres t ON sh.theatre_id = t.id
    JOIN screens sc ON sh.screen_id = sc.id
    WHERE sh.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $showId);
mysqli_stmt_execute($stmt);
$show = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$show) { header("Location: movies.php"); exit(); }

// Fetch seat details & re-verify availability (prevents race conditions / stale selections)
$placeholders = implode(',', array_fill(0, count($seatIds), '?'));
$types = str_repeat('i', count($seatIds));

$seatSql = "SELECT * FROM seats WHERE id IN ($placeholders)";
$seatStmt = mysqli_prepare($conn, $seatSql);
mysqli_stmt_bind_param($seatStmt, $types, ...$seatIds);
mysqli_stmt_execute($seatStmt);
$seatsResult = mysqli_stmt_get_result($seatStmt);
$seats = [];
while ($row = mysqli_fetch_assoc($seatsResult)) $seats[] = $row;

$checkSql = "SELECT bs.seat_id FROM booking_seats bs JOIN bookings b ON bs.booking_id = b.id
             WHERE bs.show_id = ? AND bs.seat_id IN ($placeholders) AND b.status != 'cancelled'";
$checkStmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($checkStmt, "i" . $types, $showId, ...$seatIds);
mysqli_stmt_execute($checkStmt);
$conflictResult = mysqli_stmt_get_result($checkStmt);
$conflicts = mysqli_num_rows($conflictResult) > 0;

$error = null;
if ($conflicts) {
    $error = "Sorry, one or more of your selected seats were just booked by someone else. Please go back and choose different seats.";
} elseif (count($seats) !== count($seatIds)) {
    $error = "Invalid seat selection. Please try again.";
} else {
    // Ensure every seat actually belongs to this show's screen (defense against tampered requests)
    foreach ($seats as $s) {
        if ((int)$s['screen_id'] !== (int)$show['screen_id']) {
            $error = "Invalid seat selection for this show. Please try again.";
            break;
        }
    }
}

$total = 0;
if (!$error) {
    foreach ($seats as $s) {
        $total += $show['price'] + ($s['seat_type'] === 'premium' ? 50 : 0);
    }
}

// Handle "Proceed to Payment" -> create pending booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_checkout']) && !$error) {
    mysqli_begin_transaction($conn);
    try {
        $code = generateBookingCode();
        $userId = currentUserId();
        $insertBooking = mysqli_prepare($conn, "INSERT INTO bookings (booking_code, user_id, show_id, total_amount, status) VALUES (?, ?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($insertBooking, "siid", $code, $userId, $showId, $total);
        mysqli_stmt_execute($insertBooking);
        $bookingId = mysqli_insert_id($conn);

        $insertSeat = mysqli_prepare($conn, "INSERT INTO booking_seats (booking_id, show_id, seat_id) VALUES (?, ?, ?)");
        foreach ($seatIds as $sid) {
            mysqli_stmt_bind_param($insertSeat, "iii", $bookingId, $showId, $sid);
            mysqli_stmt_execute($insertSeat);
        }

        mysqli_commit($conn);
        header("Location: payment.php?booking_id=" . $bookingId);
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Sorry, one or more seats were just booked by someone else. Please choose different seats.";
    }
}

$pageTitle = "Checkout";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="cb-card p-4 p-md-5">
                <h4 class="fw-bold mb-4">Booking Summary</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= sanitize($error) ?></div>
                    <a href="movie-details.php" class="btn btn-outline-light">Back to Movies</a>
                <?php else: ?>
                    <div class="d-flex gap-3 mb-4">
                        <img src="<?= sanitize($show['poster']) ?>" style="width:70px;height:95px;object-fit:cover;border-radius:8px;">
                        <div>
                            <h5 class="mb-1"><?= sanitize($show['title']) ?></h5>
                            <p class="text-secondary small mb-0"><?= sanitize($show['theatre_name']) ?> &bull; <?= sanitize($show['screen_name']) ?></p>
                            <p class="text-secondary small mb-0"><?= date('D, d M Y', strtotime($show['show_date'])) ?> &bull; <?= date('h:i A', strtotime($show['show_time'])) ?></p>
                        </div>
                    </div>

                    <table class="table table-dark-custom">
                        <thead><tr><th>Seat</th><th>Type</th><th class="text-end">Price</th></tr></thead>
                        <tbody>
                        <?php foreach ($seats as $s): $p = $show['price'] + ($s['seat_type'] === 'premium' ? 50 : 0); ?>
                            <tr>
                                <td><?= sanitize($s['seat_label']) ?></td>
                                <td class="text-capitalize"><?= sanitize($s['seat_type']) ?></td>
                                <td class="text-end">₹<?= number_format($p, 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="border-top">
                                <td colspan="2" class="fw-bold">Total</td>
                                <td class="text-end fw-bold text-warning">₹<?= number_format($total, 0) ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <form method="POST">
                        <input type="hidden" name="show_id" value="<?= $showId ?>">
                        <input type="hidden" name="seat_ids" value="<?= sanitize($seatIdsRaw) ?>">
                        <input type="hidden" name="confirm_checkout" value="1">
                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">Proceed to Payment</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
