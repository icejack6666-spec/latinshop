<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * SupportTicketService
 *
 * Lógica de negocio del sistema de tickets de soporte.
 * Coordina repositorios, auditoría y notificaciones.
 */
class SupportTicketService
{
    private SupportTicketRepository $ticketRepo;
    private SupportMessageRepository $msgRepo;
    private AttachmentService $attachmentService;

    private const VALID_STATUSES   = ['open', 'pending', 'answered', 'closed'];
    private const VALID_PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    private const VALID_CATEGORIES = ['technical', 'billing', 'account', 'other'];

    public function __construct()
    {
        $this->ticketRepo        = new SupportTicketRepository();
        $this->msgRepo           = new SupportMessageRepository();
        $this->attachmentService = new AttachmentService();
    }

    // ─── CREAR TICKET ─────────────────────────────────────────────────────────

    /**
     * @param array $data  Datos POST del formulario (sin sanitizar aún)
     * @param int   $userId
     * @param array $files $_FILES (puede incluir 'attachments')
     * @return array{success: bool, ticket_id?: int, errors?: array}
     */
    public function createTicket(array $data, int $userId, array $files = []): array
    {
        $errors = [];

        // Validación
        $subject  = trim($data['subject'] ?? '');
        $body     = trim($data['body']    ?? '');
        $category = $data['category']     ?? '';
        $priority = $data['priority']     ?? 'medium';

        if (mb_strlen($subject) < 5 || mb_strlen($subject) > 200) {
            $errors[] = 'El asunto debe tener entre 5 y 200 caracteres.';
        }
        if (mb_strlen($body) < 20) {
            $errors[] = 'El mensaje debe tener al menos 20 caracteres.';
        }
        if (!in_array($category, self::VALID_CATEGORIES, true)) {
            $errors[] = 'Categoría no válida.';
        }
        if (!in_array($priority, self::VALID_PRIORITIES, true)) {
            $priority = 'medium';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // 1. Crear ticket
            $ticketId = $this->ticketRepo->create($userId, $subject, $category, $priority);

            // 2. Crear mensaje inicial
            $msgId = $this->msgRepo->create($ticketId, $userId, $body, false);

            // 3. Procesar adjuntos si los hay
            $attErrors = [];
            if (!empty($files['attachments']['name'][0]) || !empty($files['attachments']['name'])) {
                $normalized = AttachmentService::normalizeFiles($files['attachments']);
                $result     = $this->attachmentService->processUploads(
                    $normalized, $msgId, $ticketId, $userId
                );
                $attErrors = $result['errors'];
            }

            // 4. Log de acción
            $this->logAction($ticketId, $userId, 'ticket_created', [
                'subject'  => $subject,
                'category' => $category,
                'priority' => $priority,
            ]);

            $db->commit();

            return [
                'success'    => true,
                'ticket_id'  => $ticketId,
                'att_errors' => $attErrors,
            ];

        } catch (\Exception $e) {
            $db->rollback();
            error_log('[SupportTicketService] createTicket error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Error interno. Intenta de nuevo.']];
        }
    }

    // ─── RESPONDER TICKET ─────────────────────────────────────────────────────

    /**
     * Agrega un mensaje a un ticket existente.
     *
     * @param int    $ticketId
     * @param int    $userId     ID del usuario que responde
     * @param string $body       Contenido del mensaje
     * @param bool   $isAdmin    Si es admin, puede marcar nota interna
     * @param bool   $isInternal Solo visible para admins
     * @param array  $files      $_FILES
     * @return array{success: bool, message_id?: int, errors?: array}
     */
    public function addReply(
        int    $ticketId,
        int    $userId,
        string $body,
        bool   $isAdmin     = false,
        bool   $isInternal  = false,
        array  $files       = []
    ): array {
        $errors = [];

        $body = trim($body);
        if (mb_strlen($body) < 5) {
            $errors[] = 'El mensaje es demasiado corto (mínimo 5 caracteres).';
        }

        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'errors' => ['Ticket no encontrado.']];
        }
        if ($ticket['status'] === 'closed') {
            return ['success' => false, 'errors' => ['El ticket está cerrado.']];
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Nota interna: solo admins
            $internal = $isAdmin && $isInternal;

            $msgId = $this->msgRepo->create($ticketId, $userId, $body, $internal);

            // Actualizar estado del ticket
            // Admin responde → 'answered'; Usuario responde → 'pending'
            if (!$internal) {
                $newStatus = $isAdmin ? 'answered' : 'pending';
                $this->ticketRepo->touchOnReply($ticketId, $newStatus);
            }

            // Adjuntos
            $attErrors = [];
            if (!empty($files['attachments']['name'][0]) || !empty($files['attachments']['name'])) {
                $normalized = AttachmentService::normalizeFiles($files['attachments']);
                $result     = $this->attachmentService->processUploads(
                    $normalized, $msgId, $ticketId, $userId
                );
                $attErrors = $result['errors'];
            }

            // Notificación al usuario si respondió el admin
            if ($isAdmin && !$internal) {
                Notifications::send(
                    (int)$ticket['user_id'],
                    'support',
                    'Respuesta en tu ticket',
                    "Tu ticket #{$ticketId} «" . mb_substr($ticket['subject'], 0, 60) . "» ha sido respondido.",
                    u('/support/ver?id=' . $ticketId)
                );
            }

            $this->logAction($ticketId, $userId, 'reply_added', [
                'is_internal' => $internal,
                'is_admin'    => $isAdmin,
            ]);

            $db->commit();

            return [
                'success'    => true,
                'message_id' => $msgId,
                'att_errors' => $attErrors,
            ];

        } catch (\Exception $e) {
            $db->rollback();
            error_log('[SupportTicketService] addReply error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Error interno. Intenta de nuevo.']];
        }
    }

    // ─── CAMBIAR ESTADO ───────────────────────────────────────────────────────

    /**
     * Cambia el estado de un ticket (admin o usuario para cerrar).
     */
    public function changeStatus(int $ticketId, string $status, int $actorId): array
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            return ['success' => false, 'errors' => ['Estado no válido.']];
        }

        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'errors' => ['Ticket no encontrado.']];
        }

        $old = $ticket['status'];
        $this->ticketRepo->updateStatus($ticketId, $status);

        $this->logAction($ticketId, $actorId, 'status_changed', [
            'from' => $old,
            'to'   => $status,
        ]);

        // Notificar al usuario si el admin cierra o reabre
        if ($status === 'closed' && $actorId !== (int)$ticket['user_id']) {
            Notifications::send(
                (int)$ticket['user_id'],
                'support',
                'Tu ticket ha sido cerrado',
                "El ticket #{$ticketId} «" . mb_substr($ticket['subject'], 0, 60) . "» fue cerrado.",
                u('/support/ver?id=' . $ticketId)
            );
        }

        return ['success' => true];
    }

    // ─── ASIGNAR ──────────────────────────────────────────────────────────────

    public function assign(int $ticketId, ?int $adminId, int $actorId): array
    {
        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'errors' => ['Ticket no encontrado.']];
        }

        $this->ticketRepo->assign($ticketId, $adminId);

        $this->logAction($ticketId, $actorId, 'assigned', ['admin_id' => $adminId]);

        return ['success' => true];
    }

    // ─── CAMBIAR PRIORIDAD ────────────────────────────────────────────────────

    public function changePriority(int $ticketId, string $priority, int $actorId): array
    {
        if (!in_array($priority, self::VALID_PRIORITIES, true)) {
            return ['success' => false, 'errors' => ['Prioridad no válida.']];
        }

        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'errors' => ['Ticket no encontrado.']];
        }

        $old = $ticket['priority'];
        $this->ticketRepo->updatePriority($ticketId, $priority);

        $this->logAction($ticketId, $actorId, 'priority_changed', [
            'from' => $old,
            'to'   => $priority,
        ]);

        return ['success' => true];
    }

    // ─── GETTERS PARA VISTAS ──────────────────────────────────────────────────

    public function getTicketRepo(): SupportTicketRepository
    {
        return $this->ticketRepo;
    }

    public function getMsgRepo(): SupportMessageRepository
    {
        return $this->msgRepo;
    }

    public function getAttachmentService(): AttachmentService
    {
        return $this->attachmentService;
    }

    public function getAttachmentRepo(): AttachmentRepository
    {
        return new AttachmentRepository();
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    /**
     * Etiqueta HTML de estado.
     */
    public static function statusBadge(string $status): string
    {
        $map = [
            'open'     => ['label' => 'Abierto',    'class' => 'badge--open'],
            'pending'  => ['label' => 'Pendiente',  'class' => 'badge--pending'],
            'answered' => ['label' => 'Respondido', 'class' => 'badge--answered'],
            'closed'   => ['label' => 'Cerrado',    'class' => 'badge--closed'],
        ];
        $d = $map[$status] ?? ['label' => $status, 'class' => 'badge--default'];
        return '<span class="support-badge ' . $d['class'] . '">'
            . htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8') . '</span>';
    }

    /**
     * Etiqueta HTML de prioridad.
     */
    public static function priorityBadge(string $priority): string
    {
        $map = [
            'low'    => ['label' => 'Baja',    'class' => 'badge--low'],
            'medium' => ['label' => 'Media',   'class' => 'badge--medium'],
            'high'   => ['label' => 'Alta',    'class' => 'badge--high'],
            'urgent' => ['label' => 'Urgente', 'class' => 'badge--urgent'],
        ];
        $d = $map[$priority] ?? ['label' => $priority, 'class' => 'badge--default'];
        return '<span class="support-badge ' . $d['class'] . '">'
            . htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8') . '</span>';
    }

    /**
     * Etiqueta legible de categoría.
     */
    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            'technical' => 'Técnico',
            'billing'   => 'Facturación',
            'account'   => 'Cuenta',
            'other'     => 'Otro',
            default     => htmlspecialchars($category, ENT_QUOTES, 'UTF-8'),
        };
    }

    /**
     * Formato legible de bytes.
     */

    public static function formatBytes(int $bytes): string
    {
        return format_bytes($bytes);
    }

    // ─── PRIVADO ──────────────────────────────────────────────────────────────

    private function logAction(int $ticketId, int $userId, string $action, array $detail = []): void
    {
        try {
            $db = Database::getInstance();
            $db->insert(
                "INSERT INTO ticket_action_log (ticket_id, user_id, action, detail, ip_address, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [
                    $ticketId,
                    $userId,
                    $action,
                    !empty($detail) ? json_encode($detail) : null,
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ]
            );
        } catch (\Exception $e) {
            error_log('[SupportTicketService] logAction error: ' . $e->getMessage());
        }
    }
}
