<?php

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: movies.php");
    exit();
}

$movieId = (int)($_POST['movie_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

$userId = (int)($_SESSION['user_id'] ?? 0);

// Validate
if ($movieId <= 0 || $userId <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    header("Location: movie-details.php?id=" . $movieId);
    exit();
}

// Check if user already reviewed this movie
$checkStmt = mysqli_prepare($conn, "
    SELECT id 
    FROM reviews 
    WHERE movie_id = ? AND user_id = ?
");

mysqli_stmt_bind_param($checkStmt, "ii", $movieId, $userId);
mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) > 0) {
    flash('error', 'You have already reviewed this movie.');
    header("Location: movie-details.php?id=" . $movieId);
    exit();
}
// Save review
$stmt = mysqli_prepare($conn, "
    INSERT INTO reviews (movie_id, user_id, rating, comment, created_at)
    VALUES (?, ?, ?, ?, NOW())
");

mysqli_stmt_bind_param(
    $stmt,
    "iiis",
    $movieId,
    $userId,
    $rating,
    $comment
);

mysqli_stmt_execute($stmt);

// Go back to movie page
header("Location: movie-details.php?id=" . $movieId);
exit();