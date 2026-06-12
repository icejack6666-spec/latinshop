<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');
if (!isset($prox_title))     $prox_title     = 'Próximamente';
if (!isset($prox_icon))      $prox_icon      = '<path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,17V16H9V14H13V13H10A1,1 0 0,1 9,12V9A1,1 0 0,1 10,8H11V7H13V8H15V10H11V11H14A1,1 0 0,1 15,12V15A1,1 0 0,1 14,16H13V17H11Z"/>';
if (!isset($prox_desc))      $prox_desc      = 'Estamos trabajando en esta sección. Vuelve pronto.';
if (!isset($prox_back_url))  $prox_back_url  = '/';
if (!isset($prox_back_label)) $prox_back_label = 'Volver al inicio';
?>

<section class="section section--page-start prox-section">
    <div class="tbt-wrap">
        <div class="prox-wrap reveal is-visible">

            <div class="prox-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <?= $prox_icon ?>
                </svg>
            </div>

            <div class="badge badge--accent">
                <span class="badge__dot"></span>
                En desarrollo
            </div>

            <h1 class="prox-title"><?= htmlspecialchars($prox_title, ENT_QUOTES, 'UTF-8') ?></h1>

            <p class="prox-desc"><?= htmlspecialchars($prox_desc, ENT_QUOTES, 'UTF-8') ?></p>

            <div class="prox-actions">
                <a href="<?= u(htmlspecialchars($prox_back_url, ENT_QUOTES, 'UTF-8')) ?>" class="tbt-btn tbt-btn--outline">
                    ← <?= htmlspecialchars($prox_back_label, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a href="<?= u('/contacto') ?>" class="tbt-btn tbt-btn--jade">Contactar</a>
            </div>

        </div>
    </div>
</section>

<style>
.prox-section {
    min-height: 70vh;
    display: flex;
    align-items: center;
    background: var(--bg-alt);
}
.prox-wrap {
    max-width: 560px;
    text-align: center;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
}
.prox-icon {
    width: 90px; height: 90px;
    background: var(--accent-soft);
    border: 1px solid rgba(232,96,44,.3);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--accent);
    box-shadow: 0 0 30px rgba(232,96,44,.15);
    animation: iconFloat 3s ease-in-out infinite;
}
@keyframes iconFloat {
    0%,100% { transform: translateY(0); box-shadow: 0 0 30px rgba(232,96,44,.15); }
    50%      { transform: translateY(-8px); box-shadow: 0 12px 40px rgba(232,96,44,.25); }
}
.prox-title {
    font-family: var(--font-display);
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 700;
    color: var(--white);
    line-height: 1.1;
    letter-spacing: -0.03em;
}
.prox-desc {
    font-size: 1.05rem;
    color: var(--gray-400);
    line-height: 1.6;
    max-width: 440px;
}
.prox-actions {
    display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;
    margin-top: 0.5rem;
}
</style>
