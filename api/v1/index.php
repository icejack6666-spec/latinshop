<?php

declare(strict_types=1);

/**
 * ============================================================
 * API REST v1 — Front Controller
 * Latin Shop
 *
 * Punto de entrada para todas las peticiones a /api/v1/*
 * Ruta del archivo: api/v1/index.php
 * ============================================================
 */
$root = dirname(__DIR__, 2);

if (!file_exists($root . '/config/config.php')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => ['code' => 'BOOT_ERROR', 'message' => 'Bootstrap failed.']]);
    exit;
}

define('LATINSHOP', true);

require_once $root . '/config/config.php';
require_once $root . '/includes/security.php';
require_once $root . '/includes/seguridad_vistas.php';
require_once $root . '/includes/Database.php';
require_once $root . '/includes/Auth.php';
require_once $root . '/includes/AuditLog.php';
require_once $root . '/includes/Notifications.php';

define('API_ENDPOINTS_PATH', __DIR__ . '/endpoints');


require_once INCLUDES_PATH . '/Api/ApiResponse.php';
require_once INCLUDES_PATH . '/Api/ApiAuth.php';
require_once INCLUDES_PATH . '/Api/ApiRateLimit.php';
require_once INCLUDES_PATH . '/Api/ApiRouter.php';
require_once INCLUDES_PATH . '/Api/BaseEndpoint.php';

if (isset($_COOKIE[session_name()])) {
    secure_session_start();
}

header('X-API-Version: 1.0');
header('X-Powered-By: LatinShop');

$auth      = new ApiAuth();
$rateLimit = new ApiRateLimit();
$router    = new ApiRouter($auth, $rateLimit);

$router->dispatch();
