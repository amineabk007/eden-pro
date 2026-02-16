<?php
session_start();
require '../config/config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM devis WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['message'] = 'Devis deleted successfully.';
    } catch (Exception $e) {
        die('Error deleting devis: ' . $e->getMessage());
    }
}

header('Location: index.php');
