<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$theatres = mysqli_query($conn, "SELECT * FROM theatres ORDER BY name");

$pageTitle = "Theatres";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <h3 class="section-title">Our Theatres</h3>
    <div class="row g-4">
        <?php while ($t = mysqli_fetch_assoc($theatres)):
            // count today's shows for this theatre
            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM shows WHERE theatre_id = ? AND show_date >= CURDATE()");
            mysqli_stmt_bind_param($stmt, "i", $t['id']);
            mysqli_stmt_execute($stmt);
            $cnt = mysqli_stmt_get_result($stmt)->fetch_assoc()['cnt'];
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="cb-card p-4 h-100">
                    <h5 class="fw-semibold"><i class="fa-solid fa-location-dot text-warning"></i> <?= sanitize($t['name']) ?></h5>
                    <p class="text-secondary mb-1"><?= sanitize($t['address']) ?></p>
                    <p class="text-secondary small mb-3"><?= sanitize($t['city']) ?></p>
                    <span class="badge bg-secondary"><?= (int)$cnt ?> upcoming shows</span>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
