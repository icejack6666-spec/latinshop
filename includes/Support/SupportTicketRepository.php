<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) {
    die('Acceso directo no permitido.');
}

/**
 * SupportTicketRepository
 *
 * Capa de acceso a datos exclusiva para support_tickets.
 * Todos los métodos usan prepared statements a través del PDO wrapper existente.
 *
 * v2 — integración con CacheService:
 *  - Lecturas frecuentes se sirven desde Redis cuando está disponible.
 *  - Las escrituras invalidan las claves afectadas de forma atómica.
 *  - Si Redis no está disponible, todas las rutas caen a MySQL sin errores.
 */
class SupportTicketRepository
{
    private Database     $db;
    private CacheService $cache;

    public function __construct()
    {
        $this->db    = Database::getInstance();
        $this->cache = CacheService::getInstance();
    }

    // ─── CREACIÓN ─────────────────────────────────────────────────────────────

    /**
     * Inserta un nuevo ticket y devuelve su ID.
     * Invalida la caché de listas del usuario y estadísticas globales.
     */
    public function create(
        int    $userId,
        string $subject,
        string $category,
        string $priority
    ): int {
        $id = $this->db->insert(
            "INSERT INTO support_tickets
                (user_id, subject, category, priority, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'open', NOW(), NOW())",
            [$userId, $subject, $category, $priority]
        );

        // Invalida listas del usuario + stats globales
        $this->cache->invalidatePattern('tickets:user:' . $userId . ':*');
        $this->cache->delete(
            CacheService::keyOpenCount($userId),
            CacheService::keyAdminTicketStats(),
            CacheService::keyAdminDashboard(),
        );

        return $id;
    }

    // ─── LECTURAS ─────────────────────────────────────────────────────────────

    /**
     * Obtiene un ticket por ID (incluye datos del usuario dueño y del admin asignado).
     * Cacheado TTL_MEDIUM (5 min). Se invalida en cada escritura.
     */
    public function findById(int $id): array|false
    {
        $cacheKey = CacheService::keyTicket($id);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $row = $this->db->fetch(
            "SELECT
                t.*,
                u.username  AS owner_username,
                u.avatar_url AS owner_avatar,
                a.username  AS assigned_username
             FROM support_tickets t
             INNER JOIN users u ON u.id = t.user_id
             LEFT  JOIN users a ON a.id = t.assigned_to
             WHERE t.id = ?
             LIMIT 1",
            [$id]
        );

        if ($row !== false) {
            $this->cache->set($cacheKey, $row, CacheService::TTL_MEDIUM);
        }

        return $row;
    }

