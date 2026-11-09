<?php
// gestion/utilisateurs.php — Mon compte (mot de passe) + gestion des utilisateurs (admin).
require __DIR__ . '/inc/bootstrap.php';

const ROLES = ['admin' => 'Administrateur', 'user' => 'Utilisateur'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'mon_mdp') {
        $u = row('SELECT * FROM g_users WHERE id=?', [$ME['id']]);
        $new = (string) ($_POST['nouveau'] ?? '');
        if (!password_verify((string) ($_POST['actuel'] ?? ''), $u['password_hash'])) {
            flash('Mot de passe actuel incorrect.', 'danger');
        } elseif (strlen($new) < 8 || $new !== ($_POST['confirmation'] ?? '')) {
            flash('Nouveau mot de passe invalide (8 caractères min.) ou confirmation différente.', 'danger');
        } else {
            exec_sql('UPDATE g_users SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $ME['id']]);
            flash('Votre mot de passe a été changé.');
        }
    } elseif ($action === 'ajouter') {
        require_admin();
        $username = trim($_POST['username'] ?? '');
        $pass = (string) ($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'user';
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            flash('Identifiant invalide (3 à 50 caractères : lettres, chiffres, . _ -).', 'danger');
        } elseif (row('SELECT id FROM g_users WHERE username=?', [$username])) {
            flash('Cet identifiant existe déjà.', 'danger');
        } elseif (strlen($pass) < 8 || !isset(ROLES[$role])) {
            flash('Mot de passe trop court (8 caractères min.) ou rôle invalide.', 'danger');
        } else {
            exec_sql('INSERT INTO g_users (username, nom, password_hash, role) VALUES (?,?,?,?)',
                [$username, trim($_POST['nom'] ?? ''), password_hash($pass, PASSWORD_DEFAULT), $role]);
            flash('Utilisateur « ' . $username . ' » créé.');
        }
    } elseif ($action === 'reinitialiser') {
        require_admin();
        $pass = (string) ($_POST['password'] ?? '');
        if (strlen($pass) < 8) {
            flash('Mot de passe trop court (8 caractères min.).', 'danger');
        } else {
            exec_sql('UPDATE g_users SET password_hash=? WHERE id=?', [password_hash($pass, PASSWORD_DEFAULT), (int) $_POST['id']]);
            flash('Mot de passe réinitialisé.');
        }
    } elseif ($action === 'delete') {
        require_admin();
        $id = (int) $_POST['id'];
        $cible = row('SELECT * FROM g_users WHERE id=?', [$id]);
        if ($id === (int) $ME['id']) {
            flash('Vous ne pouvez pas supprimer votre propre compte.', 'warning');
        } elseif ($cible && $cible['role'] === 'admin' && (int) scalar("SELECT COUNT(*) FROM g_users WHERE role='admin'") <= 1) {
            flash('Impossible de supprimer le dernier administrateur.', 'warning');
        } else {
            exec_sql('DELETE FROM g_users WHERE id=?', [$id]);
            flash('Utilisateur supprimé.');
        }
    }
    back();
}

$users = is_admin() ? rows('SELECT id, username, nom, role, created_at FROM g_users ORDER BY username') : [];

$title = is_admin() ? 'Utilisateurs' : 'Mon compte';
require __DIR__ . '/inc/header.php';
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-key"></i> Changer mon mot de passe</div>
            <div class="card-body">
                <p class="small text-muted mb-3">Connecté en tant que <strong><?= e($ME['username']) ?></strong> (<?= e(ROLES[$ME['role']] ?? $ME['role']) ?>)</p>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="mon_mdp">
                    <div class="mb-3"><label class="form-label">Mot de passe actuel</label>
                        <input type="password" name="actuel" class="form-control" required autocomplete="current-password"></div>
                    <div class="mb-3"><label class="form-label">Nouveau mot de passe</label>
                        <input type="password" name="nouveau" class="form-control" required minlength="8" autocomplete="new-password"></div>
                    <div class="mb-3"><label class="form-label">Confirmation</label>
                        <input type="password" name="confirmation" class="form-control" required minlength="8" autocomplete="new-password"></div>
                    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                </form>
            </div>
        </div>

        <?php if (is_admin()): ?>
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-person-plus"></i> Nouvel utilisateur</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="ajouter">
                    <div class="mb-3"><label class="form-label">Nom complet</label>
                        <input type="text" name="nom" class="form-control" maxlength="100"></div>
                    <div class="mb-3"><label class="form-label">Identifiant *</label>
                        <input type="text" name="username" class="form-control" required pattern="[A-Za-z0-9._\-]{3,50}" autocomplete="off"></div>
                    <div class="mb-3"><label class="form-label">Mot de passe * (8 caractères min.)</label>
                        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
                    <div class="mb-3"><label class="form-label">Rôle</label>
                        <select name="role" class="form-select"><?= select_options(ROLES, 'user') ?></select>
                        <div class="form-text">Seuls les administrateurs gèrent les utilisateurs.</div></div>
                    <button class="btn btn-success"><i class="bi bi-check-lg"></i> Créer</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (is_admin()): ?>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Identifiant</th><th>Nom</th><th>Rôle</th><th>Créé le</th><th>Réinitialiser le mot de passe</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($u['username']) ?> <?= (int) $u['id'] === (int) $ME['id'] ? '<span class="badge text-bg-light border">vous</span>' : '' ?></td>
                            <td><?= e($u['nom']) ?></td>
                            <td><span class="badge text-bg-<?= $u['role'] === 'admin' ? 'primary' : 'secondary' ?>"><?= e(ROLES[$u['role']] ?? $u['role']) ?></span></td>
                            <td class="small"><?= fdate($u['created_at']) ?></td>
                            <td>
                                <?php if ((int) $u['id'] !== (int) $ME['id']): ?>
                                <form method="post" class="d-flex gap-1" onsubmit="return confirm('Réinitialiser le mot de passe de <?= e(addslashes($u['username'])) ?> ?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reinitialiser">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <input type="password" name="password" class="form-control form-control-sm" placeholder="Nouveau mot de passe" required minlength="8" autocomplete="new-password" style="max-width:190px">
                                    <button class="btn btn-sm btn-outline-primary" title="Réinitialiser"><i class="bi bi-arrow-repeat"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= (int) $u['id'] !== (int) $ME['id'] ? delete_button((int) $u['id'], 'delete', 'Supprimer cet utilisateur ?') : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/inc/footer.php';
