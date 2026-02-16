<?php
session_start();

// Check if the user is logged in and has the proper role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    http_response_code(403);
    echo "Access denied.";
    exit();
}

// Validate the requested backup file
if (isset($_GET['file'])) {
    $file = basename($_GET['file']); // Sanitize input
    $filePath = __DIR__ . '/backups/' . $file;

    if (file_exists($filePath)) {
        // Serve the file for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
    } else {
        echo "File not found.";
        exit();
    }
} else {
    echo "No file specified.";
    exit();
}
?>
