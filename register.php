<!-- register.php -->
<?php
session_start();
require 'config/config.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize input data
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $role     = 'admin'; // Default role

    // Validate inputs
    $errors = [];

    if (empty($username)) {
        $errors[] = 'Username is required.';
    }

    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['avatar']['name'];
        $file_tmp  = $_FILES['avatar']['tmp_name'];
        $file_size = $_FILES['avatar']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file extension
        if (!in_array($file_ext, $allowed_ext)) {
            $errors[] = 'Invalid avatar file type. Allowed types: jpg, jpeg, png, gif.';
        }

        // Validate file size (e.g., max 2MB)
        if ($file_size > 2 * 1024 * 1024) {
            $errors[] = 'Avatar file size exceeds 2MB.';
        }

        // Set upload path
        $upload_dir = 'assets/avatars/';
        $new_file_name = uniqid('avatar_', true) . '.' . $file_ext;
        $upload_path = $upload_dir . $new_file_name;

        // Move uploaded file
        if (empty($errors)) {
            if (!move_uploaded_file($file_tmp, $upload_path)) {
                $errors[] = 'Failed to upload avatar.';
            } else {
                // Optionally, resize the avatar
                // resizeImage($upload_path, $upload_path, 150, 150);
            }
        }
    } else {
        // Use default avatar if no file uploaded
        $new_file_name = 'default_avatar.png';
    }

    // If no errors, proceed to insert user into database
    if (empty($errors)) {
        // Check if username or email already exists
        $stmt = $pdo->prepare('SELECT user_id FROM Users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert user into database
            $stmt = $pdo->prepare('INSERT INTO Users (username, password, email, phone, avatar, role) VALUES (?, ?, ?, ?, ?, ?)');
            $result = $stmt->execute([$username, $hashed_password, $email, $phone, $new_file_name, $role]);

            if ($result) {
                $_SESSION['success'] = 'Registration successful! You can now log in.';
                header('Location: login.php');
                exit();
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Eden Pro</title>
    <!-- Include AdminLTE CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
</head>
<body class="hold-transition dark-mode register-page">
    <div class="card">
        <div class="card-body card-body card-outline card-olive accent-olive register-card-body">
            <div class="register-box">
                <div class="register-logo">
                    <img src='/dist/img/eden.jpg' alt='Logo' class='img-thumbnail' style='max-width: 80px; max-height: 80px;'>
                    <a href="index.php"><b>EDEN</b> PRO</a>
                </div>

        
                <p class="login-box-msg">Register a new membership</p>

                <?php
                // Display errors
                if (!empty($errors)) {
                    echo '<div class="alert alert-danger">';
                    foreach ($errors as $error) {
                        echo '<p>' . htmlspecialchars($error) . '</p>';
                    }
                    echo '</div>';
                }

                // Display success message
                if (isset($_SESSION['success'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
                    unset($_SESSION['success']);
                }
                ?>

                <form action="register.php" method="POST" enctype="multipart/form-data">
                    <div class="input-group mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="confirm_password" class="form-control" placeholder="Retype password" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <!-- Additional Fields -->
                    <div class="input-group mb-3">
                        <input type="text" name="phone" class="form-control" placeholder="Phone">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-phone"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="input-group mb-3">
                        <div class="custom-file">
                            <input type="file" name="avatar" class="custom-file-input" id="avatar">
                            <label class="custom-file-label" for="avatar">Choose avatar</label>
                        </div>
                    </div>
                    <div class="row">
                        <!-- /.col -->
                        <div class="col-12">
                            <button type="submit" class="btn bg-olive btn-block">Register</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>

                <a href="login.php" class="text-center">I already have a membership</a>
            </div>
            <!-- /.form-box -->
        </div><!-- /.card -->
    </div>
    <!-- /.register-box -->

    <!-- Include AdminLTE JS -->
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
