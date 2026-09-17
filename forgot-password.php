<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$message = null;
$resetLink = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $upd = mysqli_prepare($conn, "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "ssi", $token, $expires, $user['id']);
        mysqli_stmt_execute($upd);

        $resetLink = BASE_URL . "reset-password.php?token=" . $token;
        $message = "Since this project doesn't have a live email server configured, here is your reset link (in a real deployment this would be emailed to you):";
    } else {
        $error = "No account found with that email address.";
    }
}

$pageTitle = "Forgot Password";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="cb-card p-4 p-md-5">
                <h3 class="mb-1 fw-bold text-center">Forgot Password</h3>
                <p class="text-secondary text-center mb-4">Enter your email to reset your password</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="alert alert-info">
                        <?= sanitize($message) ?>
                        <div class="mt-2">
                            <a href="<?= sanitize($resetLink) ?>" class="fw-semibold text-warning"><?= sanitize($resetLink) ?></a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Send Reset Link</button>
                    </form>
                <?php endif; ?>

                <p class="text-center mt-4 mb-0 text-secondary">
                    <a href="login.php" class="text-warning">Back to Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
