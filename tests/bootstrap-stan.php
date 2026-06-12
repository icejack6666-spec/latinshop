<?php

/**
 * bootstrap-stan.php
 * Define constantes globales para que PHPStan pueda analizar
 * el código sin ejecutar config.php completo.
 */

// Constante de acceso
define('LATINSHOP', true);

// Entorno
define('ENV', 'testing');

// Rutas
define('ROOT_PATH',     dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH',    ROOT_PATH . '/pages');
define('ASSETS_PATH',   ROOT_PATH . '/frontend/assets');
define('ASSETS_URL',    'https://example.com/assets');
define('SITE_URL',      'https://example.com');
define('SITE_NAME',     'Latin Shop');

// DB
define('DB_HOST',    'localhost');
define('DB_NAME',    'test_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Redis
define('REDIS_HOST', null);
define('REDIS_PORT', 6379);
define('REDIS_PASS', null);
define('REDIS_DB',   0);

// Seguridad
define('CSRF_TOKEN_LENGTH',  32);
define('SESSION_LIFETIME',   1800);
define('MAX_LOGIN_ATTEMPTS', 5);
define('RECAPTCHA_SITE_KEY',   '');
define('RECAPTCHA_SECRET_KEY', '');
define('CONTACT_EMAIL',        'test@example.com');
define('WHATSAPP_NUMBER',      '');
define('TWILIO_ACCOUNT_SID',   '');
define('TWILIO_AUTH_TOKEN',    '');
define('TWILIO_FROM_NUMBER',   '');
define('STORAGE_PATH',         '/tmp/latinshop_test_storage');

// Features
define('FEATURES', [
    'cuentas'            => false,
    'bots'               => true,
    'support'            => true,
]);
