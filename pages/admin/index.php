<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole('admin');
$db    = Database::getInstance();
$cache = CacheService::getInstance();

// ─── Stats cacheadas ────────────────────────────────────────────────────────
// Antes: 16 COUNT(*) individuales, con alias duplicados (usuarios_total /
//        total_usuarios, usuarios_pendientes / usuarios_pending, etc.).
// Ahora: 6 queries — dos con GROUP BY que reemplazan múltiples COUNT(*).
// ────────────────────────────────────────────────────────────────────────────
$dashboard_stats = $cache->remember(
    CacheService::keyAdminDashboard(),
    function () use ($db): array {

        // 1 query → reemplaza 5 COUNT(*) separados sobre `users`
        //   usuarios_total, usuarios_hoy, usuarios_pendientes (=pending),
        //   usuarios_baneados (=banned), usuarios_client
        $users_raw = $db->fetchAll(
            "SELECT
                COUNT(*)                                            AS total,
                SUM(DATE(created_at) = CURDATE())                  AS hoy,
                SUM(role = 'pending')                              AS pending,
                SUM(role = 'client')                               AS client,
                SUM(role = 'banned')                               AS banned
             FROM users"
        );
        $u = $users_raw[0] ?? [];

        // 1 query → reemplaza 3 COUNT(*) separados sobre `comments`
        //   comments_total, comments_pending, comments_hoy
        $comments_raw = $db->fetchAll(
            "SELECT
                COUNT(*)                              AS total,
                SUM(status = 'pending')               AS pending,
                SUM(DATE(created_at) = CURDATE())     AS hoy
             FROM comments"
        );
        $c = $comments_raw[0] ?? [];

        // 1 query → reemplaza 2 COUNT(*) separados sobre `login_attempts`
        $logins_raw = $db->fetchAll(
            "SELECT
                SUM(success = 1)  AS exitosos,
                SUM(success = 0)  AS fallidos
             FROM login_attempts
             WHERE DATE(attempted_at) = CURDATE()"
        );
        $l = $logins_raw[0] ?? [];

        return [
            // Usuarios
            'usuarios_total'        => (int)($u['total']   ?? 0),
            'usuarios_hoy'          => (int)($u['hoy']     ?? 0),
            'usuarios_pending'      => (int)($u['pending']  ?? 0),
            'usuarios_client'       => (int)($u['client']  ?? 0),
            'usuarios_banned'       => (int)($u['banned']  ?? 0),
            // Comentarios
            'comments_total'        => (int)($c['total']   ?? 0),
            'comments_pending'      => (int)($c['pending'] ?? 0),
            'comments_hoy'          => (int)($c['hoy']     ?? 0),
            // Logins
            'logins_hoy'            => (int)($l['exitosos'] ?? 0),
            'intentos_fallidos_hoy' => (int)($l['fallidos'] ?? 0),
            // Queries individuales sin equivalente duplicado
            'notificaciones'        => $db->count("SELECT COUNT(*) FROM notifications WHERE is_read = 0"),
            'ips_bloqueadas'        => $db->count("SELECT COUNT(*) FROM blocked_ips WHERE permanent = 1 OR expires_at IS NULL OR expires_at > NOW()"),
        ];
    },
    CacheService::TTL_SHORT
);

// ─── Aplanado para la vista ──────────────────────────────────────────────────
// Un único array fuente; se eliminan las variables sueltas ($total_usuarios,
// $usuarios_pending…) que antes duplicaban las claves del array.
// ─────────────────────────────────────────────────────────────────────────────
$s = $dashboard_stats; // alias corto para usar en la vista

// ─── Queries de detalle (no cacheadas — datos en tiempo real) ────────────────
$registros_semana = $db->fetchAll(
    "SELECT DATE(created_at) AS dia, COUNT(*) AS total
     FROM users
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at)
     ORDER BY dia ASC"
);

$actividad_reciente = $db->fetchAll(
    "SELECT al.action, al.created_at, al.target_id,
            u.username AS actor
     FROM audit_log al
     LEFT JOIN users u ON u.id = al.user_id
     ORDER BY al.created_at DESC
     LIMIT 10"
);

$ultimos_usuarios = $db->fetchAll(
    "SELECT id, username, email, role, created_at
     FROM users
     ORDER BY created_at DESC
     LIMIT 5"
);