    /**
     * Tickets de un usuario concreto con paginación.
     * Cacheado TTL_SHORT (60 s) por usuario+página+filtro.
     *
     * @return array{items: array, total: int}
     */
    public function findByUser(
        int    $userId,
        int    $page    = 1,
        int    $perPage = 15,
        string $status  = ''
    ): array {
        $cacheKey = CacheService::keyUserTickets($userId, $page, $status);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $offset = ($page - 1) * $perPage;
        $params = [$userId];
        $where  = 'WHERE t.user_id = ?';

        if ($status !== '') {
            $where    .= ' AND t.status = ?';
            $params[]  = $status;
        }

        $total = $this->db->count(
            "SELECT COUNT(*) FROM support_tickets t $where",
            $params
        );

        $items = $this->db->fetchAll(
            "SELECT
                t.*,
                a.username AS assigned_username,
                (SELECT COUNT(*) FROM support_messages sm WHERE sm.ticket_id = t.id) AS message_count
             FROM support_tickets t
             LEFT JOIN users a ON a.id = t.assigned_to
             $where
             ORDER BY t.updated_at DESC
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        $result = ['items' => $items, 'total' => $total];
        $this->cache->set($cacheKey, $result, CacheService::TTL_SHORT);

        return $result;
    }

    /**
     * Todos los tickets (panel admin) con filtros opcionales y paginación.
     * No se cachea porque los filtros son altamente variables y el admin
     * necesita datos frescos. Solo se cachea getAdminStats().
     *
     * @return array{items: array, total: int}
     */
    public function findAll(
        int    $page      = 1,
        int    $perPage   = 20,
        string $status    = '',
        string $priority  = '',
        string $category  = '',
        string $search    = '',
        int    $assignedTo = 0
    ): array {
        $offset  = ($page - 1) * $perPage;
        $where   = ['1=1'];
        $params  = [];

        if ($status !== '') {
            $where[]  = 't.status = ?';
            $params[] = $status;
        }
        if ($priority !== '') {
            $where[]  = 't.priority = ?';
            $params[] = $priority;
        }
        if ($category !== '') {
            $where[]  = 't.category = ?';
            $params[] = $category;
        }
        if ($assignedTo > 0) {
            $where[]  = 't.assigned_to = ?';
            $params[] = $assignedTo;
        }
        if ($search !== '') {
            $where[]  = '(t.subject LIKE ? OR u.username LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = implode(' AND ', $where);

        $total = $this->db->count(
            "SELECT COUNT(*)
             FROM support_tickets t
             INNER JOIN users u ON u.id = t.user_id
             WHERE $whereStr",
            $params
        );

        $items = $this->db->fetchAll(
            "SELECT
                t.*,
                u.username   AS owner_username,
                u.avatar_url AS owner_avatar,
                a.username   AS assigned_username,
                (SELECT COUNT(*) FROM support_messages sm WHERE sm.ticket_id = t.id) AS message_count
             FROM support_tickets t
             INNER JOIN users u ON u.id = t.user_id
             LEFT  JOIN users a ON a.id = t.assigned_to
             WHERE $whereStr
             ORDER BY
                FIELD(t.priority,'urgent','high','medium','low'),
                t.updated_at DESC
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Contador de tickets abiertos de un usuario (para badges en el nav).
     * Cacheado TTL_MEDIUM (5 min). Se invalida al cambiar estado o crear ticket.
     */
    public function countOpenByUser(int $userId): int
    {
        $cacheKey = CacheService::keyOpenCount($userId);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return (int) $cached;
        }

        $count = $this->db->count(
            "SELECT COUNT(*) FROM support_tickets
             WHERE user_id = ? AND status IN ('open','pending','answered')",
            [$userId]
        );

        $this->cache->set($cacheKey, $count, CacheService::TTL_MEDIUM);

        return $count;
    }

    /**
     * Contadores globales para el dashboard admin.
     * Cacheado TTL_MEDIUM (5 min). Se invalida en cada cambio de estado.
     *
     * @return array<string, int>
     */
    public function getAdminStats(): array
    {
        $cacheKey = CacheService::keyAdminTicketStats();

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $row = $this->db->fetch(
            "SELECT
                SUM(status = 'open')     AS open_count,
                SUM(status = 'pending')  AS pending_count,
                SUM(status = 'answered') AS answered_count,
                SUM(status = 'closed')   AS closed_count,
                SUM(priority = 'urgent' AND status != 'closed') AS urgent_open,
                COUNT(*)                 AS total_count
             FROM support_tickets"
        );

        $stats = array_map('intval', $row ?: []);
        $this->cache->set($cacheKey, $stats, CacheService::TTL_MEDIUM);

        return $stats;
    }

    // ─── ACTUALIZACIONES ──────────────────────────────────────────────────────

    /**
     * Cambia el estado de un ticket.
     * Invalida la caché del ticket y las estadísticas.
     */
    public function updateStatus(int $ticketId, string $status): int
    {
        $extra = $status === 'closed' ? ', closed_at = NOW()' : '';
        $rows  = $this->db->update(
            "UPDATE support_tickets
             SET status = ?, last_reply_at = NOW(), updated_at = NOW() $extra
             WHERE id = ?",
            [$status, $ticketId]
        );

        $this->invalidateTicketCache($ticketId);

        return $rows;
    }

    /**
     * Cambia la prioridad de un ticket.
     */
    public function updatePriority(int $ticketId, string $priority): int
    {
        $rows = $this->db->update(
            "UPDATE support_tickets SET priority = ?, updated_at = NOW() WHERE id = ?",
            [$priority, $ticketId]
        );

        $this->invalidateTicketCache($ticketId);

        return $rows;
    }

    /**
     * Asigna (o desasigna) un admin responsable.
     */
    public function assign(int $ticketId, ?int $adminId): int
    {
        $rows = $this->db->update(
            "UPDATE support_tickets SET assigned_to = ?, updated_at = NOW() WHERE id = ?",
            [$adminId, $ticketId]
        );

        $this->cache->delete(CacheService::keyTicket($ticketId));

        return $rows;
    }

    /**
     * Actualiza last_reply_at y status cuando se agrega un mensaje.
     */
    public function touchOnReply(int $ticketId, string $newStatus): void
    {
        $this->db->update(
            "UPDATE support_tickets
             SET status = ?, last_reply_at = NOW(), updated_at = NOW()
             WHERE id = ?",
            [$newStatus, $ticketId]
        );

        $this->invalidateTicketCache($ticketId);
    }

    // ─── PERMISOS ─────────────────────────────────────────────────────────────

    /**
     * Verifica que el ticket pertenece al usuario dado (o es admin).
     */
    public function belongsToUser(int $ticketId, int $userId): bool
    {
        return $this->db->count(
            "SELECT COUNT(*) FROM support_tickets WHERE id = ? AND user_id = ?",
            [$ticketId, $userId]
        ) > 0;
    }

    // ─── HELPERS PRIVADOS ─────────────────────────────────────────────────────

    /**
     * Invalida caché del ticket + stats globales.
     * Necesita el user_id del ticket para también limpiar el open_count.
     */
    private function invalidateTicketCache(int $ticketId): void
    {
        // Buscamos el user_id del ticket para poder limpiar su open_count.
        // Usamos query directa para evitar bucle recursivo con findById.
        $row = $this->db->fetch(
            "SELECT user_id FROM support_tickets WHERE id = ? LIMIT 1",
            [$ticketId]
        );

        $this->cache->delete(CacheService::keyTicket($ticketId));
        $this->cache->delete(CacheService::keyAdminTicketStats());

        if ($row !== false) {
            $userId = (int) $row['user_id'];
            $this->cache->delete(CacheService::keyOpenCount($userId));
            $this->cache->invalidatePattern('tickets:user:' . $userId . ':*');
        }
    }
}
