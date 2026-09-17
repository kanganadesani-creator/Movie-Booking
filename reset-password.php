<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$token = sanitize($_GET['token'] ?? $_POST['token'] ?? '');
$error = null;
$errors = [];

$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    $error = "This reset link is invalid or has expired.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $upd = mysqli_prepare($conn, "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        mysqli_stmt_bind_param($upd, "si", $hashed, $user['id']);
        mysqli_stmt_execute($upd);
        flash('success', 'Password reset successfully! Please log in.');
        header("Location: login.php");
        exit();
    }
}

$pageTitle = "Reset Password";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="cb-card p-4 p-md-5">
                <h3 class="mb-1 fw-bold text-center">Reset Password</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger mt-3"><?= sanitize($error) ?></div>
                    <p class="text-center"><a href="forgot-password.php" class="text-warning">Request a new link</a></p>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>" . sanitize($e) . "</li>"; ?></ul>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="token" value="<?= sanitize($token) ?>">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Reset Password</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
