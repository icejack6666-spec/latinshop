<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) {
    die('Acceso directo no permitido.');
}

/**
 * AssetManifest
 *
 * Lee el manifest.json generado por Vite (`vite build`)
 * y resuelve las URLs de assets con hash de contenido.
 *
 * ─── Cómo funciona ────────────────────────────────────────────────────────────
 * Vite genera:  frontend/assets/dist/manifest.json
 *
 * Ejemplo de contenido:
 * {
 *   "build/entrypoints/app.js": {
 *     "file": "app-Bq3KkYkA.js",
 *     "css":  ["app-DcXKoP7A.css"],
 *     "isEntry": true
 *   },
 *   "frontend/assets/js/gems-calc.js": {
 *     "file": "gems-calc-CvxPpn9g.js",
 *     "isEntry": true
 *   }
 * }
 *
 * ─── Fallback ─────────────────────────────────────────────────────────────────
 * Si el manifest no existe (entorno sin build, desarrollo sin correr npm run build),
 * devuelve la ruta original sin hash. Esto garantiza que el sitio no se rompa
 * si alguien deployó sin ejecutar el build.
 *
 * ─── Uso en header.php / footer.php ──────────────────────────────────────────
 * // Obtener URL del CSS global:
 * $css_url = AssetManifest::css('app');
 * // → "https://tu-dominio/latinshop/frontend/assets/dist/app-Bq3KkYkA.css"
 *
 * // Obtener URL del JS:
 * $js_url = AssetManifest::js('gems-calc');
 * // → "https://tu-dominio/latinshop/frontend/assets/dist/gems-calc-CvxPpn9g.js"
 *
 * // Emitir <link> y <script> listos para incrustar:
 * echo AssetManifest::linkTag('app');     // <link rel="stylesheet" href="...">
 */
final class AssetManifest
{
    /** Ruta al manifest.json generado por Vite */
    private const MANIFEST_PATH = '/frontend/assets/dist/.vite/manifest.json';

    /** Sub-URL del directorio dist */
    private const DIST_URL = '/frontend/assets/dist/';

    /** @var array<string, array>|null */
    private static ?array $manifest = null;

    private static bool $loaded = false;


    /**
     * URL del archivo JS de un entrypoint.
     *
     * @param string $entry  Nombre del entrypoint: 'app', 'gems-calc', etc.
     */
    public static function js(string $entry): string
    {
        $data = self::resolveEntry($entry);
        if ($data === null) {
            return self::fallbackJs($entry);
        }
        return self::distUrl($data['file']);
    }

    /**
     * URL(s) del CSS de un entrypoint (Vite puede emitir varios archivos CSS).
     * Devuelve array vacío si el entrypoint no tiene CSS.
     *
     * @return string[]
     */
    public static function cssFiles(string $entry): array
    {
        $data = self::resolveEntry($entry);
        if ($data === null || empty($data['css'])) {
            return [];
        }
        return array_map(fn($f) => self::distUrl($f), $data['css']);
    }

    /**
     * URL del primer (y normalmente único) archivo CSS de un entrypoint.
     * Devuelve la ruta original sin hash como fallback.
     */
    public static function css(string $entry): string
    {
        $files = self::cssFiles($entry);
        if (!empty($files)) {
            return $files[0];
        }
        return self::fallbackCss($entry);
    }

    /**
     * Emite todos los <link rel="stylesheet"> de un entrypoint.
     * Si hay varios archivos CSS, los emite todos.
     */
    public static function linkTag(string $entry): string
    {
        $files = self::cssFiles($entry);

        if (empty($files)) {
            // Fallback: ruta original sin hash
            $href = self::fallbackCss($entry);
            return '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        $html = '';
        foreach ($files as $href) {
            $html .= '<link rel="stylesheet" href="'
                . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
                . '">' . "\n";
        }
        return $html;
    }

    /**
     * Emite un <script src="..." defer></script> para un entrypoint JS.
     */
    public static function scriptTag(string $entry, bool $defer = true, bool $module = false): string
    {
        $src    = self::js($entry);
        $attrs  = $defer  ? ' defer'  : '';
        $attrs .= $module ? ' type="module"' : '';

        return '<script src="'
            . htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
            . '"' . $attrs . '></script>' . "\n";
    }

    /**
     * Indica si el manifest existe y fue cargado correctamente.
     * Útil para mostrar un aviso en el panel admin si el build no se ejecutó.
     */
    public static function isBuilt(): bool
    {
        self::load();
        return self::$manifest !== null;
    }


    /**
     * Carga el manifest.json (solo una vez por request, con memoización).
     */
    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $path = ROOT_PATH . self::MANIFEST_PATH;

        if (!file_exists($path)) {
            self::$manifest = null;
            return;
        }

        try {
            $json = file_get_contents($path);
            if ($json === false) {
                self::$manifest = null;
                return;
            }
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            self::$manifest = is_array($decoded) ? $decoded : null;
        } catch (\JsonException $e) {
            error_log('[AssetManifest] manifest.json inválido: ' . $e->getMessage());
            self::$manifest = null;
        }
    }

    /**
     * Resuelve la entrada del manifest para un entrypoint dado.
     * Acepta tanto el nombre corto ('app') como la clave completa
     * ('build/entrypoints/app.js' o 'frontend/assets/js/gems-calc.js').
     */
    private static function resolveEntry(string $entry): ?array
    {
        self::load();

        if (self::$manifest === null) {
            return null;
        }

        $candidates = [
            $entry,
            "build/entrypoints/{$entry}.js",
            "frontend/assets/js/{$entry}.js",
            "frontend/assets/css/{$entry}.css",
        ];

        foreach ($candidates as $key) {
            if (isset(self::$manifest[$key])) {
                return self::$manifest[$key];
            }
        }

        return null;
    }

    /**
     * Construye la URL completa al directorio dist/.
     */
    private static function distUrl(string $filename): string
    {
        return SITE_URL . self::DIST_URL . $filename;
    }

    /**
     * URL de fallback para JS (sin hash, ruta original).
     */
    private static function fallbackJs(string $entry): string
    {
        return ASSETS_URL . '/js/' . $entry . '.js';
    }

    /**
     * URL de fallback para CSS (sin hash, ruta original).
     */
    private static function fallbackCss(string $entry): string
    {
        return ASSETS_URL . '/css/' . $entry . '.css';
    }
}
