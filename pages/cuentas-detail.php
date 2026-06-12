<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . u('/cuentas')); exit; }

$c = $db->fetch("SELECT * FROM cuentas_venta WHERE id = ? AND status != 'hidden' LIMIT 1", [$id]);
if (!$c) { http_response_code(404); require_once PAGES_PATH . '/404.php'; exit; }

try { $db->update("UPDATE cuentas_venta SET views_count = views_count + 1 WHERE id = ?", [$id]); } catch(Throwable $e) {}

$fotos = [];
for ($n = 1; $n <= 10; $n++) {
    $col = $n === 1 ? 'image_url' : "image_url_{$n}";
    if (!empty($c[$col])) $fotos[] = SITE_URL . '/' . htmlspecialchars($c[$col]);
}

$waNum = preg_replace('/[^0-9]/', '', $c['whatsapp_number']);
$waMsg = !empty($c['whatsapp_msg']) ? $c['whatsapp_msg'] : 'Hola, me interesa la cuenta: ' . $c['title'];
$waUrl = 'https://wa.me/' . $waNum . '?text=' . urlencode($waMsg);

$page_title       = htmlspecialchars($c['title']) . ' — Cuentas | Latin Shop';
$page_description = mb_substr(strip_tags($c['description']), 0, 160);
$page_canonical   = u('/cuentas/ver') . '?id=' . $id;

require_once INCLUDES_PATH . '/header.php';
?>

<section style="padding-top:var(--tbt-s3);">
    <div class="tbt-wrap">
        <nav class="tbt-breadcrumb" aria-label="Navegación">
            <a href="<?= u('/cuentas') ?>">Cuentas</a>
            <span class="tbt-breadcrumb__sep">›</span>
            <span><?= htmlspecialchars($c['title']) ?></span>
        </nav>
    </div>
</section>

