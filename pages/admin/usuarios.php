<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole('admin');

$db      = Database::getInstance();
$usuario = $auth->getUser();

$msg_ok  = null;
$msg_err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg_err = 'Token de seguridad inválido.';
    } else {
        $accion    = $_POST['accion']  ?? '';
        $target_id = (int)($_POST['user_id'] ?? 0);

        if ($target_id === (int)$usuario['id']) {
            $msg_err = 'No puedes modificar tu propio rol desde aquí.';
        } else {
            $map_acciones = [
                'aprobar'   => 'client',
                'verificar' => 'verified',
                'admin'     => 'admin',
                'banear'    => 'banned',
                'pending'   => 'pending',
            ];

            if (isset($map_acciones[$accion])) {
                $nuevo_rol = $map_acciones[$accion];

                $anterior     = $db->fetch("SELECT role FROM users WHERE id = ? LIMIT 1", [$target_id]);
                $rol_anterior = $anterior['role'] ?? 'desconocido';

                $ok = $auth->changeRole($target_id, $nuevo_rol);

                if ($ok) {
                    if ($nuevo_rol === 'client') {
                        Notifications::send(
                            $target_id,
                            'account_approved',
                            '¡Cuenta aprobada!',
                            'Tu cuenta ha sido aprobada. Ya puedes acceder a todos los servicios.',
                            '/perfil'
                        );
                    }
                    if ($nuevo_rol === 'banned') {
                        Notifications::send(
                            $target_id,
                            'account_banned',
                            'Cuenta suspendida',
                            'Tu cuenta ha sido suspendida. Contacta al administrador.',
                            '/contacto'
                        );
                    }

                    AuditLog::log(
                        'user.role_changed',
                        $target_id,
                        ['role' => $rol_anterior],
                        ['role' => $nuevo_rol]
                    );

                    $labels = [
                        'client'   => 'aprobado como Usuario',
                        'verified' => 'marcado como Cliente Verificado ✓',
                        'admin'    => 'promovido a Admin',
                        'banned'   => 'baneado',
                        'pending'  => 'regresado a Pendiente',
                    ];
                    $u_notify = $db->fetch("SELECT email, username FROM users WHERE id = ? LIMIT 1", [$target_id]);
                    if ($u_notify) {
                       if ($nuevo_rol === 'client')   Mailer::sendAccountApproved($u_notify['email'], $u_notify['username']);
                       if ($nuevo_rol === 'verified') Mailer::sendAccountVerified($u_notify['email'], $u_notify['username']);
                    }

                    $msg_ok = 'Usuario ' . ($labels[$nuevo_rol] ?? 'actualizado') . ' correctamente.';
                } else {
                    $msg_err = 'No se pudo actualizar el usuario.';
                }
            } else {
                $msg_err = 'Acción no válida.';
            }
        }
    }
}

$filtro_rol    = $_GET['rol'] ?? 'todos';
$roles_validos = ['todos', 'pending', 'client', 'verified', 'admin', 'banned'];
if (!in_array($filtro_rol, $roles_validos, true)) $filtro_rol = 'todos';

$busqueda = sanitize_string($_GET['q'] ?? '', 100);

$por_pagina = 20;
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$offset     = ($pagina - 1) * $por_pagina;

$where  = [];
$params = [];

if ($filtro_rol !== 'todos') {
    $where[]  = "role = ?";
    $params[] = $filtro_rol;
}
if (!empty($busqueda)) {
    $where[]  = "(username LIKE ? OR email LIKE ?)";
    $params[] = '%' . $busqueda . '%';
    $params[] = '%' . $busqueda . '%';
}

$where_sql      = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$total_usuarios = $db->count("SELECT COUNT(*) FROM users $where_sql", $params);
$total_paginas  = (int)ceil($total_usuarios / $por_pagina);

$usuarios = $db->fetchAll(
    "SELECT id, username, email, role, avatar_url, phone_verified,
            two_fa_enabled, last_login, created_at
     FROM users $where_sql
     ORDER BY created_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$por_pagina, $offset])
);

$conteos = [
    'todos'    => $db->count("SELECT COUNT(*) FROM users"),
    'pending'  => $db->count("SELECT COUNT(*) FROM users WHERE role = 'pending'"),
    'client'   => $db->count("SELECT COUNT(*) FROM users WHERE role = 'client'"),
    'verified' => $db->count("SELECT COUNT(*) FROM users WHERE role = 'verified'"),
    'admin'    => $db->count("SELECT COUNT(*) FROM users WHERE role = 'admin'"),
    'banned'   => $db->count("SELECT COUNT(*) FROM users WHERE role = 'banned'"),
];

