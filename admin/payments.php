<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminLogin();

$payments = mysqli_query($conn, "
    SELECT p.*, b.booking_code, u.name AS user_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    ORDER BY p.paid_at DESC
");

$totalRevenue = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) s FROM payments WHERE status='success'")->fetch_assoc()['s'];

$pageTitle = "Payment History";
$activePage = "payments";
include 'includes/admin_header.php';
?>

<div class="cb-card p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap">
    <h6 class="fw-bold mb-0">Total Revenue Collected</h6>
    <h4 class="text-warning mb-0">₹<?= number_format($totalRevenue, 0) ?></h4>
</div>

<div class="cb-card p-4">
    <h6 class="fw-bold mb-3">All Transactions</h6>
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle">
            <thead><tr><th>Transaction ID</th><th>Booking</th><th>User</th><th>Method</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php if (mysqli_num_rows($payments) === 0): ?>
                <tr><td colspan="7" class="text-secondary text-center">No payments yet.</td></tr>
            <?php endif; ?>
            <?php while ($p = mysqli_fetch_assoc($payments)): ?>
                <tr>
                    <td><?= sanitize($p['transaction_id']) ?></td>
                    <td><?= sanitize($p['booking_code']) ?></td>
                    <td><?= sanitize($p['user_name']) ?></td>
                    <td><?= sanitize($p['method']) ?></td>
                    <td>₹<?= number_format($p['amount'], 0) ?></td>
                    <td><span class="badge bg-<?= $p['status']==='success' ? 'success' : 'danger' ?>"><?= sanitize($p['status']) ?></span></td>
                    <td class="small text-secondary"><?= date('d M Y, h:i A', strtotime($p['paid_at'])) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
