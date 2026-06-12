<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$page_title       = 'Bot WhatsApp Lords Mobile | Latin Shop';
$page_description = 'Bot de WhatsApp gratuito para tu gremio de Lords Mobile. Tageo, agenda, utilidades con imágenes, info de ventas y más. Funciona 24/7, sin spam.';
$page_canonical   = SITE_URL . '/bots/bot-whatsapp';

include INCLUDES_PATH . '/header.php';
?>

<section class="tbt-hero" style="background: var(--tbt-bg-alt);">
    <div class="tbt-wrap">
        <nav class="tbt-breadcrumb">
            <a href="<?= u('/bots') ?>">Bots</a> <span> › </span>
            <span aria-current="page">Bot WhatsApp</span>
        </nav>
        <div class="bw-hero reveal is-visible">
            <div class="bw-hero__text">
                <div class="bw-free-badge">
                    <span class="bw-free-dot"></span>
                    100% Gratuito
                </div>
                <h1 class="tbt-h-xl tbt-mb-3">
                     <span class="tbt-jade">Bot de WhatsApp para tu Gremio</span><br>
                    
                </h1>
                <p class="tbt-body-lg tbt-mb-5">
                    Agrégalo al grupo de WhatsApp de tu gremio y listo.
                    Funciona <strong style="color:var(--tbt-txt-white)">24/7</strong>, responde solo cuando
                    lo llaman con un comando — <strong style="color:var(--tbt-txt-white)">cero spam</strong>,
                    sin importar cuántos miembros tenga el grupo.
                </p>
                <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>&text=Hola,%20quiero%20agregar%20el%20Bot%20WhatsApp%20a%20mi%20gremio"
                       target="_blank" rel="noopener noreferrer" class="tbt-btn tbt-btn--wa">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2Z"/></svg>
                        Agregar a mi gremio — Gratis
                    </a>
                    <a href="#funciones" class="tbt-btn tbt-btn--outline">Ver funciones</a>
                </div>
            </div>
            <div class="bw-hero__stats reveal-stagger is-visible">
                <div class="bw-stat"><span class="bw-stat__num bw-green">$0</span><span class="bw-stat__label">Costo</span></div>
                <div class="bw-stat"><span class="bw-stat__num bw-green">24/7</span><span class="bw-stat__label">Activo</span></div>
                <div class="bw-stat"><span class="bw-stat__num bw-green">+90</span><span class="bw-stat__label">grupos</span></div>
            </div>
        </div>
    </div>
</section>

<section class="tbt-section">
    <div class="tbt-wrap">
        <div class="bw-steps reveal-stagger is-visible">
            <div class="bw-step">
                <div class="bw-step__num">1</div>
                <h3 class="bw-step__title">Contáctanos</h3>
                <p class="bw-step__desc">Escríbenos por WhatsApp indicando que quieres el bot para tu gremio.</p>
            </div>
            <div class="bw-step__arrow">→</div>
            <div class="bw-step">
                <div class="bw-step__num">2</div>
                <h3 class="bw-step__title">Te añadimos el bot</h3>
                <p class="bw-step__desc">Agregamos el bot a tu grupo. No importa cuántos miembros tenga.</p>
            </div>
            <div class="bw-step__arrow">→</div>
            <div class="bw-step">
                <div class="bw-step__num">3</div>
                <h3 class="bw-step__title">Úsalo con comandos</h3>
                <p class="bw-step__desc">El bot responde solo cuando alguien escribe un comando. Sin interrupciones.</p>
            </div>
        </div>
    </div>
</section>