<section class="tbt-section" style="padding-top:var(--tbt-s3);">
    <div class="tbt-wrap">
        <div class="cd-layout">

            <!-- Galería izquierda -->
            <div class="cd-gallery">
                <?php if (empty($fotos)): ?>
                <div class="cd-no-photo">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                    <p>Sin imágenes</p>
                </div>
                <?php else: ?>

                <!-- Foto principal -->
                <div class="cd-main-wrap">
                    <img src="<?= $fotos[0] ?>" id="cd-main-img" class="cd-main-img"
                         alt="<?= htmlspecialchars($c['title']) ?>"
                         onerror="this.src='<?= ASSETS_URL ?>/images/cuenta-placeholder.webp'">
                    <?php if (count($fotos) > 1): ?>
                    <button class="cd-nav cd-nav--prev" id="cd-prev">‹</button>
                    <button class="cd-nav cd-nav--next" id="cd-next">›</button>
                    <div class="cd-counter"><span id="cd-idx">1</span> / <?= count($fotos) ?></div>
                    <?php endif; ?>
                    <?php if ($c['status'] === 'sold'): ?>
                    <div class="cd-sold-overlay">VENDIDA</div>
                    <?php endif; ?>
                </div>

                <!-- Miniaturas -->
                <?php if (count($fotos) > 1): ?>
                <div class="cd-thumbs" id="cd-thumbs">
                    <?php foreach ($fotos as $i => $foto): ?>
                    <button class="cd-thumb <?= $i === 0 ? 'active' : '' ?>"
                            data-src="<?= $foto ?>" data-i="<?= $i ?>">
                        <img src="<?= $foto ?>" alt=""
                             onerror="this.src='<?= ASSETS_URL ?>/images/cuenta-placeholder.webp'">
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>

            <!-- Info derecha -->
            <div class="cd-info">

                <div class="cd-info__top">
                    <h1 class="cd-title"><?= htmlspecialchars($c['title']) ?></h1>
                    <span class="cd-badge cd-badge--<?= $c['status'] ?>">
                        <?= ['active'=>'Disponible','sold'=>'Vendida','hidden'=>'Oculta'][$c['status']] ?? '' ?>
                    </span>
                </div>

                <!-- Precio -->
                <div class="cd-price">
                    <?php if ($c['price'] > 0): ?>
                    <span class="cd-price__val"><?= number_format((float)$c['price'], 0, '.', ',') ?></span>
                    <span class="cd-price__cur"><?= htmlspecialchars($c['currency']) ?></span>
                    <?php else: ?>
                    <span class="cd-price__val" style="font-size:1.8rem;">Consultar precio</span>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div class="cd-stats">
                    <?php
                    $stats = [
                        ['Poder',    $c['power']    ?? null,  '⚔'],
                        ['Servidor', $c['server']   ?? null,  '🏰'],
                        ['Reino',    $c['kingdom']  ?? null,  '🗺'],
                        ['VIP',      $c['vip_level'] !== null ? 'Nivel '.(int)$c['vip_level'] : null, '⭐'],
                    ];
                    foreach ($stats as [$label, $val, $icon]):
                        if (empty($val)) continue;
                    ?>
                    <div class="cd-stat">
                        <span class="cd-stat__icon"><?= $icon ?></span>
                        <div>
                            <span class="cd-stat__label"><?= $label ?></span>
                            <span class="cd-stat__val"><?= htmlspecialchars($val) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($c['heroes'])): ?>
                <div class="cd-block">
                    <p class="cd-block__label">Héroes / Items destacados</p>
                    <p class="cd-block__text"><?= nl2br(htmlspecialchars($c['heroes'])) ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($c['description'])): ?>
                <div class="cd-block">
                    <p class="cd-block__label">Descripción</p>
                    <div class="cd-block__text"><?= nl2br(htmlspecialchars($c['description'])) ?></div>
                </div>
                <?php endif; ?>

                <!-- Botón WhatsApp -->
                <?php if ($c['status'] !== 'sold'): ?>
                <a href="<?= $waUrl ?>" target="_blank" rel="noopener noreferrer" class="cd-wa-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Contactar por WhatsApp
                    <span class="cd-wa-btn__arrow">→</span>
                </a>
                <p class="cd-wa-note">Respuesta en minutos · Sin compromiso</p>
                <?php else: ?>
                <div class="cd-sold-msg">Esta cuenta ya fue vendida.</div>
                <?php endif; ?>

                <a href="<?= u('/cuentas') ?>" class="cd-back">← Volver a cuentas</a>
            </div>

        </div><!-- /cd-layout -->
    </div>
</section>

<!-- Lightbox -->
<div id="cd-lightbox" style="display:none;">
    <div id="cd-lb-backdrop"></div>
    <img id="cd-lb-img" src="" alt="">
    <button id="cd-lb-close">✕</button>
    <button id="cd-lb-prev">‹</button>
    <button id="cd-lb-next">›</button>
</div>

<style>
/* ── Layout ── */
.cd-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: var(--tbt-s5);
    align-items: start;
}
@media (max-width: 960px) { .cd-layout { grid-template-columns: 1fr; } }

/* ── Galería ── */
.cd-main-wrap {
    position: relative;
    aspect-ratio: 4/3;
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-4);
    border-radius: var(--tbt-r-md);
    overflow: hidden;
    cursor: zoom-in;
}
.cd-main-img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform .4s var(--tbt-ease);
    display: block;
}
.cd-main-wrap:hover .cd-main-img { transform: scale(1.03); }

/* Botones nav galería */
.cd-nav {
    position: absolute;
    top: 50%; transform: translateY(-50%);
    width: 40px; height: 40px;
    background: rgba(8,8,8,.75);
    border: 1px solid var(--tbt-bg-5);
    border-radius: var(--tbt-r-sm);
    color: var(--tbt-txt-white);
    font-size: 1.5rem; line-height: 1;
    cursor: pointer;
    transition: all .2s;
    backdrop-filter: blur(6px);
    display: flex; align-items: center; justify-content: center;
}
.cd-nav--prev { left: .6rem; }
.cd-nav--next { right: .6rem; }
.cd-nav:hover { background: var(--tbt-jade); border-color: var(--tbt-jade); }

