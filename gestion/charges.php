<?php
// gestion/charges.php — Charges fixes et variables (loyer, électricité, eau, …).
require __DIR__ . '/inc/bootstrap.php';

const TYPES_CHARGE = ['fixe' => 'Fixe', 'variable' => 'Variable'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = (int) ($_POST['id'] ?? 0);
        $cid  = (int) ($_POST['categorie_id'] ?? 0);
        $date = $_POST['date_charge'] ?? '';
        $mt   = num($_POST['montant'] ?? 0);
        $mode = $_POST['mode'] ?? '';
        $data = [$cid, $date, $mt, $mode, trim($_POST['note'] ?? '')];
        if (!row('SELECT id FROM g_categories_charges WHERE id=?', [$cid])) {
            flash('Veuillez choisir une catégorie.', 'danger');
        } elseif (!valid_date($date) || $mt <= 0 || !isset(MODES_PAIEMENT[$mode])) {
            flash('Date, montant ou mode de paiement invalide.', 'danger');
        } elseif ($id) {
            exec_sql('UPDATE g_charges SET categorie_id=?, date_charge=?, montant=?, mode=?, note=? WHERE id=?', [...$data, $id]);
            flash('Charge modifiée.');
        } else {
            exec_sql('INSERT INTO g_charges (categorie_id, date_charge, montant, mode, note) VALUES (?,?,?,?,?)', $data);
            flash('Charge enregistrée : ' . money($mt));
        }
    } elseif ($action === 'delete') {
        exec_sql('DELETE FROM g_charges WHERE id=?', [(int) $_POST['id']]);
        flash('Charge supprimée.');
    } elseif ($action === 'cat_save') {
        $nom  = trim($_POST['nom'] ?? '');
        $type = $_POST['type'] ?? '';
        if ($nom === '' || !isset(TYPES_CHARGE[$type])) {
            flash('Nom ou type de catégorie invalide.', 'danger');
        } else {
            exec_sql('INSERT INTO g_categories_charges (nom, type) VALUES (?,?)', [$nom, $type]);
            flash('Catégorie « ' . $nom . ' » ajoutée.');
        }
    } elseif ($action === 'cat_delete') {
        $id = (int) $_POST['id'];
        if (scalar('SELECT COUNT(*) FROM g_charges WHERE categorie_id=?', [$id])) {
            flash('Impossible : cette catégorie est utilisée par des charges.', 'warning');
        } else {
            exec_sql('DELETE FROM g_categories_charges WHERE id=?', [$id]);
            flash('Catégorie supprimée.');
        }
    }
    back();
}

$categories = rows('SELECT * FROM g_categories_charges ORDER BY type, nom');
$optCat = [];
foreach ($categories as $c) {
    $optCat[$c['id']] = $c['nom'] . ' (' . TYPES_CHARGE[$c['type']] . ')';
}

[$du, $au] = period();
$filtreT = $_GET['type'] ?? '';
$filtreC = (int) ($_GET['categorie_id'] ?? 0);
$sql = 'SELECT ch.*, c.nom AS categorie, c.type FROM g_charges ch JOIN g_categories_charges c ON c.id=ch.categorie_id WHERE ch.date_charge BETWEEN ? AND ?';
$p = [$du, $au];
if (isset(TYPES_CHARGE[$filtreT])) {
    $sql .= ' AND c.type=?';
    $p[] = $filtreT;
}
if ($filtreC) {
    $sql .= ' AND ch.categorie_id=?';
    $p[] = $filtreC;
}
$charges = rows($sql . ' ORDER BY ch.date_charge DESC, ch.id DESC', $p);

$totType = ['fixe' => 0, 'variable' => 0];
$parCat = [];
foreach ($charges as $c) {
    $totType[$c['type']] += $c['montant'];
    $parCat[$c['categorie']] = ($parCat[$c['categorie']] ?? 0) + $c['montant'];
}
arsort($parCat);
$total = $totType['fixe'] + $totType['variable'];

$edit = isset($_GET['edit']) ? row('SELECT * FROM g_charges WHERE id=?', [(int) $_GET['edit']]) : null;
$f = $edit ?? ['id' => 0, 'categorie_id' => $filtreC, 'date_charge' => date('Y-m-d'), 'montant' => '', 'mode' => 'especes', 'note' => ''];

