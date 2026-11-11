<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_login();

$user  = current_user();
$email = $_SESSION['email'];

function count_for_user(string $sql, string $email): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute([$email]);
    return (int) $stmt->fetchColumn();
}

$total    = count_for_user('SELECT COUNT(*) FROM `tarea` WHERE `ID_usuario` = ?', $email);
$pend     = count_for_user('SELECT COUNT(*) FROM `tarea` WHERE `ID_usuario` = ? AND `completada` = 0', $email);
$done     = count_for_user('SELECT COUNT(*) FROM `tarea` WHERE `ID_usuario` = ? AND `completada` = 1', $email);
$cats     = count_for_user('SELECT COUNT(*) FROM `categoria` WHERE `id_creador` = ?', $email);

$pageTitle = 'Panel';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Hola, <?= e($user['Nombre']) ?></h1>
        <p class="text-muted mb-0">@<?= e($user['Mote']) ?> · <?= e($user['email']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="tareas.php" class="btn btn-primary">Nueva tarea</a>
        <a href="categorias.php" class="btn btn-outline-secondary">Categorías</a>
        <a href="favicon.php" class="btn btn-outline-secondary">Favicon</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <div class="display-6"><?= $total ?></div>
                <div class="text-muted small">Tareas totales</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <div class="display-6 text-warning"><?= $pend ?></div>
                <div class="text-muted small">Pendientes</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <div class="display-6 text-success"><?= $done ?></div>
                <div class="text-muted small">Completadas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm h-100">
            <div class="card-body">
                <div class="display-6"><?= $cats ?></div>
                <div class="text-muted small">Categorías</div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
