<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$search = sanitize($_GET['search'] ?? '');
$genre = sanitize($_GET['genre'] ?? '');
$language = sanitize($_GET['language'] ?? '');

$sql = "SELECT * FROM movies WHERE status IN ('now_showing','coming_soon') AND 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}
if ($genre !== '') {
    $sql .= " AND genre LIKE ?";
    $params[] = "%$genre%";
    $types .= "s";
}
if ($language !== '') {
    $sql .= " AND language = ?";
    $params[] = $language;
    $types .= "s";
}
$sql .= " ORDER BY status ASC, created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$movies = mysqli_stmt_get_result($stmt);

$pageTitle = "Movies";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <h3 class="section-title">All Movies</h3>

    <form method="GET" class="cb-card p-3 d-flex flex-column flex-md-row gap-2 mb-4">
        <input type="text" name="search" class="form-control" placeholder="Search movies..." value="<?= sanitize($search) ?>">
        <select name="genre" class="form-select" style="max-width:200px">
            <option value="">All Genres</option>
            <?php foreach (['Action','Drama','Sci-Fi','Thriller','Comedy','Crime','Adventure'] as $g): ?>
                <option <?= $genre === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
        </select>
        <select name="language" class="form-select" style="max-width:200px">
            <option value="">All Languages</option>
            <?php foreach (['Hindi','English'] as $l): ?>
                <option <?= $language === $l ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-warning px-4"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="movies.php" class="btn btn-outline-secondary">Reset</a>
    </form>

    <div class="row g-4">
        <?php if (mysqli_num_rows($movies) === 0): ?>
            <p class="text-secondary">No movies match your search.</p>
        <?php endif; ?>
        <?php while ($m = mysqli_fetch_assoc($movies)): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="movie-details.php?id=<?= $m['id'] ?>" class="text-decoration-none text-reset">
                    <div class="movie-card position-relative">
                        <?php if ($m['status'] === 'coming_soon'): ?>
                            <span class="badge bg-secondary position-absolute m-2">Coming Soon</span>
                        <?php endif; ?>
                        <img src="<?= sanitize($m['poster']) ?>" alt="<?= sanitize($m['title']) ?>">
                        <div class="card-body p-3">
                            <h6 class="mb-1 fw-semibold"><?= sanitize($m['title']) ?></h6>
                            <p class="small text-secondary mb-2"><?= sanitize($m['genre']) ?> &bull; <?= sanitize($m['language']) ?></p>
                            <span class="badge badge-rating"><i class="fa-solid fa-star"></i> <?= sanitize($m['rating']) ?></span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
