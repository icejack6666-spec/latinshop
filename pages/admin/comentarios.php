<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole('admin');

$db       = Database::getInstance();
$comments = Comments::getInstance();
$usuario  = $auth->getUser();

$msg_ok  = null;
$msg_err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg_err = 'Token de seguridad inválido.';

    } else {
        $accion     = $_POST['accion']     ?? '';
        $comment_id = (int)($_POST['comment_id'] ?? 0);
        $admin_note = sanitize_string($_POST['admin_note'] ?? '', 255);

        switch ($accion) {
            case 'aprobar':
                $ok = $comments->approve($comment_id, $admin_note);
                $msg_ok  = $ok ? 'Comentario aprobado.' : null;
                $msg_err = !$ok ? 'No se pudo aprobar el comentario.' : null;
                break;

            case 'rechazar':
                $ok = $comments->reject($comment_id, $admin_note);
                $msg_ok  = $ok ? 'Comentario rechazado.' : null;
                $msg_err = !$ok ? 'No se pudo rechazar el comentario.' : null;
                break;

            case 'eliminar':
                $ok = $comments->delete($comment_id);
                $msg_ok  = $ok ? 'Comentario eliminado permanentemente.' : null;
                $msg_err = !$ok ? 'No se pudo eliminar el comentario.' : null;
                break;

            default:
                $msg_err = 'Acción no válida.';
        }
    }
}

$filtro = $_GET['status'] ?? 'pending';
$filtros_validos = ['pending', 'approved', 'rejected', 'todos'];
if (!in_array($filtro, $filtros_validos, true)) $filtro = 'pending';

$por_pagina    = 20;
$pagina        = max(1, (int)($_GET['pagina'] ?? 1));
$offset        = ($pagina - 1) * $por_pagina;
$status_query  = $filtro !== 'todos' ? $filtro : '';

$total        = $comments->countAll($status_query);
$total_pags   = (int)ceil($total / $por_pagina);
$lista        = $comments->getAll($por_pagina, $offset, $status_query);

$conteos = [
    'pending'  => $comments->countAll('pending'),
    'approved' => $comments->countAll('approved'),
    'rejected' => $comments->countAll('rejected'),
    'todos'    => $comments->countAll(),
];

$page_title = 'Comentarios | Panel Admin | Latin Shop';
$extra_css = ['admin.css'];
include INCLUDES_PATH . '/header.php';
?>

