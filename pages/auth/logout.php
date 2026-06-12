<?php

if (!defined('LATINSHOP')) {
    define('LATINSHOP', true);
}

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/security.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Auth.php';

secure_session_start();

$auth = Auth::getInstance();

if ($auth->isLoggedIn()) {
    $auth->logout();
    secure_session_start();
    $_SESSION['flash_success'] = '¡Hasta pronto! Tu sesión ha sido cerrada correctamente.';
}

safe_redirect(u('/login'));