<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { header("Location: index.php"); exit(); }

$error = null;
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT id, name, password, status, profile_image FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'blocked') {
            $error = "Your account has been blocked. Please contact support.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_image'] = $user['profile_image'];
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit();
        }
    } else {
        $error = "Invalid email or password.";
    }
}

$pageTitle = "Login";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="cb-card p-4 p-md-5">
                <h3 class="mb-1 fw-bold text-center">Welcome Back</h3>
                <p class="text-secondary text-center mb-4">Login to continue booking</p>

                <?php if ($success): ?>
                    <div class="alert alert-success auto-dismiss"><?= sanitize($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="text-end mb-4">
                        <a href="forgot-password.php" class="small text-warning">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Login</button>
                </form>

                <p class="text-center mt-4 mb-0 text-secondary">
                    Don't have an account? <a href="register.php" class="text-warning">Sign Up</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
