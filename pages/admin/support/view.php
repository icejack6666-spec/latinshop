<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole('client');
$user    = $auth->getUser();
$userId  = (int)$user['id'];
$isAdmin = $user['role'] === 'admin';

require_once INCLUDES_PATH . '/Support/SupportTicketRepository.php';
require_once INCLUDES_PATH . '/Support/SupportMessageRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentService.php';
require_once INCLUDES_PATH . '/Support/SupportTicketService.php';

$service    = new SupportTicketService();
$attRepo    = $service->getAttachmentRepo();
$ticketRepo = $service->getTicketRepo();

$ticketId = max(0, (int)($_GET['id'] ?? 0));
if ($ticketId === 0) {
    header('Location: ' . u('/support'));
    exit;
}

$ticket = $ticketRepo->findById($ticketId);

if (!$ticket || (!$isAdmin && (int)$ticket['user_id'] !== $userId)) {
    http_response_code(403);
    include PAGES_PATH . '/404.php';
    exit;
}

$formErrors  = [];
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $formErrors[] = 'Token de seguridad inválido.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {

            case 'reply':
                $result = $service->addReply(
                    $ticketId,
                    $userId,
                    trim($_POST['body'] ?? ''),
                    $isAdmin,
                    false,
                    $_FILES
                );
                if ($result['success']) {
                    $formSuccess = 'Respuesta enviada.';
                    // Recarga para limpiar POST
                    header('Location: ' . u('/support/ver') . '?id=' . $ticketId . '#mensajes');
                    exit;
                }
                $formErrors = $result['errors'];
                break;

            case 'close':
                if (!$isAdmin && (int)$ticket['user_id'] !== $userId) {
                    $formErrors[] = 'Acceso denegado.';
                    break;
                }
                $r = $service->changeStatus($ticketId, 'closed', $userId);
                if ($r['success']) {
                    header('Location: ' . u('/support/ver') . '?id=' . $ticketId . '&closed=1');
                    exit;
                }
                $formErrors = $r['errors'];
                break;

            case 'reopen':
                if (!$isAdmin && (int)$ticket['user_id'] !== $userId) {
                    $formErrors[] = 'Acceso denegado.';
                    break;
                }
                $r = $service->changeStatus($ticketId, 'open', $userId);
                if ($r['success']) {
                    header('Location: ' . u('/support/ver') . '?id=' . $ticketId);
                    exit;
                }
                $formErrors = $r['errors'];
                break;
        }
    }

    $ticket = $ticketRepo->findById($ticketId);
}

$msgRepo  = $service->getMsgRepo();
$messages = $msgRepo->findByTicket($ticketId, $isAdmin);

// Adjuntos por mensaje
$attsByMsg = [];
foreach ($messages as $msg) {
    $attsByMsg[(int)$msg['id']] = $attRepo->findByMessage((int)$msg['id']);
}

$page_title = 'Ticket #' . $ticketId . ' | Soporte | Latin Shop';
$extra_css  = ['support.css'];

include INCLUDES_PATH . '/header.php';

$isClosed = $ticket['status'] === 'closed';
?>

