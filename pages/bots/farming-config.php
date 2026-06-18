<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');



$page_title       = 'Configuración Bot Farming | Latin Shop';
$page_description = 'Panel de configuración para instalar tu Bot Farming de Lords Mobile.';
$page_canonical   = SITE_URL . '/bots/farming-config';

$vendedores = [
    [
        'nombre' => 'MAFIAS',
        'wa'     => WHATSAPP_NUMBER,
        'img'    => ASSETS_URL . '/images/vendedores/mafias.jpg',
    ],
    [
        'nombre' => 'MininoMM',
        'wa'     => '51994361594',
        'img'    => ASSETS_URL . '/images/vendedores/mininomm.jpg',
    ],
];

include INCLUDES_PATH . '/header.php';
?>

<style>
.farm-wrap { max-width: 900px; margin: 0 auto; padding: 1.5rem 1rem 6rem; }

.farm-hero {
    text-align: center;
    margin-bottom: 2rem;
    background: linear-gradient(135deg, rgba(232,96,44,.08), rgba(255,122,69,.04));
    border: 1px solid rgba(232,96,44,.2);
    border-radius: 22px;
    padding: 2rem 1.5rem;
    position: relative;
    overflow: hidden;
}
.farm-hero::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, var(--tbt-jade), transparent);
}
.farm-hero h1 {
    font-family: var(--tbt-font-display);
    font-size: clamp(1.4rem, 4vw, 2rem);
    font-weight: 900;
    color: var(--tbt-txt-white);
    margin: 0 0 .4rem;
    letter-spacing: 2px;
}
.farm-hero p { font-size: .88rem; color: var(--tbt-txt-muted); margin: 0; }

/* ── Selector vendedor — mismas clases que bot-farming.php ── */
.bf-vendedor-wrap {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-4);
    border-top: 3px solid var(--tbt-jade);
    border-radius: var(--tbt-r-lg);
}
.bf-vendedor-label {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--tbt-jade);
    margin-bottom: .85rem;
}
.gem-vendedores {
    display: flex;
    gap: .6rem;
    justify-content: center;
    flex-wrap: wrap;
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
    width: 40px; height: 40px;
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
.gem-vendedor__avatar img { width: 100%; height: 100%; object-fit: cover; }
.gem-vendedor.active .gem-vendedor__avatar { box-shadow: 0 0 0 2px var(--tbt-jade); }

/* ── Formulario ── */
.form-section { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 1rem; }
.form-line {
    background: var(--tbt-bg-1);
    border: 1px solid var(--tbt-bg-4);
    border-top: 3px solid rgba(232,96,44,.4);
    border-radius: 14px;
    padding: 1.4rem 1.5rem;
    transition: border-top-color .2s;
}
.form-line:hover { border-top-color: var(--tbt-jade); }
.f-label {
    font-family: var(--tbt-font-body);
    font-size: .92rem;
    font-weight: 800;
    color: var(--tbt-jade);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: .9rem;
    display: block;
    border-bottom: 1px solid var(--tbt-bg-3);
    padding-bottom: 7px;
}
.f-sublabel { font-size: .77rem; color: var(--tbt-txt-muted); display: block; margin-top: 5px; }
.f-input, .f-select {
    background: var(--tbt-bg-2) !important;
    border: 1px solid var(--tbt-bg-4) !important;
    border-radius: 10px !important;
    padding: 11px 14px !important;
    color: var(--tbt-txt-white) !important;
    font-family: var(--tbt-font-body) !important;
    font-size: .9rem !important;
    width: 100% !important;
    max-width: 420px !important;
    outline: none !important;
    transition: border-color .2s !important;
    box-sizing: border-box !important;
}
.f-input:focus, .f-select:focus {
    border-color: var(--tbt-jade) !important;
    box-shadow: 0 0 0 2px rgba(232,96,44,.12) !important;
}
.f-select option { background: var(--tbt-bg-2); color: var(--tbt-txt-white); }
.f-opts { display: flex; flex-wrap: wrap; gap: 8px; margin-top: .5rem; }
.f-btn {
    position: relative;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-4);
    border-radius: 10px;
    padding: 9px 16px;
    font-family: var(--tbt-font-body);
    font-size: .82rem;
    font-weight: 700;
    transition: all .15s;
    min-width: 64px;
    user-select: none;
}
.f-btn:hover { border-color: var(--tbt-jade); transform: translateY(-1px); }
.f-btn input { position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; margin: 0; left: 0; top: 0; }
.f-btn label { font-family: var(--tbt-font-body) !important; font-size: .82rem !important; color: var(--tbt-txt-sub) !important; cursor: pointer; margin: 0 !important; padding: 0 !important; border: none !important; pointer-events: none; }
.f-btn:has(input:checked) { background: rgba(232,96,44,.15); border-color: var(--tbt-jade); box-shadow: 0 0 14px rgba(232,96,44,.2); }
.f-btn:has(input:checked) label { color: var(--tbt-jade) !important; }
.farm-btn {
    width: 100%; max-width: 480px;
    padding: 18px 24px;
    border: none;
    border-bottom: 4px solid #c04e1e;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--tbt-jade), #c04e1e);
    color: #fff;
    font-family: var(--tbt-font-display);
    font-size: 1.1rem;
    font-weight: 900;
    letter-spacing: 2px;
    cursor: pointer;
    transition: all .2s;
    position: relative;
    overflow: hidden;
}
.farm-btn::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 45%; background: linear-gradient(to bottom, rgba(255,255,255,.15), transparent); pointer-events: none; }
.farm-btn:hover { transform: translateY(-3px); filter: brightness(1.08); }
.farm-btn:active { transform: translateY(2px); border-bottom-width: 1px; }
.farm-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.farm-success {
    display: none;
    background: rgba(34,197,94,.1);
    border: 1px solid rgba(34,197,94,.3);
    border-radius: 14px;
    padding: 2rem;
    text-align: center;
    color: #4ade80;
    margin-top: 1.5rem;
}
.farm-success h3 { font-family: var(--tbt-font-display); font-size: 1.4rem; letter-spacing: 2px; margin-bottom: .5rem; }
@media (max-width: 500px) {
    .farm-wrap { padding: 1rem .75rem 5rem; }
    .form-line { padding: 1.1rem 1rem; }
    .f-input, .f-select { max-width: 100% !important; }
}
</style>

