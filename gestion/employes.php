<?php
// gestion/employes.php — Fiches du personnel.
require __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = (int) ($_POST['id'] ?? 0);
        $nom  = trim($_POST['nom'] ?? '');
        $emb  = $_POST['date_embauche'] ?? '';
        $data = [$nom, trim($_POST['poste'] ?? ''), trim($_POST['telephone'] ?? ''), num($_POST['salaire_base'] ?? 0),
            valid_date($emb) ? $emb : null, isset($_POST['actif']) ? 1 : 0];
        if ($nom === '') {
            flash('Le nom est obligatoire.', 'danger');
        } elseif ($id) {
            exec_sql('UPDATE g_employes SET nom=?, poste=?, telephone=?, salaire_base=?, date_embauche=?, actif=? WHERE id=?', [...$data, $id]);
            flash('Employé modifié.');
        } else {
            exec_sql('INSERT INTO g_employes (nom, poste, telephone, salaire_base, date_embauche, actif) VALUES (?,?,?,?,?,?)', $data);
            flash('Employé ajouté.');
        }
    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        $used = scalar('SELECT COUNT(*) FROM g_avances WHERE employe_id=?', [$id])
              + scalar('SELECT COUNT(*) FROM g_salaires WHERE employe_id=?', [$id]);
        if ($used) {
            flash('Cet employé a un historique (avances/salaires) : désactivez-le plutôt que de le supprimer.', 'warning');
        } else {
            exec_sql('DELETE FROM g_employes WHERE id=?', [$id]);
            flash('Employé supprimé.');
        }
    }
    back();
}

$mois = date('Y-m');
$employes = rows(
    'SELECT e.*,
        (SELECT COALESCE(SUM(montant),0) FROM g_avances a WHERE a.employe_id=e.id AND a.mois_deduction=?) AS avances_mois,
        (SELECT COUNT(*) FROM g_salaires s WHERE s.employe_id=e.id AND s.mois=?) AS paye_mois
     FROM g_employes e ORDER BY e.actif DESC, e.nom',
    [$mois, $mois]
);
$masse = array_sum(array_map(fn($r) => $r['actif'] ? $r['salaire_base'] : 0, $employes));

$edit = isset($_GET['edit']) ? row('SELECT * FROM g_employes WHERE id=?', [(int) $_GET['edit']]) : null;
$f = $edit ?? ['id' => 0, 'nom' => '', 'poste' => '', 'telephone' => '', 'salaire_base' => '', 'date_embauche' => '', 'actif' => 1];

$title = 'Employés';
require __DIR__ . '/inc/header.php';
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold"><?= $edit ? 'Modifier l\'employé' : 'Nouvel employé' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <div class="mb-3"><label class="form-label">Nom complet *</label>
                        <input type="text" name="nom" class="form-control" required maxlength="150" value="<?= e($f['nom']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Poste</label>
                        <input type="text" name="poste" class="form-control" maxlength="100" value="<?= e($f['poste']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Téléphone</label>
                        <input type="text" name="telephone" class="form-control" maxlength="50" value="<?= e($f['telephone']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Salaire mensuel de base (<?= DEVISE ?>)</label>
                        <input type="number" step="0.01" min="0" name="salaire_base" class="form-control" value="<?= e($f['salaire_base']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Date d'embauche</label>
                        <input type="date" name="date_embauche" class="form-control" value="<?= e($f['date_embauche']) ?>"></div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="actif" id="actif" <?= $f['actif'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="actif">Actif</label>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <?= $edit ? cancel_link() : '' ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Masse salariale mensuelle (employés actifs) : <strong><?= money($masse) ?></strong></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Nom</th><th>Poste</th><th class="num">Salaire base</th>
                        <th class="num">Avances <?= e(mois_label($mois)) ?></th><th>Salaire du mois</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($employes as $r): ?>
                        <tr class="<?= $r['actif'] ? '' : 'text-muted' ?>">
                            <td class="fw-semibold"><?= e($r['nom']) ?> <?= $r['actif'] ? '' : '<span class="badge text-bg-secondary">Inactif</span>' ?>
                                <div class="small text-muted fw-normal"><?= e($r['telephone']) ?></div></td>
                            <td><?= e($r['poste']) ?></td>
                            <td class="num"><?= money($r['salaire_base']) ?></td>
                            <td class="num <?= $r['avances_mois'] > 0 ? 'text-warning-emphasis' : '' ?>"><?= money($r['avances_mois']) ?></td>
                            <td><?= $r['paye_mois'] ? '<span class="badge text-bg-success">Payé</span>' : '<span class="badge text-bg-light border">Non payé</span>' ?></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-warning" title="Avance" href="avances.php?employe_id=<?= (int) $r['id'] ?>"><i class="bi bi-wallet2"></i></a>
                                <?= edit_link((int) $r['id']) ?> <?= delete_button((int) $r['id'], 'delete', 'Supprimer cet employé ?') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$employes): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun employé enregistré.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/inc/footer.php';
