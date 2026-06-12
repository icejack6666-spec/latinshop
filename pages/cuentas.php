<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$db      = Database::getInstance();
$search  = strip_tags(trim($_GET['q'] ?? ''));
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$where  = "status = 'active'";
$params = [];
if ($search !== '') {
    $where   .= " AND (title LIKE ? OR description LIKE ? OR server LIKE ?)";
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
}

$pdo  = $db->getPdo();
$stmt = $pdo->prepare("SELECT * FROM cuentas_venta WHERE {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?");
$i = 1;
foreach ($params as $p) $stmt->bindValue($i++, $p);
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i,   $offset,  PDO::PARAM_INT);
$stmt->execute();
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total      = $db->count("SELECT COUNT(*) FROM cuentas_venta WHERE {$where}", $params);
$totalPages = (int)ceil($total / $perPage);

$page_title       = 'Cuentas en Venta — Lords Mobile | Latin Shop';
$page_description = 'Compra cuentas de Lords Mobile verificadas. Servidor, poder, héroes y VIP detallados.';
$page_canonical   = u('/cuentas');

require_once INCLUDES_PATH . '/header.php';
?>

<section class="tbt-hero">
    <div class="tbt-wrap">
        <div class="tbt-enter" style="max-width:700px;">
            <div class="tbt-badge tbt-badge--jade tbt-mb-3">
                <span class="tbt-badge__dot"></span> Cuentas verificadas
            </div>
            <h1 class="tbt-h-xl tbt-mb-3">Cuentas en Venta</h1>
            <p class="tbt-body-lg">Cuentas de Lords Mobile con toda su información. Contacta directo por WhatsApp.</p>
        </div>
    </div>
</section>

<section class="tbt-section">
    <div class="tbt-wrap">

        <!-- Búsqueda -->
        <form method="GET" action="<?= u('/cuentas') ?>" class="cv-search tbt-mb-4">
            <div class="cv-search__bar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                       placeholder="Buscar por servidor, poder, héroes…">
                <?php if ($search): ?>
                <a href="<?= u('/cuentas') ?>" class="cv-search__clear">✕</a>
                <?php endif; ?>
            </div>
            <button type="submit" class="tbt-btn tbt-btn--jade tbt-btn--sm">Buscar</button>
        </form>

        <?php if (empty($cuentas)): ?>
        <div class="cv-empty">
            <span>🏰</span>
            <p>No hay cuentas disponibles<?= $search ? ' para "'.htmlspecialchars($search).'"' : ' en este momento' ?>.</p>
        </div>
        <?php else: ?>

        <!-- Grid de cuentas -->
        <div class="cuentas-grid">
            <?php foreach ($cuentas as $c):
                // Primera imagen disponible como portada
                $imgSrc = null;
                for ($n = 1; $n <= 10; $n++) {
                    $col = $n === 1 ? 'image_url' : "image_url_{$n}";
                   if (!empty($c[$col])) { $imgSrc = SITE_URL . '/' . htmlspecialchars($c[$col]); break; }
                }
                $imgSrc = $imgSrc ?? (ASSETS_URL . '/images/cuenta-placeholder.webp');

                // Contar fotos disponibles
                $photoCount = 0;
                for ($n = 1; $n <= 10; $n++) {
                    $col = $n === 1 ? 'image_url' : "image_url_{$n}";
                    if (!empty($c[$col])) $photoCount++;
                }

                $detailUrl = u('/cuentas/ver') . '?id=' . $c['id'];
            ?>
            <div class="cuenta-card">
                <!-- Imagen portada -->
                <a href="<?= $detailUrl ?>" class="cuenta-card__img-wrap">
                    <img src="<?= $imgSrc ?>"
                         alt="<?= htmlspecialchars($c['title']) ?>"
                         loading="lazy"
                         class="cuenta-card__img"
                         onerror="this.src='<?= ASSETS_URL ?>/images/cuenta-placeholder.webp'">
                    <?php if ($c['status'] === 'sold'): ?>
                    <div class="cuenta-card__sold-badge">VENDIDA</div>
                    <?php endif; ?>
                    <?php if ($photoCount > 1): ?>
                    <div class="cuenta-card__photo-count">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                        <?= $photoCount ?>
                    </div>
                    <?php endif; ?>
                </a>

                <!-- Cuerpo -->
                <div class="cuenta-card__body">
                    <h3 class="cuenta-card__title">
                        <a href="<?= $detailUrl ?>"><?= htmlspecialchars($c['title']) ?></a>
                    </h3>

                    <!-- Stats rápidos -->
                    <div class="cuenta-card__stats">
                        <?php if (!empty($c['power'])): ?>
                        <div class="cuenta-stat">
                            <span class="cuenta-stat__label">Poder</span>
                            <span class="cuenta-stat__val"><?= htmlspecialchars($c['power']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($c['server'])): ?>
                        <div class="cuenta-stat">
                            <span class="cuenta-stat__label">Servidor</span>
                            <span class="cuenta-stat__val"><?= htmlspecialchars($c['server']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($c['kingdom'])): ?>
                        <div class="cuenta-stat">
                            <span class="cuenta-stat__label">Reino</span>
                            <span class="cuenta-stat__val"><?= htmlspecialchars($c['kingdom']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($c['vip_level'] !== null): ?>
                        <div class="cuenta-stat">
                            <span class="cuenta-stat__label">VIP</span>
                            <span class="cuenta-stat__val"><?= (int)$c['vip_level'] ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($c['description'])): ?>
                    <p class="cuenta-card__desc"><?= nl2br(htmlspecialchars(mb_substr($c['description'], 0, 100))) ?>…</p>
                    <?php endif; ?>

                    <!-- Footer: precio + botones -->
                    <div class="cuenta-card__footer">
                        <div class="cuenta-price">
                            <?php if ($c['price'] > 0): ?>
                            <span class="cuenta-price__val"><?= number_format((float)$c['price'], 0, '.', ',') ?></span>
                            <span class="cuenta-price__cur"><?= htmlspecialchars($c['currency']) ?></span>
                            <?php else: ?>
                            <span class="cuenta-price__val">Consultar</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= $detailUrl ?>" class="cv-btn-ver">
                            Ver más <span>→</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination tbt-mt-5">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="<?= u('/cuentas') . ($search ? '?q='.urlencode($search).'&page='.$p : '?page='.$p) ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<style>
