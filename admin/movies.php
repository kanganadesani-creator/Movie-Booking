<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminLogin();

$errors = [];
$success = flash('success');
$editMovie = null;

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM movies WHERE id = $id");
    flash('success', 'Movie deleted successfully.');
    header("Location: movies.php");
    exit();
}

// Load for edit
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM movies WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $editMovie = mysqli_stmt_get_result($stmt)->fetch_assoc();
}

// Add / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $genre = sanitize($_POST['genre'] ?? '');
    $language = sanitize($_POST['language'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 0);
    $rating = (float)($_POST['rating'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $poster = sanitize($_POST['poster'] ?? '');
    $trailer = sanitize($_POST['trailer_link'] ?? '');
    $status = sanitize($_POST['status'] ?? 'now_showing');
    $movieId = (int)($_POST['movie_id'] ?? 0);

    if ($title === '') $errors[] = "Title is required.";
    if ($poster === '') $errors[] = "Poster URL is required.";

    if (empty($errors)) {
        if ($movieId > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE movies SET title=?, genre=?, language=?, duration_minutes=?, rating=?, description=?, poster=?, trailer_link=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssidssssi", $title, $genre, $language, $duration, $rating, $description, $poster, $trailer, $status, $movieId);
            mysqli_stmt_execute($stmt);
            flash('success', 'Movie updated successfully.');
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO movies (title, genre, language, duration_minutes, rating, description, poster, trailer_link, status) VALUES (?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, "sssidssss", $title, $genre, $language, $duration, $rating, $description, $poster, $trailer, $status);
            mysqli_stmt_execute($stmt);
            flash('success', 'Movie added successfully.');
        }
        header("Location: movies.php");
        exit();
    }
}

$movies = mysqli_query($conn, "SELECT * FROM movies ORDER BY created_at DESC");

$pageTitle = "Manage Movies";
$activePage = "movies";
include 'includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success auto-dismiss"><?= sanitize($success) ?></div><?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo "<li>".sanitize($e)."</li>"; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3"><?= $editMovie ? 'Edit Movie' : 'Add New Movie' ?></h6>
            <form method="POST">
                <input type="hidden" name="movie_id" value="<?= $editMovie['id'] ?? 0 ?>">
                <div class="mb-2">
                    <label class="form-label small">Title</label>
                    <input type="text" name="title" class="form-control form-control-sm" value="<?= sanitize($editMovie['title'] ?? '') ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Genre</label>
                    <input type="text" name="genre" class="form-control form-control-sm" placeholder="Action, Drama" value="<?= sanitize($editMovie['genre'] ?? '') ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Language</label>
                    <input type="text" name="language" class="form-control form-control-sm" value="<?= sanitize($editMovie['language'] ?? '') ?>">
                </div>
                <div class="row">
                    <div class="col-6 mb-2">
                        <label class="form-label small">Duration (min)</label>
                        <input type="number" name="duration_minutes" class="form-control form-control-sm" value="<?= sanitize($editMovie['duration_minutes'] ?? '') ?>">
                    </div>
                    <div class="col-6 mb-2">
                        <label class="form-label small">Rating (0-5)</label>
                        <input type="number" step="0.1" max="5" min="0" name="rating" class="form-control form-control-sm" value="<?= sanitize($editMovie['rating'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Poster Image URL</label>
                    <input type="text" name="poster" class="form-control form-control-sm" placeholder="https://..." value="<?= sanitize($editMovie['poster'] ?? '') ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Trailer Embed URL (optional)</label>
                    <input type="text" name="trailer_link" class="form-control form-control-sm" placeholder="https://www.youtube.com/embed/..." value="<?= sanitize($editMovie['trailer_link'] ?? '') ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Description</label>
                    <textarea name="description" rows="3" class="form-control form-control-sm"><?= sanitize($editMovie['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach (['now_showing'=>'Now Showing','coming_soon'=>'Coming Soon','ended'=>'Ended'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= (($editMovie['status'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100"><?= $editMovie ? 'Update Movie' : 'Add Movie' ?></button>
                <?php if ($editMovie): ?><a href="movies.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">Cancel Edit</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="cb-card p-4">
            <h6 class="fw-bold mb-3">All Movies (<?= mysqli_num_rows($movies) ?>)</h6>
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead><tr><th>Poster</th><th>Title</th><th>Genre</th><th>Status</th><th>Rating</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($m = mysqli_fetch_assoc($movies)): ?>
                        <tr>
                            <td><img src="<?= sanitize($m['poster']) ?>" style="width:40px;height:55px;object-fit:cover;border-radius:4px;"></td>
                            <td><?= sanitize($m['title']) ?></td>
                            <td class="small text-secondary"><?= sanitize($m['genre']) ?></td>
                            <td><span class="badge bg-secondary"><?= sanitize($m['status']) ?></span></td>
                            <td><i class="fa-solid fa-star text-warning"></i> <?= sanitize($m['rating']) ?></td>
                            <td>
                                <a href="movies.php?edit=<?= $m['id'] ?>" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-pen"></i></a>
                                <a href="movies.php?delete=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this movie? This will also remove related shows.');"><i class="fa-solid fa-trash"></i></a>
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
