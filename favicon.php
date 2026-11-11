<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/favicon.php';
require_login();

$letter = favicon_letter(SITE_NAME);
$color  = '#4f46e5';
$shape  = 'rounded';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mode = (string) ($_POST['mode'] ?? 'generate');

    if ($mode === 'upload') {
        try {
            $written = favicon_upload(__DIR__, $_FILES['fav_image'] ?? []);
            flash('success', 'Favicon actualizado desde imagen: ' . implode(', ', $written) . '.');
            redirect('favicon.php');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    } else {
        $letter = trim((string) ($_POST['fav_letter'] ?? ''));
        $color  = (string) ($_POST['fav_color'] ?? '#4f46e5');
        $shape  = (string) ($_POST['fav_shape'] ?? 'rounded');

        if ($letter === '') {
            $letter = favicon_letter(SITE_NAME);
        }
        $color = favicon_hex($color);
        $shape = favicon_shape($shape);

        $written = favicon_write(__DIR__, $letter, $color, $shape);
        if ($written) {
            flash('success', 'Favicon actualizado: ' . implode(', ', $written) . '.');
            redirect('favicon.php');
        }
        $errors[] = 'No se pudo escribir el favicon. Comprueba los permisos de la carpeta raíz.';
    }
}

$current = favicon_existing(__DIR__);

$pageTitle = 'Favicon';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Generador de favicon</h1>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2"><?= e($err) ?></div>
                <?php endforeach; ?>

                <ul class="nav nav-pills mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-generate" type="button" role="tab">Generar</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-upload" type="button" role="tab">Subir imagen</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-generate" role="tabpanel">
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="mode" value="generate">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="fav_letter">Letra</label>
                                    <input type="text" id="fav_letter" name="fav_letter" class="form-control" maxlength="2" value="<?= e($letter) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="fav_color">Color</label>
                                    <input type="color" id="fav_color" name="fav_color" class="form-control form-control-color" value="<?= e($color) ?>">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="fav_shape">Forma</label>
                                    <select id="fav_shape" name="fav_shape" class="form-select">
                                        <option value="rounded" <?= $shape === 'rounded' ? 'selected' : '' ?>>Redondeada</option>
                                        <option value="circle" <?= $shape === 'circle' ? 'selected' : '' ?>>Círculo</option>
                                        <option value="square" <?= $shape === 'square' ? 'selected' : '' ?>>Cuadrada</option>
                                    </select>
                                </div>
                            </div>
                            <button class="btn btn-primary mt-4">Generar favicon</button>
                        </form>
                        <p class="text-muted small mt-3 mb-0">
                            Se guarda como <code>favicon.svg</code> (y <code>favicon.png</code> si el servidor
                            tiene la extensión GD).
                        </p>
                    </div>

                    <div class="tab-pane fade" id="tab-upload" role="tabpanel">
                        <form method="post" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <input type="hidden" name="mode" value="upload">
                            <div class="mb-3">
                                <label class="form-label" for="fav_image">Imagen</label>
                                <input type="file" id="fav_image" name="fav_image" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.ico,image/*" required>
                                <div class="form-text">PNG, JPG, GIF, WEBP, SVG o ICO. Máximo 2 MB.</div>
                            </div>
                            <button class="btn btn-primary">Subir y usar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                <p class="text-muted small mb-3">Vista previa / favicon actual</p>
                <?php if ($current): ?>
                    <img src="<?= e($current[0]['href']) ?>?v=<?= time() ?>" alt="favicon" style="width:64px;height:64px;image-rendering:auto">
                <?php else: ?>
                    <span id="fav-preview" style="line-height:0"><?= favicon_svg($letter, $color, $shape) ?></span>
                <?php endif; ?>
                <?php if ($current): ?>
                    <span id="fav-preview" class="d-none" style="line-height:0"><?= favicon_svg($letter, $color, $shape) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var letter = document.getElementById('fav_letter');
    var color  = document.getElementById('fav_color');
    var shape  = document.getElementById('fav_shape');
    var prev   = document.getElementById('fav-preview');
    if (!letter || !color || !shape || !prev) { return; }

    function render() {
        var l = (letter.value || '?').trim().charAt(0).toUpperCase().replace(/[<>&"']/g, '');
        if (l === '') { l = '?'; }
        var bg = /^#[0-9a-fA-F]{6}$/.test(color.value) ? color.value : '#4f46e5';
        var bgShape;
        if (shape.value === 'circle') {
            bgShape = '<circle cx="32" cy="32" r="32" fill="' + bg + '"/>';
        } else if (shape.value === 'square') {
            bgShape = '<rect width="64" height="64" fill="' + bg + '"/>';
        } else {
            bgShape = '<rect width="64" height="64" rx="14" ry="14" fill="' + bg + '"/>';
        }
        prev.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">'
            + bgShape
            + '<text x="32" y="33" font-family="Arial, Helvetica, sans-serif" font-size="34" font-weight="bold" '
            + 'fill="#ffffff" text-anchor="middle" dominant-baseline="central">' + l + '</text></svg>';
    }

    [letter, color, shape].forEach(function (el) {
        el.addEventListener('input', render);
        el.addEventListener('change', render);
    });
    render();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
