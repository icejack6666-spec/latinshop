<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');
$page_title       = 'Política de Privacidad | Latin Shop';
$page_description = 'Política de privacidad de Latin Shop. Cómo recopilamos, usamos y protegemos tus datos personales conforme a la LFPDPPP de México.';
$page_canonical   = SITE_URL . '/politica-de-privacidad';
include INCLUDES_PATH . '/header.php';
?>

<section class="tbt-hero" style="background:var(--tbt-bg-alt);">
    <div class="tbt-wrap">
        <div class="tbt-reveal tbt-visible" style="max-width:700px;">
            <div class="tbt-badge tbt-badge--jade tbt-mb-3"><span class="tbt-badge__dot"></span>Legal</div>
            <h1 class="headline-xl mb-md">Política de <span class="tbt-jade">Privacidad</span></h1>
            <p class="tbt-body-md">Última actualización: febrero 2026 · Ley aplicable: LFPDPPP (México)</p>
        </div>
    </div>
</section>

<section class="tbt-section">
    <div class="tbt-wrap">
        <div class="legal-wrap reveal is-visible">

            <div class="legal-block">
                <h2>1. Responsable del tratamiento</h2>
                <p>El responsable del tratamiento de tus datos personales es <strong>Latin Shop</strong>, con domicilio en México. Puedes contactarnos en <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a> o por WhatsApp al <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>">+52 786 228 6246</a>.</p>
            </div>

            <div class="legal-block">
                <h2>2. Datos que recopilamos actualmente</h2>
                <p>En este momento, <strong>Latin Shop no recopila datos personales de forma automatizada</strong> a través de este sitio web. El único medio de contacto es WhatsApp y correo electrónico, donde el usuario proporciona voluntariamente su información para recibir nuestros servicios.</p>
                <p>Los datos que puedes compartir voluntariamente al contactarnos son:</p>
                <ul>
                    <li>Nombre o apodo</li>
                    <li>Número de WhatsApp</li>
                    <li>Información relacionada con tu cuenta de Lords Mobile (reino, poder, nombre de gremio)</li>
                </ul>
            </div>

            <div class="legal-block">
                <h2>3. Datos que recopilaremos próximamente</h2>
                <p>En el futuro, al implementar el sistema de cuentas de usuario, podremos recopilar:</p>
                <ul>
                    <li><strong>Correo electrónico</strong> — para identificación y comunicación</li>
                    <li><strong>Contraseña</strong> — almacenada de forma cifrada, nunca en texto plano</li>
                    <li><strong>Número de teléfono</strong> — exclusivamente para verificación en dos factores (2FA)</li>
                </ul>
                <p>Actualizaremos esta política antes de implementar dichos cambios y te notificaremos con antelación.</p>
            </div>

            <div class="legal-block">
                <h2>4. Finalidad del tratamiento</h2>
                <p>Los datos que compartes con nosotros se usan exclusivamente para:</p>
                <ul>
                    <li>Prestarte los servicios contratados</li>
                    <li>Brindarte soporte técnico</li>
                    <li>Verificar tu identidad al momento de acceder a servicios de pago</li>
                    <li>Enviarte información sobre tu servicio activo (no publicidad no solicitada)</li>
                </ul>
            </div>

            <div class="legal-block">
                <h2>5. Compartición de datos con terceros</h2>
                <p>Latin Shop <strong>no vende, alquila ni comparte</strong> tus datos personales con terceros, salvo obligación legal expresa.</p>
            </div>

            <div class="legal-block">
                <h2>6. Seguridad de los datos</h2>
                <p>Adoptamos medidas técnicas y organizativas para proteger tus datos. En particular:</p>
                <ul>
                    <li>Las contraseñas futuras se almacenarán con hash seguro (bcrypt o equivalente)</li>
                    <li>Los números de teléfono para 2FA se usarán exclusivamente para ese fin</li>
                    <li>Las comunicaciones con el sitio se realizan sobre HTTPS</li>
                </ul>
            </div>

            <div class="legal-block">
                <h2>7. Tus derechos (ARCO)</h2>
                <p>Conforme a la <strong>Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP)</strong> de México, tienes derecho a:</p>
                <ul>
                    <li><strong>Acceso</strong> — conocer qué datos tenemos sobre ti</li>
                    <li><strong>Rectificación</strong> — corregir datos incorrectos</li>
                    <li><strong>Cancelación</strong> — solicitar la eliminación de tus datos</li>
                    <li><strong>Oposición</strong> — oponerte al uso de tus datos para fines específicos</li>
                </ul>
                <p>Para ejercer estos derechos escríbenos a <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a> indicando tu solicitud. Responderemos en un plazo máximo de 20 días hábiles.</p>
            </div>

            <div class="legal-block">
                <h2>8. Retención de datos</h2>
                <p>Conservamos tus datos únicamente mientras mantengas una relación activa con Latin Shop o durante el tiempo necesario para cumplir obligaciones legales. Una vez finalizada la relación, los datos se eliminarán en un plazo máximo de 90 días.</p>
            </div>

            <div class="legal-block">
                <h2>9. Modificaciones a esta política</h2>
                <p>Podemos actualizar esta política en cualquier momento. Te notificaremos los cambios relevantes, especialmente antes de implementar el sistema de cuentas de usuario. La fecha de la última actualización siempre aparecerá al inicio de este documento.</p>
            </div>

        </div>
    </div>
</section>

<?php include INCLUDES_PATH . '/footer.php'; ?>
