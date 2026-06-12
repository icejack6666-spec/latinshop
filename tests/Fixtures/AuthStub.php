<?php

declare(strict_types=1);

/**
 * Auth Stub — entorno de testing.
 * Reemplaza la clase Auth real (que depende de sesión y DB).
 */
class Auth
{
    private static ?Auth $instance = null;

    /** Usuario simulado actualmente "logueado". */
    private ?array $user = null;

    private function __construct() {}

    public static function getInstance(): Auth
    {
        if (self::$instance === null) {
            self::$instance = new Auth();
        }
        return self::$instance;
    }

    /** Configurar el usuario simulado desde los tests. */
    public static function setTestUser(?array $user): void
    {
        self::getInstance()->user = $user;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function isLoggedIn(): bool
    {
        return $this->user !== null;
    }

    public function getUser(): ?array
    {
        return $this->user;
    }

    public function hasRole(string $role): bool
    {
        return ($this->user['role'] ?? '') === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->user['role'] ?? '', $roles, true);
    }

    /** En tests, requireRole no redirige — lanza excepción si no cumple. */
    public function requireRole(string|array $role): void
    {
        if (!$this->isLoggedIn()) {
            throw new \RuntimeException('Test: usuario no autenticado.');
        }
        $roles = is_array($role) ? $role : [$role];
        if (!in_array($this->user['role'] ?? '', $roles, true)) {
            throw new \RuntimeException('Test: permiso denegado para rol ' . ($this->user['role'] ?? 'none'));
        }
    }

    private function __clone() {}
}


/**
 * Notifications Stub
 */
class Notifications
{
    /** Registro de notificaciones enviadas durante el test. */
    public static array $sent = [];

    public static function send(
        int    $userId,
        string $type,
        string $title,
        string $message,
        string $url = ''
    ): void {
        self::$sent[] = compact('userId', 'type', 'title', 'message', 'url');
    }

    public static function getUnread(int $userId): array  { return []; }
    public static function countUnread(int $userId): int  { return 0; }
    public static function markAllRead(int $userId): void {}
    public static function markRead(int $id, int $uid): void {}

    public static function reset(): void { self::$sent = []; }
}


/**
 * AuditLog Stub
 */
class AuditLog
{
    public static array $logs = [];

    public static function log(
        string $action,
        mixed  $entityId  = null,
        mixed  $oldValue  = null,
        mixed  $newValue  = null
    ): void {
        self::$logs[] = compact('action', 'entityId', 'oldValue', 'newValue');
    }

    public static function reset(): void { self::$logs = []; }
}
