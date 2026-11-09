<?php
// gestion/inc/functions.php — fonctions utilitaires.

const APP_NAME = 'Dune Rooftop';
const DEVISE = 'DH';

// Familles d'achats (pour le food cost / coût boissons)
const CATEGORIES_ACHAT = [
    'nourriture'   => 'Nourriture',
    'boissons'     => 'Boissons',
    'consommables' => 'Consommables & emballages',
    'autre'        => 'Autre',
];

const MODES_PAIEMENT = [
    'especes'  => 'Espèces',
    'carte'    => 'Carte',
    'cheque'   => 'Chèque',
    'virement' => 'Virement',
];

const MOIS_FR = [1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet',
    'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

/**
 * Logo du restaurant : le premier fichier trouvé parmi assets/logo.png, .jpg, .webp
 * (votre logo officiel), sinon le logo par défaut assets/logo.svg.
 */
function app_logo(): string
{
    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {
        $f = __DIR__ . '/../assets/logo.' . $ext;
        if (is_file($f)) {
            return 'assets/logo.' . $ext . '?v=' . filemtime($f);
        }
    }
    return '';
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function money($n): string
{
    // espaces insécables : un montant n'est jamais coupé en fin de ligne
    return number_format((float) $n, 2, ',', "\u{00A0}") . "\u{00A0}" . DEVISE;
}

function fdate(?string $d): string
{
    if (!$d) {
        return '';
    }
    $t = strtotime($d);
    return $t ? date('d/m/Y', $t) : e($d);
}

function mois_label(string $ym): string
{
    [$y, $m] = array_map('intval', explode('-', $ym));
    return (MOIS_FR[$m] ?? $ym) . ' ' . $y;
}

function mode_label(?string $m): string
{
    return MODES_PAIEMENT[$m] ?? (string) $m;
}

function valid_date($d): bool
{
    if (!is_string($d) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        return false;
    }
    [$y, $m, $j] = array_map('intval', explode('-', $d));
    return checkdate($m, $j, $y);
}

function valid_month($m): bool
{
    return is_string($m) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m) === 1;
}

/** Convertit "1 234,50" ou "1234.5" en nombre. */
function num($v): float
{
    $v = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim((string) $v));
    return is_numeric($v) ? round((float) $v, 2) : 0.0;
}

function rows(string $sql, array $p = []): array
{
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

function row(string $sql, array $p = []): ?array
{
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($p);
    $r = $st->fetch();
    return $r ?: null;
}

function scalar(string $sql, array $p = [])
{
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($p);
    $v = $st->fetchColumn();
    return $v === false || $v === null ? 0 : $v;
}

function exec_sql(string $sql, array $p = []): void
{
    global $pdo;
    $pdo->prepare($sql)->execute($p);
}

// ---------- Sécurité & navigation ----------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function check_csrf(): void
{
    $token = $_SESSION['csrf'] ?? '';
    if ($token === '' || !hash_equals($token, (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Jeton de sécurité invalide. Veuillez recharger la page.');
    }
}

function flash(string $msg, string $type = 'success'): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit();
}

/** Recharge la page courante en gardant les filtres GET (sauf edit). */
function back(): void
{
    $q = $_GET;
    unset($q['edit']);
    redirect(basename($_SERVER['SCRIPT_NAME']) . ($q ? '?' . http_build_query($q) : ''));
}

// ---------- Période ----------

/** Période [du, au] lue dans l'URL, par défaut le mois en cours. */
function period(): array
{
    $du = $_GET['du'] ?? '';
    $au = $_GET['au'] ?? '';
    if (!valid_date($du)) {
        $du = date('Y-m-01');
    }
    if (!valid_date($au)) {
        $au = date('Y-m-t');
    }
    if ($du > $au) {
        [$du, $au] = [$au, $du];
    }
    return [$du, $au];
}

/** Formulaire de filtre par période (+ champs supplémentaires optionnels). */
function period_form(string $du, string $au, string $extra = ''): string
{
    $self = e(basename($_SERVER['SCRIPT_NAME']));
    $raccourcis = [
        'Ce mois'      => [date('Y-m-01'), date('Y-m-t')],
        'Mois dernier' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
        'Cette année'  => [date('Y-01-01'), date('Y-12-31')],
    ];
    $html = '<form class="row g-2 align-items-end mb-3" method="get" action="' . $self . '">'
        . '<div class="col-6 col-md-auto"><label class="form-label small mb-0">Du</label>'
        . '<input type="date" name="du" class="form-control form-control-sm" value="' . e($du) . '"></div>'
        . '<div class="col-6 col-md-auto"><label class="form-label small mb-0">Au</label>'
        . '<input type="date" name="au" class="form-control form-control-sm" value="' . e($au) . '"></div>'
        . $extra
        . '<div class="col-auto"><button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filtrer</button></div>'
        . '<div class="col-auto">';
    foreach ($raccourcis as $label => [$d, $a]) {
        $q = array_merge($_GET, ['du' => $d, 'au' => $a]);
        unset($q['edit']);
        $html .= '<a class="btn btn-sm btn-outline-secondary me-1" href="' . $self . '?' . e(http_build_query($q)) . '">' . $label . '</a>';
    }
    return $html . '</div></form>';
}

function select_options(array $options, $selected): string
{
    $html = '';
    foreach ($options as $val => $label) {
        $sel = ((string) $val === (string) $selected) ? ' selected' : '';
        $html .= '<option value="' . e($val) . '"' . $sel . '>' . e($label) . '</option>';
    }
    return $html;
}

/** Bouton de suppression (formulaire POST avec confirmation). */
function delete_button(int $id, string $action = 'delete', string $msg = 'Supprimer cet élément ?'): string
{
    return '<form method="post" class="d-inline" onsubmit="return confirm(\'' . e($msg) . '\')">'
        . csrf_field()
        . '<input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button></form>';
}

function edit_link(int $id): string
{
    $q = array_merge($_GET, ['edit' => $id]);
    return '<a class="btn btn-sm btn-outline-primary" title="Modifier" href="?' . e(http_build_query($q)) . '"><i class="bi bi-pencil"></i></a>';
}

function cancel_link(): string
{
    $q = $_GET;
    unset($q['edit']);
    return '<a class="btn btn-outline-secondary" href="?' . e(http_build_query($q)) . '">Annuler</a>';
}
