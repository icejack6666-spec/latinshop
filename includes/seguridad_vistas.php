<?php
/**
 * ============================================
 * SEGURIDAD_VISTAS.PHP
 * Latin Shop
 * ============================================
 * Guards de seguridad para vistas y rutas.
 *
 * NOTA: La clase Minifier fue eliminada en el Paso 8.
 * Razones:
 *  - El minificado de HTML por regex es frágil y rompe JS/CSS inline.
 *  - Apache ya comprime con mod_deflate (gzip/brotli), que es
 *    significativamente más eficiente que minificar en PHP.
 *  - OPcache (Paso 6) ya elimina el overhead de parsear PHP.
 *  - Redis (Paso 7) cachea los datos más costosos.
 *  - La inyección de protection.js se delegó a footer.php.
 * ============================================
 */

if (!defined('LATINSHOP')) {
    die('Acceso directo no permitido.');
}
