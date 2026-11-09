<?php
// gestion/inc/bootstrap.php — chargé en tête de chaque page du module.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Utilise la connexion de l'application (login.php)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