<div class="support-layout support-layout--detail">

    <aside class="support-sidebar">
        <div class="support-sidebar__header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
            </svg>
            <span>Soporte</span>
        </div>

        <a href="<?= u('/support') ?>" class="support-sidebar__link">
            ← Mis Tickets
        </a>

        <div class="support-sidebar__divider"></div>

        <div class="support-sidebar__info">

            <div class="support-sidebar__info-row">
                <span class="support-sidebar__info-label">Estado</span>
                <?= SupportTicketService::statusBadge($ticket['status']) ?>
            </div>

            <div class="support-sidebar__info-row">
                <span class="support-sidebar__info-label">Prioridad</span>
                <?= SupportTicketService::priorityBadge($ticket['priority']) ?>
            </div>

            <div class="support-sidebar__info-row">
                <span class="support-sidebar__info-label">Categoría</span>
                <span class="support-sidebar__info-value">
                    <?= htmlspecialchars(SupportTicketService::categoryLabel($ticket['category']), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <?php if (!empty($ticket['assigned_username'])): ?>
            <div class="support-sidebar__info-row">
                <span class="support-sidebar__info-label">Asignado a</span>
                <span class="support-sidebar__info-value">
                    <?= htmlspecialchars($ticket['assigned_username'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="support-sidebar__info-row">
                <span class="support-sidebar__info-label">Creado</span>
                <span class="support-sidebar__info-value">
                    <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                </span>
            </div>

            <?php if ($ticket['closed_at']): ?>
            <div class="support-sidebar__info-row">
                <span class="support-sidebar__info-label">Cerrado</span>
                <span class="support-sidebar__info-value">
                    <?= date('d/m/Y H:i', strtotime($ticket['closed_at'])) ?>
                </span>
            </div>
            <?php endif; ?>

        </div>

        <?php if (!$isClosed): ?>
        <form method="POST" action="" style="margin-top:auto">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="close">
            <button type="submit"
                    class="support-btn support-btn--danger support-btn--sm"
                    onclick="return confirm('¿Cerrar este ticket?')">
                Cerrar ticket
            </button>
        </form>
        <?php else: ?>
        <form method="POST" action="" style="margin-top:auto">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reopen">
            <button type="submit" class="support-btn support-btn--ghost support-btn--sm">
                Reabrir ticket
            </button>
        </form>
        <?php endif; ?>

    </aside>

    <main class="support-main" id="mensajes">

        <div class="support-topbar">
            <div>
                <p class="support-ticket-ref">Ticket #<?= (int)$ticketId ?></p>
                <h1 class="support-page-title">
                    <?= htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') ?>
                </h1>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (!empty($formErrors)): ?>
            <div class="support-alert support-alert--error">
                <?php foreach ($formErrors as $e): ?>
                    <p><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['closed'])): ?>
            <div class="support-alert support-alert--success">
                El ticket ha sido cerrado. Si necesitas más ayuda, puedes reabrirlo.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['att_warn'])): ?>
            <div class="support-alert support-alert--warning">
                Ticket creado, pero hubo errores con algunos archivos adjuntos.
            </div>
        <?php endif; ?>

        <div class="support-thread">

            <?php foreach ($messages as $msg): ?>
                <?php
                $isOwn     = (int)$msg['user_id'] === $userId;
                $isInternal = (bool)$msg['is_internal'];
                $msgAtts   = $attsByMsg[(int)$msg['id']] ?? [];
                $isStaff   = $msg['author_role'] === 'admin';
                ?>

                <div class="support-message <?= $isOwn ? 'support-message--mine' : 'support-message--other' ?>
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
                                <?php if ($isStaff): ?>
                                    <span class="support-staff-tag">Staff</span>
                                <?php endif; ?>
                                <?php if ($isInternal): ?>
                                    <span class="support-internal-tag">Nota interna</span>
                                <?php endif; ?>
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
                                       class="support-attachment-chip"
                                       title="Descargar <?= htmlspecialchars($att['original_name'], ENT_QUOTES, 'UTF-8') ?>">
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

        <?php if (!$isClosed): ?>
            <div class="support-reply-form">
                <h3 class="support-reply-form__title">Tu respuesta</h3>

                <form method="POST"
                      action="<?= u('/support/ver') ?>?id=<?= $ticketId ?>#mensajes"
                      enctype="multipart/form-data"
                      class="support-form">

                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reply">

                    <div class="support-form__group">
                        <textarea name="body"
                                  rows="5"
                                  placeholder="Escribe tu respuesta..."
                                  required></textarea>
                    </div>

                    <div class="support-form__group">
                        <label for="rf-attachments">
                            Adjuntar archivos
                            <span class="support-form__hint">(JPG, PNG, PDF, ZIP — máx. 5 MB c/u)</span>
                        </label>
                        <input type="file"
                               id="rf-attachments"
                               name="attachments[]"
                               multiple
                               accept=".jpg,.jpeg,.png,.pdf,.zip">
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
                Este ticket está cerrado. Puedes reabrirlo si necesitas más ayuda.
            </div>
        <?php endif; ?>

    </main>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
