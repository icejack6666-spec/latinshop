<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();

if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_user_id'])) {
    safe_redirect(u('/login'));
}
if (time() - ($_SESSION['2fa_time'] ?? 0) > 600) {
    unset($_SESSION['2fa_pending'], $_SESSION['2fa_user_id'], $_SESSION['2fa_time'], $_SESSION['2fa_method']);
    $auth->setFlash('success', 'La sesión de verificación expiró. Vuelve a iniciar sesión.');
    safe_redirect(u('/login'));
}

$method = $_SESSION['2fa_method'] ?? 'email';
$error  = null;

if (isset($_POST['accion']) && $_POST['accion'] === 'reenviar') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } elseif ($method === 'email') {
        $res = $auth->send2FACode((int)$_SESSION['2fa_user_id']);
        if (!$res['success']) $error = $res['error'];
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } else {
        $code = sanitize_string($_POST['code'] ?? '', 10);
        $res  = $auth->verify2FA($code);
        if ($res['success']) {
            $redirect = $_SESSION['redirect_after_login'] ?? u('/');
            unset($_SESSION['redirect_after_login']);
            safe_redirect($redirect);
        } else {
            $error = $res['error'];
        }
    }
}

$page_title     = 'Verificación 2FA | Latin Shop';
$page_canonical = u('/verificar-2fa');
$extra_css      = ['auth-epic.css'];
include INCLUDES_PATH . '/header.php';
?>
<style>body{background:var(--tbt-bg-base);}.tbt-site-content{padding:0;}</style>

