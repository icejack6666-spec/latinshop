<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$page_title       = 'Latin Shop | Lords Mobile';
$page_description = 'Servicios premium para Lords Mobile. Bots, gemas, recursos, cuentas y herramientas gratuitas.';
$page_canonical   = SITE_URL . '/';

include INCLUDES_PATH . '/header.php';
?>


<section class="tbt-hero">
    <div class="tbt-wrap">

        <div class="tbt-enter" style="max-width: 820px;">
            <div class="tbt-badge tbt-badge--jade tbt-mb-3">
                <span class="tbt-badge__dot"></span>
                Servicios activos
            </div>

            <h1 class="tbt-h-xl tbt-mb-3">
                Domina Lords Mobile<br>
                <span class="tbt-jade">sin esfuerzo</span>
            </h1>

            <p class="tbt-body-lg tbt-mb-4 tbt-mw-md">
                Bots, gemas, recursos, cuentas y herramientas gratuitas
                para que tu cuenta crezca 24/7.
            </p>

            <div class="tbt-flex tbt-gap-2 tbt-wrap-flex tbt-enter tbt-enter--d2">
                <a href="<?= u('/utilidades') ?>" class="tbt-btn tbt-btn--jade tbt-btn--lg">
                    Ver Utilidades <span class="tbt-arrow">→</span>
                </a>
                <a href="<?= u('/bots/bot-farming') ?>" class="tbt-btn tbt-btn--outline tbt-btn--lg">
                    Bots Premium
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="tbt-stats tbt-mt-6 tbt-reveal">
            <div class="tbt-stat">
                <div class="tbt-stat__value tbt-stat__value--jade">
                    <span class="tbt-count" data-to="250" data-suffix="+">0+</span>
                </div>
                <div class="tbt-stat__label">Instalaciones</div>
            </div>
            <div class="tbt-stat">
                <div class="tbt-stat__value">
                    <span class="tbt-count" data-to="3" data-prefix="$">$0</span>
                </div>
                <div class="tbt-stat__label">Desde / mes</div>
            </div>
            <div class="tbt-stat">
                <div class="tbt-stat__value tbt-stat__value--jade">24/7</div>
                <div class="tbt-stat__label">Disponibilidad</div>
            </div>
            <div class="tbt-stat">
                <div class="tbt-stat__value">
                    <span class="tbt-count" data-to="8">0</span>
                </div>
                <div class="tbt-stat__label">Herramientas</div>
            </div>
        </div>

    </div>
</section>


<section class="tbt-section tbt-section--alt">
    <div class="tbt-wrap">

        <header class="tbt-text-center tbt-mb-5 tbt-reveal">
            <p class="tbt-eyebrow tbt-eyebrow--jade tbt-mb-2">Servicios</p>
            <h2 class="tbt-h-md tbt-mb-3">Todo lo que necesitas para<br><span class="tbt-jade">Lords Mobile</span></h2>
        </header>

        <div class="tbt-grid-3 tbt-stagger">

            <a href="<?= u('/bots/bot-farming') ?>" class="tbt-card" style="text-decoration:none;">
                <div class="tbt-card__body">
                    <div class="tbt-feature__icon tbt-mb-3" style="margin:0 0 var(--tbt-s2); width:52px; height:52px; display:flex; align-items:center; justify-content:center; background:var(--tbt-jade-08); color:var(--tbt-jade); border-radius:var(--tbt-r-md); font-size:1.5rem;">🤖</div>
                    <div class="tbt-eyebrow tbt-eyebrow--jade tbt-mb-2">Bot Premium</div>
                    <h3 class="tbt-h-sm tbt-mb-2">Bot Farming</h3>
                    <p class="tbt-body-sm">
                        Tu cuenta trabajando 24/7. Construcción, escudo, recolección,
                        investigación y fiesta de gremio automática.
                    </p>
                    <p class="tbt-jade" style="margin-top:var(--tbt-s2); font-weight:700; font-size:.9rem;">Desde $3/mes →</p>
                </div>
            </a>

            <a href="<?= u('/gems') ?>" class="tbt-card" style="text-decoration:none;">
                <div class="tbt-card__body">
                    <div class="tbt-mb-3" style="width:52px; height:52px; display:flex; align-items:center; justify-content:center; background:var(--tbt-amber-08); color:var(--tbt-amber); border-radius:var(--tbt-r-md); font-size:1.5rem;">💎</div>
                    <div class="tbt-eyebrow tbt-eyebrow--amber tbt-mb-2">Herramienta</div>
                    <h3 class="tbt-h-sm tbt-mb-2">Calculadora de Gemas</h3>
                    <p class="tbt-body-sm">
                        Calcula exactamente cuántas gemas necesitas para cualquier ítem
                        del juego. Gratis.
                    </p>
                    <p class="tbt-jade" style="margin-top:var(--tbt-s2); font-weight:700; font-size:.9rem;">Usar gratis →</p>
                </div>
            </a>

            <a href="<?= u('/utilidades') ?>" class="tbt-card" style="text-decoration:none;">
                <div class="tbt-card__body">
                    <div class="tbt-mb-3" style="width:52px; height:52px; display:flex; align-items:center; justify-content:center; background:var(--tbt-jade-08); color:var(--tbt-jade); border-radius:var(--tbt-r-md); font-size:1.5rem;">🛠️</div>
                    <div class="tbt-eyebrow tbt-eyebrow--jade tbt-mb-2">Utilidades</div>
                    <h3 class="tbt-h-sm tbt-mb-2">8 Herramientas</h3>
                    <p class="tbt-body-sm">
                        Calculadora de tropas, calendario de eventos, fiesta de gremio,
                        y más.
                    </p>
                    <p class="tbt-jade" style="margin-top:var(--tbt-s2); font-weight:700; font-size:.9rem;">Ver todas →</p>
                </div>
            </a>

        </div>

    </div>
</section>

<section class="tbt-section">
    <div class="tbt-wrap">
        <div class="tbt-cta-box tbt-reveal">
            <div>
                <h2 class="tbt-h-md tbt-mb-2">¿Listo para empezar?</h2>
                <p class="tbt-body-md">Escríbenos por WhatsApp y te respondemos en minutos.</p>
            </div>
            <div class="tbt-cta-box__actions">
                <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>&text=Hola,%20me%20interesa%20el%20bot"
                   target="_blank" rel="noopener noreferrer"
                   class="tbt-btn tbt-btn--wa tbt-btn--lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2Z"/>
                    </svg>
                    Contactar por WhatsApp
                </a>
                <a href="<?= u('/utilidades') ?>" class="tbt-btn tbt-btn--outline tbt-btn--lg">
                    Ver Utilidades
                </a>
            </div>
        </div>
    </div>
</section>

<?php include INCLUDES_PATH . '/footer.php'; ?>
