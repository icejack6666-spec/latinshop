<?php
// Cargar .env
$__env = dirname(__DIR__) . '/.env';
if (file_exists($__env)) {
    foreach (file($__env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}
if (!defined('LATINSHOP')) {
    die('Acceso directo no permitido.');
}

#define('ENV', 'production');
define('ENV', $_ENV['APP_ENV'] ?? 'production');

date_default_timezone_set('America/Mexico_City');

if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $_SERVER['HTTPS'] = $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'on' : 'off';
}

if (ENV === 'production') {
    define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://latln-shop.com/latinshop');
} else {
    $__proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $__host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $__base  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    define('SITE_URL', $__proto . '://' . $__host . $__base);
}

define('SITE_NAME', 'Latin Shop');
define('SITE_DESCRIPTION', 'Servicios premium para Lords Mobile');

define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ASSETS_PATH', ROOT_PATH . '/frontend/assets');
define('ASSETS_URL', SITE_URL . '/frontend/assets');


define('DB_HOST',    $_ENV['DB_HOST']    ?? 'localhost');
define('DB_NAME',    $_ENV['DB_NAME']    ?? 'solitarystore_db');
define('DB_USER',    $_ENV['DB_USER']    ?? 'root');
define('DB_PASS',    $_ENV['DB_PASS']    ?? '');
define('DB_CHARSET', 'utf8mb4');

// ─── REDIS ────────────────────────────────────────────────────────────────────
define('REDIS_HOST',   $_ENV['REDIS_HOST']   ?? '127.0.0.1');
define('REDIS_PORT',   (int)($_ENV['REDIS_PORT'] ?? 6379));
define('REDIS_PASS',   $_ENV['REDIS_PASS']   ?? '');
define('REDIS_DB',     (int)($_ENV['REDIS_DB']   ?? 0));
define('REDIS_PREFIX', $_ENV['REDIS_PREFIX'] ?? 'latinshop:');

define('CSRF_TOKEN_LENGTH', 32);
define('SESSION_LIFETIME', 1800);
define('MAX_LOGIN_ATTEMPTS', 5);
define('RECAPTCHA_SITE_KEY',   $_ENV['RECAPTCHA_SITE_KEY']   ?? '');
define('RECAPTCHA_SECRET_KEY', $_ENV['RECAPTCHA_SECRET_KEY'] ?? '');

define('CONTACT_EMAIL',    $_ENV['CONTACT_EMAIL']    ?? 'solitarystore4@gmail.com');
define('WHATSAPP_NUMBER',  $_ENV['WHATSAPP_NUMBER']  ?? '527862286246');

define('TWILIO_ACCOUNT_SID',  $_ENV['TWILIO_ACCOUNT_SID']  ?? '');
define('TWILIO_AUTH_TOKEN',   $_ENV['TWILIO_AUTH_TOKEN']   ?? '');
define('TWILIO_FROM_NUMBER',  $_ENV['TWILIO_FROM_NUMBER']  ?? '');

if (ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}

function u(string $path = ''): string {
    $path = ltrim($path, '/');
    return $path === '' ? SITE_URL : SITE_URL . '/' . $path;
}

function avatar_url(?string $url): string {
    if (empty($url)) {
        return ASSETS_URL . '/images/avatars/default.png';
    }
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
    return '/' . ltrim(htmlspecialchars($url, ENT_QUOTES, 'UTF-8'), '/');
}

define('FEATURES', [
    'cuentas' => true,
    'bots'    => true,
    'support' => true,
]);

function feature(string $name): bool {
    return FEATURES[$name] ?? false;
}

function feature_gate(string $name): void {
    if (!feature($name)) {
        header('Location: ' . u('/proximamente'));
        exit;
    }
}
