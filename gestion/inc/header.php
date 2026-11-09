<?php
// gestion/inc/header.php — en-tête + menu latéral. Variables attendues : $title.
$page = basename($_SERVER['SCRIPT_NAME'], '.php');
$logo = app_logo();
$menu = [
    'index'        => ['bi-speedometer2', 'Tableau de bord'],
    'ventes'       => ['bi-cash-coin', "Chiffre d'affaires"],
    '#fournisseurs' => 'Fournisseurs',
    'fournisseurs' => ['bi-truck', 'Fournisseurs'],
    'achats'       => ['bi-cart3', 'Achats'],
    'paiements'    => ['bi-credit-card-2-front', 'Paiements fournisseurs'],
    '#personnel'   => 'Personnel',
    'employes'     => ['bi-people', 'Employés'],
    'avances'      => ['bi-wallet2', 'Avances'],
    'salaires'     => ['bi-cash-stack', 'Salaires'],
    '#charges'     => 'Charges',
    'charges'      => ['bi-receipt', 'Charges fixes & variables'],
    '#admin'       => 'Compte',
    'utilisateurs' => ['bi-person-gear', is_admin() ? 'Utilisateurs' : 'Mon compte'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Gestion') ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <?php if ($logo): ?><link rel="icon" href="<?= e($logo) ?>"><?php endif; ?>
</head>
<body>
<nav class="navbar navbar-dark bg-dark d-lg-none px-3">
    <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Menu">
        <i class="bi bi-list"></i>
    </button>
    <span class="navbar-brand mb-0 h1 d-flex align-items-center">
        <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="" class="app-logo-sm me-2"><?php endif; ?><?= APP_NAME ?>
    </span>
</nav>

<div class="d-flex">
    <aside class="sidebar offcanvas-lg offcanvas-start text-bg-dark" tabindex="-1" id="sidebar">
        <div class="offcanvas-header d-lg-none">
            <h5 class="offcanvas-title d-flex align-items-center">
                <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="" class="app-logo-sm me-2"><?php endif; ?><?= APP_NAME ?>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-3">
            <a href="index.php" class="brand d-none d-lg-flex align-items-center mb-3 text-white text-decoration-none">
                <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="Logo <?= APP_NAME ?>" class="app-logo me-2"><?php endif; ?><span><span class="d-block fs-5 fw-semibold lh-1"><?= APP_NAME ?></span><small class="text-white-50">Gestion du restaurant</small></span>
            </a>
            <ul class="nav nav-pills flex-column mb-auto">
                <?php foreach ($menu as $key => $item): ?>
                    <?php if (is_string($item)): ?>
                        <li class="nav-section"><?= e($item) ?></li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="<?= $key ?>.php" class="nav-link text-white <?= $page === $key ? 'active' : '' ?>">
                                <i class="bi <?= $item[0] ?> me-2"></i><?= e($item[1]) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <hr>
            <div class="small">
                <i class="bi bi-person-circle me-1"></i><?= e($ME['nom'] ?: $ME['username']) ?>
                <div class="mt-2">
                    <a href="logout.php" class="text-white-50"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-grow-1 p-3 p-lg-4 main-content">
        <div class="print-brand d-none align-items-center mb-3">
            <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="" class="app-logo me-2"><?php endif; ?>
            <div><div class="fs-4 fw-semibold lh-1"><?= APP_NAME ?></div><small class="text-muted">Édité le <?= date('d/m/Y') ?></small></div>
        </div>
        <h1 class="h3 mb-3"><?= e($title ?? '') ?></h1>
        <?php foreach ($_SESSION['flash'] ?? [] as [$type, $msg]): ?>
            <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
                <?= e($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; unset($_SESSION['flash']); ?>
