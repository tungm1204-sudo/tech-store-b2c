<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/view.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('tech_gadgets_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > 1800) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['last_activity'] = time();

function app_url(string $path = ''): string
{
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($basePath === '.' || $basePath === '/') {
        $basePath = '';
    }

    return $basePath . '/' . ltrim($path, '/');
}

function app_absolute_url(string $path = ''): string
{
    $configuredUrl = rtrim((string) (getenv('APP_BASE_URL') ?: ''), '/');
    if ($configuredUrl !== '') {
        return $configuredUrl . '/' . ltrim($path, '/');
    }

    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['SERVER_NAME'] ?? 'localhost';

    return $scheme . '://' . $host . app_url($path);
}
