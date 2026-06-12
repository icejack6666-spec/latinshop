<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole('admin');
$user   = $auth->getUser();
$userId = (int)$user['id'];

require_once INCLUDES_PATH . '/Support/SupportTicketRepository.php';
require_once INCLUDES_PATH . '/Support/SupportMessageRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentService.php';
require_once INCLUDES_PATH . '/Support/SupportTicketService.php';

$service    = new SupportTicketService();
$ticketRepo = $service->getTicketRepo();
$msgRepo    = $service->getMsgRepo();
$attRepo    = $service->getAttachmentRepo();

$ticketId = max(0, (int)($_GET['id'] ?? 0));
if ($ticketId === 0) {
    header('Location: ' . u('/admin/tickets'));
    exit;
}

$ticket = $ticketRepo->findById($ticketId);
if (!$ticket) {
    http_response_code(404);
    include PAGES_PATH . '/404.php';
    exit;
}

$formErrors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $formErrors[] = 'Token de seguridad inválido.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {

            case 'reply':
                $isInternal = isset($_POST['is_internal']);
                $result = $service->addReply(
                    $ticketId,
                    $userId,
                    trim($_POST['body'] ?? ''),
                    true,
                    $isInternal,
                    $_FILES
                );
                if ($result['success']) {
                    header('Location: ' . u('/admin/ticket') . '?id=' . $ticketId . '#mensajes');
                    exit;
                }
                $formErrors = $result['errors'];
                break;

            case 'change_status':
                $r = $service->changeStatus($ticketId, $_POST['status'] ?? '', $userId);
                if ($r['success']) {
                    header('Location: ' . u('/admin/ticket') . '?id=' . $ticketId);
                    exit;
                }
                $formErrors = $r['errors'];
                break;

            case 'change_priority':
                $r = $service->changePriority($ticketId, $_POST['priority'] ?? '', $userId);
                if ($r['success']) {
                    header('Location: ' . u('/admin/ticket') . '?id=' . $ticketId);
                    exit;
                }
                $formErrors = $r['errors'];
                break;

            case 'assign':
                $adminId = (int)($_POST['admin_id'] ?? 0);
                $r = $service->assign($ticketId, $adminId > 0 ? $adminId : null, $userId);
                if ($r['success']) {
                    header('Location: ' . u('/admin/ticket') . '?id=' . $ticketId);
                    exit;
                }
                $formErrors = $r['errors'];
                break;
        }
    }

    $ticket = $ticketRepo->findById($ticketId);
}

$messages  = $msgRepo->findByTicket($ticketId, true); // incluye internas
$attsByMsg = [];
foreach ($messages as $msg) {
    $attsByMsg[(int)$msg['id']] = $attRepo->findByMessage((int)$msg['id']);
}

$db     = Database::getInstance();
$admins = $db->fetchAll("SELECT id, username FROM users WHERE role = 'admin' ORDER BY username");

$actionLog = $db->fetchAll(
    "SELECT tal.action, tal.detail, tal.ip_address, tal.created_at,
            u.username AS actor
     FROM ticket_action_log tal
     LEFT JOIN users u ON u.id = tal.user_id
     WHERE tal.ticket_id = ?
     ORDER BY tal.created_at DESC
     LIMIT 30",
    [$ticketId]
);

$page_title = 'Ticket #' . $ticketId . ' | Admin | Latin Shop';
$extra_css  = ['admin.css', 'support.css'];
?>

<div class="admin-layout">
<?php include INCLUDES_PATH . '/header.php'; ?>
<?php include INCLUDES_PATH . '/admin-sidebar.php'; ?>

