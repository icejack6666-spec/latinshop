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

$filterStatus   = in_array($_GET['status']   ?? '', ['', 'open', 'pending', 'answered', 'closed'], true)
    ? ($_GET['status'] ?? '') : '';
$filterPriority = in_array($_GET['priority'] ?? '', ['', 'low', 'medium', 'high', 'urgent'], true)
    ? ($_GET['priority'] ?? '') : '';
$filterCategory = in_array($_GET['category'] ?? '', ['', 'technical', 'billing', 'account', 'other'], true)
    ? ($_GET['category'] ?? '') : '';
$search         = trim(strip_tags($_GET['q'] ?? ''));
$search         = mb_substr($search, 0, 100);
$page           = max(1, (int)($_GET['p'] ?? 1));
$perPage        = 20;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {

    $action   = $_POST['action']    ?? '';
    $targetId = (int)($_POST['ticket_id'] ?? 0);

    if ($targetId > 0) {
        switch ($action) {
            case 'change_status':
                $service->changeStatus($targetId, $_POST['status'] ?? '', $userId);
                break;
            case 'change_priority':
                $service->changePriority($targetId, $_POST['priority'] ?? '', $userId);
                break;
            case 'assign':
                $adminId = (int)($_POST['admin_id'] ?? 0);
                $service->assign($targetId, $adminId > 0 ? $adminId : null, $userId);
                break;
        }
    }

    header('Location: ' . u('/admin/tickets') . '?' . http_build_query([
        'status'   => $filterStatus,
        'priority' => $filterPriority,
        'category' => $filterCategory,
        'q'        => $search,
        'p'        => $page,
    ]));
    exit;
}

$data    = $ticketRepo->findAll($page, $perPage, $filterStatus, $filterPriority, $filterCategory, $search);
$items   = $data['items'];
$total   = $data['total'];
$pages   = (int)ceil($total / $perPage);
$stats   = $ticketRepo->getAdminStats();

$db     = Database::getInstance();
$admins = $db->fetchAll("SELECT id, username FROM users WHERE role = 'admin' ORDER BY username");

$page_title = 'Tickets de Soporte | Admin | Latin Shop';
$extra_css  = ['admin.css', 'support.css'];
?>

<div class="admin-layout">
<?php include INCLUDES_PATH . '/header.php'; ?>
<?php include INCLUDES_PATH . '/admin-sidebar.php'; ?>

