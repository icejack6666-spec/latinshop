<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    $_SESSION['redirect_after_login'] = u('/perfil/seguridad');
    safe_redirect(u('/login'));
}

$user    = $auth->getUser();
$user_id = (int)$user['id'];
$db      = Database::getInstance();
$fullUser = $db->fetch("SELECT two_fa_method, two_fa_enabled, totp_enabled, totp_secret, backup_codes FROM users WHERE id=? LIMIT 1", [$user_id]);

$method  = $fullUser['two_fa_method'] ?? 'none';
$success = null;
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } else {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'activar_email') {
            $auth->set2FAMethod($user_id, 'email');
            $method  = 'email';
            $success = 'Verificación en 2 pasos por email activada.';

        } elseif ($accion === 'desactivar') {
            $auth->disableTOTP($user_id);
            $auth->set2FAMethod($user_id, 'none');
            $method  = 'none';
            $success = 'Verificación en 2 pasos desactivada.';

        } elseif ($accion === 'setup_totp') {
            $res = $auth->setupTOTP($user_id);
            if (!$res['success']) $error = $res['error'];

        } elseif ($accion === 'confirmar_totp') {
            $code = sanitize_string($_POST['totp_code'] ?? '', 6);
            $res  = $auth->confirmTOTP($user_id, $code);
            if ($res['success']) {
                $method  = 'totp';
                $success = '¡Google Authenticator activado! Guarda tus códigos de respaldo.';
                $_SESSION['new_backup_codes'] = $res['backups'];
            } else {
                $error = $res['error'];
            }
        }

        $fullUser = $db->fetch("SELECT two_fa_method, two_fa_enabled, totp_enabled, totp_secret, backup_codes FROM users WHERE id=? LIMIT 1", [$user_id]);
        $method   = $fullUser['two_fa_method'] ?? 'none';
    }
}

$totp_setup  = $_SESSION['totp_setup_secret'] ?? null;
$qr_url      = $totp_setup ? TOTP::getQRUrl($totp_setup, $user['email'], SITE_NAME) : null;
$new_backups = $_SESSION['new_backup_codes'] ?? null;
if ($new_backups) unset($_SESSION['new_backup_codes']);

$page_title = 'Seguridad 2FA | Latin Shop';
include INCLUDES_PATH . '/header.php';
?>

