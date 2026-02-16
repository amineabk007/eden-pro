<?php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = $_POST['productName'];
    $categoryId = $_POST['category'];
    
    // Handle image upload
    $image = $_FILES['productImage'];
    $imageName = time() . '_' . basename($image['name']);
    $targetDir = '../uploads/products/';
    $targetFilePath = $targetDir . $imageName;

    // Move the uploaded file to the target directory
    if (move_uploaded_file($image['tmp_name'], $targetFilePath)) {
        // Prepare the SQL statement
        $sql = "INSERT INTO products (product_name, category_id, image) VALUES (:product_name, :category_id, :image)";
        $stmt = $pdo->prepare($sql);

        // Bind parameters and execute
        $stmt->bindParam(':product_name', $productName);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->bindParam(':image', $imageName);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Product added successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add product.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image.']);
    }
}
?>
