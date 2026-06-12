<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * ApiAuth
 *
 * Gestiona autenticación de la API REST mediante dos mecanismos:
 *
 * 1. API Key  → Header: Authorization: Bearer ls_<key>
 * 2. Session  → Cookie de sesión activa (para uso desde el frontend)
 *
 * Scope system:
 *   tickets:read    tickets:write   tickets:close
 *   profile:read    admin:read      admin:write
 */
class ApiAuth
{
    private Database $db;

    private ?array $currentUser = null;

    private ?array $currentKey = null;

    private array $currentScopes = [];

    private const KEY_PREFIX = 'ls_';
    private const KEY_LENGTH = 40;    

    public function __construct()
    {
        $this->db = Database::getInstance();
    }


    /**
     * Intenta autenticar el request actual.
     * Devuelve true si se autenticó correctamente, false si no hay credenciales.
     * Emite ApiResponse::unauthorized() si las credenciales son inválidas.
     */
    public function authenticate(): bool
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (str_starts_with($authHeader, 'Bearer ')) {
            $rawKey = substr($authHeader, 7);
            $this->authenticateByKey($rawKey);
            return true;
        }

        $auth = Auth::getInstance();
        if ($auth->isLoggedIn()) {
            $this->currentUser   = $auth->getUser();
            $this->currentScopes = $this->scopesForRole($this->currentUser['role']);
            return true;
        }

        return false;
    }

    /**
     * Requiere autenticación. Si no hay credenciales, emite 401.
     */
    public function requireAuth(): void
    {
        if (!$this->authenticate()) {
            ApiResponse::unauthorized('Se requiere un API key (Bearer) o sesión activa.');
        }
    }

    /**
     * Requiere un scope específico. Emite 403 si no lo tiene.
     */
    public function requireScope(string $scope): void
    {
        $this->requireAuth();
        if (!$this->hasScope($scope)) {
            ApiResponse::insufficientScope($scope);
        }
    }

    /**
     * Requiere rol admin (vía sesión o api_key con scope admin:*).
     */
    public function requireAdmin(): void
    {
        $this->requireAuth();
        $role = $this->currentUser['role'] ?? '';
        if ($role !== 'admin') {
            ApiResponse::forbidden('Se requiere rol de administrador.');
        }
    }


    public function getUser(): ?array      { return $this->currentUser; }
    public function getUserId(): int       { return (int)($this->currentUser['id'] ?? 0); }
    public function isAdmin(): bool        { return ($this->currentUser['role'] ?? '') === 'admin'; }
    public function hasScope(string $s): bool { return in_array($s, $this->currentScopes, true); }
    public function getKeyId(): ?int       { return isset($this->currentKey['id']) ? (int)$this->currentKey['id'] : null; }


    /**
     * Genera una nueva API key para el usuario.
     *
     * @param  int    $userId
     * @param  string $name    Etiqueta descriptiva
     * @param  array  $scopes  Lista de scopes permitidos
     * @param  int    $ttlDays 0 = sin expiración
     * @return array{key: string, prefix: string, id: int}
     */
    public function createKey(int $userId, string $name, array $scopes, int $ttlDays = 0): array
    {
        $raw    = self::KEY_PREFIX . bin2hex(random_bytes(self::KEY_LENGTH / 2));
        $hash   = hash('sha256', $raw);
        $prefix = substr($raw, 0, 8);

        $expiresAt = $ttlDays > 0
            ? date('Y-m-d H:i:s', strtotime("+{$ttlDays} days"))
            : null;

        $id = $this->db->insert(
            "INSERT INTO api_keys
                (user_id, name, key_hash, key_prefix, scope, is_active, expires_at, created_at)
             VALUES (?, ?, ?, ?, ?, 1, ?, NOW())",
            [$userId, $name, $hash, $prefix, json_encode(array_values($scopes)), $expiresAt]
        );

        AuditLog::log('api_key_created', $id, null, ['name' => $name, 'scopes' => $scopes]);

        return ['key' => $raw, 'prefix' => $prefix, 'id' => $id];
    }

    /**
     * Revoca (desactiva) una API key del usuario.
     */
    public function revokeKey(int $keyId, int $userId): bool
    {
        $rows = $this->db->update(
            "UPDATE api_keys SET is_active = 0 WHERE id = ? AND user_id = ?",
            [$keyId, $userId]
        );
        if ($rows > 0) {
            AuditLog::log('api_key_revoked', $keyId);
        }
        return $rows > 0;
    }

    /**
     * Lista las API keys de un usuario (sin mostrar el hash).
     */
    public function listKeys(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT id, name, key_prefix, scope, is_active, last_used_at, expires_at, created_at
             FROM api_keys WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }


    private function authenticateByKey(string $rawKey): void
    {
        if (!str_starts_with($rawKey, self::KEY_PREFIX)) {
            ApiResponse::unauthorized('API key con formato inválido.');
        }

        $hash = hash('sha256', $rawKey);

        $key = $this->db->fetch(
            "SELECT k.*, u.id AS uid, u.username, u.email, u.role
             FROM api_keys k
             INNER JOIN users u ON u.id = k.user_id
             WHERE k.key_hash = ? AND k.is_active = 1
             LIMIT 1",
            [$hash]
        );

        if (!$key) {
            ApiResponse::unauthorized('API key inválida o inactiva.');
        }

        if ($key['expires_at'] !== null && strtotime($key['expires_at']) < time()) {
            ApiResponse::unauthorized('API key expirada.');
        }

        try {
            $this->db->update(
                "UPDATE api_keys SET last_used_at = NOW() WHERE id = ?",
                [$key['id']]
            );
        } catch (\Exception) { /* non-fatal */ }

        $this->currentKey  = $key;
        $this->currentUser = [
            'id'       => $key['uid'],
            'username' => $key['username'],
            'email'    => $key['email'],
            'role'     => $key['role'],
        ];

        $keyScopes           = json_decode($key['scope'], true) ?? [];
        $roleScopes          = $this->scopesForRole($key['role']);
        $this->currentScopes = array_intersect($keyScopes, $roleScopes);
    }

    /**
     * Scopes máximos permitidos según el rol del usuario.
     */
    private function scopesForRole(string $role): array
    {
        return match ($role) {
            'admin' => [
                'tickets:read', 'tickets:write', 'tickets:close',
                'profile:read',
                'admin:read', 'admin:write',
            ],
            'client', 'verified' => [
                'tickets:read', 'tickets:write', 'tickets:close',
                'profile:read',
            ],
            default => [],
        };
    }
}
