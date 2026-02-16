<?php
session_start();
require '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status_id = $_POST['status_id'];

    $stmt = $pdo->prepare("DELETE FROM statuses WHERE status_id = ?");
    $stmt->execute([$status_id]);
}
?>
