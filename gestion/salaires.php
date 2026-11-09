<?php
// gestion/salaires.php — Paie mensuelle : salaire de base + primes − retenues − avances du mois.
require __DIR__ . '/inc/bootstrap.php';

$mois = $_GET['mois'] ?? date('Y-m');
if (!valid_month($mois)) {
    $mois = date('Y-m');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'payer') {
        $eid  = (int) ($_POST['employe_id'] ?? 0);
        $emp  = row('SELECT * FROM g_employes WHERE id=?', [$eid]);
        $date = $_POST['date_paiement'] ?? '';
        $mode = $_POST['mode'] ?? '';
        $base = num($_POST['salaire_base'] ?? 0);
        $prim = num($_POST['primes'] ?? 0);
        $ret  = num($_POST['retenues'] ?? 0);
        $av   = (float) scalar('SELECT COALESCE(SUM(montant),0) FROM g_avances WHERE employe_id=? AND mois_deduction=?', [$eid, $mois]);
        $net  = round($base + $prim - $ret - $av, 2);
        if (!$emp) {
            flash('Employé introuvable.', 'danger');
        } elseif (row('SELECT id FROM g_salaires WHERE employe_id=? AND mois=?', [$eid, $mois])) {
            flash('Le salaire de ' . $emp['nom'] . ' pour ' . mois_label($mois) . ' est déjà payé.', 'warning');
        } elseif (!valid_date($date) || !isset(MODES_PAIEMENT[$mode]) || $base < 0 || $prim < 0 || $ret < 0) {
            flash('Données de paiement invalides.', 'danger');
        } elseif ($net < 0) {
            flash('Le net à payer est négatif (' . money($net) . ') : vérifiez les avances et retenues.', 'danger');
        } else {
            exec_sql('INSERT INTO g_salaires (employe_id, mois, salaire_base, primes, retenues, avances, net_paye, date_paiement, mode, note)
                      VALUES (?,?,?,?,?,?,?,?,?,?)',
                [$eid, $mois, $base, $prim, $ret, $av, $net, $date, $mode, trim($_POST['note'] ?? '')]);
            flash('Salaire de ' . $emp['nom'] . ' payé : ' . money($net) . ' net.');
        }
    } elseif ($action === 'annuler') {
        exec_sql('DELETE FROM g_salaires WHERE id=?', [(int) $_POST['id']]);
        flash('Paiement du salaire annulé.');
    }
    redirect('salaires.php?mois=' . urlencode($mois));
}

$lignes = rows(
    'SELECT e.id AS employe_id, e.nom, e.poste, e.actif, e.salaire_base AS base_fiche,
        (SELECT COALESCE(SUM(montant),0) FROM g_avances a WHERE a.employe_id=e.id AND a.mois_deduction=?) AS avances_mois,
        s.id AS salaire_id, s.salaire_base, s.primes, s.retenues, s.avances, s.net_paye, s.date_paiement, s.mode
     FROM g_employes e
     LEFT JOIN g_salaires s ON s.employe_id=e.id AND s.mois=?
     WHERE e.actif=1 OR s.id IS NOT NULL
     ORDER BY e.nom',
    [$mois, $mois]
);

$tot = ['brut' => 0, 'avances' => 0, 'net' => 0, 'paye' => 0, 'reste' => 0];
foreach ($lignes as $l) {
    if ($l['salaire_id']) {
        $tot['brut'] += $l['salaire_base'] + $l['primes'] - $l['retenues'];
        $tot['avances'] += $l['avances'];
        $tot['paye'] += $l['net_paye'];
    } else {
        $tot['brut'] += $l['base_fiche'];
        $tot['avances'] += $l['avances_mois'];
        $tot['reste'] += max(0, $l['base_fiche'] - $l['avances_mois']);
    }
}

$prev = date('Y-m', strtotime($mois . '-01 -1 month'));
$next = date('Y-m', strtotime($mois . '-01 +1 month'));
$defaultDate = $mois === date('Y-m') ? date('Y-m-d') : date('Y-m-t', strtotime($mois . '-01'));

