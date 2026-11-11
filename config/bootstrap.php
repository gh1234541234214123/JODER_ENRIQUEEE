<?php
declare(strict_types=1);

// Loads configuration, picks the environment, hardens the session and
// configures error reporting. Every page must require this file first.

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    // No configuration yet: send the user to the web installer.
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Missing config/config.php. Run the web installer (setup.php) first.\n");
        exit(1);
    }
    header('Location: setup.php');
    exit;
}

$CONFIG = require $configFile;

$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
$isLocal = PHP_SAPI === 'cli'
    || in_array($serverName, ['localhost', '127.0.0.1', '::1'], true);

$ENV = $isLocal ? ($CONFIG['local'] ?? []) : ($CONFIG['production'] ?? []);

if (empty($ENV['db'])) {
    http_response_code(500);
    exit('No database configuration found for this environment.');
}

define('APP_DEBUG', (bool) ($ENV['debug'] ?? false));
define('SITE_NAME', (string) ($ENV['site_name'] ?? 'JODER_ENRIQUEEE'));
define('DB_HOST', (string) $ENV['db']['host']);
define('DB_NAME', (string) $ENV['db']['name']);
define('DB_USER', (string) $ENV['db']['user']);
define('DB_PASS', (string) $ENV['db']['pass']);

define('SESSION_LIFETIME', 60 * 60 * 24 * 30);  // absolute session lifetime (30 days)
define('SESSION_IDLE', 60 * 60 * 2);            // idle timeout (2 hours)
define('REMEMBER_LIFETIME', 60 * 60 * 24 * 30); // "remember me" cookie lifetime (30 days)
define('SESSION_DRIVER', (string) ($ENV['session_driver'] ?? 'database')); // 'database' or 'file'

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Session handling lives in includes/session.php (cookie hardening, optional
// database-backed storage, idle timeout and "remember me" auto-login).
require_once __DIR__ . '/db.php';
require_once dirname(__DIR__) . '/includes/session.php';
