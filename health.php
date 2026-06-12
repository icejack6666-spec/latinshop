<?php

define('LATINSHOP', true);
require __DIR__ . '/config/config.php';

header('Content-Type: application/json');

$response = [
    "status" => "ok",
    "timestamp" => date("Y-m-d H:i:s"),
    "env" => defined('ENV') ? ENV : null,
    "checks" => []
];

$response["checks"]["config"] = file_exists(__DIR__ . '/config/config.php');

$constants = [
    'ROOT_PATH',
    'INCLUDES_PATH',
    'PAGES_PATH',
    'ASSETS_PATH',
    'SITE_URL',
    'SITE_NAME',
    'ENV'
];

foreach ($constants as $c) {
    $response["checks"]["constants"][$c] = defined($c);
}


$paths = [
    'ROOT_PATH',
    'INCLUDES_PATH',
    'PAGES_PATH',
    'ASSETS_PATH'
];

foreach ($paths as $p) {
    if (defined($p)) {
        $val = constant($p);
        $response["checks"]["paths"][$p] = is_dir($val);
    } else {
        $response["checks"]["paths"][$p] = false;
    }
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $response["checks"]["database"] = true;

} catch (Exception $e) {
    $response["checks"]["database"] = false;
    $response["errors"]["database"] = $e->getMessage();
}

try {
    if (class_exists('Redis')) {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);

        if (defined('REDIS_PASS') && REDIS_PASS) {
            $redis->auth(REDIS_PASS);
        }

        $response["checks"]["redis"] = true;
    } else {
        $response["checks"]["redis"] = false;
        $response["errors"]["redis"] = "Redis extension not installed";
    }
} catch (Exception $e) {
    $response["checks"]["redis"] = false;
    $response["errors"]["redis"] = $e->getMessage();
}

foreach ($response["checks"] as $key => $value) {
    if (is_array($value)) {
        foreach ($value as $v) {
            if (!$v) {
                $response["status"] = "error";
            }
        }
    } else {
        if (!$value) {
            $response["status"] = "error";
        }
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
