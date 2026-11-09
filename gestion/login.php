<?php
// gestion/login.php — connexion.
require __DIR__ . '/inc/session.php';
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/functions.php';

if (isset($_SESSION['erp_user_id'])) {
    redirect('index.php');
}
if ((int) scalar('SELECT COUNT(*) FROM g_users') === 0) {
    redirect('setup.php');
}

$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $user = row('SELECT * FROM g_users WHERE username=?', [$username]);
    if ($user && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['erp_user_id'] = (int) $user['id'];
        redirect('index.php');
    }
    sleep(1); // freine les essais de mots de passe
    flash('Identifiant ou mot de passe incorrect.', 'danger');
}

$title = 'Connexion';
$content = '<form method="post">' . csrf_field() . '
    <div class="mb-3"><label class="form-label">Identifiant</label>
        <input type="text" name="username" class="form-control" required autofocus autocomplete="username" value="' . e($username) . '"></div>
    <div class="mb-3"><label class="form-label">Mot de passe</label>
        <input type="password" name="password" class="form-control" required autocomplete="current-password"></div>
    <button class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Se connecter</button>
</form>';
require __DIR__ . '/inc/auth_layout.php';
