<!-- -->
<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$page_title       = 'Calculadora de Gemas Lords Mobile | Latin Shop';
$page_description = 'Calcula cuántas gemas necesitas para cualquier ítem de Lords Mobile. Speed Ups, Combat, Recursos, Cofres y más.';
$page_canonical   = SITE_URL . '/gems';

include INCLUDES_PATH . '/header.php';
?>

<section class="tbt-hero" style="background: var(--tbt-bg-alt);">
    <div class="tbt-wrap">
        <div class="tbt-enter" style="max-width:700px;">
            <div class="tbt-badge tbt-badge--jade tbt-mb-3">
                <span class="tbt-badge__dot"></span>
                Calculadora de Gemas
            </div>
            <h1 class="tbt-h-xl tbt-mb-3">
                ¿Cuántas gemas necesitas?<br>
                <span class="tbt-jade"></span>
            </h1>
            <p class="tbt-body-lg">
                Selecciona los ítems que quieres, pon la cantidad y calcula el total.
                Cuando tengas tu lista, envíanosla por WhatsApp y te damos precio.
            </p>
        </div>
    </div>
</section>

<section class="tbt-section">
    <div class="tbt-wrap">
        <div class="gem-layout">

            <div class="gem-panel">

                <div class="gem-tabs" role="tablist">
                    <button class="gem-tab active" data-tab="speedups"     role="tab">Speed Ups</button>
                    <button class="gem-tab"        data-tab="combat"       role="tab">Combat</button>
                    <button class="gem-tab"        data-tab="boost"        role="tab">Boost</button>
                    <button class="gem-tab"        data-tab="resources"    role="tab">Resources</button>
                    <button class="gem-tab"        data-tab="chests"       role="tab">Chests</button>
                    <button class="gem-tab"        data-tab="buildings"    role="tab">Buildings</button>
                    <button class="gem-tab"        data-tab="familiar"     role="tab">Familiar</button>
                    <button class="gem-tab"        data-tab="monsterhunt"  role="tab">Monster Hunt</button>
                </div>

                <?php
                $ASSETS = SITE_URL . '/frontend/assets/images/items';

                $categories = [

                    'speedups' => [
                        ['img'=>'speedups/s60m.webp',  'name'=>'Speed 60 Minutes',  'cost'=>130],
                        ['img'=>'speedups/s3h.webp',   'name'=>'Speed 3 Hours',     'cost'=>300],
                        ['img'=>'speedups/s8h.webp',   'name'=>'Speed 8 Hours',     'cost'=>650],
                        ['img'=>'speedups/s15h.webp',  'name'=>'Speed 15 Hours',    'cost'=>1000],
                        ['img'=>'speedups/s24h.webp',  'name'=>'Speed 24 Hours',    'cost'=>1500],
                        ['img'=>'speedups/s3d.webp',   'name'=>'Speed 3 Days',      'cost'=>4400],
                        ['img'=>'speedups/s7d.webp',   'name'=>'Speed 7 Days',      'cost'=>10000],
                        ['img'=>'speedups/s30d.webp',  'name'=>'Speed 30 Days',     'cost'=>40000],
                    ],

                    'combat' => [
                        ['img'=>'combat/braveheart.webp', 'name'=>'Braveheart',                   'cost'=>2000],
                        ['img'=>'combat/rrelo.webp',      'name'=>'Random Relocator',              'cost'=>500],
                        ['img'=>'combat/relo.webp',       'name'=>'Relocator',                     'cost'=>1500],
                        ['img'=>'combat/rp.webp',         'name'=>'Royal Pass',                    'cost'=>100000],
                        ['img'=>'combat/wb.webp',         'name'=>'Winged Boots 25%',              'cost'=>500],
                        ['img'=>'combat/wb.webp',         'name'=>'Winged Boots 50%',              'cost'=>900],
                        ['img'=>'combat/atkb.webp',       'name'=>'Attack Boost 20% 12h',          'cost'=>250],
                        ['img'=>'combat/atkb.webp',       'name'=>'Attack Boost 20% 24h',          'cost'=>400],
                        ['img'=>'combat/defb.webp',       'name'=>'Army Defence Boost 20% 12h',    'cost'=>250],
                        ['img'=>'combat/defb.webp',       'name'=>'Defence Boost 20% 24h',         'cost'=>400],
                        ['img'=>'combat/asize.webp',      'name'=>'Army Size Boost 20% 4h',        'cost'=>2400],
                        ['img'=>'combat/asize.webp',      'name'=>'Army Size Boost 50% 4h',        'cost'=>5000],
                        ['img'=>'combat/sh8h.webp',       'name'=>'Shield 8 Hours',                'cost'=>500],
                        ['img'=>'combat/sh24h.webp',      'name'=>'Shield 24 Hours',               'cost'=>1000],
                        ['img'=>'combat/sh3d.webp',       'name'=>'Shield 3 Days',                 'cost'=>3500],
                        ['img'=>'combat/sh7d.webp',       'name'=>'Shield 7 Days',                 'cost'=>10000],
                        ['img'=>'combat/sh24d.webp',      'name'=>'Shield 14 Days',                'cost'=>25000],
                        ['img'=>'combat/as24.webp',       'name'=>'Anti-Scout 24h',                'cost'=>600],
                        ['img'=>'combat/as3d.webp',       'name'=>'Anti-Scout 3 Days',             'cost'=>1200],
                        ['img'=>'combat/as7d.webp',       'name'=>'Anti-Scout 7 Days',             'cost'=>3000],
                    ],

                    'boost' => [
                        ['img'=>'boost/xp.webp',    'name'=>'25% Player EXP Boost 24h',  'cost'=>2500],
                        ['img'=>'boost/tr.webp',    'name'=>'Talent Reset',               'cost'=>1000],
                        ['img'=>'boost/tt.webp',    'name'=>'Talent Tome',                'cost'=>1200],
                        ['img'=>'boost/qsa.webp',   'name'=>'Quest Scroll (Admin)',        'cost'=>800],
                        ['img'=>'boost/qsg.webp',   'name'=>'Quest Scroll (Guild)',        'cost'=>1000],
                        ['img'=>'boost/vp5.webp',   'name'=>'500 VIP Points',             'cost'=>500],
                        ['img'=>'boost/vp10.webp',  'name'=>'1000 VIP Points',            'cost'=>1000],
                        ['img'=>'boost/vp50.webp',  'name'=>'5000 VIP Points',            'cost'=>5000],
                        ['img'=>'boost/lt1.webp',   'name'=>'10 Lucky Tokens',            'cost'=>6600],
                        ['img'=>'boost/lt10.webp',  'name'=>'100 Lucky Tokens',           'cost'=>60000],
                        ['img'=>'boost/hs1.webp',   'name'=>'1000 Holy Stars',            'cost'=>2200],
                        ['img'=>'boost/hs10.webp',  'name'=>'10000 Holy Stars',           'cost'=>20000],
                    ],

                    'resources' => [
                        ['img'=>'resources/gb.webp',   'name'=>'Gather Boost 50% 24h',    'cost'=>600],
                        ['img'=>'resources/gb.webp',   'name'=>'Gather Boost 50% 7d',     'cost'=>3360],
                        ['img'=>'resources/upk.webp',  'name'=>'Reduce Upkeep 50% 24h',   'cost'=>2000],
                        ['img'=>'resources/upk.webp',  'name'=>'Reduce Upkeep 50% 7d',    'cost'=>11200],
                        ['img'=>'resources/f20.webp',  'name'=>'Food 20 Million',          'cost'=>10000],
                        ['img'=>'resources/f60.webp',  'name'=>'Food 60 Million',          'cost'=>28000],
                        ['img'=>'resources/s5.webp',   'name'=>'Stone 5 Million',          'cost'=>10000],
                        ['img'=>'resources/s15.webp',  'name'=>'Stone 15 Million',         'cost'=>28000],
                        ['img'=>'resources/w5.webp',   'name'=>'Wood 5 Million',           'cost'=>10000],
                        ['img'=>'resources/w15.webp',  'name'=>'Wood 15 Million',          'cost'=>28000],
                        ['img'=>'resources/o5.webp',   'name'=>'Ore 5 Million',            'cost'=>10000],
                        ['img'=>'resources/o15.webp',  'name'=>'Ore 15 Million',           'cost'=>28000],
                        ['img'=>'resources/g2.webp',   'name'=>'Gold 2 Million',           'cost'=>10000],
                        ['img'=>'resources/g6.webp',   'name'=>'Gold 6 Million',           'cost'=>28000],
                    ],

                    'chests' => [
                        ['img'=>'Chests/rc.webp',    'name'=>'Rare Material Chest',       'cost'=>1500],
                        ['img'=>'Chests/ec.webp',    'name'=>'Epic Material Chest',       'cost'=>3000],
                        ['img'=>'Chests/lc.webp',    'name'=>'Legendary Material Chest',  'cost'=>3000],
                        ['img'=>'Chests/rj.webp',    'name'=>'Rare Jewel Chest',          'cost'=>3000],
                        ['img'=>'Chests/ej.webp',    'name'=>'Epic Jewel Chest',          'cost'=>6000],
                        ['img'=>'Chests/lj.webp',    'name'=>'Legendary Jewel Chest',     'cost'=>6000],
                        ['img'=>'Chests/shg.webp',   'name'=>'Chisel I',                  'cost'=>400],
                        ['img'=>'Chests/shgr.webp',  'name'=>'Chisel II',                 'cost'=>1000],
                        ['img'=>'Chests/shb.webp',   'name'=>'Chisel III',                'cost'=>2000],
                        ['img'=>'Chests/shp.webp',   'name'=>'Chisel IV',                 'cost'=>3000],
                        ['img'=>'Chests/shgo.webp',  'name'=>'Chisel V',                  'cost'=>4000],
                    ],

                    'buildings' => [
                        ['img'=>'buildings/art.webp',  'name'=>'Archaic Tome',      'cost'=>900],
                        ['img'=>'buildings/gh.webp',   'name'=>'Gold Hammer',       'cost'=>2000],
                        ['img'=>'buildings/wt.webp',   'name'=>'War Tomes',         'cost'=>15],
                        ['img'=>'buildings/scu.webp',  'name'=>'Steel Cuffs',       'cost'=>15],
                        ['img'=>'buildings/sc.webp',   'name'=>'Soul Crystal',      'cost'=>15],
                        ['img'=>'buildings/cp.webp',   'name'=>'Crystal Pickaxe',   'cost'=>20],
                        ['img'=>'buildings/fd.webp',   'name'=>'Finish Demolition', 'cost'=>20],
                    ],

                    'familiar' => [
                        ['img'=>'familiar/brto.webp',  'name'=>'Bright Talent Orb',           'cost'=>3000],
                        ['img'=>'familiar/blto.webp',  'name'=>'Brilliant Talent Orb',         'cost'=>7500],
                        ['img'=>'familiar/m60m.webp',  'name'=>'Merge 60 Minutes',             'cost'=>260],
                        ['img'=>'familiar/m3h.webp',   'name'=>'Merge 3 Hours',                'cost'=>600],
                        ['img'=>'familiar/m8h.webp',   'name'=>'Merge 8 Hours',                'cost'=>1300],
                        ['img'=>'familiar/m24h.webp',  'name'=>'Merge 24 Hours',               'cost'=>3000],
                        ['img'=>'familiar/m3d.webp',   'name'=>'Merge 3 Days',                 'cost'=>8800],
                        ['img'=>'familiar/m7d.webp',   'name'=>'Merge 7 Days',                 'cost'=>20000],
                        ['img'=>'familiar/a2m.webp',   'name'=>'2M Anima',                     'cost'=>10000],
                        ['img'=>'familiar/a6m.webp',   'name'=>'6M Anima',                     'cost'=>28000],
                        ['img'=>'familiar/ac.webp',    'name'=>'Ancient Core',                 'cost'=>1000],
                        ['img'=>'familiar/cc.webp',    'name'=>'Chaos Core',                   'cost'=>7500],
                        ['img'=>'familiar/pmb.webp',   'name'=>'10% Pact Merging Boost 1h',    'cost'=>1000],
                    ],

                    'monsterhunt' => [
                        ['img'=>'monsterhunt/mh.webp',    'name'=>'Monster Hunt ATK Boost 25%',  'cost'=>1000],
                        ['img'=>'monsterhunt/mh5.webp',   'name'=>'5,000 Energy',                'cost'=>1125],
                        ['img'=>'monsterhunt/mh10.webp',  'name'=>'10,000 Energy',               'cost'=>2000],
                        ['img'=>'monsterhunt/mh20.webp',  'name'=>'20,000 Energy',               'cost'=>3500],
                        ['img'=>'monsterhunt/mh50.webp',  'name'=>'50,000 Energy',               'cost'=>7500],
                    ],
                ];

                foreach ($categories as $catKey => $items):
                    $isActive = $catKey === 'speedups' ? 'active' : '';
                ?>
                <div class="gem-table-wrap <?= $isActive ?>" id="tab-<?= $catKey ?>">
                    <table class="gem-table">
                        <thead>
                            <tr>
                                <th style="width:56px;"></th>
                                <th>Ítem</th>
                                <th>Gemas</th>
                                <th style="width:120px;">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item):
                            $safeName = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr>
                                <td class="gem-img-cell">
                                    <img src="<?= $ASSETS ?>/<?= $item['img'] ?>"
                                         alt="<?= $safeName ?>"
                                         onerror="this.style.opacity='0.2'">
                                </td>
                                <td class="gem-item-name"><?= $safeName ?></td>
                                <td class="gem-item-cost"><?= number_format($item['cost']) ?></td>
                                <td>
                                    <input type="number"
                                           min="0" max="99999" value="0"
                                           class="gem-qty"
                                           data-name="<?= $safeName ?>"
                                           data-cost="<?= $item['cost'] ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>

            </div><!-- /.gem-panel -->

            <!-- Panel derecho: wishlist + total + WhatsApp -->
            <div class="gem-sidebar">
                <div class="gem-wishlist-card">
                    <h2 class="gem-wishlist-title">Mi Lista</h2>

                    <!-- PODER -->
                    <div class="gem-field-group">
                        <label class="gem-field-label" for="input-poder">Mi poder aproximado</label>
                        <div class="gem-field-row">
                            <input type="number" id="input-poder" min="1" max="999999" class="gem-field-input" placeholder="Ej: 250">
                            <span class="gem-field-unit" id="poder-unit">M</span>
                        </div>
                        <span class="gem-field-preview" id="poder-preview"></span>
                    </div>

                    <!-- REINO -->
                    <div class="gem-field-group">
                        <label class="gem-field-label" for="input-reino">Mi reino</label>
                        <div class="gem-field-row">
                            <input type="number" id="input-reino" min="1" max="9999" class="gem-field-input" placeholder="Ej: 1500">
                        </div>
                        <div class="gem-reino-status" id="reino-status" style="display:none;"></div>
                        <span class="gem-field-hint" id="reino-max-info"></span>
                    </div>

                    <div class="gem-wishlist-empty" id="wishlist-empty">
                        <span>💎</span>
                        <p>Agrega ítems poniendo una cantidad</p>
                    </div>

                    <table class="gem-wishlist-table" id="wishlist-table" style="display:none;">
                        <thead>
                            <tr>
                                <th>Ítem</th>
                                <th>Cant.</th>
                                <th>Gemas</th>
                            </tr>
                        </thead>
                        <tbody id="wishlist-body"></tbody>
                    </table>

                    <!-- TOTAL -->
                    <div class="gem-total" id="gem-total">
                        <span>Total</span>
                        <span class="gem-total-num" id="total-display">0</span>
                    </div>

                    <!-- PRECIO USD (se pega justo debajo del total) -->
                    <div class="gem-usd-block" id="gem-usd-block" style="display:none;">
                        <div class="gem-usd-row">
                            <span class="gem-usd-label">💵 Precio estimado</span>
                            <span class="gem-usd-val" id="gem-usd-val">—</span>
                        </div>
                        <p class="gem-usd-note" id="gem-usd-note"></p>
                    </div>

                    <button class="btn-gem-clear" id="btn-clear" style="display:none;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        Limpiar lista
                    </button>

                    <!-- SELECTOR DE VENDEDOR -->
                    <div class="gem-field-group">
                        <label class="gem-field-label">Elige tu vendedor</label>
                        <div class="gem-vendedores">
                            <button class="gem-vendedor active"
                                    data-wa="<?= WHATSAPP_NUMBER ?>"
                                    data-nombre="MAFIAS">
                                <span class="gem-vendedor__avatar">
                                <img src="<?= ASSETS_URL ?>/images/vendedores/mafias.jpg"
                                    alt="MAFIAS"
                                    onerror="this.style.display='none';this.parentElement.dataset.initial='M'">
                                </span>
                                <span>MAFIAS</span>
                             </button>
                            <button class="gem-vendedor"
                                    data-wa="51994361594"
                                    data-nombre="MinimoMM">
                                <span class="gem-vendedor__avatar">
                                <img src="<?= ASSETS_URL ?>/images/vendedores/mininomm.jpg"
                                   alt="MinimoMM"
                                   onerror="this.style.display='none';this.parentElement.dataset.initial='L'">
                                </span>
                                <span>MininoMM</span>
                            </button>
                        </div>
                    </div>

                    <a href="#" id="btn-whatsapp" class="gem-wa-btn" target="_blank" rel="noopener noreferrer">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2Z"/></svg>
                        Pedir con <span id="wa-vendedor-nombre">MAFIAS</span>
                    </a>

                    <p class="gem-wa-hint">
                        Se enviará tu lista completa con el total de gemas automáticamente.
                    </p>
                </div>
            </div>

        </div><!-- /.gem-layout -->
    </div>
