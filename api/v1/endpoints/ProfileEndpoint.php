<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * ProfileEndpoint — /api/v1/profile
 *
 * GET    /api/v1/profile             → Datos del usuario autenticado
 * GET    /api/v1/profile/keys        → Listar mis API keys
 * POST   /api/v1/profile/keys        → Crear nueva API key
 * DELETE /api/v1/profile/keys/{id}   → Revocar API key
 */
class ProfileEndpoint extends BaseEndpoint
{
    // ─── GET /profile ─────────────────────────────────────────────────────────

    public function show(array $params): void
    {
        $this->auth->requireScope('profile:read');
        $user = $this->auth->getUser();

        // Enriquecer con stats de soporte
        $db       = Database::getInstance();
        $openCount = $db->count(
            "SELECT COUNT(*) FROM support_tickets
             WHERE user_id = ? AND status IN ('open','pending','answered')",
            [$user['id']]
        );

        ApiResponse::ok([
            'id'                  => (int)$user['id'],
            'username'            => $user['username'],
            'email'               => $user['email'],
            'role'                => $user['role'],
            'support_open_tickets' => $openCount,
        ]);
    }

    // ─── GET /profile/keys ────────────────────────────────────────────────────

    public function keys(array $params): void
    {
        $this->auth->requireAuth();
        $userId = $this->auth->getUserId();

        $keys = $this->auth->listKeys($userId);

        ApiResponse::ok(array_map(function (array $k) {
            $scopes = is_string($k['scope']) ? json_decode($k['scope'], true) : $k['scope'];
            return [
                'id'           => (int)$k['id'],
                'name'         => $k['name'],
                'prefix'       => $k['key_prefix'] . '…',   // nunca el hash completo
                'scope'        => $scopes ?? [],
                'is_active'    => (bool)$k['is_active'],
                'last_used_at' => $k['last_used_at'],
                'expires_at'   => $k['expires_at'],
                'created_at'   => $k['created_at'],
            ];
        }, $keys));
    }

    // ─── POST /profile/keys ───────────────────────────────────────────────────

    public function createKey(array $params): void
    {
        $this->auth->requireAuth();
        $userId = $this->auth->getUserId();
        $user   = $this->auth->getUser();

        $body = $this->body();
        $this->validate($body, [
            'name'   => 'required|string|min:3|max:100',
            'scopes' => 'required',
        ]);

        $name   = trim($body['name']);
        $scopes = $body['scopes'] ?? [];
        $ttl    = (int)($body['ttl_days'] ?? 0);

        if (!is_array($scopes) || empty($scopes)) {
            ApiResponse::validationError("'scopes' debe ser un array no vacío de permisos.");
        }

        // Scopes válidos según rol
        $validScopes = match ($user['role']) {
            'admin'            => ['tickets:read','tickets:write','tickets:close','profile:read','admin:read','admin:write'],
            'client','verified' => ['tickets:read','tickets:write','tickets:close','profile:read'],
            default            => [],
        };

        $invalid = array_diff($scopes, $validScopes);
        if (!empty($invalid)) {
            ApiResponse::validationError(
                'Scopes no permitidos para tu rol: ' . implode(', ', $invalid)
            );
        }

        // Límite: máximo 5 claves activas por usuario
        $db = Database::getInstance();
        $active = $db->count(
            "SELECT COUNT(*) FROM api_keys WHERE user_id = ? AND is_active = 1",
            [$userId]
        );
        if ($active >= 5) {
            ApiResponse::error(
                'LIMIT_EXCEEDED',
                'Límite de 5 API keys activas alcanzado. Revoca alguna antes de crear una nueva.',
                409
            );
        }

        $result = $this->auth->createKey($userId, $name, $scopes, $ttl);

        // La clave en texto claro se devuelve UNA SOLA VEZ — no se puede recuperar después
        ApiResponse::created([
            'id'      => $result['id'],
            'name'    => $name,
            'key'     => $result['key'],       // ← mostrar una sola vez
            'prefix'  => $result['prefix'] . '…',
            'scopes'  => $scopes,
            'warning' => 'Guarda esta clave en un lugar seguro. No se mostrará de nuevo.',
        ]);
    }

    // ─── DELETE /profile/keys/{id} ────────────────────────────────────────────

    public function revokeKey(array $params): void
    {
        $this->auth->requireAuth();
        $userId = $this->auth->getUserId();
        $keyId  = (int)($params['key_id'] ?? 0);

        if ($keyId === 0) {
            ApiResponse::validationError('ID de clave inválido.');
        }

        $ok = $this->auth->revokeKey($keyId, $userId);

        if (!$ok) {
            ApiResponse::notFound('API key no encontrada o ya estaba inactiva.');
        }

        ApiResponse::noContent();
    }
}
