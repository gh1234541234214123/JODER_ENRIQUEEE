<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_login();

$email = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 255) {
            flash('danger', 'El nombre de la categoría es obligatorio (máx. 255 caracteres).');
        } else {
            $stmt = db()->prepare('INSERT INTO `categoria` (`nombre`, `id_creador`) VALUES (?, ?)');
            $stmt->execute([$nombre, $email]);
            flash('success', 'Categoría creada.');
        }
        redirect('categorias.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare('DELETE FROM `categoria` WHERE `id` = ? AND `id_creador` = ?');
        $stmt->execute([$id, $email]);
        flash('success', 'Categoría eliminada. Sus tareas quedaron sin categoría.');
        redirect('categorias.php');
    }
}

$stmt = db()->prepare(
    'SELECT c.`id`, c.`nombre`,
            (SELECT COUNT(*) FROM `tarea` t WHERE t.`id_categoria` = c.`id`) AS `total`
     FROM `categoria` c
     WHERE c.`id_creador` = ?
     ORDER BY c.`nombre`'
);
$stmt->execute([$email]);
$categorias = $stmt->fetchAll();

$pageTitle = 'Categorías';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Categorías</h1>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="col-md-9">
                <label class="form-label" for="nombre">Nueva categoría</label>
                <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej. Trabajo, Casa..." required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Crear</button>
            </div>
        </form>
    </div>
</div>

<?php if (!$categorias): ?>
    <div class="alert alert-info">Todavía no tienes categorías.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle bg-white">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th style="width:140px">Tareas</th>
                    <th style="width:100px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categorias as $c): ?>
                <tr>
                    <td><?= e($c['nombre']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= (int) $c['total'] ?></span></td>
                    <td>
                        <form method="post" class="m-0" onsubmit="return confirm('¿Eliminar esta categoría? Sus tareas quedarán sin categoría.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Borrar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
