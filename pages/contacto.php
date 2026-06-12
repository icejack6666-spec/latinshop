<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$page_title       = 'Contacto | Latin Shop - Lords Mobile';
$page_description = 'Contacta con Latin Shop para soporte sobre bots, gemas y utilidades de Lords Mobile.';
$page_canonical   = SITE_URL . '/contacto';

include INCLUDES_PATH . '/header.php';
?>

<section class="tbt-section">
    <div class="tbt-wrap">

        <div class="contact-channels">

            <a href="https://api.whatsapp.com/send?phone=<?= WHATSAPP_NUMBER ?>"
               target="_blank"
               class="contact-channel contact-channel--wa">

                <div class="contact-channel__icon"></div>

                <div class="contact-channel__text">
                    <span class="contact-channel__label"></span>
                    <span class="contact-channel__value">Hablar por WhatsApp</span>
                </div>

                <span class="contact-channel__arrow">→</span>
            </a>

            <!-- Email 
            <a href="mailto:<?= CONTACT_EMAIL ?>" class="contact-channel">

                <div class="contact-channel__icon">✉️</div>

                <div class="contact-channel__text">
                    <span class="contact-channel__label">Soporte general</span>
                    <span class="contact-channel__value">Enviar correo</span>
                </div>

                <span class="contact-channel__arrow">→</span>
            </a> -->

            <!-- Grupo -->
            <a href="https://chat.whatsapp.com/BjMkEnWzcjj0m8CFJiXpn4"
               target="_blank"
               class="contact-channel contact-channel--group">

                <div class="contact-channel__icon"></div>

                <div class="contact-channel__text">
                    <span class="contact-channel__label"></span>
                    <span class="contact-channel__value">Unirse a la comunidad de whatsapp</span>
                </div>

                <span class="contact-channel__arrow">→</span>
            </a>

        </div>

    </div>
</section>

<style>
.contact-channels {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}


.contact-channel {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1.2rem;

    padding: 1.5rem;
    border-radius: 10px;

    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.08);

    text-decoration: none;
    color: inherit;

    overflow: hidden;

    transition: all .35s cubic-bezier(.2,.8,.2,1);
}

.contact-channel::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 0% 0%, rgba(255,255,255,0.08), transparent 60%);
    opacity: 0;
    transition: opacity .4s ease;
}

.contact-channel:hover {
    transform: translateY(-6px) scale(1.01);
    border-color: rgba(255,255,255,0.25);
    box-shadow: 0 15px 40px rgba(0,0,0,.4);
}

.contact-channel:hover::before {
    opacity: 1;
}

.contact-channel__icon {
    font-size: 1.6rem;
    transition: transform .3s ease;
}

.contact-channel:hover .contact-channel__icon {
    transform: scale(1.15) rotate(-3deg);
}

.contact-channel__text {
    flex: 1;
}

.contact-channel__label {
    font-size: .75rem;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: .08em;
}

.contact-channel__value {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    transition: color .3s ease;
}

.contact-channel__arrow {
    font-size: 1.3rem;
    transition: transform .3s ease, opacity .3s ease;
    opacity: .7;
}

.contact-channel:hover .contact-channel__arrow {
    transform: translateX(10px);
    opacity: 1;
}

.contact-channel--wa:hover {
    border-color: #25d366;
    box-shadow: 0 15px 40px rgba(37,211,102,.25);
}

.contact-channel--wa:hover .contact-channel__value {
    color: #25d366;
}

.contact-channel--group:hover {
    border-color: #4cc9f0;
    box-shadow: 0 15px 40px rgba(76,201,240,.25);
}

.contact-channel--group:hover .contact-channel__value {
    color: #4cc9f0;
}
</style>

<?php include INCLUDES_PATH . '/footer.php'; ?>