<div class="auth-epic-layout">

  <!-- IZQUIERDA -->
  <div class="auth-epic-left" style="background:linear-gradient(135deg,#020609 0%,#0a0600 100%);">
    <div class="auth-epic-grid"></div>
    <canvas id="auth-particles"></canvas>
    <div class="auth-orb auth-orb-1" style="background:radial-gradient(circle,rgba(245,166,35,.25),transparent 70%);"></div>
    <div class="auth-orb auth-orb-2" style="background:radial-gradient(circle,rgba(245,166,35,.15),transparent 70%);"></div>
    <span class="auth-rune auth-rune-1" style="color:rgba(245,166,35,.08);">🛡</span>
    <span class="auth-rune auth-rune-2" style="color:rgba(245,166,35,.06);">⚔</span>

    <div class="auth-epic-brand">
      <div class="twofa-shield-wrap">
        <div class="twofa-shield">
          <svg width="64" height="74" viewBox="0 0 120 140" fill="none">
            <defs>
              <linearGradient id="ag" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#fbbf24"/>
                <stop offset="50%" stop-color="#f5a623"/>
                <stop offset="100%" stop-color="#fbbf24"/>
              </linearGradient>
              <linearGradient id="ai" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="rgba(245,166,35,.2)"/>
                <stop offset="100%" stop-color="rgba(245,166,35,.05)"/>
              </linearGradient>
            </defs>
            <path d="M60 4 L108 22 L108 66 Q108 106 60 136 Q12 106 12 66 L12 22 Z" fill="url(#ai)" stroke="url(#ag)" stroke-width="2.5"/>
            <path d="M48 72 L48 62 Q48 52 60 52 Q72 52 72 62 L72 72 L48 72Z" fill="none" stroke="url(#ag)" stroke-width="2.5" stroke-linejoin="round"/>
            <rect x="44" y="72" width="32" height="24" rx="4" fill="rgba(245,166,35,.2)" stroke="url(#ag)" stroke-width="2"/>
            <circle cx="60" cy="84" r="4" fill="#f5a623"/>
          </svg>
        </div>
        <div class="twofa-pulse"></div>
      </div>

      <h1 class="auth-epic-title" style="font-size:2rem;background:linear-gradient(135deg,#fbbf24,#f5a623,#fde68a);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
        Doble<br>Autenticación
      </h1>
      <p class="auth-epic-subtitle" style="color:rgba(245,166,35,.5);">Capa extra de seguridad activa</p>

      <div class="vt-info-box" style="border-color:rgba(245,166,35,.15);background:rgba(245,166,35,.03);">
        <?php if ($method === 'totp'): ?>
          <p style="color:var(--tbt-txt-sub);font-size:.85rem;line-height:1.7;text-align:center;">
            Abre tu app autenticadora<br>
            <span style="color:var(--tbt-txt-white);font-family:var(--tbt-font-mono);font-size:1rem;">Google Authenticator / Authy</span><br>
            <span style="color:rgba(245,166,35,.7);font-size:.78rem;">El código cambia cada 30 segundos</span>
          </p>
        <?php else: ?>
          <p style="color:var(--tbt-txt-sub);font-size:.85rem;line-height:1.7;text-align:center;">
            Código enviado a tu correo<br>
            <span style="color:var(--tbt-txt-white);font-family:var(--tbt-font-mono);font-size:1rem;">
              <?php
                $db   = Database::getInstance();
                $u    = $db->fetch("SELECT email FROM users WHERE id=? LIMIT 1", [(int)$_SESSION['2fa_user_id']]);
                $em   = $u['email'] ?? '';
                $parts = explode('@', $em);
                $name  = $parts[0] ?? '';
                $dom   = $parts[1] ?? '';
                echo htmlspecialchars(substr($name,0,2) . str_repeat('*', max(0,strlen($name)-2)) . '@' . $dom, ENT_QUOTES, 'UTF-8');
              ?>
            </span>
          </p>
        <?php endif; ?>
      </div>

      <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:1.25rem;">
        <div style="display:flex;align-items:center;gap:.6rem;font-size:.78rem;color:var(--tbt-txt-sub);">
          <span style="color:#f5a623;">✓</span> Protege contra accesos no autorizados
        </div>
        <div style="display:flex;align-items:center;gap:.6rem;font-size:.78rem;color:var(--tbt-txt-sub);">
          <span style="color:#f5a623;">✓</span> <?= $method === 'totp' ? 'Código válido por 30 segundos' : 'El código expira en 10 minutos' ?>
        </div>
        <div style="display:flex;align-items:center;gap:.6rem;font-size:.78rem;color:var(--tbt-txt-sub);">
          <span style="color:#f5a623;">✓</span> <?= $method === 'totp' ? 'No requiere internet ni SMS' : 'Solo tú tienes acceso a tu correo' ?>
        </div>
      </div>
    </div>
  </div>

  <div class="auth-epic-right">
    <div class="auth-glass-card" style="border-color:rgba(245,166,35,.2);">

      <div style="position:absolute;top:0;left:10%;right:10%;height:1px;background:linear-gradient(90deg,transparent,rgba(245,166,35,.6),rgba(245,166,35,.4),transparent);border-radius:50%;"></div>

      <div class="auth-glass-logo">
        <div class="auth-glass-logo-icon" style="background:rgba(245,166,35,.1);border-color:rgba(245,166,35,.35);box-shadow:0 0 24px rgba(245,166,35,.2);">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"
                  fill="rgba(245,166,35,.3)" stroke="#f5a623" stroke-width="1.5"/>
            <text x="12" y="16" text-anchor="middle" fill="#f5a623" font-size="8" font-weight="700" font-family="monospace">2FA</text>
          </svg>
        </div>
        <span class="auth-glass-logo-title">Verificación en 2 pasos</span>
        <span class="auth-glass-logo-sub">
          <?= $method === 'totp' ? 'Código de tu app autenticadora' : 'Ingresa el código de tu correo' ?>
        </span>
      </div>

      <?php if (ENV === 'development' && !empty($_SESSION['2fa_dev_code'])): ?>
        <div style="background:rgba(245,166,35,.1);border:1px solid rgba(245,166,35,.4);color:#f5a623;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.85rem;">
          <strong>🛠 DEV</strong> — Email no enviado. Código:
          <strong style="font-family:monospace;font-size:1.1rem;letter-spacing:.15em;"><?= htmlspecialchars($_SESSION['2fa_dev_code'], ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="auth-glass-alert auth-glass-alert--error">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= u('/verificar-2fa') ?>" id="form-2fa">
        <?= csrf_field() ?>

        <div class="vt-digits-wrap">
          <div class="vt-digits" id="code-inputs">
            <?php for ($i = 0; $i < 6; $i++): ?>
              <input type="text" class="vt-digit-epic vt-digit-amber"
                     maxlength="1" inputmode="numeric" pattern="[0-9]"
                     autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
                     data-index="<?= $i ?>">
            <?php endfor; ?>
          </div>
          <input type="hidden" name="code" id="code-hidden">
        </div>

        <?php if ($method === 'email'): ?>
        <div class="vt-timer-epic" id="vt-timer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="color:#f5a623;"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
          El código expira en <span id="timer-count" style="color:#f5a623;font-weight:700;font-family:var(--tbt-font-mono);">10:00</span>
        </div>
        <?php else: ?>
        <p style="text-align:center;font-size:.8rem;color:var(--tbt-txt-muted);margin:.75rem 0 1rem;">
          ¿No tienes acceso? Usa un <strong style="color:#f5a623;">código de respaldo</strong>
        </p>
        <?php endif; ?>

        <button type="submit" class="auth-glass-submit" id="btn-verify" disabled
                style="background:linear-gradient(135deg,#f5a623 0%,#d97706 50%,#f5a623 100%);background-size:200% 100%;color:var(--tbt-bg-base);">
          Confirmar acceso
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
        </button>
      </form>

      <?php if ($method === 'email'): ?>
      <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid rgba(245,166,35,.08);text-align:center;">
        <p style="font-size:.8rem;color:var(--tbt-txt-muted);margin-bottom:.5rem;">¿No recibiste el código?</p>
        <form method="POST" action="<?= u('/verificar-2fa') ?>" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="reenviar">
          <button type="submit" id="btn-resend"
                  style="background:none;border:1px solid rgba(245,166,35,.2);color:#f5a623;
                         font-size:.8rem;font-weight:600;padding:.4rem 1rem;border-radius:8px;
                         cursor:pointer;transition:all .2s;font-family:var(--tbt-font-body);">
            Reenviar código
          </button>
        </form>
      </div>
      <?php endif; ?>

      <div class="auth-glass-footer">
        <a href="<?= u('/login') ?>" onclick="return confirm('¿Cancelar el inicio de sesión?')" class="auth-glass-link">
          ← Cancelar e ir al login
        </a>
      </div>
    </div>
  </div>

</div>

<style>
body{background:var(--tbt-bg-base);}.tbt-site-content{padding:0;}
.twofa-shield-wrap{position:relative;width:100px;height:100px;margin:0 auto 2rem;display:flex;align-items:center;justify-content:center;}
.twofa-shield{position:relative;z-index:2;filter:drop-shadow(0 0 20px rgba(245,166,35,.3));animation:shieldGlowAmber 3s ease-in-out infinite alternate;}
@keyframes shieldGlowAmber{0%{filter:drop-shadow(0 0 15px rgba(245,166,35,.3));}100%{filter:drop-shadow(0 0 35px rgba(245,166,35,.6));}}
.twofa-pulse{position:absolute;inset:-15px;border-radius:50%;border:1px solid rgba(245,166,35,.2);animation:ringPulse 2s ease-out infinite;}
@keyframes ringPulse{0%{opacity:0;transform:scale(.8);}50%{opacity:1;}100%{opacity:0;transform:scale(1.3);}}
.vt-digits-wrap{margin:1.25rem 0;}
.vt-digits{display:flex;gap:.5rem;justify-content:center;}
.vt-digit-epic{width:50px;height:58px;text-align:center;font-size:1.5rem;font-weight:700;font-family:var(--tbt-font-mono);background:rgba(255,255,255,.03);border:1px solid rgba(232,96,44,.15);border-radius:12px;color:var(--tbt-txt-white);outline:none;transition:border-color .2s,box-shadow .2s,background .2s;}
.vt-digit-amber:focus{border-color:rgba(245,166,35,.5)!important;box-shadow:0 0 0 3px rgba(245,166,35,.1)!important;background:rgba(245,166,35,.04)!important;}
.vt-digit-amber.filled{border-color:rgba(245,166,35,.4)!important;background:rgba(245,166,35,.06)!important;}
.vt-timer-epic{display:flex;align-items:center;justify-content:center;gap:.4rem;font-size:.78rem;color:var(--tbt-txt-muted);text-align:center;margin:.75rem 0 1rem;font-family:var(--tbt-font-mono);}
.vt-timer-epic.expired{color:#f87171;}
.vt-info-box{background:rgba(255,255,255,.03);border:1px solid rgba(232,96,44,.12);border-radius:12px;padding:1rem 1.25rem;margin:1.5rem 0;}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}
.vt-digit-epic.error{border-color:rgba(239,68,68,.5);animation:shake .3s ease;}
@media(max-width:480px){.vt-digit-epic{width:42px;height:50px;font-size:1.2rem;}}
</style>

<script src="<?= ASSETS_URL ?>/js/auth-2fa.js" defer
        data-method="<?= htmlspecialchars($method, ENT_QUOTES, 'UTF-8') ?>"
        data-timer="<?= $method === 'email' ? '600' : '0' ?>"></script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
