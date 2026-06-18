<?php
if (!defined('LATINSHOP')) {
    define('LATINSHOP', true);
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/seguridad_vistas.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Auth.php';
require_once INCLUDES_PATH . '/Comments.php';

secure_session_start();

$_auth        = Auth::getInstance();
$_logueado    = $_auth->isLoggedIn();
$_user_header = $_logueado ? $_auth->getUser() : null;

$nombres = [
    'login'           => 'Inicio de Sesión',
    'register'        => 'Registro de Usuarios',
    'forgot_password' => 'Recuperación de Contraseña',
    'profile'         => 'Mi Perfil',
    'cuentas'         => 'Tienda de Cuentas',
    'bots'            => 'Bots',
    'support'         => 'Soporte',
];
$seccion = $nombres[$_GET['s'] ?? ''] ?? 'Esta sección';

$page_title       = 'Próximamente — ' . $seccion . ' | Latin Shop';
$page_description = $seccion . ' estará disponible muy pronto en Latin Shop.';

include INCLUDES_PATH . '/header.php';
?>

<style>
.prox-wrap {
    min-height: 70vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 4rem 1.5rem;
    gap: 1.5rem;
}

.prox-badge {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    font-size: .75rem;
    font-weight: 700;
    font-family: var(--tbt-font-mono);
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--tbt-jade-light);
    background: var(--tbt-jade-08);
    border: 1px solid var(--tbt-jade-30);
    border-radius: var(--tbt-r-full);
    padding: .35rem 1rem;
}
.prox-badge::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--tbt-jade);
    animation: prox-pulse 1.8s ease-in-out infinite;
}
@keyframes prox-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: .4; transform: scale(.7); }
}

.prox-title {
    font-family: var(--tbt-font-display);
    font-size: clamp(2.4rem, 7vw, 4.5rem);
    font-weight: 400;
    color: var(--tbt-txt-white);
    letter-spacing: .03em;
    line-height: 1.05;
    margin: 0;
}
.prox-title span {
    color: var(--tbt-jade);
}

.prox-desc {
    font-size: 1rem;
    color: var(--tbt-txt-sub);
    max-width: 480px;
    line-height: 1.65;
    margin: 0;
}

.prox-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: .5rem;
}
.prox-btn {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .65rem 1.4rem;
    border-radius: var(--tbt-r-md);
    font-size: .88rem;
    font-weight: 700;
    text-decoration: none;
    transition: opacity .2s, transform .2s;
}
.prox-btn:hover { opacity: .85; transform: translateY(-1px); }
.prox-btn--primary {
    background: var(--tbt-jade);
    color: #fff;
}
.prox-btn--ghost {
    background: transparent;
    color: var(--tbt-txt-sub);
    border: 1px solid var(--tbt-bg-5);
}
.prox-btn--ghost:hover { color: var(--tbt-txt-white); border-color: var(--tbt-bg-6); }

/* Decoración de fondo */
.prox-deco {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 0;
}
.prox-deco::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -60%);
    width: min(700px, 120vw);
    height: min(700px, 120vw);
    border-radius: 50%;
    background: radial-gradient(circle, rgba(232,96,44,.06) 0%, transparent 70%);
}
.prox-wrap { position: relative; z-index: 1; }
</style>

<section style="position:relative;">
    <div class="prox-deco"></div>
    <div class="prox-wrap">

        <span class="prox-badge">En desarrollo</span>

        <h1 class="prox-title">
            <?= htmlspecialchars($seccion, ENT_QUOTES, 'UTF-8') ?><br>
            <span>Próximamente</span>
        </h1>

        <p class="prox-desc">
            Estamos trabajando en esta sección para traerte la mejor experiencia.
            Vuelve pronto, ¡va a valer la pena!
        </p>

        <div class="prox-actions">
            <a href="<?= u() ?>" class="prox-btn prox-btn--primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Ir al inicio
            </a>
            <a href="<?= u('/contacto') ?>" class="prox-btn prox-btn--ghost">
                Contactar
            </a>
        </div>

    </div>
</section>

<?php include INCLUDES_PATH . '/footer.php'; ?>
