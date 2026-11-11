<?php
declare(strict_types=1);

// "Remember me" persistent login.
//
// The cookie stores "<selector>:<validator>". Only a hash of the validator is
// kept in the database, so a database leak alone cannot forge a login. The
// token is rotated on every successful auto-login.

function remember_cookie_name(): string
{
    return 'remember';
}

function remember_cookie_options(int $expires): array
{
    $secure = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    return [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $secure,
        'samesite' => 'Lax',
    ];
}

// Create a new persistent token for a user and set the cookie.
function remember_issue(string $email): void
{
    $selector  = bin2hex(random_bytes(9));   // public lookup key
    $validator = bin2hex(random_bytes(32));  // secret, only its hash is stored
    $hash      = hash('sha256', $validator);
    $expires   = time() + REMEMBER_LIFETIME;

    $stmt = db()->prepare(
        'INSERT INTO `remember_token` (`selector`, `validator_hash`, `email`, `expira_en`) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$selector, $hash, $email, $expires]);

    setcookie(remember_cookie_name(), $selector . ':' . $validator, remember_cookie_options($expires));
    $_COOKIE[remember_cookie_name()] = $selector . ':' . $validator;
}

// Delete the current token (if any) and clear the cookie.
function remember_clear(): void
{
    $raw = $_COOKIE[remember_cookie_name()] ?? '';
    if (is_string($raw) && strpos($raw, ':') !== false) {
        $selector = explode(':', $raw, 2)[0];
        if ($selector !== '') {
            try {
                $stmt = db()->prepare('DELETE FROM `remember_token` WHERE `selector` = ?');
                $stmt->execute([$selector]);
            } catch (Throwable $e) {
                // ignore
            }
        }
    }

    setcookie(remember_cookie_name(), '', remember_cookie_options(time() - 3600));
    unset($_COOKIE[remember_cookie_name()]);
}

// Validate the remember cookie. Returns the user's email, or null.
function remember_login(): ?string
{
    $raw = $_COOKIE[remember_cookie_name()] ?? '';
    if (!is_string($raw) || $raw === '' || strpos($raw, ':') === false) {
        return null;
    }

    [$selector, $validator] = explode(':', $raw, 2);
    if ($selector === '' || $validator === '') {
        remember_clear();
        return null;
    }

    try {
        $stmt = db()->prepare('SELECT `email`, `validator_hash`, `expira_en` FROM `remember_token` WHERE `selector` = ?');
        $stmt->execute([$selector]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        return null;
    }

    if (!$row || (int) $row['expira_en'] < time() || !hash_equals((string) $row['validator_hash'], hash('sha256', $validator))) {
        // Unknown, expired or tampered token: drop it.
        try {
            $stmt = db()->prepare('DELETE FROM `remember_token` WHERE `selector` = ?');
            $stmt->execute([$selector]);
        } catch (Throwable $e) {
            // ignore
        }
        remember_clear();
        return null;
    }

    $email = (string) $row['email'];

    // Rotate: consume the used token and issue a fresh one.
    $stmt = db()->prepare('DELETE FROM `remember_token` WHERE `selector` = ?');
    $stmt->execute([$selector]);
    remember_issue($email);

    return $email;
}