<div class="admin-layout">
    <?php include INCLUDES_PATH . '/admin-sidebar.php'; ?>

    <main class="admin-main">

        <!-- Topbar -->
        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Comentarios</h1>
                <p class="admin-page-sub"><?= $total ?> comentario<?= $total !== 1 ? 's' : '' ?> en esta vista</p>
            </div>
        </div>

        <?php if ($msg_ok): ?>
            <div class="admin-alert admin-alert--ok">✓ <?= htmlspecialchars($msg_ok, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($msg_err): ?>
            <div class="admin-alert admin-alert--err">✕ <?= htmlspecialchars($msg_err, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="admin-filters" style="margin-bottom: var(--tbt-s3);">
            <div class="admin-tabs">
                <?php
                $tab_labels = [
                    'pending'  => 'Pendientes',
                    'approved' => 'Aprobados',
                    'rejected' => 'Rechazados',
                    'todos'    => 'Todos',
                ];
                foreach ($tab_labels as $key => $label):
                    $activo = $filtro === $key ? 'admin-tab--active' : '';
                    $url_tab = u('/admin/comentarios') . '?status=' . $key;
                ?>
                    <a href="<?= $url_tab ?>" class="admin-tab <?= $activo ?>">
                        <?= $label ?>
                        <span class="admin-tab__count"><?= $conteos[$key] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-panel">

            <?php if (empty($lista)): ?>
                <div class="admin-empty" style="padding: var(--tbt-s5);">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor" style="color: var(--tbt-txt-dim);">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                    </svg>
                    <p>No hay comentarios en esta categoría.</p>
                </div>

            <?php else: ?>
                <div class="cmod-list">
                    <?php foreach ($lista as $c): ?>

                    <div class="cmod-item <?= $c['status'] === 'pending' ? 'cmod-item--pending' : '' ?>">

                        <div class="cmod-item__header">
                            <!-- Avatar + usuario -->
                            <div class="cmod-user">
                                <div class="admin-avatar admin-avatar--default" style="width:36px;height:36px;font-size:11px;">
                                    <?= Comments::getInitials($c['username']) ?>
                                </div>
                                <div>
                                    <span class="cmod-username">
                                        <?= htmlspecialchars($c['username'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <br>
                                    <span class="admin-table__muted" style="font-size:var(--tbt-text-2xs);">
                                        <?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="cmod-meta">
                                <span class="admin-badge admin-badge--outline">
                                    <?= htmlspecialchars(Comments::slugToLabel($c['page_slug']), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php
                                $status_badges = [
                                    'pending'  => 'admin-badge--amber',
                                    'approved' => 'admin-badge--green',
                                    'rejected' => 'admin-badge--red',
                                ];
                                $status_labels = [
                                    'pending'  => 'Pendiente',
                                    'approved' => 'Aprobado',
                                    'rejected' => 'Rechazado',
                                ];
                                ?>
                                <span class="admin-badge <?= $status_badges[$c['status']] ?? '' ?>">
                                    <?= $status_labels[$c['status']] ?? $c['status'] ?>
                                </span>
                                <span class="admin-table__mono admin-table__muted" style="font-size:var(--tbt-text-2xs);">
                                    <?= Comments::timeAgo($c['created_at']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="cmod-item__content">
                            <?= nl2br(htmlspecialchars($c['content'], ENT_QUOTES, 'UTF-8')) ?>
                        </div>

                        <?php if (!empty($c['admin_note'])): ?>
                            <div class="cmod-admin-note">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                                </svg>
                                Nota admin: <?= htmlspecialchars($c['admin_note'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <div class="cmod-item__actions">

                            <?php if ($c['status'] === 'pending'): ?>

                                <form method="POST" action="<?= u('/admin/comentarios') ?>?status=<?= $filtro ?>"
                                      class="cmod-action-form"
                                      onsubmit="return confirm('¿Aprobar este comentario?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="accion" value="aprobar">
                                    <input
                                        type="text"
                                        name="admin_note"
                                        class="cmod-note-input"
                                        placeholder="Nota opcional..."
                                        maxlength="255"
                                    >
                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--green">
                                        ✓ Aprobar
                                    </button>
                                </form>

                                <form method="POST" action="<?= u('/admin/comentarios') ?>?status=<?= $filtro ?>"
                                      class="cmod-action-form"
                                      onsubmit="return confirm('¿Rechazar este comentario?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="accion" value="rechazar">
                                    <input
                                        type="text"
                                        name="admin_note"
                                        class="cmod-note-input"
                                        placeholder="Motivo del rechazo..."
                                        maxlength="255"
                                    >
                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--red">
                                        ✕ Rechazar
                                    </button>
                                </form>

                            <?php elseif ($c['status'] === 'approved'): ?>

                                <form method="POST" action="<?= u('/admin/comentarios') ?>?status=<?= $filtro ?>"
                                      class="cmod-action-form"
                                      onsubmit="return confirm('¿Ocultar este comentario aprobado?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="accion" value="rechazar">
                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--ghost">
                                        ↩ Ocultar
                                    </button>
                                </form>

                            <?php elseif ($c['status'] === 'rejected'): ?>

                                <form method="POST" action="<?= u('/admin/comentarios') ?>?status=<?= $filtro ?>"
                                      class="cmod-action-form"
                                      onsubmit="return confirm('¿Aprobar este comentario?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="accion" value="aprobar">
                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--green">
                                        ✓ Re-aprobar
                                    </button>
                                </form>

                            <?php endif; ?>

                            <form method="POST" action="<?= u('/admin/comentarios') ?>?status=<?= $filtro ?>"
                                  onsubmit="return confirm('⚠️ ¿Eliminar permanentemente? Esta acción no se puede deshacer.')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <button type="submit" class="admin-btn admin-btn--sm admin-btn--red" style="opacity:0.6;">
                                    🗑 Eliminar
                                </button>
                            </form>

                            <a href="<?= u('/' . $c['page_slug']) ?>#comentarios"
                               target="_blank"
                               class="admin-btn admin-btn--sm admin-btn--ghost">
                               ↗ Ver página
                            </a>

                        </div><!-- /cmod-item__actions -->

                    </div><!-- /cmod-item -->

                    <?php endforeach; ?>
                </div><!-- /cmod-list -->

                <?php if ($total_pags > 1): ?>
                    <div class="admin-pagination">
                        <?php for ($p = 1; $p <= $total_pags; $p++):
                            $url_p = u('/admin/comentarios') . '?status=' . $filtro . '&pagina=' . $p;
                        ?>
                            <a href="<?= $url_p ?>"
                               class="admin-page-btn <?= $p === $pagina ? 'admin-page-btn--active' : '' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div><!-- /admin-panel -->

    </main>
</div>

<style>
.admin-alert {
    padding: var(--tbt-s2) var(--tbt-s3);
    border-radius: var(--tbt-r-md);
    font-size: var(--tbt-text-sm);
    font-weight: 500;
    margin-bottom: var(--tbt-s3);
}
.admin-alert--ok  { background: rgba(34,197,94,.08); color: #4ade80; border: 1px solid rgba(34,197,94,.2); }
.admin-alert--err { background: rgba(239,68,68,.08); color: #f87171; border: 1px solid rgba(239,68,68,.2); }

.cmod-list { display: flex; flex-direction: column; }

.cmod-item {
    padding: var(--tbt-s3);
    border-bottom: 1px solid var(--tbt-bg-3);
    transition: background var(--tbt-t1);
}
.cmod-item:last-child { border-bottom: none; }
.cmod-item:hover { background: var(--tbt-bg-2); }
.cmod-item--pending { border-left: 3px solid var(--tbt-amber); }

.cmod-item__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--tbt-s2);
    margin-bottom: var(--tbt-s2);
}

.cmod-user {
    display: flex;
    align-items: center;
    gap: var(--tbt-s1);
}
.cmod-username {
    font-size: var(--tbt-text-sm);
    font-weight: 700;
    color: var(--tbt-txt-white);
}

.cmod-meta {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.cmod-item__content {
    font-size: var(--tbt-text-sm);
    color: var(--tbt-txt-base);
    line-height: 1.65;
    background: var(--tbt-bg-3);
    border-radius: var(--tbt-r-md);
    padding: var(--tbt-s2);
    margin-bottom: var(--tbt-s2);
    word-break: break-word;
    border-left: 3px solid var(--tbt-bg-5);
}

.cmod-admin-note {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: var(--tbt-text-xs);
    color: var(--tbt-jade-light);
    background: var(--tbt-jade-08);
    border: 1px solid var(--tbt-jade-15);
    border-radius: var(--tbt-r-md);
    padding: 0.4rem var(--tbt-s2);
    margin-bottom: var(--tbt-s2);
}

.cmod-item__actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--tbt-s1);
}
.cmod-action-form {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.cmod-note-input {
    background: var(--tbt-bg-3);
    border: 1px solid var(--tbt-bg-5);
    border-radius: var(--tbt-r-md);
    color: var(--tbt-txt-base);
    font-family: var(--tbt-font-body);
    font-size: var(--tbt-text-xs);
    padding: 0.3rem 0.6rem;
    outline: none;
    width: 180px;
    transition: border-color var(--tbt-t1);
}
.cmod-note-input:focus { border-color: var(--tbt-jade-40); }
.cmod-note-input::placeholder { color: var(--tbt-txt-dim); }

.admin-tabs { display: flex; flex-wrap: wrap; gap: 4px; }
.admin-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 0.4rem 0.9rem; border-radius: var(--tbt-r-full);
    font-size: var(--tbt-text-xs); font-weight: 600;
    text-decoration: none; color: var(--tbt-txt-sub);
    background: var(--tbt-bg-2); border: 1px solid var(--tbt-bg-4);
    transition: all var(--tbt-t1);
}
.admin-tab:hover { border-color: var(--tbt-bg-5); color: var(--tbt-txt-white); }
.admin-tab--active { background: var(--tbt-jade-15); color: var(--tbt-jade-light); border-color: var(--tbt-jade-30); }
.admin-tab__count {
    background: var(--tbt-bg-3); color: var(--tbt-txt-muted);
    font-family: var(--tbt-font-mono); font-size: 10px;
    padding: 1px 6px; border-radius: var(--tbt-r-full);
}
.admin-tab--active .admin-tab__count { background: var(--tbt-jade-30); color: var(--tbt-jade-light); }
.admin-btn {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 4px; border: none; border-radius: var(--tbt-r-md);
    font-family: var(--tbt-font-display); font-weight: 600; cursor: pointer;
    text-decoration: none; transition: opacity var(--tbt-t1), transform var(--tbt-t1);
    white-space: nowrap;
}
.admin-btn:hover { opacity: 0.82; transform: translateY(-1px); }
.admin-btn--sm { font-size: var(--tbt-text-xs); padding: 0.35rem 0.75rem; }
.admin-btn--green  { background: rgba(34,197,94,.15);  color: #4ade80; border: 1px solid rgba(34,197,94,.3); }
.admin-btn--red    { background: rgba(239,68,68,.1);   color: #f87171; border: 1px solid rgba(239,68,68,.25); }
.admin-btn--ghost  { background: var(--tbt-bg-2);      color: var(--tbt-txt-sub); border: 1px solid var(--tbt-bg-5); }
.admin-badge {
    display: inline-block; font-size: var(--tbt-text-2xs); font-weight: 700;
    font-family: var(--tbt-font-mono); padding: 2px 8px;
    border-radius: var(--tbt-r-full); text-transform: uppercase;
    letter-spacing: 0.04em; white-space: nowrap;
}
.admin-badge--amber   { background: var(--tbt-amber-15); color: var(--tbt-amber); border: 1px solid var(--tbt-amber-30); }
.admin-badge--green   { background: rgba(34,197,94,.1); color: #4ade80; border: 1px solid rgba(34,197,94,.25); }
.admin-badge--red     { background: rgba(239,68,68,.1); color: #f87171; border: 1px solid rgba(239,68,68,.25); }
.admin-badge--outline { background: var(--tbt-bg-3); color: var(--tbt-txt-sub); border: 1px solid var(--tbt-bg-5); }
.admin-avatar--default {
    border-radius: 50%; background: var(--tbt-jade-15); border: 2px solid var(--tbt-jade-30);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; color: var(--tbt-jade-light); font-family: var(--tbt-font-mono); flex-shrink: 0;
}
.admin-pagination {
    display: flex; gap: 4px; justify-content: center;
    padding: var(--tbt-s2) var(--tbt-s3); border-top: 1px solid var(--tbt-bg-4);
}
.admin-page-btn {
    width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
    border-radius: var(--tbt-r-md); font-size: var(--tbt-text-xs); font-family: var(--tbt-font-mono);
    font-weight: 600; text-decoration: none; color: var(--tbt-txt-sub);
    background: var(--tbt-bg-2); border: 1px solid var(--tbt-bg-4); transition: all var(--tbt-t1);
}
.admin-page-btn:hover { border-color: var(--tbt-bg-5); color: var(--tbt-txt-white); }
.admin-page-btn--active { background: var(--tbt-jade-15); color: var(--tbt-jade-light); border-color: var(--tbt-jade-30); }
.admin-empty {
    display: flex; flex-direction: column; align-items: center;
    gap: var(--tbt-s1); color: var(--tbt-txt-muted); font-size: var(--tbt-text-sm); text-align: center;
}

@media (max-width: 640px) {
    .cmod-note-input { width: 100%; }
    .cmod-action-form { width: 100%; }
    .cmod-item__actions { flex-direction: column; align-items: flex-start; }
}
</style>

<?php include INCLUDES_PATH . '/footer.php'; ?>