<div class="farm-wrap">

    <div class="farm-hero">
        <h1>⚙ Instalación Bot Farming</h1>
        <p>Elige tu vendedor, rellena el formulario y envía la configuración directo por WhatsApp.</p>
    </div>

    <!-- ── SELECTOR DE VENDEDOR ── -->
    <div class="bf-vendedor-wrap">
        <p class="bf-vendedor-label">👤 Paso 1 — Elige tu vendedor</p>
        <div class="gem-vendedores">
            <?php foreach ($vendedores as $i => $v): ?>
            <button
                type="button"
                class="gem-vendedor<?= $i === 0 ? ' active' : '' ?>"
                data-wa="<?= htmlspecialchars($v['wa']) ?>"
                data-nombre="<?= htmlspecialchars($v['nombre']) ?>"
            >
                <span class="gem-vendedor__avatar">
                    <?php if (!empty($v['img'])): ?>
                    <img src="<?= htmlspecialchars($v['img']) ?>" alt="<?= htmlspecialchars($v['nombre']) ?>" onerror="this.style.display='none'">
                    <?php else: ?>
                    <?= mb_substr($v['nombre'], 0, 1) ?>
                    <?php endif; ?>
                </span>
                <span><?= htmlspecialchars($v['nombre']) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── FORMULARIO ── -->
    <form id="farmForm" autocomplete="off">
        <ul class="form-section">

            <li class="form-line" id="fline_1">
                <label class="f-label">Nombre del Banco</label>
                <input type="text" id="fq_1" class="f-input" placeholder="Ej. LatinShopBot" autocomplete="off">
                <span class="f-sublabel">Nombre de tu cuenta banco en el juego</span>
            </li>

            <li class="form-line" id="fline_2">
                <label class="f-label">¿Escudo?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_2_0" name="fq_r_2" value="8 Horas"><label for="fq_2_0">8 Horas</label></span>
                    <span class="f-btn"><input type="radio" id="fq_2_1" name="fq_r_2" value="24 Horas"><label for="fq_2_1">24 Horas</label></span>
                    <span class="f-btn"><input type="radio" id="fq_2_2" name="fq_r_2" value="3 Días"><label for="fq_2_2">3 Días</label></span>
                    <span class="f-btn"><input type="radio" id="fq_2_3" name="fq_r_2" value="7 Días"><label for="fq_2_3">7 Días</label></span>
                    <span class="f-btn"><input type="radio" id="fq_2_4" name="fq_r_2" value="Sin escudo"><label for="fq_2_4">Sin escudo</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_3">
                <label class="f-label">¿Abre todos los Cofres de Mochila?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_3_0" name="fq_r_3" value="Si"><label for="fq_3_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_3_1" name="fq_r_3" value="No"><label for="fq_3_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_4">
                <label class="f-label">¿Tiempo de Entrada a la Cuenta?</label>
                <select id="fq_4" class="f-select">
                    <option value="">Seleccione…</option>
                    <option value="5 Minutos">5 Minutos</option>
                    <option value="10 Minutos">10 Minutos</option>
                    <option value="30 Minutos">30 Minutos</option>
                    <option value="1 Hora">1 Hora</option>
                </select>
            </li>

            <li class="form-line" id="fline_5">
                <label class="f-label">¿Envía a Algún Banco?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_5_0" name="fq_r_5" value="Si"><label for="fq_5_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_5_1" name="fq_r_5" value="No"><label for="fq_5_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_6" style="display:none">
                <label class="f-label">Nombre del Banco donde envía</label>
                <input type="text" id="fq_6" class="f-input" placeholder="Ej. LatinShopBanco" autocomplete="off">
            </li>

            <li class="form-line" id="fline_7">
                <label class="f-label">¿Cuántas Salidas a Recolectar?</label>
                <select id="fq_7" class="f-select">
                    <option value="">Seleccione…</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?= $i ?> Salida<?= $i > 1 ? 's' : '' ?>"><?= $i ?> Salida<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </li>

            <li class="form-line" id="fline_8">
                <label class="f-label">¿Qué Recursos Recolecta?</label>
                <div class="f-opts" role="group">
                    <?php foreach (['Trigo','Mineral','Piedra','Madera','Oro','Todas'] as $r): ?>
                    <span class="f-btn"><input type="checkbox" name="fq_c_8[]" value="<?= $r ?>"><label><?= $r ?></label></span>
                    <?php endforeach; ?>
                </div>
            </li>

            <li class="form-line" id="fline_9">
                <label class="f-label">Nivel a Recolectar</label>
                <div class="f-opts" role="group">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="f-btn"><input type="checkbox" name="fq_c_9[]" value="Nv <?= $i ?>"><label>Nv <?= $i ?></label></span>
                    <?php endfor; ?>
                </div>
            </li>

            <li class="form-line" id="fline_10">
                <label class="f-label">Marchas para Fortalezas</label>
                <select id="fq_10" class="f-select">
                    <option value="">Seleccione…</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?= $i ?> Marcha<?= $i > 1 ? 's' : '' ?>"><?= $i ?> Marcha<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </li>

            <li class="form-line" id="fline_11">
                <label class="f-label">¿Qué Comp envía a Fortaleza?</label>
                <select id="fq_11" class="f-select">
                    <option value="">Seleccione…</option>
                    <option value="1 Tropa">1 Tropa</option>
                    <option value="Como recomienda el Armador">Como recomienda el Armador</option>
                    <option value="Nivel más alto">Nivel más alto</option>
                </select>
            </li>

            <li class="form-line" id="fline_12">
                <label class="f-label">Nivel de Fortalezas a las que se une</label>
                <div class="f-opts" role="group">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                    <span class="f-btn"><input type="checkbox" name="fq_c_12[]" value="Fort <?= $i ?>"><label>Fort <?= $i ?></label></span>
                    <?php endfor; ?>
                </div>
            </li>

            <li class="form-line" id="fline_13">
                <label class="f-label">¿Intercambia en el Barco?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_13_0" name="fq_r_13" value="Si"><label for="fq_13_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_13_1" name="fq_r_13" value="No"><label for="fq_13_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_14">
                <label class="f-label">¿Mejora/Revive Líder?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_14_0" name="fq_r_14" value="Si"><label for="fq_14_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_14_1" name="fq_r_14" value="No"><label for="fq_14_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_15">
                <label class="f-label">¿Hace Etapa de Héroes?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_15_0" name="fq_r_15" value="Si"><label for="fq_15_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_15_1" name="fq_r_15" value="No"><label for="fq_15_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_16" style="display:none">
                <label class="f-label">¿Qué Etapas?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_16_0" name="fq_r_16" value="Normal"><label for="fq_16_0">Normal</label></span>
                    <span class="f-btn"><input type="radio" id="fq_16_1" name="fq_r_16" value="Elite"><label for="fq_16_1">Elite</label></span>
                    <span class="f-btn"><input type="radio" id="fq_16_2" name="fq_r_16" value="Ambas"><label for="fq_16_2">Ambas</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_17">
                <label class="f-label">¿Coliseo?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_17_0" name="fq_r_17" value="Si"><label for="fq_17_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_17_1" name="fq_r_17" value="No"><label for="fq_17_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_18">
                <label class="f-label">¿Hace Spam?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_18_0" name="fq_r_18" value="Si"><label for="fq_18_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_18_1" name="fq_r_18" value="No"><label for="fq_18_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_19">
                <label class="f-label">¿Construye?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_19_0" name="fq_r_19" value="Si"><label for="fq_19_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_19_1" name="fq_r_19" value="No"><label for="fq_19_1">No</label></span>
                    <span class="f-btn"><input type="radio" id="fq_19_2" name="fq_r_19" value="Hace Spam"><label for="fq_19_2">Hace Spam</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_20">
                <label class="f-label">¿Investiga?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_20_0" name="fq_r_20" value="Si"><label for="fq_20_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_20_1" name="fq_r_20" value="No"><label for="fq_20_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_21" style="display:none">
                <label class="f-label">¿Qué Rama?</label>
                <div class="f-opts" role="group">
                    <?php foreach (['Economia','Defensa','Ejercito','Caceria','Mejorar Defensa','Mejora Militar','Liderazgo de Ejercito','Comando Militar','Monstruitos','Sigilos','Maravillas','Combate Monstruitos','Equipo (T5)','Maravillas Avanzada'] as $r): ?>
                    <span class="f-btn"><input type="checkbox" name="fq_c_21[]" value="<?= $r ?>"><label><?= $r ?></label></span>
                    <?php endforeach; ?>
                </div>
            </li>

            <li class="form-line" id="fline_22">
                <label class="f-label">¿Entrena Tropas?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_22_0" name="fq_r_22" value="Si"><label for="fq_22_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_22_1" name="fq_r_22" value="No"><label for="fq_22_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_23" style="display:none">
                <label class="f-label">¿Qué Tropas?</label>
                <div class="f-opts" role="group">
                    <?php foreach (['T1','T2','T3','T4','T5'] as $t): ?>
                    <span class="f-btn"><input type="checkbox" name="fq_c_23[]" value="<?= $t ?>"><label><?= $t ?></label></span>
                    <?php endforeach; ?>
                </div>
            </li>

            <li class="form-line" id="fline_24">
                <label class="f-label">¿Construye Trampas?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_24_0" name="fq_r_24" value="Si"><label for="fq_24_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_24_1" name="fq_r_24" value="No"><label for="fq_24_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_25">
                <label class="f-label">¿Hace Equipo Luminario?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_25_0" name="fq_r_25" value="Si"><label for="fq_25_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_25_1" name="fq_r_25" value="No"><label for="fq_25_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_26">
                <label class="f-label">¿Cacería en Reino Ilusorio o Normal?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_26_0" name="fq_r_26" value="Reino Ilusorio"><label for="fq_26_0">Reino Ilusorio</label></span>
                    <span class="f-btn"><input type="radio" id="fq_26_1" name="fq_r_26" value="Normal"><label for="fq_26_1">Normal</label></span>
                    <span class="f-btn"><input type="radio" id="fq_26_2" name="fq_r_26" value="Ambos"><label for="fq_26_2">Ambos</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_27">
                <label class="f-label">¿Qué nivel de Mobs Cazará?</label>
                <div class="f-opts" role="group">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="f-btn"><input type="checkbox" name="fq_c_27[]" value="Nv <?= $i ?>"><label>Nv <?= $i ?></label></span>
                    <?php endfor; ?>
                </div>
            </li>

            <li class="form-line" id="fline_28">
                <label class="f-label">¿Hace Fiesta de Gremio?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_28_0" name="fq_r_28" value="Si"><label for="fq_28_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_28_1" name="fq_r_28" value="No"><label for="fq_28_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_29" style="display:none">
                <label class="f-label">Puntos x Misión Mínimo</label>
                <input type="number" id="fq_29" class="f-input" placeholder="Ej. 120" autocomplete="off">
            </li>

            <li class="form-line" id="fline_30">
                <label class="f-label">¿Borra Misiones?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_30_0" name="fq_r_30" value="Si"><label for="fq_30_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_30_1" name="fq_r_30" value="No"><label for="fq_30_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_31" style="display:none">
                <label class="f-label">Puntos a Borrar (máximo)</label>
                <input type="number" id="fq_31" class="f-input" placeholder="Ej. 119" autocomplete="off">
            </li>

            <li class="form-line" id="fline_32">
                <label class="f-label">¿Hace Pactos?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_32_0" name="fq_r_32" value="Si"><label for="fq_32_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_32_1" name="fq_r_32" value="No"><label for="fq_32_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_33" style="display:none">
                <label class="f-label">¿Qué Pactos?</label>
                <input type="text" id="fq_33" class="f-input" placeholder="Pacto 1 - Pacto 2 - Pacto 3" autocomplete="off">
                <span class="f-sublabel">Separa los pactos con " - "</span>
            </li>

            <li class="form-line" id="fline_34">
                <label class="f-label">¿Qué Armadura deja en Descanso?</label>
                <input type="text" id="fq_34" class="f-input" placeholder="Ej. Cacería" autocomplete="off">
            </li>

            <li class="form-line" id="fline_35">
                <label class="f-label">¿Qué Monstruitos Entrena?</label>
                <div class="f-opts" role="group">
                    <?php foreach (['Terrizo','Ingeniero','Conchaspina','Yeti','Totem','Pyris','Cabeza Hueca','Noceros','Truco Estrella','Caballo de Troya','Drider Infernal','Bestia de la nieve','Alaescarcha','Buen Apetito','Baum','Jaziek','Gnomo','Tempestizo','Strix','Rocoso','Arpia','Gemming Duendecillo','Magus','La Muerte','Megalarva','Acaparador','Alanegra','Gargantua','Magmalius','Aquarion','Maestro Bestia','El Mal Gorgojo','Hechicero','Kan-grejo','Grifo','Goblin','Chaman Topo','Titan de Marea','Saberfang','Abeja Reina','Huey Hops'] as $m): ?>
                    <span class="f-btn"><input type="checkbox" name="fq_c_35[]" value="<?= htmlspecialchars($m) ?>"><label><?= htmlspecialchars($m) ?></label></span>
                    <?php endforeach; ?>
                </div>
            </li>

            <li class="form-line" id="fline_36">
                <label class="f-label">¿Activa Habilidades de Monstruito?</label>
                <div class="f-opts" role="group">
                    <?php foreach (['Terrizo','Ingeniero','Conchaspina','Yeti','Totem','Pyris','Cabeza Hueca','Noceros','Truco Estrella','Caballo de Troya','Drider Infernal','Bestia de la nieve','Alaescarcha','Buen Apetito','Baum','Jaziek','Gnomo','Tempestizo','Strix','Rocoso','Arpia','Gemming Duendecillo','Magus','La Muerte','Megalarva','Acaparador','Alanegra','Gargantua','Magmalius','Aquarion','Maestro Bestia','El Mal Gorgojo','Hechicero','Kan-grejo','Grifo','Goblin','Chaman Topo','Titan de Marea','Saberfang','Abeja Reina','Huey Hops'] as $m): ?>
                    <span class="f-btn"><input type="checkbox" name="fq_c_36[]" value="<?= htmlspecialchars($m) ?>"><label><?= htmlspecialchars($m) ?></label></span>
                    <?php endforeach; ?>
                </div>
            </li>

            <li class="form-line" id="fline_37">
                <label class="f-label">¿Bot Privado o de Gremio?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_37_0" name="fq_r_37" value="Privado"><label for="fq_37_0">Privado</label></span>
                    <span class="f-btn"><input type="radio" id="fq_37_1" name="fq_r_37" value="Gremio"><label for="fq_37_1">Gremio</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_38">
                <label class="f-label">¿Prefijo para Comandos?</label>
                <input type="text" id="fq_38" class="f-input" placeholder="Ej. !#$%&/+*-" autocomplete="off">
                <span class="f-sublabel">Carácter que activa los comandos del bot</span>
            </li>

            <li class="form-line" id="fline_39">
                <label class="f-label">¿Lista Negra y Blanca?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_39_0" name="fq_r_39" value="Si"><label for="fq_39_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_39_1" name="fq_r_39" value="No"><label for="fq_39_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_40">
                <label class="f-label">¿Control de Cacería?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_40_0" name="fq_r_40" value="Diario"><label for="fq_40_0">Diario</label></span>
                    <span class="f-btn"><input type="radio" id="fq_40_1" name="fq_r_40" value="Semanal"><label for="fq_40_1">Semanal</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_41">
                <label class="f-label">¿Puntos de Meta de Caza?</label>
                <input type="text" id="fq_41" class="f-input" placeholder="Ej. 45 Pts Semanales" autocomplete="off">
            </li>

            <li class="form-line" id="fline_42">
                <label class="f-label">¿Qué Archivos Exporta (Estadísticas)?</label>
                <div class="f-opts" role="group">
                    <span class="f-btn"><input type="checkbox" name="fq_c_42[]" value="Kills"><label>Kills</label></span>
                    <span class="f-btn"><input type="checkbox" name="fq_c_42[]" value="FDG"><label>FDG</label></span>
                    <span class="f-btn"><input type="checkbox" name="fq_c_42[]" value="Banco"><label>Banco</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_43">
                <label class="f-label">¿Autorizados?</label>
                <div class="f-opts" role="radiogroup">
                    <span class="f-btn"><input type="radio" id="fq_43_0" name="fq_r_43" value="Si"><label for="fq_43_0">Sí</label></span>
                    <span class="f-btn"><input type="radio" id="fq_43_1" name="fq_r_43" value="No"><label for="fq_43_1">No</label></span>
                </div>
            </li>

            <li class="form-line" id="fline_44" style="display:none"><label class="f-label">Autorizado 1</label><input type="text" id="fq_44" class="f-input" placeholder="Nombre en juego" autocomplete="off"></li>
            <li class="form-line" id="fline_45" style="display:none"><label class="f-label">Autorizado 2</label><input type="text" id="fq_45" class="f-input" placeholder="Nombre en juego" autocomplete="off"></li>
            <li class="form-line" id="fline_46" style="display:none"><label class="f-label">Autorizado 3</label><input type="text" id="fq_46" class="f-input" placeholder="Nombre en juego" autocomplete="off"></li>
            <li class="form-line" id="fline_47" style="display:none"><label class="f-label">Autorizado 4</label><input type="text" id="fq_47" class="f-input" placeholder="Nombre en juego" autocomplete="off"></li>

            <li class="form-line" id="fline_notas">
                <label class="f-label">📝 Notas adicionales (opcional)</label>
                <textarea id="fq_notas" class="f-input" rows="3" placeholder="Cualquier configuración especial o información adicional..." style="max-width:100%!important;resize:vertical;"></textarea>
            </li>

            <li class="form-line" style="background:transparent;border:none;padding:.5rem 0">
                <div style="display:flex;justify-content:center">
                    <button type="button" id="farmSubmit" class="farm-btn" onclick="farmEnviar()">
                        📲 Enviar por WhatsApp
                    </button>
                </div>
            </li>

        </ul>
    </form>

    <div class="farm-success" id="farm-success">
        <h3>✅ ¡LISTO!</h3>
        <p>Se abrió WhatsApp con tu configuración lista.<br>Solo presiona <strong>Enviar</strong> en WhatsApp.</p>
    </div>

