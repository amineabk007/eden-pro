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
    // Get the product ID and other form data
    $productId = $_POST['productId'];
    $productName = $_POST['productName'];
    $categoryId = $_POST['category'];

    // Check if an image was uploaded
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
        $imageName = $_FILES['productImage']['name'];
        $imageTmp = $_FILES['productImage']['tmp_name'];
        $imagePath = "../uploads/products/" . basename($imageName);

        // Move the uploaded file to the desired location
        if (move_uploaded_file($imageTmp, $imagePath)) {
            // Update the product in the database with new image
            $updateQuery = "UPDATE products SET product_name = ?, category_id = ?, image = ? WHERE id = ?";
            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute([$productName, $categoryId, $imageName, $productId]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Image upload failed.']);
            exit();
        }
    } else {
        // Update the product without changing the image
        $updateQuery = "UPDATE products SET product_name = ?, category_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([$productName, $categoryId, $productId]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Product updated successfully.']);
}
?>