</section>

<style>
/* ─── LAYOUT ─────────────────────────────────────────── */
.gem-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
    align-items: start;
}
@media (max-width: 900px) {
    .gem-layout { grid-template-columns: 1fr; }
    .gem-sidebar { position: static; }
}

/* ─── RESPONSIVE MÓVIL ───────────────────────────────── */
@media (max-width: 600px) {
    .gem-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: .5rem;
        gap: .4rem;
        scrollbar-width: none;
    }
    .gem-tabs::-webkit-scrollbar { display: none; }
    .gem-tab { white-space: nowrap; flex-shrink: 0; font-size: .75rem; padding: .4rem .8rem; }

    .gem-img-cell { padding: .3rem .4rem; }
    .gem-img-cell img { width: 28px; height: 28px; padding: 2px; }
    .gem-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .gem-table { min-width: 280px; font-size: .82rem; }
    .gem-item-name { padding: .6rem .4rem; }
    .gem-item-cost { padding: .6rem .4rem; font-size: .82rem; }
    .gem-qty { width: 65px; padding: .35rem .4rem; font-size: .82rem; }

    .gem-wishlist-card { padding: 1.1rem; gap: .85rem; }
    .gem-total-num { font-size: 1.2rem; }

    .gem-field-input { font-size: .85rem; padding: .45rem .6rem; }
    .gem-field-unit { font-size: .8rem; padding: .45rem .6rem; min-width: 32px; }

    .gem-vendedores { gap: .3rem; }
    .gem-vendedor { font-size: .68rem; padding: .5rem .2rem; }
    .gem-vendedor__avatar { width: 28px; height: 28px; font-size: .78rem; }
}