<section class="tbt-section" id="funciones" style="background: var(--tbt-bg-alt);">
    <div class="tbt-wrap">
        <header class="tbt-reveal tbt-visible" style="max-width:650px; margin-bottom:2.5rem;">
            <h2 class="tbt-h-lg tbt-mb-3">
                <br>
                <span class="tbt-jade">Todo lo que puede hacer por tu gremio</span>
            </h2>
            <p class="tbt-body-md">Cada función se activa por comando — el bot no habla solo.</p>
        </header>

        <div class="bw-grid reveal-stagger is-visible">

            <div class="bw-card">
                <div class="bw-card__head">
                    <span class="bw-card__icon">📢</span>
                    <h3 class="bw-card__title">Tageo masivo</h3>
                    <code class="bw-cmd">similar a @todos</code>
                </div>
                <p class="bw-card__desc">
                    Taggea a todos los miembros del grupo con un solo comando.
                    Ideal para avisos de guerra, ataques o eventos urgentes del gremio.
                </p>
            </div>

            <!-- Agenda -->
            <div class="bw-card">
                <div class="bw-card__head">
                    <span class="bw-card__icon">📒</span>
                    <h3 class="bw-card__title">Agenda de emergencia</h3>
                    <code class="bw-cmd">agenda</code>
                </div>
                <p class="bw-card__desc">
                    Guarda hasta <strong style="color:var(--tbt-txt-white)">20 contactos</strong> de emergencia por miembro del gremio
                    con número y nombre. Busca por nombre sin tener que ver toda la lista.
                </p>
                <div class="bw-cmd-list">
                    <code>agregar</code><code>eliminar</code><code>buscar [nombre]</code>
                </div>
            </div>

            <!-- Utilidades con imágenes: tarjeta ancha -->
            <div class="bw-card bw-card--full">
                <div class="bw-card__head">
                    <span class="bw-card__icon">🖼️</span>
                    <h3 class="bw-card__title">Utilidades con imágenes</h3>
                    <code class="bw-cmd">guia [tema]</code>
                </div>
                <p class="bw-card__desc" style="margin-bottom:1.1rem;">
                    El bot responde con imagen + texto explicativo al instante. Consultas rápidas directo en el grupo.
                </p>
                <div class="bw-utils-grid">
                    <div class="bw-util"><span>⚔️</span> Héroes para cazar mobs</div>
                    <div class="bw-util"><span>🛡️</span> Héroes para muralla o ataque</div>
                    <div class="bw-util"><span>🗡️</span> Sets de guerra recomendados</div>
                    <div class="bw-util"><span>📚</span> Costo de libros arcaicos en investigaciones</div>
                    <div class="bw-util"><span>💎</span> Costo de gemas en construcciones especiales</div>
                    <div class="bw-util"><span>💎</span> IA integrada de uso gratuito</div>
                    <div class="bw-util bw-util--soon"><span>✨</span> Más guías próximamente</div>
                </div>
            </div>

            <!-- Info de ventas -->
            <div class="bw-card">
                <div class="bw-card__head">
                    <span class="bw-card__icon">🛒</span>
                    <h3 class="bw-card__title">Info de ventas</h3>
                    <code class="bw-cmd">precio</code>
                </div>
                <p class="bw-card__desc">
                    Consulta precios e información de nuestros servicios directo en el chat del gremio.
                    Imagen + descripción completa con un solo comando.
                </p>
                <div class="bw-cmd-list">
                    <code>bots</code><code>gemas</code><code>planes</code>
                </div>
            </div>

            <!-- Sin spam -->
            <div class="bw-card bw-card--green">
                <div class="bw-card__head">
                    <span class="bw-card__icon">✅</span>
                    <h3 class="bw-card__title">Sin spam, por comandos</h3>
                </div>
                <p class="bw-card__desc">
                    El bot <strong style="color:var(--tbt-txt-white)">no manda mensajes solos</strong> ni interrumpe conversaciones.
                    Solo responde cuando alguien escribe un comando. Tu grupo sigue siendo tuyo.
                </p>
            </div>

            <!-- Próximamente -->
            <div class="bw-card bw-card--full bw-card--soon">
                <div class="bw-card__head">
                    <span class="bw-card__icon">🚀</span>
                    <h3 class="bw-card__title">Más funciones en camino</h3>
                    <span class="bw-soon-tag">En desarrollo</span>
                </div>
                <p class="bw-card__desc">
                    Seguimos añadiendo funciones constantemente. Al tener el bot en tu grupo
                    recibirás todas las actualizaciones automáticamente — sin hacer nada.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- CTA FINAL -->
<section class="tbt-section">
    <div class="tbt-wrap">
        <div class="tbt-cta-box tbt-reveal" style="border-color:#25d366; background: linear-gradient(135deg, rgba(232,96,44,0.06) 0%, transparent 60%);">
            <div class="cta-box__content">
                <h2 class="tbt-h-md tbt-mb-2">Es gratis. ¿A qué esperas?</h2>
                <p class="tbt-body-md tbt-mb-4">
                    Escríbenos por WhatsApp y en minutos tienes el bot activo en tu gremio.
                </p>
                <div class="tbt-cta-box__actions">
                    <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>&text=Hola,%20quiero%20agregar%20el%20Bot%20WhatsApp%20gratis%20a%20mi%20gremio%20de%20Lords%20Mobile"
                       target="_blank" rel="noopener noreferrer" class="tbt-btn tbt-btn--wa">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2Z"/></svg>
                        Agregar bot a mi gremio
                    </a>
                    <a href="<?= u('/contacto') ?>" class="tbt-btn tbt-btn--outline">Tengo una duda</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* HERO */
.bw-hero { display:grid; grid-template-columns:1fr auto; gap:3rem; align-items:center; }
@media(max-width:800px){ .bw-hero{ grid-template-columns:1fr; } }

.bw-free-badge {
    display:inline-flex; align-items:center; gap:.5rem;
    padding:.35rem 1rem;
    background:rgba(232,96,44,.1); border:1px solid rgba(232,96,44,.3);
    border-radius:var(--tbt-r-sm); font-size:.75rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.1em; color:#25d366;
    margin-bottom:1.25rem;
}
.bw-free-dot {
    width:7px; height:7px; background:#25d366; border-radius:50%;
    animation:glowPulse 1.8s ease-in-out infinite;
    box-shadow:0 0 6px #25d366;
}
@keyframes glowPulse {
    0%,100%{ opacity:1; transform:scale(1); }
    50%{ opacity:.6; transform:scale(1.3); }
}

