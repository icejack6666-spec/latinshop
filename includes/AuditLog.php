<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class AuditLog {
    public static function log(
        string $action,
        ?int   $targetId  = null,
        ?array $oldValue  = null,
        ?array $newValue  = null
    ): void {
        try {
            $db = Database::getInstance();
            $db->insert(
                "INSERT INTO audit_log (user_id, action, target_id, old_value, new_value, ip_address, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    $_SESSION['user_id'] ?? null,
                    $action,
                    $targetId,
                    $oldValue ? json_encode($oldValue) : null,
                    $newValue ? json_encode($newValue) : null,
                    filter_var(
                        explode(',', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')[0],
                        FILTER_VALIDATE_IP
                    ) ?: '0.0.0.0',
                ]
            );
        } catch (\Exception $e) {
            error_log('[AuditLog] Error: ' . $e->getMessage());
        }
    }
}
