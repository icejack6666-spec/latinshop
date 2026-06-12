<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$page_title       = 'Página no encontrada | Latin Shop';
$page_description = 'La página que buscas no existe. Vuelve al inicio de Latin Shop.';

include INCLUDES_PATH . '/header.php';
?>

<section class="tbt-hero" style="min-height: 60vh; display:flex; align-items:center;">
    <div class="tbt-wrap">
        <div class="tbt-reveal tbt-visible" style="max-width: 600px;">
            
            <div class="tbt-badge tbt-badge--jade tbt-mb-3">
                <span class="tbt-badge__dot"></span>
                Error 404
            </div>
            
            <h1 class="tbt-h-xl tbt-mb-3">
                Página<br>
                <span class="tbt-jade">no encontrada</span>
            </h1>
            
            <p class="tbt-body-lg tbt-mb-5">
                La página que buscas no existe o ha sido movida.
                Aquí tienes algunos enlaces útiles:
            </p>
            
            <div style="display:flex; gap: 1rem; flex-wrap: wrap;">
                <a href="<?= u() ?>" class="tbt-btn tbt-btn--jade">Ir al inicio</a>
                <a href="<?= u('/utilidades') ?>" class="tbt-btn tbt-btn--outline">Utilidades</a>
                <a href="<?= u('/contacto') ?>" class="tbt-btn tbt-btn--outline">Contacto</a>
            </div>
            
        </div>
    </div>
</section>

<?php include INCLUDES_PATH . '/footer.php'; ?>
