<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$current = trim(strtok(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '?'), '/');
$script_dir_s = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($script_dir_s !== '' && strpos($current, ltrim($script_dir_s, '/')) === 0) {
    $current = substr($current, strlen(ltrim($script_dir_s, '/')));
}
$current = trim($current, '/');

$nav = [
    ['url' => 'admin',               'label' => 'Dashboard',    'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
    ['url' => 'admin/usuarios',      'label' => 'Usuarios',     'icon' => 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z'],
    ['url' => 'admin/comentarios',   'label' => 'Comentarios',  'icon' => 'M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z'],
    ['url' => 'admin/seguridad', 'label' => 'Seguridad', 'icon' => 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z'],
    ['url' => 'admin/cuentas',   'label' => 'Cuentas',   'icon' => 'M21 6.5l-4-4-8.5 8.5-2 4 4-2L21 6.5zm-14 9l-2 1 1-2 1 1z'],
    ['url'   => 'admin/tickets', 'label' => 'Soporte', 'icon'  => 'M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z'],
    ['url' => 'admin/backups',       'label' => 'Backups',      'icon' => 'M20 6h-2.18c.07-.44.18-.88.18-1.36C18 2.52 15.48 0 12 0S6 2.52 6 4.64c0 .48.11.92.18 1.36H4c-1.11 0-2 .89-2 2v11c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-8-4c1.66 0 3 1.34 3 3 0 .51-.17.96-.4 1.36H9.4C9.17 6.01 9 5.56 9 5c0-1.66 1.34-3 3-3zm8 17H4v-2h16v2zm0-5H4v-6h4.08l-1.08 1 1.42 1.41L11 8.83V15h2V8.83l2.58 2.58L17 10l-1.08-1H20v6z'],
    ['url' => 'admin/opcache',       'label' => 'OPcache',      'icon' => 'M13 2.05v2.02c3.95.49 7 3.85 7 7.93 0 3.21-1.81 6-4.72 7.28L13 17v5h5l-1.22-1.22C19.91 19.07 22 15.76 22 12c0-5.18-3.95-9.45-9-9.95zM11 2.05C5.95 2.55 2 6.82 2 12c0 3.76 2.09 7.07 5.22 8.78L6 22h5V2.05zM11 17H9.34l1.66 1.66V17z'],
    ];

?>
<aside class="admin-sidebar">
    <span class="admin-sidebar__label">Panel</span>
    <?php foreach ($nav as $item): ?>
        <a href="<?= u('/' . $item['url']) ?>"
           class="admin-sidebar__link <?= $current === $item['url'] ? 'admin-sidebar__link--active' : '' ?>">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
                <path d="<?= $item['icon'] ?>"/>
            </svg>
            <?= $item['label'] ?>
        </a>
    <?php endforeach; ?>

    <span class="admin-sidebar__label" style="margin-top: auto;">Cuenta</span>
    <a href="<?= u('/') ?>" class="admin-sidebar__link">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
        </svg>
        Ir al sitio
    </a>
    <a href="<?= u('/logout') ?>" class="admin-sidebar__link" style="color: #f87171;"
       onclick="return confirm('¿Cerrar sesión?')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
        </svg>
        Cerrar sesión
    </a>
</aside>
