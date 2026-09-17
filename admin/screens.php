<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminLogin();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM screens WHERE id = $id");
    flash('success', 'Screen deleted successfully.');
    header("Location: screens.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theatreId = (int)($_POST['theatre_id'] ?? 0);
    $screenName = sanitize($_POST['screen_name'] ?? '');
    $rows = max(1, min(10, (int)($_POST['rows_count'] ?? 5)));
    $seatsPerRow = max(1, min(20, (int)($_POST['seats_per_row'] ?? 8)));
    $premiumRows = max(0, min($rows, (int)($_POST['premium_rows'] ?? 0)));

    if ($theatreId > 0 && $screenName !== '') {
        $stmt = mysqli_prepare($conn, "INSERT INTO screens (theatre_id, screen_name, rows_count, seats_per_row) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "isii", $theatreId, $screenName, $rows, $seatsPerRow);
        mysqli_stmt_execute($stmt);
        $screenId = mysqli_insert_id($conn);

        // Auto-generate seat layout: rows A, B, C...; first N rows (front) are marked premium
        $letters = range('A', 'Z');
        $seatInsert = mysqli_prepare($conn, "INSERT INTO seats (screen_id, seat_label, seat_type) VALUES (?, ?, ?)");
        for ($r = 0; $r < $rows; $r++) {
            $rowLetter = $letters[$r];
            $seatType = ($r < $premiumRows) ? 'premium' : 'normal';
            for ($n = 1; $n <= $seatsPerRow; $n++) {
                $label = $rowLetter . $n;
                mysqli_stmt_bind_param($seatInsert, "iss", $screenId, $label, $seatType);
                mysqli_stmt_execute($seatInsert);
            }
        }
        flash('success', "Screen added with $rows rows x $seatsPerRow seats (" . ($rows * $seatsPerRow) . " total seats generated).");
    }
    header("Location: screens.php");
    exit();
}

$screens = mysqli_query($conn, "
    SELECT sc.*, t.name AS theatre_name, (SELECT COUNT(*) FROM seats WHERE screen_id = sc.id) as seat_count
    FROM screens sc JOIN theatres t ON sc.theatre_id = t.id ORDER BY t.name, sc.screen_name
");
$theatres = mysqli_query($conn, "SELECT * FROM theatres ORDER BY name");
$success = flash('success');

$pageTitle = "Manage Screens";
$activePage = "screens";
include 'includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success auto-dismiss"><?= sanitize($success) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">Add New Screen</h6>
            <div class="alert alert-info small">Seats are auto-generated based on rows &times; seats-per-row. This cannot be edited later — delete and recreate the screen if you need to change layout.</div>
            <form method="POST">
                <div class="mb-2">
                    <label class="form-label small">Theatre</label>
                    <select name="theatre_id" class="form-select form-select-sm" required>
                        <option value="">Select Theatre</option>
                        <?php mysqli_data_seek($theatres, 0); while ($t = mysqli_fetch_assoc($theatres)): ?>
                            <option value="<?= $t['id'] ?>"><?= sanitize($t['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Screen Name</label>
                    <input type="text" name="screen_name" class="form-control form-control-sm" placeholder="Screen 1" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-2">
                        <label class="form-label small">Rows (max 10)</label>
                        <input type="number" name="rows_count" class="form-control form-control-sm" value="5" min="1" max="10">
                    </div>
                    <div class="col-6 mb-2">
                        <label class="form-label small">Seats/Row (max 20)</label>
                        <input type="number" name="seats_per_row" class="form-control form-control-sm" value="8" min="1" max="20">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Premium Rows (front rows, e.g. 2)</label>
                    <input type="number" name="premium_rows" class="form-control form-control-sm" value="2" min="0">
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">Create Screen &amp; Generate Seats</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">All Screens</h6>
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead><tr><th>Theatre</th><th>Screen</th><th>Layout</th><th>Total Seats</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($s = mysqli_fetch_assoc($screens)): ?>
                        <tr>
                            <td><?= sanitize($s['theatre_name']) ?></td>
                            <td><?= sanitize($s['screen_name']) ?></td>
                            <td class="small text-secondary"><?= (int)$s['rows_count'] ?> rows &times; <?= (int)$s['seats_per_row'] ?></td>
                            <td><?= (int)$s['seat_count'] ?></td>
                            <td>
                                <a href="screens.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this screen and its seat layout?');"><i class="fa-solid fa-trash"></i></a>
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
