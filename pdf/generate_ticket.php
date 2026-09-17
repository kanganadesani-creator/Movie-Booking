<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../fpdf/fpdf.php';

if (!isLoggedIn() && !isAdminLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$bookingId = (int)($_GET['booking_id'] ?? 0);

if (isAdminLoggedIn()) {
    // Admin can view/print any booking's ticket
    $stmt = mysqli_prepare($conn, "
        SELECT b.*, sh.show_date, sh.show_time, m.title AS movie_title, t.name AS theatre_name, t.address, sc.screen_name, u.name AS user_name
        FROM bookings b
        JOIN shows sh ON b.show_id = sh.id
        JOIN movies m ON sh.movie_id = m.id
        JOIN theatres t ON sh.theatre_id = t.id
        JOIN screens sc ON sh.screen_id = sc.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $bookingId);
} else {
    $userId = currentUserId();
    $stmt = mysqli_prepare($conn, "
        SELECT b.*, sh.show_date, sh.show_time, m.title AS movie_title, t.name AS theatre_name, t.address, sc.screen_name, u.name AS user_name
        FROM bookings b
        JOIN shows sh ON b.show_id = sh.id
        JOIN movies m ON sh.movie_id = m.id
        JOIN theatres t ON sh.theatre_id = t.id
        JOIN screens sc ON sh.screen_id = sc.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $bookingId, $userId);
}
mysqli_stmt_execute($stmt);
$booking = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$booking || $booking['status'] !== 'confirmed') {
    die("Ticket not found.");
}

$seatStmt = mysqli_prepare($conn, "SELECT s.seat_label FROM booking_seats bs JOIN seats s ON bs.seat_id = s.id WHERE bs.booking_id = ? ORDER BY s.seat_label");
mysqli_stmt_bind_param($seatStmt, "i", $bookingId);
mysqli_stmt_execute($seatStmt);
$seatsResult = mysqli_stmt_get_result($seatStmt);
$seatLabels = [];
while ($r = mysqli_fetch_assoc($seatsResult)) $seatLabels[] = $r['seat_label'];

// ----------------------------------------
// Generate PDF
// ----------------------------------------
class TicketPDF extends FPDF {
    function Header() {
        $this->SetFillColor(229, 9, 20); // red banner
        $this->Rect(0, 0, 210, 25, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 20);
        $this->SetXY(10, 7);
        $this->Cell(0, 10, 'CineBook - E-Ticket', 0, 1, 'L');
        $this->SetTextColor(0, 0, 0);
    }
}

$pdf = new TicketPDF();
$pdf->AddPage();
$pdf->SetY(35);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $booking['movie_title']), 0, 1);

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(90, 90, 90);
$pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $booking['theatre_name'] . ' - ' . $booking['screen_name']), 0, 1);
$pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $booking['address']), 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(6);

// Details table
$pdf->SetFont('Arial', 'B', 11);
$rows = [
    ['Booking ID', $booking['booking_code']],
    ['Date', date('d M Y', strtotime($booking['show_date']))],
    ['Time', date('h:i A', strtotime($booking['show_time']))],
    ['Seats', implode(', ', $seatLabels)],
    ['Booked By', $booking['user_name']],
    ['Amount Paid', 'Rs. ' . number_format($booking['total_amount'], 2)],
];

foreach ($rows as $row) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(50, 9, $row[0], 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 9, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$row[1]), 0, 1);
}

$pdf->Ln(8);

// QR Code (fetched from external API, embedded as image)
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($booking['booking_code']);
$qrData = @file_get_contents($qrUrl);
if ($qrData !== false) {
    $tmpFile = sys_get_temp_dir() . '/qr_' . $bookingId . '.png';
    file_put_contents($tmpFile, $qrData);
    $pdf->Image($tmpFile, 150, 40, 40, 40, 'PNG');
    @unlink($tmpFile);
}

$pdf->SetY(-40);
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(130, 130, 130);
$pdf->Cell(0, 6, 'Please arrive at least 20 minutes before showtime. Carry a valid ID.', 0, 1, 'C');
$pdf->Cell(0, 6, 'This is a computer-generated ticket and does not require a signature.', 0, 1, 'C');

$pdf->Output('D', 'CineBook_Ticket_' . $booking['booking_code'] . '.pdf');
