<?php
require_once __DIR__ . '/auth.php';

$__user    = current_user();
$__flashes = take_flashes();
$__title   = isset($pageTitle) ? $pageTitle : SITE_NAME;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($__title) ?> · <?= e(SITE_NAME) ?></title>
<?php require_once __DIR__ . '/favicon.php'; foreach (favicon_existing(dirname(__DIR__)) as $__fav): ?>
    <link rel="icon" type="<?= e($__fav['type']) ?>" href="<?= e($__fav['href']) ?>">
<?php endforeach; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $__user ? 'dashboard.php' : 'login.php' ?>"><?= e(SITE_NAME) ?></a>
        <?php if ($__user): ?>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <a class="link-light link-opacity-75 text-decoration-none" href="dashboard.php">Panel</a>
                <a class="link-light link-opacity-75 text-decoration-none" href="tareas.php">Tareas</a>
                <a class="link-light link-opacity-75 text-decoration-none" href="categorias.php">Categorías</a>
                <span class="navbar-text text-white-50">@<?= e($__user['Mote']) ?></span>
                <form method="post" action="logout.php" class="m-0">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-light btn-sm">Salir</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</nav>
<main class="container pb-5">
<?php foreach ($__flashes as $__f): ?>
    <div class="alert alert-<?= e($__f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($__f['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endforeach; ?>
