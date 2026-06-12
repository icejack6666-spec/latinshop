<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole('admin');

require_once INCLUDES_PATH . '/Backup/BackupService.php';

$service = new BackupService();
$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Token CSRF inválido. Recarga la página e inténtalo de nuevo.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'backup_full':
                $result  = $service->createFull();
                $success = $result['success'] ? $result['message'] : null;
                $error   = !$result['success'] ? $result['message'] : null;
                AuditLog::log('backup_full', null, null, ['result' => $result['success']]);
                break;

            case 'backup_db':
                $result  = $service->backupDatabase();
                $success = $result['success'] ? 'Backup de base de datos creado: ' . htmlspecialchars($result['file'] ?? '', ENT_QUOTES, 'UTF-8') : null;
                $error   = !$result['success'] ? $result['message'] : null;
                AuditLog::log('backup_db', null, null, ['result' => $result['success']]);
                break;

            case 'backup_files':
                $result  = $service->backupFiles();
                $success = $result['success'] ? 'Backup de archivos creado: ' . htmlspecialchars($result['file'] ?? '', ENT_QUOTES, 'UTF-8') : null;
                $error   = !$result['success'] ? $result['message'] : null;
                AuditLog::log('backup_files', null, null, ['result' => $result['success']]);
                break;

            case 'purge':
                $result  = $service->purgeOld();
                $success = "Purga completada: {$result['deleted']} archivos eliminados.";
                AuditLog::log('backup_purge', null, null, $result);
                break;

            case 'delete':
                $filename = $_POST['filename'] ?? '';
                $type     = $_POST['type']     ?? '';
                if ($service->deleteBackup($filename, $type)) {
                    $success = 'Backup eliminado correctamente.';
                    AuditLog::log('backup_delete', null, null, ['file' => $filename, 'type' => $type]);
                } else {
                    $error = 'No se pudo eliminar el backup. El archivo no existe o no es válido.';
                }
                break;
        }
    }
}

$backups = $service->listBackups();
$stats   = $service->getStats();
$logs    = $service->getRecentLogs(10);
$page_title = 'Backups del Sistema | Admin';
require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/admin-sidebar.php';
?>

