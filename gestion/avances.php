<?php
// gestion/avances.php — Avances sur salaire du personnel.
require __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = (int) ($_POST['id'] ?? 0);
        $eid  = (int) ($_POST['employe_id'] ?? 0);
        $date = $_POST['date_avance'] ?? '';
        $mt   = num($_POST['montant'] ?? 0);
        $md   = $_POST['mois_deduction'] ?? '';
        $emp  = row('SELECT * FROM g_employes WHERE id=?', [$eid]);
        $old  = $id ? row('SELECT * FROM g_avances WHERE id=?', [$id]) : null;
        $deja = row('SELECT id FROM g_salaires WHERE employe_id=? AND mois=?', [$eid, $md]);
        $verrou = $old ? row('SELECT id FROM g_salaires WHERE employe_id=? AND mois=?', [$old['employe_id'], $old['mois_deduction']]) : null;
        if (!$emp) {
            flash('Veuillez choisir un employé.', 'danger');
        } elseif (!valid_date($date) || $mt <= 0 || !valid_month($md)) {
            flash('Date, montant ou mois de déduction invalide.', 'danger');
        } elseif ($deja || $verrou) {
            flash('Le salaire de ' . mois_label($verrou ? $old['mois_deduction'] : $md) . ' est déjà payé pour cet employé : choisissez un autre mois de déduction (ou annulez le paiement du salaire).', 'warning');
        } else {
            $data = [$eid, $date, $mt, $md, trim($_POST['note'] ?? '')];
            if ($id) {
                exec_sql('UPDATE g_avances SET employe_id=?, date_avance=?, montant=?, mois_deduction=?, note=? WHERE id=?', [...$data, $id]);
                flash('Avance modifiée.');
            } else {
                exec_sql('INSERT INTO g_avances (employe_id, date_avance, montant, mois_deduction, note) VALUES (?,?,?,?,?)', $data);
                flash('Avance de ' . money($mt) . ' enregistrée pour ' . $emp['nom'] . '.');
            }
            $cumul = scalar('SELECT COALESCE(SUM(montant),0) FROM g_avances WHERE employe_id=? AND mois_deduction=?', [$eid, $md]);
            if ($emp['salaire_base'] > 0 && $cumul > $emp['salaire_base']) {
                flash('Attention : les avances de ' . mois_label($md) . ' (' . money($cumul) . ') dépassent le salaire de base (' . money($emp['salaire_base']) . ').', 'warning');
            }
        }
    } elseif ($action === 'delete') {
        $a = row('SELECT * FROM g_avances WHERE id=?', [(int) $_POST['id']]);
        if ($a && row('SELECT id FROM g_salaires WHERE employe_id=? AND mois=?', [$a['employe_id'], $a['mois_deduction']])) {
            flash('Cette avance a déjà été déduite d\'un salaire payé : annulez d\'abord le paiement du salaire.', 'warning');
        } else {
            exec_sql('DELETE FROM g_avances WHERE id=?', [(int) $_POST['id']]);
            flash('Avance supprimée.');
        }
    }
    back();
}

$employes = array_column(rows('SELECT id, nom FROM g_employes WHERE actif=1 ORDER BY nom'), 'nom', 'id');
$tous = array_column(rows('SELECT id, nom FROM g_employes ORDER BY nom'), 'nom', 'id');
[$du, $au] = period();
$filtreE = (int) ($_GET['employe_id'] ?? 0);

$sql = 'SELECT a.*, e.nom,
        (SELECT COUNT(*) FROM g_salaires s WHERE s.employe_id=a.employe_id AND s.mois=a.mois_deduction) AS deduite
        FROM g_avances a JOIN g_employes e ON e.id=a.employe_id WHERE a.date_avance BETWEEN ? AND ?';
$p = [$du, $au];
if ($filtreE) {
    $sql .= ' AND a.employe_id=?';
    $p[] = $filtreE;
}
$avances = rows($sql . ' ORDER BY a.date_avance DESC, a.id DESC', $p);
$total = array_sum(array_column($avances, 'montant'));

$edit = isset($_GET['edit']) ? row('SELECT * FROM g_avances WHERE id=?', [(int) $_GET['edit']]) : null;
$f = $edit ?? ['id' => 0, 'employe_id' => $filtreE, 'date_avance' => date('Y-m-d'), 'montant' => '', 'mois_deduction' => date('Y-m'), 'note' => ''];
if ($edit && !isset($employes[$edit['employe_id']])) {
    $employes[$edit['employe_id']] = $tous[$edit['employe_id']] ?? '';
}

$title = 'Avances du personnel';
require __DIR__ . '/inc/header.php';
$filtre = '<div class="col-12 col-md-auto"><label class="form-label small mb-0">Employé</label>'
    . '<select name="employe_id" class="form-select form-select-sm"><option value="0">Tous</option>'
    . select_options($tous, $filtreE) . '</select></div>';
?>
<?php if (!$tous): ?>
    <div class="alert alert-info">Ajoutez d'abord un employé dans <a href="employes.php">Employés</a>.</div>
<?php endif; ?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold"><?= $edit ? 'Modifier l\'avance' : 'Nouvelle avance' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <div class="mb-3"><label class="form-label">Employé *</label>
                        <select name="employe_id" class="form-select" required>
                            <option value="">— Choisir —</option><?= select_options($employes, $f['employe_id']) ?>
                        </select></div>
                    <div class="mb-3"><label class="form-label">Date *</label>
                        <input type="date" name="date_avance" class="form-control" required value="<?= e($f['date_avance']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Montant (<?= DEVISE ?>) *</label>
                        <input type="number" step="0.01" min="0.01" name="montant" class="form-control" required value="<?= e($f['montant']) ?>"></div>
                    <div class="mb-3"><label class="form-label">À déduire du salaire de *</label>
                        <input type="month" name="mois_deduction" class="form-control" required value="<?= e($f['mois_deduction']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Note</label>
                        <input type="text" name="note" class="form-control" maxlength="255" value="<?= e($f['note']) ?>"></div>
                    <button class="btn btn-warning"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <?= $edit ? cancel_link() : '' ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <?= period_form($du, $au, $filtre) ?>
        <div class="card">
            <div class="card-header">Total des avances versées : <strong><?= money($total) ?></strong></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Employé</th><th class="num">Montant</th><th>Déduction</th><th>Note</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($avances as $a): ?>
                        <tr>
                            <td><?= fdate($a['date_avance']) ?></td>
                            <td><?= e($a['nom']) ?></td>
                            <td class="num fw-semibold"><?= money($a['montant']) ?></td>
                            <td><?= e(mois_label($a['mois_deduction'])) ?>
                                <?= $a['deduite'] ? '<span class="badge text-bg-success">Déduite</span>' : '<span class="badge text-bg-warning">En attente</span>' ?></td>
                            <td class="small text-muted"><?= e($a['note']) ?></td>
                            <td class="text-end text-nowrap"><?= $a['deduite'] ? '' : edit_link((int) $a['id']) . ' ' . delete_button((int) $a['id']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$avances): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune avance sur cette période.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/inc/footer.php';
