<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class Notifications {

    public static function send(
        int    $userId,
        string $type,
        string $title,
        string $message,
        string $url = ''
    ): void {
        try {
            $db = Database::getInstance();
            $db->insert(
                "INSERT INTO notifications (user_id, type, title, message, url, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$userId, $type, $title, $message, $url ?: null]
            );
        } catch (\Exception $e) {
            error_log('[Notifications] Error: ' . $e->getMessage());
        }
    }

    public static function getUnread(int $userId): array {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM notifications
             WHERE user_id = ? AND is_read = 0
             ORDER BY created_at DESC LIMIT 20",
            [$userId]
        );
    }

    public static function countUnread(int $userId): int {
        $db = Database::getInstance();
        return $db->count(
            "SELECT COUNT(*) FROM notifications
             WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    public static function markAllRead(int $userId): void {
        $db = Database::getInstance();
        $db->update(
            "UPDATE notifications SET is_read = 1
             WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    public static function markRead(int $notificationId, int $userId): void {
        $db = Database::getInstance();
        $db->update(
            "UPDATE notifications SET is_read = 1
             WHERE id = ? AND user_id = ?",
            [$notificationId, $userId]
        );
    }
}