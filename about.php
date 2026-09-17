<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
$pageTitle = "About Us";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h3 class="section-title">About CineBook</h3>
            <div class="cb-card p-4 p-md-5">
                <p class="text-secondary">
                    CineBook is a movie ticket booking web application built to make watching movies simple and hassle-free.
                    Users can browse now-showing and upcoming movies, pick a theatre and showtime, choose their preferred
                    seats from an interactive seat map, and complete their booking with a simulated payment flow —
                    receiving an instant e-ticket with a downloadable PDF.
                </p>
                <!-- <p class="text-secondary mb-0">
                    This project was developed as an academic assignment demonstrating full-stack web development
                    using PHP, MySQL, Bootstrap 5, and JavaScript, covering user authentication, dynamic database-driven
                    pages, and an administrative back-end for managing movies, theatres, shows, and bookings.
                </p> -->
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
