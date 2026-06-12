<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$page_title       = 'Utilidades Lords Mobile | Calculadoras y Herramientas | Latin Shop';
$page_description = 'Herramientas gratuitas para Lords Mobile: calculadoras de tropas, gemas, guías y más.';
$page_canonical   = SITE_URL . '/utilidades';

$utilidades = [
    ['url'=>'/gems',                              'title'=>'Calculadora de Gemas',     'desc'=>'Calcula cuántas gemas necesitas y optimiza tu gasto.',             'icon'=>'💎', 'cat'=>'calc'],
];

include INCLUDES_PATH . '/header.php';
?>

<section class="tbt-hero">
    <div class="tbt-wrap">
        <div class="tbt-enter" style="max-width:780px;">
            <p class="tbt-eyebrow tbt-eyebrow--jade tbt-mb-2">Herramientas gratuitas</p>
            <h1 class="tbt-h-xl tbt-mb-3">
                <br>
                <span class="tbt-jade">Utilidades Lords Mobile</span>
            </h1>
            <p class="tbt-body-lg">
                Calculadoras, guías y herramientas para optimizar tu juego sin costo.
            </p>
        </div>
    </div>
</section>

<section class="tbt-section" style="padding-top:0;">
    <div class="tbt-wrap">

        <div class="tbt-mb-5">
            <p class="tbt-eyebrow tbt-eyebrow--jade tbt-mb-3">⚙ Calculadoras</p>
            <div class="tbt-stagger" style="display:flex; flex-direction:column; gap:var(--tbt-s2);">
                <?php foreach ($utilidades as $u): if ($u['cat'] !== 'calc') continue; ?>
                <a href="<?= u(htmlspecialchars($u['url'], ENT_QUOTES, 'UTF-8')) ?>" class="tbt-entry">
                    <div class="tbt-entry__icon"><?= $u['icon'] ?></div>
                    <div>
                        <p class="tbt-entry__title"><?= htmlspecialchars($u['title'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="tbt-entry__desc"><?= htmlspecialchars($u['desc'],  ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="tbt-entry__arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/></svg>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <p class="tbt-eyebrow tbt-eyebrow--jade tbt-mb-3">📖 Guías y Herramientas</p>
            <div class="tbt-stagger" style="display:flex; flex-direction:column; gap:var(--tbt-s2);">
                <?php foreach ($utilidades as $u): if ($u['cat'] !== 'util') continue; ?>
                <a href="<?= u(htmlspecialchars($u['url'], ENT_QUOTES, 'UTF-8')) ?>" class="tbt-entry">
                    <div class="tbt-entry__icon"><?= $u['icon'] ?></div>
                    <div>
                        <p class="tbt-entry__title"><?= htmlspecialchars($u['title'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="tbt-entry__desc"><?= htmlspecialchars($u['desc'],  ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="tbt-entry__arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/></svg>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>

<section class="tbt-section tbt-section--alt">
    <div class="tbt-wrap">
        <div class="tbt-cta-box tbt-reveal">
            <div>
                <h2 class="tbt-h-md tbt-mb-2">¿Tienes alguna duda?</h2>
                <p class="tbt-body-md">Contáctanos y te respondemos en minutos.</p>
            </div>
            <div class="tbt-cta-box__actions">
                <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>"
                   target="_blank" rel="noopener noreferrer" class="tbt-btn tbt-btn--wa">WhatsApp</a>
                <a href="<?= u('/contacto') ?>" class="tbt-btn tbt-btn--outline">Formulario</a>
            </div>
        </div>
    </div>
</section>

<?php include INCLUDES_PATH . '/footer.php'; ?>
