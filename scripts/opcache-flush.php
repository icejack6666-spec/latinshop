#!/usr/bin/env php
<?php

define('LATINSHOP', true);

$rootPath = dirname(__DIR__) . '/latinshop';

if (!file_exists($rootPath . '/config/config.php')) {
    fwrite(STDERR, "[ERROR] config.php no encontrado en: {$rootPath}\n");
    exit(1);
}

require_once $rootPath . '/config/config.php';
require_once INCLUDES_PATH . '/OPcacheService.php';

$opcache = new OPcacheService();

echo "[" . date('Y-m-d H:i:s') . "] OPcache post-deploy flush\n";
echo "PHP Version: " . phpversion() . "\n";
echo "OPcache disponible: " . ($opcache->isAvailable() ? 'SÍ' : 'NO') . "\n";
echo "OPcache CLI:        " . ($opcache->isAvailableCli() ? 'SÍ' : 'NO') . "\n\n";

if (!$opcache->isAvailable() && !$opcache->isAvailableCli()) {
    echo "[WARN] OPcache no disponible. Nada que vaciar.\n";
    exit(0);
}

// Paso 1: Flush
echo "→ Vaciando caché... ";
$flushed = $opcache->flush();
echo ($flushed ? "OK\n" : "FALLO (verifica opcache.enable_cli)\n");

// Paso 2: Warmup
echo "→ Precargando archivos críticos... ";
$result = $opcache->warmup();
echo "OK\n";
echo "   Compilados: {$result['compiled']}\n";
echo "   Fallidos:   {$result['failed']}\n";
echo "   Omitidos:   {$result['skipped']}\n";

// Paso 3: Advertencias
$warnings = $opcache->getConfigWarnings();
if (!empty($warnings)) {
    echo "\n[ADVERTENCIAS]\n";
    foreach ($warnings as $w) {
        echo "  ⚠  {$w}\n";
    }
}

echo "\n[DONE] " . date('Y-m-d H:i:s') . "\n";
exit(0);