$ultimos_comentarios = $db->fetchAll(
    "SELECT c.id, c.content, c.page_slug, c.created_at, u.username
     FROM comments c
     INNER JOIN users u ON c.user_id = u.id
     WHERE c.status = 'pending'
     ORDER BY c.created_at DESC
     LIMIT 5"
);

$usuario    = $auth->getUser();
$page_title = 'Panel Admin | Latin Shop';
$extra_css  = ['admin.css'];

?>
<div class="admin-layout">
<?php require_once INCLUDES_PATH . '/header.php'; ?>
<?php require_once INCLUDES_PATH . '/admin-sidebar.php'; ?>

<div class="admin-content">

    <div class="admin-page-header">
        <h1>Dashboard</h1>
        <?php if ($cache->isEnabled()): ?>
            <span class="badge badge--answered" title="Redis activo — stats cacheadas <?= CacheService::TTL_SHORT ?>s">
                ⚡ Caché activa
            </span>
        <?php endif; ?>
    </div>

    <!-- Resumen general -->
    <div class="admin-stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $s['usuarios_total'] ?></div>
            <div class="stat-label">Usuarios totales</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $s['usuarios_hoy'] ?></div>
            <div class="stat-label">Registros hoy</div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-number"><?= $s['usuarios_pending'] ?></div>
            <div class="stat-label">Usuarios pendientes</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-number"><?= $s['usuarios_banned'] ?></div>
            <div class="stat-label">Usuarios baneados</div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-number"><?= $s['comments_pending'] ?></div>
            <div class="stat-label">Comentarios pendientes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $s['logins_hoy'] ?></div>
            <div class="stat-label">Logins hoy</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-number"><?= $s['intentos_fallidos_hoy'] ?></div>
            <div class="stat-label">Intentos fallidos hoy</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-number"><?= $s['ips_bloqueadas'] ?></div>
            <div class="stat-label">IPs bloqueadas</div>
        </div>
    </div>

    <!-- Sección usuarios -->
    <div class="admin-section">
        <h2>Usuarios</h2>
        <div class="admin-stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $s['usuarios_total'] ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card stat-card--warning">
                <div class="stat-number"><?= $s['usuarios_pending'] ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $s['usuarios_client'] ?></div>
                <div class="stat-label">Clientes</div>
            </div>
            <div class="stat-card stat-card--danger">
                <div class="stat-number"><?= $s['usuarios_banned'] ?></div>
                <div class="stat-label">Baneados</div>
            </div>
        </div>

        <h3>Últimos registros</h3>
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Fecha</th></tr>
            </thead>
            <tbody>
                <?php foreach ($ultimos_usuarios as $u): ?>
                <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= htmlspecialchars($u['username'],   ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($u['email'],      ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge--<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Sección comentarios -->
    <div class="admin-section">
        <h2>Comentarios</h2>
        <div class="admin-stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $s['comments_total'] ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card stat-card--warning">
                <div class="stat-number"><?= $s['comments_pending'] ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $s['comments_hoy'] ?></div>
                <div class="stat-label">Hoy</div>
            </div>
        </div>

        <?php if (!empty($ultimos_comentarios)): ?>
        <h3>Comentarios pendientes recientes</h3>
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Usuario</th><th>Página</th><th>Contenido</th><th>Fecha</th></tr>
            </thead>
            <tbody>
                <?php foreach ($ultimos_comentarios as $c): ?>
                <tr>
                    <td><?= (int)$c['id'] ?></td>
                    <td><?= htmlspecialchars($c['username'],  ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($c['page_slug'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(mb_substr($c['content'], 0, 80), ENT_QUOTES, 'UTF-8') ?>…</td>
                    <td><?= htmlspecialchars($c['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Sección actividad -->
    <div class="admin-section">
        <h2>Actividad reciente</h2>
        <table class="admin-table">
            <thead>
                <tr><th>Actor</th><th>Acción</th><th>Target</th><th>Fecha</th></tr>
            </thead>
            <tbody>
                <?php foreach ($actividad_reciente as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['actor'] ?? '–',              ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['action'],                     ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($a['target_id'] ?? '–'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['created_at'],                 ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<?php require_once INCLUDES_PATH . '/footer.php'; ?>