.cd-counter {
    position: absolute; bottom: .6rem; right: .7rem;
    background: rgba(0,0,0,.7);
    color: var(--tbt-txt-light);
    font-family: var(--tbt-font-mono);
    font-size: .68rem; letter-spacing: .06em;
    padding: .2rem .55rem;
    border-radius: 3px;
    backdrop-filter: blur(4px);
}
.cd-sold-overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,.65);
    display: flex; align-items: center; justify-content: center;
    font-family: var(--tbt-font-display);
    font-size: 2.5rem; letter-spacing: .15em;
    color: #f87171;
}

/* Miniaturas */
.cd-thumbs {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-top: .6rem;
}
.cd-thumb {
    width: 68px; height: 52px;
    border: 2px solid var(--tbt-bg-4);
    border-radius: var(--tbt-r-sm);
    overflow: hidden;
    cursor: pointer;
    padding: 0;
    background: var(--tbt-bg-2);
    transition: border-color .15s;
    flex-shrink: 0;
}
.cd-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.cd-thumb.active { border-color: var(--tbt-jade); }
.cd-thumb:hover { border-color: var(--tbt-jade-40); }

/* Sin foto */
.cd-no-photo {
    aspect-ratio: 4/3;
    background: var(--tbt-bg-2);
    border: 1px dashed var(--tbt-bg-4);
    border-radius: var(--tbt-r-md);
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: .75rem; color: var(--tbt-txt-dim);
    font-family: var(--tbt-font-mono);
    font-size: .8rem; letter-spacing: .1em;
    text-transform: uppercase;
}

/* ── Info panel ── */
.cd-info { display: flex; flex-direction: column; gap: var(--tbt-s3); }

.cd-info__top { display: flex; align-items: flex-start; gap: .75rem; flex-wrap: wrap; }

.cd-title {
    font-family: var(--tbt-font-display);
    font-size: clamp(1.6rem, 3vw, 2.4rem);
    color: var(--tbt-txt-white);
    letter-spacing: .04em;
    font-weight: 400;
    line-height: 1.05;
    flex: 1;
}
.cd-badge {
    display: inline-block;
    font-family: var(--tbt-font-mono);
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    padding: .25rem .7rem;
    border-radius: 2px;
    flex-shrink: 0;
    margin-top: .3rem;
}
.cd-badge--active { background: rgba(74,222,128,.1); color: #4ade80; border: 1px solid rgba(74,222,128,.25); }
.cd-badge--sold   { background: rgba(248,113,113,.1); color: #f87171; border: 1px solid rgba(248,113,113,.25); }
.cd-badge--hidden { background: var(--tbt-bg-3); color: var(--tbt-txt-muted); border: 1px solid var(--tbt-bg-4); }

/* Precio */
.cd-price {
    display: flex; align-items: baseline; gap: .4rem;
    padding: var(--tbt-s2) var(--tbt-s3);
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-4);
    border-left: 4px solid var(--tbt-jade);
    border-radius: var(--tbt-r-md);
}
.cd-price__val {
    font-family: var(--tbt-font-display);
    font-size: 2.8rem; letter-spacing: .04em;
    color: var(--tbt-jade);
    text-shadow: 0 0 24px var(--tbt-jade-40);
    line-height: 1;
}
.cd-price__cur {
    font-family: var(--tbt-font-mono);
    font-size: .8rem; color: var(--tbt-txt-muted);
    text-transform: uppercase; letter-spacing: .1em;
}

/* Stats */
.cd-stats { display: flex; flex-direction: column; gap: .5rem; }
.cd-stat {
    display: flex; align-items: center; gap: .85rem;
    padding: .7rem var(--tbt-s2);
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-3);
    border-radius: var(--tbt-r-sm);
    transition: border-color .15s;
}
.cd-stat:hover { border-color: var(--tbt-bg-5); }
.cd-stat__icon { font-size: 1.1rem; flex-shrink: 0; width: 24px; text-align: center; }
.cd-stat__label {
    display: block;
    font-family: var(--tbt-font-mono);
    font-size: .62rem; color: var(--tbt-txt-muted);
    text-transform: uppercase; letter-spacing: .1em;
    margin-bottom: 1px;
}
.cd-stat__val {
    display: block;
    font-family: var(--tbt-font-display);
    font-size: 1.15rem; color: var(--tbt-txt-white);
    letter-spacing: .03em; font-weight: 400;
}

