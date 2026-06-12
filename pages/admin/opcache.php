<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole('admin');

require_once INCLUDES_PATH . '/OPcacheService.php';

$opcache = new OPcacheService();
$message = null;
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $message = 'Token CSRF inválido.';
        $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'flush':
                if ($opcache->flush()) {
                    $message = 'Cache OPcache vaciado correctamente.';
                    AuditLog::log('opcache_flush');
                } else {
                    $message = 'No se pudo vaciar el caché. Verifica que OPcache esté activo.';
                    $msgType = 'error';
                }
                break;

            case 'warmup':
                $result  = $opcache->warmup();
                $message = "Warmup completado: {$result['compiled']} compilados, {$result['failed']} fallidos, {$result['skipped']} omitidos.";
                if ($result['failed'] > 0) {
                    $msgType = 'warning';
                }
                AuditLog::log('opcache_warmup', null, null, $result);
                break;

            case 'invalidate':
                $file = trim($_POST['file'] ?? '');
                if (preg_match('/^[a-zA-Z0-9\/_\-\.]+\.php$/', $file) && !str_contains($file, '..')) {
                    if ($opcache->invalidate($file, true)) {
                        $message = "Archivo invalidado: " . htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
                        AuditLog::log('opcache_invalidate', null, null, ['file' => $file]);
                    } else {
                        $message = 'No se pudo invalidar el archivo. ¿Existe?';
                        $msgType = 'error';
                    }
                } else {
                    $message = 'Ruta de archivo inválida.';
                    $msgType = 'error';
                }
                break;
        }
    }
}

$summary  = $opcache->getSummary();
$warnings = $opcache->getConfigWarnings();
$config   = $opcache->getConfig();

$relevantConfig = [
    'opcache.enable'                => 'Activado',
    'opcache.memory_consumption'    => 'Memoria (MB)',
    'opcache.interned_strings_buffer' => 'Strings interned (MB)',
    'opcache.max_accelerated_files' => 'Máx. archivos acelerados',
    'opcache.validate_timestamps'   => 'Validar timestamps',
    'opcache.revalidate_freq'       => 'Frecuencia revalidación (s)',
    'opcache.jit'                   => 'JIT modo',
    'opcache.jit_buffer_size'       => 'JIT buffer (bytes)',
    'opcache.optimization_level'    => 'Nivel de optimización',
    'opcache.fast_shutdown'         => 'Fast shutdown',
];

$page_title = 'OPcache | Admin';
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/admin-sidebar.php';
?>

