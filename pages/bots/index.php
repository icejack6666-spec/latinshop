<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$page_title       = 'Bots Lords Mobile | Latin Shop';
$page_description = 'Bot Premium 24/7 para farming, escudo y entrenamiento — y Bot de WhatsApp gratuito para tu gremio. Automatiza Lords Mobile con Latin Shop.';
$page_canonical   = SITE_URL . '/bots';

include INCLUDES_PATH . '/header.php';
?>


<section class="tbt-hero" style="background: var(--tbt-bg-alt);">
    <div class="tbt-wrap">
        <div class="bi-hero tbt-enter">
            <div class="tbt-badge tbt-badge--jade tbt-mb-3">
                <span class="tbt-badge__dot"></span>
                Automatización
            </div>
            <h1 class="tbt-h-xl tbt-mb-3">
                <br>
                <span class="tbt-jade">Bots Lords Mobile</span>
            </h1>
            <p class="tbt-body-lg" style="max-width:560px;">
                Herramientas distintas para llevar tu cuenta y gremio al maximo nivel
                Elige la que necesitas — o usa las dos.
            </p>
        </div>
    </div>
</section>


<section class="tbt-section">
    <div class="tbt-wrap">
        <div class="bi-grid">

           
            <div class="bi-card bi-card--farming">

                <div class="bi-card__top">
                    <div class="bi-card__icon-wrap">
                        <span class="bi-card__icon">🤖</span>
                    </div>
                    <div class="bi-card__meta">
                        <span class="bi-card__type">Bot Premium</span>
                        <span class="bi-card__price">Desde <strong>$3</strong>/mes</span>
                    </div>
                </div>

                <h2 class="bi-card__title">Bot Farming</h2>
                <p class="bi-card__desc">
                    Tu cuenta trabajando <strong>24/7</strong> completamente sola.
                    Construcción, escudo, investigación, recolección, fiesta de gremio y mucho más —
                    controlado desde el juego o WhatsApp.
                </p>

                <!-- Funciones destacadas -->
                <ul class="bi-feats">
                    <li><span class="bi-feat__dot"></span>Autoconstrucción e investigación continua</li>
                    <li><span class="bi-feat__dot"></span>Escudo y anti-scout automáticos</li>
                    <li><span class="bi-feat__dot"></span>Recolección y fiesta de gremio 24/7</li>
                    <li><span class="bi-feat__dot"></span>Entrenamiento de tropas sin parar</li>
                    <li><span class="bi-feat__dot"></span>Control por comandos desde WhatsApp</li>
                    <li class="bi-feat--more">+16 funciones más →</li>
                </ul>

                <!-- Stats rápidos -->
                <div class="bi-stats">
                    <div class="bi-stat">
                        <span class="bi-stat__num">24/7</span>
                        <span class="bi-stat__label">Activo</span>
                    </div>
                    <div class="bi-stat">
                        <span class="bi-stat__num">350+</span>
                        <span class="bi-stat__label">Instalaciones</span>
                    </div>
                    <div class="bi-stat">
                        <span class="bi-stat__num">+21</span>
                        <span class="bi-stat__label">Funciones</span>
                    </div>
                </div>

                <div class="bi-card__actions">
                    <a href="<?= u('/bots/bot-farming') ?>" class="tbt-btn tbt-btn--jade tbt-btn--lg">
                        Ver Bot Farming <span class="tbt-arrow">→</span>
                    </a>
                    <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>&text=Hola,%20quiero%20información%20sobre%20el%20Bot%20Premium"
                       target="_blank" rel="noopener noreferrer"
                       class="tbt-btn tbt-btn--outline">
                        Preguntar
                    </a>
                </div>
            </div>

            <!-- ── BOT WHATSAPP ── -->
            <div class="bi-card bi-card--wa">

                <div class="bi-card__top">
                    <div class="bi-card__icon-wrap bi-card__icon-wrap--wa">
                        <span class="bi-card__icon">💬</span>
                    </div>
                    <div class="bi-card__meta">
                        <span class="bi-card__type">Bot WhatsApp</span>
                        <span class="bi-card__price bi-card__price--free">
                            <span class="bi-free-dot"></span>
                            100% Gratuito
                        </span>
                    </div>
                </div>

                <h2 class="bi-card__title">Bot de WhatsApp</h2>
                <p class="bi-card__desc">
                    Agrégalo al grupo de tu gremio y listo.
                    Tageo masivo, agenda de emergencias, guías con imágenes y más —
                    responde solo cuando lo llaman, <strong>cero spam</strong>.
                </p>

                <!-- Funciones destacadas -->
                <ul class="bi-feats">
                    <li><span class="bi-feat__dot bi-feat__dot--wa"></span>Tageo masivo con un comando</li>
                    <li><span class="bi-feat__dot bi-feat__dot--wa"></span>Agenda de emergencias del gremio</li>
                    <li><span class="bi-feat__dot bi-feat__dot--wa"></span>Guías con imagen: héroes, sets, costos</li>
                    <li><span class="bi-feat__dot bi-feat__dot--wa"></span>Info de precios y servicios</li>
                    <li><span class="bi-feat__dot bi-feat__dot--wa"></span>Sin spam — solo responde comandos</li>
                    <li><span class="bi-feat__dot bi-feat__dot--wa"></span>IA integrada y gratuita</li>
                    <li class="bi-feat--more">Actualizaciones automáticas →</li>
                </ul>

                <!-- Stats rápidos -->
                <div class="bi-stats">
                    <div class="bi-stat bi-stat--wa">
                        <span class="bi-stat__num">$0</span>
                        <span class="bi-stat__label">Costo</span>
                    </div>
                    <div class="bi-stat bi-stat--wa">
                        <span class="bi-stat__num">24/7</span>
                        <span class="bi-stat__label">Activo</span>
                    </div>
                    <div class="bi-stat bi-stat--wa">
                        <span class="bi-stat__num">0</span>
                        <span class="bi-stat__label">Spam</span>
                    </div>
                </div>

                <div class="bi-card__actions">
                    <a href="<?= u('/bots/bot-whatsapp') ?>" class="bi-btn-wa">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2Z"/>
                        </svg>
                        Ver Bot WhatsApp
                    </a>
                    <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>&text=Hola,%20quiero%20agregar%20el%20Bot%20WhatsApp%20gratis%20a%20mi%20gremio"
                       target="_blank" rel="noopener noreferrer"
                       class="tbt-btn tbt-btn--outline">
                        Agregar gratis
                    </a>
                </div>
            </div>

        </div><!-- /.bi-grid -->
    </div>
