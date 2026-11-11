<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';
require __DIR__ . '/includes/auth.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

$email  = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if ($email === '' || $password === '') {
        $errors[] = 'Introduce tu email y contraseña.';
    } else {
        $stmt = db()->prepare('SELECT `email`, `password` FROM `Usuario` WHERE `email` = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['email'] = $row['email'];
            $_SESSION['last_activity'] = time();
            if ($remember) {
                remember_issue($row['email']);
            } else {
                remember_clear();
            }
            flash('success', '¡Bienvenido de nuevo!');
            redirect('dashboard.php');
        }

        $errors[] = 'Email o contraseña incorrectos.';
    }
}

$pageTitle = 'Entrar';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Iniciar sesión</h1>

                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2"><?= e($err) ?></div>
                <?php endforeach; ?>

                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                        <label class="form-check-label" for="remember">Mantener sesión iniciada</label>
                    </div>
                    <button class="btn btn-primary w-100">Entrar</button>
                </form>

                <p class="mt-3 mb-0 text-center small">
                    ¿Sin cuenta? <a href="register.php">Regístrate</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
