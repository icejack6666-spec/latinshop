<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$page_title       = 'Bot Farming Lords Mobile | Latin Shop';
$page_description = 'Bot Premium para Lords Mobile con autoconstrucción, escudo automático, recolección, investigación, fiesta de gremio y más. Desde $3/mes.';
$page_canonical   = SITE_URL . '/bots/bot-farming';

include INCLUDES_PATH . '/header.php';
?>

<section class="tbt-hero">
    <div class="tbt-wrap">

        <nav class="tbt-breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= u('/bots') ?>">Bots</a>
            <span class="tbt-breadcrumb__sep">›</span>
            <span aria-current="page">Bot Farming</span>
        </nav>

        <div class="bf-hero tbt-enter">
            <div class="bf-hero__text">
                <div class="tbt-badge tbt-badge--jade tbt-mb-3">
                    <span class="tbt-badge__dot"></span>
                    Bot Premium Activo
                </div>
                <h1 class="tbt-h-xl tbt-mb-3">
                    <span class="tbt-jade"></span><br>
                    Bot Premium Lords Mobile
                </h1>
                <p class="tbt-body-lg tbt-mb-4">
                    Tu cuenta trabajando <strong class="tbt-white">24/7</strong> completamente sola.
                    Farming, escudo, investigación, fiesta de gremio y mucho más —
                    todo controlado desde el juego o WhatsApp.
                </p>
                <div class="tbt-flex tbt-gap-2 tbt-wrap-flex">
                    <a href="#bf-planes" class="tbt-btn tbt-btn--jade tbt-btn--lg">
                        Ver Planes y Precios <span class="tbt-arrow">→</span>
                    </a>
                    <a href="#" data-wa-text="Hola, quiero información sobre el Bot Premium"
                       target="_blank" rel="noopener noreferrer"
                       class="bf-contratar tbt-btn tbt-btn--outline tbt-btn--lg">
                        Preguntar por WhatsApp
                    </a>
                </div>
            </div>

            <div class="bf-hero__stats tbt-stagger">
                <div class="bf-stat-block">
                    <span class="bf-stat-block__num tbt-count" data-to="24" data-suffix="/7">0</span>
                    <span class="bf-stat-block__label">Activo</span>
                </div>
                <div class="bf-stat-block">
                    <span class="bf-stat-block__num">$3</span>
                    <span class="bf-stat-block__label">Desde/mes</span>
                </div>
                <div class="bf-stat-block">
                    <span class="bf-stat-block__num tbt-count" data-to="250" data-suffix="+">0+</span>
                    <span class="bf-stat-block__label">Instalaciones</span>
                </div>
            </div>
        </div>

    </div>
</section>


