<?php
// gestion/ventes.php — Entrées du chiffre d'affaires (espèces / carte).
require __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id      = (int) ($_POST['id'] ?? 0);
        $date    = $_POST['date_vente'] ?? '';
        $especes = num($_POST['montant_especes'] ?? 0);
        $carte   = num($_POST['montant_carte'] ?? 0);
        $couv    = max(0, (int) ($_POST['nb_couverts'] ?? 0));
        $note    = trim($_POST['note'] ?? '');
        if (!valid_date($date)) {
            flash('Date invalide.', 'danger');
        } elseif ($especes < 0 || $carte < 0 || ($especes + $carte) <= 0) {
            flash('Veuillez saisir un montant en espèces et/ou par carte.', 'danger');
        } elseif ($id) {
            exec_sql('UPDATE g_ventes SET date_vente=?, montant_especes=?, montant_carte=?, nb_couverts=?, note=? WHERE id=?',
                [$date, $especes, $carte, $couv, $note, $id]);
            flash('Entrée modifiée.');
        } else {
            exec_sql('INSERT INTO g_ventes (date_vente, montant_especes, montant_carte, nb_couverts, note) VALUES (?,?,?,?,?)',
                [$date, $especes, $carte, $couv, $note]);
            flash('Chiffre d\'affaires enregistré : ' . money($especes + $carte));
        }
    } elseif ($action === 'delete') {
        exec_sql('DELETE FROM g_ventes WHERE id=?', [(int) $_POST['id']]);
        flash('Entrée supprimée.');
    }
    back();
}

[$du, $au] = period();
$ventes = rows('SELECT * FROM g_ventes WHERE date_vente BETWEEN ? AND ? ORDER BY date_vente DESC, id DESC', [$du, $au]);
$totE = array_sum(array_column($ventes, 'montant_especes'));
$totC = array_sum(array_column($ventes, 'montant_carte'));
$nbJours = count(array_unique(array_column($ventes, 'date_vente')));
$totCouv = array_sum(array_column($ventes, 'nb_couverts'));
$caCouv  = array_sum(array_map(fn($v) => $v['nb_couverts'] > 0 ? $v['montant_especes'] + $v['montant_carte'] : 0, $ventes));

$edit = isset($_GET['edit']) ? row('SELECT * FROM g_ventes WHERE id=?', [(int) $_GET['edit']]) : null;
$f = $edit ?? ['id' => 0, 'date_vente' => date('Y-m-d'), 'montant_especes' => '', 'montant_carte' => '', 'nb_couverts' => '', 'note' => ''];

$title = "Chiffre d'affaires";
require __DIR__ . '/inc/header.php';
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold"><?= $edit ? 'Modifier l\'entrée' : 'Nouvelle entrée de caisse' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date_vente" class="form-control" required value="<?= e($f['date_vente']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-cash text-success"></i> Montant en espèces (<?= DEVISE ?>)</label>
                        <input type="number" step="0.01" min="0" name="montant_especes" id="m_esp" class="form-control" value="<?= e($f['montant_especes']) ?>" placeholder="0,00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-credit-card text-primary"></i> Montant par carte (<?= DEVISE ?>)</label>
                        <input type="number" step="0.01" min="0" name="montant_carte" id="m_cb" class="form-control" value="<?= e($f['montant_carte']) ?>" placeholder="0,00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-people"></i> Nombre de couverts</label>
                        <input type="number" min="0" step="1" name="nb_couverts" class="form-control" value="<?= e($f['nb_couverts']) ?>" placeholder="Optionnel">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <input type="text" name="note" maxlength="255" class="form-control" value="<?= e($f['note']) ?>" placeholder="Service midi / soir, événement…">
                    </div>
                    <div class="alert alert-light py-2">Total : <strong id="m_tot">0,00</strong> <?= DEVISE ?></div>
                    <button class="btn btn-success"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <?= $edit ? cancel_link() : '' ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?= period_form($du, $au) ?>
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3"><div class="card kpi p-3 h-100" style="--kpi:#198754">
                <div class="kpi-label">Espèces</div><div class="kpi-value"><?= money($totE) ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="card kpi p-3 h-100" style="--kpi:#0d6efd">
                <div class="kpi-label">Carte</div><div class="kpi-value"><?= money($totC) ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="card kpi p-3 h-100" style="--kpi:#6f42c1">
                <div class="kpi-label">Total</div><div class="kpi-value"><?= money($totE + $totC) ?></div>
                <div class="kpi-sub"><?= $nbJours ?> jour(s) — moyenne <?= money($nbJours ? ($totE + $totC) / $nbJours : 0) ?>/jour</div></div></div>
            <div class="col-6 col-xl-3"><div class="card kpi p-3 h-100" style="--kpi:#fd7e14">
                <div class="kpi-label">Couverts</div><div class="kpi-value"><?= number_format($totCouv, 0, ',', ' ') ?></div>
                <div class="kpi-sub">Ticket moyen <?= money($totCouv ? $caCouv / $totCouv : 0) ?></div></div></div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>Date</th><th class="num">Espèces</th><th class="num">Carte</th><th class="num">Total</th><th class="num">Couverts</th><th class="num">Ticket moy.</th><th>Note</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($ventes as $v): ?>
                        <tr>
                            <td><?= fdate($v['date_vente']) ?></td>
                            <td class="num"><?= money($v['montant_especes']) ?></td>
                            <td class="num"><?= money($v['montant_carte']) ?></td>
                            <td class="num fw-semibold"><?= money($v['montant_especes'] + $v['montant_carte']) ?></td>
                            <td class="num"><?= $v['nb_couverts'] ?: '' ?></td>
                            <td class="num small"><?= $v['nb_couverts'] ? money(($v['montant_especes'] + $v['montant_carte']) / $v['nb_couverts']) : '' ?></td>
                            <td class="text-muted small"><?= e($v['note']) ?></td>
                            <td class="text-end text-nowrap"><?= edit_link((int) $v['id']) ?> <?= delete_button((int) $v['id']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$ventes): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Aucune entrée sur cette période.</td></tr>
                    <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-semibold"><tr>
                        <td>Total</td><td class="num"><?= money($totE) ?></td><td class="num"><?= money($totC) ?></td>
                        <td class="num"><?= money($totE + $totC) ?></td><td class="num"><?= $totCouv ?: '' ?></td>
                        <td class="num small"><?= $totCouv ? money($caCouv / $totCouv) : '' ?></td><td colspan="2"></td>
                    </tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
$scripts = <<<'JS'
<script>
(function () {
    const e = document.getElementById('m_esp'), c = document.getElementById('m_cb'), t = document.getElementById('m_tot');
    const upd = () => t.textContent = ((parseFloat(e.value) || 0) + (parseFloat(c.value) || 0))
        .toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    e.addEventListener('input', upd); c.addEventListener('input', upd); upd();
})();
</script>
JS;
require __DIR__ . '/inc/footer.php';
