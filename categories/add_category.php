<?php
require '../config/config.php';

if (isset($_POST['categoryName'])) {
    $categoryName = $_POST['categoryName'];

    // Insert the new category
    $query = "INSERT INTO categories (category_name) VALUES (:category_name)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':category_name', $categoryName);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Category added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add category']);
    }
}
?>
