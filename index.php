<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$movies = mysqli_query($conn, "SELECT * FROM movies WHERE status = 'now_showing' ORDER BY created_at DESC LIMIT 8");
$comingSoon = mysqli_query($conn, "SELECT * FROM movies WHERE status = 'coming_soon' ORDER BY created_at DESC LIMIT 4");

$pageTitle = "Home";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Hero -->
<section class="cb-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1>Book Your Next <span class="highlight">Movie Night</span> in Seconds</h1>
                <p class="text-secondary fs-5 mt-3">Browse now showing movies, pick your seats, and get your e-ticket instantly. No queues, no hassle.</p>
                <a href="movies.php" class="btn btn-primary btn-lg mt-3 px-4"><i class="fa-solid fa-ticket"></i> Browse Movies</a>
            </div>
        </div>
    </div>
</section>

<!-- Search -->
<div class="container mt-4">
    <form action="movies.php" method="GET" class="cb-card p-3 d-flex flex-column flex-md-row gap-2">
        <input type="text" name="search" class="form-control" placeholder="Search movies by title...">
        <select name="genre" class="form-select" style="max-width:200px">
            <option value="">All Genres</option>
            <option>Action</option>
            <option>Drama</option>
            <option>Sci-Fi</option>
            <option>Thriller</option>
            <option>Comedy</option>
            <option>Horror</option>
            <option>Adventure</option>

        </select>
        <select name="language" class="form-select" style="max-width:200px">
            <option value="">All Languages</option>
            <option>Hindi</option>
            <option>English</option>
        </select>
        <button class="btn btn-warning px-4"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </form>
</div>

<!-- Now Showing -->
<div class="container mt-5">
    <h3 class="section-title">Now Showing</h3>
    <div class="row g-4">
        <?php if (mysqli_num_rows($movies) === 0): ?>
            <p class="text-secondary">No movies available right now. Please check back soon.</p>
        <?php endif; ?>
        <?php while ($m = mysqli_fetch_assoc($movies)): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="movie-details.php?id=<?= $m['id'] ?>" class="text-decoration-none text-reset">
                    <div class="movie-card">
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

<!-- Coming Soon -->
<?php if (mysqli_num_rows($comingSoon) > 0): ?>
<div class="container mt-5">
    <h3 class="section-title">Coming Soon</h3>
    <div class="row g-4">
        <?php while ($m = mysqli_fetch_assoc($comingSoon)): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="movie-card">
                    <img src="<?= sanitize($m['poster']) ?>" alt="<?= sanitize($m['title']) ?>">
                    <div class="card-body p-3">
                        <h6 class="mb-1 fw-semibold"><?= sanitize($m['title']) ?></h6>
                        <p class="small text-secondary mb-0"><?= sanitize($m['genre']) ?></p>
                        <span class="badge bg-secondary mt-2">Coming Soon</span>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
