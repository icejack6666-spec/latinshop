<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * ApiRateLimit
 *
 * Rate limiting específico para la API REST.
 * Usa la tabla rate_limits existente (misma que check_rate_limit() de security.php).
 *
 * Límites por defecto:
 *   - Sin autenticación  : 20 req / 60 s
 *   - Autenticado (client): 120 req / 60 s
 *   - Admin              : 300 req / 60 s
 *
 * La clave de rate limit es: api:<tipo>:<identificador>:<endpoint>
 */
class ApiRateLimit
{
    private Database $db;

    private const LIMITS = [
        'anon'   => ['max' => 20,  'window' => 60],
        'client' => ['max' => 120, 'window' => 60],
        'admin'  => ['max' => 300, 'window' => 60],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Verifica el rate limit para el request actual.
     * Emite ApiResponse::rateLimitExceeded() si supera el límite.
     *
     * @param string      $endpoint   Ruta del endpoint (para granularidad)
     * @param string|null $identifier API key ID o user ID; null = IP anónima
     * @param string      $role       Rol del usuario ('anon', 'client', 'admin')
     */
    public function check(string $endpoint, ?string $identifier, string $role = 'anon'): void
    {
        $tier   = array_key_exists($role, self::LIMITS) ? $role : 'anon';
        $limit  = self::LIMITS[$tier];
        $ip     = $this->getIp();
        $ident  = $identifier ?? 'ip:' . $ip;
        $key    = 'api:' . $tier . ':' . $ident . ':' . $endpoint;

        $this->db->update(
            "DELETE FROM rate_limits
             WHERE action_key = ?
               AND created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$key, $limit['window']]
        );

        $count = $this->db->count(
            "SELECT COUNT(*) FROM rate_limits WHERE action_key = ?",
            [$key]
        );

        header('X-RateLimit-Limit: '     . $limit['max']);
        header('X-RateLimit-Remaining: ' . max(0, $limit['max'] - $count - 1));
        header('X-RateLimit-Window: '    . $limit['window']);

        if ($count >= $limit['max']) {
            ApiResponse::rateLimitExceeded($limit['window']);
        }

        $this->db->insert(
            "INSERT INTO rate_limits (action_key, ip_address, created_at) VALUES (?, ?, NOW())",
            [$key, $ip]
        );
    }


     private function getIp(): string
    {
        return IpHelper::getRealIP();
    }
}