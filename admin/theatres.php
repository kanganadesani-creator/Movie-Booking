<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminLogin();

$editTheatre = null;

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM theatres WHERE id = $id");
    flash('success', 'Theatre deleted successfully.');
    header("Location: theatres.php");
    exit();
}

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM theatres WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $editTheatre = mysqli_stmt_get_result($stmt)->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $theatreId = (int)($_POST['theatre_id'] ?? 0);

    if ($name !== '') {
        if ($theatreId > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE theatres SET name=?, address=?, city=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssi", $name, $address, $city, $theatreId);
            mysqli_stmt_execute($stmt);
            flash('success', 'Theatre updated successfully.');
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO theatres (name, address, city) VALUES (?,?,?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $address, $city);
            mysqli_stmt_execute($stmt);
            flash('success', 'Theatre added successfully.');
        }
        header("Location: theatres.php");
        exit();
    }
}

$theatres = mysqli_query($conn, "SELECT * FROM theatres ORDER BY name");
$success = flash('success');

$pageTitle = "Manage Theatres";
$activePage = "theatres";
include 'includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success auto-dismiss"><?= sanitize($success) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3"><?= $editTheatre ? 'Edit Theatre' : 'Add New Theatre' ?></h6>
            <form method="POST">
                <input type="hidden" name="theatre_id" value="<?= $editTheatre['id'] ?? 0 ?>">
                <div class="mb-2">
                    <label class="form-label small">Theatre Name</label>
                    <input type="text" name="name" class="form-control form-control-sm" value="<?= sanitize($editTheatre['name'] ?? '') ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Address</label>
                    <input type="text" name="address" class="form-control form-control-sm" value="<?= sanitize($editTheatre['address'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small">City</label>
                    <input type="text" name="city" class="form-control form-control-sm" value="<?= sanitize($editTheatre['city'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100"><?= $editTheatre ? 'Update' : 'Add Theatre' ?></button>
                <?php if ($editTheatre): ?><a href="theatres.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">Cancel</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">All Theatres</h6>
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead><tr><th>Name</th><th>Address</th><th>City</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($t = mysqli_fetch_assoc($theatres)): ?>
                        <tr>
                            <td><?= sanitize($t['name']) ?></td>
                            <td class="small text-secondary"><?= sanitize($t['address']) ?></td>
                            <td><?= sanitize($t['city']) ?></td>
                            <td>
                                <a href="theatres.php?edit=<?= $t['id'] ?>" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-pen"></i></a>
                                <a href="theatres.php?delete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this theatre and all its screens/shows?');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
