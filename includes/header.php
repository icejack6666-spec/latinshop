<?php
/**
 * ============================================
 * HEADER.PHP - Cabecera reutilizable
 * ============================================
 * Uso: include INCLUDES_PATH . '/header.php';
 * Variables opcionales antes de incluir:
 *   $page_title       - Título de la página
 *   $page_description - Meta description
 *   $page_canonical   - URL canónica
 *   $og_image         - Imagen Open Graph
 */

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$page_title       = $page_title       ?? 'Latin Shop | Lords Mobile';
$page_description = $page_description ?? 'Servicios premium para Lords Mobile. Bots, gemas, recursos y herramientas gratuitas.';
$page_canonical   = $page_canonical   ?? SITE_URL . $_SERVER['REQUEST_URI'];
$og_image         = $og_image         ?? ASSETS_URL . '/images/og-default.png';

$page_title_safe       = htmlspecialchars($page_title,       ENT_QUOTES, 'UTF-8');
$page_description_safe = htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8');
$page_canonical_safe   = htmlspecialchars($page_canonical,   ENT_QUOTES, 'UTF-8');
$og_image_safe         = htmlspecialchars($og_image,         ENT_QUOTES, 'UTF-8');

$_auth         = Auth::getInstance();
$_logueado     = $_auth->isLoggedIn();
$_user_header  = $_logueado ? $_auth->getUser() : null;

$nav_items = [
    ...( feature('bots') ? [[
        'label'    => 'Bots',
        'url'      => u('/bots'),
        'class'    => 'tbt-header__link--accent',
        'dropdown' => [
            ['label' => 'Bot Farming',  'url' => u('/bots/bot-farming')],
            ['label' => 'Bot WhatsApp', 'url' => u('/bots/bot-whatsapp')],
        ],
    ]] : []),

    [
        'label'    => 'utilidades',
        'url'      => u('/utilidades'),
        'class'    => 'tbt-header__link',
        'dropdown' => array_filter([
            ['label' => 'Calculadora de Gemas', 'url' => u('/gems')],
        ])
    ],

    ...( feature('cuentas') ? [[
        'label' => 'Cuentas',
        'url'   => u('/cuentas'),
        'class' => 'tbt-header__link',
    ]] : []),

];

$current_path = strtok($_SERVER['REQUEST_URI'], '?');
$script_dir_h = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($script_dir_h !== '' && strpos($current_path, $script_dir_h) === 0) {
    $current_path_rel = substr($current_path, strlen($script_dir_h));
} else {
    $current_path_rel = $current_path;
}

// ─── RESOLVER ASSETS CON HASH VIA ASSETMANIFEST ──────────────────────────────
// Si el build de Vite existe  → URLs con hash  (cache busting permanente)
// Si NO existe el manifest    → URLs sin hash   (fallback transparente)
$_is_admin_route   = str_starts_with($request_path ?? '', 'admin');
$_is_auth_route    = in_array($request_path ?? '', [
    'login','registrar','recuperar-password','verificar-telefono','verificar-2fa'
], true);
$_is_support_route = str_starts_with($request_path ?? '', 'support')
                  || in_array($request_path ?? '', ['admin/tickets','admin/ticket'], true);