/* ── Búsqueda ── */
.cv-search { display:flex; gap:.75rem; align-items:center; max-width:600px; }
.cv-search__bar {
    flex:1; display:flex; align-items:center; gap:.5rem;
    background:var(--tbt-bg-1); border:1px solid var(--tbt-bg-4);
    border-radius:var(--tbt-r-sm); padding:.55rem .9rem;
    transition:border-color .2s, box-shadow .2s;
}
.cv-search__bar:focus-within { border-color:var(--tbt-jade); box-shadow:0 0 0 3px var(--tbt-jade-08); }
.cv-search__bar svg { color:var(--tbt-txt-muted); flex-shrink:0; }
.cv-search__bar input { flex:1; background:transparent; border:none; outline:none; color:var(--tbt-txt-white); font-family:var(--tbt-font-body); font-size:.95rem; }
.cv-search__bar input::placeholder { color:var(--tbt-txt-dim); font-style:italic; }
.cv-search__clear { color:var(--tbt-txt-muted); text-decoration:none; font-size:.8rem; transition:color .15s; }
.cv-search__clear:hover { color:var(--tbt-txt-white); }

/* ── Empty state ── */
.cv-empty { text-align:center; padding:4rem 2rem; color:var(--tbt-txt-muted); }
.cv-empty span { display:block; font-size:3rem; margin-bottom:1rem; }
.cv-empty p { font-family:var(--tbt-font-display); font-size:1.3rem; letter-spacing:.05em; }

/* ── Card ── */
.cuenta-card {
    background:var(--tbt-bg-1);
    border:1px solid var(--tbt-bg-3);
    border-top:2px solid var(--tbt-bg-3);
    border-radius:var(--tbt-r-md);
    overflow:hidden;
    display:flex; flex-direction:column;
    transition:border-color .25s, transform .25s, box-shadow .25s;
    clip-path:polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 0 100%);
}
.cuenta-card:hover {
    border-color:var(--tbt-jade-40);
    border-top-color:var(--tbt-jade);
    transform:translateY(-4px);
    box-shadow:0 16px 40px rgba(0,0,0,.5), 0 0 0 1px var(--tbt-jade-15);
}