<section class="tbt-section tbt-section--alt">
    <div class="tbt-wrap">

        <header class="tbt-mb-5 tbt-reveal" style="max-width:620px;">
            <p class="tbt-eyebrow tbt-eyebrow--jade tbt-mb-2">Funciones</p>
            <h2 class="tbt-h-md">
                <br>
                <span class="tbt-jade">Todo lo que el bot hace por ti</span>
            </h2>
            <p class="tbt-body-md tbt-mt-2">Sin instalar nada. Sin tocar el teléfono.</p>
        </header>

        <div class="bf-funcs-grid tbt-stagger">
            <?php
            $funciones = [
                ['icon'=>'🏗️', 'title'=>'Autoconstrucción',            'desc'=>'Mantiene las colas de construcción siempre activas.'],
                ['icon'=>'🛡️', 'title'=>'Escudo y Anti automáticos',   'desc'=>'Activa escudo y anti-scout cuando detecta amenazas.'],
                ['icon'=>'📦', 'title'=>'Suministro automático',        'desc'=>'Gestiona el suministro de recursos sin intervención.'],
                ['icon'=>'🌾', 'title'=>'Recolección automática',       'desc'=>'Envía tropas a recolectar recursos las 24 horas.'],
                ['icon'=>'🏰', 'title'=>'Fortalezas oscuras',           'desc'=>'Se une automáticamente en el momento correcto.'],
                ['icon'=>'⚓', 'title'=>'Intercambios del barco',       'desc'=>'Realiza intercambios de forma automática.'],
                ['icon'=>'⚔️', 'title'=>'Etapas de héroes',            'desc'=>'Realiza etapas automáticamente para farmear experiencia.'],
                ['icon'=>'🔬', 'title'=>'Investigación automática',     'desc'=>'Mantiene el laboratorio siempre activo.'],
                ['icon'=>'🎉', 'title'=>'Fiesta de Gremio',             'desc'=>'Participa y maximiza puntos sin esfuerzo.'],
                ['icon'=>'🔥', 'title'=>'Prueba de fuego y diario',     'desc'=>'Completa el diario de aventura diariamente.'],
                ['icon'=>'👾', 'title'=>'Pactos y mejora de Mobs',      'desc'=>'Gestiona pactos y mejora de monstruitos.'],
                ['icon'=>'💪', 'title'=>'Entrenamiento de tropas',      'desc'=>'Entrena tropas y equipo sin parar.'],
                ['icon'=>'❌', 'title'=>'Eliminar misiones',            'desc'=>'Elimina misiones de intercambio extravagante.'],
                ['icon'=>'🚪', 'title'=>'Portero lista blanca/negra',   'desc'=>'Controla quién puede entrar a tu castillo.'],
                ['icon'=>'💬', 'title'=>'Control por comandos',         'desc'=>'Control desde el juego o WhatsApp.'],
                ['icon'=>'🦁', 'title'=>'Reportes de cacería',          'desc'=>'Reportes automáticos de cacería al instante.'],
                ['icon'=>'🏆', 'title'=>'Top 10 cazadores',             'desc'=>'Monitorea los mejores cazadores en tiempo real.'],
                ['icon'=>'📋', 'title'=>'Menú de comandos',             'desc'=>'Menú completo directo en el chat del juego.'],
                ['icon'=>'💀', 'title'=>'Control de kills',             'desc'=>'Registro de kills diario y semanal.'],
                ['icon'=>'🗡️', 'title'=>'Comandos de héroes',          'desc'=>'Héroes recomendados y sets sugeridos.'],
                ['icon'=>'🏪', 'title'=>'Cobro de tiendas',             'desc'=>'Avisa con tiempo para elegir qué cobrar.'],
            ];
            foreach ($funciones as $f): ?>
            <div class="bf-func-card">
                <div class="bf-func-card__icon"><?= $f['icon'] ?></div>
                <div class="bf-func-card__body">
                    <p class="bf-func-card__title"><?= htmlspecialchars($f['title'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="bf-func-card__desc"><?= htmlspecialchars($f['desc'],  ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<section class="tbt-section" id="bf-planes">
    <div class="tbt-wrap">

        <header class="tbt-text-center tbt-mb-5 tbt-reveal" style="max-width:580px; margin-inline:auto;">
            <p class="tbt-eyebrow tbt-eyebrow--jade tbt-mb-2">Precios</p>
            <h2 class="tbt-h-md"><span class="tbt-jade">Planes y Precios</span></h2>
            <p class="tbt-body-md tbt-mt-2">Elige el plan que mejor se adapte. Más meses = más ahorro.</p>
        </header>

        <div class="bf-vendedor-wrap tbt-reveal">
            <p class="bf-vendedor-label">Elige tu vendedor</p>
            <div class="gem-vendedores">
                <button class="gem-vendedor active"
                        data-wa="<?= WHATSAPP_NUMBER ?>"
                        data-nombre="MAFIAS">
                    <span class="gem-vendedor__avatar">
                        <img src="<?= ASSETS_URL ?>/images/vendedores/mafias.jpg"
                             alt="MAFIAS"
                             onerror="this.style.display='none'">
                    </span>
                    <span>MAFIAS</span>
                </button>
                <button class="gem-vendedor"
                        data-wa="51994361594"
                        data-nombre="MininoMM">
                    <span class="gem-vendedor__avatar">
                        <img src="<?= ASSETS_URL ?>/images/vendedores/mininomm.jpg"
                             alt="MininoMM"
                             onerror="this.style.display='none'">
                    </span>
                    <span>MininoMM</span>
                </button>
            </div>
        </div>

        <div class="bf-planes tbt-reveal">

            <div class="bf-plan">
                <div class="bf-plan__header">
                    <div class="bf-plan__icon">💬</div>
                    <div>
                        <h3 class="bf-plan__name">Premium WhatsApp</h3>
                        <p class="bf-plan__sub">Control y gestión vía WhatsApp</p>
                    </div>
                </div>
                <div class="bf-plan__prices">
                    <?php
                    $precios_wa = [
                        ['meses'=>1,  'precio'=>4,  'ahorro'=>null, 'tag'=>null],
                        ['meses'=>3,  'precio'=>9,  'ahorro'=>3,   'tag'=>null],
                        ['meses'=>6,  'precio'=>16, 'ahorro'=>8,   'tag'=>'Popular'],
                        ['meses'=>12, 'precio'=>24, 'ahorro'=>24,  'tag'=>'Mejor precio'],
                    ];
                    foreach ($precios_wa as $p):
                        $mensual = round($p['precio'] / $p['meses'], 2);
                        $wa_text = 'Quiero el plan Premium WhatsApp ' . $p['meses'] . ' ' . ($p['meses']===1?'mes':'meses') . ' ($' . $p['precio'] . ')';
                    ?>
                    <div class="bf-price-row <?= $p['tag'] ? 'bf-price-row--featured' : '' ?>">
                        <div class="bf-price-row__period">
                            <?= $p['meses'] ?> <?= $p['meses'] === 1 ? 'MES' : 'MESES' ?>
                            <?php if ($p['tag']): ?><span class="bf-price-row__tag"><?= $p['tag'] ?></span><?php endif; ?>
                        </div>
                        <div class="bf-price-row__detail">
                            <?php if ($p['ahorro']): ?><span class="bf-price-row__save">Ahorras $<?= $p['ahorro'] ?></span><?php endif; ?>
                            <span class="bf-price-row__per">$<?= number_format($mensual, 2) ?>/mes</span>
                        </div>
                        <div class="bf-price-row__actions">
                            <span class="bf-price-row__total">$<?= number_format($p['precio'], 2) ?></span>
                            <a href="#"
                               data-wa-text="<?= htmlspecialchars($wa_text, ENT_QUOTES) ?>"
                               target="_blank" rel="noopener noreferrer"
                               class="bf-contratar tbt-btn <?= $p['tag'] ? 'tbt-btn--jade' : 'tbt-btn--outline' ?> tbt-btn--sm">
                                Contratar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bf-plan bf-plan--promo">
                <div class="bf-plan__promo-label">🎯 Promoción primer contrato</div>
                <div class="bf-plan__header">
                    <div class="bf-plan__icon">📄</div>
                    <div>
                        <h3 class="bf-plan__name">Premium Contrato</h3>
                        <p class="bf-plan__sub">Precios especiales para nuevos clientes</p>
                    </div>
                </div>
                <div class="bf-plan__prices">
                    <?php
                    $precios_ct = [
                        ['meses'=>1,  'precio'=>3,  'ahorro'=>null, 'tag'=>null],
                        ['meses'=>3,  'precio'=>7,  'ahorro'=>2,   'tag'=>null],
                        ['meses'=>6,  'precio'=>12, 'ahorro'=>6,   'tag'=>'Popular'],
                        ['meses'=>12, 'precio'=>20, 'ahorro'=>16,  'tag'=>'Mejor precio'],
                    ];
                    foreach ($precios_ct as $p):
                        $mensual = round($p['precio'] / $p['meses'], 2);
                        $wa_text = 'Quiero el plan Premium Contrato ' . $p['meses'] . ' ' . ($p['meses']===1?'mes':'meses') . ' ($' . $p['precio'] . ')';
                    ?>
                    <div class="bf-price-row <?= $p['tag'] ? 'bf-price-row--featured' : '' ?>">
                        <div class="bf-price-row__period">
                            <?= $p['meses'] ?> <?= $p['meses'] === 1 ? 'MES' : 'MESES' ?>
                            <?php if ($p['tag']): ?><span class="bf-price-row__tag"><?= $p['tag'] ?></span><?php endif; ?>
                        </div>
                        <div class="bf-price-row__detail">
                            <?php if ($p['ahorro']): ?><span class="bf-price-row__save">Ahorras $<?= $p['ahorro'] ?></span><?php endif; ?>
                            <span class="bf-price-row__per">$<?= number_format($mensual, 2) ?>/mes</span>
                        </div>
                        <div class="bf-price-row__actions">
                            <span class="bf-price-row__total">$<?= number_format($p['precio'], 2) ?></span>
                            <a href="#"
                               data-wa-text="<?= htmlspecialchars($wa_text, ENT_QUOTES) ?>"
                               target="_blank" rel="noopener noreferrer"
                               class="bf-contratar tbt-btn <?= $p['tag'] ? 'tbt-btn--jade' : 'tbt-btn--outline' ?> tbt-btn--sm">
                                Contratar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /.bf-planes -->

    </div>
</section>

<section class="tbt-section tbt-section--alt">
    <div class="tbt-wrap">

        <header class="tbt-mb-5 tbt-reveal" style="max-width:600px;">
            <p class="tbt-eyebrow tbt-eyebrow--jade tbt-mb-2">FAQ</p>
            <h2 class="tbt-h-md"><span class="tbt-jade">Preguntas Frecuentes</span></h2>
        </header>

        <div class="bf-faq tbt-stagger" style="max-width:760px;">
            <?php
            $faqs = [
                ['q'=>'¿Necesito instalar algo en mi PC o teléfono?',
                 'a'=>'No. El bot corre en nuestros servidores. Solo necesitas darnos acceso y nosotros gestionamos todo.'],
                ['q'=>'¿Es seguro para mi cuenta?',
                 'a'=>'Llevamos tiempo con este servicio y cientos de cuentas gestionadas. Usamos métodos para minimizar cualquier riesgo al máximo.'],
                ['q'=>'¿Puedo cancelar cuando quiera?',
                 'a'=>'Sí, sin permanencia ni penalizaciones. Cancela con un simple mensaje por WhatsApp.'],
                ['q'=>'¿Cuál es la diferencia entre Premium WhatsApp y Premium Contrato?',
                 'a'=>'El plan Contrato es una promoción especial para nuevos clientes con precios reducidos. El plan WhatsApp es el precio estándar con control total.'],
                ['q'=>'¿Cómo pago?',
                 'a'=>'Aceptamos varios métodos de pago. Contáctanos por WhatsApp para más detalles según tu país.'],
                ['q'=>'¿Qué pasa si tengo un problema?',
                 'a'=>'Soporte disponible por WhatsApp. Respondemos lo antes posible, normalmente en minutos.'],
            ];
            foreach ($faqs as $faq): ?>
            <details class="bf-faq-item">
                <summary class="bf-faq-item__q">
                    <?= htmlspecialchars($faq['q'], ENT_QUOTES, 'UTF-8') ?>
                    <svg class="bf-faq-item__arrow" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z"/></svg>
                </summary>
                <p class="bf-faq-item__a"><?= htmlspecialchars($faq['a'], ENT_QUOTES, 'UTF-8') ?></p>
            </details>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<section class="tbt-section">
    <div class="tbt-wrap">
        <div class="tbt-cta-box tbt-reveal">
            <div>
                <h2 class="tbt-h-md tbt-mb-2">¿Listo para empezar?</h2>
                <p class="tbt-body-md">Escríbenos por WhatsApp y en menos de 24h tenemos tu bot corriendo.</p>
            </div>
            <div class="tbt-cta-box__actions">
                <a href="#"
                   data-wa-text="Quiero contratar el Bot Premium"
                   target="_blank" rel="noopener noreferrer"
                   class="bf-contratar tbt-btn tbt-btn--jade tbt-btn--lg">
                    Empezar ahora <span class="tbt-arrow">→</span>
                </a>
                <a href="<?= u('/contacto') ?>" class="tbt-btn tbt-btn--outline tbt-btn--lg">Más información</a>
            </div>
        </div>
    </div>
</section>

<style>
.bf-hero {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: var(--tbt-s5);
    align-items: center;
}
@media (max-width: 800px) { .bf-hero { grid-template-columns: 1fr; } }

.bf-hero__stats {
    display: flex;
    flex-direction: column;
    gap: var(--tbt-s2);
}
@media (max-width: 800px) {
    .bf-hero__stats { flex-direction: row; flex-wrap: wrap; }
}

.bf-stat-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.1rem 1.5rem;
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-4);
    border-radius: var(--tbt-r-md);
    min-width: 100px;
    text-align: center;
    transition: border-color var(--tbt-t2) var(--tbt-ease), transform var(--tbt-t2) var(--tbt-ease);
}
.bf-stat-block:hover {
    border-color: var(--tbt-jade-30);
    transform: translateY(-3px);
}
.bf-stat-block__num {
    display: block;
    font-family: var(--tbt-font-display);
    font-size: 1.7rem;
    font-weight: 800;
    color: var(--tbt-jade);
    line-height: 1;
    margin-bottom: 4px;
}
.bf-stat-block__label {
    font-family: var(--tbt-font-mono);
    font-size: var(--tbt-text-2xs);
    color: var(--tbt-txt-muted);
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.bf-funcs-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--tbt-s2);
}
@media (max-width: 900px) { .bf-funcs-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 520px)  { .bf-funcs-grid { grid-template-columns: 1fr; } }