<main class="admin-content">

    <div class="admin-topbar">
        <div>
            <h1 class="admin-page-title">Tickets de Soporte</h1>
            <p class="admin-page-sub">
                Gestiona todas las solicitudes de soporte de los usuarios.
            </p>
        </div>
    </div>

    <div class="admin-stats-grid" style="margin-bottom: var(--tbt-s3)">

        <div class="admin-stat-card <?= ($stats['open_count'] ?? 0) > 0 ? 'alert' : '' ?>">
            <div class="admin-stat-icon">🟢</div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= (int)($stats['open_count'] ?? 0) ?></span>
                <span class="admin-stat-label">Abiertos</span>
            </div>
        </div>

        <div class="admin-stat-card <?= ($stats['pending_count'] ?? 0) > 0 ? 'alert' : '' ?>">
            <div class="admin-stat-icon">🟡</div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= (int)($stats['pending_count'] ?? 0) ?></span>
                <span class="admin-stat-label">Pendientes</span>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">🔵</div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= (int)($stats['answered_count'] ?? 0) ?></span>
                <span class="admin-stat-label">Respondidos</span>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon">⚫</div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= (int)($stats['closed_count'] ?? 0) ?></span>
                <span class="admin-stat-label">Cerrados</span>
            </div>
        </div>

        <?php if (($stats['urgent_open'] ?? 0) > 0): ?>
        <div class="admin-stat-card alert">
            <div class="admin-stat-icon">🔴</div>
            <div class="admin-stat-info">
                <span class="admin-stat-number"><?= (int)$stats['urgent_open'] ?></span>
                <span class="admin-stat-label">Urgentes abiertos</span>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <form method="GET" action="<?= u('/admin/tickets') ?>" class="support-filters">

        <input type="text"
               name="q"
               placeholder="Buscar por asunto o usuario..."
               value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
               class="support-filter-input">

        <select name="status" class="support-filter-select">
            <option value="">Todos los estados</option>
            <option value="open"     <?= $filterStatus === 'open'     ? 'selected' : '' ?>>Abierto</option>
            <option value="pending"  <?= $filterStatus === 'pending'  ? 'selected' : '' ?>>Pendiente</option>
            <option value="answered" <?= $filterStatus === 'answered' ? 'selected' : '' ?>>Respondido</option>
            <option value="closed"   <?= $filterStatus === 'closed'   ? 'selected' : '' ?>>Cerrado</option>
        </select>

        <select name="priority" class="support-filter-select">
            <option value="">Todas las prioridades</option>
            <option value="urgent" <?= $filterPriority === 'urgent' ? 'selected' : '' ?>>Urgente</option>
            <option value="high"   <?= $filterPriority === 'high'   ? 'selected' : '' ?>>Alta</option>
            <option value="medium" <?= $filterPriority === 'medium' ? 'selected' : '' ?>>Media</option>
            <option value="low"    <?= $filterPriority === 'low'    ? 'selected' : '' ?>>Baja</option>
        </select>

        <select name="category" class="support-filter-select">
            <option value="">Todas las categorías</option>
            <option value="technical" <?= $filterCategory === 'technical' ? 'selected' : '' ?>>Técnico</option>
            <option value="billing"   <?= $filterCategory === 'billing'   ? 'selected' : '' ?>>Facturación</option>
            <option value="account"   <?= $filterCategory === 'account'   ? 'selected' : '' ?>>Cuenta</option>
            <option value="other"     <?= $filterCategory === 'other'     ? 'selected' : '' ?>>Otro</option>
        </select>

        <button type="submit" class="support-btn support-btn--primary support-btn--sm">
            Filtrar
        </button>

        <?php if ($filterStatus || $filterPriority || $filterCategory || $search): ?>
            <a href="<?= u('/admin/tickets') ?>" class="support-btn support-btn--ghost support-btn--sm">
                Limpiar
            </a>
        <?php endif; ?>

    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Asunto</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Categoría</th>
                    <th>Asignado</th>
                    <th>Actualizado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:2rem;color:var(--tbt-txt-sub)">
                        No se encontraron tickets con los filtros actuales.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $t): ?>
                    <tr class="<?= $t['priority'] === 'urgent' && $t['status'] !== 'closed' ? 'row--urgent' : '' ?>">
                        <td>
                            <a href="<?= u('/admin/ticket') ?>?id=<?= (int)$t['id'] ?>"
                               class="admin-link">#<?= (int)$t['id'] ?></a>
                        </td>
                        <td>
                            <a href="<?= u('/admin/ticket') ?>?id=<?= (int)$t['id'] ?>"
                               class="admin-link admin-link--subject"
                               title="<?= htmlspecialchars($t['subject'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(mb_substr($t['subject'], 0, 60), ENT_QUOTES, 'UTF-8') ?>
                                <?= mb_strlen($t['subject']) > 60 ? '…' : '' ?>
                            </a>
                            <span class="admin-msg-count"><?= (int)$t['message_count'] ?> msg</span>
                        </td>
                        <td>
                            <span class="admin-username">
                                <?= htmlspecialchars($t['owner_username'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= SupportTicketService::statusBadge($t['status']) ?></td>
                        <td><?= SupportTicketService::priorityBadge($t['priority']) ?></td>
                        <td>
                            <span class="support-cat-tag">
                                <?= htmlspecialchars(SupportTicketService::categoryLabel($t['category']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <span class="admin-assigned">
                                <?= !empty($t['assigned_username'])
                                    ? htmlspecialchars($t['assigned_username'], ENT_QUOTES, 'UTF-8')
                                    : '<em style="color:var(--tbt-txt-sub)">Sin asignar</em>' ?>
                            </span>
                        </td>
                        <td class="admin-date">
                            <?= date('d/m/Y H:i', strtotime($t['updated_at'])) ?>
                        </td>
                        <td>
                            <a href="<?= u('/admin/ticket') ?>?id=<?= (int)$t['id'] ?>"
                               class="support-btn support-btn--xs">
                                Ver
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <div class="support-pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?<?= http_build_query([
                    'status'   => $filterStatus,
                    'priority' => $filterPriority,
                    'category' => $filterCategory,
                    'q'        => $search,
                    'p'        => $i,
                ]) ?>"
                   class="support-pagination__item <?= $i === $page ? 'is-active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <p style="color:var(--tbt-txt-sub);font-size:var(--tbt-text-xs);margin-top:var(--tbt-s2)">
        Total: <?= number_format($total) ?> ticket<?= $total !== 1 ? 's' : '' ?>
    </p>

</main>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
