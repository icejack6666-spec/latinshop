<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * SecurityLogger
 *
 * Writes security events to the `security_logs` table and exposes
 * read methods for the admin panel.  All writes are fire-and-forget:
 * a logging failure must never break the request that triggered it.
 */
class SecurityLogger
{
    public function __construct(private readonly Database $db) {}

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Persist a security event.
     *
     * @param string   $eventType   Short identifier, e.g. 'xss_attempt'.
     * @param string   $description Human-readable detail (truncated to 500 chars).
     * @param string   $ip          Client IP address.
     * @param int|null $userId      Authenticated user ID, if known.
     */
    public function log(
        string $eventType,
        string $description,
        string $ip,
        ?int $userId = null
    ): void {
        try {
            $uid = $userId ?? ($_SESSION['user_id'] ?? null);

            $this->db->insert(
                "INSERT INTO security_logs
                    (event_type, ip_address, user_id, description, request_uri, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    $eventType,
                    $ip,
                    $uid,
                    substr($description, 0, 500),
                    substr($_SERVER['REQUEST_URI']      ?? '', 0, 300),
                    substr($_SERVER['HTTP_USER_AGENT']  ?? '', 0, 300),
                ]
            );
        } catch (\Exception $e) {
            error_log('[SecurityLogger] Write failed: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Read (admin panel)
    // -------------------------------------------------------------------------

    /**
     * Fetch the most recent security log entries, optionally filtered
     * by event type.
     *
     * @param int    $limit     Maximum number of rows to return.
     * @param string $eventType Optional filter; empty string means all types.
     * @return array<int, array<string, mixed>>
     */
    public function getLogs(int $limit = 50, string $eventType = ''): array
    {
        if ($eventType !== '') {
            return $this->db->fetchAll(
                "SELECT sl.*, u.username
                 FROM   security_logs sl
                 LEFT JOIN users u ON sl.user_id = u.id
                 WHERE  sl.event_type = ?
                 ORDER  BY sl.created_at DESC
                 LIMIT  ?",
                [$eventType, $limit]
            );
        }

        return $this->db->fetchAll(
            "SELECT sl.*, u.username
             FROM   security_logs sl
             LEFT JOIN users u ON sl.user_id = u.id
             ORDER  BY sl.created_at DESC
             LIMIT  ?",
            [$limit]
        );
    }

    /**
     * Return aggregate counters for the admin dashboard.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return [
            'total_logs'      => $this->db->count("SELECT COUNT(*) FROM security_logs"),
            'logs_hoy'        => $this->db->count("SELECT COUNT(*) FROM security_logs WHERE DATE(created_at) = CURDATE()"),
            'ips_bloqueadas'  => $this->db->count("SELECT COUNT(*) FROM blocked_ips WHERE permanent = 1 OR expires_at > NOW()"),
            'intentos_xss'    => $this->db->count("SELECT COUNT(*) FROM security_logs WHERE event_type = 'xss_attempt'"),
            'intentos_sqli'   => $this->db->count("SELECT COUNT(*) FROM security_logs WHERE event_type = 'sqli_attempt'"),
            'bots_bloqueados' => $this->db->count("SELECT COUNT(*) FROM security_logs WHERE event_type = 'bad_bot'"),
            'logins_fallidos' => $this->db->count(
                "SELECT COUNT(*) FROM login_attempts
                 WHERE  success = 0
                 AND    attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            ),
        ];
    }

    // -------------------------------------------------------------------------
    // Maintenance
    // -------------------------------------------------------------------------

    /**
     * Delete log rows older than $days days, and prune expired IP blocks.
     * Designed to be called from a cron job, not during a web request.
     */
    public function cleanOldLogs(int $days = 30): void
    {
        $this->db->update(
            "DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );

        $this->db->update(
            "DELETE FROM blocked_ips WHERE permanent = 0 AND expires_at < NOW()"
        );
    }
}
