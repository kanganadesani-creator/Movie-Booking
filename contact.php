<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if ($name && $email && $message) {
        $stmt = mysqli_prepare($conn, "INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $message);
        mysqli_stmt_execute($stmt);
        $success = "Thanks for reaching out! We'll get back to you soon.";
    }
}

$pageTitle = "Contact Us";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <h3 class="section-title">Contact Us</h3>
            <div class="cb-card p-4 p-md-5">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= sanitize($success) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Your Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Message</label>
                        <textarea name="message" rows="5" class="form-control" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