/* ─── TABS ───────────────────────────────────────────── */
.gem-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-bottom: 1.25rem;
}
.gem-tab {
    padding: .45rem 1rem;
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-4);
    color: var(--tbt-txt-sub);
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    border-radius: 2px;
    transition: all .2s;
    font-family: var(--tbt-font-body);
}
.gem-tab:hover { border-color: var(--tbt-txt-muted); color: var(--tbt-txt-white); }
.gem-tab.active {
    background: var(--tbt-jade);
    border-color: var(--tbt-jade);
    color: #000;
    box-shadow: 0 0 12px rgba(232,96,44,.35);
}

/* ─── TABLA ──────────────────────────────────────────── */
.gem-table-wrap { display: none; }
.gem-table-wrap.active { display: block; }

.gem-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-2);
    border-radius: 2px;
    overflow: hidden;
}
.gem-table thead th {
    padding: .75rem 1rem;
    background: var(--tbt-bg-2);
    color: var(--tbt-txt-sub);
    font-size: .75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    text-align: left;
}
.gem-table tbody tr {
    border-bottom: 1px solid var(--tbt-bg-2);
    transition: background .15s;
}
.gem-table tbody tr:last-child { border-bottom: none; }
.gem-table tbody tr:hover { background: rgba(255,255,255,.02); }

