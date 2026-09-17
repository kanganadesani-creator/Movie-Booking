<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (isAdminLoggedIn()) { header("Location: dashboard.php"); exit(); }

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT id, name, password FROM admins WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $admin = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | CineBook</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="d-flex align-items-center" style="min-height:100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="cb-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="fa-solid fa-user-shield fa-2x text-warning"></i>
                        <h4 class="fw-bold mt-2">Admin Login</h4>
                        <p class="text-secondary small">CineBook Administration Panel</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= sanitize($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Login</button>
                    </form>
                    <p class="text-center small text-secondary mt-4 mb-0">
                        Default: admin@cinebook.com / admin123
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