<main class="admin-main">
    <div class="admin-content">

        <div class="admin-page-header">
            <h1 class="admin-page-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:8px">
                    <path d="M13 2.05v2.02c3.95.49 7 3.85 7 7.93 0 3.21-1.81 6-4.72 7.28L13 17v5h5l-1.22-1.22C19.91 19.07 22 15.76 22 12c0-5.18-3.95-9.45-9-9.95zM11 2.05C5.95 2.55 2 6.82 2 12c0 3.76 2.09 7.07 5.22 8.78L6 22h5V2.05zM11 17H9.34l1.66 1.66V17z"/>
                </svg>
                Estado de OPcache
            </h1>
            <p class="admin-page-subtitle">Monitoreo y gestión de la caché de bytecode PHP 8.3.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert--<?= $msgType === 'error' ? 'error' : ($msgType === 'warning' ? 'warning' : 'success') ?>" role="alert">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php foreach ($warnings as $warn): ?>
            <div class="alert alert--warning" role="alert">
                ⚠️ <?= htmlspecialchars($warn, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>

        <?php if (!$summary['available']): ?>
            <div class="alert alert--error">
                <strong>OPcache no está disponible.</strong> Instala la extensión <code>ext-opcache</code> o actívala en <code>php.ini</code>:
                <pre style="margin-top:.5rem;font-size:.8rem;">opcache.enable=1</pre>
            </div>
        <?php else: ?>

        <div class="admin-stats-grid" style="margin-bottom:1.5rem;">

            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--<?= $summary['hit_ratio'] >= 90 ? 'green' : ($summary['hit_ratio'] >= 70 ? 'amber' : 'red') ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                </div>
                <div class="admin-stat-card__info">
                    <span class="admin-stat-card__num"><?= $summary['hit_ratio'] >= 0 ? $summary['hit_ratio'] . '%' : 'N/A' ?></span>
                    <span class="admin-stat-card__label">Hit Ratio</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--<?= $summary['memory_pct'] > 85 ? 'red' : 'purple' ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M15 9H9v6h6V9zm-2 4h-2v-2h2v2zm8-2V9h-2V7c0-1.1-.9-2-2-2h-2V3h-2v2h-2V3H9v2H7c-1.1 0-2 .9-2 2v2H3v2h2v2H3v2h2v2c0 1.1.9 2 2 2h2v2h2v-2h2v2h2v-2h2c1.1 0 2-.9 2-2v-2h2v-2h-2v-2h2zm-4 6H7V7h10v10z"/></svg>
                </div>
                <div class="admin-stat-card__info">
                    <span class="admin-stat-card__num"><?= $summary['memory_used_mb'] ?> MB</span>
                    <span class="admin-stat-card__label">Memoria usada / <?= $summary['memory_total_mb'] ?> MB total</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                </div>
                <div class="admin-stat-card__info">
                    <span class="admin-stat-card__num"><?= number_format($summary['cached_scripts']) ?></span>
                    <span class="admin-stat-card__label">Scripts en caché</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--<?= $summary['jit_enabled'] ? 'green' : 'amber' ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
                </div>
                <div class="admin-stat-card__info">
                    <span class="admin-stat-card__num"><?= $summary['jit_enabled'] ? 'Activo' : 'Inactivo' ?></span>
                    <span class="admin-stat-card__label">JIT Compiler<?= $summary['jit_enabled'] ? ' — ' . $summary['jit_buffer_mb'] . ' MB buffer' : '' ?></span>
                </div>
            </div>

        </div>

        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div class="admin-card__header">
                <h2 class="admin-card__title">Uso de Memoria OPcache</h2>
                <span style="font-size:.8rem;color:var(--color-text-muted);">
                    Libre: <?= $summary['memory_free_mb'] ?> MB
                </span>
            </div>
            <div class="admin-card__body">
                <div style="background:var(--tbt-bg-3);border-radius:6px;overflow:hidden;height:12px;">
                    <div style="
                        width:<?= min(100, $summary['memory_pct']) ?>%;
                        height:100%;
                        background:<?= $summary['memory_pct'] > 85 ? '#f87171' : ($summary['memory_pct'] > 70 ? 'var(--tbt-amber)' : 'var(--tbt-jade)') ?>;
                        transition:width .3s;
                    "></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--color-text-muted);margin-top:.4rem;">
                    <span><?= $summary['memory_pct'] ?>% utilizado</span>
                    <span>Total: <?= $summary['memory_total_mb'] ?> MB</span>
                </div>

                <div style="display:flex;gap:1.5rem;margin-top:1rem;flex-wrap:wrap;">
                    <div>
                        <span style="font-size:.75rem;color:var(--color-text-muted);">Hits</span>
                        <div style="font-weight:700;color:#4ade80;"><?= number_format($summary['hits']) ?></div>
                    </div>
                    <div>
                        <span style="font-size:.75rem;color:var(--color-text-muted);">Misses</span>
                        <div style="font-weight:700;color:#f87171;"><?= number_format($summary['misses']) ?></div>
                    </div>
                    <div>
                        <span style="font-size:.75rem;color:var(--color-text-muted);">Reinicios</span>
                        <div style="font-weight:700;color:<?= $summary['restarts'] > 0 ? '#f87171' : 'var(--tbt-txt-white)' ?>;">
                            <?= $summary['restarts'] ?>
                        </div>
                    </div>
                    <div>
                        <span style="font-size:.75rem;color:var(--color-text-muted);">Uptime</span>
                        <div style="font-weight:700;"><?= adminOpcacheFormatUptime($summary['uptime_seconds']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div class="admin-card__header">
                <h2 class="admin-card__title">Acciones</h2>
            </div>
            <div class="admin-card__body" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-start;">

                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="flush">
                    <button type="submit" class="btn btn--danger"
                            onclick="return confirm('¿Vaciar todo el caché OPcache? Esto provocará re-compilación en las próximas requests.')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                        Vaciar Caché
                    </button>
                </form>

                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="warmup">
                    <button type="submit" class="btn btn--primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2.05v2.02c3.95.49 7 3.85 7 7.93 0 3.21-1.81 6-4.72 7.28L13 17v5h5l-1.22-1.22C19.91 19.07 22 15.76 22 12c0-5.18-3.95-9.45-9-9.95z"/></svg>
                        Warmup (Precargar)
                    </button>
                </form>

                <form method="POST" action="" style="display:flex;gap:.5rem;flex-wrap:wrap;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="invalidate">
                    <input type="text" name="file" placeholder="includes/Auth.php"
                           pattern="[a-zA-Z0-9/_\-\.]+\.php"
                           style="background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-5);color:var(--tbt-txt-white);padding:.5rem .75rem;border-radius:6px;font-size:.85rem;min-width:220px;"
                           title="Ruta relativa al proyecto, ej: includes/Auth.php">
                    <button type="submit" class="btn btn--secondary">
                        Invalidar Archivo
                    </button>
                </form>

            </div>
        </div>

        <?php if (!empty($config)): ?>
        <div class="admin-card">
            <div class="admin-card__header">
                <h2 class="admin-card__title">Configuración Activa</h2>
                <span style="font-size:.75rem;color:var(--color-text-muted);">php.ini / .user.ini</span>
            </div>
            <div class="admin-card__body" style="padding:0;">
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Directiva</th>
                                <th>Valor Actual</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relevantConfig as $key => $label):
                                if (!array_key_exists($key, $config)) continue;
                                $val = $config[$key];
                                $displayVal = is_bool($val) ? ($val ? 'ON' : 'OFF') : (string) $val;
                                $isWarning = ($key === 'opcache.validate_timestamps' && $val && ENV === 'production');
                            ?>
                                <tr <?= $isWarning ? 'style="background:rgba(251,191,36,.05);"' : '' ?>>
                                    <td style="font-family:monospace;font-size:.82rem;color:var(--tbt-jade-light);">
                                        <?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <code style="background:var(--tbt-bg-3);padding:2px 6px;border-radius:3px;font-size:.82rem;">
                                            <?= htmlspecialchars($displayVal, ENT_QUOTES, 'UTF-8') ?>
                                        </code>
                                        <?php if ($isWarning): ?>
                                            <span style="color:#fbbf24;font-size:.75rem;margin-left:.4rem;">⚠️ Desactivar en prod</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:.82rem;color:var(--color-text-muted);">
                                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; // isAvailable ?>

    </div>
</main>

<?php
require_once INCLUDES_PATH . '/footer.php';

/**
 * Formatea segundos como "Xd Xh Xm".
 */
function adminOpcacheFormatUptime(int $seconds): string
{
    if ($seconds <= 0) return '—';
    $d = intdiv($seconds, 86400);
    $h = intdiv($seconds % 86400, 3600);
    $m = intdiv($seconds % 3600, 60);
    $parts = [];
    if ($d) $parts[] = "{$d}d";
    if ($h) $parts[] = "{$h}h";
    if ($m) $parts[] = "{$m}m";
    return implode(' ', $parts) ?: '< 1m';
}
