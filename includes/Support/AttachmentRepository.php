<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * AttachmentRepository
 *
 * Capa de acceso a datos para support_attachments.
 */
class AttachmentRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Registra un adjunto en base de datos.
     */
    public function create(
        int    $messageId,
        int    $ticketId,
        int    $uploadedBy,
        string $originalName,
        string $storedName,
        string $mimeType,
        int    $sizeBytes
    ): int {
        return $this->db->insert(
            "INSERT INTO support_attachments
                (message_id, ticket_id, uploaded_by,
                 original_name, stored_name, mime_type, size_bytes, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [$messageId, $ticketId, $uploadedBy,
             $originalName, $storedName, $mimeType, $sizeBytes]
        );
    }

    /**
     * Adjuntos de un mensaje concreto (excluyendo soft-deleted).
     */
    public function findByMessage(int $messageId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM support_attachments
             WHERE message_id = ? AND deleted_at IS NULL
             ORDER BY created_at ASC",
            [$messageId]
        );
    }

    /**
     * Todos los adjuntos activos de un ticket.
     */
    public function findByTicket(int $ticketId): array
    {
        return $this->db->fetchAll(
            "SELECT sa.*, u.username AS uploader_username
             FROM support_attachments sa
             INNER JOIN users u ON u.id = sa.uploaded_by
             WHERE sa.ticket_id = ? AND sa.deleted_at IS NULL
             ORDER BY sa.created_at ASC",
            [$ticketId]
        );
    }

    /**
     * Obtiene un adjunto por ID (para servir el archivo).
     */
    public function findById(int $id): array|false
    {
        return $this->db->fetch(
            "SELECT * FROM support_attachments WHERE id = ? AND deleted_at IS NULL LIMIT 1",
            [$id]
        );
    }

    /**
     * Soft delete de un adjunto.
     */
    public function softDelete(int $id): int
    {
        return $this->db->update(
            "UPDATE support_attachments SET deleted_at = NOW() WHERE id = ?",
            [$id]
        );
    }
}
