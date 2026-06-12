<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * Endpoint de descarga de adjuntos.
 * Ruta: /support/attachment?id=N
 *
 * Seguridad:
 *  - Usuario autenticado obligatorio
 *  - Solo puede descargar adjuntos de sus propios tickets
 *  - Admins pueden descargar cualquier adjunto
 *  - Los archivos se sirven desde fuera del webroot (readfile)
 */

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

$attId = max(0, (int)($_GET['id'] ?? 0));

if ($attId === 0) {
    http_response_code(400);
    exit('ID inválido.');
}

$attService = new AttachmentService();
$attService->serveFile($attId, $userId, $isAdmin);
