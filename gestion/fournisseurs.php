<?php
// gestion/fournisseurs.php — Liste des fournisseurs, soldes et relevé de compte.
require __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id  = (int) ($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $data = [$nom, trim($_POST['telephone'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['adresse'] ?? '')];
        if ($nom === '') {
            flash('Le nom du fournisseur est obligatoire.', 'danger');
        } elseif ($id) {
            exec_sql('UPDATE g_fournisseurs SET nom=?, telephone=?, email=?, adresse=? WHERE id=?', [...$data, $id]);
            flash('Fournisseur modifié.');
        } else {
            exec_sql('INSERT INTO g_fournisseurs (nom, telephone, email, adresse) VALUES (?,?,?,?)', $data);
            flash('Fournisseur ajouté.');
        }
    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        $used = scalar('SELECT COUNT(*) FROM g_achats WHERE fournisseur_id=?', [$id])
              + scalar('SELECT COUNT(*) FROM g_paiements_fournisseurs WHERE fournisseur_id=?', [$id]);
        if ($used) {
            flash('Impossible de supprimer : ce fournisseur a des achats ou paiements enregistrés.', 'warning');
        } else {
            exec_sql('DELETE FROM g_fournisseurs WHERE id=?', [$id]);
            flash('Fournisseur supprimé.');
        }
    }
    back();
}

$fournisseurs = rows(
    'SELECT f.*,
        (SELECT COALESCE(SUM(montant),0) FROM g_achats a WHERE a.fournisseur_id=f.id) AS total_achats,
        (SELECT COALESCE(SUM(montant),0) FROM g_paiements_fournisseurs p WHERE p.fournisseur_id=f.id) AS total_paye
     FROM g_fournisseurs f ORDER BY f.nom'
);
$totA = array_sum(array_column($fournisseurs, 'total_achats'));
$totP = array_sum(array_column($fournisseurs, 'total_paye'));

$edit = isset($_GET['edit']) ? row('SELECT * FROM g_fournisseurs WHERE id=?', [(int) $_GET['edit']]) : null;
$f = $edit ?? ['id' => 0, 'nom' => '', 'telephone' => '', 'email' => '', 'adresse' => ''];

// Relevé de compte d'un fournisseur
$releve = null;
if (isset($_GET['id'])) {
    $four = row('SELECT * FROM g_fournisseurs WHERE id=?', [(int) $_GET['id']]);
    if ($four) {
        $mvts = rows(
            "SELECT date_achat AS d, 'Achat' AS type, num_facture AS ref, montant AS debit, 0 AS credit, note
               FROM g_achats WHERE fournisseur_id=?
             UNION ALL
             SELECT date_paiement AS d, 'Paiement' AS type, reference AS ref, 0 AS debit, montant AS credit, mode AS note
               FROM g_paiements_fournisseurs WHERE fournisseur_id=?
             ORDER BY d, type",
            [$four['id'], $four['id']]
        );
        $releve = ['f' => $four, 'mvts' => $mvts];
    }
}

$title = 'Fournisseurs';
require __DIR__ . '/inc/header.php';
?>
<?php if ($releve): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-journal-text"></i> Relevé de compte — <?= e($releve['f']['nom']) ?></span>
            <span>
                <a href="achats.php?fournisseur_id=<?= (int) $releve['f']['id'] ?>" class="btn btn-sm btn-outline-primary">+ Achat</a>
                <a href="paiements.php?fournisseur_id=<?= (int) $releve['f']['id'] ?>" class="btn btn-sm btn-outline-success">+ Paiement</a>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button>
                <a href="fournisseurs.php" class="btn btn-sm btn-outline-dark"><i class="bi bi-x-lg"></i></a>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Opération</th><th>Réf.</th><th>Détail</th>
                    <th class="num">Achat (dû)</th><th class="num">Paiement</th><th class="num">Solde</th></tr></thead>
                <tbody>
                <?php $solde = 0; foreach ($releve['mvts'] as $m): $solde += $m['debit'] - $m['credit']; ?>
                    <tr>
                        <td><?= fdate($m['d']) ?></td>
                        <td><span class="badge text-bg-<?= $m['type'] === 'Achat' ? 'primary' : 'success' ?>"><?= e($m['type']) ?></span></td>
                        <td><?= e($m['ref']) ?></td>
                        <td class="small text-muted"><?= e($m['type'] === 'Paiement' ? mode_label($m['note']) : $m['note']) ?></td>
                        <td class="num"><?= $m['debit'] > 0 ? money($m['debit']) : '' ?></td>
                        <td class="num"><?= $m['credit'] > 0 ? money($m['credit']) : '' ?></td>
                        <td class="num fw-semibold <?= $solde > 0 ? 'text-danger' : 'text-success' ?>"><?= money($solde) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$releve['mvts']): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Aucune opération.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold"><?= $edit ? 'Modifier le fournisseur' : 'Nouveau fournisseur' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <div class="mb-3"><label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-control" required maxlength="150" value="<?= e($f['nom']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Téléphone</label>
                        <input type="text" name="telephone" class="form-control" maxlength="50" value="<?= e($f['telephone']) ?>"></div>
                    <div class="mb-3"><label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" maxlength="150" value="<?= e($f['email']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Adresse</label>
                        <input type="text" name="adresse" class="form-control" maxlength="255" value="<?= e($f['adresse']) ?>"></div>
                    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <?= $edit ? cancel_link() : '' ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="row g-3 mb-3">
            <div class="col-sm-4"><div class="card kpi p-3" style="--kpi:#0d6efd">
                <div class="kpi-label">Total achats</div><div class="kpi-value"><?= money($totA) ?></div></div></div>
            <div class="col-sm-4"><div class="card kpi p-3" style="--kpi:#198754">
                <div class="kpi-label">Total payé</div><div class="kpi-value"><?= money($totP) ?></div></div></div>
            <div class="col-sm-4"><div class="card kpi p-3" style="--kpi:#dc3545">
                <div class="kpi-label">Reste à payer</div><div class="kpi-value"><?= money($totA - $totP) ?></div></div></div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Fournisseur</th><th>Contact</th>
                        <th class="num">Achats</th><th class="num">Payé</th><th class="num">Solde dû</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($fournisseurs as $r): $s = $r['total_achats'] - $r['total_paye']; ?>
                        <tr>
                            <td><a href="?id=<?= (int) $r['id'] ?>" class="fw-semibold text-decoration-none"><?= e($r['nom']) ?></a></td>
                            <td class="small text-muted"><?= e($r['telephone']) ?><?= $r['email'] ? '<br>' . e($r['email']) : '' ?></td>
                            <td class="num"><?= money($r['total_achats']) ?></td>
                            <td class="num"><?= money($r['total_paye']) ?></td>
                            <td class="num fw-semibold <?= $s > 0 ? 'text-danger' : 'text-success' ?>"><?= money($s) ?></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="?id=<?= (int) $r['id'] ?>" title="Relevé"><i class="bi bi-journal-text"></i></a>
                                <?= edit_link((int) $r['id']) ?> <?= delete_button((int) $r['id'], 'delete', 'Supprimer ce fournisseur ?') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$fournisseurs): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun fournisseur. Ajoutez-en un avec le formulaire.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/inc/footer.php';
