<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole('client');   // client o admin
$user = $auth->getUser();
$userId = (int) $user['id'];

require_once INCLUDES_PATH . '/Support/SupportTicketRepository.php';
require_once INCLUDES_PATH . '/Support/SupportMessageRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentService.php';
require_once INCLUDES_PATH . '/Support/SupportTicketService.php';

$service = new SupportTicketService();

$formErrors  = [];
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $formErrors[] = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $result = $service->createTicket($_POST, $userId, $_FILES);

        if ($result['success']) {
            $ticketId = $result['ticket_id'];
            $qs       = !empty($result['att_errors']) ? '&att_warn=1' : '';
            header('Location: ' . u('/support/ver') . '?id=' . $ticketId . $qs);
            exit;
        }

        $formErrors = $result['errors'];
    }
}

$filterStatus = in_array($_GET['status'] ?? '', ['', 'open', 'pending', 'answered', 'closed'], true)
    ? ($_GET['status'] ?? '')
    : '';

$page    = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;

$data  = $service->getTicketRepo()->findByUser($userId, $page, $perPage, $filterStatus);
$items = $data['items'];
$total = $data['total'];
$pages = (int)ceil($total / $perPage);

$page_title = 'Soporte | Latin Shop';
$extra_css  = ['support.css'];

include INCLUDES_PATH . '/header.php';
?>

