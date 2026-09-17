<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminLogin();

if (isset($_GET['block'])) {
    $id = (int)$_GET['block'];
    mysqli_query($conn, "UPDATE users SET status = 'blocked' WHERE id = $id");
    flash('success', 'User blocked.');
    header("Location: users.php"); exit();
}
if (isset($_GET['unblock'])) {
    $id = (int)$_GET['unblock'];
    mysqli_query($conn, "UPDATE users SET status = 'active' WHERE id = $id");
    flash('success', 'User unblocked.');
    header("Location: users.php"); exit();
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    flash('success', 'User deleted.');
    header("Location: users.php"); exit();
}

$search = sanitize($_GET['search'] ?? '');
$sql = "SELECT u.*, (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as booking_count FROM users u";
if ($search) {
    $sql .= " WHERE u.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR u.email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
}
$sql .= " ORDER BY u.created_at DESC";
$users = mysqli_query($conn, $sql);
$success = flash('success');

$pageTitle = "Manage Users";
$activePage = "users";
include 'includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success auto-dismiss"><?= sanitize($success) ?></div><?php endif; ?>

<div class="cb-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0">All Users (<?= mysqli_num_rows($users) ?>)</h6>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name/email" value="<?= sanitize($search) ?>">
            <button class="btn btn-sm btn-warning">Search</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-dark-custom align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($u = mysqli_fetch_assoc($users)): ?>
                <tr>
                    <td><?= sanitize($u['name']) ?></td>
                    <td><?= sanitize($u['email']) ?></td>
                    <td><?= sanitize($u['phone']) ?></td>
                    <td><?= (int)$u['booking_count'] ?></td>
                    <td><span class="badge bg-<?= $u['status']==='active' ? 'success' : 'danger' ?>"><?= sanitize($u['status']) ?></span></td>
                    <td class="small text-secondary"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['status'] === 'active'): ?>
                            <a href="users.php?block=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning" title="Block"><i class="fa-solid fa-ban"></i></a>
                        <?php else: ?>
                            <a href="users.php?unblock=<?= $u['id'] ?>" class="btn btn-sm btn-outline-success" title="Unblock"><i class="fa-solid fa-check"></i></a>
                        <?php endif; ?>
                        <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user and all their bookings?');" title="Delete"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
