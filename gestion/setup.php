<?php
// gestion/setup.php — création du premier compte administrateur (uniquement si aucun compte n'existe).
require __DIR__ . '/inc/session.php';
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/functions.php';

if ((int) scalar('SELECT COUNT(*) FROM g_users') > 0) {
    redirect('login.php');
}

$f = ['nom' => '', 'username' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $f = ['nom' => trim($_POST['nom'] ?? ''), 'username' => trim($_POST['username'] ?? '')];
    $pass = (string) ($_POST['password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $f['username'])) {
        flash('Identifiant invalide (3 à 50 caractères : lettres, chiffres, . _ -).', 'danger');
    } elseif (strlen($pass) < 8) {
        flash('Le mot de passe doit contenir au moins 8 caractères.', 'danger');
    } elseif ($pass !== ($_POST['password2'] ?? '')) {
        flash('Les deux mots de passe ne correspondent pas.', 'danger');
    } else {
        exec_sql("INSERT INTO g_users (username, nom, password_hash, role) VALUES (?,?,?,'admin')",
            [$f['username'], $f['nom'], password_hash($pass, PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['erp_user_id'] = (int) $pdo->lastInsertId();
        flash('Bienvenue ! Votre compte administrateur est créé.');
        redirect('index.php');
    }
}

$title = 'Installation — compte administrateur';
$content = '<p class="small text-muted">Première utilisation : créez le compte administrateur de l\'application.</p>
<form method="post">' . csrf_field() . '
    <div class="mb-3"><label class="form-label">Nom complet</label>
        <input type="text" name="nom" class="form-control" maxlength="100" value="' . e($f['nom']) . '"></div>
    <div class="mb-3"><label class="form-label">Identifiant *</label>
        <input type="text" name="username" class="form-control" required pattern="[A-Za-z0-9._\-]{3,50}" autocomplete="username" value="' . e($f['username']) . '"></div>
    <div class="mb-3"><label class="form-label">Mot de passe * (8 caractères min.)</label>
        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
    <div class="mb-3"><label class="form-label">Confirmer le mot de passe *</label>
        <input type="password" name="password2" class="form-control" required minlength="8" autocomplete="new-password"></div>
    <button class="btn btn-success w-100"><i class="bi bi-check-lg"></i> Créer le compte</button>
</form>';
require __DIR__ . '/inc/auth_layout.php';