<div class="support-layout">

    <aside class="support-sidebar">
        <div class="support-sidebar__header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
            </svg>
            <span>Soporte</span>
        </div>
        <a href="<?= u('/support') ?>"
           class="support-sidebar__link <?= $filterStatus === '' ? 'is-active' : '' ?>">
            Todos mis tickets
        </a>
        <a href="<?= u('/support') ?>?status=open"
           class="support-sidebar__link <?= $filterStatus === 'open' ? 'is-active' : '' ?>">
            Abiertos
        </a>
        <a href="<?= u('/support') ?>?status=pending"
           class="support-sidebar__link <?= $filterStatus === 'pending' ? 'is-active' : '' ?>">
            Pendientes
        </a>
        <a href="<?= u('/support') ?>?status=answered"
           class="support-sidebar__link <?= $filterStatus === 'answered' ? 'is-active' : '' ?>">
            Respondidos
        </a>
        <a href="<?= u('/support') ?>?status=closed"
           class="support-sidebar__link <?= $filterStatus === 'closed' ? 'is-active' : '' ?>">
            Cerrados
        </a>

        <div class="support-sidebar__divider"></div>

        <button class="support-btn support-btn--primary support-btn--sm"
                id="js-open-modal">
            + Nuevo ticket
        </button>
    </aside>

    <main class="support-main">

        <div class="support-topbar">
            <h1 class="support-page-title">Mis Tickets de Soporte</h1>
        </div>

        <?php if (empty($items)): ?>
            <div class="support-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor" opacity=".25">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
                </svg>
                <p>No tienes tickets<?= $filterStatus !== '' ? ' con ese estado' : '' ?>.</p>
                <button class="support-btn support-btn--primary" id="js-open-modal2">
                    Crear mi primer ticket
                </button>
            </div>
        <?php else: ?>

            <div class="support-ticket-list">
                <?php foreach ($items as $t): ?>
                    <a href="<?= u('/support/ver') ?>?id=<?= (int)$t['id'] ?>"
                       class="support-ticket-card">

                        <div class="support-ticket-card__meta">
                            <?= SupportTicketService::statusBadge($t['status']) ?>
                            <?= SupportTicketService::priorityBadge($t['priority']) ?>
                            <span class="support-ticket-card__cat">
                                <?= htmlspecialchars(SupportTicketService::categoryLabel($t['category']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <h3 class="support-ticket-card__subject">
                            #<?= (int)$t['id'] ?> — <?= htmlspecialchars($t['subject'], ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <div class="support-ticket-card__footer">
                            <span><?= (int)$t['message_count'] ?> mensaje<?= $t['message_count'] != 1 ? 's' : '' ?></span>
                            <span><?= date('d/m/Y H:i', strtotime($t['updated_at'])) ?></span>
                        </div>

                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
                <div class="support-pagination">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <a href="?<?= http_build_query(['status' => $filterStatus, 'p' => $i]) ?>"
                           class="support-pagination__item <?= $i === $page ? 'is-active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </main>

</div><!-- /.support-layout -->


<div class="support-modal" id="js-modal-create" role="dialog" aria-modal="true"
     aria-labelledby="modal-title" style="display:none">
    <div class="support-modal__backdrop" id="js-modal-backdrop"></div>
    <div class="support-modal__box">

        <div class="support-modal__header">
            <h2 id="modal-title">Nuevo Ticket de Soporte</h2>
            <button class="support-modal__close" id="js-modal-close" aria-label="Cerrar">✕</button>
        </div>

        <?php if (!empty($formErrors)): ?>
            <div class="support-alert support-alert--error">
                <ul>
                    <?php foreach ($formErrors as $e): ?>
                        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST"
              action="<?= u('/support') ?>"
              enctype="multipart/form-data"
              class="support-form"
              id="js-create-form">

            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_ticket">

            <div class="support-form__group">
                <label for="f-subject">Asunto <span class="required">*</span></label>
                <input type="text"
                       id="f-subject"
                       name="subject"
                       maxlength="200"
                       placeholder="Describe brevemente tu problema"
                       value="<?= htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       required>
            </div>

            <div class="support-form__row">
                <div class="support-form__group">
                    <label for="f-category">Categoría <span class="required">*</span></label>
                    <select id="f-category" name="category" required>
                        <option value="">— Seleccionar —</option>
                        <option value="technical" <?= ($_POST['category'] ?? '') === 'technical' ? 'selected' : '' ?>>Técnico</option>
                        <option value="billing"   <?= ($_POST['category'] ?? '') === 'billing'   ? 'selected' : '' ?>>Facturación</option>
                        <option value="account"   <?= ($_POST['category'] ?? '') === 'account'   ? 'selected' : '' ?>>Cuenta</option>
                        <option value="other"     <?= ($_POST['category'] ?? '') === 'other'     ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>

                <div class="support-form__group">
                    <label for="f-priority">Prioridad</label>
                    <select id="f-priority" name="priority">
                        <option value="low"    <?= ($_POST['priority'] ?? '') === 'low'    ? 'selected' : '' ?>>Baja</option>
                        <option value="medium" <?= ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Media</option>
                        <option value="high"   <?= ($_POST['priority'] ?? '') === 'high'   ? 'selected' : '' ?>>Alta</option>
                        <option value="urgent" <?= ($_POST['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgente</option>
                    </select>
                </div>
            </div>

            <div class="support-form__group">
                <label for="f-body">Descripción detallada <span class="required">*</span></label>
                <textarea id="f-body"
                          name="body"
                          rows="7"
                          placeholder="Describe el problema con el mayor detalle posible..."
                          required><?= htmlspecialchars($_POST['body'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="support-form__group">
                <label for="f-attachments">Adjuntos
                    <span class="support-form__hint">(JPG, PNG, PDF, ZIP — máx. 5 MB c/u)</span>
                </label>
                <input type="file"
                       id="f-attachments"
                       name="attachments[]"
                       multiple
                       accept=".jpg,.jpeg,.png,.pdf,.zip">
                <div class="support-file-preview" id="js-file-preview"></div>
            </div>

            <div class="support-form__actions">
                <button type="button" class="support-btn support-btn--ghost" id="js-modal-cancel">
                    Cancelar
                </button>
                <button type="submit" class="support-btn support-btn--primary">
                    Enviar ticket
                </button>
            </div>

        </form>
    </div>
</div>

<script nonce="<?= generate_csp_nonce() ?>">
(function () {
    'use strict';

    const modal      = document.getElementById('js-modal-create');
    const backdrop   = document.getElementById('js-modal-backdrop');
    const btnOpen    = document.getElementById('js-open-modal');
    const btnOpen2   = document.getElementById('js-open-modal2');
    const btnClose   = document.getElementById('js-modal-close');
    const btnCancel  = document.getElementById('js-modal-cancel');
    const fileInput  = document.getElementById('f-attachments');
    const filePreview = document.getElementById('js-file-preview');

    function openModal() {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (btnOpen)  btnOpen.addEventListener('click', openModal);
    if (btnOpen2) btnOpen2.addEventListener('click', openModal);
    if (btnClose)  btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);
    if (backdrop)  backdrop.addEventListener('click', closeModal);

    // Abrir modal automáticamente si hay errores del formulario
    <?php if (!empty($formErrors)): ?>
    openModal();
    <?php endif; ?>

    if (fileInput && filePreview) {
        fileInput.addEventListener('change', function () {
            filePreview.innerHTML = '';
            Array.from(this.files).forEach(function (f) {
                const chip = document.createElement('span');
                chip.className = 'support-file-chip';
                chip.textContent = f.name + ' (' + (f.size > 1048576
                    ? (f.size / 1048576).toFixed(1) + ' MB'
                    : (f.size / 1024).toFixed(0) + ' KB') + ')';
                filePreview.appendChild(chip);
            });
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });
}());
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
