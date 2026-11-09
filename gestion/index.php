<?php
// gestion/index.php — Tableau de bord.
require __DIR__ . '/inc/bootstrap.php';

[$du, $au] = period();
$p = [$du, $au];

// ---- Indicateurs de la période ----
$ca = row('SELECT COALESCE(SUM(montant_especes),0) AS esp, COALESCE(SUM(montant_carte),0) AS cb FROM g_ventes WHERE date_vente BETWEEN ? AND ?', $p);
$caTotal = $ca['esp'] + $ca['cb'];

$achats    = (float) scalar('SELECT COALESCE(SUM(montant),0) FROM g_achats WHERE date_achat BETWEEN ? AND ?', $p);
$paiements = (float) scalar('SELECT COALESCE(SUM(montant),0) FROM g_paiements_fournisseurs WHERE date_paiement BETWEEN ? AND ?', $p);
$detteFour = (float) scalar('SELECT COALESCE(SUM(montant),0) FROM g_achats') - (float) scalar('SELECT COALESCE(SUM(montant),0) FROM g_paiements_fournisseurs');

$sal = row('SELECT COALESCE(SUM(salaire_base + primes - retenues),0) AS brut, COALESCE(SUM(net_paye),0) AS net, COUNT(*) AS nb
            FROM g_salaires WHERE date_paiement BETWEEN ? AND ?', $p);
$avances = (float) scalar('SELECT COALESCE(SUM(montant),0) FROM g_avances WHERE date_avance BETWEEN ? AND ?', $p);

$ch = ['fixe' => 0.0, 'variable' => 0.0];
foreach (rows('SELECT c.type, SUM(ch.montant) AS t FROM g_charges ch JOIN g_categories_charges c ON c.id=ch.categorie_id
               WHERE ch.date_charge BETWEEN ? AND ? GROUP BY c.type', $p) as $r) {
    $ch[$r['type']] = (float) $r['t'];
}
$charges = $ch['fixe'] + $ch['variable'];

$depenses = $achats + $sal['brut'] + $charges;
$resultat = $caTotal - $depenses;
$decaisse = $paiements + $sal['net'] + $avances + $charges;
$tresorerie = $caTotal - $decaisse;

// ---- Graphique 1 : CA par jour (ou par mois si période longue) ----
$long = (strtotime($au) - strtotime($du)) > 62 * 86400;
$caSerie = [];
foreach (rows('SELECT date_vente, montant_especes, montant_carte FROM g_ventes WHERE date_vente BETWEEN ? AND ?', $p) as $r) {
    $k = $long ? substr($r['date_vente'], 0, 7) : $r['date_vente'];
    $caSerie[$k]['esp'] = ($caSerie[$k]['esp'] ?? 0) + $r['montant_especes'];
    $caSerie[$k]['cb']  = ($caSerie[$k]['cb'] ?? 0) + $r['montant_carte'];
}
$labels1 = $esp1 = $cb1 = [];
$cur = strtotime($du);
while ($cur <= strtotime($au)) {
    $k = $long ? date('Y-m', $cur) : date('Y-m-d', $cur);
    $labels1[] = $long ? mois_label($k) : date('d/m', $cur);
    $esp1[] = round($caSerie[$k]['esp'] ?? 0, 2);
    $cb1[]  = round($caSerie[$k]['cb'] ?? 0, 2);
    $cur = $long ? strtotime('first day of next month', $cur) : strtotime('+1 day', $cur);
}

// ---- Graphique 2 : 12 derniers mois CA vs dépenses ----
$debut12 = date('Y-m-01', strtotime('first day of -11 months'));
$mois12 = [];
for ($i = 11; $i >= 0; $i--) {
    $mois12[date('Y-m', strtotime("first day of -$i months"))] = ['ca' => 0, 'dep' => 0];
}
$add = function (string $sql, string $key) use (&$mois12, $debut12) {
    foreach (rows($sql, [$debut12]) as $r) {
        $m = substr($r['d'], 0, 7);
        if (isset($mois12[$m])) {
            $mois12[$m][$key] += $r['m'];
        }
    }
};
$add('SELECT date_vente AS d, montant_especes + montant_carte AS m FROM g_ventes WHERE date_vente >= ?', 'ca');
$add('SELECT date_achat AS d, montant AS m FROM g_achats WHERE date_achat >= ?', 'dep');
$add('SELECT date_paiement AS d, salaire_base + primes - retenues AS m FROM g_salaires WHERE date_paiement >= ?', 'dep');
$add('SELECT date_charge AS d, montant AS m FROM g_charges WHERE date_charge >= ?', 'dep');

$chart2 = [
    'labels' => array_map(fn($m) => ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'][(int) substr($m, 5, 2) - 1]
        . ' ' . substr($m, 2, 2), array_keys($mois12)),
    'ca'     => array_map(fn($v) => round($v['ca'], 2), array_values($mois12)),
    'dep'    => array_map(fn($v) => round($v['dep'], 2), array_values($mois12)),
];

// ---- Tableaux ----
$topDettes = rows(
    'SELECT f.id, f.nom,
        (SELECT COALESCE(SUM(montant),0) FROM g_achats a WHERE a.fournisseur_id=f.id)
      - (SELECT COALESCE(SUM(montant),0) FROM g_paiements_fournisseurs p WHERE p.fournisseur_id=f.id) AS solde
     FROM g_fournisseurs f ORDER BY solde DESC LIMIT 5'
);
$topDettes = array_filter($topDettes, fn($r) => $r['solde'] > 0);

$chargesCat = rows('SELECT c.nom, c.type, SUM(ch.montant) AS t FROM g_charges ch JOIN g_categories_charges c ON c.id=ch.categorie_id
                    WHERE ch.date_charge BETWEEN ? AND ? GROUP BY c.id, c.nom, c.type ORDER BY t DESC', $p);

// ---- Rappels ----
$rappels = [];
$moisPrec = date('Y-m', strtotime('first day of last month'));
$nonPayes = rows('SELECT nom FROM g_employes e WHERE actif=1 AND NOT EXISTS (SELECT 1 FROM g_salaires s WHERE s.employe_id=e.id AND s.mois=?)', [$moisPrec]);
if ($nonPayes) {
    $rappels[] = ['salaires.php?mois=' . $moisPrec, 'Salaires de ' . mois_label($moisPrec) . ' non payés : ' . implode(', ', array_column($nonPayes, 'nom'))];
}
$fixesManquantes = rows("SELECT c.nom FROM g_categories_charges c
    WHERE c.type='fixe' AND EXISTS (SELECT 1 FROM g_charges x WHERE x.categorie_id=c.id)
    AND NOT EXISTS (SELECT 1 FROM g_charges x WHERE x.categorie_id=c.id AND x.date_charge BETWEEN ? AND ?)",
    [date('Y-m-01'), date('Y-m-t')]);
if ($fixesManquantes) {
    $rappels[] = ['charges.php?type=fixe', 'Charges fixes non saisies ce mois-ci : ' . implode(', ', array_column($fixesManquantes, 'nom'))];
}
if (!scalar('SELECT COUNT(*) FROM g_ventes WHERE date_vente=?', [date('Y-m-d', strtotime('-1 day'))])) {
    $rappels[] = ['ventes.php', 'Le chiffre d\'affaires d\'hier (' . date('d/m/Y', strtotime('-1 day')) . ') n\'a pas été saisi.'];
}

function kpi(string $label, $value, string $color, string $icon, string $sub = '', string $href = ''): string
{
    $tag = $href ? 'a href="' . e($href) . '"' : 'div';
    $end = $href ? 'a' : 'div';
    return '<div class="col-6 col-xl-3"><' . $tag . ' class="card kpi p-3 h-100 text-reset text-decoration-none" style="--kpi:' . $color . '">'
        . '<div class="d-flex justify-content-between"><div class="kpi-label">' . e($label) . '</div><i class="bi ' . $icon . '" style="color:' . $color . '"></i></div>'
        . '<div class="kpi-value">' . money($value) . '</div>'
        . ($sub ? '<div class="kpi-sub">' . $sub . '</div>' : '') . '</' . $end . '></div>';
}

$title = 'Tableau de bord';
$charts = true;
require __DIR__ . '/inc/header.php';
?>
<?= period_form($du, $au) ?>

<?php foreach ($rappels as [$href, $msg]): ?>
    <div class="alert alert-warning py-2 mb-2"><i class="bi bi-bell"></i> <a href="<?= e($href) ?>" class="alert-link fw-normal"><?= e($msg) ?></a></div>
<?php endforeach; ?>

<div class="row g-3 mb-3 mt-1">
    <?= kpi("Chiffre d'affaires", $caTotal, '#6f42c1', 'bi-graph-up-arrow',
        'Espèces ' . money($ca['esp']) . '<br>Carte ' . money($ca['cb']), 'ventes.php') ?>
    <?= kpi('Achats fournisseurs', $achats, '#0d6efd', 'bi-cart3',
        'Payé ' . money($paiements) . '<br>Dette totale <span class="text-danger">' . money($detteFour) . '</span>', 'achats.php') ?>
    <?= kpi('Salaires', $sal['brut'], '#fd7e14', 'bi-people',
        'Net payé ' . money($sal['net']) . ' (' . (int) $sal['nb'] . ')<br>Avances versées ' . money($avances), 'salaires.php') ?>
    <?= kpi('Charges', $charges, '#dc3545', 'bi-receipt',
        'Fixes ' . money($ch['fixe']) . '<br>Variables ' . money($ch['variable']), 'charges.php') ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card p-3 h-100 border-0 text-white" style="background:<?= $resultat >= 0 ? '#198754' : '#dc3545' ?>">
            <div class="small text-uppercase opacity-75">Résultat estimé de la période</div>
            <div class="fs-2 fw-bold"><?= money($resultat) ?></div>
            <div class="small opacity-75">CA − (achats + salaires + charges) = <?= money($caTotal) ?> − <?= money($depenses) ?>
                <?= $caTotal > 0 ? ' · marge ' . number_format($resultat / $caTotal * 100, 1, ',', ' ') . ' %' : '' ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 h-100 border-0 text-white" style="background:<?= $tresorerie >= 0 ? '#0d6efd' : '#b02a37' ?>">
            <div class="small text-uppercase opacity-75">Flux de trésorerie de la période</div>
            <div class="fs-2 fw-bold"><?= money($tresorerie) ?></div>
            <div class="small opacity-75">Encaissé <?= money($caTotal) ?> − décaissé <?= money($decaisse) ?>
                (fournisseurs, salaires nets, avances, charges)</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header fw-semibold">Chiffre d'affaires <?= $long ? 'par mois' : 'par jour' ?></div>
            <div class="card-body"><div class="chart-box"><canvas id="chartCA"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold">Répartition des dépenses</div>
            <div class="card-body"><div class="chart-box">
                <?php if ($depenses > 0): ?><canvas id="chartDep"></canvas>
                <?php else: ?><p class="text-muted text-center pt-5">Aucune dépense sur la période.</p><?php endif; ?>
            </div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">12 derniers mois : CA vs dépenses</div>
            <div class="card-body"><div class="chart-box"><canvas id="chart12"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Dettes fournisseurs</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($topDettes as $r): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <a href="fournisseurs.php?id=<?= (int) $r['id'] ?>" class="text-decoration-none"><?= e($r['nom']) ?></a>
                        <span class="text-danger fw-semibold"><?= money($r['solde']) ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$topDettes): ?><li class="list-group-item text-muted">Aucune dette en cours.</li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Charges par catégorie</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($chargesCat as $r): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= e($r['nom']) ?> <small class="text-muted"><?= $r['type'] === 'fixe' ? 'fixe' : 'var.' ?></small></span>
                        <span class="fw-semibold"><?= money($r['t']) ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$chargesCat): ?><li class="list-group-item text-muted">Aucune charge.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>
<?php
$data = json_encode([
    'labels1' => $labels1, 'esp' => $esp1, 'cb' => $cb1,
    'dep' => [round($achats, 2), round($sal['brut'], 2), round($ch['fixe'], 2), round($ch['variable'], 2)],
    'c2' => $chart2,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
$scripts = <<<JS
<script>
const D = $data;
const fmt = v => v.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' DH';
const tip = {callbacks: {label: c => ' ' + c.dataset.label + ' : ' + fmt(c.parsed.y ?? c.parsed)}};
new Chart(document.getElementById('chartCA'), {
    type: 'bar',
    data: {labels: D.labels1, datasets: [
        {label: 'Espèces', data: D.esp, backgroundColor: '#198754'},
        {label: 'Carte', data: D.cb, backgroundColor: '#0d6efd'}
    ]},
    options: {maintainAspectRatio: false, scales: {x: {stacked: true}, y: {stacked: true, beginAtZero: true}}, plugins: {tooltip: tip}}
});
new Chart(document.getElementById('chart12'), {
    type: 'bar',
    data: {labels: D.c2.labels, datasets: [
        {label: "Chiffre d'affaires", data: D.c2.ca, backgroundColor: '#6f42c1'},
        {label: 'Dépenses', data: D.c2.dep, backgroundColor: '#dc3545'}
    ]},
    options: {maintainAspectRatio: false, scales: {y: {beginAtZero: true}}, plugins: {tooltip: tip}}
});
const dep = document.getElementById('chartDep');
if (dep) new Chart(dep, {
    type: 'doughnut',
    data: {labels: ['Achats', 'Salaires', 'Charges fixes', 'Charges variables'],
        datasets: [{data: D.dep, backgroundColor: ['#0d6efd', '#fd7e14', '#0dcaf0', '#6c757d']}]},
    options: {maintainAspectRatio: false, plugins: {legend: {position: 'bottom'},
        tooltip: {callbacks: {label: c => ' ' + c.label + ' : ' + fmt(c.parsed)}}}}
});
</script>
JS;
require __DIR__ . '/inc/footer.php';