</div>

<!-- JS del selector de vendedor — mismo archivo que ya usa bot-farming.php -->
<script src="<?= ASSETS_URL ?>/js/bot-farming-vendedor.js" defer></script>

<script <?= csp_nonce_attr() ?>>
var QUESTIONS = [
    {id:1,  label:'Banco',             type:'text',     cond_parent_id:null, cond_value:null},
    {id:2,  label:'Escudo',            type:'radio',    cond_parent_id:null, cond_value:null},
    {id:3,  label:'Cofres Mochila',    type:'radio',    cond_parent_id:null, cond_value:null},
    {id:4,  label:'Tiempo Entrada',    type:'select',   cond_parent_id:null, cond_value:null},
    {id:5,  label:'Envía a Banco',     type:'radio',    cond_parent_id:null, cond_value:null},
    {id:6,  label:'Banco destino',     type:'text',     cond_parent_id:5,    cond_value:'Si'},
    {id:7,  label:'Salidas',           type:'select',   cond_parent_id:null, cond_value:null},
    {id:8,  label:'Recursos',          type:'checkbox', cond_parent_id:null, cond_value:null},
    {id:9,  label:'Nv Recolección',    type:'checkbox', cond_parent_id:null, cond_value:null},
    {id:10, label:'Marchas Fort',      type:'select',   cond_parent_id:null, cond_value:null},
    {id:11, label:'Comp Fort',         type:'select',   cond_parent_id:null, cond_value:null},
    {id:12, label:'Nv Fortalezas',     type:'checkbox', cond_parent_id:null, cond_value:null},
    {id:13, label:'Barco',             type:'radio',    cond_parent_id:null, cond_value:null},
    {id:14, label:'Líder',             type:'radio',    cond_parent_id:null, cond_value:null},
    {id:15, label:'Etapa Héroes',      type:'radio',    cond_parent_id:null, cond_value:null},
    {id:16, label:'Qué Etapas',        type:'radio',    cond_parent_id:15,   cond_value:'Si'},
    {id:17, label:'Coliseo',           type:'radio',    cond_parent_id:null, cond_value:null},
    {id:18, label:'Spam',              type:'radio',    cond_parent_id:null, cond_value:null},
    {id:19, label:'Construye',         type:'radio',    cond_parent_id:null, cond_value:null},
    {id:20, label:'Investiga',         type:'radio',    cond_parent_id:null, cond_value:null},
    {id:21, label:'Rama',              type:'checkbox', cond_parent_id:20,   cond_value:'Si'},
    {id:22, label:'Entrena Tropas',    type:'radio',    cond_parent_id:null, cond_value:null},
    {id:23, label:'Qué Tropas',        type:'checkbox', cond_parent_id:22,   cond_value:'Si'},
    {id:24, label:'Trampas',           type:'radio',    cond_parent_id:null, cond_value:null},
    {id:25, label:'Luminario',         type:'radio',    cond_parent_id:null, cond_value:null},
    {id:26, label:'Cacería',           type:'radio',    cond_parent_id:null, cond_value:null},
    {id:27, label:'Nv Mobs',           type:'checkbox', cond_parent_id:null, cond_value:null},
    {id:28, label:'Fiesta Gremio',     type:'radio',    cond_parent_id:null, cond_value:null},
    {id:29, label:'Pts Misión Min',    type:'text',     cond_parent_id:28,   cond_value:'Si'},
    {id:30, label:'Borra Misiones',    type:'radio',    cond_parent_id:null, cond_value:null},
    {id:31, label:'Pts Borrar',        type:'text',     cond_parent_id:30,   cond_value:'Si'},
    {id:32, label:'Pactos',            type:'radio',    cond_parent_id:null, cond_value:null},
    {id:33, label:'Qué Pactos',        type:'text',     cond_parent_id:32,   cond_value:'Si'},
    {id:34, label:'Armadura Descanso', type:'text',     cond_parent_id:null, cond_value:null},
    {id:35, label:'Monstruitos',       type:'checkbox', cond_parent_id:null, cond_value:null},
    {id:36, label:'Hab Monstruitos',   type:'checkbox', cond_parent_id:null, cond_value:null},
    {id:37, label:'Tipo Bot',          type:'radio',    cond_parent_id:null, cond_value:null},
    {id:38, label:'Prefijo',           type:'text',     cond_parent_id:null, cond_value:null},
    {id:39, label:'Listas',            type:'radio',    cond_parent_id:null, cond_value:null},
    {id:40, label:'Control Caza',      type:'radio',    cond_parent_id:null, cond_value:null},
    {id:41, label:'Meta Caza',         type:'text',     cond_parent_id:null, cond_value:null},
    {id:42, label:'Exporta',           type:'checkbox', cond_parent_id:null, cond_value:null},
    {id:43, label:'Autorizados',       type:'radio',    cond_parent_id:null, cond_value:null},
    {id:44, label:'Auth 1',            type:'text',     cond_parent_id:43,   cond_value:'Si'},
    {id:45, label:'Auth 2',            type:'text',     cond_parent_id:43,   cond_value:'Si'},
    {id:46, label:'Auth 3',            type:'text',     cond_parent_id:43,   cond_value:'Si'},
    {id:47, label:'Auth 4',            type:'text',     cond_parent_id:43,   cond_value:'Si'},
];