.bf-func-card {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 1.1rem 1.25rem;
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-3);
    border-radius: var(--tbt-r-md);
    transition: border-color var(--tbt-t1) var(--tbt-ease), transform var(--tbt-t1) var(--tbt-ease), background var(--tbt-t1);
}
.bf-func-card:hover {
    border-color: var(--tbt-jade-30);
    background: var(--tbt-bg-2);
    transform: translateY(-2px);
}
.bf-func-card__icon  { font-size: 1.4rem; flex-shrink: 0; line-height: 1; margin-top: 0.1rem; }
.bf-func-card__title { font-size: 0.9rem; font-weight: 700; color: var(--tbt-txt-white); margin-bottom: 0.2rem; line-height: 1.3; }
.bf-func-card__desc  { font-size: 0.78rem; color: var(--tbt-txt-muted); line-height: 1.5; }

.bf-vendedor-wrap {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-4);
    border-radius: var(--tbt-r-lg);
}
.bf-vendedor-label {
    font-size: .75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--tbt-txt-sub);
    margin-bottom: .75rem;
}
.gem-vendedores {
    display: flex;
    gap: .6rem;
    justify-content: center;
}
.gem-vendedor {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .3rem;
    padding: .6rem .9rem;
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-4);
    border-radius: 8px;
    color: var(--tbt-txt-sub);
    font-size: .75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
    font-family: var(--tbt-font-body);
    min-width: 80px;
}
.gem-vendedor:hover { border-color: var(--tbt-jade); color: var(--tbt-txt-white); }
.gem-vendedor.active {
    background: rgba(232,96,44,.12);
    border-color: var(--tbt-jade);
    color: var(--tbt-jade);
    box-shadow: 0 0 10px rgba(232,96,44,.2);
}
.gem-vendedor__avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--tbt-bg-4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .9rem;
    font-weight: 700;
    color: var(--tbt-txt-white);
    overflow: hidden;
    flex-shrink: 0;
}
.gem-vendedor__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.gem-vendedor.active .gem-vendedor__avatar {
    box-shadow: 0 0 0 2px var(--tbt-jade);
}

