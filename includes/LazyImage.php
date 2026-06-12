<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * LazyImage
 *
 * Helper para generar etiquetas <img> con lazy loading consistente.
 *
 * Estrategia doble:
 *  1. `loading="lazy"` nativo (soportado en todos los browsers modernos)
 *  2. `data-src` + class `ls-lazy` para IntersectionObserver (lazy-load.js)
 *
 * Uso básico:
 *   <?= LazyImage::img('uploads/foto.jpg', 'Descripción') ?>
 *
 * Con opciones:
 *   <?= LazyImage::img($src, $alt, [
 *       'class'    => 'cuenta-card__img',
 *       'width'    => 400,
 *       'height'   => 300,
 *       'fallback' => ASSETS_URL . '/images/placeholder.webp',
 *       'eager'    => false,   // true = cargar inmediatamente (above-the-fold)
 *       'srcset'   => '400w img-400.jpg, 800w img-800.jpg',
 *       'sizes'    => '(max-width:600px) 100vw, 50vw',
 *   ]) ?>
 *
 * Avatar (wrapper con fallback a iniciales):
 *   <?= LazyImage::avatar($avatarUrl, $username, 'post-avatar') ?>
 */
final class LazyImage
{
    /**
     * Genera una etiqueta <img> con lazy loading.
     *
     * @param string $src  URL de la imagen
     * @param string $alt  Texto alternativo
     * @param array  $opts Opciones adicionales
     */
    public static function img(string $src, string $alt = '', array $opts = []): string
    {
        $eager    = $opts['eager']    ?? false;
        $class    = $opts['class']    ?? '';
        $width    = $opts['width']    ?? null;
        $height   = $opts['height']  ?? null;
        $fallback = $opts['fallback'] ?? '';
        $srcset   = $opts['srcset']  ?? '';
        $sizes    = $opts['sizes']   ?? '';
        $extra    = $opts['attrs']   ?? '';       // atributos HTML extra como string

        // Escape
        $srcSafe      = htmlspecialchars($src,      ENT_QUOTES, 'UTF-8');
        $altSafe      = htmlspecialchars($alt,      ENT_QUOTES, 'UTF-8');
        $classSafe    = htmlspecialchars($class,    ENT_QUOTES, 'UTF-8');
        $fallbackSafe = htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8');

        if ($eager) {
            // Above-the-fold: carga inmediata, sin clase ls-lazy
            $tag = '<img src="' . $srcSafe . '"';
            $tag .= ' alt="' . $altSafe . '"';
            $tag .= ' loading="eager" decoding="async"';
            if ($classSafe) $tag .= ' class="' . $classSafe . '"';
            if ($width)     $tag .= ' width="' . (int)$width . '"';
            if ($height)    $tag .= ' height="' . (int)$height . '"';
            if ($srcset)    $tag .= ' srcset="' . htmlspecialchars($srcset, ENT_QUOTES, 'UTF-8') . '"';
            if ($sizes)     $tag .= ' sizes="'  . htmlspecialchars($sizes,  ENT_QUOTES, 'UTF-8') . '"';
            if ($fallback)  $tag .= ' onerror="this.onerror=null;this.src=\'' . $fallbackSafe . '\'"';
            if ($extra)     $tag .= ' ' . $extra;
            $tag .= '>';
            return $tag;
        }

        // Lazy loading: data-src + loading="lazy" + clase ls-lazy
        $placeholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 3"%3E%3C/svg%3E';

        $classes = trim('ls-lazy ' . $classSafe);

        $tag  = '<img src="' . $placeholder . '"';
        $tag .= ' data-src="' . $srcSafe . '"';
        $tag .= ' alt="' . $altSafe . '"';
        $tag .= ' loading="lazy" decoding="async"';
        $tag .= ' class="' . $classes . '"';
        if ($width)     $tag .= ' width="'  . (int)$width  . '"';
        if ($height)    $tag .= ' height="' . (int)$height . '"';
        if ($srcset)    $tag .= ' data-srcset="' . htmlspecialchars($srcset, ENT_QUOTES, 'UTF-8') . '"';
        if ($sizes)     $tag .= ' data-sizes="'  . htmlspecialchars($sizes,  ENT_QUOTES, 'UTF-8') . '"';
        if ($fallback)  $tag .= ' data-fallback="' . $fallbackSafe . '"';
        if ($extra)     $tag .= ' ' . $extra;
        $tag .= '>';

        return $tag;
    }

