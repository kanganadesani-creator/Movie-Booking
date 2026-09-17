    <footer class="cb-footer mt-5">
        <div class="container py-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5><i class="fa-solid fa-film text-warning"></i> CineBook</h5>
                    <p class="text-secondary small">Book your favorite movies in just a few clicks. Fast, simple, and secure ticket booking.</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-warning">Quick Links</h6>
                    <ul class="list-unstyled small">
                        <li><a href="<?= BASE_URL ?>movies.php">Movies</a></li>
                        <li><a href="<?= BASE_URL ?>theatres.php">Theatres</a></li>
                        <li><a href="<?= BASE_URL ?>about.php">About Us</a></li>
                        <li><a href="<?= BASE_URL ?>contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-warning">Follow Us</h6>
                    <div class="d-flex gap-3 fs-5">
                        <a href="#"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#"><i class="fa-brands fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <hr class="border-secondary">
            <p class="text-center small text-secondary mb-0">&copy; <?= date('Y') ?> CineBook. All Rights Reserved.</p>
        </div>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/script.js"></script>
</body>
</html>