.bf-planes {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--tbt-s4);
}
@media (max-width: 860px) { .bf-planes { grid-template-columns: 1fr; } }

.bf-plan {
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-4);
    border-radius: var(--tbt-r-lg);
    overflow: hidden;
    transition: border-color var(--tbt-t2) var(--tbt-ease), box-shadow var(--tbt-t2) var(--tbt-ease);
}
.bf-plan:hover { border-color: var(--tbt-bg-5); }
.bf-plan--promo {
    border-color: var(--tbt-jade-30);
    box-shadow: 0 0 0 1px var(--tbt-jade-15), 0 8px 30px var(--tbt-jade-08);
}
.bf-plan__promo-label {
    background: var(--tbt-jade);
    color: #000;
    text-align: center;
    font-size: var(--tbt-text-xs);
    font-weight: 700;
    padding: 0.45rem 1rem;
    letter-spacing: 0.05em;
}
.bf-plan__header {
    display: flex;
    align-items: center;
    gap: var(--tbt-s2);
    padding: var(--tbt-s3);
    border-bottom: 1px solid var(--tbt-bg-3);
}
.bf-plan__icon { font-size: 2rem; }
.bf-plan__name { font-family: var(--tbt-font-display); font-size: 1.2rem; font-weight: 700; color: var(--tbt-txt-white); }
.bf-plan__sub  { font-size: var(--tbt-text-sm); color: var(--tbt-txt-muted); margin-top: 0.15rem; }
.bf-plan__prices { padding: var(--tbt-s2); display: flex; flex-direction: column; gap: 0.6rem; }

