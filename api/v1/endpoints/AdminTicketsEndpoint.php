<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

require_once INCLUDES_PATH . '/Support/SupportTicketRepository.php';
require_once INCLUDES_PATH . '/Support/SupportMessageRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentService.php';
require_once INCLUDES_PATH . '/Support/SupportTicketService.php';

/**
 * AdminTicketsEndpoint — /api/v1/admin/tickets
 *
 * Solo accesible con rol admin.
 *
 * GET    /api/v1/admin/tickets          → Listar todos (con filtros)
 * GET    /api/v1/admin/tickets/{id}     → Ver detalle completo
 * PATCH  /api/v1/admin/tickets/{id}     → Actualizar estado/prioridad/asignación
 * POST   /api/v1/admin/tickets/{id}/reply → Responder (con soporte de notas internas)
 * GET    /api/v1/admin/stats            → Estadísticas globales
 */
class AdminTicketsEndpoint extends BaseEndpoint
{
    private SupportTicketService     $service;
    private SupportTicketRepository  $ticketRepo;
    private SupportMessageRepository $msgRepo;
    private AttachmentRepository     $attRepo;

    public function __construct(ApiAuth $auth)
    {
        parent::__construct($auth);
        $this->service    = new SupportTicketService();
        $this->ticketRepo = $this->service->getTicketRepo();
        $this->msgRepo    = $this->service->getMsgRepo();
        $this->attRepo    = $this->service->getAttachmentRepo();
    }

    // ─── GET /admin/tickets ───────────────────────────────────────────────────

    public function index(array $params): void
    {
        $this->auth->requireAdmin();

        $pag      = $this->pagination(100);
        $status   = $this->query('status', '');
        $priority = $this->query('priority', '');
        $category = $this->query('category', '');
        $search   = mb_substr(trim($this->query('q', '')), 0, 100);

        // Sanear filtros de enum
        $validStatus   = ['', 'open', 'pending', 'answered', 'closed'];
        $validPriority = ['', 'low', 'medium', 'high', 'urgent'];
        $validCategory = ['', 'technical', 'billing', 'account', 'other'];

        if (!in_array($status,   $validStatus,   true)) $status   = '';
        if (!in_array($priority, $validPriority, true)) $priority = '';
        if (!in_array($category, $validCategory, true)) $category = '';

        $data = $this->ticketRepo->findAll(
            $pag['page'], $pag['per_page'],
            $status, $priority, $category, $search
        );

        ApiResponse::ok(
            array_map([$this, 'formatTicketAdmin'], $data['items']),
            $this->paginationMeta($data['total'], $pag['page'], $pag['per_page'])
        );
    }

    // ─── GET /admin/tickets/{id} ─────────────────────────────────────────────

    public function show(array $params): void
    {
        $this->auth->requireAdmin();
        $ticketId = (int)($params['id'] ?? 0);

        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) ApiResponse::notFound('Ticket no encontrado.');

        $messages = $this->msgRepo->findByTicket($ticketId, true); // incluye internas
        $formatted = array_map(function (array $msg) {
            $atts = $this->attRepo->findByMessage((int)$msg['id']);
            return $this->formatMessage($msg, $atts);
        }, $messages);

        // Log de acciones
        $db  = Database::getInstance();
        $log = $db->fetchAll(
            "SELECT tal.action, tal.detail, tal.created_at, u.username AS actor
             FROM ticket_action_log tal
             LEFT JOIN users u ON u.id = tal.user_id
             WHERE tal.ticket_id = ?
             ORDER BY tal.created_at DESC LIMIT 30",
            [$ticketId]
        );

        ApiResponse::ok([
            'ticket'     => $this->formatTicketAdmin($ticket),
            'messages'   => $formatted,
            'action_log' => $log,
        ]);
    }

    // ─── PATCH /admin/tickets/{id} ────────────────────────────────────────────

    /**
     * Actualización parcial: puede incluir status, priority, assigned_to
     * en el mismo request.
     */
    public function update(array $params): void
    {
        $this->auth->requireAdmin();
        $actorId  = $this->auth->getUserId();
        $ticketId = (int)($params['id'] ?? 0);

        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) ApiResponse::notFound('Ticket no encontrado.');

        $body   = $this->body();
        $errors = [];

        if (isset($body['status'])) {
            $r = $this->service->changeStatus($ticketId, $body['status'], $actorId);
            if (!$r['success']) $errors = array_merge($errors, $r['errors']);
        }

        if (isset($body['priority'])) {
            $r = $this->service->changePriority($ticketId, $body['priority'], $actorId);
            if (!$r['success']) $errors = array_merge($errors, $r['errors']);
        }

        if (array_key_exists('assigned_to', $body)) {
            $adminId = $body['assigned_to'] === null ? null : (int)$body['assigned_to'];
            $r = $this->service->assign($ticketId, $adminId, $actorId);
            if (!$r['success']) $errors = array_merge($errors, $r['errors']);
        }

        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }

        $ticket = $this->ticketRepo->findById($ticketId);
        ApiResponse::ok($this->formatTicketAdmin($ticket));
    }

    // ─── POST /admin/tickets/{id}/reply ──────────────────────────────────────

    public function reply(array $params): void
    {
        $this->auth->requireAdmin();
        $actorId  = $this->auth->getUserId();
        $ticketId = (int)($params['id'] ?? 0);

        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) ApiResponse::notFound('Ticket no encontrado.');

        $body = $this->body();
        $this->validate($body, ['body' => 'required|string|min:5']);

        $isInternal = !empty($body['is_internal']);

        $result = $this->service->addReply(
            $ticketId, $actorId, $body['body'],
            true, $isInternal
        );

        if (!$result['success']) {
            ApiResponse::validationError($result['errors']);
        }

        $msg = $this->msgRepo->findById($result['message_id']);
        ApiResponse::created($this->formatMessage($msg, []));
    }

    // ─── GET /admin/stats ─────────────────────────────────────────────────────

    public function stats(array $params): void
    {
        $this->auth->requireAdmin();
        ApiResponse::ok($this->ticketRepo->getAdminStats());
    }

    // ─── FORMATTERS ──────────────────────────────────────────────────────────

    private function formatTicketAdmin(array $t): array
    {
        return [
            'id'               => (int)$t['id'],
            'subject'          => $t['subject'],
            'status'           => $t['status'],
            'priority'         => $t['priority'],
            'category'         => $t['category'],
            'owner'            => [
                'username'   => $t['owner_username'],
                'avatar_url' => $t['owner_avatar'] ?? null,
            ],
            'assigned_to'      => $t['assigned_username'] ?? null,
            'message_count'    => (int)($t['message_count'] ?? 0),
            'created_at'       => $t['created_at'],
            'updated_at'       => $t['updated_at'],
            'last_reply_at'    => $t['last_reply_at'] ?? null,
            'closed_at'        => $t['closed_at'] ?? null,
        ];
    }

    private function formatMessage(array $m, array $attachments): array
    {
        return [
            'id'          => (int)$m['id'],
            'body'        => $m['body'],
            'is_internal' => (bool)$m['is_internal'],
            'author'      => [
                'username' => $m['author_username'],
                'role'     => $m['author_role'],
            ],
            'created_at'  => $m['created_at'],
            'attachments' => array_map(fn($a) => [
                'id'            => (int)$a['id'],
                'original_name' => $a['original_name'],
                'mime_type'     => $a['mime_type'],
                'size_bytes'    => (int)$a['size_bytes'],
                'download_url'  => u('/support/attachment?id=' . $a['id']),
            ], $attachments),
        ];
    }
}
