<nav class="navbar navbar-expand-lg navbar-dark cb-navbar sticky-top">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="<?= BASE_URL ?>index.php">
            <i class="fa-solid fa-film"></i>
            Cine<span class="text-warning">Book</span>
        </a>
        <!-- Mobile Menu Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">

                <!-- Home -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>index.php">Home </a>
                </li>

                <!-- Movies -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>movies.php">Movies</a>
                </li>
                       
                <!-- Theatres -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>theatres.php">Theatres</a>
                </li>
                        
                <!-- About -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>about.php">About</a>
                </li>
          
                <!-- Contact -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>contact.php">Contact </a>
                </li>

                <?php if (isLoggedIn()): ?>
                    <?php
                    $userImage = $_SESSION['user_image'] ?? '';
                    ?>

                    <!-- Logged-in User -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if (!empty($userImage)): ?>

                                <!-- User Uploaded Image -->
                                <img src="<?= BASE_URL ?>uploads/profiles/<?= sanitize($userImage) ?>" alt="Profile" style="
                                        width:32px;
                                        height:32px;
                                        object-fit:cover;
                                        border-radius:50%;
                                        margin-right:6px;
                                    " >

                            <?php else: ?>
                                <!-- No Image -->
                                <i class="fa-solid fa-circle-user" style=" font-size:32px; margin-right:6px;" ></i>
                                <?php endif; ?>
                                <?= sanitize($_SESSION['user_name'] ?? 'Account') ?>
                            </a>

                        <!-- Dropdown -->
                        <ul class="dropdown-menu dropdown-menu-end">

                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>user/profile.php">
                                    <i class="fa-solid fa-user me-2"></i>My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>user/bookings.php">
                                    <i class="fa-solid fa-ticket me-2"></i>My Bookings
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>

                    <!-- Login -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>login.php">Login
                        </a>
                    </li>
                        
                    <!-- Sign Up -->
                    <li class="nav-item">
                        <a class="btn btn-warning btn-sm px-3 fw-semibold" href="<?= BASE_URL ?>register.php">
                            Sign Up
                        </a>
                    </li>
                <?php endif; ?>

            </ul>

        </div>
    </div>
</nav>