.bw-hero__stats { display:flex; flex-direction:column; gap:1rem; }
@media(max-width:800px){ .bw-hero__stats{ flex-direction:row; flex-wrap:wrap; } }

.bw-stat {
    text-align:center; background:var(--tbt-bg-1);
    border:1px solid rgba(232,96,44,.2);
    padding:1rem 1.5rem; border-radius:2px; min-width:95px;
}
.bw-stat__num { display:block; font-family:var(--tbt-font-display); font-size:1.6rem; font-weight:700; }
.bw-green { color:#25d366; }
.bw-stat__label { font-size:.7rem; color:var(--tbt-txt-muted); text-transform:uppercase; letter-spacing:.08em; }

/* Botón WhatsApp verde */
.btn--wa {
    display:inline-flex; align-items:center; gap:.5rem;
    padding:.75rem 1.5rem; background:#25d366; color:#000;
    font-weight:700; font-size:.9rem; border-radius:2px;
    text-decoration:none; border:none; cursor:pointer;
    transition:background .2s, transform .2s, box-shadow .2s;
}
.btn--wa:hover { background:#20bd5a; transform:translateY(-2px); box-shadow:0 6px 20px rgba(232,96,44,.35); }

/* PASOS */
.bw-steps {
    display:flex; align-items:center; justify-content:center;
    gap:1.25rem; flex-wrap:wrap; max-width:860px; margin:0 auto;
}
.bw-step {
    flex:1; min-width:170px; max-width:230px; text-align:center;
    background:var(--tbt-bg-1); border:1px solid var(--tbt-bg-2);
    border-radius:2px; padding:1.4rem 1.1rem;
}
.bw-step__num {
    width:38px; height:38px; background:var(--tbt-jade); color:#000;
    border-radius:50%; font-family:var(--tbt-font-display); font-size:1rem; font-weight:700;
    display:flex; align-items:center; justify-content:center; margin:0 auto .8rem;
}
.bw-step__title { font-size:.95rem; font-weight:600; color:var(--tbt-txt-white); margin-bottom:.35rem; }
.bw-step__desc  { font-size:.8rem; color:var(--tbt-txt-muted); line-height:1.5; }
.bw-step__arrow { font-size:1.4rem; color:var(--tbt-bg-5); flex-shrink:0; }
@media(max-width:600px){ .bw-step__arrow{ display:none; } }

/* GRID FUNCIONES */
.bw-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; }
@media(max-width:600px){ .bw-grid{ grid-template-columns:1fr; } }

.bw-card {
    background:var(--tbt-bg-1); border:1px solid var(--tbt-bg-2);
    border-radius:2px; padding:1.35rem;
    transition:border-color .2s, transform .2s;
}
.bw-card:hover { border-color:rgba(232,96,44,.25); transform:translateY(-2px); }
.bw-card--full { grid-column:1 / -1; }
.bw-card--green { border-color:rgba(232,96,44,.2); background:rgba(232,96,44,.03); }
.bw-card--soon  { border-color:rgba(232,96,44,.15); background:var(--accent-dark); opacity:.85; }

.bw-card__head { display:flex; align-items:center; gap:.7rem; margin-bottom:.8rem; flex-wrap:wrap; }
.bw-card__icon  { font-size:1.4rem; flex-shrink:0; }
.bw-card__title { font-size:.95rem; font-weight:600; color:var(--tbt-txt-white); flex:1; }
.bw-card__desc  { font-size:.875rem; color:var(--tbt-txt-sub); line-height:1.65; }

.bw-cmd {
    font-size:.72rem; font-weight:700; font-family:monospace;
    background:var(--tbt-bg-2); color:var(--tbt-jade);
    padding:.2rem .5rem; border-radius:2px;
    border:1px solid rgba(232,96,44,.15); white-space:nowrap;
}
.bw-soon-tag {
    font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
    background:var(--tbt-jade-08); color:var(--tbt-jade);
    padding:.2rem .55rem; border-radius:2px;
}
.bw-cmd-list { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.85rem; }
.bw-cmd-list code {
    font-size:.72rem; font-family:monospace; font-weight:600;
    background:var(--tbt-bg-2); color:var(--tbt-txt-base);
    padding:.2rem .55rem; border-radius:2px; border:1px solid var(--tbt-bg-4);
}

/* UTILIDADES GRID */
.bw-utils-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.6rem; }
@media(max-width:700px){ .bw-utils-grid{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:420px){ .bw-utils-grid{ grid-template-columns:1fr; } }

.bw-util {
    display:flex; align-items:center; gap:.6rem;
    padding:.65rem .85rem;
    background:var(--tbt-bg-2); border:1px solid var(--tbt-bg-4);
    border-radius:2px; font-size:.82rem; color:var(--tbt-txt-base);
    transition:border-color .2s;
}
.bw-util:hover { border-color:rgba(232,96,44,.3); }
.bw-util span { font-size:1rem; flex-shrink:0; }
.bw-util--soon { opacity:.5; border-style:dashed; }
</style>

<?php include INCLUDES_PATH . '/footer.php'; ?>
