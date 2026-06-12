<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * pages/admin/backup_download.php
 *
 * Sirve un archivo de backup como descarga forzada.
 * Solo accesible para administradores y con CSRF (GET token).
 */

$auth = Auth::getInstance();
$auth->requireRole('admin');

require_once INCLUDES_PATH . '/Backup/BackupService.php';

$filename = $_GET['file'] ?? '';
$type     = $_GET['type'] ?? '';

if (empty($filename) || empty($type)) {
    http_response_code(400);
    exit('Parámetros inválidos.');
}

if (
    $filename !== basename($filename) ||
    str_contains($filename, '..') ||
    str_contains($filename, '/') ||
    str_contains($filename, '\\')
) {
    http_response_code(400);
    exit('Nombre de archivo inválido.');
}

$service  = new BackupService();
$filePath = $service->getDownloadPath($filename, $type);

if ($filePath === null) {
    http_response_code(404);
    exit('Backup no encontrado.');
}

$filesize = filesize($filePath);
$mime     = match (true) {
    str_ends_with($filename, '.sql.gz') => 'application/gzip',
    str_ends_with($filename, '.tar.gz') => 'application/gzip',
    default                             => 'application/octet-stream',
};

AuditLog::log('backup_download', null, null, ['file' => $filename, 'type' => $type]);

header('Content-Type: '        . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
header('Content-Length: '      . $filesize);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

while (ob_get_level()) {
    ob_end_clean();
}

readfile($filePath);
exit;
