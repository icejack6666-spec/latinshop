<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$page_title       = 'Política de Cookies | Latin Shop';
$page_description = 'Política de cookies de Latin Shop. Qué cookies usamos y cómo puedes controlarlas.';
$page_canonical   = SITE_URL . '/politica-de-cookies';
include INCLUDES_PATH . '/header.php';
?>

<section class="tbt-hero" style="background:var(--tbt-bg-alt);">
    <div class="tbt-wrap">
        <div class="tbt-reveal tbt-visible" style="max-width:700px;">
            <div class="tbt-badge tbt-badge--jade tbt-mb-3"><span class="tbt-badge__dot"></span>Legal</div>
            <h1 class="headline-xl mb-md">Política de <span class="tbt-jade">Cookies</span></h1>
            <p class="tbt-body-md">Última actualización: febrero 2026</p>
        </div>
    </div>
</section>

<section class="tbt-section">
    <div class="tbt-wrap">
        <div class="legal-wrap reveal is-visible">

            <div class="legal-block">
                <h2>1. ¿Qué son las cookies?</h2>
                <p>Las cookies son pequeños archivos de texto que un sitio web almacena en tu navegador cuando lo visitas. Sirven para recordar preferencias, mantener sesiones activas y recopilar información de uso.</p>
            </div>

            <div class="legal-block">
                <h2>2. Cookies que usamos actualmente</h2>
                <p>Latin Shop utiliza únicamente <strong>cookies técnicas estrictamente necesarias</strong> para el funcionamiento del sitio:</p>

                <div class="legal-table-wrap">
                    <table class="legal-table">
                        <thead>
                            <tr>
                                <th>Cookie</th>
                                <th>Tipo</th>
                                <th>Finalidad</th>
                                <th>Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>PHPSESSID</code></td>
                                <td>Técnica</td>
                                <td>Mantiene la sesión del usuario activa y gestiona tokens de seguridad CSRF</td>
                                <td>Sesión</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p>No usamos cookies de analítica, publicidad ni rastreo de terceros.</p>
            </div>

            <div class="legal-block">
                <h2>3. Cookies que podremos añadir en el futuro</h2>
                <p>Al implementar el sistema de cuentas de usuario, podremos añadir:</p>
                <ul>
                    <li><strong>Cookie de sesión autenticada</strong> — para mantener tu sesión iniciada de forma segura</li>
                    <li><strong>Cookie de preferencias</strong> — para recordar ajustes de la interfaz</li>
                </ul>
                <p>Antes de añadir cualquier cookie no esencial, actualizaremos esta política y, si aplica, solicitaremos tu consentimiento.</p>
            </div>

            <div class="legal-block">
                <h2>4. Cookies de terceros</h2>
                <p>Actualmente <strong>no usamos ningún servicio de terceros</strong> que instale cookies (Google Analytics, Meta Pixel, etc.). Si en el futuro los incorporamos, lo notificaremos en esta política.</p>
            </div>

            <div class="legal-block">
                <h2>5. Cómo controlar las cookies</h2>
                <p>Puedes configurar tu navegador para bloquear o eliminar cookies en cualquier momento. Ten en cuenta que bloquear la cookie <code>PHPSESSID</code> puede afectar funcionalidades de seguridad del sitio. Aquí te dejamos los enlaces de configuración de los navegadores más comunes:</p>
                <ul>
                    <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener noreferrer">Google Chrome</a></li>
                    <li><a href="https://support.mozilla.org/es/kb/cookies-informacion-que-los-sitios-web-guardan-en-" target="_blank" rel="noopener noreferrer">Mozilla Firefox</a></li>
                    <li><a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank" rel="noopener noreferrer">Safari</a></li>
                    <li><a href="https://support.microsoft.com/es-es/microsoft-edge/eliminar-las-cookies-en-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener noreferrer">Microsoft Edge</a></li>
                </ul>
            </div>

            <div class="legal-block">
                <h2>6. Contacto</h2>
                <p>Si tienes preguntas sobre el uso de cookies en este sitio, escríbenos a <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>.</p>
            </div>

        </div>
    </div>
</section>

<?php include INCLUDES_PATH . '/footer.php'; ?>