</section>

<section class="tbt-section" style="background: var(--tbt-bg-alt); padding-top:0;">
    <div class="tbt-wrap">

        <div class="bi-compare">
            <div class="bi-compare__header">
                <span></span>
                <span class="bi-compare__col-label">🤖 Bot Farming</span>
                <span class="bi-compare__col-label bi-compare__col-label--wa">💬 Bot WhatsApp</span>
            </div>

            <?php
            $rows = [
                ['label'=>'Costo',              'farm'=>'Desde $3/mes',  'wa'=>'Gratis'],
                ['label'=>'Para quién es',       'farm'=>'Tu cuenta',     'wa'=>'Tu gremio'],
                ['label'=>'Dónde funciona',      'farm'=>'In-game',       'wa'=>'WhatsApp'],
                ['label'=>'Instalación',         'farm'=>'Nosotros lo gestionamos', 'wa'=>'Lo agregamos al grupo'],
                ['label'=>'Activo',              'farm'=>'24/7',          'wa'=>'24/7'],
                ['label'=>'Spam',                'farm'=>'Ninguno',       'wa'=>'Cero'],
                ['label'=>'Soporte',             'farm'=>'WhatsApp',      'wa'=>'WhatsApp'],
            ];
            foreach ($rows as $i => $r):
            ?>
            <div class="bi-compare__row <?= $i % 2 === 0 ? 'bi-compare__row--alt' : '' ?>">
                <span class="bi-compare__label"><?= htmlspecialchars($r['label']) ?></span>
                <span class="bi-compare__val"><?= htmlspecialchars($r['farm']) ?></span>
                <span class="bi-compare__val bi-compare__val--wa"><?= htmlspecialchars($r['wa']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<section class="tbt-section">
    <div class="tbt-wrap">
        <div class="tbt-cta-box tbt-reveal">
            <div>
                <h2 class="tbt-h-md tbt-mb-2">¿No sabes cuál necesitas?</h2>
                <p class="tbt-body-md">Escríbenos por WhatsApp y te ayudamos.</p>
            </div>
            <div class="tbt-cta-box__actions">
                <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>&text=Hola,%20quiero%20saber%20qué%20bot%20me%20conviene"
                   target="_blank" rel="noopener noreferrer"
                   class="tbt-btn tbt-btn--jade tbt-btn--lg">
                    Hablar por WhatsApp <span class="tbt-arrow">→</span>
                </a>
            </div>
        </div>
    </div>
</section>

<style>
/* ── HERO ─────────────────────────────────────────────── */
.bi-hero { max-width: 640px; }

/* ── GRID PRINCIPAL ──────────────────────────────────── */
.bi-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    align-items: start;
}
@media (max-width: 860px) {
    .bi-grid { grid-template-columns: 1fr; }
}

/* ── CARDS ───────────────────────────────────────────── */
.bi-card {
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-4);
    border-radius: 4px;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    transition: border-color .25s, box-shadow .25s, transform .25s;
    position: relative;
    overflow: hidden;
}
.bi-card::before {
    content: '';
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity .3s;
    pointer-events: none;
}
.bi-card--farming::before {
    background: radial-gradient(ellipse at top left, rgba(232,96,44,.07) 0%, transparent 65%);
}
.bi-card--wa::before {
    background: radial-gradient(ellipse at top left, rgba(37,211,102,.07) 0%, transparent 65%);
}
.bi-card:hover { transform: translateY(-4px); }
.bi-card:hover::before { opacity: 1; }
.bi-card--farming:hover { border-color: rgba(232,96,44,.35); box-shadow: 0 12px 40px rgba(232,96,44,.1); }
.bi-card--wa:hover      { border-color: rgba(37,211,102,.35); box-shadow: 0 12px 40px rgba(37,211,102,.1); }

