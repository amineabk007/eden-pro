<?php
require '../config/config.php';

if (isset($_POST['id'])) {
    $categoryId = $_POST['id'];

    // Option 1: Set category_id to NULL for linked products
    $updateProductsQuery = "UPDATE products SET category_id = NULL WHERE category_id = :category_id";
    // Option 2: Move products to a default category (ID = 1, for example)
    // $updateProductsQuery = "UPDATE products SET category_id = 1 WHERE category_id = :category_id";

    $updateStmt = $pdo->prepare($updateProductsQuery);
    $updateStmt->bindParam(':category_id', $categoryId);
    $updateStmt->execute();

    // Now, delete the category
    $query = "DELETE FROM categories WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $categoryId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete category']);
    }
}
?>
