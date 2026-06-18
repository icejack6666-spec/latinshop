<?php
if (!defined('LATINSHOP')) {
    define('LATINSHOP', true);
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/seguridad_vistas.php';

require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Twilio.php';
require_once INCLUDES_PATH . '/Auth.php';
require_once INCLUDES_PATH . '/Comments.php';
require_once INCLUDES_PATH . '/TOTP.php';
require_once INCLUDES_PATH . '/Mailer.php';
// Después (usando INCLUDES_PATH como el resto):
require_once INCLUDES_PATH . '/Security/IpHelper.php';
require_once INCLUDES_PATH . '/Security/BotDetector.php';
require_once INCLUDES_PATH . '/Security/SecurityLogger.php';
require_once INCLUDES_PATH . '/Security/RateLimiter.php';
require_once INCLUDES_PATH . '/Security/InputScanner.php';
require_once INCLUDES_PATH . '/helpers.php';
require_once INCLUDES_PATH . '/Security/Security.php';

require_once INCLUDES_PATH . '/AuditLog.php';
require_once INCLUDES_PATH . '/Notifications.php';
require_once INCLUDES_PATH . '/Support/SupportTicketRepository.php';
require_once INCLUDES_PATH . '/Support/SupportMessageRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentRepository.php';
require_once INCLUDES_PATH . '/Support/AttachmentService.php';
require_once INCLUDES_PATH . '/Support/SupportTicketService.php';
require_once INCLUDES_PATH . '/Backup/BackupService.php';  // Paso 5
require_once INCLUDES_PATH . '/OPcacheService.php';        // Paso 6
require_once INCLUDES_PATH . '/Support/RedisConnection.php';
require_once INCLUDES_PATH . '/Support/CacheService.php';
require_once INCLUDES_PATH . '/AssetManifest.php';
require_once INCLUDES_PATH . '/LazyImage.php';
require_once INCLUDES_PATH . '/Api/ApiResponse.php';
require_once INCLUDES_PATH . '/Api/ApiAuth.php';

if (str_starts_with(trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/'), 'api/')) {
    http_response_code(404);
    exit('Not found.');
}

secure_session_start();
set_security_headers();

$security = Security::getInstance();
$security->checkRequest();
$security->autoBlockOnFailedLogins(20, 60, 24);

$auth = Auth::getInstance();
$auth->rememberMeCheck();

$request_uri  = $_SERVER['REQUEST_URI'] ?? '/';
$path         = parse_url($request_uri, PHP_URL_PATH);

$script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($script_dir !== '' && strpos($path, $script_dir) === 0) {
    $path = substr($path, strlen($script_dir));
}

$request_path = trim(strtok($path, '?'), '/');

$routes = [
    ''                                      => 'pages/home.php',

    'bots'                                  => 'pages/bots/index.php',
    'bots/bot-farming'                      => 'pages/bots/bot-farming.php',
    'bots/bot-whatsapp'                     => 'pages/bots/bot-whatsapp.php',
    'bots/farming-config'                   => 'pages/bots/farming-config.php',
    'gems'                                  => 'pages/gems.php',

    'utilidades'                            => 'pages/utilidades/index.php',

    'admin/farming'  => 'pages/admin/farming.php',

    'contacto'                              => 'pages/contacto.php',
    'terminos-y-condiciones'                => 'pages/terminos.php',
    'politica-de-privacidad'               => 'pages/privacidad.php',
    'politica-de-cookies'                   => 'pages/cookies.php',

    'login'                                 => 'pages/auth/login.php',
    'registrar'                             => 'pages/auth/registrar.php',
    'logout'                                => 'pages/auth/logout.php',
    'recuperar-password'                    => 'pages/auth/recuperar-password.php',
    'verificar-telefono'                    => 'pages/auth/verificar-telefono.php',
    'verificar-2fa'                         => 'pages/auth/verificar-2fa.php',

    'perfil'                                => 'pages/perfil.php',
    'perfil/seguridad'                      => 'pages/perfil-2fa.php',

    'admin'                                 => 'pages/admin/index.php',
    'admin/usuarios'                        => 'pages/admin/usuarios.php',
    'admin/comentarios'                     => 'pages/admin/comentarios.php',
    'admin/seguridad'                       => 'pages/admin/seguridad.php',

    'cuentas'                               => 'pages/cuentas.php',
    'cuentas/ver'                           => 'pages/cuentas-detail.php',

    'admin/cuentas'                         => 'pages/admin/cuentas.php',

    'offline'                               => 'pages/offline.php',
    'proximamente'                          => 'pages/proximamente.php',


    'support'             => 'pages/admin/support/index.php',
    'support/ver'         => 'pages/admin/support/view.php',
    'support/attachment'  => 'pages/admin/support/attachment.php',

    'admin/tickets'       => 'pages/admin/tickets.php',
    'admin/ticket'        => 'pages/admin/ticket_view.php',

    'admin/backups'           => 'pages/admin/backups.php',
    'admin/backup/download'   => 'pages/admin/backup_download.php',

    'admin/opcache' => 'pages/admin/opcache.php',
];

$feature_routes = [
    // Autenticación — bloquear el index de cada sección bloquea el flujo completo
    'login'           => ['login'],
    'register'        => ['registrar'],
    'forgot_password' => ['recuperar-password'],

    // Usuarios
    'profile'         => ['perfil', 'perfil/seguridad'],

    // Tienda
    'cuentas'         => ['cuentas', 'cuentas/ver'],
    'bots'            => ['bots', 'bots/bot-farming', 'bots/bot-whatsapp', 'bots/farming-config'],

    // Soporte
    'support'         => ['support', 'support/ver', 'support/attachment'],
];

foreach ($feature_routes as $feature_name => $blocked_paths) {
    if (!feature($feature_name) && in_array($request_path, $blocked_paths, true)) {
        header('Location: ' . u('/proximamente') . '?s=' . urlencode($feature_name));
        exit;
    }
}

$file = $routes[$request_path] ?? null;

if ($file && file_exists(ROOT_PATH . '/' . $file)) {
    require_once ROOT_PATH . '/' . $file;
} else {
    http_response_code(404);
    require_once PAGES_PATH . '/404.php';
}