.bf-price-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: var(--tbt-s2);
    padding: 0.85rem var(--tbt-s2);
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-4);
    border-radius: var(--tbt-r-sm);
    transition: border-color var(--tbt-t1);
}
.bf-price-row--featured {
    background: var(--tbt-jade-08);
    border-color: var(--tbt-jade-30);
}
.bf-price-row:hover { border-color: var(--tbt-bg-5); }
.bf-price-row--featured:hover { border-color: var(--tbt-jade-40); }
.bf-price-row__period {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    min-width: 80px;
    font-family: var(--tbt-font-display);
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--tbt-txt-white);
}
.bf-price-row__tag {
    font-family: var(--tbt-font-mono);
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--tbt-jade);
    background: var(--tbt-jade-08);
    padding: 0.15rem 0.45rem;
    border-radius: var(--tbt-r-sm);
    width: fit-content;
}
.bf-price-row__detail { display: flex; flex-direction: column; gap: 0.15rem; }
.bf-price-row__save { font-size: var(--tbt-text-xs); color: var(--tbt-jade); font-weight: 600; }
.bf-price-row__per  { font-size: var(--tbt-text-sm); color: var(--tbt-txt-sub); }
.bf-price-row__actions { display: flex; align-items: center; gap: var(--tbt-s2); }
.bf-price-row__total {
    font-family: var(--tbt-font-display);
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--tbt-txt-white);
    white-space: nowrap;
}

