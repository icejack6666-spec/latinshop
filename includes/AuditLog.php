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
                    // DESPUÉS
                    IpHelper::getRealIP(),
                ]
            );
        } catch (\Exception $e) {
            error_log('[AuditLog] Error: ' . $e->getMessage());
        }
    }
}
