<?php
require '../config/config.php';

if (isset($_POST['categoryId']) && isset($_POST['categoryName'])) {
    $categoryId = $_POST['categoryId'];
    $categoryName = $_POST['categoryName'];

    // Update the category name
    $query = "UPDATE categories SET category_name = :category_name WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':category_name', $categoryName);
    $stmt->bindParam(':id', $categoryId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Category updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update category']);
    }
}
?>
