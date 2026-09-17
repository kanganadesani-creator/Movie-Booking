<?php
// ==========================================
// Database Configuration
// Update these if your XAMPP MySQL settings differ
// ==========================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cinebook');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error() .
        "<br><br>Make sure: 1) MySQL is running in XAMPP, 2) You imported database.sql, 3) DB credentials above are correct.");
}

// Base URL of the project - change 'movie_booking' if your folder name is different
define('BASE_URL', 'http://localhost/movie_booking/');