@media (max-width: 480px) {
    .bf-price-row { grid-template-columns: 1fr 1fr; grid-template-rows: auto auto; }
    .bf-price-row__actions { grid-column: 1/-1; justify-content: space-between; }
    .bf-price-row__detail { display: none; }
}

.bf-faq { display: flex; flex-direction: column; gap: 0.6rem; }

.bf-faq-item {
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-3);
    border-radius: var(--tbt-r-md);
    overflow: hidden;
    transition: border-color var(--tbt-t1);
}
.bf-faq-item[open] { border-color: var(--tbt-jade-30); }
.bf-faq-item__q {
    padding: 1rem 1.25rem;
    cursor: pointer;
    list-style: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    font-weight: 600;
    color: var(--tbt-txt-white);
    font-size: 0.9rem;
    transition: background 0.15s;
}
.bf-faq-item__q::-webkit-details-marker { display: none; }
.bf-faq-item__q:hover { background: var(--tbt-bg-2); }
.bf-faq-item__arrow { flex-shrink: 0; color: var(--tbt-jade); transition: transform 0.2s; }
.bf-faq-item[open] .bf-faq-item__arrow { transform: rotate(180deg); }
.bf-faq-item__a { padding: 0 1.25rem 1rem; font-size: 0.875rem; color: var(--tbt-txt-sub); line-height: 1.65; }
</style>

<script src="<?= ASSETS_URL ?>/js/bot-farming-vendedor.js" defer></script>

<?php include INCLUDES_PATH . '/footer.php'; ?>