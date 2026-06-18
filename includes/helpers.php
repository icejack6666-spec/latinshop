<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * ============================================================
 * helpers.php — Utilidades globales del proyecto
 * ============================================================
 *
 * Funciones puras sin dependencias externas que se usan en
 * múltiples capas (páginas, servicios, scripts CLI).
 *
 * Cargado por index.php y api/v1/index.php justo después de
 * security.php, antes de cualquier clase que lo necesite.
 * ============================================================
 */


// ─── formatBytes ─────────────────────────────────────────────────────────────

/**
 * Convierte un número de bytes a cadena legible (B / KB / MB / GB).
 *
 * Precisión: 2 decimales. Cubre hasta TB para futura compatibilidad.
 *
 * Ejemplos:
 *   format_bytes(512)              → "512 B"
 *   format_bytes(2048)             → "2.00 KB"
 *   format_bytes(5 * 1024 * 1024)  → "5.00 MB"
 *   format_bytes(2 * 1073741824)   → "2.00 GB"
 */
function format_bytes(int $bytes, int $decimals = 2): string
{
    if ($bytes >= 1_099_511_627_776) {
        return round($bytes / 1_099_511_627_776, $decimals) . ' TB';
    }
    if ($bytes >= 1_073_741_824) {
        return round($bytes / 1_073_741_824, $decimals) . ' GB';
    }
    if ($bytes >= 1_048_576) {
        return round($bytes / 1_048_576, $decimals) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, $decimals) . ' KB';
    }
    return $bytes . ' B';
}


// ─── getRoleBadge ─────────────────────────────────────────────────────────────

/**
 * Devuelve los metadatos de visualización para un rol de usuario.
 *
 * @param string $role     Rol del usuario ('admin', 'verified', 'client',
 *                         'pending', 'banned').
 * @param string $context  Prefijo CSS del contexto de uso:
 *                           'hdr'   → header del sitio  (hdr-badge--*)
 *                           'admin' → panel admin       (admin-badge--*)
 *                           'pf'    → perfil de usuario (badge--*)
 *                         Por defecto 'hdr'.
 *
 * @return array{label: string, class: string, desc: string}
 *   - label : texto visible del badge
 *   - class : sufijo completo de clase CSS listo para usar
 *   - desc  : descripción larga del rol (útil en la página de perfil)
 */
function get_role_badge(string $role, string $context = 'hdr'): array
{
    // ── Fuente de verdad de etiquetas y descripciones ──────────────────────
    $roles = [
        'admin' => [
            'label' => 'Admin',
            'desc'  => 'Tienes acceso total al panel de administración.',
            'color' => 'purple',
        ],
        'verified' => [
            'label' => 'Cliente Verificado',
            'desc'  => 'Cuenta verificada con teléfono. Acceso al foro y comentarios.',
            'color' => 'blue',
        ],
        'client' => [
            'label' => 'Usuario',
            'desc'  => 'Puedes comentar en páginas y acceder al foro.',
            'color' => 'green',
        ],
        'pending' => [
            'label' => 'Pendiente',
            'desc'  => 'Tu cuenta está esperando aprobación de un administrador.',
            'color' => 'amber',
        ],
        'banned' => [
            'label' => 'Suspendido',
            'desc'  => 'Tu cuenta ha sido suspendida. Contacta al administrador.',
            'color' => 'red',
        ],
    ];

    // ── Prefijos CSS por contexto ──────────────────────────────────────────
    $prefixes = [
        'hdr'   => 'hdr-badge--',
        'admin' => 'admin-badge--',
        'pf'    => 'badge--',
    ];

    $prefix = $prefixes[$context] ?? $prefixes['hdr'];

    if (!isset($roles[$role])) {
        // Rol desconocido: devuelve el valor crudo sin clase de color
        return ['label' => $role, 'class' => '', 'desc' => ''];
    }

    $data = $roles[$role];

    return [
        'label' => $data['label'],
        'class' => $prefix . $data['color'],
        'desc'  => $data['desc'],
    ];
}
