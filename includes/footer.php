<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');
?>

</main><!-- /#main-content -->

<footer class="tbt-footer">
    <div class="tbt-wrap">

        <div class="tbt-footer__grid">

            <div class="tbt-footer__brand">
                <a href="<?= u() ?>" class="tbt-footer__logo">
                    <img src="<?= ASSETS_URL ?>/images/logo-footer.png"
                         alt="Latin Shop"
                         onerror="this.style.display='none'">
                    <span>Latin Shop</span>
                </a>
                <p class="tbt-footer__tagline">
                    Servicios premium para Lords Mobile.<br>
                    Bots, gemas y herramientas.
                </p>
            </div>

            <?php if (feature('bots')): ?>
            <div>
                <p class="tbt-footer__col-title">Servicios</p>
                <ul class="tbt-footer__links">
                    <li><a href="<?= u('/bots/bot-farming') ?>">Bot Farming</a></li>
                    <li><a href="<?= u('/bots/bot-whatsapp') ?>">Bot WhatsApp</a></li>
                </ul>
            </div>
            <?php endif; ?>

            <div>
                <p class="tbt-footer__col-title">Herramientas</p>
                <ul class="tbt-footer__links">
                    <li><a href="<?= u('/utilidades') ?>">Todas las Utilidades</a></li>
                    <li><a href="<?= u('/gems') ?>">Calculadora de Gemas</a></li>
                </ul>
            </div>

            <div>
                <p class="tbt-footer__col-title">Contacto</p>
                <ul class="tbt-footer__links">
                    <li><a href="<?= u('/contacto') ?>">Soporte</a></li>
                </ul>
                <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>"
                   class="tbt-footer__wa" target="_blank" rel="noopener noreferrer"
                   aria-label="Contáctanos por WhatsApp">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2Z"/>
                    </svg>
                    WhatsApp
                </a>
            </div>

        </div>

        <div class="tbt-footer__bottom">
            <p>© <?= date('Y') ?> Latin Shop. Todos los derechos reservados.</p>
            <div class="tbt-footer__legal">
                <a href="<?= u('/politica-de-privacidad') ?>">Política de Privacidad</a>
                <a href="<?= u('/terminos-y-condiciones') ?>">Términos y Condiciones</a>
                <a href="<?= u('/politica-de-cookies') ?>">Política de Cookies</a>
            </div>
        </div>

    </div>
</footer>

<?= AssetManifest::scriptTag('app') ?>
<?= AssetManifest::scriptTag('pwa') ?>

<?php
$_is_admin_user = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
if (!$_is_admin_user):
    echo AssetManifest::scriptTag('protection');
endif;
?>

<?php if (!empty($extra_js)): ?>
    <?php foreach ((array)$extra_js as $_ejs): ?>
        <?php
        $ejs_url = AssetManifest::js($_ejs);
        ?>
        <script src="<?= htmlspecialchars($ejs_url, ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (ENV === 'production'): ?>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js"
    data-cf-beacon='{"token": "4ab47d7e4c4749adb8a32f4f7efbd3ae"}'
    crossorigin="anonymous"></script>
<?php endif; ?>

<style>
.pwa-banner { position:fixed; bottom:1.5rem; left:50%; transform:translateX(-50%) translateY(140px); background:#161616; border:1px solid rgba(232,96,44,.45); border-radius:14px; padding:1rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 8px 32px rgba(0,0,0,.6); z-index:10001; max-width:420px; width:calc(100% - 2rem); transition:transform .4s cubic-bezier(.22,1,.36,1); pointer-events:auto; }
.pwa-banner--visible { transform:translateX(-50%) translateY(0); }
.pwa-banner__icon { font-size:1.8rem; flex-shrink:0; }
.pwa-banner__text { flex:1; min-width:0; }
.pwa-banner__text strong { display:block; color:#fff; font-size:.92rem; font-weight:700; margin-bottom:.2rem; }
.pwa-banner__text span { font-size:.78rem; color:#9a9a9a; }
.pwa-banner__actions { display:flex; flex-direction:column; gap:.4rem; flex-shrink:0; }
#pwa-btn-install { background:#e8602c; color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-size:.82rem; font-weight:700; cursor:pointer; white-space:nowrap; transition:opacity .2s; }
#pwa-btn-install:hover { opacity:.85; }
#pwa-btn-dismiss { background:none; border:none; color:#666; font-size:.76rem; cursor:pointer; text-align:center; transition:color .2s; }
#pwa-btn-dismiss:hover { color:#aaa; }
#pwa-fab { position:fixed; bottom:24px; right:20px; z-index:9999; display:none; align-items:center; gap:8px; background:#e8602c; color:#fff; border:none; border-radius:50px; padding:13px 20px; font-size:14px; font-weight:700; font-family:inherit; cursor:pointer; box-shadow:0 4px 20px rgba(232,96,44,.5); transition:transform .15s ease,box-shadow .15s ease; }
#pwa-fab:hover { transform:translateY(-2px); box-shadow:0 6px 24px rgba(232,96,44,.65); }
</style>

<div class="pwa-banner" id="pwa-install-banner" role="region" aria-label="Instalar aplicación">
    <div class="pwa-banner__icon">⚔</div>
    <div class="pwa-banner__text">
        <strong>Instalar Latin Shop</strong>
        <span>Accede más rápido desde tu pantalla de inicio</span>
    </div>
    <div class="pwa-banner__actions">
        <button id="pwa-btn-install" type="button">Instalar</button>
        <button id="pwa-btn-dismiss" type="button">Ahora no</button>
    </div>
</div>

<div id="pwa-manual-modal" role="dialog" aria-modal="true">
    <div class="pwa-modal__box">
        <p class="pwa-modal__title">📲 Instalar Latin Shop</p>
        <p class="pwa-modal__body" id="pwa-modal-body"></p>
        <button class="pwa-modal__close" id="pwa-modal-close" type="button">Entendido</button>
    </div>
</div>

</body>
</html>
