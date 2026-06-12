#!/usr/bin/env php
<?php

/**
 * ============================================================
 * scripts/backup.php  —  CLI de Backups Automáticos
 * ============================================================
 * USO DESDE LÍNEA DE COMANDOS:
 *
 *   php /ruta/al/proyecto/scripts/backup.php [comando]
 *
 * COMANDOS DISPONIBLES:
 *   full    → Backup completo (DB + archivos)  [default]
 *   db      → Solo volcado SQL comprimido
 *   files   → Solo archivado de archivos
 *   purge   → Eliminar backups más antiguos que BACKUP_RETENTION_DAYS
 *   list    → Listar backups existentes
 *   stats   → Mostrar estadísticas
 *   logs    → Mostrar últimas entradas del log
 *
 * EJEMPLOS DE CRONTAB:
 *   # Backup completo diario a las 02:00
 *   0 2 * * * php /var/www/html/latinshop/scripts/backup.php full >> /dev/null 2>&1
 *
 *   # Solo DB cada 6 horas
 *   0 *6 * * * php /var/www/html/latinshop/scripts/backup.php db >> /dev/null 2>&1
 *
 *   # Purga automática semanal (lunes 03:00)
 *   0 3 * * 1 php /var/www/html/latinshop/scripts/backup.php purge >> /dev/null 2>&1
 */

// ── Bootstrap mínimo (sin HTTP, sin sesiones) ──────────────────────────────
define('LATINSHOP', true);

$rootPath = dirname(__DIR__) . '/latinshop';

if (!file_exists($rootPath . '/config/config.php')) {
    fwrite(STDERR, "[ERROR] No se encontró config/config.php en: {$rootPath}\n");
    exit(1);
}

require_once $rootPath . '/config/config.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Backup/BackupService.php';

// ── Parsear argumento ──────────────────────────────────────────────────────
$command = $argv[1] ?? 'full';
$validCommands = ['full', 'db', 'files', 'purge', 'list', 'stats', 'logs'];

if (!in_array($command, $validCommands, true)) {
    fwrite(STDERR, "[ERROR] Comando inválido: {$command}\n");
    fwrite(STDERR, "Uso: php backup.php [" . implode('|', $validCommands) . "]\n");
    exit(1);
}

// ── Ejecutar ───────────────────────────────────────────────────────────────
$service = new BackupService();
$start   = microtime(true);

echo "[" . date('Y-m-d H:i:s') . "] Iniciando backup: {$command}\n";

switch ($command) {
    case 'full':
        $result = $service->createFull();
        printResult($result);
        exit($result['success'] ? 0 : 1);

    case 'db':
        $result = $service->backupDatabase();
        printResult($result);
        exit($result['success'] ? 0 : 1);

    case 'files':
        $result = $service->backupFiles();
        printResult($result);
        exit($result['success'] ? 0 : 1);

    case 'purge':
        $result = $service->purgeOld();
        echo "[OK] Purgados: {$result['deleted']} archivos, liberados: " . formatBytes($result['freed_bytes']) . "\n";
        exit(0);

    case 'list':
        $backups = $service->listBackups();
        if (empty($backups)) {
            echo "No hay backups disponibles.\n";
        } else {
            printf("%-8s %-45s %-12s %s\n", 'Tipo', 'Archivo', 'Tamaño', 'Fecha');
            echo str_repeat('-', 90) . "\n";
            foreach ($backups as $b) {
                printf("%-8s %-45s %-12s %s\n",
                    $b['type'],
                    $b['filename'],
                    $b['size_human'],
                    $b['created_at']
                );
            }
        }
        exit(0);

    case 'stats':
        $stats = $service->getStats();
        echo "=== Estadísticas de Backups ===\n";
        echo "Backups DB:       {$stats['total_db']} ({$stats['size_db_human']})\n";
        echo "Backups Files:    {$stats['total_files']} ({$stats['size_files_human']})\n";
        echo "Más reciente:     " . ($stats['newest'] ?? 'N/A') . "\n";
        echo "Más antiguo:      " . ($stats['oldest'] ?? 'N/A') . "\n";
        exit(0);

    case 'logs':
        $limit = (int) ($argv[2] ?? 20);
        $logs  = $service->getRecentLogs($limit);
        if (empty($logs)) {
            echo "No hay entradas en el log.\n";
        } else {
            foreach ($logs as $entry) {
                $status = $entry['success'] ? '[OK]  ' : '[FAIL]';
                echo "{$status} [{$entry['timestamp']}] {$entry['type']}\n";
            }
        }
        exit(0);
}

// ── Helpers ────────────────────────────────────────────────────────────────
function printResult(array $result): void
{
    $status = $result['success'] ? '[OK]  ' : '[FAIL]';
    echo "{$status} {$result['message']}\n";

    if (!empty($result['db_file'])) {
        echo "      DB file:    {$result['db_file']}\n";
    }
    if (!empty($result['files_file'])) {
        echo "      Files file: {$result['files_file']}\n";
    }
    if (!empty($result['file'])) {
        echo "      File: {$result['file']} (" . formatBytes($result['size'] ?? 0) . ")\n";
    }
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1_073_741_824) return round($bytes / 1_073_741_824, 2) . ' GB';
    if ($bytes >= 1_048_576)     return round($bytes / 1_048_576,     2) . ' MB';
    if ($bytes >= 1024)          return round($bytes / 1024,          2) . ' KB';
    return $bytes . ' B';
}