<main class="admin-content">

    <div class="admin-topbar">
        <div>
            <a href="<?= u('/admin/tickets') ?>" class="admin-back-link">← Todos los tickets</a>
            <h1 class="admin-page-title">
                Ticket #<?= (int)$ticketId ?>
            </h1>
            <p class="admin-page-sub">
                <?= htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <div class="admin-topbar__badges">
            <?= SupportTicketService::statusBadge($ticket['status']) ?>
            <?= SupportTicketService::priorityBadge($ticket['priority']) ?>
        </div>
    </div>

    <?php if (!empty($formErrors)): ?>
        <div class="support-alert support-alert--error">
            <?php foreach ($formErrors as $e): ?>
                <p><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="admin-ticket-grid">

        <div class="admin-ticket-main" id="mensajes">

            <div class="admin-ticket-meta-bar">
                <span>
                    👤 <strong><?= htmlspecialchars($ticket['owner_username'], ENT_QUOTES, 'UTF-8') ?></strong>
                </span>
                <span>
                    🗂 <?= htmlspecialchars(SupportTicketService::categoryLabel($ticket['category']), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span>
                    📅 <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                </span>
                <?php if (!empty($ticket['assigned_username'])): ?>
                    <span>
                        🔧 Asignado a <strong><?= htmlspecialchars($ticket['assigned_username'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                <?php endif; ?>
            </div>

            <div class="support-thread">
                <?php foreach ($messages as $msg): ?>
                    <?php
                    $isInternal = (bool)$msg['is_internal'];
                    $isStaff    = $msg['author_role'] === 'admin';
                    $msgAtts    = $attsByMsg[(int)$msg['id']] ?? [];
                    ?>

                    <div class="support-message <?= $isStaff ? 'support-message--other' : 'support-message--mine' ?>
                                                <?= $isInternal ? 'support-message--internal' : '' ?>"
                         id="msg-<?= (int)$msg['id'] ?>">

                        <div class="support-message__avatar">
                            <img src="<?= avatar_url($msg['author_avatar'] ?? null) ?>"
                                 alt="<?= htmlspecialchars($msg['author_username'], ENT_QUOTES, 'UTF-8') ?>"
                                 width="38" height="38" loading="lazy">
                        </div>

                        <div class="support-message__content">
                            <div class="support-message__header">
                                <span class="support-message__author">
                                    <?= htmlspecialchars($msg['author_username'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($isStaff): ?><span class="support-staff-tag">Staff</span><?php endif; ?>
                                    <?php if ($isInternal): ?><span class="support-internal-tag">🔒 Nota interna</span><?php endif; ?>
                                </span>
                                <span class="support-message__date">
                                    <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
                                </span>
                            </div>

                            <div class="support-message__body">
                                <?= nl2br(htmlspecialchars($msg['body'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>

                            <?php if (!empty($msgAtts)): ?>
                                <div class="support-message__attachments">
                                    <?php foreach ($msgAtts as $att): ?>
                                        <a href="<?= u('/support/attachment') ?>?id=<?= (int)$att['id'] ?>"
                                           class="support-attachment-chip">
                                            📎 <?= htmlspecialchars($att['original_name'], ENT_QUOTES, 'UTF-8') ?>
                                            <span class="support-attachment-chip__size">
                                                (<?= SupportTicketService::formatBytes((int)$att['size_bytes']) ?>)
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div><!-- /.support-thread -->

            <?php if ($ticket['status'] !== 'closed'): ?>
            <div class="support-reply-form">
                <h3 class="support-reply-form__title">Responder como staff</h3>

                <form method="POST"
                      action="<?= u('/admin/ticket') ?>?id=<?= $ticketId ?>#mensajes"
                      enctype="multipart/form-data"
                      class="support-form">

                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reply">

                    <div class="support-form__group">
                        <textarea name="body" rows="6"
                                  placeholder="Escribe la respuesta al usuario..."
                                  required></textarea>
                    </div>

                    <div class="support-form__group">
                        <label for="af-attachments">Adjuntar archivos</label>
                        <input type="file" id="af-attachments" name="attachments[]"
                               multiple accept=".jpg,.jpeg,.png,.pdf,.zip">
                    </div>

                    <div class="support-form__group support-form__group--inline">
                        <label class="support-checkbox-label">
                            <input type="checkbox" name="is_internal" value="1">
                            Nota interna (solo visible para admins)
                        </label>
                    </div>

                    <div class="support-form__actions">
                        <button type="submit" class="support-btn support-btn--primary">
                            Enviar respuesta
                        </button>
                    </div>
                </form>
            </div>
            <?php else: ?>
                <div class="support-alert support-alert--muted">
                    Ticket cerrado el <?= date('d/m/Y H:i', strtotime($ticket['closed_at'] ?? 'now')) ?>.
                </div>
            <?php endif; ?>

        </div>

        <aside class="admin-ticket-sidebar">

            <div class="admin-ticket-panel">
                <h4 class="admin-ticket-panel__title">Cambiar estado</h4>
                <form method="POST" action="<?= u('/admin/ticket') ?>?id=<?= $ticketId ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_status">
                    <select name="status" class="support-filter-select" style="width:100%;margin-bottom:.6rem">
                        <?php foreach (['open','pending','answered','closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>>
                                <?= ucfirst($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="support-btn support-btn--sm support-btn--primary"
                            style="width:100%">
                        Guardar estado
                    </button>
                </form>
            </div>

            <div class="admin-ticket-panel">
                <h4 class="admin-ticket-panel__title">Cambiar prioridad</h4>
                <form method="POST" action="<?= u('/admin/ticket') ?>?id=<?= $ticketId ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_priority">
                    <select name="priority" class="support-filter-select" style="width:100%;margin-bottom:.6rem">
                        <?php foreach (['low','medium','high','urgent'] as $p): ?>
                            <option value="<?= $p ?>" <?= $ticket['priority'] === $p ? 'selected' : '' ?>>
                                <?= match($p) {
                                    'low'    => 'Baja',
                                    'medium' => 'Media',
                                    'high'   => 'Alta',
                                    'urgent' => 'Urgente',
                                } ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="support-btn support-btn--sm support-btn--ghost"
                            style="width:100%">
                        Guardar prioridad
                    </button>
                </form>
            </div>

            <div class="admin-ticket-panel">
                <h4 class="admin-ticket-panel__title">Asignar responsable</h4>
                <form method="POST" action="<?= u('/admin/ticket') ?>?id=<?= $ticketId ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="assign">
                    <select name="admin_id" class="support-filter-select" style="width:100%;margin-bottom:.6rem">
                        <option value="0">— Sin asignar —</option>
                        <?php foreach ($admins as $a): ?>
                            <option value="<?= (int)$a['id'] ?>"
                                <?= (int)$ticket['assigned_to'] === (int)$a['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['username'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="support-btn support-btn--sm support-btn--ghost"
                            style="width:100%">
                        Asignar
                    </button>
                </form>
            </div>

            <div class="admin-ticket-panel">
                <h4 class="admin-ticket-panel__title">Acciones rápidas</h4>
                <?php if ($ticket['status'] !== 'closed'): ?>
                    <form method="POST" action="<?= u('/admin/ticket') ?>?id=<?= $ticketId ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" name="status" value="closed">
                        <button type="submit"
                                class="support-btn support-btn--danger support-btn--sm"
                                style="width:100%"
                                onclick="return confirm('¿Cerrar este ticket?')">
                            Cerrar ticket
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="<?= u('/admin/ticket') ?>?id=<?= $ticketId ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" name="status" value="open">
                        <button type="submit" class="support-btn support-btn--sm support-btn--ghost"
                                style="width:100%">
                            Reabrir ticket
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (!empty($actionLog)): ?>
            <div class="admin-ticket-panel">
                <h4 class="admin-ticket-panel__title">Historial de acciones</h4>
                <div class="support-action-log">
                    <?php foreach ($actionLog as $log): ?>
                        <div class="support-action-log__item">
                            <span class="support-action-log__action">
                                <?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="support-action-log__actor">
                                <?= htmlspecialchars($log['actor'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="support-action-log__date">
                                <?= date('d/m H:i', strtotime($log['created_at'])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </aside>

    </div><!-- /.admin-ticket-grid -->

</main>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
