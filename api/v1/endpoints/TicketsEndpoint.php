<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

require_once INCLUDES_PATH . '/Support/SupportTicketRepository.php';
require_once INCLUDES_PATH . '/Support/SupportMessageRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentService.php';
require_once INCLUDES_PATH . '/Support/SupportTicketService.php';

/**
 * TicketsEndpoint — /api/v1/tickets
 *
 * Recursos del usuario autenticado sobre sus propios tickets.
 *
 * GET    /api/v1/tickets               → Listar mis tickets
 * POST   /api/v1/tickets               → Crear ticket
 * GET    /api/v1/tickets/{id}          → Ver detalle
 * POST   /api/v1/tickets/{id}/reply    → Agregar respuesta
 * PATCH  /api/v1/tickets/{id}/status   → Cambiar estado (solo close/reopen propios)
 * PATCH  /api/v1/tickets/{id}/close    → Cerrar ticket
 */
class TicketsEndpoint extends BaseEndpoint
{
    private SupportTicketService    $service;
    private SupportTicketRepository $ticketRepo;
    private SupportMessageRepository $msgRepo;
    private AttachmentRepository    $attRepo;

    public function __construct(ApiAuth $auth)
    {
        parent::__construct($auth);
        $this->service    = new SupportTicketService();
        $this->ticketRepo = $this->service->getTicketRepo();
        $this->msgRepo    = $this->service->getMsgRepo();
        $this->attRepo    = $this->service->getAttachmentRepo();
    }

    // ─── GET /tickets ─────────────────────────────────────────────────────────

    public function index(array $params): void
    {
        $this->auth->requireScope('tickets:read');
        $userId = $this->auth->getUserId();

        $pag    = $this->pagination(50);
        $status = $this->query('status', '');

        // Validar status si viene
        if ($status !== '' && !in_array($status, ['open','pending','answered','closed'], true)) {
            ApiResponse::validationError("El parámetro 'status' debe ser: open, pending, answered o closed.");
        }

        $data = $this->ticketRepo->findByUser($userId, $pag['page'], $pag['per_page'], $status);

        ApiResponse::ok(
            array_map([$this, 'formatTicket'], $data['items']),
            $this->paginationMeta($data['total'], $pag['page'], $pag['per_page'])
        );
    }

    // ─── POST /tickets ────────────────────────────────────────────────────────

    public function store(array $params): void
    {
        $this->auth->requireScope('tickets:write');
        $userId = $this->auth->getUserId();

        $body = $this->body();
        $this->validate($body, [
            'subject'  => 'required|string|min:5|max:200',
            'body'     => 'required|string|min:20',
            'category' => 'required|in:technical,billing,account,other',
            'priority' => 'in:low,medium,high,urgent',
        ]);

        $result = $this->service->createTicket($body, $userId);

        if (!$result['success']) {
            ApiResponse::validationError($result['errors']);
        }

        $ticket = $this->ticketRepo->findById($result['ticket_id']);
        ApiResponse::created(
            $this->formatTicket($ticket),
            u('/api/v1/tickets/' . $result['ticket_id'])
        );
    }

    // ─── GET /tickets/{id} ────────────────────────────────────────────────────

    public function show(array $params): void
    {
        $this->auth->requireScope('tickets:read');
        $userId   = $this->auth->getUserId();
        $ticketId = (int)($params['id'] ?? 0);

        $ticket = $this->ticketRepo->findById($ticketId);

        if (!$ticket) {
            ApiResponse::notFound('Ticket no encontrado.');
        }
        if ((int)$ticket['user_id'] !== $userId && !$this->auth->isAdmin()) {
            ApiResponse::forbidden();
        }

        $messages = $this->msgRepo->findByTicket($ticketId, false);
        $formatted = array_map(function (array $msg) {
            $atts = $this->attRepo->findByMessage((int)$msg['id']);
            return $this->formatMessage($msg, $atts);
        }, $messages);

        ApiResponse::ok([
            'ticket'   => $this->formatTicket($ticket),
            'messages' => $formatted,
        ]);
    }

    // ─── POST /tickets/{id}/reply ─────────────────────────────────────────────

    public function reply(array $params): void
    {
        $this->auth->requireScope('tickets:write');
        $userId   = $this->auth->getUserId();
        $ticketId = (int)($params['id'] ?? 0);

        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) ApiResponse::notFound('Ticket no encontrado.');
        if ((int)$ticket['user_id'] !== $userId) ApiResponse::forbidden();

        $body = $this->body();
        $this->validate($body, ['body' => 'required|string|min:5']);

        $result = $this->service->addReply($ticketId, $userId, $body['body'], false, false);

        if (!$result['success']) {
            ApiResponse::validationError($result['errors']);
        }

        $msg = $this->msgRepo->findById($result['message_id']);
        ApiResponse::created($this->formatMessage($msg, []));
    }

    // ─── PATCH /tickets/{id}/status ───────────────────────────────────────────

    public function changeStatus(array $params): void
    {
        $this->auth->requireScope('tickets:close');
        $userId   = $this->auth->getUserId();
        $ticketId = (int)($params['id'] ?? 0);

        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) ApiResponse::notFound('Ticket no encontrado.');
        if ((int)$ticket['user_id'] !== $userId) ApiResponse::forbidden();

        $body = $this->body();
        $this->validate($body, [
            'status' => 'required|in:closed,open',
        ]);

        $result = $this->service->changeStatus($ticketId, $body['status'], $userId);
        if (!$result['success']) {
            ApiResponse::validationError($result['errors']);
        }

        ApiResponse::ok(['status' => $body['status']]);
    }

    // ─── PATCH /tickets/{id}/close ────────────────────────────────────────────

    public function close(array $params): void
    {
        $this->auth->requireScope('tickets:close');
        $userId   = $this->auth->getUserId();
        $ticketId = (int)($params['id'] ?? 0);

        $ticket = $this->ticketRepo->findById($ticketId);
        if (!$ticket) ApiResponse::notFound('Ticket no encontrado.');
        if ((int)$ticket['user_id'] !== $userId) ApiResponse::forbidden();

        $result = $this->service->changeStatus($ticketId, 'closed', $userId);
        if (!$result['success']) {
            ApiResponse::validationError($result['errors']);
        }

        ApiResponse::ok(['status' => 'closed']);
    }

    // ─── FORMATTERS ──────────────────────────────────────────────────────────

    private function formatTicket(array $t): array
    {
        return [
            'id'                => (int)$t['id'],
            'subject'           => $t['subject'],
            'status'            => $t['status'],
            'priority'          => $t['priority'],
            'category'          => $t['category'],
            'assigned_to'       => $t['assigned_username'] ?? null,
            'message_count'     => (int)($t['message_count'] ?? 0),
            'created_at'        => $t['created_at'],
            'updated_at'        => $t['updated_at'],
            'closed_at'         => $t['closed_at'] ?? null,
        ];
    }

    private function formatMessage(array $m, array $attachments): array
    {
        return [
            'id'         => (int)$m['id'],
            'body'       => $m['body'],
            'author'     => [
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
