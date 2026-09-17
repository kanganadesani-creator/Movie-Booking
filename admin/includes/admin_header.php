<?php
// Expects $activePage to be set by the including page, e.g. 'dashboard', 'movies', etc.
if (!isset($activePage)) $activePage = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? 'Admin') ?> | CineBook Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="admin-sidebar" style="width:240px; flex-shrink:0;">
        <div class="p-3 mb-2">
            <a href="dashboard.php" class="navbar-brand"><i class="fa-solid fa-film"></i> Cine<span class="text-warning">Book</span></a>
            <p class="small text-secondary mb-0">Admin Panel</p>
        </div>
        <nav>
            <a href="dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a>
            <a href="movies.php" class="<?= $activePage === 'movies' ? 'active' : '' ?>"><i class="fa-solid fa-clapperboard me-2"></i> Movies</a>
            <a href="theatres.php" class="<?= $activePage === 'theatres' ? 'active' : '' ?>"><i class="fa-solid fa-building me-2"></i> Theatres</a>
            <a href="screens.php" class="<?= $activePage === 'screens' ? 'active' : '' ?>"><i class="fa-solid fa-tv me-2"></i> Screens</a>
            <a href="shows.php" class="<?= $activePage === 'shows' ? 'active' : '' ?>"><i class="fa-solid fa-calendar-days me-2"></i> Shows</a>
            <a href="bookings.php" class="<?= $activePage === 'bookings' ? 'active' : '' ?>"><i class="fa-solid fa-ticket me-2"></i> Bookings</a>
            <a href="payments.php" class="<?= $activePage === 'payments' ? 'active' : '' ?>"><i class="fa-solid fa-credit-card me-2"></i> Payments</a>
            <a href="users.php" class="<?= $activePage === 'users' ? 'active' : '' ?>"><i class="fa-solid fa-users me-2"></i> Users</a>
            <a href="reports.php" class="<?= $activePage === 'reports' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line me-2"></i> Reports</a>
            <hr class="border-secondary mx-3">
            <a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a>
        </nav>
    </div>

    <!-- Main content -->
    <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom" style="border-color:var(--cb-border) !important;">
            <h5 class="mb-0"><?= sanitize($pageTitle ?? '') ?></h5>
            <div class="text-secondary small"><i class="fa-solid fa-circle-user"></i> <?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?></div>
        </div>
        <div class="p-4">