$title = 'Salaires';
require __DIR__ . '/inc/header.php';
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="?mois=<?= $prev ?>"><i class="bi bi-chevron-left"></i></a>
    <form method="get" class="d-flex gap-2">
        <input type="month" name="mois" value="<?= e($mois) ?>" class="form-control form-control-sm" onchange="this.form.submit()">
    </form>
    <a class="btn btn-outline-secondary btn-sm" href="?mois=<?= $next ?>"><i class="bi bi-chevron-right"></i></a>
    <span class="fs-5 fw-semibold ms-2"><?= e(mois_label($mois)) ?></span>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card kpi p-3" style="--kpi:#6f42c1">
        <div class="kpi-label">Salaires bruts</div><div class="kpi-value"><?= money($tot['brut']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card kpi p-3" style="--kpi:#fd7e14">
        <div class="kpi-label">Avances à déduire</div><div class="kpi-value"><?= money($tot['avances']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card kpi p-3" style="--kpi:#198754">
        <div class="kpi-label">Net déjà payé</div><div class="kpi-value"><?= money($tot['paye']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card kpi p-3" style="--kpi:#dc3545">
        <div class="kpi-label">Reste à payer (estimé)</div><div class="kpi-value"><?= money($tot['reste']) ?></div></div></div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr>
                <th>Employé</th><th class="num">Salaire base</th><th class="num">Primes</th><th class="num">Retenues</th>
                <th class="num">Avances</th><th class="num">Net à payer</th><th>Paiement</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($lignes as $l): ?>
                <?php if ($l['salaire_id']): ?>
                    <tr class="table-success-subtle">
                        <td class="fw-semibold"><?= e($l['nom']) ?><div class="small text-muted fw-normal"><?= e($l['poste']) ?></div></td>
                        <td class="num"><?= money($l['salaire_base']) ?></td>
                        <td class="num"><?= money($l['primes']) ?></td>
                        <td class="num"><?= money($l['retenues']) ?></td>
                        <td class="num"><?= money($l['avances']) ?></td>
                        <td class="num fw-bold"><?= money($l['net_paye']) ?></td>
                        <td><span class="badge text-bg-success"><i class="bi bi-check2"></i> Payé</span>
                            <div class="small text-muted"><?= fdate($l['date_paiement']) ?> · <?= e(mode_label($l['mode'])) ?></div></td>
                        <td class="text-end"><?= delete_button((int) $l['salaire_id'], 'annuler', 'Annuler ce paiement de salaire ?') ?></td>
                    </tr>
                <?php else: $fid = 'pay' . (int) $l['employe_id']; ?>
                    <tr data-row="<?= $fid ?>" data-avances="<?= e($l['avances_mois']) ?>">
                        <td class="fw-semibold"><?= e($l['nom']) ?><div class="small text-muted fw-normal"><?= e($l['poste']) ?></div></td>
                        <td class="num"><input form="<?= $fid ?>" type="number" step="0.01" min="0" name="salaire_base" value="<?= e($l['base_fiche']) ?>" class="form-control form-control-sm text-end calc" style="width:110px;margin-left:auto"></td>
                        <td class="num"><input form="<?= $fid ?>" type="number" step="0.01" min="0" name="primes" value="0" class="form-control form-control-sm text-end calc" style="width:95px;margin-left:auto"></td>
                        <td class="num"><input form="<?= $fid ?>" type="number" step="0.01" min="0" name="retenues" value="0" class="form-control form-control-sm text-end calc" style="width:95px;margin-left:auto"></td>
                        <td class="num"><a href="avances.php?employe_id=<?= (int) $l['employe_id'] ?>&du=<?= e($mois) ?>-01&au=<?= e(date('Y-m-t', strtotime($mois . '-01'))) ?>"><?= money($l['avances_mois']) ?></a></td>
                        <td class="num fw-bold net"></td>
                        <td>
                            <div class="d-flex gap-1">
                                <input form="<?= $fid ?>" type="date" name="date_paiement" value="<?= e($defaultDate) ?>" class="form-control form-control-sm" style="width:140px">
                                <select form="<?= $fid ?>" name="mode" class="form-select form-select-sm" style="width:110px"><?= select_options(MODES_PAIEMENT, 'especes') ?></select>
                            </div>
                        </td>
                        <td class="text-end">
                            <form id="<?= $fid ?>" method="post" onsubmit="return confirm('Confirmer le paiement du salaire de <?= e(addslashes($l['nom'])) ?> ?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="payer">
                                <input type="hidden" name="employe_id" value="<?= (int) $l['employe_id'] ?>">
                                <button class="btn btn-sm btn-success text-nowrap"><i class="bi bi-cash"></i> Payer</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$lignes): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Aucun employé actif. <a href="employes.php">Ajouter un employé</a></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="small text-muted mt-2">Net à payer = salaire de base + primes − retenues − avances à déduire ce mois-ci.</p>
<?php
$scripts = <<<'JS'
<script>
document.querySelectorAll('tr[data-row]').forEach(function (tr) {
    const id = tr.dataset.row, av = parseFloat(tr.dataset.avances) || 0;
    const v = n => parseFloat(document.querySelector('[form="' + id + '"][name="' + n + '"]').value) || 0;
    const upd = () => {
        const net = v('salaire_base') + v('primes') - v('retenues') - av;
        const cell = tr.querySelector('.net');
        cell.textContent = net.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' DH';
        cell.classList.toggle('text-danger', net < 0);
    };
    tr.querySelectorAll('.calc').forEach(i => i.addEventListener('input', upd));
    upd();
});
</script>
JS;
require __DIR__ . '/inc/footer.php';
