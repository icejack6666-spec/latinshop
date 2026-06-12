<?php

declare(strict_types=1);

/**
 * bootstrap.php — Latin Shop Tests
 *
 * Bootstrap del entorno de testing.
 * Usa SQLite en memoria para tests de unidad (sin MySQL real).
 * Carga stubs mínimos de las dependencias del proyecto.
 */

// ─── CONSTANTES BÁSICAS (si no las definió phpunit.xml) ──────────────────────
if (!defined('LATINSHOP'))       define('LATINSHOP',       true);
if (!defined('ENV'))             define('ENV',             'testing');
if (!defined('SITE_URL'))        define('SITE_URL',        'http://localhost');
if (!defined('SITE_NAME'))       define('SITE_NAME',       'Latin Shop Test');
if (!defined('ROOT_PATH'))       define('ROOT_PATH',       dirname(__DIR__));
if (!defined('INCLUDES_PATH'))   define('INCLUDES_PATH',   ROOT_PATH . '/includes');
if (!defined('PAGES_PATH'))      define('PAGES_PATH',      ROOT_PATH . '/pages');
if (!defined('ASSETS_PATH'))     define('ASSETS_PATH',     ROOT_PATH . '/frontend/assets');
if (!defined('ASSETS_URL'))      define('ASSETS_URL',      'http://localhost/assets');
if (!defined('DB_HOST'))         define('DB_HOST',         'localhost');
if (!defined('DB_NAME'))         define('DB_NAME',         ':memory:');
if (!defined('DB_USER'))         define('DB_USER',         'root');
if (!defined('DB_PASS'))         define('DB_PASS',         '');
if (!defined('DB_CHARSET'))      define('DB_CHARSET',      'utf8mb4');
if (!defined('CSRF_TOKEN_LENGTH')) define('CSRF_TOKEN_LENGTH', 32);
if (!defined('SESSION_LIFETIME'))  define('SESSION_LIFETIME',  1800);

// ─── AUTOLOADER ──────────────────────────────────────────────────────────────
$autoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// ─── STUBS DE CLASES DEL PROYECTO ────────────────────────────────────────────
// Se cargan stubs en lugar de las clases reales para aislar dependencias.
require_once __DIR__ . '/Fixtures/DatabaseStub.php';
require_once __DIR__ . '/Fixtures/AuthStub.php';
require_once __DIR__ . '/Fixtures/NotificationsStub.php';
require_once __DIR__ . '/Fixtures/AuditLogStub.php';

// Cargar clases reales que no dependen de MySQL ni sesión
require_once INCLUDES_PATH . '/security.php';
require_once INCLUDES_PATH . '/LazyImage.php';
require_once INCLUDES_PATH . '/Api/ApiResponse.php';
require_once INCLUDES_PATH . '/Api/BaseEndpoint.php';

// Support classes (usan DatabaseStub — inyectado por los tests)
require_once INCLUDES_PATH . '/Support/SupportTicketRepository.php';
require_once INCLUDES_PATH . '/Support/SupportMessageRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentService.php';
require_once INCLUDES_PATH . '/Support/SupportTicketService.php';

// ─── SESIÓN FAKE PARA TESTS ───────────────────────────────────────────────────
// Evitar errores de session_start() en entorno CLI
if (!isset($_SESSION)) {
    $_SESSION = [];
}
if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

// ─── HELPERS GLOBALES ─────────────────────────────────────────────────────────
if (!function_exists('u')) {
    function u(string $path = ''): string {
        $path = ltrim($path, '/');
        return $path === '' ? SITE_URL : SITE_URL . '/' . $path;
    }
}
if (!function_exists('avatar_url')) {
    function avatar_url(?string $url): string {
        return $url ?: ASSETS_URL . '/images/avatars/default.png';
    }
}
if (!function_exists('feature')) {
    function feature(string $name): bool { return true; }
}

// Stub de ApiAuth (no cargamos el real que depende de Database MySQL auth)
if (!class_exists('ApiAuth')) {
    class ApiAuth {
        public function authenticate(): bool      { return false; }
        public function requireAuth(): void       {}
        public function requireScope(string $s): void {}
        public function requireAdmin(): void      {}
        public function getUser(): ?array         { return null; }
        public function getUserId(): int          { return 0; }
        public function isAdmin(): bool           { return false; }
        public function hasScope(string $s): bool { return false; }
        public function getKeyId(): ?int          { return null; }
        public function listKeys(int $uid): array { return []; }
        public function createKey(int $u, string $n, array $s, int $t = 0): array { return []; }
        public function revokeKey(int $kid, int $uid): bool { return false; }
    }
}
