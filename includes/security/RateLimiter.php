<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * RateLimiter
 *
 * Owns everything related to the `blocked_ips` table:
 *   - checking whether a request IP is currently blocked,
 *   - manually blocking / unblocking addresses,
 *   - auto-blocking IPs that exceed a failed-login threshold.
 *
 * All public mutating methods return bool so callers can react
 * without catching exceptions.
 */
class RateLimiter
{
    public function __construct(
        private readonly Database       $db,
        private readonly SecurityLogger $logger
    ) {}

    // -------------------------------------------------------------------------
    // Checks
    // -------------------------------------------------------------------------

    /**
     * Return the block record if $ip is currently blocked, or null if not.
     *
     * @return array<string, mixed>|null
     */
    public function findBlock(string $ip): ?array
    {
        return $this->db->fetch(
            "SELECT reason, permanent, expires_at
             FROM   blocked_ips
             WHERE  ip_address = ?
             AND    (permanent = 1 OR expires_at > NOW())
             LIMIT  1",
            [$ip]
        ) ?: null;
    }

    // -------------------------------------------------------------------------
    // Mutations
    // -------------------------------------------------------------------------

    /**
     * Block an IP address.
     *
     * Refuses to block loopback, private, or reserved addresses.
     * Uses INSERT … ON DUPLICATE KEY UPDATE so repeated calls are idempotent.
     *
     * @param string $ip        Must be a valid public IP.
     * @param string $reason    Human-readable explanation stored in the DB.
     * @param bool   $permanent True = never expires.
     * @param int    $hours     TTL when $permanent is false (default 24 h).
     * @return bool             True on success, false on validation failure or DB error.
     */
    public function blockIP(
        string $ip,
        string $reason    = '',
        bool   $permanent = false,
        int    $hours     = 24
    ): bool {
        if (!IpHelper::isValidIP($ip))      return false;
        if (IpHelper::isProtectedIP($ip))   return false;
        if (!IpHelper::isPublicIP($ip))     return false;

        $expires = $permanent ? null : date('Y-m-d H:i:s', time() + ($hours * 3600));

        try {
            $this->db->insert(
                "INSERT INTO blocked_ips
                    (ip_address, reason, blocked_at, expires_at, permanent)
                 VALUES (?, ?, NOW(), ?, ?)
                 ON DUPLICATE KEY UPDATE
                    reason     = VALUES(reason),
                    blocked_at = NOW(),
                    expires_at = VALUES(expires_at),
                    permanent  = VALUES(permanent)",
                [$ip, $reason, $expires, $permanent ? 1 : 0]
            );

            $this->logger->log('ip_blocked', "IP bloqueada: {$ip} — {$reason}", $ip);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Remove a block record for $ip.
     *
     * @return bool True when at least one row was deleted.
     */
    public function unblockIP(string $ip): bool
    {
        $affected = $this->db->update(
            "DELETE FROM blocked_ips WHERE ip_address = ?",
            [$ip]
        );
        return $affected > 0;
    }

    /**
     * Return all currently active blocked IPs, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBlockedIPs(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM blocked_ips ORDER BY blocked_at DESC"
        );
    }

    // -------------------------------------------------------------------------
    // Auto-block on failed logins
    // -------------------------------------------------------------------------

    /**
     * Inspect recent failed login attempts for $ip and auto-block if
     * the threshold is exceeded.
     *
     * Call this after processing a login (success or failure) so the
     * counter is always current when the decision is made.
     *
     * @param string $ip             IP to evaluate.
     * @param int    $maxAttempts    Block after this many failures (default 20).
     * @param int    $windowMinutes  Look-back window in minutes (default 60).
     * @param int    $blockHours     Block duration in hours (default 24).
     */
    public function autoBlockOnFailedLogins(
        string $ip,
        int    $maxAttempts    = 20,
        int    $windowMinutes  = 60,
        int    $blockHours     = 24
    ): void {
        $attempts = $this->db->count(
            "SELECT COUNT(*) FROM login_attempts
             WHERE  ip_address = ?
             AND    success    = 0
             AND    attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$ip, $windowMinutes]
        );

        if ($attempts >= $maxAttempts) {
            $this->blockIP(
                $ip,
                "Auto-bloqueado: {$attempts} intentos de login fallidos en {$windowMinutes} minutos",
                false,
                $blockHours
            );
        }
    }
}
