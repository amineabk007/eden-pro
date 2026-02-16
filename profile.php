<?php
// profile.php
session_start();
require 'config/config.php';

// Check if the user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch user data from the database
$stmt = $pdo->prepare('SELECT username, email, phone, address, avatar, password FROM Users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $errors[] = 'User not found.';
    $username = $email = $phone = $address = $avatar = '';
} else {
    $username = $user['username'];
    $email = $user['email'];
    $phone = $user['phone'];
    $address = $user['address'];
    $avatar = $user['avatar'];
    $hashed_password = $user['password'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Sanitize inputs
        $new_username = trim($_POST['username']);
        $new_email = trim($_POST['email']);
        $new_phone = trim($_POST['phone']);
        $new_address = trim($_POST['address']);

        // Validate inputs
        if (empty($new_username)) {
            $errors[] = 'Username is required.';
        }

        if (empty($new_email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }

        // Avatar handling
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_tmp = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_size = $_FILES['avatar']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        $valid_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($mime_type, $valid_mime_types) || !in_array($file_ext, $allowed_ext)) {
            $errors[] = 'Invalid avatar file type.';
        }

        // Validate file size (max 2MB)
        if ($file_size > 2 * 1024 * 1024) {
            $errors[] = 'Avatar file size exceeds 2MB.';
        }

        if (empty($errors)) {
            // Define upload path
            $upload_dir = 'assets/avatars/';
            $new_file_name = uniqid('avatar_', true) . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Delete old avatar if not default
                if ($avatar !== 'default_avatar.png' && file_exists($upload_dir . $avatar)) {
                    unlink($upload_dir . $avatar);
                }
                $avatar = $new_file_name;
                $_SESSION['avatar'] = $avatar;
            } else {
                $errors[] = 'Failed to upload avatar.';
            }
        }
    }

        // If no errors, update profile details in the database
        if (empty($errors)) {
            // Check for duplicate username or email
            $stmt = $pdo->prepare('SELECT user_id FROM Users WHERE (username = ? OR email = ?) AND user_id != ?');
            $stmt->execute([$new_username, $new_email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists.';
            } else {
                $stmt = $pdo->prepare('UPDATE Users SET username = ?, email = ?, phone = ?, address = ?, avatar = ? WHERE user_id = ?');
                if ($stmt->execute([$new_username, $new_email, $new_phone, $new_address, $avatar, $user_id])) {
                    $success = 'Profile updated successfully.';
                    $_SESSION['username'] = $new_username;
                } else {
                    $errors[] = 'Failed to update profile. Please try again.';
                }
            }
        }
    }

    if (isset($_POST['change_password'])) {
        // Change password logic
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        if (!password_verify($current_password, $hashed_password)) {
            $errors[] = 'Current password is incorrect.';
        }

        // Validate new password
        if (empty($new_password)) {
            $errors[] = 'New password is required.';
        } elseif (strlen($new_password) < 4) {
            $errors[] = 'Password must be at least 4 characters long.';
        }

        if ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirmation do not match.';
        }

        // Update password in the database
        if (empty($errors)) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE Users SET password = ? WHERE user_id = ?');
            if ($stmt->execute([$new_hashed_password, $user_id])) {
                $success = 'Password updated successfully.';
            } else {
                $errors[] = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - Eden</title>
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
    
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <?php include 'includes/navbar.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">My Profile</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active">Profile</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <section class="content">
                <div class="container-fluid">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Update Profile</h3>
                        </div>
                        <form action="profile.php" method="POST" enctype="multipart/form-data">
                            <div class="card-body">
                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger">
                                        <?php foreach ($errors as $error): ?>
                                            <p><?= htmlspecialchars($error) ?></p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($success)): ?>
                                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" name="username" class="form-control" id="username" value="<?= htmlspecialchars($username) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" class="form-control" id="email" value="<?= htmlspecialchars($email) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="text" name="phone" class="form-control" id="phone" value="<?= htmlspecialchars($phone) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea name="address" class="form-control" id="address"><?= htmlspecialchars($address ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="avatar">Avatar</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" name="avatar" class="custom-file-input" id="avatar" accept="image/*">
                                            <label class="custom-file-label" for="avatar">Choose file</label>
                                        </div>
                                    </div>
                                    <img src="assets/avatars/<?= htmlspecialchars($avatar) ?>" alt="Current Avatar" class="img-thumbnail mt-2" style="width: 150px; height: 150px;">
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>

                    <div class="card card-outline card-warning mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Change Password</h3>
                        </div>
                        <form action="profile.php" method="POST">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" id="current_password" required>
                                </div>
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" name="new_password" class="form-control" id="new_password" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
        <?php include 'includes/footer.php'; ?>
    </div>
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>
    <!-- Include bs-custom-file-input for custom file input fields -->
    <script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
    <script>
        $(document).ready(function () {
            bsCustomFileInput.init()
        })
    </script>
</body>
</html>
