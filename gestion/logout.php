<?php
// gestion/logout.php — déconnexion.
require __DIR__ . '/inc/session.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit();