// Determinar si $extra_css requiere el bundle 'support' o 'auth'
// (las páginas pueden sobreescribir $extra_css antes de include header.php)
$_extra_css_list = (array)($extra_css ?? []);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $page_title_safe ?></title>
    <meta name="description" content="<?= $page_description_safe ?>">
    <meta name="robots"      content="index, follow">
    <link rel="canonical"    href="<?= $page_canonical_safe ?>">
    <meta name="author"      content="Latin Shop">

    <link rel="icon"             type="image/png" href="<?= ASSETS_URL ?>/images/favicon.png">
    <link rel="apple-touch-icon" href="<?= ASSETS_URL ?>/assets/pwa/icon-192.png">

    <link rel="manifest" href="<?= rtrim(SITE_URL, '/') ?>/manifest.json">
    <meta name="theme-color" content="#e8602c">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LatinShop">

    <meta property="og:locale"      content="es_ES">
    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?= $page_title_safe ?>">
    <meta property="og:description" content="<?= $page_description_safe ?>">
    <meta property="og:url"         content="<?= $page_canonical_safe ?>">
    <meta property="og:site_name"   content="Latin Shop">
    <meta property="og:image"       content="<?= $og_image_safe ?>">

    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= $page_title_safe ?>">
    <meta name="twitter:description" content="<?= $page_description_safe ?>">
    <meta name="twitter:image"       content="<?= $og_image_safe ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=JetBrains+Mono:wght@400;500;700&display=swap"
          rel="stylesheet">

    <?= AssetManifest::linkTag('app') ?>

    <?php if ($_is_admin_route): ?>
        <?= AssetManifest::linkTag('admin') ?>
    <?php endif; ?>

    <?php if ($_is_auth_route): ?>
        <?= AssetManifest::linkTag('auth') ?>
    <?php endif; ?>

    <?php if ($_is_support_route || in_array('support.css', $_extra_css_list, true)): ?>
        <?= AssetManifest::linkTag('support') ?>
    <?php endif; ?>

    <?php
    $managed_bundles = ['admin.css','auth-epic.css','support.css'];
    foreach ($_extra_css_list as $css):
        if (in_array($css, $managed_bundles, true)) continue;
        $css_safe = htmlspecialchars($css, ENT_QUOTES, 'UTF-8');
    ?>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/<?= $css_safe ?>">
    <?php endforeach; ?>

    <?php
    if ($_is_admin_route && !AssetManifest::isBuilt()):
    ?>
    <style>
    .build-warning {
        position: fixed; bottom: 0; left: 0; right: 0; z-index: 99999;
        background: #7c2d12; color: #fed7aa; padding: .5rem 1rem;
        font-size: .8rem; font-family: monospace; text-align: center;
    }
    </style>
    <div class="build-warning">
        ⚠️ <strong>Build de assets no encontrado.</strong>
        Ejecuta <code>npm install &amp;&amp; npm run build</code> en la raíz del proyecto.
        Los assets se sirven sin minificar ni hashes (solo en desarrollo).
    </div>
    <?php endif; ?>

</head>
<body>

