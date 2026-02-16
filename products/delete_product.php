<?php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the product ID
    $productId = $_POST['id'];

    // Delete the product from the database
    $deleteQuery = "DELETE FROM products WHERE id = ?";
    $stmt = $pdo->prepare($deleteQuery);
    $stmt->execute([$productId]);

    echo json_encode(['status' => 'success', 'message' => 'Product deleted successfully.']);
}
?>
