<?php
// gestion/inc/bootstrap.php — chargé en tête de chaque page protégée.

require __DIR__ . '/session.php';
require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';

if (!isset($_SESSION['erp_user_id'])) {
    $hasUsers = (int) scalar('SELECT COUNT(*) FROM g_users') > 0;
    redirect($hasUsers ? 'login.php' : 'setup.php');
}

// Utilisateur connecté (déconnexion si le compte a été supprimé)
$ME = row('SELECT id, username, nom, role FROM g_users WHERE id=?', [(int) $_SESSION['erp_user_id']]);
if (!$ME) {
    $_SESSION = [];
    session_destroy();
    redirect('login.php');
}

function is_admin(): bool
{
    global $ME;
    return ($ME['role'] ?? '') === 'admin';
}

function require_admin(): void
{
    if (!is_admin()) {
        http_response_code(403);
        exit('Accès réservé aux administrateurs.');
    }
}
