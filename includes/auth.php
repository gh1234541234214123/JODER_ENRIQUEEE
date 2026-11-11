<?php
declare(strict_types=1);

// Authentication, CSRF and flash-message helpers.
// Requires config/bootstrap.php (session already started).

function current_user(): ?array
{
    static $user = null;
    static $loaded = false;

    if (empty($_SESSION['email'])) {
        return null;
    }
    if ($loaded) {
        return $user;
    }

    $stmt = db()->prepare('SELECT `Mote`, `Nombre`, `url_pfp`, `email` FROM `Usuario` WHERE `email` = ?');
    $stmt->execute([$_SESSION['email']]);
    $row = $stmt->fetch();

    $user = $row ?: null;
    $loaded = true;

    return $user;
}

function require_login(): void
{
    if (current_user() === null) {
        redirect('login.php');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        exit('Token CSRF inválido.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES);
}
