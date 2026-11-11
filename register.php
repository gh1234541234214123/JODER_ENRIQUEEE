<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';
require __DIR__ . '/includes/auth.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

$mote   = '';
$nombre = '';
$email  = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $mote      = trim((string) ($_POST['Mote'] ?? ''));
    $nombre    = trim((string) ($_POST['Nombre'] ?? ''));
    $email     = trim((string) ($_POST['email'] ?? ''));
    $password  = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');

    if ($mote === '' || mb_strlen($mote) > 255) {
        $errors[] = 'El mote es obligatorio (máx. 255 caracteres).';
    }
    if ($nombre === '' || mb_strlen($nombre) > 255) {
        $errors[] = 'El nombre es obligatorio (máx. 255 caracteres).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($password !== $password2) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT `email` FROM `Usuario` WHERE `email` = ? OR `Mote` = ?');
        $stmt->execute([$email, $mote]);
        if ($stmt->fetch()) {
            $errors[] = 'Ese email o mote ya está registrado.';
        }
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO `Usuario` (`Mote`, `Nombre`, `email`, `password`) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$mote, $nombre, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Cuenta creada. Ya puedes iniciar sesión.');
            redirect('login.php');
        } catch (PDOException $ex) {
            $errors[] = 'No se pudo crear la cuenta (¿email o mote en uso?).';
        }
    }
}

$pageTitle = 'Registro';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Crear cuenta</h1>

                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="Mote">Mote</label>
                            <input type="text" id="Mote" name="Mote" class="form-control" value="<?= e($mote) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="Nombre">Nombre</label>
                            <input type="text" id="Nombre" name="Nombre" class="form-control" value="<?= e($nombre) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password">Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <div class="form-text">Mínimo 8 caracteres.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password2">Repite la contraseña</label>
                            <input type="password" id="password2" name="password2" class="form-control" required>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 mt-4">Registrarme</button>
                </form>

                <p class="mt-3 mb-0 text-center small">
                    ¿Ya tienes cuenta? <a href="login.php">Entra</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