    /**
     * Genera un avatar con fallback a iniciales si la URL está vacía.
     *
     * @param string|null $url       URL del avatar
     * @param string      $username  Nombre de usuario (para iniciales de fallback)
     * @param string      $class     Clase CSS
     * @param int         $size      Tamaño en px (para width/height)
     * @param bool        $eager     Cargar de inmediato (above-the-fold)
     */
    public static function avatar(
        ?string $url,
        string  $username = '',
        string  $class    = '',
        int     $size     = 38,
        bool    $eager    = false
    ): string {
        $resolved = $url ?: self::defaultAvatar($username);

        return self::img($resolved, htmlspecialchars($username, ENT_QUOTES, 'UTF-8'), [
            'class'  => $class,
            'width'  => $size,
            'height' => $size,
            'eager'  => $eager,
        ]);
    }

    /**
     * Genera una etiqueta <picture> con lazy loading.
     * Permite WebP + JPG fallback.
     *
     * @param array  $sources  [['src' => '...webp', 'type' => 'image/webp'], ...]
     * @param string $fallback URL de la imagen <img> de respaldo
     * @param string $alt
     * @param array  $opts     Mismas opciones que img()
     */
    public static function picture(array $sources, string $fallback, string $alt = '', array $opts = []): string
    {
        $eager = $opts['eager'] ?? false;
        $html  = '<picture>';

        foreach ($sources as $s) {
            $srcSafe  = htmlspecialchars($s['src'],  ENT_QUOTES, 'UTF-8');
            $typeSafe = htmlspecialchars($s['type'] ?? 'image/webp', ENT_QUOTES, 'UTF-8');
            $media    = !empty($s['media']) ? ' media="' . htmlspecialchars($s['media'], ENT_QUOTES, 'UTF-8') . '"' : '';

            if ($eager) {
                $html .= '<source srcset="' . $srcSafe . '" type="' . $typeSafe . '"' . $media . '>';
            } else {
                $html .= '<source data-srcset="' . $srcSafe . '" type="' . $typeSafe . '"' . $media . '>';
            }
        }

        $html .= self::img($fallback, $alt, $opts);
        $html .= '</picture>';

        return $html;
    }

    /**
     * Genera un div con background-image lazy.
     *
     * @param string $url     URL de la imagen de fondo
     * @param string $class   Clase CSS
     * @param string $content HTML interno del div
     * @param array  $attrs   Atributos HTML adicionales ['style' => '...', 'id' => '...']
     */
    public static function bg(string $url, string $class = '', string $content = '', array $attrs = []): string
    {
        $urlSafe   = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $classSafe = htmlspecialchars(trim('ls-lazy-bg ' . $class), ENT_QUOTES, 'UTF-8');

        $attrStr = '';
        foreach ($attrs as $k => $v) {
            $attrStr .= ' ' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8')
                      . '="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<div class="' . $classSafe . '" data-bg="' . $urlSafe . '"' . $attrStr . '>'
             . $content
             . '</div>';
    }


    /**
     * Genera URL de avatar por defecto con iniciales (UI Avatars).
     * Si no tiene conexión a internet, devuelve un SVG inline.
     */
    private static function defaultAvatar(string $username): string
    {
        if ($username === '') {
            return 'data:image/svg+xml,' . rawurlencode(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">'
                . '<circle cx="20" cy="20" r="20" fill="#2d2d4e"/>'
                . '<text x="20" y="25" text-anchor="middle" font-size="16" fill="#aaa">?</text>'
                . '</svg>'
            );
        }

        $initials = strtoupper(mb_substr($username, 0, 1));
        $colors   = ['1a7a4a','2563eb','7c3aed','be185d','b45309','0f766e'];
        $color    = $colors[abs(crc32($username)) % count($colors)];

        return 'data:image/svg+xml,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">'
            . '<circle cx="20" cy="20" r="20" fill="#' . $color . '"/>'
            . '<text x="20" y="26" text-anchor="middle" font-size="18" font-weight="bold" '
            . 'font-family="system-ui,sans-serif" fill="#fff">' . htmlspecialchars($initials, ENT_XML1) . '</text>'
            . '</svg>'
        );
    }
}