/* top: ícono + meta */
.bi-card__top {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.bi-card__icon-wrap {
    width: 52px; height: 52px;
    background: rgba(232,96,44,.12);
    border: 1px solid rgba(232,96,44,.25);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.5rem;
}
.bi-card__icon-wrap--wa {
    background: rgba(37,211,102,.12);
    border-color: rgba(37,211,102,.25);
}
.bi-card__meta { display: flex; flex-direction: column; gap: .2rem; }
.bi-card__type {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .09em;
    color: var(--tbt-txt-muted);
}
.bi-card__price {
    font-size: .9rem;
    font-weight: 600;
    color: var(--tbt-txt-sub);
}
.bi-card__price strong { color: var(--tbt-jade); font-size: 1.05rem; }
.bi-card__price--free {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    color: #25d366;
}
.bi-free-dot {
    width: 7px; height: 7px;
    background: #25d366;
    border-radius: 50%;
    animation: biPulse 1.8s ease-in-out infinite;
    box-shadow: 0 0 6px #25d366;
}
@keyframes biPulse {
    0%,100%{ opacity:1; transform:scale(1); }
    50%{ opacity:.5; transform:scale(1.35); }
}

/* título y desc */
.bi-card__title {
    font-family: var(--tbt-font-display);
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--tbt-txt-white);
    line-height: 1.2;
    margin: 0;
}
.bi-card__desc {
    font-size: .9rem;
    color: var(--tbt-txt-sub);
    line-height: 1.7;
    margin: 0;
}
.bi-card__desc strong { color: var(--tbt-txt-white); }

/* lista de features */
.bi-feats {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: .55rem;
    font-size: .855rem;
    color: var(--tbt-txt-base);
}
.bi-feats li { display: flex; align-items: center; gap: .6rem; }
.bi-feat__dot {
    width: 7px; height: 7px;
    background: var(--tbt-jade);
    border-radius: 50%;
    flex-shrink: 0;
}
.bi-feat__dot--wa { background: #25d366; }
.bi-feat--more {
    font-size: .78rem;
    color: var(--tbt-txt-muted);
    font-style: italic;
    padding-left: 1.1rem;
}

/* stats rápidos */
.bi-stats {
    display: flex;
    gap: .75rem;
    padding-top: .5rem;
    border-top: 1px solid var(--tbt-bg-2);
}
.bi-stat {
    flex: 1;
    text-align: center;
    padding: .65rem .5rem;
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-3);
    border-radius: 4px;
}
.bi-stat__num {
    display: block;
    font-family: var(--tbt-font-display);
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--tbt-jade);
    line-height: 1;
    margin-bottom: .2rem;
}
.bi-stat--wa .bi-stat__num { color: #25d366; }
.bi-stat__label {
    font-size: .65rem;
    color: var(--tbt-txt-muted);
    text-transform: uppercase;
    letter-spacing: .07em;
}

/* botones */
.bi-card__actions { display: flex; gap: .75rem; flex-wrap: wrap; padding-top: .25rem; }

.bi-btn-wa {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .65rem 1.25rem;
    background: #25d366;
    color: #000;
    font-weight: 700;
    font-size: .875rem;
    border-radius: 2px;
    text-decoration: none;
    transition: background .2s, transform .2s, box-shadow .2s;
}
.bi-btn-wa:hover {
    background: #20bd5a;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37,211,102,.3);
    color: #000;
}

/* ── COMPARATIVA ─────────────────────────────────────── */
.bi-compare {
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-4);
    border-radius: 4px;
    overflow: hidden;
    max-width: 720px;
    margin: 0 auto;
}
.bi-compare__header,
.bi-compare__row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    align-items: center;
}
.bi-compare__header {
    background: var(--tbt-bg-2);
    border-bottom: 1px solid var(--tbt-bg-3);
    padding: .65rem 1.25rem;
}
.bi-compare__col-label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--tbt-jade);
    text-align: center;
}
.bi-compare__col-label--wa { color: #25d366; }
.bi-compare__row {
    padding: .7rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,.04);
    font-size: .855rem;
    transition: background .15s;
}
.bi-compare__row:last-child { border-bottom: none; }
.bi-compare__row:hover { background: rgba(255,255,255,.02); }
.bi-compare__row--alt { background: rgba(255,255,255,.015); }
.bi-compare__label { color: var(--tbt-txt-muted); font-size: .8rem; }
.bi-compare__val { color: var(--tbt-txt-white); font-weight: 600; text-align: center; font-size: .83rem; }
.bi-compare__val--wa { color: #25d366; }

@media (max-width: 500px) {
    .bi-compare__row,
    .bi-compare__header { grid-template-columns: 1fr 1fr 1fr; gap: .25rem; padding: .6rem .75rem; }
    .bi-compare__label { font-size: .72rem; }
    .bi-compare__val   { font-size: .75rem; }
}
</style>

<?php include INCLUDES_PATH . '/footer.php'; ?>
