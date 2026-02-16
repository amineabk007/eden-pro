<?php
session_start();
require '../config/config.php';

$response = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize input data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $address = 'No Address';
    $role = trim($_POST['role']); // Default role

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

    // Avatar upload logic
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['avatar']['name'];
        $file_tmp  = $_FILES['avatar']['tmp_name'];
        $file_size = $_FILES['avatar']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) {
            $errors[] = 'Invalid avatar file type. Allowed types: jpg, jpeg, png, gif.';
        }

        if ($file_size > 2 * 1024 * 1024) {
            $errors[] = 'Avatar file size exceeds 2MB.';
        }

        $upload_dir = '../assets/avatars/';
        $new_file_name = uniqid('avatar_', true) . '.' . $file_ext;
        $upload_path = $upload_dir . $new_file_name;

        if (empty($errors) && !move_uploaded_file($file_tmp, $upload_path)) {
            $errors[] = 'Failed to upload avatar.';
        }
    } else {
        $new_file_name = 'default_avatar.png';
    }

    // Process user creation if no errors
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT user_id FROM Users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare('INSERT INTO Users (username, password, email, phone, avatar, role) VALUES (?, ?, ?, ?, ?, ?)');
            if ($stmt->execute([$username, $hashed_password, $email, $phone, $new_file_name, $role])) {
                $response['success'] = 'User added successfully!';
            } else {
                $errors[] = 'Failed to add user.';
            }
        }
    }

    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    // Return JSON response
    echo json_encode($response);
    exit();
}