<header class="tbt-header" id="tbt-header">
    <div class="tbt-header__inner">

        <!-- Logo -->
        <a href="<?= u() ?>" class="tbt-header__logo">
            <img src="<?= ASSETS_URL ?>/images/logo.png" alt="Latin Shop" onerror="this.style.display='none'">
            <span class="tbt-header__logo-text">
                <span>Lords Mobile</span>
                Latin Shop
            </span>
        </a>

        <button class="tbt-header__toggle" id="tbt-toggle" type="button"
                aria-label="Abrir menú" aria-expanded="false" aria-controls="tbt-nav">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="tbt-header__nav" id="tbt-nav" role="navigation" aria-label="Navegación principal">
            <ul class="tbt-header__menu">
                <?php foreach ($nav_items as $item): ?>
                <li <?= !empty($item['dropdown']) ? 'data-has-dropdown' : '' ?>>
                    <?php
                    $item_path  = parse_url($item['url'], PHP_URL_PATH);
                    $is_active  = str_starts_with($current_path_rel, $item_path);
                    $extra_cls  = $is_active ? ' tbt-header__link--active' : '';
                    ?>
                    <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>"
                       class="tbt-header__link <?= $item['class'] ?? '' ?><?= $extra_cls ?>"
                       <?= $is_active ? 'aria-current="page"' : '' ?>>
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($item['dropdown'])): ?>
                        <svg class="tbt-header__arrow" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                            <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php endif; ?>
                    </a>
                    <?php if (!empty($item['dropdown'])): ?>
                    <ul class="tbt-header__dropdown" role="menu">
                        <?php foreach ($item['dropdown'] as $sub): ?>
                        <li role="menuitem">
                            <a href="<?= htmlspecialchars($sub['url'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($sub['label'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>

            <a href="<?= u('/contacto') ?>" class="tbt-header__cta">Contacto</a>

            <?php if ($_logueado && $_user_header): ?>


                <div class="hdr-user" id="hdr-user">
                    <button class="hdr-user__btn" id="hdr-user-btn" type="button"
                            aria-label="Menú de usuario" aria-expanded="false">
                        <?php if (!empty($_user_header['avatar'])): ?>
                            <img src="<?= htmlspecialchars($_user_header['avatar'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="" class="hdr-user__avatar">
                        <?php else: ?>
                            <div class="hdr-user__avatar hdr-user__avatar--default">
                                <?= Comments::getInitials($_user_header['username']) ?>
                            </div>
                        <?php endif; ?>
                        <span class="hdr-user__name">
                            <?= htmlspecialchars($_user_header['username'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <svg class="hdr-user__arrow" width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div class="hdr-user__dropdown" id="hdr-user-dropdown" role="menu">

                        <div class="hdr-user__info">
                            <span class="hdr-user__info-name">
                                <?= htmlspecialchars($_user_header['username'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="hdr-user__info-email">
                                <?= htmlspecialchars($_user_header['email'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php
                            $rb = get_role_badge($_user_header['role'], 'hdr');
                            ?>
                            <span class="hdr-badge <?= $rb['class'] ?>"><?= $rb['label'] ?></span>
                        </div>

                        <div class="hdr-user__divider"></div>

                        <?php if (feature('profile')): ?>
                        <a href="<?= u('/perfil') ?>" class="hdr-user__item" role="menuitem">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                            </svg>
                            Mi perfil
                        </a>
                        <?php endif; ?>

                        <?php if ($_user_header['role'] === 'admin'): ?>
                            <a href="<?= u('/admin') ?>" class="hdr-user__item hdr-user__item--admin" role="menuitem">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                                </svg>
                                Panel de administración
                            </a>
                        <?php endif; ?>

                        <div class="hdr-user__divider"></div>

                        <a href="<?= u('/logout') ?>"
                           class="hdr-user__item hdr-user__item--logout"
                           role="menuitem"
                           onclick="return confirm('¿Cerrar sesión?')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                            </svg>
                            Cerrar sesión
                        </a>

                    </div><!-- /hdr-user__dropdown -->
                </div><!-- /hdr-user -->

            <?php elseif (feature('login') || feature('register')): ?>

                <div class="hdr-auth">
                    <?php if (feature('login')): ?>
                    <a href="<?= u('/login') ?>" class="hdr-auth__login">Iniciar sesión</a>
                    <?php endif; ?>
                    <?php if (feature('register')): ?>
                    <a href="<?= u('/registrar') ?>" class="hdr-auth__registro">Registrarse</a>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </nav>

    </div>
</header>

<main class="tbt-site-content" id="main-content">

<style>
.hdr-auth { display:flex; align-items:center; gap:.5rem; margin-left:var(--tbt-s2); }
.hdr-auth__login { font-size:var(--tbt-text-sm); font-weight:600; color:var(--tbt-txt-light); text-decoration:none; padding:.4rem .9rem; border-radius:var(--tbt-r-md); border:1px solid var(--tbt-bg-5); transition:border-color var(--tbt-t1),color var(--tbt-t1); }
.hdr-auth__login:hover { border-color:var(--tbt-jade-40); color:var(--tbt-jade-light); }
.hdr-auth__registro { font-size:var(--tbt-text-sm); font-weight:700; color:#fff; text-decoration:none; padding:.4rem .9rem; border-radius:var(--tbt-r-md); background:var(--tbt-jade); transition:opacity var(--tbt-t1); }
.hdr-auth__registro:hover { opacity:.85; }
.hdr-user { position:relative; margin-left:var(--tbt-s2); }
.hdr-user__btn { display:flex; align-items:center; gap:.5rem; background:var(--tbt-bg-2); border:1px solid var(--tbt-bg-5); border-radius:var(--tbt-r-full); padding:.3rem .7rem .3rem .3rem; cursor:pointer; transition:border-color var(--tbt-t1); }
.hdr-user__btn:hover,.hdr-user__btn[aria-expanded="true"] { border-color:var(--tbt-jade-40); }
.hdr-user__avatar { width:28px; height:28px; border-radius:50%; object-fit:cover; border:2px solid var(--tbt-bg-4); flex-shrink:0; }
.hdr-user__avatar--default { width:28px; height:28px; border-radius:50%; background:var(--tbt-jade-15); border:2px solid var(--tbt-jade-30); display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:800; color:var(--tbt-jade-light); font-family:var(--tbt-font-mono); }
.hdr-user__name { font-size:var(--tbt-text-xs); font-weight:600; color:var(--tbt-txt-white); max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.hdr-user__arrow { color:var(--tbt-txt-muted); transition:transform var(--tbt-t1); flex-shrink:0; }
.hdr-user__btn[aria-expanded="true"] .hdr-user__arrow { transform:rotate(180deg); }
.hdr-user__dropdown { position:absolute; top:calc(100% + 8px); right:0; width:220px; background:var(--tbt-bg-2); border:1px solid var(--tbt-bg-5); border-radius:var(--tbt-r-lg); padding:.5rem 0; box-shadow:0 8px 32px rgba(0,0,0,.4); opacity:0; visibility:hidden; transform:translateY(-8px); transition:opacity var(--tbt-t2) var(--tbt-ease),transform var(--tbt-t2) var(--tbt-ease),visibility var(--tbt-t2); z-index:200; }
.hdr-user__dropdown.is-open { opacity:1; visibility:visible; transform:translateY(0); }
.hdr-user__info { padding:.6rem var(--tbt-s2) .5rem; display:flex; flex-direction:column; gap:.2rem; }
.hdr-user__info-name { font-size:var(--tbt-text-sm); font-weight:700; color:var(--tbt-txt-white); }
.hdr-user__info-email { font-size:var(--tbt-text-xs); color:var(--tbt-txt-muted); word-break:break-all; }
.hdr-badge { display:inline-block; font-size:9px; font-weight:700; font-family:var(--tbt-font-mono); padding:1px 7px; border-radius:var(--tbt-r-full); text-transform:uppercase; letter-spacing:.05em; margin-top:.2rem; width:fit-content; }
.hdr-badge--purple { background:var(--tbt-jade-15); color:var(--tbt-jade-light); border:1px solid var(--tbt-jade-30); }
.hdr-badge--green  { background:rgba(34,197,94,.1); color:#4ade80; border:1px solid rgba(34,197,94,.25); }
.hdr-badge--amber  { background:rgba(201,162,39,.1); color:#c9a227; border:1px solid rgba(201,162,39,.25); }
.hdr-badge--red    { background:rgba(239,68,68,.1); color:#f87171; border:1px solid rgba(239,68,68,.25); }
.hdr-user__divider { height:1px; background:var(--tbt-bg-4); margin:.4rem 0; }
.hdr-user__item { display:flex; align-items:center; gap:.6rem; padding:.5rem var(--tbt-s2); font-size:var(--tbt-text-sm); font-weight:500; color:var(--tbt-txt-sub); text-decoration:none; transition:background var(--tbt-t1),color var(--tbt-t1); }
.hdr-user__item:hover { background:var(--tbt-bg-3); color:var(--tbt-txt-white); }
.hdr-user__item--admin { color:var(--tbt-jade-light); }
.hdr-user__item--admin:hover { background:var(--tbt-jade-08); }
.hdr-user__item--logout { color:#f87171; }
.hdr-user__item--logout:hover { background:rgba(239,68,68,.08); }
.tbt-header__notif { position:relative; text-decoration:none; font-size:1.2rem; }
.tbt-notif-badge { position:absolute; top:-6px; right:-8px; background:#e53e3e; color:white; font-size:.65rem; font-weight:700; padding:2px 5px; border-radius:999px; min-width:16px; text-align:center; }
@media (max-width:768px) {
    .hdr-user { margin-left:0; margin-top:var(--tbt-s1); }
    .hdr-user__dropdown { position:static; transform:none; box-shadow:none; border:1px solid var(--tbt-bg-4); }
    .hdr-auth { margin-left:0; margin-top:var(--tbt-s1); }
}
</style>

<?php /* hdr-user dropdown JS → assets/header.js */ ?>

<button id="pwa-fab" type="button" aria-label="Instalar aplicación">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2v13M7 11l5 5 5-5"/><rect x="3" y="18" width="18" height="3" rx="1.5"/>
    </svg>
    Instalar App
</button>

<div id="pwa-manual-modal" role="dialog" aria-modal="true" aria-labelledby="pwa-modal-title">
    <div class="pwa-banner">
  📲 Instala la app para mejor experiencia
  <button>Instalar</button>
</div>
</div>