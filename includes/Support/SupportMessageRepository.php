<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * SupportMessageRepository
 *
 * Capa de acceso a datos para support_messages.
 */
class SupportMessageRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Inserta un nuevo mensaje y devuelve su ID.
     */
    public function create(
        int    $ticketId,
        int    $userId,
        string $body,
        bool   $isInternal = false
    ): int {
        return $this->db->insert(
            "INSERT INTO support_messages
                (ticket_id, user_id, body, is_internal, created_at)
             VALUES (?, ?, ?, ?, NOW())",
            [$ticketId, $userId, $body, (int)$isInternal]
        );
    }

    /**
     * Todos los mensajes de un ticket con datos del autor.
     * Los clientes NO ven mensajes internos.
     */
    public function findByTicket(int $ticketId, bool $includeInternal = false): array
    {
        $where = $includeInternal ? '' : 'AND m.is_internal = 0';

        return $this->db->fetchAll(
            "SELECT
                m.*,
                u.username   AS author_username,
                u.role       AS author_role,
                u.avatar_url AS author_avatar
             FROM support_messages m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.ticket_id = ? $where
             ORDER BY m.created_at ASC",
            [$ticketId]
        );
    }

    /**
     * Obtiene un mensaje por ID.
     */
    public function findById(int $id): array|false
    {
        return $this->db->fetch(
            "SELECT m.*, u.username AS author_username, u.role AS author_role
             FROM support_messages m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.id = ?
             LIMIT 1",
            [$id]
        );
    }
}
