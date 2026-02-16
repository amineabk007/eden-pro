<?php
session_start();
require '../config/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the devis ID
    $devis_id = $_GET['id'] ?? null;
    if (!$devis_id) {
        header('Location: index.php');
        exit();
    }

    // Sanitize input
    $client_id = $_POST['client_id'] ?? null;
    $valid_from = $_POST['valid_from'] ?? null;
    $valid_until = $_POST['valid_until'] ?? null;
    $status_id = $_POST['status_id'] ?? null;
    $product_prices = $_POST['product_prices'] ?? [];

    try {
        // Prepare SQL to update the devis
        $updateDevis = $pdo->prepare("
            UPDATE devis SET 
                client_id = ?, 
                valid_from = ?, 
                valid_until = ?, 
                status_id = ? 
            WHERE id = ?
        ");
        $updateDevis->execute([$client_id, $valid_from, $valid_until, $status_id, $devis_id]);

        // Check if the update was successful
        if ($updateDevis->rowCount() === 0) {
            throw new Exception("No rows updated. Check if the devis ID is valid.");
        }

        // Clear existing products for the devis
        $deleteProducts = $pdo->prepare("DELETE FROM devis_details WHERE devis_id = ?");
        $deleteProducts->execute([$devis_id]);

        // Insert new product prices
        foreach ($product_prices as $product_id => $price) {
            $insertProduct = $pdo->prepare("
                INSERT INTO devis_details (devis_id, product_id, price) 
                VALUES (?, ?, ?)
            ");
            $insertProduct->execute([$devis_id, $product_id, $price]);
        }

        // Redirect to the devis listing page
        header('Location: index.php?update_success=1');
        exit();

    } catch (PDOException $e) {
        // Handle database errors
        echo "Database error: " . $e->getMessage();
        exit();
    } catch (Exception $e) {
        // Handle general errors
        echo "Error: " . $e->getMessage();
        exit();
    }
}
?>