$title = 'Charges fixes & variables';
require __DIR__ . '/inc/header.php';
$filtre = '<div class="col-6 col-md-auto"><label class="form-label small mb-0">Type</label>'
    . '<select name="type" class="form-select form-select-sm"><option value="">Tous</option>' . select_options(TYPES_CHARGE, $filtreT) . '</select></div>'
    . '<div class="col-6 col-md-auto"><label class="form-label small mb-0">Catégorie</label>'
    . '<select name="categorie_id" class="form-select form-select-sm"><option value="0">Toutes</option>' . select_options(array_column($categories, 'nom', 'id'), $filtreC) . '</select></div>';
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold"><?= $edit ? 'Modifier la charge' : 'Nouvelle charge' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <div class="mb-3"><label class="form-label">Catégorie *</label>
                        <select name="categorie_id" class="form-select" required>
                            <option value="">— Choisir —</option><?= select_options($optCat, $f['categorie_id']) ?>
                        </select></div>
                    <div class="mb-3"><label class="form-label">Date *</label>
                        <input type="date" name="date_charge" class="form-control" required value="<?= e($f['date_charge']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Montant (<?= DEVISE ?>) *</label>
                        <input type="number" step="0.01" min="0.01" name="montant" class="form-control" required value="<?= e($f['montant']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Mode de paiement</label>
                        <select name="mode" class="form-select"><?= select_options(MODES_PAIEMENT, $f['mode']) ?></select></div>
                    <div class="mb-3"><label class="form-label">Note / Période concernée</label>
                        <input type="text" name="note" class="form-control" maxlength="255" value="<?= e($f['note']) ?>" placeholder="ex. Facture n° … / Octobre"></div>
                    <button class="btn btn-danger"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <?= $edit ? cancel_link() : '' ?>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">Catégories de charges</div>
            <div class="card-body">
                <form method="post" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="cat_save">
                    <div class="col-7"><input type="text" name="nom" class="form-control form-control-sm" placeholder="Nouvelle catégorie" required maxlength="100"></div>
                    <div class="col-3 px-0"><select name="type" class="form-select form-select-sm"><?= select_options(TYPES_CHARGE, 'variable') ?></select></div>
                    <div class="col-2"><button class="btn btn-sm btn-outline-primary w-100" title="Ajouter"><i class="bi bi-plus-lg"></i></button></div>
                </form>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($categories as $c): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                            <span><?= e($c['nom']) ?>
                                <span class="badge <?= $c['type'] === 'fixe' ? 'text-bg-info' : 'text-bg-secondary' ?>"><?= TYPES_CHARGE[$c['type']] ?></span></span>
                            <?= delete_button((int) $c['id'], 'cat_delete', 'Supprimer cette catégorie ?') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?= period_form($du, $au, $filtre) ?>
        <div class="row g-3 mb-3">
            <div class="col-sm-4"><div class="card kpi p-3" style="--kpi:#0dcaf0">
                <div class="kpi-label">Charges fixes</div><div class="kpi-value"><?= money($totType['fixe']) ?></div></div></div>
            <div class="col-sm-4"><div class="card kpi p-3" style="--kpi:#6c757d">
                <div class="kpi-label">Charges variables</div><div class="kpi-value"><?= money($totType['variable']) ?></div></div></div>
            <div class="col-sm-4"><div class="card kpi p-3" style="--kpi:#dc3545">
                <div class="kpi-label">Total charges</div><div class="kpi-value"><?= money($total) ?></div></div></div>
        </div>
        <?php if ($parCat): ?>
            <div class="card mb-3"><div class="card-body py-2">
                <?php foreach ($parCat as $nom => $v): ?>
                    <div class="d-flex justify-content-between small"><span><?= e($nom) ?></span><span><?= money($v) ?></span></div>
                    <div class="progress mb-2" style="height:6px"><div class="progress-bar bg-danger" style="width:<?= $total ? round($v / $total * 100, 1) : 0 ?>%"></div></div>
                <?php endforeach; ?>
            </div></div>
        <?php endif; ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Catégorie</th><th>Type</th><th>Mode</th><th class="num">Montant</th><th>Note</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($charges as $c): ?>
                        <tr>
                            <td><?= fdate($c['date_charge']) ?></td>
                            <td><?= e($c['categorie']) ?></td>
                            <td><span class="badge <?= $c['type'] === 'fixe' ? 'text-bg-info' : 'text-bg-secondary' ?>"><?= TYPES_CHARGE[$c['type']] ?></span></td>
                            <td><?= e(mode_label($c['mode'])) ?></td>
                            <td class="num fw-semibold"><?= money($c['montant']) ?></td>
                            <td class="small text-muted"><?= e($c['note']) ?></td>
                            <td class="text-end text-nowrap"><?= edit_link((int) $c['id']) ?> <?= delete_button((int) $c['id']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$charges): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucune charge sur cette période.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/inc/footer.php';
