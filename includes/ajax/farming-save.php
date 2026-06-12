<?php
/**
 * farming-save.php — Guarda configuración de bot farming en la DB
 * Ruta: /latinshop/includes/ajax/farming-save.php
 */

define('LATINSHOP', true);
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . '/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['answers'])) {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    $db = Database::getInstance();

    $db->insert(
        "INSERT INTO farming_configs
            (answers_json, contact_email, contact_whatsapp, contact_telegram, ip_address)
         VALUES (?, ?, ?, ?, ?)",
        [
            json_encode($data['answers'], JSON_UNESCAPED_UNICODE),
            trim($data['contact_email']    ?? ''),
            trim($data['contact_whatsapp'] ?? ''),
            trim($data['contact_telegram'] ?? ''),
            $_SERVER['REMOTE_ADDR'] ?? '',
        ]
    );

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    error_log('[FarmingSave] Error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error interno']);
}