<main class="admin-main">
    <div class="admin-content">

        <div class="admin-page-header">
            <h1 class="admin-page-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:8px">
                    <path d="M20 6h-2.18c.07-.44.18-.88.18-1.36C18 2.52 15.48 0 12 0S6 2.52 6 4.64c0 .48.11.92.18 1.36H4c-1.11 0-2 .89-2 2v11c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-8-4c1.66 0 3 1.34 3 3 0 .51-.17.96-.4 1.36H9.4C9.17 6.01 9 5.56 9 5c0-1.66 1.34-3 3-3zm8 17H4v-2h16v2zm0-5H4v-6h4.08l-1.08 1 1.42 1.41L11 8.83V15h2V8.83l2.58 2.58L17 10l-1.08-1H20v6z"/>
                </svg>
                Backups del Sistema
            </h1>
            <p class="admin-page-subtitle">Gestiona los respaldos de la base de datos y los archivos del proyecto.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert--error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert--success" role="alert">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="admin-stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:2rem;">

            <div class="admin-stat-card">
                <span class="admin-stat-label">Backups DB</span>
                <span class="admin-stat-value"><?= $stats['total_db'] ?></span>
                <span class="admin-stat-sub"><?= htmlspecialchars(adminFormatBytes($stats['size_db']), ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="admin-stat-card">
                <span class="admin-stat-label">Backups Archivos</span>
                <span class="admin-stat-value"><?= $stats['total_files'] ?></span>
                <span class="admin-stat-sub"><?= htmlspecialchars(adminFormatBytes($stats['size_files']), ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="admin-stat-card">
                <span class="admin-stat-label">Más Reciente</span>
                <span class="admin-stat-value" style="font-size:0.9rem;"><?= $stats['newest'] ? htmlspecialchars($stats['newest'], ENT_QUOTES, 'UTF-8') : '—' ?></span>
            </div>

            <div class="admin-stat-card">
                <span class="admin-stat-label">Más Antiguo</span>
                <span class="admin-stat-value" style="font-size:0.9rem;"><?= $stats['oldest'] ? htmlspecialchars($stats['oldest'], ENT_QUOTES, 'UTF-8') : '—' ?></span>
            </div>

        </div>

        <div class="admin-card" style="margin-bottom:2rem;">
            <div class="admin-card__header">
                <h2 class="admin-card__title">Crear Backup</h2>
            </div>
            <div class="admin-card__body" style="display:flex;gap:1rem;flex-wrap:wrap;">

                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="backup_full">
                    <button type="submit" class="btn btn--primary"
                            onclick="return confirm('¿Crear backup completo (DB + archivos)?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2v9.67z"/></svg>
                        Backup Completo
                    </button>
                </form>

                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="backup_db">
                    <button type="submit" class="btn btn--secondary"
                            onclick="return confirm('¿Crear backup solo de la base de datos?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3C7.58 3 4 4.79 4 7v10c0 2.21 3.59 4 8 4s8-1.79 8-4V7c0-2.21-3.58-4-8-4zm0 2c3.87 0 6 1.5 6 2s-2.13 2-6 2-6-1.5-6-2 2.13-2 6-2zm0 14c-3.87 0-6-1.5-6-2v-2.23c1.61.78 3.72 1.23 6 1.23s4.39-.45 6-1.23V17c0 .5-2.13 2-6 2z"/></svg>
                        Solo Base de Datos
                    </button>
                </form>

                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="backup_files">
                    <button type="submit" class="btn btn--secondary"
                            onclick="return confirm('¿Crear backup de archivos del proyecto?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                        Solo Archivos
                    </button>
                </form>

                <form method="POST" action="" style="margin-left:auto;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="purge">
                    <button type="submit" class="btn btn--danger"
                            onclick="return confirm('¿Eliminar todos los backups con más de <?= (int)($_ENV['BACKUP_RETENTION_DAYS'] ?? 30) ?> días?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                        Purgar Antiguos
                    </button>
                </form>

            </div>
        </div>

        <div class="admin-card" style="margin-bottom:2rem;">
            <div class="admin-card__header">
                <h2 class="admin-card__title">Backups Disponibles</h2>
                <span class="admin-badge"><?= count($backups) ?></span>
            </div>
            <div class="admin-card__body" style="padding:0;">

                <?php if (empty($backups)): ?>
                    <div style="padding:2rem;text-align:center;color:var(--color-text-muted);">
                        No hay backups disponibles. Crea el primero con los botones de arriba.
                    </div>
                <?php else: ?>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Archivo</th>
                                    <th>Tamaño</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <span class="admin-badge admin-badge--<?= $backup['type'] === 'db' ? 'info' : 'secondary' ?>">
                                                <?= $backup['type'] === 'db' ? '🗄️ DB' : '📁 Files' ?>
                                            </span>
                                        </td>
                                        <td style="font-family:monospace;font-size:0.82rem;">
                                            <?= htmlspecialchars($backup['filename'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars($backup['size_human'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($backup['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <div style="display:flex;gap:0.5rem;">
                                                <!-- Descargar -->
                                                <a href="<?= u('/admin/backup/download?file=' . urlencode($backup['filename']) . '&type=' . urlencode($backup['type'])) ?>"
                                                   class="btn btn--xs btn--secondary"
                                                   title="Descargar backup">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                                                </a>

                                                <!-- Eliminar -->
                                                <form method="POST" action="" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action"   value="delete">
                                                    <input type="hidden" name="filename" value="<?= htmlspecialchars($backup['filename'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="type"     value="<?= htmlspecialchars($backup['type'],     ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit"
                                                            class="btn btn--xs btn--danger"
                                                            title="Eliminar backup"
                                                            onclick="return confirm('¿Eliminar este backup permanentemente?')">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <?php if (!empty($logs)): ?>
        <div class="admin-card">
            <div class="admin-card__header">
                <h2 class="admin-card__title">Actividad Reciente</h2>
            </div>
            <div class="admin-card__body" style="padding:0;">
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Tipo</th>
                                <th>Fecha / Hora</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <?php if ($log['success']): ?>
                                            <span class="admin-badge admin-badge--success">✓ OK</span>
                                        <?php else: ?>
                                            <span class="admin-badge admin-badge--error">✗ Error</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars((string) $log['type'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $log['timestamp'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="font-size:0.8rem;color:var(--color-text-muted);">
                                        <?php
                                        $details = $log['details'] ?? [];
                                        if (isset($details['db']['message'])) {
                                            echo htmlspecialchars($details['db']['message'], ENT_QUOTES, 'UTF-8');
                                        } elseif (isset($details['message'])) {
                                            echo htmlspecialchars($details['message'], ENT_QUOTES, 'UTF-8');
                                        } elseif (isset($details['deleted'])) {
                                            echo "Eliminados: {$details['deleted']} | Liberados: " . adminFormatBytes((int)($details['freed_bytes'] ?? 0));
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>

<?php
require_once INCLUDES_PATH . '/footer.php';

function adminFormatBytes(int $bytes): string
{
    if ($bytes >= 1_073_741_824) return round($bytes / 1_073_741_824, 2) . ' GB';
    if ($bytes >= 1_048_576)     return round($bytes / 1_048_576,     2) . ' MB';
    if ($bytes >= 1024)          return round($bytes / 1024,          2) . ' KB';
    return $bytes . ' B';
}
