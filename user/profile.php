<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();

$userId = currentUserId();
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $profileImage = $_FILES['profile_image'] ?? null;
    if ($profileImage && $profileImage['error'] !== UPLOAD_ERR_NO_FILE) {
    $allowedTypes = ['image/jpeg', 'image/png'];
    if ($profileImage['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Error uploading profile image.";
    } elseif (!in_array($profileImage['type'], $allowedTypes)) {
        $errors[] = "Only JPG, JPEG and PNG images are allowed.";
    } elseif ($profileImage['size'] > 2 * 1024 * 1024) {
        $errors[] = "Profile image must be less than 2MB.";
    } else {
        $extension = strtolower(pathinfo($profileImage['name'], PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "Only JPG, JPEG and PNG images are allowed.";
        } else {

            $uploadDir = '../uploads/profiles/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = 'user_' . $userId . '_' . time() . '.' . $extension;

            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($profileImage['tmp_name'], $uploadPath)) {

                $updImage = mysqli_prepare(
                    $conn,
                    "UPDATE users SET profile_image = ? WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $updImage,
                    "si",
                    $fileName,
                    $userId
                );

                mysqli_stmt_execute($updImage);

                $user['profile_image'] = $fileName;

                $_SESSION['user_image'] = $fileName;

            } else {
                $errors[] = "Failed to upload profile image.";
            }
        }
    }
}

    if ($name === '') $errors[] = "Name is required.";

    if (empty($errors)) {
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                $errors[] = "New password must be at least 6 characters.";
            } else {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $upd = mysqli_prepare($conn, "UPDATE users SET name = ?, phone = ?, password = ? WHERE id = ?");
                mysqli_stmt_bind_param($upd, "sssi", $name, $phone, $hashed, $userId);
                mysqli_stmt_execute($upd);
            }
        } else {
            $upd = mysqli_prepare($conn, "UPDATE users SET name = ?, phone = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "ssi", $name, $phone, $userId);
            mysqli_stmt_execute($upd);
        }

        if (empty($errors)) {
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully.";
            $user['name'] = $name;
            $user['phone'] = $phone;
        }
    }
}

$pageTitle = "My Profile";
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="cb-card p-4 p-md-5">
                <h4 class="fw-bold mb-4"><i class="fa-solid fa-circle-user text-warning"></i> My Profile</h4>

                <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo "<li>".sanitize($e)."</li>"; ?></ul></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= sanitize($user['phone']) ?>">
                    </div>
                    <div class="mb-3"> 
                        <label class="form-label">Profile Image</label> 
                    <input type="file"  name="profile_image"  class="form-control" accept=".jpg,.jpeg,.png">
                        <div class="form-text">
                           Allowed formats: JPG, JPEG, PNG
                          </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">New Password <span class="text-secondary small">(leave blank to keep current)</span></label>
                        <input type="password" name="new_password" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
