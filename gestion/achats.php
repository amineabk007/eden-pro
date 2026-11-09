<?php
// gestion/achats.php — Achats fournisseurs (factures).
require __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = (int) ($_POST['id'] ?? 0);
        $fid  = (int) ($_POST['fournisseur_id'] ?? 0);
        $date = $_POST['date_achat'] ?? '';
        $mt   = num($_POST['montant'] ?? 0);
        $data = [$fid, $date, trim($_POST['num_facture'] ?? ''), $mt, trim($_POST['note'] ?? '')];
        if (!row('SELECT id FROM g_fournisseurs WHERE id=?', [$fid])) {
            flash('Veuillez choisir un fournisseur.', 'danger');
        } elseif (!valid_date($date) || $mt <= 0) {
            flash('Date ou montant invalide.', 'danger');
        } elseif ($id) {
            exec_sql('UPDATE g_achats SET fournisseur_id=?, date_achat=?, num_facture=?, montant=?, note=? WHERE id=?', [...$data, $id]);
            flash('Achat modifié.');
        } else {
            exec_sql('INSERT INTO g_achats (fournisseur_id, date_achat, num_facture, montant, note) VALUES (?,?,?,?,?)', $data);
            flash('Achat enregistré : ' . money($mt));
        }
    } elseif ($action === 'delete') {
        exec_sql('DELETE FROM g_achats WHERE id=?', [(int) $_POST['id']]);
        flash('Achat supprimé.');
    }
    back();
}

$fournisseurs = array_column(rows('SELECT id, nom FROM g_fournisseurs ORDER BY nom'), 'nom', 'id');
[$du, $au] = period();
$filtreF = (int) ($_GET['fournisseur_id'] ?? 0);

$sql = 'SELECT a.*, f.nom FROM g_achats a JOIN g_fournisseurs f ON f.id=a.fournisseur_id WHERE a.date_achat BETWEEN ? AND ?';
$p = [$du, $au];
if ($filtreF) {
    $sql .= ' AND a.fournisseur_id=?';
    $p[] = $filtreF;
}
$achats = rows($sql . ' ORDER BY a.date_achat DESC, a.id DESC', $p);
$total = array_sum(array_column($achats, 'montant'));

$edit = isset($_GET['edit']) ? row('SELECT * FROM g_achats WHERE id=?', [(int) $_GET['edit']]) : null;
$f = $edit ?? ['id' => 0, 'fournisseur_id' => $filtreF, 'date_achat' => date('Y-m-d'), 'num_facture' => '', 'montant' => '', 'note' => ''];

$title = 'Achats fournisseurs';
require __DIR__ . '/inc/header.php';
$filtre = '<div class="col-12 col-md-auto"><label class="form-label small mb-0">Fournisseur</label>'
    . '<select name="fournisseur_id" class="form-select form-select-sm"><option value="0">Tous</option>'
    . select_options($fournisseurs, $filtreF) . '</select></div>';
?>
<?php if (!$fournisseurs): ?>
    <div class="alert alert-info">Ajoutez d'abord un fournisseur dans <a href="fournisseurs.php">Fournisseurs</a>.</div>
<?php endif; ?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold"><?= $edit ? 'Modifier l\'achat' : 'Nouvel achat' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <div class="mb-3"><label class="form-label">Fournisseur *</label>
                        <select name="fournisseur_id" class="form-select" required>
                            <option value="">— Choisir —</option><?= select_options($fournisseurs, $f['fournisseur_id']) ?>
                        </select></div>
                    <div class="mb-3"><label class="form-label">Date *</label>
                        <input type="date" name="date_achat" class="form-control" required value="<?= e($f['date_achat']) ?>"></div>
                    <div class="mb-3"><label class="form-label">N° facture / BL</label>
                        <input type="text" name="num_facture" class="form-control" maxlength="100" value="<?= e($f['num_facture']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Montant (<?= DEVISE ?>) *</label>
                        <input type="number" step="0.01" min="0.01" name="montant" class="form-control" required value="<?= e($f['montant']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Note</label>
                        <input type="text" name="note" class="form-control" maxlength="255" value="<?= e($f['note']) ?>"></div>
                    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <?= $edit ? cancel_link() : '' ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <?= period_form($du, $au, $filtre) ?>
        <div class="card">
            <div class="card-header">Total de la période : <strong><?= money($total) ?></strong> (<?= count($achats) ?> achat(s))</div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Fournisseur</th><th>N° facture</th><th class="num">Montant</th><th>Note</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($achats as $a): ?>
                        <tr>
                            <td><?= fdate($a['date_achat']) ?></td>
                            <td><a href="fournisseurs.php?id=<?= (int) $a['fournisseur_id'] ?>"><?= e($a['nom']) ?></a></td>
                            <td><?= e($a['num_facture']) ?></td>
                            <td class="num fw-semibold"><?= money($a['montant']) ?></td>
                            <td class="small text-muted"><?= e($a['note']) ?></td>
                            <td class="text-end text-nowrap"><?= edit_link((int) $a['id']) ?> <?= delete_button((int) $a['id']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$achats): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun achat sur cette période.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/inc/footer.php';