/* Bloques de texto */
.cd-block {
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-3);
    border-radius: var(--tbt-r-md);
    padding: var(--tbt-s2) var(--tbt-s3);
}
.cd-block__label {
    font-family: var(--tbt-font-mono);
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .12em;
    color: var(--tbt-jade);
    margin-bottom: .6rem;
}
.cd-block__text {
    font-size: .95rem;
    color: var(--tbt-txt-base);
    line-height: 1.7;
    font-family: var(--tbt-font-body);
}

/* Botón WhatsApp */
.cd-wa-btn {
    display: flex; align-items: center; justify-content: center;
    gap: .7rem;
    padding: 1rem 2rem;
    background: #25d366;
    color: #000;
    font-family: var(--tbt-font-display);
    font-size: 1.3rem; letter-spacing: .1em;
    text-decoration: none;
    border-radius: var(--tbt-r-sm);
    font-weight: 400;
    transition: all .2s var(--tbt-ease);
    clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 12px 100%, 0 calc(100% - 12px));
    box-shadow: 0 4px 24px rgba(37,211,102,.3);
}
.cd-wa-btn:hover {
    background: #20bd5a;
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(37,211,102,.5);
    color: #000;
}
.cd-wa-btn__arrow { transition: transform .2s; display: inline-block; }
.cd-wa-btn:hover .cd-wa-btn__arrow { transform: translateX(5px); }

.cd-wa-note {
    text-align: center;
    font-family: var(--tbt-font-mono);
    font-size: .68rem; color: var(--tbt-txt-dim);
    letter-spacing: .08em; text-transform: uppercase;
    margin-top: -.5rem;
}

.cd-sold-msg {
    padding: var(--tbt-s2) var(--tbt-s3);
    background: rgba(248,113,113,.08);
    border: 1px solid rgba(248,113,113,.2);
    border-radius: var(--tbt-r-sm);
    color: #f87171;
    font-family: var(--tbt-font-mono);
    font-size: .8rem; letter-spacing: .06em;
    text-transform: uppercase;
    text-align: center;
}

.cd-back {
    color: var(--tbt-txt-muted);
    font-family: var(--tbt-font-mono);
    font-size: .72rem; letter-spacing: .08em;
    text-decoration: none;
    transition: color .15s;
    text-transform: uppercase;
    display: inline-block;
}
.cd-back:hover { color: var(--tbt-jade); }

