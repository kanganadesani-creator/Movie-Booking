<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminLogin();
// ==========================================
// EDIT SHOW
// ==========================================
if (isset($_GET['edit'])) {

    $editId = (int)$_GET['edit'];

    $editStmt = mysqli_prepare($conn, "
        SELECT *
        FROM shows
        WHERE id = ?
    ");

    mysqli_stmt_bind_param($editStmt, "i", $editId);
    mysqli_stmt_execute($editStmt);

    $editResult = mysqli_stmt_get_result($editStmt);
    $editShow = mysqli_fetch_assoc($editResult);

    if (!$editShow) {
        flash('error', 'Show not found.');
        header("Location: shows.php");
        exit();
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM shows WHERE id = $id");
    flash('success', 'Show deleted successfully.');
    header("Location: shows.php");
    exit();
}
// ==========================================
// UPDATE SHOW
// ==========================================
if (isset($_POST['update_show'])) {

    $showId = (int)($_POST['show_id'] ?? 0);
    $movieId = (int)($_POST['movie_id'] ?? 0);
    $theatreId = (int)($_POST['theatre_id'] ?? 0);
    $screenId = (int)($_POST['screen_id'] ?? 0);

    $showDate = sanitize($_POST['show_date'] ?? '');
    $showTime = sanitize($_POST['show_time'] ?? '');
    $price = (float)($_POST['price'] ?? 0);

    if (
        $showId &&
        $movieId &&
        $theatreId &&
        $screenId &&
        $showDate &&
        $showTime &&
        $price > 0
    ) {

        $stmt = mysqli_prepare($conn, "
            UPDATE shows
            SET
                movie_id = ?,
                theatre_id = ?,
                screen_id = ?,
                show_date = ?,
                show_time = ?,
                price = ?
            WHERE id = ?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "iiissdi",
            $movieId,
            $theatreId,
            $screenId,
            $showDate,
            $showTime,
            $price,
            $showId
        );

        mysqli_stmt_execute($stmt);

        flash('success', 'Show updated successfully.');

    } else {

        flash('error', 'All fields are required.');
    }

    header("Location: shows.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movieId = (int)($_POST['movie_id'] ?? 0);
    $theatreId = (int)($_POST['theatre_id'] ?? 0);
    $screenId = (int)($_POST['screen_id'] ?? 0);
    $showDate = sanitize($_POST['show_date'] ?? '');
    $showTime = sanitize($_POST['show_time'] ?? '');
    $price = (float)($_POST['price'] ?? 0);

    if ($movieId && $theatreId && $screenId && $showDate && $showTime && $price > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO shows (movie_id, theatre_id, screen_id, show_date, show_time, price) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "iiissd", $movieId, $theatreId, $screenId, $showDate, $showTime, $price);
        mysqli_stmt_execute($stmt);
        flash('success', 'Show scheduled successfully.');
    } else {
        flash('error', 'All fields are required to schedule a show.');
    }
    header("Location: shows.php");
    exit();
}

$movies = mysqli_query($conn, "SELECT id, title FROM movies WHERE status != 'ended' ORDER BY title");
$theatres = mysqli_query($conn, "SELECT id, name FROM theatres ORDER BY name");
$screensAll = mysqli_query($conn, "SELECT id, theatre_id, screen_name FROM screens ORDER BY screen_name");
$screensByTheatre = [];
while ($sc = mysqli_fetch_assoc($screensAll)) {
    $screensByTheatre[$sc['theatre_id']][] = $sc;
}

$shows = mysqli_query($conn, "
    SELECT sh.*, m.title, t.name AS theatre_name, sc.screen_name
    FROM shows sh
    JOIN movies m ON sh.movie_id = m.id
    JOIN theatres t ON sh.theatre_id = t.id
    JOIN screens sc ON sh.screen_id = sc.id
    ORDER BY sh.show_date DESC, sh.show_time DESC
");

$success = flash('success');
$errorMsg = flash('error');

$pageTitle = "Manage Shows";
$activePage = "shows";
include 'includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success auto-dismiss"><?= sanitize($success) ?></div><?php endif; ?>
<?php if ($errorMsg): ?><div class="alert alert-danger"><?= sanitize($errorMsg) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="cb-card p-4">
        <h6 class="fw-bold mb-3"><?= isset($editShow) ? 'Edit Show' : 'Schedule New Show' ?></h6>
            <form method="POST">
            <?php if (isset($editShow)): ?><input type="hidden" name="show_id" value="<?= (int)$editShow['id'] ?>">
              <?php endif; ?>
                <div class="mb-2">
                    <label class="form-label small">Movie</label>
                    <select name="movie_id" class="form-select form-select-sm" required>
                        <option value="">Select Movie</option>
                        <?php mysqli_data_seek($movies, 0); while ($m = mysqli_fetch_assoc($movies)): ?>
                            <option value="<?= $m['id'] ?>"<?= isset($editShow) && $editShow['movie_id'] == $m['id'] ? 'selected' : '' ?>><?= sanitize($m['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Theatre</label>
                    <select name="theatre_id" id="theatreSelect" class="form-select form-select-sm" required onchange="updateScreens()">
                        <option value="">Select Theatre</option>
                        <?php mysqli_data_seek($theatres, 0); while ($t = mysqli_fetch_assoc($theatres)): ?>
                            <option value="<?= $t['id'] ?>"<?= isset($editShow) && $editShow['theatre_id'] == $t['id'] ? 'selected' : '' ?>><?= sanitize($t['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Screen</label>
                    <select name="screen_id" id="screenSelect" class="form-select form-select-sm" required>
                        <option value="">Select Theatre First</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Date</label>
                    <input type="date" name="show_date" class="form-control form-control-sm" min="<?= date('Y-m-d') ?>" value="<?= isset($editShow) ? htmlspecialchars($editShow['show_date']) : '' ?>"required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Time</label>
                    <input type="time" name="show_time" class="form-control form-control-sm" value="<?= isset($editShow) ? htmlspecialchars($editShow['show_time']) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Base Price (₹)</label>
                    <input type="number" name="price" class="form-control form-control-sm" min="1" step="0.01" value="<?= isset($editShow) ? htmlspecialchars($editShow['price']) : '' ?>"required>
                </div>
                <button type="submit" name="<?= isset($editShow) ? 'update_show' : '' ?>" value="<?= isset($editShow) ? '1' : '' ?>" class="btn btn-<?= isset($editShow) ? 'warning' : 'primary' ?> btn-sm w-100">
               <i class="fa-solid fa-<?= isset($editShow) ? 'pen' : 'plus' ?>"></i><?= isset($editShow) ? 'Update Show' : 'Schedule Show' ?></button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">All Shows</h6>
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead><tr><th>Movie</th><th>Theatre</th><th>Date</th><th>Time</th><th>Price</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (mysqli_num_rows($shows) === 0): ?>
                        <tr><td colspan="6" class="text-secondary text-center">No shows scheduled.</td></tr>
                    <?php endif; ?>
                    <?php while ($s = mysqli_fetch_assoc($shows)): ?>
                        <tr>
                            <td><?= sanitize($s['title']) ?></td>
                            <td class="small text-secondary"><?= sanitize($s['theatre_name']) ?> (<?= sanitize($s['screen_name']) ?>)</td>
                            <td><?= date('d M Y', strtotime($s['show_date'])) ?></td>
                            <td><?= date('h:i A', strtotime($s['show_time'])) ?></td>
                            <td>₹<?= number_format($s['price'], 0) ?></td>
                            <td><a href="shows.php?edit=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit Show">
                            <i class="fa-solid fa-pen"></i></a>
                            <!-- Delete Button -->
                            <a href="shows.php?delete=<?= (int)$s['id'] ?>"class="btn btn-sm btn-outline-danger"title="Delete Show"onclick="return confirm('Delete this show?');">
                            <i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<script>
const screensByTheatre = <?= json_encode($screensByTheatre) ?>;
function updateScreens(selectedScreenId = '') {

const theatreId =
    document.getElementById('theatreSelect').value;

const screenSelect =
    document.getElementById('screenSelect');

screenSelect.innerHTML = '';

if (!theatreId || !screensByTheatre[theatreId]) {

    screenSelect.innerHTML =
        '<option value="">No screens available</option>';

    return;
}

screensByTheatre[theatreId].forEach(sc => {

    const opt = document.createElement('option');

    opt.value = sc.id;
    opt.textContent = sc.screen_name;

    if (String(sc.id) === String(selectedScreenId)) {
        opt.selected = true;
    }

    screenSelect.appendChild(opt);
});
}
<?php if (isset($editShow)): ?>

updateScreens("<?= (int)$editShow['screen_id'] ?>");

<?php endif; ?>
</script>

<?php include 'includes/admin_footer.php'; ?>
