<?php
declare(strict_types=1);

// Session bootstrap: cookie hardening, an optional database-backed save
// handler, an idle timeout and "remember me" auto-login. Required by
// config/bootstrap.php (which defines the SESSION_* constants first).

require_once __DIR__ . '/remember.php';

class DbSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private int $lifetime;

    public function __construct(PDO $pdo, int $lifetime)
    {
        $this->pdo = $pdo;
        $this->lifetime = $lifetime;
    }

    #[\ReturnTypeWillChange]
    public function open(string $path, string $name)
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function close()
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read(string $id)
    {
        $stmt = $this->pdo->prepare('SELECT `data` FROM `sesion` WHERE `id` = ? AND `ultima_actividad` >= ?');
        $stmt->execute([$id, time() - $this->lifetime]);
        $data = $stmt->fetchColumn();

        return $data === false ? '' : (string) $data;
    }

    #[\ReturnTypeWillChange]
    public function write(string $id, string $data)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `sesion` (`id`, `data`, `ultima_actividad`) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE `data` = VALUES(`data`), `ultima_actividad` = VALUES(`ultima_actividad`)'
        );

        return $stmt->execute([$id, $data, time()]);
    }

    #[\ReturnTypeWillChange]
    public function destroy(string $id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM `sesion` WHERE `id` = ?');

        return $stmt->execute([$id]);
    }

    #[\ReturnTypeWillChange]
    public function gc(int $max_lifetime)
    {
        $stmt = $this->pdo->prepare('DELETE FROM `sesion` WHERE `ultima_actividad` < ?');
        $stmt->execute([time() - $max_lifetime]);

        return $stmt->rowCount();
    }
}

$secure = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $secure,
    'samesite' => 'Lax',
]);

ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');

if (SESSION_DRIVER === 'database') {
    try {
        // Only use the DB handler if the table actually exists, otherwise fall
        // back to PHP's default file-based sessions.
        db()->query('SELECT 1 FROM `sesion` LIMIT 1');
        session_set_save_handler(new DbSessionHandler(db(), SESSION_LIFETIME), true);
    } catch (Throwable $e) {
        // Missing table or DB error: keep file sessions.
    }
}

session_start();

// Idle timeout.
$now = time();
if (isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > SESSION_IDLE) {
    $_SESSION = [
        'flash' => [['type' => 'warning', 'msg' => 'Tu sesión expiró por inactividad. Vuelve a entrar.']],
    ];
} else {
    $_SESSION['last_activity'] = $now;
}

// "Remember me" auto-login.
if (empty($_SESSION['email'])) {
    $remembered = remember_login();
    if ($remembered !== null) {
        $_SESSION['email'] = $remembered;
        $_SESSION['last_activity'] = time();
        session_regenerate_id(true);
    }
}
