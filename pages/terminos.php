<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');
$page_title       = 'Términos y Condiciones | Latin Shop';
$page_description = 'Términos y condiciones de uso de los servicios de Latin Shop para Lords Mobile.';
$page_canonical   = SITE_URL . '/terminos-y-condiciones';
include INCLUDES_PATH . '/header.php';
?>

<section class="tbt-hero" style="background:var(--tbt-bg-alt);">
    <div class="tbt-wrap">
        <div class="tbt-reveal tbt-visible" style="max-width:700px;">
            <div class="tbt-badge tbt-badge--jade tbt-mb-3"><span class="tbt-badge__dot"></span>Legal</div>
            <h1 class="headline-xl mb-md">Términos y <span class="tbt-jade">Condiciones</span></h1>
            <p class="tbt-body-md">Última actualización: febrero 2026</p>
        </div>
    </div>
</section>

<section class="tbt-section">
    <div class="tbt-wrap">
        <div class="legal-wrap reveal is-visible">

            <div class="legal-block">
                <h2>1. Aceptación de los términos</h2>
                <p>Al acceder y utilizar los servicios de <strong>Latin Shop</strong> (en adelante "el Servicio"), aceptas quedar vinculado por estos Términos y Condiciones. Si no estás de acuerdo con alguno de ellos, te pedimos que no uses nuestros servicios.</p>
            </div>

            <div class="legal-block">
                <h2>2. Descripción del servicio</h2>
                <p>Latin Shop ofrece herramientas y servicios relacionados con el videojuego Lords Mobile, incluyendo:</p>
                <ul>
                    <li>Bots de automatización de cuentas (Bot Farming)</li>
                    <li>Bot de asistencia para grupos de WhatsApp (Bot WhatsApp)</li>
                    <li>Venta de gemas y recursos del juego</li>
                    <li>Calculadoras y utilidades informativas</li>
                </ul>
            </div>

            <div class="legal-block">
                <h2>3. Uso del servicio</h2>
                <p>Al contratar cualquiera de nuestros servicios, el usuario acepta:</p>
                <ul>
                    <li>Proporcionar información veraz al momento de contratar.</li>
                    <li>No usar los servicios para actividades ilegales o que perjudiquen a terceros.</li>
                    <li>Asumir plena responsabilidad sobre su cuenta de Lords Mobile. Latin Shop no se hace responsable de sanciones o pérdidas derivadas del uso de los servicios.</li>
                    <li>No revender ni compartir accesos a los servicios contratados sin autorización expresa.</li>
                </ul>
            </div>

            <div class="legal-block">
                <h2>4. Pagos y reembolsos</h2>
                <p>Los precios están expresados en dólares estadounidenses (USD). El pago se realiza de forma anticipada antes de la activación del servicio. No se realizan reembolsos una vez que el servicio ha sido activado, salvo en caso de fallo técnico imputable exclusivamente a Latin Shop.</p>
            </div>

            <div class="legal-block">
                <h2>5. Disponibilidad del servicio</h2>
                <p>Latin Shop se compromete a mantener los servicios disponibles de forma continua, pero no garantiza una disponibilidad del 100%. Pueden existir interrupciones por mantenimiento, actualizaciones del juego o causas de fuerza mayor. En caso de interrupciones prolongadas, se compensará al usuario con tiempo adicional equivalente.</p>
            </div>

            <div class="legal-block">
                <h2>6. Cuentas de usuario (próximamente)</h2>
                <p>En el futuro, Latin Shop podrá ofrecer cuentas de usuario que requerirán correo electrónico, contraseña y verificación por dos factores (2FA). El usuario será responsable de mantener la confidencialidad de sus credenciales. Latin Shop nunca solicitará tu contraseña por WhatsApp o cualquier otro canal externo a la plataforma.</p>
            </div>

            <div class="legal-block">
                <h2>7. Propiedad intelectual</h2>
                <p>Todo el contenido de este sitio web — textos, diseños, código, imágenes y herramientas — es propiedad de Latin Shop o tiene licencia de uso. Lords Mobile y sus elementos son propiedad de IGG. Latin Shop no está afiliado oficialmente con IGG.</p>
            </div>

            <div class="legal-block">
                <h2>8. Limitación de responsabilidad</h2>
                <p>Latin Shop no será responsable de daños directos, indirectos, incidentales o consecuentes derivados del uso o imposibilidad de uso de los servicios, incluyendo pero no limitado a pérdida de datos, pérdida de progreso en el juego o sanciones impuestas por IGG.</p>
            </div>

            <div class="legal-block">
                <h2>9. Modificaciones</h2>
                <p>Nos reservamos el derecho de modificar estos términos en cualquier momento. Los cambios serán notificados mediante la actualización de la fecha al inicio de este documento. El uso continuado del servicio implica la aceptación de los nuevos términos.</p>
            </div>

            <div class="legal-block">
                <h2>10. Ley aplicable</h2>
                <p>Estos términos se rigen por las leyes de los <strong>Estados Unidos Mexicanos</strong>. Cualquier controversia se someterá a los tribunales competentes del domicilio del prestador del servicio.</p>
            </div>

            <div class="legal-block">
                <h2>11. Contacto</h2>
                <p>Para cualquier duda sobre estos términos puedes contactarnos a través de:</p>
                <ul>
                    <li>WhatsApp: <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>">+52 786 228 6246</a></li>
                    <li>Email: <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a></li>
                </ul>
            </div>

        </div>
    </div>
</section>

<?php include INCLUDES_PATH . '/footer.php'; ?>
