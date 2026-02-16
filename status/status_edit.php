<?php
session_start();
require '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status_id = $_POST['status_id'];
    $entity_type = $_POST['entity_type'];
    $status_name = $_POST['status_name'];
    $class_name = $_POST['class_name'];

    $stmt = $pdo->prepare("UPDATE statuses SET entity_type = ?, status_name = ?, class_name = ? WHERE status_id = ?");
    $stmt->execute([$entity_type, $status_name, $class_name, $status_id]);
}
?>