function getVal(qid, type) {
    if (type === 'radio') {
        var el = document.querySelector('input[name="fq_r_' + qid + '"]:checked');
        return el ? el.value : '';
    }
    if (type === 'checkbox') {
        return Array.from(document.querySelectorAll('input[name="fq_c_' + qid + '[]"]:checked')).map(function(e){ return e.value; });
    }
    var el = document.getElementById('fq_' + qid);
    return el ? el.value.trim() : '';
}

function evalConds() {
    QUESTIONS.forEach(function(q) {
        var line = document.getElementById('fline_' + q.id);
        if (!line) return;
        if (!q.cond_parent_id) { line.style.display = 'block'; return; }
        var parentQ    = QUESTIONS.filter(function(x){ return x.id === q.cond_parent_id; })[0];
        var parentLine = document.getElementById('fline_' + q.cond_parent_id);
        if (!parentQ || (parentLine && parentLine.style.display === 'none')) { line.style.display = 'none'; return; }
        var val   = getVal(q.cond_parent_id, parentQ.type);
        var match = Array.isArray(val) ? val.indexOf(q.cond_value) !== -1 : val === q.cond_value;
        line.style.display = match ? 'block' : 'none';
    });
}
document.getElementById('farmForm').addEventListener('change', evalConds);
evalConds();