<div class="tbt-site-content" style="max-width:680px;margin:2rem auto;padding:0 1rem;">

  <div style="margin-bottom:2rem;">
    <a href="<?= u('/perfil') ?>" style="color:var(--tbt-txt-sub);font-size:.85rem;text-decoration:none;">← Volver al perfil</a>
    <h1 style="margin:.5rem 0 .25rem;color:var(--tbt-txt-white);font-size:1.5rem;">🔐 Verificación en 2 pasos</h1>
    <p style="color:var(--tbt-txt-sub);font-size:.9rem;">Agrega una capa extra de seguridad a tu cuenta.</p>
  </div>

  <?php if ($success): ?>
    <div style="background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.3);color:#4ade80;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1.5rem;font-size:.9rem;">
      ✓ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1.5rem;font-size:.9rem;">
      ✗ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <!-- ESTADO ACTUAL -->
  <div style="background:var(--tbt-bg-1);border:1px solid var(--tbt-bg-4);border-radius:14px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;">
    <p style="margin:0 0 .4rem;font-size:.8rem;color:var(--tbt-txt-muted);text-transform:uppercase;letter-spacing:.08em;">Estado actual</p>
    <?php if ($method === 'none'): ?>
      <p style="margin:0;color:#f87171;font-weight:600;">⚠ Sin verificación en 2 pasos — tu cuenta es más vulnerable</p>
    <?php elseif ($method === 'email'): ?>
      <p style="margin:0;color:#4ade80;font-weight:600;">✓ Activo — Código por Email</p>
    <?php else: ?>
      <p style="margin:0;color:#4ade80;font-weight:600;">✓ Activo — Google Authenticator (TOTP)</p>
    <?php endif; ?>
  </div>

  <!-- OPCIONES -->
  <div style="display:grid;gap:1rem;">

    <!-- EMAIL 2FA -->
    <div style="background:var(--tbt-bg-1);border:1px solid <?= $method==='email' ? 'rgba(74,222,128,.3)' : 'var(--tbt-bg-4)' ?>;border-radius:14px;padding:1.25rem 1.5rem;">
      <div style="display:flex;align-items:flex-start;gap:1rem;">
        <div style="font-size:2rem;line-height:1;">📧</div>
        <div style="flex:1;">
          <h3 style="margin:0 0 .3rem;color:var(--tbt-txt-white);font-size:1rem;">Código por Email</h3>
          <p style="margin:0 0 1rem;font-size:.85rem;color:var(--tbt-txt-sub);">
            Recibirás un código de 6 dígitos en tu correo <strong style="color:var(--tbt-txt-base);"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></strong> cada vez que inicies sesión.
          </p>
          <?php if ($method !== 'email'): ?>
            <form method="POST">
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="activar_email">
              <button type="submit" style="background:var(--tbt-jade);color:#fff;border:none;border-radius:8px;padding:.5rem 1.25rem;font-size:.85rem;font-weight:600;cursor:pointer;">
                Activar Email 2FA
              </button>
            </form>
          <?php else: ?>
            <span style="font-size:.82rem;color:#4ade80;">✓ Método activo</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- TOTP -->
    <div style="background:var(--tbt-bg-1);border:1px solid <?= $method==='totp' ? 'rgba(74,222,128,.3)' : 'var(--tbt-bg-4)' ?>;border-radius:14px;padding:1.25rem 1.5rem;">
      <div style="display:flex;align-items:flex-start;gap:1rem;">
        <div style="font-size:2rem;line-height:1;">📱</div>
        <div style="flex:1;">
          <h3 style="margin:0 0 .3rem;color:var(--tbt-txt-white);font-size:1rem;">Google Authenticator <span style="font-size:.75rem;background:rgba(245,166,35,.15);color:#f5a623;border-radius:4px;padding:.1rem .4rem;">Recomendado</span></h3>
          <p style="margin:0 0 1rem;font-size:.85rem;color:var(--tbt-txt-sub);">
            Usa una app como Google Authenticator o Authy. Genera códigos cada 30 segundos, no requiere internet ni SMS.
          </p>

          <?php if ($method === 'totp'): ?>
            <span style="font-size:.82rem;color:#4ade80;">✓ Método activo</span>

          <?php elseif ($totp_setup): ?>
            <!-- Mostrar QR para escanear -->
            <div style="background:var(--tbt-bg-2);border-radius:10px;padding:1.25rem;margin-bottom:1rem;text-align:center;">
              <p style="margin:0 0 1rem;font-size:.85rem;color:var(--tbt-txt-base);">1. Abre <strong>Google Authenticator</strong> o <strong>Authy</strong><br>2. Toca el <strong>+</strong> y escanea este QR:</p>
              <img src="<?= htmlspecialchars($qr_url, ENT_QUOTES, 'UTF-8') ?>" alt="QR TOTP" style="width:180px;height:180px;border-radius:8px;background:#fff;padding:8px;">
              <p style="margin:1rem 0 .5rem;font-size:.78rem;color:var(--tbt-txt-muted);">¿No puedes escanear? Ingresa este código manualmente:</p>
              <code style="font-size:.9rem;letter-spacing:.15em;color:#f5a623;background:var(--tbt-bg-base);padding:.3rem .7rem;border-radius:6px;">
                <?= htmlspecialchars($totp_setup, ENT_QUOTES, 'UTF-8') ?>
              </code>
            </div>
            <p style="margin:0 0 .75rem;font-size:.85rem;color:var(--tbt-txt-base);">3. Ingresa el código que muestra la app para confirmar:</p>
            <form method="POST" style="display:flex;gap:.75rem;align-items:center;">
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="confirmar_totp">
              <input type="text" name="totp_code" maxlength="6" inputmode="numeric"
                     placeholder="000000"
                     style="width:120px;padding:.5rem .75rem;background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);border-radius:8px;color:var(--tbt-txt-white);font-family:monospace;font-size:1.1rem;letter-spacing:.1em;text-align:center;" required>
              <button type="submit" style="background:var(--tbt-jade);color:#fff;border:none;border-radius:8px;padding:.5rem 1.25rem;font-size:.85rem;font-weight:600;cursor:pointer;">
                Confirmar y activar
              </button>
            </form>

          <?php else: ?>
            <form method="POST">
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="setup_totp">
              <button type="submit" style="background:rgba(245,166,35,.15);color:#f5a623;border:1px solid rgba(245,166,35,.3);border-radius:8px;padding:.5rem 1.25rem;font-size:.85rem;font-weight:600;cursor:pointer;">
                Configurar Google Authenticator
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- CÓDIGOS DE RESPALDO (si se acaban de generar) -->
    <?php if ($new_backups): ?>
    <div style="background:rgba(245,166,35,.05);border:1px solid rgba(245,166,35,.25);border-radius:14px;padding:1.25rem 1.5rem;">
      <h3 style="margin:0 0 .75rem;color:#f5a623;font-size:1rem;">🔑 Guarda tus códigos de respaldo</h3>
      <p style="margin:0 0 1rem;font-size:.85rem;color:var(--tbt-txt-sub);">
        Úsalos si pierdes acceso a tu app autenticadora. <strong style="color:#f87171;">Cada código solo funciona una vez.</strong>
      </p>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem;margin-bottom:1rem;">
        <?php foreach ($new_backups as $bc): ?>
          <code style="background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);border-radius:6px;padding:.4rem .5rem;font-size:.85rem;text-align:center;color:var(--tbt-txt-white);letter-spacing:.05em;">
            <?= htmlspecialchars($bc, ENT_QUOTES, 'UTF-8') ?>
          </code>
        <?php endforeach; ?>
      </div>
      <p style="margin:0;font-size:.78rem;color:var(--tbt-txt-muted);">Cópialos y guárdalos en un lugar seguro. No los volverás a ver.</p>
    </div>
    <?php endif; ?>

    <!-- DESACTIVAR -->
    <?php if ($method !== 'none'): ?>
    <div style="background:var(--tbt-bg-1);border:1px solid rgba(239,68,68,.2);border-radius:14px;padding:1.25rem 1.5rem;">
      <h3 style="margin:0 0 .4rem;color:#f87171;font-size:.95rem;">Desactivar verificación en 2 pasos</h3>
      <p style="margin:0 0 1rem;font-size:.82rem;color:var(--tbt-txt-muted);">Tu cuenta quedará protegida solo con contraseña.</p>
      <form method="POST" onsubmit="return confirm('¿Seguro que quieres desactivar el 2FA?')">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="desactivar">
        <button type="submit" style="background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:.45rem 1rem;font-size:.82rem;font-weight:600;cursor:pointer;">
          Desactivar 2FA
        </button>
      </form>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