.gem-img-cell { padding: .5rem .75rem; }
.gem-img-cell img {
    width: 40px; height: 40px;
    object-fit: contain;
    border-radius: 2px;
    background: var(--tbt-bg-2);
    padding: 4px;
}
.gem-item-name { padding: .75rem .5rem; font-size: .875rem; color: var(--tbt-txt-light); }
.gem-item-cost { padding: .75rem .5rem; font-size: .875rem; color: var(--tbt-jade); font-weight: 600; white-space: nowrap; }

.gem-qty {
    width: 80px;
    padding: .4rem .6rem;
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-4);
    color: var(--tbt-txt-white);
    font-size: .875rem;
    border-radius: 2px;
    text-align: center;
    transition: border-color .2s;
    font-family: var(--tbt-font-body);
}
.gem-qty:focus { outline: none; border-color: var(--tbt-jade); box-shadow: 0 0 0 2px rgba(232,96,44,.15); }
.gem-qty::-webkit-inner-spin-button { opacity: 1; }

/* ─── SIDEBAR ────────────────────────────────────────── */
.gem-sidebar { position: sticky; top: 90px; }

.gem-wishlist-card {
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-4);
    border-radius: 2px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.gem-wishlist-title {
    font-family: var(--tbt-font-display);
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--tbt-txt-white);
    padding-bottom: .75rem;
    border-bottom: 1px solid var(--tbt-bg-2);
}
.gem-wishlist-empty {
    text-align: center;
    padding: 2rem 0;
    color: var(--tbt-txt-muted);
    font-size: .875rem;
}
.gem-wishlist-empty span { display: block; font-size: 2rem; margin-bottom: .5rem; }

