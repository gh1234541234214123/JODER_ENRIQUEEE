<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_login();

$email = $_SESSION['email'];

function category_owned(int $id, string $email): bool
{
    $stmt = db()->prepare('SELECT 1 FROM `categoria` WHERE `id` = ? AND `id_creador` = ?');
    $stmt->execute([$id, $email]);
    return (bool) $stmt->fetchColumn();
}

function parse_category($raw, string $email): ?int
{
    if ($raw === null || $raw === '' || $raw === '0') {
        return null;
    }
    $id = (int) $raw;
    return category_owned($id, $email) ? $id : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            flash('danger', 'El nombre de la tarea no puede estar vacío.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO `tarea` (`nombre`, `completada`, `ID_usuario`, `id_categoria`) VALUES (?, 0, ?, ?)'
            );
            $stmt->execute([$nombre, $email, parse_category($_POST['id_categoria'] ?? null, $email)]);
            flash('success', 'Tarea creada.');
        }
        redirect('tareas.php');
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare(
            'UPDATE `tarea` SET `completada` = NOT `completada` WHERE `id` = ? AND `ID_usuario` = ?'
        );
        $stmt->execute([$id, $email]);
        redirect('tareas.php');
    }

    if ($action === 'update') {
        $id     = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            flash('danger', 'El nombre de la tarea no puede estar vacío.');
        } else {
            $stmt = db()->prepare(
                'UPDATE `tarea` SET `nombre` = ?, `id_categoria` = ? WHERE `id` = ? AND `ID_usuario` = ?'
            );
            $stmt->execute([$nombre, parse_category($_POST['id_categoria'] ?? null, $email), $id, $email]);
            flash('success', 'Tarea actualizada.');
        }
        redirect('tareas.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare('DELETE FROM `tarea` WHERE `id` = ? AND `ID_usuario` = ?');
        $stmt->execute([$id, $email]);
        flash('success', 'Tarea eliminada.');
        redirect('tareas.php');
    }
}

$filtro    = $_GET['filtro'] ?? 'todas';
$catFiltro = (isset($_GET['cat']) && $_GET['cat'] !== '') ? (int) $_GET['cat'] : null;

$sql    = 'SELECT t.`id`, t.`nombre`, t.`completada`, t.`id_categoria`, c.`nombre` AS `categoria`
           FROM `tarea` t
           LEFT JOIN `categoria` c ON c.`id` = t.`id_categoria`
           WHERE t.`ID_usuario` = ?';
$params = [$email];

if ($filtro === 'pendientes') {
    $sql .= ' AND t.`completada` = 0';
} elseif ($filtro === 'completadas') {
    $sql .= ' AND t.`completada` = 1';
}
if ($catFiltro !== null) {
    $sql .= ' AND t.`id_categoria` = ?';
    $params[] = $catFiltro;
}
$sql .= ' ORDER BY t.`completada` ASC, t.`creado_en` DESC, t.`id` DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$tareas = $stmt->fetchAll();

$stmt = db()->prepare('SELECT `id`, `nombre` FROM `categoria` WHERE `id_creador` = ? ORDER BY `nombre`');
$stmt->execute([$email]);
$categorias = $stmt->fetchAll();

$pageTitle = 'Tareas';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Tareas</h1>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="col-md-6">
                <label class="form-label" for="nombre">Nueva tarea</label>
                <input type="text" id="nombre" name="nombre" class="form-control" placeholder="¿Qué hay que hacer?" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="id_categoria">Categoría</label>
                <select id="id_categoria" name="id_categoria" class="form-select">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Añadir</button>
            </div>
        </form>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    <?php
    $filtros = ['todas' => 'Todas', 'pendientes' => 'Pendientes', 'completadas' => 'Completadas'];
    foreach ($filtros as $key => $label):
        $active = $filtro === $key ? 'active' : '';
        $url = 'tareas.php?filtro=' . urlencode($key) . ($catFiltro !== null ? '&cat=' . $catFiltro : '');
    ?>
        <li class="nav-item"><a class="nav-link <?= $active ?>" href="<?= e($url) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
</ul>

<?php if (!$tareas): ?>
    <div class="alert alert-info">No hay tareas que mostrar.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle bg-white">
            <thead>
                <tr>
                    <th style="width:110px">Estado</th>
                    <th>Tarea</th>
                    <th style="width:90px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tareas as $t): ?>
                <tr>
                    <td>
                        <form method="post" class="m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                            <button class="btn btn-sm <?= $t['completada'] ? 'btn-success' : 'btn-outline-secondary' ?>">
                                <?= $t['completada'] ? 'Completada' : 'Pendiente' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="post" class="row g-2 align-items-center m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                            <div class="col-12 col-md-6">
                                <input type="text" name="nombre" class="form-control form-control-sm <?= $t['completada'] ? 'text-decoration-line-through text-muted' : '' ?>" value="<?= e($t['nombre']) ?>" required>
                            </div>
                            <div class="col-8 col-md-4">
                                <select name="id_categoria" class="form-select form-select-sm">
                                    <option value="">Sin categoría</option>
                                    <?php foreach ($categorias as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>" <?= ((int) $c['id'] === (int) $t['id_categoria']) ? 'selected' : '' ?>>
                                            <?= e($c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4 col-md-2">
                                <button class="btn btn-sm btn-outline-primary w-100">Guardar</button>
                            </div>
                        </form>
                    </td>
                    <td>
                        <form method="post" class="m-0" onsubmit="return confirm('¿Eliminar esta tarea?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
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
