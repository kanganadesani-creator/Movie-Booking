<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// India timezone
date_default_timezone_set('Asia/Kolkata');

$id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT * FROM movies WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$movie = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$movie) {
    header("Location: movies.php");
    exit();
}


// ==========================================
// Get reviews for this movie
// ==========================================
$reviewStmt = mysqli_prepare($conn, "
    SELECT 
        r.rating,
        r.comment,
        r.created_at,
        u.name AS user_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.movie_id = ?
    ORDER BY r.created_at DESC
");

mysqli_stmt_bind_param($reviewStmt, "i", $id);
mysqli_stmt_execute($reviewStmt);
$reviewsResult = mysqli_stmt_get_result($reviewStmt);


// ==========================================
// Get shows for this movie
// ==========================================
$showsStmt = mysqli_prepare($conn, "
    SELECT 
        sh.id,
        sh.show_date,
        sh.show_time,
        sh.price,
        t.name AS theatre_name,
        t.city,
        t.id AS theatre_id
    FROM shows sh
    JOIN theatres t ON sh.theatre_id = t.id
    WHERE sh.movie_id = ?
      AND sh.show_date >= CURDATE()
    ORDER BY sh.show_date, t.name, sh.show_time
");

mysqli_stmt_bind_param($showsStmt, "i", $id);
mysqli_stmt_execute($showsStmt);
$showsResult = mysqli_stmt_get_result($showsStmt);


// ==========================================
// Group shows by theatre + date
// ==========================================
$grouped = [];

while ($row = mysqli_fetch_assoc($showsResult)) {

    $key = $row['theatre_id'] . '_' . $row['show_date'];

    $grouped[$key]['theatre_name'] = $row['theatre_name'];
    $grouped[$key]['city'] = $row['city'];
    $grouped[$key]['date'] = $row['show_date'];
    $grouped[$key]['shows'][] = $row;
}


$pageTitle = $movie['title'];

include 'includes/header.php';
include 'includes/navbar.php';
?>


<div class="container py-5">

    <div class="row g-4">

        <!-- Movie Poster -->
        <div class="col-md-4">

            <img
                src="<?= sanitize($movie['poster']) ?>"
                class="w-100 rounded-3"
                alt="<?= sanitize($movie['title']) ?>"
            >

        </div>


        <!-- Movie Details -->
        <div class="col-md-8">

            <h2 class="fw-bold">
                <?= sanitize($movie['title']) ?>
            </h2>


            <div class="mb-3">

                <span class="badge badge-rating me-2">

                    <i class="fa-solid fa-star"></i>

                    <?= sanitize($movie['rating']) ?>

                </span>

                <span class="text-secondary">

                    <?= sanitize($movie['genre']) ?>

                    &bull;

                    <?= sanitize($movie['language']) ?>

                    &bull;

                    <?= (int)$movie['duration_minutes'] ?> min

                </span>

            </div>


            <p class="text-secondary">

                <?= nl2br(sanitize($movie['description'])) ?>

            </p>


            <!-- Trailer -->
            <?php if (!empty($movie['trailer_link'])): ?>

                <button
                    class="btn btn-outline-warning mb-4"
                    data-bs-toggle="modal"
                    data-bs-target="#trailerModal"
                >

                    <i class="fa-solid fa-play"></i>

                    Watch Trailer

                </button>

            <?php endif; ?>


            <!-- Showtime -->
            <h5 class="section-title">
                Select Showtime
            </h5>


            <?php if (empty($grouped)): ?>

                <p class="text-secondary">

                    No shows scheduled currently

                    <?= $movie['status'] === 'coming_soon'
                        ? ' — this movie is coming soon!'
                        : '.'
                    ?>

                </p>

            <?php else: ?>
                <?php foreach ($grouped as $g): ?>

                    <div class="cb-card p-3 mb-3">
                        <!-- Theatre + Date -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">

                            <div>

                                <strong>
                                    <?= sanitize($g['theatre_name']) ?>
                                </strong>
                                <span class="text-secondary small">
                                    &bull;
                                    <?= sanitize($g['city']) ?>

                                </span>

                            </div>
                            <span class="badge bg-secondary">

                                <?= date(
                                    'D, d M Y',
                                    strtotime($g['date'])
                                ) ?>

                            </span>

                        </div>


                        <!-- Show Times -->
                        <div class="d-flex flex-wrap gap-2">


                            <?php foreach ($g['shows'] as $s): ?>
                                <?php
                                // ==========================================
                                // Create show datetime in India timezone
                                // ==========================================
                                $showDateTime = new DateTime(
                                    $s['show_date'] . ' ' . $s['show_time'],
                                    new DateTimeZone('Asia/Kolkata')
                                );
                                // Current India time
                                $currentDateTime = new DateTime(
                                    'now',
                                    new DateTimeZone('Asia/Kolkata')
                                );

                                // Check if show time is completed
                                $showExpired = $showDateTime <= $currentDateTime;
                                ?>
                                <?php if (!$showExpired): ?>
                                    <!-- FUTURE SHOW -->
                                    <a
                                        href="seat-selection.php?show_id=<?= (int)$s['id'] ?>"
                                        class="btn btn-outline-light btn-sm"
                                    >

                                        <?= date(
                                            'h:i A',
                                            strtotime($s['show_time'])
                                        ) ?>

                                        &middot;

                                        ₹<?= number_format(
                                            $s['price'],
                                            0
                                        ) ?>

                                    </a>


                                <?php else: ?>

                                    <!-- EXPIRED SHOW -->
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm"
                                        disabled
                                    >

                                        <?= date(
                                            'h:i A',
                                            strtotime($s['show_time'])
                                        ) ?>

                                        &middot;

                                        Show Ended

                                    </button>

                                <?php endif; ?>


                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
    <!-- ==========================================
         Review Form
    ========================================== -->

    <?php if (isLoggedIn()): ?>
        <div class="cb-card p-4 mt-4">
            <h4 class="fw-bold mb-3">
                <i class="fa-solid fa-pen text-warning"></i>
                Write a Review
            </h4>

            <form method="POST" action="review-submit.php">
                <input type="hidden" name="movie_id"
                    value="<?= (int)$movie['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">
                        Rating
                    </label>
                    <select name="rating"class="form-select" required>
                        <option value="">
                            Select Rating
                        </option>

                        <option value="5">
                            ⭐⭐⭐⭐⭐ - Excellent
                        </option>

                        <option value="4">
                            ⭐⭐⭐⭐ - Very Good
                        </option>

                        <option value="3">
                            ⭐⭐⭐ - Good
                        </option>

                        <option value="2">
                            ⭐⭐ - Average
                        </option>

                        <option value="1">
                            ⭐ - Poor
                        </option>

                    </select>

                </div>
                <div class="mb-3">

                    <label class="form-label">
                        Your Comment
                    </label>
                    <textarea
                        name="comment"
                        class="form-control"
                        rows="4"
                        placeholder="Write your review..."
                        required></textarea>
                </div>
                <button
                    type="submit"
                    class="btn btn-warning"
                >

                    <i class="fa-solid fa-paper-plane me-1"></i>

                    Submit Review

                </button>

            </form>

        </div>
    <?php else: ?>
        <div class="cb-card p-3 mt-4 text-center">

            <p class="mb-0 text-secondary">

                Please login to write a review.

            </p>

        </div>
    <?php endif; ?>
    <!-- ==========================================
         Reviews Section
    ========================================== -->

    <div class="cb-card p-4 mt-4">

        <h4 class="fw-bold mb-4">
            <i class="fa-solid fa-star text-warning"></i>
            Movie Reviews
        </h4>
        <?php if (mysqli_num_rows($reviewsResult) > 0): ?>
            <?php while ($review = mysqli_fetch_assoc($reviewsResult)): ?>
                <div class="border-bottom border-secondary pb-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>
                            <i class="fa-solid fa-circle-user me-2"></i>
                            <?= sanitize($review['user_name']) ?>
                        </strong>
                        <span class="text-warning">
                            <?= str_repeat(
                                '★',
                                (int)$review['rating']
                            ) ?>
                            <?= str_repeat(
                                '☆',
                                5 - (int)$review['rating']
                            ) ?>
                        </span>

                    </div>
                    <p class="text-secondary mt-2 mb-1">

                        <?= nl2br(
                            sanitize($review['comment'])
                        ) ?>

                    </p>
                    <small class="text-secondary">

                        <?= date(
                            'd M Y',
                            strtotime($review['created_at'])
                        ) ?>

                    </small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-secondary mb-0">
                No reviews yet.
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- ==========================================
     Trailer Modal
========================================== -->

<div
    class="modal fade"
    id="trailerModal"
    tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">
                    Trailer -
                    <?= sanitize($movie['title']) ?>

                </h5>
                <button
                class="btn-close btn-close-white"
                data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body ratio ratio-16x9">
                <iframe
                    src="<?= sanitize($movie['trailer_link']) ?>"
                    allowfullscreen
                ></iframe>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>