/* ── Lightbox ── */
#cd-lightbox {
    position: fixed; inset: 0; z-index: 9999;
    display: flex; align-items: center; justify-content: center;
}
#cd-lb-backdrop {
    position: absolute; inset: 0;
    background: rgba(0,0,0,.93);
    cursor: zoom-out;
    backdrop-filter: blur(6px);
}
#cd-lb-img {
    position: relative; z-index: 1;
    max-width: 90vw; max-height: 88vh;
    object-fit: contain;
    border-radius: var(--tbt-r-md);
    border: 1px solid var(--tbt-bg-4);
    box-shadow: 0 24px 80px rgba(0,0,0,.8);
    user-select: none;
}
#cd-lb-close {
    position: absolute; top: 1rem; right: 1rem; z-index: 2;
    width: 38px; height: 38px;
    background: var(--tbt-bg-2); border: 1px solid var(--tbt-bg-4);
    border-radius: var(--tbt-r-sm); color: var(--tbt-txt-white);
    font-size: 1rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: border-color .2s, color .2s;
}
#cd-lb-close:hover { border-color: var(--tbt-jade); color: var(--tbt-jade); }
#cd-lb-prev, #cd-lb-next {
    position: absolute; top: 50%; transform: translateY(-50%); z-index: 2;
    width: 48px; height: 48px;
    background: rgba(8,8,8,.8); border: 1px solid var(--tbt-bg-5);
    border-radius: var(--tbt-r-sm); color: var(--tbt-txt-white);
    font-size: 1.8rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s;
    backdrop-filter: blur(6px);
}
#cd-lb-prev { left: 1rem; }
#cd-lb-next { right: 1rem; }
#cd-lb-prev:hover, #cd-lb-next:hover { background: var(--tbt-jade); border-color: var(--tbt-jade); }
</style>

<script <?= csp_nonce() ?>>
(function() {
    const fotos = <?= json_encode(array_values($fotos)) ?>;
    if (!fotos.length) return;

    let current = 0;

    const mainImg   = document.getElementById('cd-main-img');
    const idxLabel  = document.getElementById('cd-idx');
    const thumbs    = document.querySelectorAll('.cd-thumb');
    const mainWrap  = document.querySelector('.cd-main-wrap');

    // Lightbox
    const lb        = document.getElementById('cd-lightbox');
    const lbImg     = document.getElementById('cd-lb-img');
    const lbClose   = document.getElementById('cd-lb-close');
    const lbPrev    = document.getElementById('cd-lb-prev');
    const lbNext    = document.getElementById('cd-lb-next');

    function goTo(i) {
        current = (i + fotos.length) % fotos.length;
        mainImg.src = fotos[current];
        if (idxLabel) idxLabel.textContent = current + 1;
        thumbs.forEach((t, ti) => t.classList.toggle('active', ti === current));
        thumbs[current]?.scrollIntoView({ behavior: 'smooth', inline: 'nearest' });
        if (lb && lb.style.display !== 'none') lbImg.src = fotos[current];
    }

    // Flechas galería
    document.getElementById('cd-prev')?.addEventListener('click', () => goTo(current - 1));
    document.getElementById('cd-next')?.addEventListener('click', () => goTo(current + 1));

    // Miniaturas
    thumbs.forEach(t => t.addEventListener('click', () => goTo(+t.dataset.i)));

    // Abrir lightbox al clic en foto principal
    mainWrap?.addEventListener('click', function(e) {
        if (e.target.closest('.cd-nav')) return;
        lbImg.src = fotos[current];
        lb.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });

    // Cerrar lightbox
    lbClose?.addEventListener('click', closeLb);
    document.getElementById('cd-lb-backdrop')?.addEventListener('click', closeLb);
    lbPrev?.addEventListener('click', () => goTo(current - 1));
    lbNext?.addEventListener('click', () => goTo(current + 1));

    function closeLb() { lb.style.display = 'none'; document.body.style.overflow = ''; }

    // Teclado
    document.addEventListener('keydown', e => {
        if (lb?.style.display !== 'none') {
            if (e.key === 'ArrowLeft')  goTo(current - 1);
            if (e.key === 'ArrowRight') goTo(current + 1);
            if (e.key === 'Escape')     closeLb();
        }
    });

    // Swipe táctil
    let touchX = 0;
    mainWrap?.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
    mainWrap?.addEventListener('touchend',   e => {
        const dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 40) goTo(dx < 0 ? current + 1 : current - 1);
    });
})();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