.gem-wishlist-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.gem-wishlist-table th { color: var(--tbt-txt-muted); text-align: left; padding: .25rem .3rem; font-weight: 600; }
.gem-wishlist-table td { padding: .35rem .3rem; color: var(--tbt-txt-base); border-bottom: 1px solid var(--tbt-bg-2); vertical-align: middle; }
.gem-wishlist-table td:last-child { color: var(--tbt-jade); font-weight: 600; white-space: nowrap; }
.gem-wishlist-table tr:last-child td { border-bottom: none; }
.wl-name { max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ─── TOTAL ──────────────────────────────────────────── */
.gem-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .85rem 1rem;
    background: var(--tbt-bg-alt);
    border: 1px solid rgba(232,96,44,.2);
    border-radius: 2px;
    margin-bottom: 0;
}
.gem-total.has-usd {
    border-radius: 2px 2px 0 0;
}
.gem-total span:first-child { font-size: .85rem; color: var(--tbt-txt-sub); font-weight: 600; }
.gem-total-num {
    font-family: var(--tbt-font-display);
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--tbt-jade);
}

/* ─── BLOQUE USD (pegado debajo del total) ───────────── */
.gem-usd-block {
    background: rgba(240,180,41,.07);
    border: 1px solid rgba(240,180,41,.25);
    border-top: none;
    border-radius: 0 0 2px 2px;
    padding: .55rem 1rem;
}
.gem-usd-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .5rem;
}
.gem-usd-label {
    font-size: .73rem;
    color: var(--tbt-txt-muted);
    font-weight: 600;
}
.gem-usd-val {
    font-size: .98rem;
    font-weight: 700;
    color: #f0b429;
    font-family: var(--tbt-font-display);
    white-space: nowrap;
}
.gem-usd-note {
    font-size: .67rem;
    color: var(--tbt-txt-muted);
    margin: .2rem 0 0;
    line-height: 1.4;
}