$page_title = 'Usuarios | Panel Admin | Latin Shop';
$extra_css = ['admin.css'];
include INCLUDES_PATH . '/header.php';
?>

<div class="admin-layout">
    <?php include INCLUDES_PATH . '/admin-sidebar.php'; ?>

    <main class="admin-main">

        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Usuarios</h1>
                <p class="admin-page-sub"><?= $total_usuarios ?> usuario<?= $total_usuarios !== 1 ? 's' : '' ?> encontrado<?= $total_usuarios !== 1 ? 's' : '' ?></p>
            </div>
        </div>

        <?php if ($msg_ok): ?>
            <div class="admin-alert admin-alert--ok">✓ <?= htmlspecialchars($msg_ok, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($msg_err): ?>
            <div class="admin-alert admin-alert--err">✕ <?= htmlspecialchars($msg_err, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- Filtros + Búsqueda -->
        <div class="admin-filters">
            <div class="admin-tabs">
                <?php
                $tab_labels = [
                    'todos'    => 'Todos',
                    'pending'  => 'Pendientes',
                    'client'   => 'Usuarios',
                    'verified' => 'Clientes Verificados',
                    'admin'    => 'Admins',
                    'banned'   => 'Baneados',
                ];
                foreach ($tab_labels as $key => $label):
                    $activo  = $filtro_rol === $key ? 'admin-tab--active' : '';
                    $url_tab = u('/admin/usuarios') . '?rol=' . $key
                             . (!empty($busqueda) ? '&q=' . urlencode($busqueda) : '');
                ?>
                    <a href="<?= $url_tab ?>" class="admin-tab <?= $activo ?>">
                        <?= $label ?>
                        <span class="admin-tab__count"><?= $conteos[$key] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" action="<?= u('/admin/usuarios') ?>" class="admin-search-form">
                <input type="hidden" name="rol" value="<?= htmlspecialchars($filtro_rol, ENT_QUOTES, 'UTF-8') ?>">
                <div class="admin-search-wrap">
                    <svg class="admin-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                    <input type="text" name="q" class="admin-search-input"
                        placeholder="Buscar por usuario o email..."
                        value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <button type="submit" class="admin-btn admin-btn--sm">Buscar</button>
                <?php if (!empty($busqueda)): ?>
                    <a href="<?= u('/admin/usuarios') ?>?rol=<?= $filtro_rol ?>"
                       class="admin-btn admin-btn--ghost admin-btn--sm">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-panel">
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Tel. verificado</th>
                            <th>Registro</th>
                            <th>Último acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="7" class="admin-table__empty">
                                    No se encontraron usuarios con ese filtro.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u_row):
                                $es_yo = ((int)$u_row['id'] === (int)$usuario['id']);
                                // DESPUÉS
                                $rol = get_role_badge($u_row['role'], 'admin');
                            ?>
                            <tr <?= $es_yo ? 'class="admin-table__row--me"' : '' ?>>

                                <td>
                                    <div class="admin-user-cell">
                                        <?php if (!empty($u_row['avatar_url'])): ?>
                                            <img src="<?= htmlspecialchars($u_row['avatar_url'], ENT_QUOTES, 'UTF-8') ?>"
                                                 class="admin-avatar" alt="">
                                        <?php else: ?>
                                            <div class="admin-avatar admin-avatar--default">
                                                <?= Comments::getInitials($u_row['username']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <span class="admin-table__username">
                                                <?= htmlspecialchars($u_row['username'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <?php if ($es_yo): ?>
                                                <span class="admin-badge admin-badge--outline" style="font-size:9px;">Tú</span>
                                            <?php endif; ?>
                                            <?php if ($u_row['two_fa_enabled']): ?>
                                                <span class="admin-badge admin-badge--amber" style="font-size:9px;" title="2FA activo">🔐 2FA</span>
                                            <?php endif; ?>
                                            <br>
                                            <span class="admin-table__muted">#<?= $u_row['id'] ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="admin-table__muted" style="font-size:var(--tbt-text-xs);">
                                    <?= htmlspecialchars($u_row['email'], ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <span class="admin-badge <?= $rol['class'] ?>">
                                        <?= $rol['label'] ?>
                                    </span>
                                </td>

                                <td style="text-align:center;">
                                    <?php if ($u_row['phone_verified']): ?>
                                        <span style="color:#4ade80;font-size:1.2rem;" title="Teléfono verificado">✓</span>
                                    <?php else: ?>
                                        <span style="color:var(--tbt-txt-dim);font-size:1.1rem;" title="Sin verificar">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="admin-table__mono admin-table__muted">
                                    <?= date('d/m/Y', strtotime($u_row['created_at'])) ?>
                                </td>

                                <td class="admin-table__mono admin-table__muted">
                                    <?= $u_row['last_login']
                                        ? date('d/m/Y H:i', strtotime($u_row['last_login']))
                                        : '—' ?>
                                </td>

                                <td>
                                    <?php if ($es_yo): ?>
                                        <span class="admin-table__muted" style="font-size:var(--tbt-text-xs);">Tu cuenta</span>
                                    <?php else: ?>
                                        <div class="admin-actions">

                                            
                                            <?php if ($u_row['role'] === 'pending'): ?>
                                                <form method="POST" action="<?= u('/admin/usuarios') ?>"
                                                      onsubmit="return confirm('¿Aprobar a <?= htmlspecialchars($u_row['username'], ENT_QUOTES) ?> como Usuario?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= $u_row['id'] ?>">
                                                    <input type="hidden" name="accion" value="aprobar">
                                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--green">
                                                        ✓ Aprobar
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            
                                            <?php if (in_array($u_row['role'], ['pending','client'], true) && $u_row['phone_verified']): ?>
                                                <form method="POST" action="<?= u('/admin/usuarios') ?>"
                                                      onsubmit="return confirm('¿Marcar a <?= htmlspecialchars($u_row['username'], ENT_QUOTES) ?> como Cliente Verificado?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= $u_row['id'] ?>">
                                                    <input type="hidden" name="accion" value="verificar">
                                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--blue">
                                                        ✓ Verificar
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                           
                                            <?php if ($u_row['role'] !== 'admin'): ?>
                                                <form method="POST" action="<?= u('/admin/usuarios') ?>"
                                                      onsubmit="return confirm('¿Hacer admin a <?= htmlspecialchars($u_row['username'], ENT_QUOTES) ?>? Tendrá acceso total.')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= $u_row['id'] ?>">
                                                    <input type="hidden" name="accion" value="admin">
                                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--purple">
                                                        ★ Admin
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($u_row['role'] !== 'banned'): ?>
                                                <form method="POST" action="<?= u('/admin/usuarios') ?>"
                                                      onsubmit="return confirm('¿Banear a <?= htmlspecialchars($u_row['username'], ENT_QUOTES) ?>?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= $u_row['id'] ?>">
                                                    <input type="hidden" name="accion" value="banear">
                                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--red">
                                                        ✕ Banear
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="<?= u('/admin/usuarios') ?>"
                                                      onsubmit="return confirm('¿Desbanear a <?= htmlspecialchars($u_row['username'], ENT_QUOTES) ?>?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= $u_row['id'] ?>">
                                                    <input type="hidden" name="accion" value="pending">
                                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--ghost">
                                                        ↩ Desbanear
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                        </div>
                                    <?php endif; ?>
                                </td>

                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="admin-pagination">
                    <?php for ($p = 1; $p <= $total_paginas; $p++):
                        $url_p = u('/admin/usuarios') . '?rol=' . $filtro_rol
                               . (!empty($busqueda) ? '&q=' . urlencode($busqueda) : '')
                               . '&pagina=' . $p;
                    ?>
                        <a href="<?= $url_p ?>"
                           class="admin-page-btn <?= $p === $pagina ? 'admin-page-btn--active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<style>
.admin-alert{padding:var(--tbt-s2) var(--tbt-s3);border-radius:var(--tbt-r-md);font-size:var(--tbt-text-sm);font-weight:500;margin-bottom:var(--tbt-s3)}
.admin-alert--ok{background:rgba(34,197,94,.08);color:#4ade80;border:1px solid rgba(34,197,94,.2)}
.admin-alert--err{background:rgba(239,68,68,.08);color:#f87171;border:1px solid rgba(239,68,68,.2)}
.admin-filters{display:flex;flex-wrap:wrap;align-items:center;gap:var(--tbt-s2);margin-bottom:var(--tbt-s3)}
.admin-tabs{display:flex;flex-wrap:wrap;gap:4px}
.admin-tab{display:inline-flex;align-items:center;gap:6px;padding:.4rem .9rem;border-radius:var(--tbt-r-full);font-size:var(--tbt-text-xs);font-weight:600;text-decoration:none;color:var(--tbt-txt-sub);background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);transition:all var(--tbt-t1)}
.admin-tab:hover{border-color:var(--tbt-bg-5);color:var(--tbt-txt-white)}
.admin-tab--active{background:var(--tbt-jade-15);color:var(--tbt-jade-light);border-color:var(--tbt-jade-30)}
.admin-tab__count{background:var(--tbt-bg-3);color:var(--tbt-txt-muted);font-family:var(--tbt-font-mono);font-size:10px;padding:1px 6px;border-radius:var(--tbt-r-full)}
.admin-tab--active .admin-tab__count{background:var(--tbt-jade-30);color:var(--tbt-jade-light)}
.admin-search-form{display:flex;align-items:center;gap:var(--tbt-s1);flex-wrap:wrap;margin-left:auto}
.admin-search-wrap{position:relative}
.admin-search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--tbt-txt-muted);pointer-events:none}
.admin-search-input{background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-5);border-radius:var(--tbt-r-md);color:var(--tbt-txt-white);font-family:var(--tbt-font-body);font-size:var(--tbt-text-sm);padding:.5rem .75rem .5rem 2.2rem;outline:none;width:240px;transition:border-color var(--tbt-t1)}
.admin-search-input:focus{border-color:var(--tbt-jade-40)}
.admin-search-input::placeholder{color:var(--tbt-txt-muted)}
.admin-btn{display:inline-flex;align-items:center;justify-content:center;gap:4px;border:none;border-radius:var(--tbt-r-md);font-family:var(--tbt-font-display);font-weight:600;cursor:pointer;text-decoration:none;transition:opacity var(--tbt-t1),transform var(--tbt-t1);white-space:nowrap}
.admin-btn:hover{opacity:.82;transform:translateY(-1px)}
.admin-btn--sm{font-size:var(--tbt-text-xs);padding:.35rem .75rem}
.admin-btn--green{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
.admin-btn--blue{background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3)}
.admin-btn--purple{background:var(--tbt-jade-15);color:var(--tbt-jade-light);border:1px solid var(--tbt-jade-30)}
.admin-btn--red{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.25)}
.admin-btn--ghost{background:var(--tbt-bg-2);color:var(--tbt-txt-sub);border:1px solid var(--tbt-bg-5)}
.admin-actions{display:flex;gap:4px;flex-wrap:wrap}
.admin-actions form{margin:0}
.admin-user-cell{display:flex;align-items:center;gap:var(--tbt-s1)}
.admin-avatar{width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid var(--tbt-bg-4);flex-shrink:0}
.admin-avatar--default{width:32px;height:32px;border-radius:50%;background:var(--tbt-jade-15);border:2px solid var(--tbt-jade-30);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:var(--tbt-jade-light);font-family:var(--tbt-font-mono);flex-shrink:0}
.admin-table__row--me td{background:var(--tbt-jade-04)}
.admin-badge{display:inline-block;font-size:var(--tbt-text-2xs);font-weight:700;font-family:var(--tbt-font-mono);padding:2px 8px;border-radius:var(--tbt-r-full);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
.admin-badge--purple{background:var(--tbt-jade-15);color:var(--tbt-jade-light);border:1px solid var(--tbt-jade-30)}
.admin-badge--amber{background:var(--tbt-amber-15);color:var(--tbt-amber);border:1px solid var(--tbt-amber-30)}
.admin-badge--green{background:rgba(34,197,94,.1);color:#4ade80;border:1px solid rgba(34,197,94,.25)}
.admin-badge--blue{background:rgba(59,130,246,.1);color:#60a5fa;border:1px solid rgba(59,130,246,.25)}
.admin-badge--red{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.25)}
.admin-badge--outline{background:var(--tbt-bg-3);color:var(--tbt-txt-sub);border:1px solid var(--tbt-bg-5)}
.admin-pagination{display:flex;gap:4px;justify-content:center;padding:var(--tbt-s2) var(--tbt-s3);border-top:1px solid var(--tbt-bg-4)}
.admin-page-btn{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:var(--tbt-r-md);font-size:var(--tbt-text-xs);font-family:var(--tbt-font-mono);font-weight:600;text-decoration:none;color:var(--tbt-txt-sub);background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);transition:all var(--tbt-t1)}
.admin-page-btn:hover{border-color:var(--tbt-bg-5);color:var(--tbt-txt-white)}
.admin-page-btn--active{background:var(--tbt-jade-15);color:var(--tbt-jade-light);border-color:var(--tbt-jade-30)}
@media(max-width:768px){.admin-search-input{width:180px}.admin-filters{flex-direction:column;align-items:flex-start}.admin-search-form{margin-left:0}}
</style>

<?php include INCLUDES_PATH . '/footer.php'; ?>