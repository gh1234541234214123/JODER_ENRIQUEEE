<?php
declare(strict_types=1);

// Shared PDO connection. Requires config/bootstrap.php to have been loaded
// (for the DB_* constants and APP_DEBUG).

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            throw $e;
        }
        http_response_code(500);
        exit('Database connection error.');
    }

    return $pdo;
}