.btn-gem-clear {
    width: 100%;
    background: transparent;
    border: 1px solid var(--tbt-bg-4);
    color: var(--tbt-txt-muted);
    font-size: .8rem;
    font-weight: 600;
    padding: .55rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all .2s;
    font-family: var(--tbt-font-body);
    letter-spacing: .04em;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
}
.btn-gem-clear:hover {
    background: rgba(255,255,255,.04);
    border-color: var(--tbt-txt-muted);
    color: var(--tbt-txt-base);
}

.gem-wa-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    width: 100%;
    padding: .75rem 1rem;
    background: #25d366;
    color: #000;
    font-weight: 700;
    font-size: .9rem;
    font-family: var(--tbt-font-body);
    border-radius: 8px;
    text-decoration: none;
    transition: background .2s, transform .1s, box-shadow .2s;
    box-shadow: 0 4px 14px rgba(37,211,102,.3);
    letter-spacing: .02em;
}
.gem-wa-btn:hover {
    background: #20c45c;
    box-shadow: 0 6px 20px rgba(37,211,102,.45);
    transform: translateY(-1px);
    color: #000;
    text-decoration: none;
}
.gem-wa-btn:active { transform: translateY(0); }
.gem-wa-hint { font-size: .72rem; color: var(--tbt-txt-muted); text-align: center; line-height: 1.4; }

