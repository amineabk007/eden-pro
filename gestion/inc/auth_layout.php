<?php
// gestion/inc/auth_layout.php — gabarit des pages de connexion / installation.
// Variables : $title, $content (HTML).
$logo = app_logo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <?php if ($logo): ?><link rel="icon" href="<?= e($logo) ?>"><?php endif; ?>
</head>
<body class="auth-page d-flex align-items-center justify-content-center min-vh-100 p-3">
<div class="card auth-card border-0 shadow">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <?php if ($logo): ?><img src="<?= e($logo) ?>" alt="Logo <?= APP_NAME ?>" class="auth-logo mb-2"><?php endif; ?>
            <h1 class="h4 mb-0"><?= APP_NAME ?></h1>
            <div class="text-muted small"><?= e($title) ?></div>
        </div>
        <?php foreach ($_SESSION['flash'] ?? [] as [$type, $msg]): ?>
            <div class="alert alert-<?= e($type) ?> py-2"><?= e($msg) ?></div>
        <?php endforeach; unset($_SESSION['flash']); ?>
        <?= $content ?>
    </div>
</div>
</body>
</html>