function collectAnswers() {
    var ans = [];
    QUESTIONS.forEach(function(q) {
        var line = document.getElementById('fline_' + q.id);
        if (!line || line.style.display === 'none') return;
        var val    = getVal(q.id, q.type);
        var strVal = Array.isArray(val) ? val.join(', ') : val;
        if (strVal) ans.push({ label: q.label, answer: strVal });
    });
    var notasEl = document.getElementById('fq_notas');
    var notas   = notasEl ? notasEl.value.trim() : '';
    if (notas) ans.push({ label: 'Notas', answer: notas });
    return ans;
}

function buildWAMessage(answers, vendedorNombre) {
    var msg = '\u2699\ufe0f *Config Bot Farming \u2014 Latin Shop*\n';
    msg += '\ud83d\udc64 Vendedor: *' + vendedorNombre + '*\n\n';
    answers.forEach(function(a) {
        msg += '*' + a.label + ':* ' + a.answer + '\n';
    });
    return msg;
}

// ── Lee el vendedor activo con las mismas clases que usa bot-farming-vendedor.js ──
function getActiveVendedor() {
    var active = document.querySelector('.gem-vendedor.active');
    if (!active) return null;
    return { wa: active.dataset.wa, nombre: active.dataset.nombre };
}

function farmEnviar() {
    var vendedor = getActiveVendedor();
    if (!vendedor || !vendedor.wa) {
        alert('Por favor elige un vendedor primero.');
        return;
    }

    var btn = document.getElementById('farmSubmit');
    btn.disabled = true;
    btn.textContent = '\u23f3 Preparando\u2026';

    var answers = collectAnswers();
    var waMsg   = buildWAMessage(answers, vendedor.nombre);


    document.getElementById('farm-success').style.display = 'block';
    document.getElementById('farm-success').scrollIntoView({ behavior: 'smooth' });

    setTimeout(function() {
        window.open('https://wa.me/' + vendedor.wa + '?text=' + encodeURIComponent(waMsg), '_blank');
    }, 600);

    btn.disabled = false;
    btn.textContent = '\u2705 \u00a1Enviado!';
    setTimeout(function() { btn.textContent = '\ud83d\udcf2 Enviar por WhatsApp'; }, 5000);
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>