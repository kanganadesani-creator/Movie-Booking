<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { header("Location: index.php"); exit(); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '') $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $errors[] = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $hashed);
            if (mysqli_stmt_execute($stmt)) {
                flash('success', 'Account created successfully! Please log in.');
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }
        }
    }
}

$pageTitle = "Register";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="cb-card p-4 p-md-5">
                <h3 class="mb-1 fw-bold text-center">Create Account</h3>
                <p class="text-secondary text-center mb-4">Join CineBook and start booking</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e) echo "<li>" . sanitize($e) . "</li>"; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= sanitize($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= sanitize($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign Up</button>
                </form>

                <p class="text-center mt-4 mb-0 text-secondary">
                    Already have an account? <a href="login.php" class="text-warning">Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>