/* ─── CAMPOS PODER / REINO ───────────────────────────── */
.gem-field-group { display: flex; flex-direction: column; gap: .4rem; }
.gem-field-label { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--tbt-txt-sub); }
.gem-field-row { display: flex; align-items: center; gap: .4rem; }
.gem-field-input {
    flex: 1;
    padding: .5rem .75rem;
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-4);
    color: var(--tbt-txt-white);
    font-size: .9rem;
    border-radius: 7px;
    font-family: var(--tbt-font-body);
    transition: border-color .2s;
    min-width: 0;
}
.gem-field-input:focus { outline: none; border-color: var(--tbt-jade); box-shadow: 0 0 0 2px rgba(232,96,44,.15); }
.gem-field-unit {
    padding: .5rem .7rem;
    background: var(--tbt-jade);
    color: #000;
    font-weight: 700;
    font-size: .85rem;
    border-radius: 7px;
    min-width: 36px;
    text-align: center;
}
.gem-field-preview { font-size: .8rem; color: var(--tbt-jade); font-weight: 600; min-height: 1rem; }
.gem-field-hint { font-size: .75rem; color: var(--tbt-txt-muted); min-height: 1rem; }

.gem-reino-status {
    padding: .45rem .75rem;
    border-radius: 7px;
    font-size: .8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.gem-reino-status.ok   { background: rgba(39,201,113,.12); color: var(--tbt-jade); border: 1px solid rgba(39,201,113,.25); }
.gem-reino-status.warn { background: rgba(232,96,44,.08);   color: var(--tbt-jade); border: 1px solid rgba(232,96,44,.2); }
.gem-reino-status.soon { background: rgba(255,190,0,.1);   color: #ffbe00; border: 1px solid rgba(255,190,0,.25); }

/* ─── SELECTOR DE VENDEDORES ─────────────────────────── */
.gem-vendedores {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: .4rem;
}
.gem-vendedor {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .3rem;
    padding: .6rem .3rem;
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-4);
    border-radius: 8px;
    color: var(--tbt-txt-sub);
    font-size: .72rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
    font-family: var(--tbt-font-body);
}
.gem-vendedor:hover {
    border-color: var(--tbt-jade);
    color: var(--tbt-txt-white);
}
.gem-vendedor.active {
    background: rgba(232,96,44,.12);
    border-color: var(--tbt-jade);
    color: var(--tbt-jade);
    box-shadow: 0 0 10px rgba(232,96,44,.2);
}
.gem-vendedor__avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--tbt-bg-4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .85rem;
    font-weight: 700;
    color: var(--tbt-txt-white);
    transition: background .2s;
    overflow: hidden;       /* recorta la foto en círculo */
}
.gem-vendedor__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
.gem-vendedor.active .gem-vendedor__avatar {
    background: var(--tbt-jade);
    color: #000;
    box-shadow: 0 0 0 2px var(--tbt-jade);
}
</style>

<script <?= csp_nonce_attr() ?>>
window.LATINSHOP_CONFIG = { whatsappNumber: "<?= htmlspecialchars(WHATSAPP_NUMBER, ENT_QUOTES, 'UTF-8') ?>" };
</script>
<script src="<?= SITE_URL ?>/frontend/assets/js/gems-calc.js" defer></script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
<?php include INCLUDES_PATH . '/comments-widget.php'; ?>