/* Imagen portada */
.cuenta-card__img-wrap {
    display:block; position:relative;
    aspect-ratio:4/3; overflow:hidden; text-decoration:none;
}
.cuenta-card__img {
    width:100%; height:100%; object-fit:cover;
    transition:transform .4s var(--tbt-ease);
    background:var(--tbt-bg-3);
}
.cuenta-card:hover .cuenta-card__img { transform:scale(1.05); }

.cuenta-card__sold-badge {
    position:absolute; inset:0;
    background:rgba(0,0,0,.7);
    display:flex; align-items:center; justify-content:center;
    font-family:var(--tbt-font-display);
    font-size:1.8rem; letter-spacing:.12em;
    color:#f87171;
}
.cuenta-card__photo-count {
    position:absolute; bottom:.5rem; right:.5rem;
    display:flex; align-items:center; gap:.3rem;
    background:rgba(0,0,0,.75);
    color:#fff; font-family:var(--tbt-font-mono);
    font-size:.68rem; font-weight:600;
    padding:.2rem .5rem; border-radius:3px;
    backdrop-filter:blur(4px);
}

/* Cuerpo */
.cuenta-card__body { padding:1.1rem; display:flex; flex-direction:column; gap:.65rem; flex:1; }
.cuenta-card__title { margin:0; }
.cuenta-card__title a {
    font-family:var(--tbt-font-display);
    font-size:1.3rem; letter-spacing:.04em; font-weight:400;
    color:var(--tbt-txt-white); text-decoration:none;
    transition:color .2s; line-height:1.1;
}
.cuenta-card__title a:hover { color:var(--tbt-jade); }

.cuenta-card__stats { display:grid; grid-template-columns:repeat(2,1fr); gap:.35rem; }
.cuenta-stat {
    background:var(--tbt-bg-2); border:1px solid var(--tbt-bg-4);
    border-radius:var(--tbt-r-sm); padding:.4rem .6rem;
    display:flex; flex-direction:column; gap:.1rem;
}
.cuenta-stat__label { font-size:.62rem; color:var(--tbt-txt-muted); text-transform:uppercase; letter-spacing:.08em; font-family:var(--tbt-font-mono); }
.cuenta-stat__val   { font-size:.9rem; font-weight:600; color:var(--tbt-txt-white); font-family:var(--tbt-font-display); letter-spacing:.03em; }

.cuenta-card__desc { font-size:.85rem; color:var(--tbt-txt-base); line-height:1.5; }

/* Footer */
.cuenta-card__footer {
    display:flex; align-items:center; justify-content:space-between;
    gap:.75rem; margin-top:auto;
    padding-top:.65rem; border-top:1px solid var(--tbt-bg-3);
}
.cuenta-price { display:flex; align-items:baseline; gap:.3rem; }
.cuenta-price__val { font-family:var(--tbt-font-display); font-size:1.5rem; color:var(--tbt-jade); letter-spacing:.04em; }
.cuenta-price__cur { font-size:.72rem; color:var(--tbt-txt-muted); text-transform:uppercase; font-family:var(--tbt-font-mono); }

/* Botón Ver más */
.cv-btn-ver {
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.4rem 1rem;
    background:transparent;
    border:1px solid var(--tbt-bg-5);
    border-radius:var(--tbt-r-sm);
    color:var(--tbt-txt-light);
    font-family:var(--tbt-font-display);
    font-size:.95rem; letter-spacing:.08em;
    text-decoration:none;
    transition:all .2s var(--tbt-ease);
    clip-path:polygon(0 0, calc(100% - 7px) 0, 100% 7px, 100% 100%, 7px 100%, 0 calc(100% - 7px));
}
.cv-btn-ver:hover {
    border-color:var(--tbt-jade);
    color:var(--tbt-jade);
    background:var(--tbt-jade-08);
    box-shadow:0 0 16px var(--tbt-jade-15);
}
.cv-btn-ver span { transition:transform .2s; display:inline-block; }
.cv-btn-ver:hover span { transform:translateX(4px); }
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
