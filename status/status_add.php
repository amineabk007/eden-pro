<?php
session_start();
require '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entity_type = $_POST['entity_type'];
    $status_name = $_POST['status_name'];
    $class_name = $_POST['class_name'];

    $stmt = $pdo->prepare("INSERT INTO statuses (entity_type, status_name, class_name) VALUES (?, ?, ?)");
    $stmt->execute([$entity_type, $status_name, $class_name]);
}
?>
