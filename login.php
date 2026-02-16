<!-- login.php -->
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
    $password = $_POST['password'];

    // Validate inputs
    $errors = [];

    if (empty($username)) {
        $errors[] = 'Username is required.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    // If no errors, proceed to authenticate
    if (empty($errors)) {
        // Retrieve user from database
        $stmt = $pdo->prepare('SELECT * FROM Users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Authentication successful
                session_regenerate_id(true); // Prevent session fixation
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar'];

                // Redirect to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = 'Invalid username or password.';
            }
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Eden</title>
    <!-- Include AdminLTE CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
</head>
<body class="hold-transition dark-mode login-page">
<div class="card">
<div class="card-body card-outline card-olive accent-olive">
    <div class="login-box">
        <div class="login-logo">
            <img src='/dist/img/eden.jpg' alt='Logo' class='img-thumbnail' style='max-width: 80px; max-height: 80px;'>
            <a href="dashboard.php"><b>EDEN</b> PRO</a>
        </div>
        <!-- /.login-logo -->
        
                <p class="login-box-msg">Sign in to start your session</p>

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

                <form action="login.php" method="POST">
                    <div class="input-group mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
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
                    <div class="row">
                        <!-- /.col -->
                        <div class="col-12">
                            <button type="submit" class="btn bg-olive btn-block">Sign In</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>

                <p class="mb-1">
                    <a href="forgot-password.php">I forgot my password</a>
                </p>
                <p class="mb-0">
                    <a href="register.php" class="text-center">Register a new membership</a>
                </p>
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>
    <!-- /.login-box -->

    <!-- Include AdminLTE JS -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>
</body>
</html>
