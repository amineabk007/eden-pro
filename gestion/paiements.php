<?php
// gestion/paiements.php — Paiements aux fournisseurs.
require __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = (int) ($_POST['id'] ?? 0);
        $fid  = (int) ($_POST['fournisseur_id'] ?? 0);
        $date = $_POST['date_paiement'] ?? '';
        $mt   = num($_POST['montant'] ?? 0);
        $mode = $_POST['mode'] ?? '';
        $data = [$fid, $date, $mt, $mode, trim($_POST['reference'] ?? ''), trim($_POST['note'] ?? '')];
        if (!row('SELECT id FROM g_fournisseurs WHERE id=?', [$fid])) {
            flash('Veuillez choisir un fournisseur.', 'danger');
        } elseif (!valid_date($date) || $mt <= 0 || !isset(MODES_PAIEMENT[$mode])) {
            flash('Date, montant ou mode de paiement invalide.', 'danger');
        } elseif ($id) {
            exec_sql('UPDATE g_paiements_fournisseurs SET fournisseur_id=?, date_paiement=?, montant=?, mode=?, reference=?, note=? WHERE id=?', [...$data, $id]);
            flash('Paiement modifié.');
        } else {
            exec_sql('INSERT INTO g_paiements_fournisseurs (fournisseur_id, date_paiement, montant, mode, reference, note) VALUES (?,?,?,?,?,?)', $data);
            flash('Paiement enregistré : ' . money($mt));
        }
    } elseif ($action === 'delete') {
        exec_sql('DELETE FROM g_paiements_fournisseurs WHERE id=?', [(int) $_POST['id']]);
        flash('Paiement supprimé.');
    }
    back();
}

$fournisseurs = rows(
    'SELECT f.id, f.nom,
        (SELECT COALESCE(SUM(montant),0) FROM g_achats a WHERE a.fournisseur_id=f.id)
      - (SELECT COALESCE(SUM(montant),0) FROM g_paiements_fournisseurs p WHERE p.fournisseur_id=f.id) AS solde
     FROM g_fournisseurs f ORDER BY f.nom'
);
$optF = [];
foreach ($fournisseurs as $r) {
    $optF[$r['id']] = $r['nom'] . ' — solde dû : ' . money($r['solde']);
}

[$du, $au] = period();
$filtreF = (int) ($_GET['fournisseur_id'] ?? 0);
$sql = 'SELECT p.*, f.nom FROM g_paiements_fournisseurs p JOIN g_fournisseurs f ON f.id=p.fournisseur_id WHERE p.date_paiement BETWEEN ? AND ?';
$p = [$du, $au];
if ($filtreF) {
    $sql .= ' AND p.fournisseur_id=?';
    $p[] = $filtreF;
}
$paiements = rows($sql . ' ORDER BY p.date_paiement DESC, p.id DESC', $p);
$total = array_sum(array_column($paiements, 'montant'));
$parMode = [];
foreach ($paiements as $r) {
    $parMode[$r['mode']] = ($parMode[$r['mode']] ?? 0) + $r['montant'];
}

$edit = isset($_GET['edit']) ? row('SELECT * FROM g_paiements_fournisseurs WHERE id=?', [(int) $_GET['edit']]) : null;
$f = $edit ?? ['id' => 0, 'fournisseur_id' => $filtreF, 'date_paiement' => date('Y-m-d'), 'montant' => '', 'mode' => 'especes', 'reference' => '', 'note' => ''];

$title = 'Paiements fournisseurs';
require __DIR__ . '/inc/header.php';
$filtre = '<div class="col-12 col-md-auto"><label class="form-label small mb-0">Fournisseur</label>'
    . '<select name="fournisseur_id" class="form-select form-select-sm"><option value="0">Tous</option>'
    . select_options(array_column($fournisseurs, 'nom', 'id'), $filtreF) . '</select></div>';
?>
<?php if (!$fournisseurs): ?>
    <div class="alert alert-info">Ajoutez d'abord un fournisseur dans <a href="fournisseurs.php">Fournisseurs</a>.</div>
<?php endif; ?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold"><?= $edit ? 'Modifier le paiement' : 'Nouveau paiement' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <div class="mb-3"><label class="form-label">Fournisseur *</label>
                        <select name="fournisseur_id" class="form-select" required>
                            <option value="">— Choisir —</option><?= select_options($optF, $f['fournisseur_id']) ?>
                        </select></div>
                    <div class="mb-3"><label class="form-label">Date *</label>
                        <input type="date" name="date_paiement" class="form-control" required value="<?= e($f['date_paiement']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Montant (<?= DEVISE ?>) *</label>
                        <input type="number" step="0.01" min="0.01" name="montant" class="form-control" required value="<?= e($f['montant']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Mode de paiement</label>
                        <select name="mode" class="form-select"><?= select_options(MODES_PAIEMENT, $f['mode']) ?></select></div>
                    <div class="mb-3"><label class="form-label">Référence (n° chèque, virement…)</label>
                        <input type="text" name="reference" class="form-control" maxlength="100" value="<?= e($f['reference']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Note</label>
                        <input type="text" name="note" class="form-control" maxlength="255" value="<?= e($f['note']) ?>"></div>
                    <button class="btn btn-success"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <?= $edit ? cancel_link() : '' ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <?= period_form($du, $au, $filtre) ?>
        <div class="card">
            <div class="card-header">
                Total payé : <strong><?= money($total) ?></strong>
                <?php foreach ($parMode as $m => $v): ?>
                    <span class="badge text-bg-light border ms-2"><?= e(mode_label($m)) ?> : <?= money($v) ?></span>
                <?php endforeach; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Fournisseur</th><th>Mode</th><th>Référence</th><th class="num">Montant</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($paiements as $r): ?>
                        <tr>
                            <td><?= fdate($r['date_paiement']) ?></td>
                            <td><a href="fournisseurs.php?id=<?= (int) $r['fournisseur_id'] ?>"><?= e($r['nom']) ?></a></td>
                            <td><?= e(mode_label($r['mode'])) ?></td>
                            <td><?= e($r['reference']) ?><?= $r['note'] ? '<div class="small text-muted">' . e($r['note']) . '</div>' : '' ?></td>
                            <td class="num fw-semibold"><?= money($r['montant']) ?></td>
                            <td class="text-end text-nowrap"><?= edit_link((int) $r['id']) ?> <?= delete_button((int) $r['id']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$paiements): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun paiement sur cette période.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/inc/footer.php';
